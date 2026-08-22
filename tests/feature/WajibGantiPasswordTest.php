<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Kewajiban mengganti password yang dibuatkan sistem.
 *
 * Password awal dan password hasil reset dibuat acak lalu diserahkan HRD lewat
 * chat atau lisan. Selama belum diganti, password itu diketahui orang lain dan
 * tersimpan di riwayat percakapan mereka - jadi pemiliknya dipaksa mengganti
 * sebelum bisa memakai aplikasi.
 *
 * Yang paling rawan di fitur semacam ini bukan penegakannya, melainkan jalan
 * keluarnya: kalau halaman ganti password ikut dialihkan, penggunanya terkunci
 * dalam pengalihan tanpa ujung dan akun itu tidak bisa dipakai sama sekali.
 * Sebagian besar tes di sini menjaga hal tersebut.
 *
 * @internal
 */
final class WajibGantiPasswordTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private function sesiWajibGanti(bool $wajib = true): array
    {
        return [
            'user_id'              => 2,
            'nik'                  => 'TL001',
            'nama'                 => 'Team Leader Uji',
            'role'                 => 'team-leader',
            'departemen'           => 'Produksi',
            'posisi'               => 'Supervisor',
            'harus_ganti_password' => $wajib,
        ];
    }

    // ---------------------------------------------------------------
    // Penegakan
    // ---------------------------------------------------------------

    public static function ruteYangHarusDialihkan(): array
    {
        return [
            'dashboard'  => ['/dashboard/team-leader'],
            'tim saya'   => ['/team'],
            'profil'     => ['/profile'],
            'aspek'      => ['/evaluations/aspects'],
            'statistik'  => ['/dashboard/stats'],
        ];
    }

    /**
     * @dataProvider ruteYangHarusDialihkan
     */
    public function testDialihkanKeHalamanGantiPassword(string $rute): void
    {
        $hasil = $this->withSession($this->sesiWajibGanti())->call('get', $rute);

        $hasil->assertRedirect();
        $this->assertStringContainsString(
            '/profile/change-password',
            $hasil->getRedirectUrl(),
            "Route {$rute} seharusnya dialihkan ke halaman ganti password",
        );
    }

    public function testBerlakuJugaUntukRouteYangDijagaRoleFilter(): void
    {
        // Sebuah route hanya memakai salah satu dari filter 'auth' atau 'role'.
        // Kalau RoleFilter tidak ikut memanggil pemeriksaan ini, seluruh route
        // ber-role akan melewatkan kewajiban ganti password.
        $hasil = $this->withSession($this->sesiWajibGanti())->call('get', '/team');

        $hasil->assertRedirect();
        $this->assertStringContainsString('/profile/change-password', $hasil->getRedirectUrl());
    }

    public function testPermintaanAjaxDijawab403(): void
    {
        $hasil = $this->withSession($this->sesiWajibGanti())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->call('get', '/dashboard/stats');

        $hasil->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // Jalan keluar
    // ---------------------------------------------------------------

    public function testHalamanGantiPasswordTidakIkutDialihkan(): void
    {
        // Inti dari fitur ini. Kalau halaman tujuan pengalihan ikut dialihkan,
        // pengguna berputar tanpa ujung dan akunnya mati total.
        $hasil = $this->withSession($this->sesiWajibGanti())->call('get', '/profile/change-password');

        $hasil->assertOK();
    }

    public function testLogoutAdaDiDaftarPengecualian(): void
    {
        // Logout adalah jalan keluar terakhir kalau pengguna tidak bisa
        // menyelesaikan penggantian password - misalnya lupa password lamanya.
        //
        // Diperiksa lewat daftar pengecualiannya, bukan lewat request, karena
        // memanggil /auth/logout akan menjalankan pencatatan audit yang butuh
        // basis data. Saat ini route logout memang tidak berfilter sehingga
        // aman dengan sendirinya; entri ini menjaga kalau suatu saat route itu
        // dipindahkan ke dalam grup yang difilter.
        $daftar = (new ReflectionClass(App\Filters\AuthFilter::class))
            ->getConstant('BEBAS_WAJIB_GANTI');

        $this->assertContains('auth/logout', $daftar);
        $this->assertContains('profile/change-password', $daftar);
        $this->assertContains('profile/update-password', $daftar);
    }

    // ---------------------------------------------------------------
    // Setelah kewajiban selesai
    // ---------------------------------------------------------------

    /*
     * Dua tes di bawah memakai /users, yaitu route milik HRD. Seorang Team
     * Leader selalu ditolak di sana, jadi permintaannya berhenti di lapisan
     * filter dan tidak pernah menyentuh controller maupun basis data.
     *
     * Yang membedakan justru TUJUAN pengalihannya: kalau kewajiban ganti
     * password aktif, tujuannya halaman ganti password; kalau tidak, tujuannya
     * dashboard miliknya sendiri karena ditolak RoleFilter. Perbedaan itulah
     * yang diperiksa.
     */

    public function testTanpaKewajibanTidakDialihkanKeGantiPassword(): void
    {
        $hasil = $this->withSession($this->sesiWajibGanti(false))->call('get', '/users');

        $this->assertStringNotContainsString(
            '/profile/change-password',
            $hasil->getRedirectUrl(),
            'Pengguna tanpa kewajiban ganti password tidak boleh diarahkan ke sana',
        );
        $this->assertStringContainsString('/dashboard/team-leader', $hasil->getRedirectUrl());
    }

    public function testSesiLamaTanpaPenandaDianggapTidakWajib(): void
    {
        // Sesi yang dibuat sebelum fitur ini ada tidak punya kunci tersebut.
        // Nilai yang hilang harus berarti "tidak ada kewajiban", bukan sebaliknya -
        // kalau terbalik, semua pengguna yang sedang login langsung terkunci
        // begitu pembaruan dipasang.
        $sesi = $this->sesiWajibGanti();
        unset($sesi['harus_ganti_password']);

        $hasil = $this->withSession($sesi)->call('get', '/users');

        $this->assertStringNotContainsString('/profile/change-password', $hasil->getRedirectUrl());
    }
}
