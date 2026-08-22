<?php

use App\Libraries\SuratKeputusan;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Isi Surat Keputusan pengangkatan karyawan tetap.
 *
 * SK dicetak lewat dua mesin yang berbeda:
 *
 *   Windows : Word COM, langsung dari "template SK team member.docx"
 *   Linux   : DomPDF, dari HTML di SuratKeputusan::html()
 *
 * Server produksi klien berbasis Linux, jadi yang benar-benar dipakai di sana
 * adalah jalur HTML. Ini dokumen resmi yang memuat rujukan hukum dan nama
 * penandatangan - kalau ada klausul yang hilang saat seseorang merapikan HTML,
 * suratnya menjadi cacat tanpa ada yang menyadari sampai dipermasalahkan.
 *
 * Daftar frasa di bawah diambil dari isi template Word, supaya kedua jalur
 * cetak tetap memuat hal yang sama.
 *
 * @internal
 */
final class SuratKeputusanTest extends CIUnitTestCase
{
    private function contohData(): array
    {
        return SuratKeputusan::data(
            [
                'nama' => 'Budi Santoso', 'nik' => 'EMP001',
                'departemen' => 'Produksi', 'posisi' => 'Operator Produksi',
                'tanggal_masuk' => '2025-11-01', 'mulai_probation' => '2025-11-01',
            ],
            [
                'nomor_sk' => '001', 'tanggal_sk' => '2026-08-22',
                'tanggal_diangkat' => '2026-02-01', 'status_akhir' => 'lulus',
                'created_at' => '2026-08-22 10:00:00',
            ],
        );
    }

    /** Frasa wajib, disalin dari template Word. */
    public static function frasaWajib(): array
    {
        return [
            'judul surat'      => ['SURAT KEPUTUSAN'],
            'penetap'          => ['KEPUTUSAN MANAJEMEN'],
            'nama perusahaan'  => ['SUMBER MASANDA JAYA'],
            'perihal'          => ['PENGANGKATAN KARYAWAN TETAP'],
            'bagian menimbang' => ['Menimbang'],
            'rujukan PKB'      => ['Perjanjian Kerja Bersama'],
            'pasal PKB'        => ['Pasal 13 ayat 3'],
            'bagian mengingat' => ['Mengingat'],
            'rujukan UU'       => ['Undang-Undang Nomor 13 Tahun 2003'],
            'memperhatikan'    => ['Memperhatikan'],
            'diktum'           => ['MEMUTUSKAN'],
            'penutup'          => ['Demikian surat keputusan ini dibuat'],
            'tempat terbit'    => ['Dibuat di'],
            'divisi'           => ['Divisi RSC'],
            'penandatangan'    => ['Munawar Arsad'],
            'jabatan penanda'  => ['Senior Manager'],
            'alamat kantor'    => ['Jalan Raya Bangsri'],
            'kode pos'         => ['52253'],
        ];
    }

    /**
     * @dataProvider frasaWajib
     */
    public function testHtmlMemuatSeluruhElemenTemplateResmi(string $frasa): void
    {
        $html = SuratKeputusan::html($this->contohData());

        $this->assertStringContainsStringIgnoringCase(
            $frasa,
            $html,
            "Elemen \"{$frasa}\" ada di template Word tetapi hilang dari jalur cetak HTML "
                . '- SK yang terbit di server Linux akan berbeda isinya',
        );
    }

    public function testHtmlMemuatIdentitasKaryawan(): void
    {
        $html = SuratKeputusan::html($this->contohData());

        foreach (['Budi Santoso', 'EMP001', 'Produksi', 'Operator Produksi'] as $nilai) {
            $this->assertStringContainsString($nilai, $html);
        }
    }

    public function testNomorSuratDisusunLengkap(): void
    {
        $d = $this->contohData();

        // Bukan sekadar "001" - nomor SK resmi memuat kode unit dan tahun.
        $this->assertStringContainsString('001', $d['nomor']);
        $this->assertStringContainsString('SMJ', $d['nomor']);
        $this->assertStringContainsString('2026', $d['nomor']);
    }

    // ---------------------------------------------------------------
    // Tanggal
    // ---------------------------------------------------------------

    public function testTanggalDitulisPanjangDalamBahasaIndonesia(): void
    {
        $this->assertSame('15 Januari 2026', SuratKeputusan::tanggalPanjang('2026-01-15'));
        $this->assertSame('1 Agustus 2026', SuratKeputusan::tanggalPanjang('2026-08-01'));
        $this->assertSame('31 Desember 2025', SuratKeputusan::tanggalPanjang('2025-12-31'));
    }

    public function testTanggalKosongTidakMenghasilkanTulisanAneh(): void
    {
        // Lebih baik kosong daripada "1 Januari 1970" di dokumen resmi.
        $this->assertSame('', SuratKeputusan::tanggalPanjang(null));
        $this->assertSame('', SuratKeputusan::tanggalPanjang(''));
        $this->assertSame('', SuratKeputusan::tanggalPanjang('bukan-tanggal'));
    }

    public function testTanggalSpkJatuhKeMulaiProbationSaatTanggalMasukKosong(): void
    {
        // tanggal_masuk boleh NULL di basis data, mulai_probation tidak.
        $d = SuratKeputusan::data(
            [
                'nama' => 'Tanpa Tanggal Masuk', 'nik' => 'EMP999',
                'departemen' => 'Produksi', 'posisi' => 'Operator',
                'tanggal_masuk' => null, 'mulai_probation' => '2026-03-10',
            ],
            ['nomor_sk' => '002', 'tanggal_sk' => '2026-08-22', 'tanggal_diangkat' => '2026-06-10'],
        );

        $this->assertSame('10 Maret 2026', $d['tanggal_spk']);
    }

    public function testNamaBerkasAmanDipakaiSebagaiNamaFile(): void
    {
        $nama = SuratKeputusan::namaBerkas(['nama' => 'Budi Santoso', 'nik' => 'EMP001']);

        $this->assertStringEndsWith('.pdf', $nama);
        $this->assertDoesNotMatchRegularExpression(
            '#[/\\\\:*?"<>|]#',
            $nama,
            'Nama berkas tidak boleh memuat karakter yang dilarang sistem berkas',
        );
    }
}
