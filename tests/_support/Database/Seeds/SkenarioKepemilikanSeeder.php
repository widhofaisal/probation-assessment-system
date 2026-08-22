<?php

namespace Tests\Support\Database\Seeds;

use App\Models\UserModel;
use CodeIgniter\Database\Seeder;

/**
 * Data minimal untuk menguji batas kepemilikan data.
 *
 * Bentuknya sengaja dibuat sesederhana mungkin tapi cukup untuk memancing
 * kesalahan yang ingin dijaga:
 *
 *   TL_SATU  memegang KARYAWAN_SATU  dan menilainya  -> PENILAIAN_SATU
 *   TL_DUA   memegang KARYAWAN_DUA   dan menilainya  -> PENILAIAN_DUA
 *
 * Dengan dua pasangan yang setara, tes bisa menanyakan hal yang tepat:
 * bolehkah TL_DUA menyentuh milik TL_SATU?
 */
class SkenarioKepemilikanSeeder extends Seeder
{
    /** Id tetap supaya tes bisa merujuk tanpa menebak. */
    public const HRD           = 1;
    public const TL_SATU       = 2;
    public const TL_DUA        = 3;
    public const MEMBER_SATU   = 4;   // akun login milik KARYAWAN_SATU
    public const MEMBER_DUA    = 5;   // akun login milik KARYAWAN_DUA

    public const KARYAWAN_SATU = 1;
    public const KARYAWAN_DUA  = 2;

    public const PENILAIAN_SATU = 1;
    public const PENILAIAN_DUA  = 2;

    /** Password semua akun uji. */
    public const PASSWORD = 'rahasia-uji-123';

    public function run(): void
    {
        $hash = UserModel::hashPassword(self::PASSWORD);
        $now  = '2026-08-01 08:00:00';

        $this->db->table('users')->insertBatch([
            [
                'id' => self::HRD, 'nik' => 'HRD900', 'nama' => 'HRD Uji',
                'email' => 'hrd.uji@contoh.test', 'password_hash' => $hash, 'role' => 'hrd',
                'departemen' => 'Human Resources', 'posisi' => 'HR Manager',
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => self::TL_SATU, 'nik' => 'TL900', 'nama' => 'Team Leader Satu',
                'email' => 'tl.satu@contoh.test', 'password_hash' => $hash, 'role' => 'team-leader',
                'departemen' => 'Produksi', 'posisi' => 'Supervisor',
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => self::TL_DUA, 'nik' => 'TL901', 'nama' => 'Team Leader Dua',
                'email' => 'tl.dua@contoh.test', 'password_hash' => $hash, 'role' => 'team-leader',
                'departemen' => 'Quality Control', 'posisi' => 'Supervisor',
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => self::MEMBER_SATU, 'nik' => 'EMP900', 'nama' => 'Karyawan Satu',
                'email' => 'emp.satu@contoh.test', 'password_hash' => $hash,
                'role' => 'probationary-employee', 'departemen' => 'Produksi', 'posisi' => 'Operator',
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => self::MEMBER_DUA, 'nik' => 'EMP901', 'nama' => 'Karyawan Dua',
                'email' => 'emp.dua@contoh.test', 'password_hash' => $hash,
                'role' => 'probationary-employee', 'departemen' => 'Quality Control', 'posisi' => 'Inspector',
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);

        // NIK karyawan sengaja disamakan dengan NIK akun login, karena beberapa
        // controller mencari karyawan lewat findByNik(session('nik')).
        $this->db->table('employees')->insertBatch([
            [
                'id' => self::KARYAWAN_SATU, 'nik' => 'EMP900', 'nama' => 'Karyawan Satu',
                'departemen' => 'Produksi', 'posisi' => 'Operator', 'email' => 'emp.satu@contoh.test',
                'mulai_probation' => '2026-05-01', 'akhir_probation' => '2026-07-30',
                'status' => 'pending', 'team_leader_id' => self::TL_SATU, 'created_by' => self::HRD,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => self::KARYAWAN_DUA, 'nik' => 'EMP901', 'nama' => 'Karyawan Dua',
                'departemen' => 'Quality Control', 'posisi' => 'Inspector', 'email' => 'emp.dua@contoh.test',
                'mulai_probation' => '2026-05-01', 'akhir_probation' => '2026-07-30',
                'status' => 'pending', 'team_leader_id' => self::TL_DUA, 'created_by' => self::HRD,
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);

        $this->db->table('penilaian')->insertBatch([
            [
                'id' => self::PENILAIAN_SATU, 'employee_id' => self::KARYAWAN_SATU,
                'nomor_penilaian' => 1, 'team_leader_id' => self::TL_SATU,
                'tanggal_penilaian' => $now, 'tanggal_mulai_penilaian' => '2026-05-01',
                'tanggal_selesai_penilaian' => '2026-06-15', 'nilai_total' => 8.50,
                'status' => 'submitted', 'catatan_team_leader' => 'Catatan untuk karyawan satu',
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => self::PENILAIAN_DUA, 'employee_id' => self::KARYAWAN_DUA,
                'nomor_penilaian' => 1, 'team_leader_id' => self::TL_DUA,
                'tanggal_penilaian' => $now, 'tanggal_mulai_penilaian' => '2026-05-01',
                'tanggal_selesai_penilaian' => '2026-06-15', 'nilai_total' => 7.25,
                'status' => 'submitted', 'catatan_team_leader' => 'Catatan untuk karyawan dua',
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);

        $this->db->table('penilaian_detail')->insertBatch([
            [
                'penilaian_id' => self::PENILAIAN_SATU, 'kategori' => 'A. Pengetahuan Akan Tugas (Knowledge)',
                'aspek' => 'Mengerti prosedur kerja standar.', 'nilai' => 9, 'alasan' => '', 'created_at' => $now,
            ],
            [
                'penilaian_id' => self::PENILAIAN_DUA, 'kategori' => 'A. Pengetahuan Akan Tugas (Knowledge)',
                'aspek' => 'Mengerti prosedur kerja standar.', 'nilai' => 7, 'alasan' => '', 'created_at' => $now,
            ],
        ]);
    }
}
