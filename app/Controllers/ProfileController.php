<?php

namespace App\Controllers;

use App\Models\UserModel;

class ProfileController extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index()
    {
        if (!session()->has('user_id')) {
            return redirect()->to('/auth/login');
        }

        $user = $this->userModel->find(session()->get('user_id'));

        $roleLabels = [
            'hrd' => 'HRD',
            'team-leader' => 'Team Leader',
            'probationary-employee' => 'Probationary Employee',
        ];

        $data = [
            'title' => 'Profil Saya',
            'user' => $user,
            'roleLabel' => $roleLabels[$user['role']] ?? $user['role'],
        ];

        return view('Profile/index', $data);
    }

    public function update()
    {
        if (!session()->has('user_id')) {
            return redirect()->to('/auth/login');
        }

        $userId = session()->get('user_id');
        $user = $this->userModel->find($userId);

        $nama = $this->request->getPost('nama');
        $email = $this->request->getPost('email');
        $departemen = $this->request->getPost('departemen');
        $posisi = $this->request->getPost('posisi');

        if (empty($nama)) {
            return redirect()->back()->with('error', 'Nama tidak boleh kosong');
        }

        $updateData = [
            'nama'      => $nama,
            'email'     => $email ?: null,
            'departemen' => $departemen,
            'posisi'    => $posisi,
        ];

        $this->userModel->skipValidation(true)->update($userId, $updateData);

        // Refresh session data
        session()->set('nama', $nama);
        session()->set('departemen', $departemen);
        session()->set('posisi', $posisi);

        $auditModel = model('AuditLogModel');
        $auditModel->logAction($userId, 'UPDATE', 'users', $userId, $user, $updateData, 'User updated own profile');

        return redirect()->to('/profile')->with('success', 'Profil berhasil diperbarui');
    }

    public function changePassword()
    {
        if (!session()->has('user_id')) {
            return redirect()->to('/auth/login');
        }

        $data = [
            'title' => 'Ganti Password',
        ];

        return view('Profile/change_password', $data);
    }

    public function updatePassword()
    {
        if (!session()->has('user_id')) {
            return redirect()->to('/auth/login');
        }

        $userId = session()->get('user_id');
        $user = $this->userModel->find($userId);

        $currentPassword = $this->request->getPost('current_password');
        $newPassword = $this->request->getPost('new_password');
        $confirmPassword = $this->request->getPost('confirm_password');

        if (!UserModel::verifyPassword($currentPassword, $user['password_hash'])) {
            return redirect()->back()->with('error', 'Password saat ini tidak sesuai');
        }

        if (strlen($newPassword) < 8) {
            return redirect()->back()->with('error', 'Password baru minimal 8 karakter');
        }

        if ($newPassword !== $confirmPassword) {
            return redirect()->back()->with('error', 'Konfirmasi password tidak cocok');
        }

        $this->userModel->skipValidation(true)->update($userId, [
            'password_hash' => UserModel::hashPassword($newPassword),
        ]);

        $auditModel = model('AuditLogModel');
        $auditModel->logAction($userId, 'UPDATE', 'users', $userId, null, null, 'User changed own password');

        return redirect()->to('/profile')->with('success', 'Password berhasil diubah');
    }
}
