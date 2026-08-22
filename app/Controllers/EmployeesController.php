<?php

namespace App\Controllers;

use App\Models\EmployeeModel;
use App\Models\UserModel;

class EmployeesController extends BaseController
{
    protected $employeeModel;
    protected $userModel;

    public function __construct()
    {
        $this->employeeModel = new EmployeeModel();
        $this->userModel = new UserModel();
    }

    /**
     * List all employees (for HRD)
     */
    public function index()
    {
        if (!$this->checkAuthAndRole('hrd')) {
            return redirect()->to('/auth/login');
        }

        $department = $this->request->getGet('department');
        $status = $this->request->getGet('status');

        $query = $this->employeeModel;

        if ($department) {
            $query = $query->where('departemen', $department);
        }

        if ($status) {
            $query = $query->where('status', $status);
        }

        $employees   = $query->findAll();
        $departments = $this->employeeModel->distinct()->select('departemen')->findAll();
        $teamLeaders = $this->userModel->getTeamLeaders();

        // Ambil jumlah penilaian per karyawan
        $db = \Config\Database::connect();
        $evalCounts = $db->table('penilaian')
            ->select('employee_id, COUNT(*) AS total_penilaian')
            ->groupBy('employee_id')
            ->get()->getResultArray();
        $evalCountMap = array_column($evalCounts, 'total_penilaian', 'employee_id');

        foreach ($employees as &$emp) {
            $emp['total_penilaian'] = (int)($evalCountMap[$emp['id']] ?? 0);
        }
        unset($emp);

        $data = [
            'title' => 'Data Team Member',
            'employees' => $employees,
            'departments' => $departments,
            'teamLeaders' => $teamLeaders,
            'selectedDepartment' => $department,
            'selectedStatus' => $status,
        ];

        return view('Employees/index', $data);
    }

    /**
     * Show employee detail
     */
    public function show(int $id)
    {
        if (!$this->checkAuthAndRole('hrd')) {
            return redirect()->to('/auth/login');
        }

        $employee = $this->employeeModel->find($id);

        if (!$employee) {
            return redirect()->back()->with('error', 'Team Member tidak ditemukan');
        }

        $data = [
            'title' => 'Detail Team Member',
            'employee' => $employee,
        ];

        return view('Employees/show', $data);
    }

