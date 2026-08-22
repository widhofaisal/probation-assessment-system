<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Membatasi route hanya untuk role tertentu.
 *
 * Dipakai di app/Config/Routes.php, misalnya:
 *
 *     ['filter' => 'role:hrd']                  -> hanya HRD
 *     ['filter' => 'role:hrd,team-leader']      -> HRD atau Team Leader
 *
 * Role yang dikenal: hrd, team-leader, probationary-employee.
 */
class RoleFilter implements FilterInterface
{
    /** Halaman awal tiap role, dipakai saat menolak akses. */
    private const DASHBOARD = [
        'hrd'                    => '/dashboard/hrd',
        'team-leader'            => '/dashboard/team-leader',
        'probationary-employee'  => '/dashboard/probationary',
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        // Belum login: serahkan penanganannya ke AuthFilter agar perilakunya
        // seragam (arahkan ke halaman login, bukan halaman "akses ditolak").
        if (!session()->has('user_id')) {
            return (new AuthFilter())->before($request, $arguments);
        }

        $role = session()->get('role');

        if (!empty($arguments) && in_array($role, $arguments, true)) {
            return;   // role sesuai, lanjutkan
        }

        if ($request->isAJAX()) {
            return service('response')
                ->setStatusCode(ResponseInterface::HTTP_FORBIDDEN)
                ->setJSON([
                    'success' => false,
                    'message' => 'Anda tidak punya akses ke tindakan ini.',
                ]);
        }

        // Sudah login tapi salah role: kembalikan ke dashboard miliknya sendiri.
        // Mengarahkan ke halaman login akan membingungkan, karena sesinya valid.
        $tujuan = self::DASHBOARD[$role] ?? '/auth/login';

        return redirect()->to($tujuan)
            ->with('errorMsg', 'Anda tidak punya akses ke halaman tersebut.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // tidak ada yang perlu dikerjakan setelah response
    }
}
