<?php

namespace App\Libraries;

use App\Models\EvaluationDecisionModel;

/**
 * Surat Keputusan pengangkatan karyawan tetap — isi surat, terlepas dari cara
 * mencetaknya.
 *
 * Sumbernya satu berkas Word, 'template SK team member.docx', yang isinya sudah
 * terisi contoh milik "Muhammad Iqbal". Kelas ini yang tahu bagian mana dari
 * contoh itu milik karyawan (dan harus diganti) dan bagian mana kalimat baku
 * perusahaan (dan harus dibiarkan) — termasuk area tanda tangan, yang tetap
 * seperti aslinya: Divisi RSC, PT. Sumber Masanda Jaya, Munawar Arsad Senior
 * Manager, beserta QR-nya.
 *
 * Dua renderer memakainya, persis seperti pada form penilaian:
 *   - Word COM  — membuka .docx-nya lalu Find & Replace, jadi hasilnya asli
 *                 sampai ke jenis huruf dan tata letaknya. Hanya di Windows.
 *   - DomPDF    — menyusun ulang suratnya sebagai HTML. Satu-satunya pilihan di
 *                 host Linux, tempat exec() mati dan Word tidak ada.
 * Keduanya membaca angka yang sama dari data(), jadi tidak bisa berbeda isi.
 */
final class SuratKeputusan
{
    /** Tempat surat dibuat — tercetak sebagai "Dibuat di : Brebes". */
    private const KOTA = 'Brebes';

    /**
     * Data surat untuk satu karyawan.
     *
     * $keputusan adalah baris penilaian_keputusan yang sudah lolos
     * EvaluationDecisionModel::skSiap(), jadi nomor SK dan tanggal
     * pengangkatannya dijamin ada.
     */
    public static function data(array $employee, array $keputusan): array
    {
        // Tanggal surat perjanjian kerja: tanggal_masuk boleh kosong di basis
        // data, sedangkan mulai_probation tidak — jadi itu cadangannya.
        $tglSpk = $employee['tanggal_masuk'] ?? null;
        if (empty($tglSpk)) {
            $tglSpk = $employee['mulai_probation'] ?? null;
        }

        // tanggal_sk dibekukan saat SK pertama terbit; created_at hanya dipakai
        // untuk baris keputusan lama yang dibuat sebelum kolom itu ada.
        $tglSurat = $keputusan['tanggal_sk'] ?? null;
        if (empty($tglSurat)) {
            $tglSurat = $keputusan['created_at'] ?? null;
        }

        return [
            'nomor'          => EvaluationDecisionModel::nomorSkLengkap($keputusan),
            'nama'           => trim((string) ($employee['nama'] ?? '')),
            'nik'            => trim((string) ($employee['nik'] ?? '')),
            'departemen'     => trim((string) ($employee['departemen'] ?? '')),
            'jabatan'        => trim((string) ($employee['posisi'] ?? '')),
            'tanggal_spk'    => self::tanggalPanjang($tglSpk),
            'tanggal_angkat' => self::tanggalPanjang($keputusan['tanggal_diangkat'] ?? null),
            'tanggal_surat'  => self::tanggalPanjang($tglSurat),
            'kota'           => self::KOTA,
        ];
    }

    /** '2026-01-15' → '15 Januari 2026'. Kosong bila tanggalnya tidak terbaca. */
    public static function tanggalPanjang(?string $date): string
    {
        if (empty($date)) {
            return '';
        }

        $ts = strtotime($date);
        if ($ts === false) {
            return '';
        }

        $bulan = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];

