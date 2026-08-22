<?php

namespace App\Database\Seeds;

use App\Models\UserModel;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;

/**
 * Membuat satu akun HRD pertama untuk instalasi yang benar-benar kosong.
 *
 * Dipakai setelah `php spark migrate` pada instalasi baru. Tanpa ini tidak ada
 * seorang pun yang bisa masuk, karena akun hanya bisa dibuat dari dalam
 * aplikasi oleh HRD - dan HRD pertama tidak punya siapa pun yang membuatkannya.
 *
 * Password dibuat acak dan ditampilkan sekali di layar. Akunnya ditandai wajib
 * ganti password, jadi nilai yang tercetak di terminal ini hanya berlaku sampai
 * pemiliknya login pertama kali.
 *
 * Jalankan:
 *   php spark db:seed AkunHrdPertamaSeeder
 *
 * NIK dan nama bisa disesuaikan lewat variabel lingkungan:
 *   HRD_NIK=HRD001 HRD_NAMA="Nama Lengkap" php spark db:seed AkunHrdPertamaSeeder
 */
class AkunHrdPertamaSeeder extends Seeder
{
    public function run(): void
    {
        $nik  = getenv('HRD_NIK') ?: 'HRD001';
        $nama = getenv('HRD_NAMA') ?: 'Administrator HRD';

        $sudahAda = $this->db->table('users')->where('nik', $nik)->countAllResults();
        if ($sudahAda > 0) {
            CLI::write("Akun dengan NIK {$nik} sudah ada. Tidak ada yang diubah.", 'yellow');

            return;
        }

        $password = UserModel::generatePassword();
        $sekarang = date('Y-m-d H:i:s');

        $this->db->table('users')->insert([
            'nik'                  => $nik,
            'nama'                 => $nama,
            'email'                => null,
            'password_hash'        => UserModel::hashPassword($password),
            'harus_ganti_password' => 1,
            'role'                 => 'hrd',
            'departemen'           => 'Human Resources',
            'posisi'               => 'HR Manager',
            'created_at'           => $sekarang,
            'updated_at'           => $sekarang,
        ]);

        CLI::newLine();
        CLI::write('================================================', 'green');
        CLI::write(' AKUN HRD PERTAMA DIBUAT', 'green');
        CLI::write('================================================', 'green');
        CLI::write('  NIK      : ' . $nik);
        CLI::write('  Nama     : ' . $nama);
        CLI::write('  Password : ' . $password, 'yellow');
        CLI::newLine();
        CLI::write('  Catat password di atas sekarang - nilainya tidak', 'light_gray');
        CLI::write('  disimpan dalam bentuk terbaca dan tidak bisa', 'light_gray');
        CLI::write('  ditampilkan lagi.', 'light_gray');
        CLI::newLine();
        CLI::write('  Anda akan diminta menggantinya saat login pertama.', 'light_gray');
        CLI::newLine();
    }
}
