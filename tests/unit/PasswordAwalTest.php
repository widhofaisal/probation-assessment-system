<?php

use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Password awal akun baru dan penyimpanannya.
 *
 * Dulu password awal disamakan dengan NIK, padahal NIK juga dipakai sebagai
 * username - jadi akun baru bisa dimasuki siapa pun yang tahu NIK-nya. Tes di
 * sini menjaga agar perilaku itu tidak diam-diam kembali.
 *
 * @internal
 */
final class PasswordAwalTest extends CIUnitTestCase
{
    public function testPanjangnyaDuaBelasKarakter(): void
    {
        $this->assertSame(12, strlen(UserModel::generatePassword()));
    }

    public function testPanjangnyaBisaDiatur(): void
    {
        $this->assertSame(20, strlen(UserModel::generatePassword(20)));
    }

    public function testTidakMemakaiKarakterYangMudahTertukar(): void
    {
        // Password ini disalin manual oleh HRD lalu diketik ulang oleh pemiliknya,
        // jadi 0/O dan 1/l/I sengaja dibuang dari alfabetnya.
        $gabungan = '';
        for ($i = 0; $i < 200; $i++) {
            $gabungan .= UserModel::generatePassword();
        }

        $this->assertDoesNotMatchRegularExpression('/[0O1lI]/', $gabungan);
    }

    public function testHanyaHurufDanAngka(): void
    {
        // Tanpa simbol, supaya aman dibacakan lewat telepon atau chat.
        $this->assertMatchesRegularExpression('/^[A-Za-z2-9]+$/', UserModel::generatePassword());
    }

    public function testSelaluBerbedaTiapPemanggilan(): void
    {
        $kumpulan = [];
        for ($i = 0; $i < 500; $i++) {
            $kumpulan[] = UserModel::generatePassword();
        }

        // Dengan ruang 56^12, 500 nilai yang sama adalah tanda generatornya rusak
        // - misalnya tidak sengaja diganti jadi nilai tetap.
        $this->assertCount(500, array_unique($kumpulan));
    }

    public function testTidakPernahSamaDenganNik(): void
    {
        $nik = 'EMP001';

        for ($i = 0; $i < 200; $i++) {
            $this->assertNotSame($nik, UserModel::generatePassword());
        }
    }

    public function testDisimpanSebagaiHashBukanTeksPolos(): void
    {
        $password = UserModel::generatePassword();
        $hash     = UserModel::hashPassword($password);

        $this->assertStringStartsWith('$2y$', $hash, 'Harus bcrypt');
        $this->assertStringNotContainsString($password, $hash, 'Password tidak boleh terbaca di hash');
        $this->assertTrue(UserModel::verifyPassword($password, $hash));
    }

    public function testPasswordSalahDitolak(): void
    {
        $hash = UserModel::hashPassword(UserModel::generatePassword());

        $this->assertFalse(UserModel::verifyPassword('tebakan-asal', $hash));
    }

    public function testHashSelaluBerbedaWalauPasswordnyaSama(): void
    {
        // bcrypt memakai salt acak. Kalau dua hash identik, berarti ada yang
        // mengganti hashing-nya dengan md5/sha1 tanpa salt.
        $password = 'password-yang-sama';

        $this->assertNotSame(
            UserModel::hashPassword($password),
            UserModel::hashPassword($password),
        );
    }
}
