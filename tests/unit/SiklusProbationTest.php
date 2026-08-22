<?php

use App\Libraries\RingkasanDashboard;
use App\Libraries\SiklusProbation;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Aturan siklus penilaian dan ringkasan dashboard.
 *
 * Semuanya fungsi murni, jadi "hari ini" selalu dioper sebagai argumen —
 * tanpa itu tesnya akan lulus hari ini dan gagal bulan depan.
 *
 * @internal
 */
final class SiklusProbationTest extends CIUnitTestCase
{
    private const HARI_INI = '2026-08-20';

    // ---------------------------------------------------------------
    // Jadwal penilaian
    // ---------------------------------------------------------------

    public function testBelumPernahDinilaiDanBelumGenapIntervalnya(): void
    {
        // Mulai probation 10 hari lalu — penilaian ke-1 masih 35 hari lagi.
        $j = SiklusProbation::jadwal('2026-08-10', 0, null, self::HARI_INI);

        $this->assertSame(SiklusProbation::BELUM_WAKTUNYA, $j['status']);
        $this->assertSame(35, $j['sisa_hari']);
        $this->assertNull($j['hari_sejak_eval']);
    }

    public function testBelumPernahDinilaiDanSudahLewatIntervalnya(): void
    {
        $j = SiklusProbation::jadwal('2026-06-01', 0, null, self::HARI_INI);

        $this->assertSame(SiklusProbation::PERLU_DINILAI, $j['status']);
        $this->assertSame(0, $j['sisa_hari']);
    }

    public function testTepatDiHariKe45SudahBolehDinilai(): void
    {
        $j = SiklusProbation::jadwal('2026-07-06', 0, null, self::HARI_INI);

        $this->assertSame(SiklusProbation::PERLU_DINILAI, $j['status']);
    }

    /**
     * Tanggal mulai di masa depan pernah terbaca sebagai "sudah lewat 45 hari"
     * karena DateTime::diff()->days tidak bertanda, sehingga karyawan yang
     * probationnya belum mulai ikut muncul sebagai perlu dinilai.
     */
    public function testMulaiProbationDiMasaDepanTidakDianggapJatuhTempo(): void
    {
        $j = SiklusProbation::jadwal('2026-12-01', 0, null, self::HARI_INI);

        $this->assertSame(SiklusProbation::BELUM_WAKTUNYA, $j['status']);
        $this->assertGreaterThan(SiklusProbation::INTERVAL_HARI, $j['sisa_hari']);
    }

    public function testSudahDinilaiSekaliMenungguJatuhTempoBerikutnya(): void
    {
        $j = SiklusProbation::jadwal('2026-01-01', 1, '2026-08-01', self::HARI_INI);

        $this->assertSame(SiklusProbation::SUDAH_DINILAI, $j['status']);
        $this->assertSame(19, $j['hari_sejak_eval']);
        $this->assertSame(26, $j['sisa_hari']);
    }

    public function testSudahDinilaiDuaKaliBerhentiDiMenungguKeputusan(): void
    {
        $j = SiklusProbation::jadwal('2026-01-01', 2, '2026-08-01', self::HARI_INI);

        $this->assertSame(SiklusProbation::SELESAI_DINILAI, $j['status']);
        $this->assertNull($j['sisa_hari']);
    }

    public function testTanpaTanggalMulaiPenilaianTidakDitahan(): void
    {
        $j = SiklusProbation::jadwal(null, 0, null, self::HARI_INI);

        $this->assertSame(SiklusProbation::PERLU_DINILAI, $j['status']);
    }

    // ---------------------------------------------------------------
    // Progres masa probation
    // ---------------------------------------------------------------

    public function testProgresDihitungDariRentangProbation(): void
    {
        $p = SiklusProbation::progres('2026-07-21', '2026-10-19', self::HARI_INI);

        $this->assertSame(90, $p['total']);
        $this->assertSame(30, $p['terpakai']);
        $this->assertSame(60, $p['sisa']);
        $this->assertSame(33, $p['persen']);
        $this->assertFalse($p['lewat']);
    }

    public function testAkhirProbationKosongJatuhKeTigaBulan(): void
    {
        $p = SiklusProbation::progres('2026-07-21', null, self::HARI_INI);

        $this->assertSame(SiklusProbation::DURASI_HARI, $p['total']);
        $this->assertSame('2026-10-19', $p['akhir']);
    }

