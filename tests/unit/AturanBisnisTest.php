<?php

use App\Models\EmployeeModel;
use App\Models\EvaluationDecisionModel;
use App\Models\EvaluationDetailModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Aturan bisnis yang berdiri sendiri: masa probation, kesiapan SK, penomoran
 * SK, dan daftar aspek penilaian.
 *
 * Semuanya fungsi murni tanpa basis data, tetapi keputusan yang dihasilkannya
 * berakibat nyata - menentukan kapan seorang karyawan boleh dinilai, dan kapan
 * surat pengangkatan boleh terbit.
 *
 * @internal
 */
final class AturanBisnisTest extends CIUnitTestCase
{
    // ---------------------------------------------------------------
    // Akhir masa probation
    // ---------------------------------------------------------------

    public function testAkhirProbationSembilanPuluhHariSetelahMulai(): void
    {
        $this->assertSame('2026-03-31', EmployeeModel::calculateProbationEnd('2025-12-31'));
    }

    public function testAkhirProbationMelintasiPergantianTahun(): void
    {
        $this->assertSame('2026-01-29', EmployeeModel::calculateProbationEnd('2025-10-31'));
    }

    public function testAkhirProbationMemperhitungkanTahunKabisat(): void
    {
        // 2028 kabisat, jadi Februari punya 29 hari dan hasilnya bergeser.
        $this->assertSame('2028-03-31', EmployeeModel::calculateProbationEnd('2028-01-01'));
        $this->assertSame('2027-04-01', EmployeeModel::calculateProbationEnd('2027-01-01'));
    }

    public function testAkhirProbationSelaluBerselisihSembilanPuluhHari(): void
    {
        foreach (['2026-01-15', '2026-06-30', '2026-11-01', '2027-02-28'] as $mulai) {
            $akhir  = EmployeeModel::calculateProbationEnd($mulai);
            $selisih = (new DateTime($mulai))->diff(new DateTime($akhir))->days;

            $this->assertSame(90, $selisih, "Selisih dari {$mulai} seharusnya 90 hari");
        }
    }

    // ---------------------------------------------------------------
    // Kesiapan Surat Keputusan
    // ---------------------------------------------------------------

    public function testSkSiapHanyaSaatKetigaSyaratTerpenuhi(): void
    {
        $lengkap = [
            'status_akhir' => 'lulus',
            'nomor_sk' => '001',
            'tanggal_diangkat' => '2026-02-01',
        ];

        $this->assertTrue(EvaluationDecisionModel::skSiap($lengkap));
    }

    public static function keputusanYangBelumSiap(): array
    {
        return [
            'belum ada keputusan'       => [null],
            'status tidak lulus'        => [['status_akhir' => 'tidak-lulus', 'nomor_sk' => '001', 'tanggal_diangkat' => '2026-02-01']],
            'status warning'            => [['status_akhir' => 'warning', 'nomor_sk' => '001', 'tanggal_diangkat' => '2026-02-01']],
            'nomor SK kosong'           => [['status_akhir' => 'lulus', 'nomor_sk' => '', 'tanggal_diangkat' => '2026-02-01']],
            'nomor SK hanya spasi'      => [['status_akhir' => 'lulus', 'nomor_sk' => '   ', 'tanggal_diangkat' => '2026-02-01']],
            'nomor SK belum diisi'      => [['status_akhir' => 'lulus', 'tanggal_diangkat' => '2026-02-01']],
            'tanggal diangkat kosong'   => [['status_akhir' => 'lulus', 'nomor_sk' => '001', 'tanggal_diangkat' => null]],
            'tanggal diangkat hilang'   => [['status_akhir' => 'lulus', 'nomor_sk' => '001']],
        ];
    }

    /**
     * @dataProvider keputusanYangBelumSiap
     */
    public function testSkBelumBolehTerbit(?array $keputusan): void
    {
        // Surat berbunyi "Terhitung sejak tanggal ..., mengangkat karyawan
        // tersebut" - menerbitkannya tanpa salah satu syarat berarti mengeluarkan
        // dokumen resmi yang cacat.
        $this->assertFalse(EvaluationDecisionModel::skSiap($keputusan));
    }

