<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Database\Seeds\SkenarioKepemilikanSeeder as Data;

/**
 * Batas kepemilikan data antar pengguna dengan role yang sama.
 *
 * Filter di app/Filters/ hanya tahu ROLE, tidak tahu SIAPA PEMILIK sebuah baris
 * data. Jadi pertanyaan "bolehkah Team Leader Dua membuka penilaian milik Team
 * Leader Satu?" tidak bisa dijawab filter, dan harus dijawab controller.
 *
 * Logikanya tersebar di beberapa controller - persis pola yang dulu membuat
 * pengecekan role kebobolan di dua endpoint. Tes di sini menjadi penjaganya:
 * kalau ada yang menyederhanakan salah satu pengecekan itu, tes ini gagal.
 *
 * Berbeda dengan KontrolAksesTest yang berhenti di lapisan filter, tes ini
 * memang harus menyentuh basis data - kepemilikan hanya bisa diuji kalau
 * datanya benar-benar ada.
 *
 * @internal
 */
final class KepemilikanDataTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = 'App';
    protected $seed        = Data::class;
    protected $basePath    = APPPATH . 'Database';

    private function sesi(string $peran): array
    {
        $daftar = [
            'hrd' => [
                'user_id' => Data::HRD, 'nik' => 'HRD900', 'nama' => 'HRD Uji',
                'role' => 'hrd', 'departemen' => 'Human Resources', 'posisi' => 'HR Manager',
            ],
            'tl_satu' => [
                'user_id' => Data::TL_SATU, 'nik' => 'TL900', 'nama' => 'Team Leader Satu',
                'role' => 'team-leader', 'departemen' => 'Produksi', 'posisi' => 'Supervisor',
            ],
            'tl_dua' => [
                'user_id' => Data::TL_DUA, 'nik' => 'TL901', 'nama' => 'Team Leader Dua',
                'role' => 'team-leader', 'departemen' => 'Quality Control', 'posisi' => 'Supervisor',
            ],
            'member_satu' => [
                'user_id' => Data::MEMBER_SATU, 'nik' => 'EMP900', 'nama' => 'Karyawan Satu',
                'role' => 'probationary-employee', 'departemen' => 'Produksi', 'posisi' => 'Operator',
            ],
            'member_dua' => [
                'user_id' => Data::MEMBER_DUA, 'nik' => 'EMP901', 'nama' => 'Karyawan Dua',
                'role' => 'probationary-employee', 'departemen' => 'Quality Control', 'posisi' => 'Inspector',
            ],
        ];

        return $daftar[$peran];
    }

    /** Ditolak = tidak menampilkan isi, entah lewat pengalihan atau status 403. */
    private function pastikanDitolak($hasil, string $pesan): void
    {
        $status = $hasil->response()->getStatusCode();

        $this->assertTrue(
            $hasil->isRedirect() || in_array($status, [401, 403, 404], true),
            $pesan . " (status yang diterima: {$status})",
        );
    }

    // ---------------------------------------------------------------
    // Team Leader terhadap penilaian milik Team Leader lain
    // ---------------------------------------------------------------

    public function testTeamLeaderTidakBisaMembukaPenilaianTimLain(): void
    {
        $hasil = $this->withSession($this->sesi('tl_dua'))
            ->call('get', '/evaluations/' . Data::PENILAIAN_SATU);

        $this->pastikanDitolak($hasil, 'Team Leader Dua tidak boleh membuka penilaian milik Team Leader Satu');
    }

    public function testTeamLeaderTidakBisaMengeditPenilaianTimLain(): void
    {
        $hasil = $this->withSession($this->sesi('tl_dua'))
            ->call('get', '/evaluations/' . Data::PENILAIAN_SATU . '/edit');

        $this->pastikanDitolak($hasil, 'Team Leader Dua tidak boleh membuka form edit penilaian milik Team Leader Satu');
    }

    public function testTeamLeaderTidakBisaMenghapusPenilaianTimLain(): void
    {
        // Token CSRF yang sah sengaja disertakan. Tanpa itu permintaan sudah
        // ditolak filter CSRF lebih dulu, sehingga tes ini lulus tanpa pernah
        // menyentuh pengecekan kepemilikan yang justru ingin diuji.
        $hasil = $this->withSession($this->sesi('tl_dua'))
            ->withHeaders(['X-CSRF-TOKEN' => csrf_hash()])
            ->call('delete', '/evaluations/' . Data::PENILAIAN_SATU);

        $this->pastikanDitolak($hasil, 'Team Leader Dua tidak boleh menghapus penilaian milik Team Leader Satu');

        // Yang paling penting: datanya harus masih ada.
        $this->seeInDatabase('penilaian', ['id' => Data::PENILAIAN_SATU]);
    }

    public function testTeamLeaderTetapBisaMembukaPenilaianTimnyaSendiri(): void
    {
        // Pembanding. Tanpa ini, tes di atas tetap lulus seandainya semua
        // permintaan ditolak karena sebab lain.
        $hasil = $this->withSession($this->sesi('tl_satu'))
            ->call('get', '/evaluations/' . Data::PENILAIAN_SATU);

        $hasil->assertOK();
    }

    // ---------------------------------------------------------------
    // Team Member terhadap data karyawan lain
    // ---------------------------------------------------------------

    public function testMemberTidakBisaMembukaPenilaianKaryawanLain(): void
    {
        $hasil = $this->withSession($this->sesi('member_dua'))
            ->call('get', '/evaluations/' . Data::PENILAIAN_SATU);

        $this->pastikanDitolak($hasil, 'Karyawan Dua tidak boleh membuka penilaian milik Karyawan Satu');
    }

    public function testMemberTetapBisaMembukaPenilaiannyaSendiri(): void
    {
        $hasil = $this->withSession($this->sesi('member_satu'))
            ->call('get', '/evaluations/' . Data::PENILAIAN_SATU);

        $hasil->assertOK();
    }

    // ---------------------------------------------------------------
    // Laporan dan berkas PDF
    // ---------------------------------------------------------------

    public function testTeamLeaderTidakBisaMembukaLaporanTimLain(): void
    {
        $hasil = $this->withSession($this->sesi('tl_dua'))
            ->call('get', '/reports/evaluation/' . Data::PENILAIAN_SATU);

        $this->pastikanDitolak($hasil, 'Team Leader Dua tidak boleh membuka laporan penilaian milik Team Leader Satu');
    }

    public function testMemberTidakBisaMembukaLaporanKaryawanLain(): void
    {
        $hasil = $this->withSession($this->sesi('member_dua'))
            ->call('get', '/reports/evaluation/' . Data::PENILAIAN_SATU);

        $this->pastikanDitolak($hasil, 'Karyawan Dua tidak boleh membuka laporan milik Karyawan Satu');
    }

    public function testMemberTidakBisaMengunduhPdfGabunganKaryawanLain(): void
    {
        // Endpoint ini memakai id KARYAWAN, bukan id penilaian - jalur akses
        // yang berbeda, jadi perlu dijaga terpisah.
        $hasil = $this->withSession($this->sesi('member_dua'))
            ->call('get', '/reports/pdf-all/' . Data::KARYAWAN_SATU);

        $this->pastikanDitolak($hasil, 'Karyawan Dua tidak boleh mengunduh PDF gabungan milik Karyawan Satu');
    }

    public function testMemberTidakBisaMembukaSuratKeputusanKaryawanLain(): void
    {
        $hasil = $this->withSession($this->sesi('member_dua'))
            ->call('get', '/reports/sk/' . Data::KARYAWAN_SATU);

        $this->pastikanDitolak($hasil, 'Karyawan Dua tidak boleh membuka SK milik Karyawan Satu');
    }

    public function testTeamLeaderTidakBisaMembukaSuratKeputusanTimLain(): void
    {
        $hasil = $this->withSession($this->sesi('tl_dua'))
            ->call('get', '/reports/sk/' . Data::KARYAWAN_SATU);

        $this->pastikanDitolak($hasil, 'Team Leader Dua tidak boleh membuka SK milik tim Team Leader Satu');
    }

    // ---------------------------------------------------------------
    // HRD
    // ---------------------------------------------------------------

    public function testHrdBolehMembukaPenilaianSiapaPun(): void
    {
        foreach ([Data::PENILAIAN_SATU, Data::PENILAIAN_DUA] as $id) {
            $hasil = $this->withSession($this->sesi('hrd'))->call('get', '/evaluations/' . $id);

            $hasil->assertOK();
        }
    }

    // ---------------------------------------------------------------
    // Kepemilikan yang berpindah tangan
    // ---------------------------------------------------------------

    public function testTeamLeaderLamaTetapBisaMembukaHasilPenilaiannyaSetelahKaryawanPindah(): void
    {
        // Karyawan Satu dipindah ke Team Leader Dua, tetapi penilaian lamanya
        // tetap dibuat oleh Team Leader Satu. Yang dulu menilai harus tetap bisa
        // membuka hasil penilaiannya sendiri, kalau tidak riwayat penilaian
        // menjadi tidak bisa diakses siapa pun selain HRD.
        $this->db->table('employees')
            ->where('id', Data::KARYAWAN_SATU)
            ->update(['team_leader_id' => Data::TL_DUA]);

        $hasil = $this->withSession($this->sesi('tl_satu'))
            ->call('get', '/reports/pdf-all/' . Data::KARYAWAN_SATU);

        $this->assertFalse(
            $hasil->isRedirect(),
            'Team Leader yang dulu menilai seharusnya tetap bisa mengakses hasil penilaiannya',
        );
    }
}