    public function testProgresTidakLebihDariSeratusPersenSaatTerlewat(): void
    {
        $p = SiklusProbation::progres('2025-01-01', '2025-04-01', self::HARI_INI);

        $this->assertTrue($p['lewat']);
        $this->assertSame(100, $p['persen']);
        $this->assertSame(0, $p['sisa']);
    }

    // ---------------------------------------------------------------
    // Golongan nilai
    // ---------------------------------------------------------------

    public function testBandNilaiMengikutiPanduanNorma(): void
    {
        $this->assertSame('sangat_baik', SiklusProbation::bandNilai(9.0)['kunci']);
        $this->assertSame('baik', SiklusProbation::bandNilai(7.0)['kunci']);
        $this->assertSame('cukup', SiklusProbation::bandNilai(6.0)['kunci']);
        $this->assertSame('kurang', SiklusProbation::bandNilai(5.99)['kunci']);
        $this->assertSame('kosong', SiklusProbation::bandNilai(null)['kunci']);
    }

    // ---------------------------------------------------------------
    // Ringkasan dashboard HRD
    // ---------------------------------------------------------------

    public function testRingkasanHrdMenghitungCorongDanTindakan(): void
    {
        $employees = [
            ['id' => 1, 'nik' => 'A1', 'nama' => 'Belum dinilai',   'departemen' => 'Produksi', 'status' => 'pending', 'mulai_probation' => '2026-08-15', 'akhir_probation' => '2026-11-13', 'team_leader_nama' => 'TL'],
            ['id' => 2, 'nik' => 'A2', 'nama' => 'Baru sekali',     'departemen' => 'Produksi', 'status' => 'pending', 'mulai_probation' => '2026-05-01', 'akhir_probation' => '2026-07-30', 'team_leader_nama' => 'TL'],
            ['id' => 3, 'nik' => 'A3', 'nama' => 'Menunggu HRD',    'departemen' => 'QC',       'status' => 'pending', 'mulai_probation' => '2026-04-01', 'akhir_probation' => '2026-06-30', 'team_leader_nama' => 'TL'],
            ['id' => 4, 'nik' => 'A4', 'nama' => 'Sudah lulus',     'departemen' => 'QC',       'status' => 'lulus',   'mulai_probation' => '2026-01-01', 'akhir_probation' => '2026-04-01', 'team_leader_nama' => 'TL'],
        ];
        $penilaian = [
            ['employee_id' => 2, 'nomor_penilaian' => 1, 'nilai_total' => '8.00', 'tanggal_penilaian' => '2026-06-15 10:00:00', 'dilihat_at' => null],
            ['employee_id' => 3, 'nomor_penilaian' => 1, 'nilai_total' => '5.00', 'tanggal_penilaian' => '2026-05-15 10:00:00', 'dilihat_at' => '2026-05-16 08:00:00'],
            ['employee_id' => 3, 'nomor_penilaian' => 2, 'nilai_total' => '6.50', 'tanggal_penilaian' => '2026-06-30 10:00:00', 'dilihat_at' => null],
            ['employee_id' => 4, 'nomor_penilaian' => 1, 'nilai_total' => '9.20', 'tanggal_penilaian' => '2026-02-01 10:00:00', 'dilihat_at' => '2026-02-02 08:00:00'],
        ];
        $keputusan = [
            ['employee_id' => 4, 'status_akhir' => 'lulus', 'nomor_sk' => '123', 'tanggal_sk' => '2026-04-01', 'tanggal_diangkat' => '2026-04-02'],
        ];

        $r = RingkasanDashboard::hrd($employees, $penilaian, $keputusan, self::HARI_INI);

        $corong = array_column($r['corong'], 'jumlah', 'kunci');
        $this->assertSame(1, $corong[SiklusProbation::TAHAP_BELUM]);
        $this->assertSame(1, $corong[SiklusProbation::TAHAP_SATU_KALI]);
        $this->assertSame(1, $corong[SiklusProbation::TAHAP_MENUNGGU]);
        $this->assertSame(1, $corong[SiklusProbation::TAHAP_SELESAI]);

        // A3 sudah dinilai 2x tapi belum ada keputusannya.
        $this->assertSame(1, $r['tindakan']['menunggu_keputusan']['jumlah']);
        $this->assertSame('Menunggu HRD', $r['tindakan']['menunggu_keputusan']['daftar'][0]['nama']);

        // Dua lembar belum pernah dibuka Team Member-nya.
        $this->assertSame(2, $r['tindakan']['belum_dibuka']['jumlah']);

        // A2 dan A3 masa probationnya sudah lewat, A1 masih jauh.
        $this->assertSame(2, $r['tindakan']['segera_berakhir']['jumlah']);

        $this->assertSame(1, $r['hasil']['lulus']);
        $this->assertSame(3, $r['hasil']['berjalan']);
        $this->assertSame(100, $r['hasil']['tingkat_kelulusan']);

        $this->assertSame(4, $r['nilai']['jumlah']);
        $this->assertSame(7.18, $r['nilai']['rata']);
        $this->assertSame(1, $r['nilai']['di_bawah_standar']);
    }

