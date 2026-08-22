<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Skema awal sistem penilaian probation.
 *
 * Sebelumnya satu-satunya cara membuat basis data adalah mengimpor
 * database_dump.sql, yang selalu ikut membawa data demo. Migration ini
 * memberi jalur instalasi bersih (`php spark migrate`) sekaligus menjadi
 * titik awal versi untuk perubahan skema berikutnya.
 *
 * Isinya sengaja dibuat setara dengan struktur di database_dump.sql, dengan
 * dua penyesuaian yang disengaja:
 *
 * Isinya dijaga setara dengan struktur di database_dump.sql, karena keduanya
 * adalah jalur instalasi yang sama-sama dipakai: klien lama mengimpor dump,
 * instalasi baru menjalankan migration. Skema yang berbeda di antara keduanya
 * akan melahirkan bug yang hanya muncul di salah satu jalur.
 *
 * Dua perbedaan yang disengaja terhadap dump:
 *
 *  - ON UPDATE CURRENT_TIMESTAMP tidak dipasang, karena Forge tidak
 *    mendukungnya dan seluruh model kecuali AuditLogModel sudah mengisi
 *    updated_at lewat $useTimestamps.
 *  - Indeks non-unik yang menduplikasi unique key tidak disalin. Dump memuat
 *    idx_nik dan idx_email di samping UNIQUE KEY pada kolom yang sama; unique
 *    key sudah menjadi indeks, jadi yang kedua hanya menambah beban tulis
 *    tanpa mempercepat apa pun.
 *
 * Selebihnya sudah diverifikasi identik: seluruh kolom beserta tipe, nullability,
 * nilai bawaan, dan kesembilan foreign key.
 */
class CreateInitialSchema extends Migration
{
    public function up(): void
    {
        $this->buatUsers();
        $this->buatEmployees();
        $this->buatPenilaian();
        $this->buatPenilaianDetail();
        $this->buatPenilaianKeputusan();
        $this->buatAuditLogs();
    }

    public function down(): void
    {
        // Urutan terbalik dari up(), karena ada foreign key antar tabel.
        $this->forge->dropTable('audit_logs', true);
        $this->forge->dropTable('penilaian_keputusan', true);
        $this->forge->dropTable('penilaian_detail', true);
        $this->forge->dropTable('penilaian', true);
        $this->forge->dropTable('employees', true);
        $this->forge->dropTable('users', true);
    }

    // -----------------------------------------------------------------

    private function buatUsers(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nik'           => ['type' => 'VARCHAR', 'constraint' => 20],
            'nama'          => ['type' => 'VARCHAR', 'constraint' => 100],
            'email'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'role'          => ['type' => 'ENUM', 'constraint' => ['hrd', 'team-leader', 'probationary-employee']],
            'departemen'    => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'posisi'        => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'jenis_kelamin' => ['type' => 'ENUM', 'constraint' => ['L', 'P'], 'null' => true],
            'tanggal_lahir' => ['type' => 'DATE', 'null' => true],
            'alamat'        => ['type' => 'TEXT', 'null' => true],
            'created_at'    => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'    => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('nik');
        $this->forge->addUniqueKey('email');
        $this->forge->addKey('role');
        $this->forge->createTable('users', true);
    }

