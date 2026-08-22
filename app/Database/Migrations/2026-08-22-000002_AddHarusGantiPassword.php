<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Penanda bahwa pengguna wajib mengganti password sebelum memakai sistem.
 *
 * Password awal dibuat acak lalu diserahkan HRD kepada pemiliknya lewat chat
 * atau lisan. Tanpa penanda ini tidak ada yang memaksa penggantian, sehingga
 * password tersebut bisa bertahan selamanya di riwayat percakapan orang lain.
 *
 * Kolom diisi 1 saat akun dibuat dan saat password direset oleh HRD, lalu
 * dikembalikan ke 0 begitu pemiliknya mengganti password sendiri.
 *
 * Akun yang sudah ada sebelum migration ini dibiarkan bernilai 0 - passwordnya
 * sudah dipilih sendiri oleh masing-masing pemilik, jadi tidak ada alasan
 * memaksa mereka menggantinya.
 */
class AddHarusGantiPassword extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('users', [
            'harus_ganti_password' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 0,
                'after'      => 'password_hash',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('users', 'harus_ganti_password');
    }
}
