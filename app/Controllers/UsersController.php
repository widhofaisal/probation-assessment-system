<?php

namespace App\Controllers;

use App\Models\UserModel;

class UsersController extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index()
    {
        if (session()->get('role') !== 'hrd') {
            return redirect()->to('/auth/login');
        }

        $users = $this->userModel
            ->where('role', 'team-leader')
            ->orderBy('nama', 'ASC')
            ->findAll();

        return view('Users/index', [
            'title' => 'Data Team Leader',
            'users' => $users,
        ]);
    }

    public function store()
    {
        if (session()->get('role') !== 'hrd') {
            return redirect()->to('/auth/login');
        }

        $nik  = $this->request->getPost('nik');
        $role = $this->request->getPost('role');

        if (!in_array($role, ['hrd', 'team-leader'])) {
            return redirect()->back()->withInput()->with('error', 'Role tidak valid');
        }

        // Cek NIK sudah ada
        if ($this->userModel->where('nik', $nik)->first()) {
            return redirect()->back()->withInput()->with('error', 'NIK sudah digunakan');
        }

        // Password awal dibuat acak, bukan disamakan dengan NIK. Nilainya hanya
        // muncul sekali di pesan sukses di bawah - setelah itu tinggal hash-nya.
        $passwordAwal = UserModel::generatePassword();

        $this->userModel->skipValidation(true)->insert([
            'nik'            => $nik,
            'nama'           => $this->request->getPost('nama'),
            'email'          => $this->request->getPost('email') ?: null,
            'password_hash'  => UserModel::hashPassword($passwordAwal),
            // Password ini dibuat sistem, bukan dipilih pemiliknya - wajib
            // diganti sebelum akun dipakai.
            'harus_ganti_password' => 1,
            'role'           => $role,
            'departemen'     => $this->request->getPost('departemen') ?: null,
            'posisi'         => $this->request->getPost('posisi') ?: null,
            'jenis_kelamin'  => $this->request->getPost('jenis_kelamin') ?: null,
            'tanggal_lahir'  => $this->request->getPost('tanggal_lahir') ?: null,
            'alamat'         => $this->request->getPost('alamat') ?: null,
        ]);

        return redirect()->to('/users')->with(
            'success_sekali',
            'User berhasil ditambahkan. Password awal: ' . $passwordAwal
                . ' - catat sekarang, password ini tidak bisa dilihat lagi.'
        );
    }

    public function update(int $id)
    {
        if (session()->get('role') !== 'hrd') {
            return redirect()->to('/auth/login');
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            return redirect()->back()->with('error', 'User tidak ditemukan');
        }

        // Jangan izinkan edit diri sendiri via halaman ini
        if ($id === (int) session()->get('user_id')) {
            return redirect()->back()->with('error', 'Edit profil sendiri melalui halaman Profil');
        }

        $nik = $this->request->getPost('nik');

        // Cek NIK unik (kecuali milik user ini sendiri)
        $conflict = $this->userModel->where('nik', $nik)->where('id !=', $id)->first();
        if ($conflict) {
            return redirect()->back()->withInput()->with('error', 'NIK sudah digunakan');
        }

        $updateData = [
            'nik'           => $nik,
            'nama'          => $this->request->getPost('nama'),
            'email'         => $this->request->getPost('email') ?: null,
            'role'          => $this->request->getPost('role'),
            'departemen'    => $this->request->getPost('departemen') ?: null,
            'posisi'        => $this->request->getPost('posisi') ?: null,
            'jenis_kelamin' => $this->request->getPost('jenis_kelamin') ?: null,
            'tanggal_lahir' => $this->request->getPost('tanggal_lahir') ?: null,
            'alamat'        => $this->request->getPost('alamat') ?: null,
        ];

        $this->userModel->skipValidation(true)->update($id, $updateData);

        return redirect()->to('/users')->with('success', 'User berhasil diperbarui');
    }

    public function resetPassword(int $id)
    {
        if (session()->get('role') !== 'hrd') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Akses ditolak']);
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'User tidak ditemukan']);
        }

        // Password baru dibuat acak, bukan dikembalikan ke NIK. Kalau direset ke
        // NIK, akun tersebut praktis terbuka bagi siapa pun yang tahu NIK-nya
        // sampai pemiliknya sempat mengganti password.
        $passwordBaru = UserModel::generatePassword();

        $this->userModel->skipValidation(true)->update($id, [
            'password_hash'        => UserModel::hashPassword($passwordBaru),
            'harus_ganti_password' => 1,
        ]);

        return $this->response->setJSON([
            'success'  => true,
            'message'  => 'Password ' . $user['nik'] . ' berhasil direset.',
            'password' => $passwordBaru,
        ]);
    }

    public function delete(int $id)
    {
        if (session()->get('role') !== 'hrd') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Akses ditolak']);
        }

        if ($id === (int) session()->get('user_id')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Tidak bisa menghapus akun sendiri']);
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'User tidak ditemukan']);
        }

        $this->userModel->delete($id);

        return $this->response->setJSON(['success' => true]);
    }
}
