<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Kontrol akses di lapisan route: AuthFilter dan RoleFilter.
 *
 * Semua kasus di sini menguji jalur DITOLAK, dan itu disengaja. Filter menolak
 * sebelum controller sempat jalan, jadi tesnya tidak menyentuh database sama
 * sekali dan bisa dijalankan siapa pun tanpa menyiapkan MySQL lebih dulu.
 *
 * Kalau suatu saat ada yang menghapus filter dari sebuah route di
 * app/Config/Routes.php, tes di berkas ini yang akan gagal lebih dulu.
 *
 * @internal
 */
final class KontrolAksesTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    /** Sesi tiruan untuk tiap role. */
    private const SESI = [
        'hrd' => [
            'user_id' => 1, 'nik' => 'HRD001', 'nama' => 'Siti Rahayu',
            'role' => 'hrd', 'departemen' => 'Human Resources', 'posisi' => 'HR Manager',
        ],
        'team-leader' => [
            'user_id' => 2, 'nik' => 'TL001', 'nama' => 'Ahmad Fauzi',
            'role' => 'team-leader', 'departemen' => 'Produksi', 'posisi' => 'Supervisor',
        ],
        'probationary-employee' => [
            'user_id' => 4, 'nik' => 'EMP001', 'nama' => 'Budi Santoso',
            'role' => 'probationary-employee', 'departemen' => 'Produksi', 'posisi' => 'Operator',
        ],
    ];

    // ---------------------------------------------------------------
    // Belum login
    // ---------------------------------------------------------------

    /**
     * Route yang wajib login. Termasuk /evaluations/aspects dan
     * /dashboard/stats, yang dulu sama sekali tidak punya pengecekan.
     */
    public static function rutePerluLogin(): array
    {
        return [
            'daftar user'        => ['/users'],
            'daftar karyawan'    => ['/employees'],
            'dashboard hrd'      => ['/dashboard/hrd'],
            'dashboard tl'       => ['/dashboard/team-leader'],
            'dashboard member'   => ['/dashboard/probationary'],
            'statistik'          => ['/dashboard/stats'],
            'tim saya'           => ['/team'],
            'daftar penilaian'   => ['/evaluations'],
            'penilaian saya'     => ['/evaluations/my'],
            'aspek penilaian'    => ['/evaluations/aspects'],
            'status keputusan'   => ['/evaluations/keputusan-status'],
            'profil'             => ['/profile'],
            'ganti password'     => ['/profile/change-password'],
            'ekspor penilaian'   => ['/reports/export-csv'],
            'ekspor karyawan'    => ['/reports/export-employees-csv'],
            'lihat laporan'      => ['/reports/evaluation/1'],
            'pdf laporan'        => ['/reports/pdf/1'],
            'surat keputusan'    => ['/reports/sk/1'],
        ];
    }

    /**
     * @dataProvider rutePerluLogin
     */
    public function testTamuDiarahkanKeHalamanLogin(string $rute): void
    {
        $hasil = $this->call('get', $rute);

        $hasil->assertRedirect();
        $this->assertStringContainsString(
            '/auth/login',
            $hasil->getRedirectUrl(),
            "Route {$rute} seharusnya mengarahkan tamu ke halaman login",
        );
    }

    public function testHalamanLoginTerbukaUntukTamu(): void
    {
        // Kalau halaman login ikut difilter, tidak ada seorang pun yang bisa masuk.
        $this->call('get', '/auth/login')->assertOK();
    }

    public function testPermintaanAjaxTanpaSesiDijawab401(): void
    {
        // Bukan halaman login berbentuk HTML - pemanggil AJAX butuh status code.
        $hasil = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->call('get', '/dashboard/stats');

        $hasil->assertStatus(401);
        $this->assertFalse($hasil->getJSON() === null, 'Respons AJAX harus berupa JSON');
    }

    // ---------------------------------------------------------------
    // Sudah login, tapi salah role
    // ---------------------------------------------------------------

    /**
     * [role yang login, route milik role lain, tujuan pengalihan]
     */
    public static function ruteSalahRole(): array
    {
        return [
            'hrd buka dashboard tl'      => ['hrd', '/dashboard/team-leader', '/dashboard/hrd'],
            'hrd buka tim saya'          => ['hrd', '/team', '/dashboard/hrd'],
            'hrd buka penilaian saya'    => ['hrd', '/evaluations/my', '/dashboard/hrd'],
            'tl buka daftar user'        => ['team-leader', '/users', '/dashboard/team-leader'],
            'tl buka daftar karyawan'    => ['team-leader', '/employees', '/dashboard/team-leader'],
            'tl buka daftar penilaian'   => ['team-leader', '/evaluations', '/dashboard/team-leader'],
            'tl buka ekspor csv'         => ['team-leader', '/reports/export-csv', '/dashboard/team-leader'],
            'member buka daftar user'    => ['probationary-employee', '/users', '/dashboard/probationary'],
            'member buka tim saya'       => ['probationary-employee', '/team', '/dashboard/probationary'],
            'member buka ekspor csv'     => ['probationary-employee', '/reports/export-csv', '/dashboard/probationary'],
        ];
    }

    /**
     * @dataProvider ruteSalahRole
     */
    public function testRoleSalahDikembalikanKeDashboardSendiri(string $role, string $rute, string $tujuan): void
    {
        $hasil = $this->withSession(self::SESI[$role])->call('get', $rute);

        $hasil->assertRedirect();
        $this->assertStringContainsString(
            $tujuan,
            $hasil->getRedirectUrl(),
            "Role {$role} yang membuka {$rute} seharusnya dikembalikan ke {$tujuan}, "
                . 'bukan dilempar ke halaman login padahal sesinya masih sah',
        );
    }

    public function testRoleSalahLewatAjaxDijawab403(): void
    {
        $hasil = $this->withSession(self::SESI['team-leader'])
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->call('get', '/reports/export-csv');

        $hasil->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // Kelengkapan konfigurasi
    // ---------------------------------------------------------------

    /**
     * Jaring pengaman untuk route yang ditambahkan di kemudian hari.
     *
     * Route tanpa filter terbuka untuk siapa saja termasuk tamu. Satu-satunya
     * pengecualian yang sah adalah halaman awal dan grup auth, karena keduanya
     * justru bertugas melayani pengguna yang belum login.
     */
    public function testSemuaRoutePunyaFilterKecualiYangDikecualikan(): void
    {
        // Opsi route disimpan per verb HTTP, jadi tiap verb harus ditelusuri
        // sendiri. Dua hal yang mudah salah di sini:
        //
        //  - getRoutes('*') saja tidak cukup. Bucket '*' hanya berisi route yang
        //    didaftarkan lewat add(), sementara route di sini memakai
        //    get()/post()/delete() sehingga tidak pernah masuk ke sana.
        //  - nama verb HARUS huruf kapital. Dengan huruf kecil, $routes[$verb]
        //    tidak pernah ketemu dan koleksinya selalu kosong - tesnya lulus
        //    terus tanpa memeriksa apa pun.
        $verb = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'CLI'];

        $dikecualikan = ['/', 'auth/login', 'auth/logout', 'auth/session'];

        $koleksi = service('routes');
        $koleksi->loadRoutes();

        $tanpaFilter = [];

        foreach ($verb as $v) {
            foreach ($koleksi->getRoutes($v, false) as $dari => $ke) {
                $bersih = trim($dari, '/');
                $bersih = $bersih === '' ? '/' : $bersih;

                if (in_array($bersih, $dikecualikan, true)) {
                    continue;
                }

                if ($koleksi->getFiltersForRoute($dari, $v) === []) {
                    $tanpaFilter[] = $v . ' ' . $dari;
                }
            }
        }

        $this->assertSame(
            [],
            $tanpaFilter,
            "Route berikut belum diberi filter di app/Config/Routes.php, jadi terbuka "
                . "untuk siapa saja termasuk tamu:\n  " . implode("\n  ", $tanpaFilter),
        );
    }
}
