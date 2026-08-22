<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Pembatasan percobaan login.
 *
 * AuthController memakai dua pemanggilan throttler yang berbeda peran:
 *
 *   check($kunci, $batas, $jendela, 0)   - MENGINTIP, tidak mengurangi jatah
 *   check($kunci, $batas, $jendela)      - MENCATAT satu kegagalan
 *
 * Pembagian itulah yang membuat login berhasil tidak ikut memakan jatah,
 * sekaligus memastikan penolakan terjadi SEBELUM password sempat diperiksa.
 * Kalau cost 0 suatu saat ikut mengurangi, pengguna sah akan terkunci hanya
 * karena sering login - tes di sini menangkapnya.
 *
 * @internal
 */
final class PembatasanLoginTest extends CIUnitTestCase
{
    /** Sama dengan konstanta di AuthController. */
    private const BATAS_NIK = 5;
    private const JENDELA   = 900;

    private function kunciBaru(): string
    {
        return 'uji_' . bin2hex(random_bytes(6));
    }

    public function testMengintipTidakMengurangiJatah(): void
    {
        $throttler = service('throttler');
        $kunci     = $this->kunciBaru();

        // Seratus kali mengintip pada ember berkapasitas 5 harus tetap lolos.
        for ($i = 1; $i <= 100; $i++) {
            $this->assertTrue(
                $throttler->check($kunci, self::BATAS_NIK, self::JENDELA, 0),
                "Intipan ke-{$i} seharusnya masih lolos - cost 0 tidak boleh mengurangi jatah",
            );
        }
    }

    public function testTerkunciSetelahKegagalanKeLima(): void
    {
        $throttler = service('throttler');
        $kunci     = $this->kunciBaru();

        for ($i = 1; $i <= self::BATAS_NIK; $i++) {
            $this->assertTrue(
                $throttler->check($kunci, self::BATAS_NIK, self::JENDELA, 0),
                "Percobaan ke-{$i} masih di bawah batas, seharusnya boleh diproses",
            );
            $throttler->check($kunci, self::BATAS_NIK, self::JENDELA);   // catat gagal
        }

        $this->assertFalse(
            $throttler->check($kunci, self::BATAS_NIK, self::JENDELA, 0),
            'Percobaan setelah kegagalan ke-' . self::BATAS_NIK . ' seharusnya ditolak',
        );
    }

    public function testMemberiTahuSisaWaktuTunggu(): void
    {
        $throttler = service('throttler');
        $kunci     = $this->kunciBaru();

        for ($i = 0; $i < self::BATAS_NIK; $i++) {
            $throttler->check($kunci, self::BATAS_NIK, self::JENDELA);
        }
        $throttler->check($kunci, self::BATAS_NIK, self::JENDELA, 0);

        // Pengguna perlu tahu harus menunggu berapa lama, bukan sekadar ditolak.
        $this->assertGreaterThan(0, $throttler->getTokenTime());
    }

    public function testEmberTiapNikTerpisah(): void
    {
        $throttler = service('throttler');
        $kunciA    = $this->kunciBaru();
        $kunciB    = $this->kunciBaru();

        for ($i = 0; $i < self::BATAS_NIK; $i++) {
            $throttler->check($kunciA, self::BATAS_NIK, self::JENDELA);
        }

        $this->assertFalse($throttler->check($kunciA, self::BATAS_NIK, self::JENDELA, 0));
        $this->assertTrue(
            $throttler->check($kunciB, self::BATAS_NIK, self::JENDELA, 0),
            'Akun yang diserang tidak boleh ikut mengunci akun lain',
        );
    }

    public function testBatasIpLebihLonggarDaripadaBatasNik(): void
    {
        // Satu kantor umumnya keluar lewat satu IP publik. Kalau batas IP dibuat
        // seketat batas per akun, seluruh karyawan bisa terkunci bersamaan hanya
        // karena beberapa orang salah ketik password.
        $refleksi = new ReflectionClass(App\Controllers\AuthController::class);

        $this->assertGreaterThan(
            $refleksi->getConstant('LOGIN_BATAS_NIK'),
            $refleksi->getConstant('LOGIN_BATAS_IP'),
        );
    }
}
