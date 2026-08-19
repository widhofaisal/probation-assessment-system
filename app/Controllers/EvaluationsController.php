<?php

namespace App\Controllers;

use App\Libraries\PdfCache;
use App\Models\EvaluationModel;
use App\Models\EvaluationDecisionModel;
use App\Models\EvaluationDetailModel;
use App\Models\EmployeeModel;

class EvaluationsController extends BaseController
{
    protected $evaluationModel;
    protected $evaluationDetailModel;
    protected $evaluationDecisionModel;
    protected $employeeModel;

    public function __construct()
    {
        $this->evaluationModel = new EvaluationModel();
        $this->evaluationDetailModel = new EvaluationDetailModel();
        $this->evaluationDecisionModel = new EvaluationDecisionModel();
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
            ->orderBy('penilaian.nomor_penilaian', 'ASC')
            ->findAll();

        $evalsByEmployee = [];
        foreach ($evaluations as $eval) {
            $eid   = $eval['employee_id'];
            $nomor = (int)($eval['nomor_penilaian'] ?? 1);
            if (!isset($evalsByEmployee[$eid])) {
                $evalsByEmployee[$eid] = [
                    'employee_id'      => $eid,
                    'nik'              => $eval['nik'],
                    'nama'             => $eval['nama'],
                    'team_leader_nama' => $eval['team_leader_nama'],
                    'eval1'            => null,
                    'eval2'            => null,
                ];
            }
            if ($nomor === 1 && !$evalsByEmployee[$eid]['eval1']) $evalsByEmployee[$eid]['eval1'] = $eval;
            if ($nomor === 2 && !$evalsByEmployee[$eid]['eval2']) $evalsByEmployee[$eid]['eval2'] = $eval;
        }

        // Keputusan HRD menempel pada lembar ke-2 — diambil sekaligus agar tidak
        // ada query per baris tabel.
        $eval2Ids = [];
        foreach ($evalsByEmployee as $grp) {
            if ($grp['eval2']) $eval2Ids[] = (int)$grp['eval2']['id'];
        }
        $keputusanMap = $this->evaluationDecisionModel->mapByEvaluations($eval2Ids);
        foreach ($evalsByEmployee as $eid => $grp) {
            $evalsByEmployee[$eid]['keputusan'] = $grp['eval2']
                ? ($keputusanMap[(int)$grp['eval2']['id']] ?? null)
                : null;
        }

        $data = [
            'title'           => 'Daftar Penilaian',
            'evaluations'     => $evaluations,
            'evalsByEmployee' => array_values($evalsByEmployee),
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

        // Hitung nomor penilaian (1 atau 2)
        $existingCount = $this->evaluationModel->where('employee_id', $employeeId)->countAllResults();
        $nomorPenilaian = min($existingCount + 1, 2);

        // Calculate average score
        $averageScore = round(array_sum($scores) / count($scores), 2);

        // Create evaluation
        $evaluationData = [
            'employee_id'              => $employeeId,
            'nomor_penilaian'          => $nomorPenilaian,
            'team_leader_id'           => session()->get('user_id'),
            'tanggal_penilaian'        => date('Y-m-d H:i:s'),
            'tanggal_mulai_penilaian'  => $this->request->getPost('tanggal_mulai_penilaian') ?: null,
            'tanggal_selesai_penilaian'=> $this->request->getPost('tanggal_selesai_penilaian') ?: null,
            'nilai_total'              => $averageScore,
            'status'                   => 'submitted',
            'catatan_team_leader'      => $catatanTeamLeader,
        ];

        try {
            $evaluationId = $this->evaluationModel->insert($evaluationData);

            // Insert details
            $details = $this->request->getPost('details');
            if (is_array($details) && !empty($details)) {
                $this->evaluationDetailModel->insertDetails($evaluationId, $details);
            }

            // Invalidate the employee's combined PDF — it must now include this new sheet
            PdfCache::forget(PdfCache::keyForEmployee((int)$employeeId));

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

            $this->evaluationModel->markViewed($id, (int)$employee['id']);
            $evaluation = $this->evaluationModel->getWithDetails($id);
        }

        $details = $this->evaluationDetailModel->getByEvaluationGrouped($id);
        $categoryScores = $this->evaluationDetailModel->getCategoryAverages($id);

        $data = [
            'title'          => 'Hasil Penilaian Ke-' . ($evaluation['nomor_penilaian'] ?? 1),
            'evaluation'     => $evaluation,
            'details'        => $details,
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

            // Invalidate PDF cache for this eval and the employee's combined PDF
            PdfCache::forget(PdfCache::keyForEvaluation($id));
            PdfCache::forget(PdfCache::keyForEmployee((int)$evaluation['employee_id']));

            // Log audit
            $this->logAudit('UPDATE', 'penilaian', $id, (array)$evaluation, $updateData);

            return redirect()->to('/evaluations/' . $id)
                ->with('success', 'Penilaian berhasil diperbarui');
        }

        return redirect()->back()->with('error', 'Gagal memperbarui penilaian');
    }

    /**
     * Simpan kotak "(Diisi oleh Dept. HRD)" milik lembar penilaian ke-2.
     *
     * Ini gerbang terakhir masa probation: Team Leader menutup penilaian ke-2,
     * lalu HRD mengisi keputusan di sini, dan baru dari sinilah employees.status
     * berubah menjadi lulus / tidak-lulus / warning. Form data karyawan sudah
     * tidak bisa lagi mengubah status (lihat EmployeesController::update()).
     */
    public function storeKeputusan(int $id)
    {
        if (session()->get('role') !== 'hrd') {
            return redirect()->to('/auth/login');
        }

        $evaluation = $this->evaluationModel->find($id);

        if (!$evaluation) {
            return redirect()->back()->with('error', 'Penilaian tidak ditemukan');
        }

        if ((int)($evaluation['nomor_penilaian'] ?? 1) !== 2) {
            return redirect()->back()->with('error', 'Keputusan HRD hanya diisi pada penilaian ke-2');
        }

        if (($evaluation['status'] ?? '') !== 'submitted') {
            return redirect()->back()->with('error', 'Penilaian ke-2 belum diselesaikan Team Leader');
        }

        $tanggalDiangkat = $this->request->getPost('tanggal_diangkat') ?: null;
        $tanggalDiakhiri = $this->request->getPost('tanggal_diakhiri') ?: null;
        $lainLain        = trim((string)$this->request->getPost('lain_lain'));
        $statusAkhir     = (string)$this->request->getPost('status_akhir');
        $nomorSk         = trim((string)$this->request->getPost('nomor_sk'));

        if (!in_array($statusAkhir, EvaluationDecisionModel::STATUS_AKHIR, true)) {
            return redirect()->back()->with('error', 'Status akhir belum dipilih');
        }

        // Keputusan Lulus melahirkan Surat Keputusan pengangkatan, dan surat itu
        // berbunyi "Terhitung sejak tanggal ..." dengan nomor agenda di kepalanya
        // — keduanya karena itu wajib di sini, bukan diisi belakangan. Status lain
        // tidak menerbitkan surat apa pun, jadi tidak diminta.
        if ($statusAkhir === 'lulus') {
            if (!$tanggalDiangkat) {
                return redirect()->back()->with('error',
                    'Status Lulus butuh tanggal pengangkatan — tanggal itu yang tercetak di Surat Keputusan');
            }

            if ($nomorSk === '') {
                return redirect()->back()->with('error',
                    'Status Lulus butuh Nomor SK sesuai buku agenda perusahaan');
            }

            if (!preg_match('/^[A-Za-z0-9.\/-]{1,20}$/', $nomorSk)) {
                return redirect()->back()->with('error',
                    'Nomor SK maksimal 20 karakter, hanya huruf, angka, titik, garis miring, dan strip');
            }
        }

        if (!$tanggalDiangkat && !$tanggalDiakhiri && $lainLain === '') {
            return redirect()->back()
                ->with('error', 'Isi minimal satu baris keputusan (diangkat / diakhiri / lain-lain)');
        }

        if (mb_strlen($lainLain) > EvaluationDecisionModel::MAX_LAIN_LAIN) {
            return redirect()->back()->with('error',
                'Keterangan "Lain-lain" maksimal ' . EvaluationDecisionModel::MAX_LAIN_LAIN . ' karakter');
        }

        $employeeId = (int)$evaluation['employee_id'];
        $existing   = $this->evaluationDecisionModel->getByEvaluation($id);

        // Tanggal surat dibekukan saat SK pertama kali terbit: nomor dan tanggal
        // pada surat yang sudah dipegang Team Member tidak boleh bergeser hanya
        // karena keputusannya disunting lagi di bulan lain.
        $tanggalSk = $existing['tanggal_sk'] ?? null;
        if ($statusAkhir === 'lulus' && empty($tanggalSk)) {
            $tanggalSk = date('Y-m-d');
        }

        $keputusanData = [
            'penilaian_id'     => $id,
            'employee_id'      => $employeeId,
            'tanggal_diangkat' => $tanggalDiangkat,
            'tanggal_diakhiri' => $tanggalDiakhiri,
            'lain_lain'        => $lainLain !== '' ? $lainLain : null,
            'nomor_sk'         => $nomorSk !== '' ? $nomorSk : null,
            'tanggal_sk'       => $tanggalSk,
            'status_akhir'     => $statusAkhir,
            'hrd_id'           => session()->get('user_id'),
        ];

        try {
            if ($existing) {
                $this->evaluationDecisionModel->update($existing['id'], $keputusanData);
                $keputusanId = (int)$existing['id'];
            } else {
                $keputusanId = (int)$this->evaluationDecisionModel->insert($keputusanData);
            }

            // Status akhir karyawan mengikuti keputusan ini.
            $employee = $this->employeeModel->find($employeeId);
            $this->employeeModel->skipValidation(true)->update($employeeId, ['status' => $statusAkhir]);

            // Kotak HRD ikut tercetak di PDF, jadi hasil render lama sudah basi.
            PdfCache::forget(PdfCache::keyForEvaluation($id));
            PdfCache::forget(PdfCache::keyForEmployee($employeeId));
            // Nomor, tanggal, dan nama pada Surat Keputusan ikut berubah.
            PdfCache::forget(PdfCache::keyForSk($employeeId));

            $this->logAudit($existing ? 'UPDATE' : 'CREATE', 'penilaian_keputusan',
                            $keputusanId, $existing, $keputusanData);

            $nama = $employee['nama'] ?? 'Team Member';

            return redirect()->to('/evaluations')->with('success',
                'Keputusan HRD tersimpan. Status ' . $nama . ' menjadi '
                . EvaluationDecisionModel::statusLabel($statusAkhir) . '.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan keputusan: ' . $e->getMessage());
        }
    }

    /**
     * Apakah PDF ini perlu dikonfirmasi dulu karena Keputusan HRD belum diisi?
     *
     * Dipanggil pdfDownload() di layout sebelum berkas diunduh — berlaku untuk
     * ketiga role, karena ketiganya bisa mengunduh lembar ke-2. Lembar yang
     * belum diputuskan tetap boleh diunduh; yang penting pengunduh tahu bahwa
     * kotak "(Diisi oleh Dept. HRD)" akan tercetak kosong.
     *
     * Terima ?eval=ID (satu lembar) atau ?employee=ID (PDF gabungan). Jawabannya
     * hanya boolean "ada keputusan atau belum", jadi cukup dibatasi ke pengguna
     * yang sudah login — kontrol akses berkasnya sendiri tetap di ReportsController.
     */
    public function keputusanStatus()
    {
        if (!session()->has('user_id')) {
            return $this->response->setStatusCode(401)->setJSON(['perlu_konfirmasi' => false]);
        }

        $evalId     = (int) $this->request->getGet('eval');
        $employeeId = (int) $this->request->getGet('employee');

        $eval2 = null;

        if ($evalId) {
            $eval = $this->evaluationModel->find($evalId);
            // Lembar ke-1 tidak punya kotak HRD — tidak ada yang perlu dikonfirmasi.
            if ($eval && (int)($eval['nomor_penilaian'] ?? 1) === 2) {
                $eval2 = $eval;
            }
        } elseif ($employeeId) {
            $eval2 = $this->evaluationModel
                ->where('employee_id', $employeeId)
                ->where('nomor_penilaian', 2)
                ->first();
        }

        // Belum ada lembar ke-2 sama sekali → belum waktunya HRD memutuskan.
        if (!$eval2) {
            return $this->response->setJSON(['perlu_konfirmasi' => false]);
        }

        $keputusan = $this->evaluationDecisionModel->getByEvaluation((int) $eval2['id']);

        return $this->response->setJSON(['perlu_konfirmasi' => $keputusan === null]);
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

        // Invalidate PDF cache
        PdfCache::forget(PdfCache::keyForEvaluation($id));
        PdfCache::forget(PdfCache::keyForEmployee((int)$evaluation['employee_id']));

        $this->logAudit('DELETE', 'penilaian', $id, (array)$evaluation, null);

        return $this->response->setJSON(['success' => true]);
    }

    /**
     * Hasil evaluasi untuk probationary employee (diri sendiri)
     */
    public function myEvaluations()
    {
        if (session()->get('role') !== 'probationary-employee') {
            return redirect()->to('/auth/login');
        }

        $employee = $this->employeeModel->findByNik(session()->get('nik'));

        if (!$employee) {
            return view('Dashboard/member_no_data', [
                'title' => 'Hasil Evaluasi',
                'user'  => session()->get(),
            ]);
        }

        // Halaman ini menampilkan nilai total, rincian per aspek, dan catatan
        // Team Leader secara langsung — jadi membukanya memang sudah berarti
        // "melihat hasil", dan semua penilaian yang tampil ikut tertandai.
        $unmarked = $this->evaluationModel
            ->select('id')
            ->where('employee_id', $employee['id'])
            ->where('dilihat_at', null)
            ->findAll();

        foreach ($unmarked as $row) {
            $this->evaluationModel->markViewed((int)$row['id'], (int)$employee['id']);
        }

        $evaluations = $this->evaluationModel
            ->select('penilaian.*, users.nama as team_leader_nama')
            ->join('users', 'penilaian.team_leader_id = users.id', 'left')
            ->where('penilaian.employee_id', $employee['id'])
            ->orderBy('penilaian.tanggal_penilaian', 'DESC')
            ->findAll();

        // Ambil detail per penilaian (grouped by category)
        $evalDetails = [];
        foreach ($evaluations as $eval) {
            $evalDetails[$eval['id']] = $this->evaluationDetailModel->getByEvaluationGrouped($eval['id']);
        }

        $data = [
            'title'       => 'Hasil Evaluasi',
            'user'        => session()->get(),
            'employee'    => $employee,
            'evaluations' => $evaluations,
            'evalDetails' => $evalDetails,
        ];

        return view('Evaluations/my', $data);
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
