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

        $employees = $query->findAll();
        $departments = $this->employeeModel->distinct()->select('departemen')->findAll();
        $teamLeaders = $this->userModel->getTeamLeaders();

        $data = [
            'title' => 'Data Karyawan',
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
            return redirect()->back()->with('error', 'Karyawan tidak ditemukan');
        }

        $data = [
            'title' => 'Detail Karyawan',
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
            'nik' => $this->request->getPost('nik'),
            'nama' => $this->request->getPost('nama'),
            'departemen' => $this->request->getPost('departemen'),
            'posisi' => $this->request->getPost('posisi'),
            'email' => $this->request->getPost('email'),
            'tanggal_masuk' => $this->request->getPost('tanggal_masuk'),
            'mulai_probation' => $this->request->getPost('mulai_probation'),
            'akhir_probation' => EmployeeModel::calculateProbationEnd($this->request->getPost('mulai_probation')),
            'status' => 'pending',
            'team_leader_id' => $this->request->getPost('team_leader_id') ?: null,
            'created_by' => session()->get('user_id'),
        ];

        $db = \Config\Database::connect();
        $db->transStart();

        $employeeId = $this->employeeModel->insert($data);

        // Buat akun login otomatis, password default = NIK
        $nikValue = $this->request->getPost('nik');
        $existingUser = $this->userModel->where('nik', $nikValue)->first();
        if (!$existingUser) {
            $this->userModel->skipValidation(true)->insert([
                'nik'           => $nikValue,
                'nama'          => $this->request->getPost('nama'),
                'email'         => $this->request->getPost('email') ?: null,
                'password_hash' => UserModel::hashPassword($nikValue),
                'role'          => 'probationary-employee',
                'departemen'    => $this->request->getPost('departemen'),
                'posisi'        => $this->request->getPost('posisi'),
            ]);
        }

        $db->transComplete();

        if ($db->transStatus()) {
            $this->logAudit('CREATE', 'employees', $this->employeeModel->getInsertID(), null, $data);
            return redirect()->to('/employees')->with('success', 'Karyawan berhasil ditambahkan. Password login: ' . $nikValue);
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
            return redirect()->back()->with('error', 'Karyawan tidak ditemukan');
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
            'status' => 'required|in_list[pending,lulus,tidak-lulus,warning]',
            'team_leader_id' => 'permit_empty|integer',
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $newData = [
            'nik' => $this->request->getPost('nik'),
            'nama' => $this->request->getPost('nama'),
            'departemen' => $this->request->getPost('departemen'),
            'posisi' => $this->request->getPost('posisi'),
            'email' => $this->request->getPost('email'),
            'tanggal_masuk' => $this->request->getPost('tanggal_masuk'),
            'mulai_probation' => $this->request->getPost('mulai_probation'),
            'status' => $this->request->getPost('status'),
            'team_leader_id' => $this->request->getPost('team_leader_id') ?: null,
        ];

        if ($this->employeeModel->update($id, $newData)) {
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

            return redirect()->to('/employees')->with('success', 'Karyawan berhasil diperbarui');
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
            return redirect()->back()->with('error', 'Karyawan tidak ditemukan');
        }

        if ($this->employeeModel->delete($id)) {
            // Hapus akun login karyawan
            $this->userModel->where('nik', $employee['nik'])->where('role', 'probationary-employee')->delete();

            // Log audit
            $this->logAudit('DELETE', 'employees', $id, $employee, null);

            return redirect()->to('/employees')->with('success', 'Karyawan berhasil dihapus');
        }

        return redirect()->back()->with('error', 'Gagal menghapus karyawan');
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