    public function testRingkasanHrdMenandaiSkYangBelumBisaTerbit(): void
    {
        $employees = [
            ['id' => 9, 'nik' => 'B1', 'nama' => 'Lulus tanpa nomor SK', 'departemen' => 'QC', 'status' => 'lulus', 'mulai_probation' => '2026-01-01', 'akhir_probation' => '2026-04-01', 'team_leader_nama' => 'TL'],
        ];
        $keputusan = [
            ['employee_id' => 9, 'status_akhir' => 'lulus', 'nomor_sk' => '', 'tanggal_sk' => null, 'tanggal_diangkat' => '2026-04-02'],
        ];

        $r = RingkasanDashboard::hrd($employees, [], $keputusan, self::HARI_INI);

        $this->assertSame(1, $r['tindakan']['sk_belum_terbit']['jumlah']);
        $this->assertSame('Nomor SK belum diisi', $r['tindakan']['sk_belum_terbit']['daftar'][0]['sebab']);
    }

    public function testRingkasanHrdTahanTanpaDataSamaSekali(): void
    {
        $r = RingkasanDashboard::hrd([], [], [], self::HARI_INI);

        $this->assertSame(0, $r['total']);
        $this->assertNull($r['hasil']['tingkat_kelulusan']);
        $this->assertNull($r['nilai']['rata']);
        $this->assertCount(6, $r['tren']);
    }

    // ---------------------------------------------------------------
    // Ringkasan dashboard Team Member
    // ---------------------------------------------------------------

    public function testRingkasanMemberMenyusunEmpatLangkah(): void
    {
        $employee = ['id' => 1, 'nik' => 'C1', 'nama' => 'Andi', 'status' => 'pending',
                     'mulai_probation' => '2026-05-01', 'akhir_probation' => '2026-07-30'];
        $evaluations = [
            ['id' => 5, 'nomor_penilaian' => 1, 'nilai_total' => '7.40', 'tanggal_penilaian' => '2026-06-15 09:00:00', 'dilihat_at' => null],
        ];

        $r = RingkasanDashboard::member($employee, $evaluations, null, self::HARI_INI);

        $this->assertSame(7.4, $r['rata']);
        $this->assertSame(1, $r['belum_dibuka']);
        $this->assertCount(4, $r['langkah']);
        $this->assertSame('selesai', $r['langkah'][1]['status']);   // penilaian ke-1 sudah
        $this->assertSame('aktif', $r['langkah'][2]['status']);     // penilaian ke-2 giliran berikutnya
        $this->assertSame('menunggu', $r['langkah'][3]['status']);  // keputusan HRD belum bisa
        $this->assertStringContainsString('Perkiraan', $r['langkah'][2]['detail']);
    }

    public function testRingkasanMemberTanpaPenilaian(): void
    {
        $employee = ['id' => 1, 'nik' => 'C2', 'nama' => 'Budi', 'status' => 'pending',
                     'mulai_probation' => '2026-08-01', 'akhir_probation' => '2026-10-30'];

        $r = RingkasanDashboard::member($employee, [], null, self::HARI_INI);

        $this->assertNull($r['rata']);
        $this->assertSame([], $r['penilaian']);
        $this->assertSame('aktif', $r['langkah'][1]['status']);
    }
}
