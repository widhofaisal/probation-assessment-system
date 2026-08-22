<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Memastikan pengguna sudah login sebelum sebuah route dijalankan.
 *
 * Sebelumnya pengecekan ini ditulis ulang di tiap method controller, sehingga
 * satu method yang terlewat langsung menjadi celah. Filter ini menjadikannya
 * satu gerbang di depan route, jadi route baru otomatis ikut terlindungi
 * kecuali memang sengaja dikecualikan di app/Config/Routes.php.
 */
class AuthFilter implements FilterInterface
{
    /**
     * Halaman yang tetap boleh dibuka walau pengguna wajib ganti password.
     *
     * Tanpa daftar ini, pengguna yang diwajibkan mengganti password akan
     * dialihkan ke halaman ganti password, lalu halaman itu sendiri ikut
     * dialihkan lagi - berputar tanpa ujung dan akunnya tidak bisa dipakai
     * sama sekali. Logout juga harus tetap terbuka sebagai jalan keluar.
     */
    public const BEBAS_WAJIB_GANTI = [
        'profile/change-password',
        'profile/update-password',
        'auth/logout',
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        if (session()->has('user_id')) {
            return $this->periksaWajibGantiPassword($request);
        }

        // Permintaan AJAX butuh status code, bukan halaman login sebagai HTML.
        if ($request->isAJAX()) {
            return service('response')
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                ->setJSON([
                    'success' => false,
                    'message' => 'Sesi Anda sudah berakhir. Silakan login kembali.',
                ]);
        }

        // Simpan tujuan awal supaya bisa dikembalikan ke sana setelah login.
        if ($request->getMethod() === 'GET') {
            session()->setFlashdata('redirect_url', current_url());
        }

        return redirect()->to('/auth/login')
            ->with('errorMsg', 'Silakan login terlebih dahulu.');
    }

    /**
     * Paksa pengguna mengganti password yang dibuatkan sistem.
     *
     * Password awal dan password hasil reset dibuat acak lalu diserahkan HRD
     * lewat chat atau lisan. Selama belum diganti, password itu diketahui orang
     * lain dan tersimpan di riwayat percakapan mereka.
     */
    private function periksaWajibGantiPassword(RequestInterface $request)
    {
        // Penandanya dibaca dari sesi, bukan dari basis data. Filter ini berjalan
        // di setiap request, jadi membaca tabel users di sini berarti satu query
        // tambahan untuk setiap halaman yang dibuka - dan membuat lapisan auth
        // bergantung pada basis data bahkan saat menolak permintaan.
        //
        // Nilainya disimpan ke sesi oleh AuthController saat login dan dihapus
        // oleh ProfileController begitu passwordnya diganti. Sesi lama yang belum
        // punya kunci ini bernilai null, yang berarti tidak ada kewajiban -
        // default yang aman.
        if (empty(session()->get('harus_ganti_password'))) {
            return;   // tidak ada kewajiban, lanjutkan
        }

        $jalur = trim($request->getUri()->getPath(), '/');
        if (in_array($jalur, self::BEBAS_WAJIB_GANTI, true)) {
            return;
        }

        if ($request->isAJAX()) {
            return service('response')
                ->setStatusCode(ResponseInterface::HTTP_FORBIDDEN)
                ->setJSON([
                    'success' => false,
                    'message' => 'Anda harus mengganti password terlebih dahulu.',
                ]);
        }

        return redirect()->to('/profile/change-password')
            ->with('errorMsg', 'Password Anda masih password yang dibuatkan sistem. '
                . 'Ganti dulu sebelum memakai aplikasi.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // tidak ada yang perlu dikerjakan setelah response
    }
}
