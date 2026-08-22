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
    public function before(RequestInterface $request, $arguments = null)
    {
        if (session()->has('user_id')) {
            return;   // sudah login, lanjutkan
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

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // tidak ada yang perlu dikerjakan setelah response
    }
}