    /**
     * Store new employee
     */
    public function store()
    {
        if (!$this->checkAuthAndRole('hrd')) {
            return redirect()->to('/auth/login');
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'nik' => 'required|is_unique[employees.nik]',
            'nama' => 'required',
            'departemen' => 'required',
            'posisi' => 'required',
            'email' => 'permit_empty|valid_email',
            'tanggal_masuk' => 'permit_empty|valid_date',
            'mulai_probation' => 'required|valid_date',
            'team_leader_id' => 'permit_empty|integer',
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $data = [
            'nik'             => $this->request->getPost('nik'),
            'nama'            => $this->request->getPost('nama'),
            'departemen'      => $this->request->getPost('departemen'),
            'posisi'          => $this->request->getPost('posisi'),
            'email'           => $this->request->getPost('email'),
            'tanggal_masuk'   => $this->request->getPost('tanggal_masuk'),
            'mulai_probation' => $this->request->getPost('mulai_probation'),
            'akhir_probation' => EmployeeModel::calculateProbationEnd($this->request->getPost('mulai_probation')),
            'status'          => 'pending',
            'team_leader_id'  => $this->request->getPost('team_leader_id') ?: null,
            'jenis_kelamin'   => $this->request->getPost('jenis_kelamin') ?: null,
            'tanggal_lahir'   => $this->request->getPost('tanggal_lahir') ?: null,
            'alamat'          => $this->request->getPost('alamat') ?: null,
            'created_by'      => session()->get('user_id'),
        ];

        $db = \Config\Database::connect();
        $db->transStart();

        $employeeId = $this->employeeModel->insert($data);

        // Buat akun login otomatis dengan password awal yang acak. Sebelumnya
        // password disamakan dengan NIK, padahal NIK sekaligus dipakai sebagai
        // username - jadi akun karyawan baru bisa dimasuki siapa pun yang tahu
        // NIK-nya. Nilainya hanya ditampilkan sekali di pesan sukses.
        $nikValue     = $this->request->getPost('nik');
        $passwordAwal = null;
        $existingUser = $this->userModel->where('nik', $nikValue)->first();
        if (!$existingUser) {
            $passwordAwal = UserModel::generatePassword();
            $this->userModel->skipValidation(true)->insert([
                'nik'           => $nikValue,
                'nama'          => $this->request->getPost('nama'),
                'email'         => $this->request->getPost('email') ?: null,
                'password_hash' => UserModel::hashPassword($passwordAwal),
                'role'          => 'probationary-employee',
                'departemen'    => $this->request->getPost('departemen'),
                'posisi'        => $this->request->getPost('posisi'),
                'jenis_kelamin' => $this->request->getPost('jenis_kelamin') ?: null,
                'tanggal_lahir' => $this->request->getPost('tanggal_lahir') ?: null,
                'alamat'        => $this->request->getPost('alamat') ?: null,
            ]);
        }

        $db->transComplete();

        if ($db->transStatus()) {
            $this->logAudit('CREATE', 'employees', $this->employeeModel->getInsertID(), null, $data);

            $pesan = 'Team Member berhasil ditambahkan.';
            if ($passwordAwal !== null) {
                $pesan .= ' Password login: ' . $passwordAwal
                    . ' - catat sekarang, password ini tidak bisa dilihat lagi.';
            } else {
                $pesan .= ' Akun login dengan NIK tersebut sudah ada sebelumnya,'
                    . ' jadi passwordnya tidak diubah.';
            }

            return redirect()->to('/employees')->with(
                $passwordAwal !== null ? 'success_sekali' : 'success',
                $pesan
            );
        }

        return redirect()->back()->withInput()->with('error', 'Gagal menambahkan karyawan');
    }

    /**
     * Update employee
     */
    public function update(int $id)
    {
        if (!$this->checkAuthAndRole('hrd')) {
            return redirect()->to('/auth/login');
        }

        $employee = $this->employeeModel->find($id);

        if (!$employee) {
            return redirect()->back()->with('error', 'Team Member tidak ditemukan');
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'nik' => "required|is_unique[employees.nik,id,{$id}]",
            'nama' => 'required',
            'departemen' => 'required',
            'posisi' => 'required',
            'email' => 'permit_empty|valid_email',
            'tanggal_masuk' => 'permit_empty|valid_date',
            'mulai_probation' => 'required|valid_date',
            'team_leader_id' => 'permit_empty|integer',
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        // `status` sengaja tidak diambil dari request. Status probation hanya boleh
        // berubah lewat Keputusan HRD pada penilaian ke-2
        // (EvaluationsController::storeKeputusan()), supaya karyawan tidak bisa
        // dinyatakan lulus/tidak lulus sebelum kotak "(Diisi oleh Dept. HRD)" diisi.
        $newData = [
            'nik'           => $this->request->getPost('nik'),
            'nama'          => $this->request->getPost('nama'),
            'departemen'    => $this->request->getPost('departemen'),
            'posisi'        => $this->request->getPost('posisi'),
            'email'         => $this->request->getPost('email'),
            'tanggal_masuk' => $this->request->getPost('tanggal_masuk'),
            'mulai_probation' => $this->request->getPost('mulai_probation'),
            'team_leader_id' => $this->request->getPost('team_leader_id') ?: null,
            'jenis_kelamin'  => $this->request->getPost('jenis_kelamin') ?: null,
            'tanggal_lahir'  => $this->request->getPost('tanggal_lahir') ?: null,
            'alamat'         => $this->request->getPost('alamat') ?: null,
        ];

        if ($this->employeeModel->skipValidation(true)->update($id, $newData)) {
            // Sinkron data akun login
            $this->userModel->where('nik', $employee['nik'])->where('role', 'probationary-employee')->set([
                'nik'        => $newData['nik'],
                'nama'       => $newData['nama'],
                'email'      => $newData['email'] ?: null,
                'departemen' => $newData['departemen'],
                'posisi'     => $newData['posisi'],
            ])->update();

            // Log audit
            $this->logAudit('UPDATE', 'employees', $id, $employee, $newData);

            return redirect()->to('/employees')->with('success', 'Team Member berhasil diperbarui');
        }

        return redirect()->back()->withInput()->with('error', 'Gagal memperbarui karyawan');
    }

    /**
     * Delete employee
     */
    public function delete(int $id)
    {
        if (!$this->checkAuthAndRole('hrd')) {
            return redirect()->to('/auth/login');
        }

        $employee = $this->employeeModel->find($id);

        if (!$employee) {
            return redirect()->back()->with('error', 'Team Member tidak ditemukan');
        }

        if ($this->employeeModel->delete($id)) {
            // Hapus akun login karyawan
            $this->userModel->where('nik', $employee['nik'])->where('role', 'probationary-employee')->delete();

            // Log audit
            $this->logAudit('DELETE', 'employees', $id, $employee, null);

            return $this->response->setJSON(['success' => true, 'message' => 'Team Member berhasil dihapus']);
        }

        return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Gagal menghapus karyawan']);
    }

    /**
     * Get employees by department (API)
     */
    public function getByDepartment(string $department)
    {
        if (!session()->has('user_id')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        $employees = $this->employeeModel->where('departemen', $department)->findAll();

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $employees,
        ]);
    }

    /**
     * Helper: Check auth and role
     */
    protected function checkAuthAndRole(string $requiredRole): bool
    {
        if (!session()->has('user_id')) {
            return false;
        }

        return session()->get('role') === $requiredRole;
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
            "{$action} employee record"
        );
    }
}
