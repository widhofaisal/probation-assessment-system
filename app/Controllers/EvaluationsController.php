<?php

namespace App\Controllers;

use App\Models\EvaluationModel;
use App\Models\EvaluationDetailModel;
use App\Models\EmployeeModel;

class EvaluationsController extends BaseController
{
    protected $evaluationModel;
    protected $evaluationDetailModel;
    protected $employeeModel;

    public function __construct()
    {
        $this->evaluationModel = new EvaluationModel();
        $this->evaluationDetailModel = new EvaluationDetailModel();
        $this->employeeModel = new EmployeeModel();
    }

    /**
     * List evaluations (for HRD)
     */
    public function index()
    {
        if (session()->get('role') !== 'hrd') {
            return redirect()->to('/auth/login');
        }

        $evaluations = $this->evaluationModel->select('penilaian.*, employees.nama, employees.nik, users.nama as team_leader_nama')
            ->join('employees', 'penilaian.employee_id = employees.id')
            ->join('users', 'penilaian.team_leader_id = users.id')
            ->orderBy('penilaian.tanggal_penilaian', 'DESC')
            ->findAll();

        $data = [
            'title' => 'Daftar Penilaian',
            'evaluations' => $evaluations,
        ];

        return view('Evaluations/index', $data);
    }

    /**
     * Save evaluation (all steps)
     */
    public function store()
    {
        if (session()->get('role') !== 'team-leader') {
            return redirect()->to('/auth/login');
        }

        $employeeId = $this->request->getPost('employee_id');
        $catatanTeamLeader = $this->request->getPost('catatan_team_leader');
        $scores = $this->request->getPost('scores'); // Array of scores

        // Validate
        if (!$employeeId || !is_array($scores) || empty($scores)) {
            return redirect()->back()->withInput()->with('error', 'Data tidak lengkap');
        }

        // Verify employee belongs to this team leader
        $employee = $this->employeeModel->find($employeeId);
        if (!$employee || (int)$employee['team_leader_id'] !== (int)session()->get('user_id')) {
            return redirect()->back()->with('error', 'Akses ditolak');
        }

        // Calculate average score
        $averageScore = round(array_sum($scores) / count($scores), 2);

        // Create evaluation
        $evaluationData = [
            'employee_id' => $employeeId,
            'team_leader_id' => session()->get('user_id'),
            'tanggal_penilaian' => date('Y-m-d H:i:s'),
            'nilai_total' => $averageScore,
            'status' => 'submitted',
            'catatan_team_leader' => $catatanTeamLeader,
        ];

        try {
            $evaluationId = $this->evaluationModel->insert($evaluationData);

            // Insert details
            $details = $this->request->getPost('details');
            if (is_array($details) && !empty($details)) {
                $this->evaluationDetailModel->insertDetails($evaluationId, $details);
            }

            // Log audit
            $this->logAudit('CREATE', 'penilaian', $evaluationId, null, $evaluationData);

            return redirect()->to('/evaluations/' . $evaluationId)
                ->with('success', 'Penilaian berhasil disimpan');

        } catch (\Exception $e) {
            return redirect()->back()->withInput()
                ->with('error', 'Gagal menyimpan penilaian: ' . $e->getMessage());
        }
    }

    /**
     * View evaluation result
     */
    public function show(int $id)
    {
        $evaluation = $this->evaluationModel->getWithDetails($id);

        if (!$evaluation) {
            return redirect()->back()->with('error', 'Penilaian tidak ditemukan');
        }

        // Check authorization
        $role = session()->get('role');
        $userId = session()->get('user_id');

        if ($role === 'team-leader' && $evaluation['team_leader_id'] != $userId) {
            return redirect()->back()->with('error', 'Akses ditolak');
        }

        if ($role === 'probationary-employee') {
            $employee = $this->employeeModel->findByNik(session()->get('nik'));
            if (!$employee || $employee['id'] != $evaluation['employee_id']) {
                return redirect()->back()->with('error', 'Akses ditolak');
            }
        }

        $details = $this->evaluationDetailModel->getByEvaluationGrouped($id);
        $categoryScores = $this->evaluationDetailModel->getCategoryAverages($id);

        $data = [
            'title' => 'Hasil Penilaian',
            'evaluation' => $evaluation,
            'details' => $details,
            'categoryScores' => $categoryScores,
        ];

        return view('Evaluations/result', $data);
    }