    // ---------------------------------------------------------------
    // Penomoran SK
    // ---------------------------------------------------------------

    public function testNomorSkMemakaiBulanRomawiDariTanggalTerbit(): void
    {
        $this->assertSame(
            '33372/SMJ/RSC-HRD/VIII/2026',
            EvaluationDecisionModel::nomorSkLengkap(['nomor_sk' => '33372', 'tanggal_sk' => '2026-08-22']),
        );
        $this->assertSame(
            '001/SMJ/RSC-HRD/I/2026',
            EvaluationDecisionModel::nomorSkLengkap(['nomor_sk' => '001', 'tanggal_sk' => '2026-01-05']),
        );
        $this->assertSame(
            '010/SMJ/RSC-HRD/XII/2025',
            EvaluationDecisionModel::nomorSkLengkap(['nomor_sk' => '010', 'tanggal_sk' => '2025-12-31']),
        );
    }

    public function testNomorSkKosongSaatNomorUrutBelumDiisi(): void
    {
        $this->assertSame('', EvaluationDecisionModel::nomorSkLengkap(['nomor_sk' => '', 'tanggal_sk' => '2026-08-22']));
        $this->assertSame('', EvaluationDecisionModel::nomorSkLengkap(['tanggal_sk' => '2026-08-22']));
    }

    public function testNomorSkTidakBerubahWalauKeputusanDisuntingBulanLain(): void
    {
        // Ekor nomor disusun dari tanggal_sk yang dibekukan saat SK pertama
        // terbit, bukan dari waktu sekarang. Kalau memakai waktu sekarang, nomor
        // pada surat yang sudah beredar akan berubah setiap kali keputusannya
        // disunting - dan nomor surat resmi tidak boleh berubah.
        $keputusan = ['nomor_sk' => '007', 'tanggal_sk' => '2026-03-10'];

        $this->assertSame(
            '007/SMJ/RSC-HRD/III/2026',
            EvaluationDecisionModel::nomorSkLengkap($keputusan),
        );
    }

    // ---------------------------------------------------------------
    // Label status
    // ---------------------------------------------------------------

    public function testLabelStatusTerbacaManusia(): void
    {
        $this->assertSame('Lulus', EvaluationDecisionModel::statusLabel('lulus'));
        $this->assertSame('Tidak Lulus', EvaluationDecisionModel::statusLabel('tidak-lulus'));
        $this->assertSame('Warning', EvaluationDecisionModel::statusLabel('warning'));
        $this->assertSame('-', EvaluationDecisionModel::statusLabel(null));
        $this->assertSame('-', EvaluationDecisionModel::statusLabel('status-yang-tidak-dikenal'));
    }

    // ---------------------------------------------------------------
    // Aspek penilaian
    // ---------------------------------------------------------------

    public function testAspekPenilaianEmpatKategoriTujuhBelasButir(): void
    {
        $aspek = EvaluationDetailModel::getStandardAspects();

        $this->assertCount(4, $aspek, 'Form penilaian punya empat kategori');

        $jumlah = array_sum(array_map('count', $aspek));
        $this->assertSame(
            17,
            $jumlah,
            'Jumlah butir penilaian berubah. Formulir resmi memuat 17 butir; '
                . 'kalau memang berubah, formulir cetak dan dokumentasi harus ikut disesuaikan',
        );
    }

    public function testSetiapKategoriPunyaButirDanTidakAdaYangKosong(): void
    {
        foreach (EvaluationDetailModel::getStandardAspects() as $kategori => $butir) {
            $this->assertNotEmpty($kategori);
            $this->assertNotEmpty($butir, "Kategori {$kategori} tidak boleh kosong");

            foreach ($butir as $b) {
                $this->assertNotEmpty(trim($b));
            }
        }
    }

    public function testTidakAdaButirPenilaianYangKembar(): void
    {
        // Butir kembar membuat satu hal dinilai dua kali sehingga bobotnya
        // menjadi ganda pada rata-rata akhir.
        $semua = [];
        foreach (EvaluationDetailModel::getStandardAspects() as $butir) {
            $semua = array_merge($semua, $butir);
        }

        $this->assertSame(count($semua), count(array_unique($semua)));
    }
}