    private function buatEmployees(): void
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nik'             => ['type' => 'VARCHAR', 'constraint' => 20],
            'nama'            => ['type' => 'VARCHAR', 'constraint' => 100],
            'departemen'      => ['type' => 'VARCHAR', 'constraint' => 50],
            'posisi'          => ['type' => 'VARCHAR', 'constraint' => 50],
            'email'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'jenis_kelamin'   => ['type' => 'ENUM', 'constraint' => ['L', 'P'], 'null' => true],
            'tanggal_lahir'   => ['type' => 'DATE', 'null' => true],
            'alamat'          => ['type' => 'TEXT', 'null' => true],
            'tanggal_masuk'   => ['type' => 'DATE', 'null' => true],
            'mulai_probation' => ['type' => 'DATE'],
            'akhir_probation' => ['type' => 'DATE', 'null' => true],
            'status'          => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'lulus', 'tidak-lulus', 'warning'],
                'default'    => 'pending',
            ],
            'team_leader_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_by'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'      => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'      => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('nik');
        $this->forge->addKey('status');
        $this->forge->addKey('deleted_at');
        $this->forge->addForeignKey('team_leader_id', 'users', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('created_by', 'users', 'id', '', 'SET NULL');
        $this->forge->createTable('employees', true);
    }

    private function buatPenilaian(): void
    {
        $this->forge->addField([
            'id'                        => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'employee_id'               => ['type' => 'INT', 'unsigned' => true],
            'nomor_penilaian'           => ['type' => 'TINYINT', 'default' => 1],
            'team_leader_id'            => ['type' => 'INT', 'unsigned' => true],
            'tanggal_penilaian'         => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'tanggal_mulai_penilaian'   => ['type' => 'DATE', 'null' => true],
            'tanggal_selesai_penilaian' => ['type' => 'DATE', 'null' => true],
            'nilai_total'               => ['type' => 'DECIMAL', 'constraint' => '4,2', 'null' => true],
            'status'                    => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'submitted'],
                'default'    => 'submitted',
            ],
            'catatan_team_leader'       => ['type' => 'LONGTEXT', 'null' => true],
            'dilihat_at'                => ['type' => 'TIMESTAMP', 'null' => true],
            'diunduh_at'                => ['type' => 'TIMESTAMP', 'null' => true],
            'created_at'                => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'                => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('employee_id');
        $this->forge->addKey('team_leader_id');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('employee_id', 'employees', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('team_leader_id', 'users', 'id');
        $this->forge->createTable('penilaian', true);
    }

    private function buatPenilaianDetail(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'penilaian_id' => ['type' => 'INT', 'unsigned' => true],
            'kategori'     => ['type' => 'VARCHAR', 'constraint' => 100],
            'aspek'        => ['type' => 'VARCHAR', 'constraint' => 255],
            'nilai'        => ['type' => 'TINYINT'],
            'alasan'       => ['type' => 'LONGTEXT', 'null' => true],
            'created_at'   => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('penilaian_id');
        $this->forge->addKey('kategori');
        $this->forge->addForeignKey('penilaian_id', 'penilaian', 'id', '', 'CASCADE');
        $this->forge->createTable('penilaian_detail', true);

        // Forge tidak punya API untuk CHECK, jadi ditambahkan manual. Nilai aspek
        // hanya sah pada rentang 1-10; batas ini ditegakkan di basis data supaya
        // tetap berlaku walau ada jalur penyimpanan yang melewatkan validasi.
        //
        // Hanya dijalankan di MySQL. SQLite tidak mendukung penambahan CHECK
        // lewat ALTER TABLE, dan basis data uji memakai SQLite in-memory supaya
        // rangkaian tes bisa dijalankan tanpa menyiapkan MySQL lebih dulu.
        if ($this->db->DBDriver === 'MySQLi') {
            $this->db->query(
                'ALTER TABLE `penilaian_detail`
                 ADD CONSTRAINT `penilaian_detail_chk_1` CHECK ((`nilai` >= 1 AND `nilai` <= 10))'
            );
        }
    }

    private function buatPenilaianKeputusan(): void
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'penilaian_id'     => ['type' => 'INT', 'unsigned' => true],
            'employee_id'      => ['type' => 'INT', 'unsigned' => true],
            'tanggal_diangkat' => ['type' => 'DATE', 'null' => true],
            'tanggal_diakhiri' => ['type' => 'DATE', 'null' => true],
            'lain_lain'        => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'nomor_sk'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'tanggal_sk'       => ['type' => 'DATE', 'null' => true],
            'status_akhir'     => ['type' => 'ENUM', 'constraint' => ['lulus', 'tidak-lulus', 'warning']],
            'hrd_id'           => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'       => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'       => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        // Satu penilaian hanya boleh punya satu keputusan.
        $this->forge->addUniqueKey('penilaian_id');
        $this->forge->addKey('employee_id');
        $this->forge->addKey('hrd_id');
        $this->forge->addForeignKey('penilaian_id', 'penilaian', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('employee_id', 'employees', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('hrd_id', 'users', 'id', '', 'SET NULL');
        $this->forge->createTable('penilaian_keputusan', true);
    }

    private function buatAuditLogs(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'action'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'table_name'  => ['type' => 'VARCHAR', 'constraint' => 100],
            'record_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'old_values'  => ['type' => 'JSON', 'null' => true],
            'new_values'  => ['type' => 'JSON', 'null' => true],
            'description' => ['type' => 'LONGTEXT', 'null' => true],
            'ip_address'  => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            // AuditLogModel mematikan $useTimestamps, jadi nilai ini harus
            // datang dari basis data.
            'created_at'  => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('action');
        $this->forge->addKey('table_name');
        $this->forge->addKey('created_at');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'SET NULL');
        $this->forge->createTable('audit_logs', true);
    }
}