    /**
     * Edit evaluation
     */
    public function edit(int $id)
    {
        if (session()->get('role') !== 'team-leader') {
            return redirect()->to('/auth/login');
        }

        $evaluation = $this->evaluationModel->getWithDetails($id);

        if (!$evaluation || $evaluation['team_leader_id'] != session()->get('user_id')) {
            return redirect()->back()->with('error', 'Akses ditolak');
        }

        $details = $this->evaluationDetailModel->getByEvaluationGrouped($id);

        $data = [
            'title' => 'Edit Penilaian',
            'evaluation' => $evaluation,
            'details' => $details,
            'step' => 1,
        ];

        return view('Evaluations/form_edit', $data);
    }

    /**
     * Update evaluation
     */
    public function update(int $id)
    {
        if (session()->get('role') !== 'team-leader') {
            return redirect()->to('/auth/login');
        }

        $evaluation = $this->evaluationModel->find($id);

        if (!$evaluation || $evaluation['team_leader_id'] != session()->get('user_id')) {
            return redirect()->back()->with('error', 'Akses ditolak');
        }

        $scores = $this->request->getPost('scores');
        if (!is_array($scores) || empty($scores)) {
            return redirect()->back()->with('error', 'Data tidak lengkap');
        }

        $averageScore = round(array_sum($scores) / count($scores), 2);

        $updateData = [
            'nilai_total' => $averageScore,
            'catatan_team_leader' => $this->request->getPost('catatan_team_leader'),
        ];

        if ($this->evaluationModel->update($id, $updateData)) {
            // Delete old details and insert new ones
            $db = \Config\Database::connect();
            $db->table('penilaian_detail')->where('penilaian_id', $id)->delete();

            $details = $this->request->getPost('details');
            if (is_array($details)) {
                $this->evaluationDetailModel->insertDetails($id, $details);
            }

            // Log audit
            $this->logAudit('UPDATE', 'penilaian', $id, (array)$evaluation, $updateData);

            return redirect()->to('/evaluations/' . $id)
                ->with('success', 'Penilaian berhasil diperbarui');
        }

        return redirect()->back()->with('error', 'Gagal memperbarui penilaian');
    }

    /**
     * Delete evaluation
     */
    public function destroy(int $id)
    {
        if (session()->get('role') !== 'team-leader') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Akses ditolak']);
        }

        $evaluation = $this->evaluationModel->find($id);

        if (!$evaluation || $evaluation['team_leader_id'] != session()->get('user_id')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Akses ditolak']);
        }

        $db = \Config\Database::connect();
        $db->table('penilaian_detail')->where('penilaian_id', $id)->delete();
        $this->evaluationModel->delete($id);

        $this->logAudit('DELETE', 'penilaian', $id, (array)$evaluation, null);

        return $this->response->setJSON(['success' => true]);
    }

    /**
     * Get standard evaluation aspects
     */
    public function getAspects()
    {
        $aspects = EvaluationDetailModel::getStandardAspects();

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $aspects,
        ]);
    }

    /**
     * Helper: Log audit
     */
    protected function logAudit(string $action, string $table, int $recordId, ?array $oldData, ?array $newData)
    {
        $auditModel = model('AuditLogModel');
        $auditModel->logAction(
            session()->get('user_id'),
            $action,
            $table,
            $recordId,
            $oldData,
            $newData,
            "{$action} evaluation record"
        );
    }
}
