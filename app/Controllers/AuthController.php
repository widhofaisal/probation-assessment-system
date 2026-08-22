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

        // Tolak lebih awal kalau percobaan gagal sudah melewati batas, supaya
        // password tidak sempat diuji sama sekali.
        if ($sisa = $this->cekPembatasanLogin($nik)) {
            return redirect()->back()->with(
                'errorMsg',
                "Terlalu banyak percobaan login yang gagal. Silakan coba lagi dalam {$sisa} detik."
            );
        }

        // Find user by NIK or Email
        $user = $this->userModel->findByNikOrEmail($nik);

        // Pesan sengaja dibuat sama untuk "akun tidak ada" dan "password salah".
        // Kalau dibedakan, form login bisa dipakai untuk menebak NIK mana yang
        // terdaftar hanya dari perbedaan pesannya.
        if (!$user || !UserModel::verifyPassword($password, $user['password_hash'])) {
            $this->catatLoginGagal($nik);

            return redirect()->back()->with('errorMsg', 'NIK/Email atau password salah');
        }

        // Ganti ID sesi setelah login berhasil. Tanpa ini, ID sesi yang sudah
        // dipegang penyerang sebelum korban login tetap berlaku sesudahnya
        // (session fixation).
        session()->regenerate(true);

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

    /* =====================================================================
     * PEMBATASAN PERCOBAAN LOGIN
     *
     * Tanpa pembatasan, password bisa ditebak sebanyak-banyaknya tanpa
     * hambatan. Yang dihitung hanya percobaan yang GAGAL - login yang
     * berhasil tidak mengurangi jatah, jadi pengguna yang memang tahu
     * passwordnya tidak pernah ikut terkunci.
     *
     * Dua ember terpisah:
     *
     *  - per NIK : melindungi satu akun dari ditebak berulang kali.
     *  - per IP  : melindungi dari penyerang yang mencoba banyak NIK
     *              sekaligus. Batasnya lebih longgar, karena satu kantor
     *              biasanya keluar lewat satu IP publik yang sama sehingga
     *              batas ketat akan mengunci seluruh karyawan.
     * ===================================================================== */

    /** Jumlah kegagalan yang ditoleransi untuk satu NIK, per LOGIN_JENDELA. */
    private const LOGIN_BATAS_NIK = 5;

    /** Jumlah kegagalan yang ditoleransi untuk satu alamat IP. */
    private const LOGIN_BATAS_IP = 30;

    /** Lebar jendela waktu dalam detik (15 menit). */
    private const LOGIN_JENDELA = 900;

    /**
     * Periksa apakah percobaan login sudah melewati batas.
     *
     * Memakai cost 0 supaya hanya mengintip isi ember tanpa menguranginya -
     * pengurangan dilakukan catatLoginGagal(), khusus saat login gagal.
     *
     * @return int Sisa detik sampai boleh mencoba lagi. 0 berarti boleh lanjut.
     */
    protected function cekPembatasanLogin(string $nik): int
    {
        $throttler = service('throttler');

        foreach ($this->emberLogin($nik) as [$kunci, $batas]) {
            if (!$throttler->check($kunci, $batas, self::LOGIN_JENDELA, 0)) {
                return max(1, $throttler->getTokenTime());
            }
        }

        return 0;
    }

    /**
     * Catat satu percobaan login yang gagal.
     */
    protected function catatLoginGagal(string $nik): void
    {
        $throttler = service('throttler');

        foreach ($this->emberLogin($nik) as [$kunci, $batas]) {
            $throttler->check($kunci, $batas, self::LOGIN_JENDELA);
        }

        log_message('warning', 'Login gagal untuk "{nik}" dari IP {ip}', [
            'nik' => $nik,
            'ip'  => $this->request->getIPAddress(),
        ]);
    }

    /**
     * Daftar ember pembatas beserta kapasitasnya: [kunci, batas].
     */
    private function emberLogin(string $nik): array
    {
        // NIK dinormalkan supaya "HRD001", "hrd001", dan " HRD001 " dihitung
        // sebagai akun yang sama, dan di-hash agar tidak tersimpan apa adanya
        // sebagai nama berkas cache.
        $kunciNik = 'login_nik_' . sha1(strtolower(trim($nik)));
        $kunciIp  = 'login_ip_' . sha1($this->request->getIPAddress());

        return [
            [$kunciNik, self::LOGIN_BATAS_NIK],
            [$kunciIp, self::LOGIN_BATAS_IP],
        ];
    }
}