        return (int) date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    }

    /**
     * Nama berkas PDF yang diunduh pemakai.
     */
    public static function namaBerkas(array $employee): string
    {
        $nik = trim((string) ($employee['nik'] ?? '')) ?: (string) ($employee['id'] ?? '');

        return 'SK_' . $nik . '.pdf';
    }

    // ---------------------------------------------------------------
    // RENDERER WORD COM
    // ---------------------------------------------------------------

    /**
     * Isi contoh pada template → penanda sementara → isi karyawan.
     *
     * Penggantiannya dua tahap supaya isi baru tidak bisa tertimpa penggantian
     * berikutnya. Tanpa itu, karyawan yang tanggal perjanjian kerjanya kebetulan
     * "15 Januari 2026" — tanggal pengangkatan pada contoh — akan ikut tertimpa
     * saat baris tanggal pengangkatan diganti, dan suratnya salah tanggal.
     *
     * Penanda dibuat dari '@@' karena tidak pernah muncul di template dan bukan
     * karakter istimewa bagi Find & Replace Word (wildcard-nya dimatikan). '^'
     * dibuang dari isian karena Word membacanya sebagai awalan kode ganti
     * (^p, ^t, ^l) — bukan sebagai huruf.
     *
     * Kuncinya adalah teks apa adanya pada template — jangan diubah kecuali
     * templatenya memang diganti.
     *
     * @return list<array{0:string,1:string}> pasangan [cari, ganti] terurut
     */
    public static function wordReplacements(array $d): array
    {
        $bersih = static fn($v) => str_replace('^', '', (string) $v);

        $peta = [
            '33371/SMJ/RSC-HRD/I/2026' => ['SK_NOMOR',  $bersih($d['nomor'])],
            '15 Oktober 2025'          => ['SK_TGLSPK', $bersih($d['tanggal_spk'])],
            '15 Januari 2026'          => ['SK_TGLANG', $bersih($d['tanggal_angkat'])],
            '14 Januari 2026'          => ['SK_TGLSRT', $bersih($d['tanggal_surat'])],
            'Muhammad Iqbal'           => ['SK_NAMA',   $bersih($d['nama'])],
            '51199'                    => ['SK_NIK',    $bersih($d['nik'])],
            // Departemen dan Jabatan berbagi satu paragraf pada template; "IT"
            // saja terlalu pendek untuk dicari sendirian, jadi labelnya ikut
            // jadi jangkar. '^l' menutup baris Departemen dengan line break
            // Word supaya Jabatan selalu mulai di baris sendiri — pada template
            // keduanya memang tercetak dua baris, dan tanpa ini nama departemen
            // yang lebih panjang dari "IT" membuat labelnya patah di tengah.
            // Spasi di ujung kunci ikut dicari: itu spasi pemisah menuju
            // "Jabatan", dan kalau ditinggal, baris Jabatan mulai dengan spasi
            // menggantung sehingga tidak lurus dengan baris Departemen.
            'Departemen : IT '         => ['SK_DEPT',   'Departemen : ' . $bersih($d['departemen']) . '^l'],
            'Staf A'                   => ['SK_JABATAN', $bersih($d['jabatan'])],
        ];

        $tahap1 = [];
        $tahap2 = [];
        foreach ($peta as $contoh => [$tanda, $nilai]) {
            $tahap1[] = [$contoh, '@@' . $tanda . '@@'];
            $tahap2[] = ['@@' . $tanda . '@@', $nilai];
        }

        return array_merge($tahap1, $tahap2);
    }

    // ---------------------------------------------------------------
    // RENDERER DOMPDF
    // ---------------------------------------------------------------

    /**
     * Surat versi HTML — tiruan template untuk DomPDF.
     *
     * Setiap angka pada CSS di bawah diambil dari 'template SK team member.docx'
     * dan dari PDF hasil ekspor Word-nya, bukan dikira-kira: satuan Word (twip,
     * 1/20 pt) tinggal dibagi 20, dan posisi tiap baris sudah dicocokkan dengan
     * koordinat teks pada PDF acuan. Jadi kalau template berubah, angka-angka
     * ini harus ikut diukur ulang — jangan disetel dengan perasaan.
     *
     * Susunannya sengaja mengalir biasa (bukan position:absolute) supaya
     * berperilaku sama seperti Word: nama atau jabatan yang lebih panjang dari
     * contohnya mendorong isi di bawahnya, bukan menimpanya.
     *
     * Area tanda tangan — garis, Divisi RSC, QR, PT. Sumber Masanda Jaya, dan
     * Munawar Arsad Senior Manager — dijiplak apa adanya dari template dan
     * tidak menerima nilai apa pun dari data karyawan.
     */
    public static function html(array $d): string
    {
        $h = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

        $menimbang = [
            'Bahwa untuk melaksanakan ketentuan Perjanjian Kerja Bersama Ke-III Periode 2025-2027 '
                . 'Pasal 13 ayat 3 tentang Masa Percobaan Pekerja Baru PT Sumber Masanda Jaya.',
            'Untuk memperoleh tenaga kerja yang tepat dan memiliki produktivitas tinggi harus '
                . 'melalui masa percobaan.',
        ];
        $mengingat = [
            'Undang-Undang Nomor 13 Tahun 2003 Tentang Ketenagakerjaan.',
            'Perjanjian Kerja Bersama Ke-III Periode 2025-2027 Pasal 13 ayat 3 tentang Masa '
                . 'Percobaan Pekerja Baru PT Sumber Masanda Jaya.',
            'Prosedur Penilaian Karyawan Masa Percobaan dan Pengangkatan Karyawan Tetap.',
        ];
        $memperhatikan = [
            'Surat Perjanjian Kerja tertanggal ' . $h($d['tanggal_spk']) . '.',
            'Penilaian Karyawan Masa Percobaan Sdr/Sdri. ' . $h($d['nama']) . ' selama masa percobaan.',
        ];

        // Satu butir = satu baris tabel. Label dan titik dua hanya ditulis pada
        // butir pertama, persis seperti tabel di template.
        $blok = static function (string $label, array $butir) use ($h): string {
            $out = '';
            foreach ($butir as $i => $isi) {
                $out .= '<tr>'
                      . '<td class="lbl">' . ($i === 0 ? $h($label) : '') . '</td>'
                      . '<td class="sep">' . ($i === 0 ? ':' : '') . '</td>'
                      . '<td class="no">' . ($i + 1) . '.</td>'
                      . '<td class="isi">' . $isi . '</td>'
                      . '</tr>';
            }

            return $out;
        };

        ob_start(); ?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
/* Halaman: pgMar template = atas 320, kanan 1275, bawah 280, kiri 992 twip.

   Setiap line-height di bawah sudah dikali 1/0,99: DomPDF memasang jarak baris
   tepat 99% dari yang ditulis (13,7pt jadi 13,563pt), jadi angka mentahnya
   selalu meleset dan kesalahannya menumpuk sampai kaki surat. Nilai bulat di
   komentar tiap blok adalah jarak baris Word yang sebenarnya dituju.

   Jangan pernah pakai selektor '*' atau 'html' di sini: DomPDF memasang gaya
   @page pada elemen html, jadi aturan yang kena elemen itu ikut menghapus
   margin halamannya dan seluruh surat bergeser ke pojok kertas. */
@page { size: A4 portrait; margin: 16pt 63.75pt 14pt 49.6pt; }
body, p, div, table, tr, td, img { margin: 0; padding: 0; border: 0; }
body  { font-family: "Times New Roman", Times, serif; font-size: 12pt; color: #000; }
table { border-collapse: collapse; }
td    { vertical-align: top; }

/* Kop — logo 1344021 x 424052 EMU, indent paragraf 43 twip. */
.kop     { margin-left: 2.15pt; }
.kop img { width: 105.7pt; height: 33.35pt; }

/* Judul. Indent kiri 642 twip dulu, baru rata tengah pada sisa lebarnya —
   itu sebabnya judulnya sedikit bergeser ke kanan dari tengah halaman.
   Spasi di depan "SURAT" ada di template dan ikut digarisbawahi. */
.judul { margin-top: 6.1pt; margin-left: 32.1pt; text-align: center;
         font-size: 18pt; font-weight: bold; line-height: 20.808pt; }
.nomor { margin-left: 22.3pt; text-align: center; font-size: 11pt; line-height: 12.677pt; }

/* "…TENTANG" dan "PENGANGKATAN KARYAWAN TETAP" dua paragraf terpisah dengan
   indent berbeda, jadi titik tengahnya pun berbeda sedikit. */
.tentang  { margin-top: 13.75pt; margin-left: 56.6pt; margin-right: 42.55pt;
            text-align: center; font-weight: bold; line-height: 14.141pt; }
.tentang2 { margin-left: 13.9pt; text-align: center; font-weight: bold; line-height: 13.687pt; }

/* Dasar hukum — indent tabel 405 twip, kolom 1785/399/412/6534 twip.
   Lebar td di CSS tidak termasuk padding, jadi tiap kolom = width + padding. */
.dasar          { margin-top: 13.21pt; margin-left: 20.25pt; }
.dasar td       { line-height: 13.838pt; padding-top: 0.26pt; }
.dasar .lbl     { width: 86.75pt; padding-left: 2.5pt; }
.dasar .sep     { width: 15.1pt;  padding-left: 4.85pt; text-align: center; }
.dasar .no      { width: 20.4pt;  padding-left: 0.2pt;  text-align: center; }
.dasar .isi     { width: 320.9pt; padding-left: 5.8pt;  text-align: justify; }

.memutuskan { margin-top: 14.4pt; margin-left: 14.35pt; text-align: center;
              font-weight: bold; line-height: 13.838pt; }

/* Baris "Menetapkan" — kolom isinya dilebarkan sampai margin kanan (template hanya
   328pt) supaya tanggal pengangkatan yang lebih panjang dari contohnya tidak
   memutus "mengangkat karyawan" lebih awal. Baris kedua paragrafnya menjorok
   1,65pt lebih dalam, sama seperti di template. */
.tetap      { margin-top: 13.5pt; margin-left: 20.25pt; }
.tetap td   { line-height: 13.838pt; }
.tetap .lbl { width: 78.5pt;  padding-left: 2.5pt; }
.tetap .sep { width: 22.7pt;  padding-right: 5.55pt; text-align: right; }
.tetap .isi { width: 345.4pt; padding-left: 7.25pt; text-indent: -1.65pt; }

/* Data karyawan — indent 2715 twip, titik dua di tab stop 4049 twip.
   NIK memang tanpa titik dua pada template; jangan ditambahkan. */
.data       { margin-top: 0.66pt; margin-left: 135.75pt; font-weight: bold; }
.data td    { line-height: 13.889pt; }
.data .sela td { padding-top: 2.9pt; }  /* jarak antar baris data: 16,65pt */
.data .k    { width: 66.6pt; }
.data .t    { width: 6.6pt; }

.penutup { margin-top: 0.3pt; margin-left: 22.4pt; line-height: 13.333pt; text-align: justify; }

/* ---------------------------------------------------------------
   AREA TANDA TANGAN — jiplakan template, tanpa nilai dari karyawan.
   Blok mulai di indent 6119 twip; titik dua di tab stop 7396 twip;
   garis 2016125 EMU; QR 704088 EMU persegi.
   --------------------------------------------------------------- */
.ttd        { margin-top: 14.03pt; margin-left: 305.95pt; }
.ttd td     { line-height: 13.838pt; }
.ttd .k     { width: 63.85pt; }
.ttd-garis  { margin-top: 1.82pt; margin-left: 308.25pt; width: 158.75pt;
              border-top: 1.25pt solid #000; height: 0; font-size: 0; }
.ttd-baris  { margin-left: 305.95pt; font-weight: bold; line-height: 14.05pt; }
.ttd-divisi { margin-top: 10.69pt; }
.ttd-qr     { margin-top: 0.93pt; margin-left: 306pt; }
.ttd-qr img { width: 55.4pt; height: 55.4pt; }
.ttd-nama   { margin-top: 0.05pt; }

/* Kaki surat — Heading2 14pt lalu alamat 12pt dengan indent kanan 910 twip. */
.kaki-nama   { margin-top: 38.02pt; margin-left: 22.4pt; font-size: 14pt;
               font-weight: bold; line-height: 16.111pt; }
.kaki-alamat { margin-top: 0.15pt; margin-left: 22.4pt; margin-right: 45.5pt; line-height: 13.838pt; }
</style></head><body>

<div class="kop"><img src="<?= KopSurat::logoDataUri() ?>" alt="SUMBER"></div>

<p class="judul"><u>&nbsp;SURAT KEPUTUSAN</u></p>
<p class="nomor"><?= $h($d['nomor']) ?></p>

<p class="tentang">KEPUTUSAN MANAJEMEN PT SUMBER MASANDA JAYA<br>TENTANG</p>
<p class="tentang2">PENGANGKATAN KARYAWAN TETAP</p>

<table class="dasar">
  <?= $blok('Menimbang', array_map($h, $menimbang)) ?>
  <?= $blok('Mengingat', array_map($h, $mengingat)) ?>
  <?= $blok('Memperhatikan', $memperhatikan) ?>
</table>

<p class="memutuskan">MEMUTUSKAN</p>

<table class="tetap">
  <tr>
    <td class="lbl">Menetapkan</td>
    <td class="sep">:</td>
    <td class="isi">Terhitung sejak tanggal <b><?= $h($d['tanggal_angkat']) ?></b>, mengangkat karyawan tersebut dibawah ini</td>
  </tr>
</table>

<table class="data">
  <tr><td class="k">Nama</td><td class="t">:</td><td><?= $h($d['nama']) ?></td></tr>
  <tr class="sela"><td class="k">NIK</td><td class="t"></td><td><?= $h($d['nik']) ?></td></tr>
  <tr class="sela"><td class="k">Departemen</td><td class="t">:</td><td><?= $h($d['departemen']) ?></td></tr>
  <tr class="sela"><td class="k">Jabatan</td><td class="t">:</td><td><?= $h($d['jabatan']) ?></td></tr>
</table>

<p class="penutup">Demikian surat keputusan ini dibuat, apabila dikemudian hari terdapat kekeliruan dalam surat keputusan ini maka akan dilakukan perbaikan sebagaimana mestinya.</p>

<!-- AREA TANDA TANGAN — dipertahankan sama untuk setiap SK, seperti templatenya -->
<table class="ttd">
  <tr><td class="k">Dibuat di</td><td>: <?= $h($d['kota']) ?></td></tr>
  <tr><td class="k">Tanggal</td><td>: <?= $h($d['tanggal_surat']) ?></td></tr>
</table>
<div class="ttd-garis"></div>
<p class="ttd-baris ttd-divisi">Divisi RSC</p>
<p class="ttd-baris">PT. Sumber Masanda Jaya</p>
<div class="ttd-qr"><img src="<?= SkAssets::qrDataUri() ?>" alt=""></div>
<p class="ttd-baris ttd-nama"><u>Munawar Arsad</u></p>
<p class="ttd-baris">Senior Manager</p>

<p class="kaki-nama">PT. SUMBER MASANDA JAYA</p>
<p class="kaki-alamat">Jalan Raya Bangsri RT.001 RW.001 Desa Bangsri, Kecamatan Bulakamba,<br>Kabupaten Brebes, Jawa Tengah - 52253. Telp. 0283-6180088, 0283-6180400</p>

</body></html>
<?php
        return ob_get_clean();
    }
}
