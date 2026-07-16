<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class AuthController extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * Show login page
     */
    public function login()
    {
        // If already logged in, redirect to appropriate dashboard
        if (session()->has('user_id')) {
            return $this->redirectToDashboard();
        }

        $data = [
            'title' => 'Login',
            'errorMsg' => session()->getFlashdata('errorMsg'),
        ];

        return view('Auth/login', $data);
    }

    /**
     * Handle login form submission
     */
    public function processLogin()
    {
        // Check if user is already logged in
        if (session()->has('user_id')) {
            return redirect()->to('/');
        }

        $nik = $this->request->getPost('nik');
        $password = $this->request->getPost('password');

        if (empty($nik) || empty($password)) {
            return redirect()->back()->with('errorMsg', 'NIK dan Password harus diisi');
        }

        // Find user by NIK or Email
        $user = $this->userModel->findByNikOrEmail($nik);

        if (!$user) {
            return redirect()->back()->with('errorMsg', 'NIK/Email tidak ditemukan');
        }

        // Verify password
        if (!UserModel::verifyPassword($password, $user['password_hash'])) {
            return redirect()->back()->with('errorMsg', 'Password salah');
        }

        // Set session
        session()->set([
            'user_id' => $user['id'],
            'nik' => $user['nik'],
            'nama' => $user['nama'],
            'email' => $user['email'],
            'role' => $user['role'],
            'departemen' => $user['departemen'],
            'posisi' => $user['posisi'],
        ]);

        // Log the login
        $auditModel = model('AuditLogModel');
        $auditModel->logAction(
            $user['id'],
            'LOGIN',
            'users',
            $user['id'],
            null,
            ['ip_address' => $this->request->getIPAddress()],
            'User login'
        );

        return redirect()->to($this->getDashboardUrl($user['role']));
    }

    /**
     * Logout
     */
    public function logout()
    {
        if (session()->has('user_id')) {
            $userId = session()->get('user_id');

            // Log the logout
            $auditModel = model('AuditLogModel');
            $auditModel->logAction(
                $userId,
                'LOGOUT',
                'users',
                $userId,
                null,
                null,
                'User logout'
            );
        }

        session()->destroy();
        return redirect()->to('/auth/login')->with('errorMsg', 'Anda telah logout');
    }

    /**
     * Get dashboard URL based on role
     */
    protected function getDashboardUrl(string $role): string
    {
        return match ($role) {
            'hrd' => '/dashboard/hrd',
            'team-leader' => '/dashboard/team-leader',
            'probationary-employee' => '/dashboard/probationary',
            default => '/',
        };
    }

    /**
     * Redirect to appropriate dashboard
     */
    protected function redirectToDashboard()
    {
        $role = session()->get('role');
        $url = $this->getDashboardUrl($role);
        return redirect()->to($url);
    }

    /**
     * API: Check session
     */
    public function checkSession()
    {
        if (session()->has('user_id')) {
            return $this->response->setJSON([
                'status' => 'success',
                'data' => [
                    'user_id' => session()->get('user_id'),
                    'nik' => session()->get('nik'),
                    'nama' => session()->get('nama'),
                    'role' => session()->get('role'),
                ],
            ]);
        }

        return $this->response->setStatusCode(401)->setJSON([
            'status' => 'error',
            'message' => 'Not authenticated',
        ]);
    }
}
