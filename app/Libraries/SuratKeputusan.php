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
     * Ukuran dan indennya mengikuti .docx-nya (A4, Times New Roman 12pt, blok
     * tanda tangan mulai di 4,25 inci dari tepi kiri teks).
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
@page { size: A4 portrait; margin: 0.6cm 2.2cm 0.5cm 1.7cm; }
body  { font-family: "Times New Roman", Times, serif; font-size: 12pt; color: #000; line-height: 1.25; }
p     { margin: 0; }

.logo img   { width: 148px; }
.judul-sk   { text-align: center; font-size: 17pt; font-weight: bold; text-decoration: underline; margin-top: 2px; }
.nomor      { text-align: center; font-size: 11pt; margin-bottom: 14px; }
.tentang    { text-align: center; font-weight: bold; margin-bottom: 12px; }

table.dasar { width: 100%; border-collapse: collapse; }
table.dasar td { vertical-align: top; padding: 0 0 2px; }
.lbl  { width: 116px; }
.sep  { width: 26px; }
.no   { width: 22px; }
.isi  { text-align: justify; }

.memutuskan { text-align: center; font-weight: bold; margin: 14px 0; }

.data       { margin: 6px 0 0 34px; font-weight: bold; }
.data td    { padding: 1px 0; vertical-align: top; }
.data .k    { width: 96px; }
.data .t    { width: 18px; }
.penutup    { margin-top: 10px; text-align: justify; }

.ttd        { margin-top: 26px; width: 100%; }
.ttd td     { vertical-align: top; }
.ttd .kolom { width: 47%; }
.ttd .garis { border-top: 1px solid #000; height: 1px; font-size: 0; }
.ttd .k     { width: 74px; }
.ttd .t     { width: 14px; }
.qr         { text-align: right; padding-top: 10px; }
.qr img     { width: 74px; height: 74px; }
.nm         { font-weight: bold; }
.nm u       { text-decoration: underline; }

.kaki       { margin-top: 22px; }
.kaki .nama { font-weight: bold; font-size: 13pt; }
.kaki .alamat { font-size: 11.5pt; }
</style></head><body>

<div class="logo"><img src="<?= KopSurat::logoDataUri() ?>" alt="SUMBER"></div>

<p class="judul-sk">SURAT KEPUTUSAN</p>
<p class="nomor"><?= $h($d['nomor']) ?></p>

<p class="tentang">KEPUTUSAN MANAJEMEN PT SUMBER MASANDA JAYA TENTANG<br>PENGANGKATAN KARYAWAN TETAP</p>

<table class="dasar">
  <?= $blok('Menimbang', array_map($h, $menimbang)) ?>
  <?= $blok('Mengingat', array_map($h, $mengingat)) ?>
  <?= $blok('Memperhatikan', $memperhatikan) ?>
</table>

<p class="memutuskan">MEMUTUSKAN</p>

<table class="dasar">
  <tr>
    <td class="lbl">Menetapkan</td>
    <td class="sep">:</td>
    <td class="isi" colspan="2">
      Terhitung sejak tanggal <strong><?= $h($d['tanggal_angkat']) ?></strong>, mengangkat karyawan
      tersebut dibawah ini
      <table class="data">
        <tr><td class="k">Nama</td><td class="t">:</td><td><?= $h($d['nama']) ?></td></tr>
        <tr><td class="k">NIK</td><td class="t"></td><td><?= $h($d['nik']) ?></td></tr>
        <tr><td class="k">Departemen</td><td class="t">:</td><td><?= $h($d['departemen']) ?></td></tr>
        <tr><td class="k">Jabatan</td><td class="t">:</td><td><?= $h($d['jabatan']) ?></td></tr>
      </table>
    </td>
  </tr>
</table>

<p class="penutup">
  Demikian surat keputusan ini dibuat, apabila dikemudian hari terdapat kekeliruan dalam surat
  keputusan ini maka akan dilakukan perbaikan sebagaimana mestinya.
</p>

<!-- AREA TANDA TANGAN — dipertahankan sama untuk setiap SK, seperti templatenya -->
<table class="ttd">
  <tr>
    <td class="kolom"></td>
    <td>
      <table style="width:100%">
        <tr><td class="k">Dibuat di</td><td class="t">:</td><td><?= $h($d['kota']) ?></td></tr>
        <tr><td class="k">Tanggal</td><td class="t">:</td><td><?= $h($d['tanggal_surat']) ?></td></tr>
      </table>
      <p class="nm" style="margin-top:6px">Divisi RSC</p>
    </td>
    <td class="qr" style="width:96px">
      <div class="garis" style="border-top:1px solid #000;margin-bottom:34px"></div>
      <img src="<?= SkAssets::qrDataUri() ?>" alt="">
    </td>
  </tr>
  <tr>
    <td></td>
    <td colspan="2">
      <p class="nm" style="margin-top:14px">PT. Sumber Masanda Jaya</p>
      <p class="nm"><u>Munawar Arsad</u> Senior Manager</p>
    </td>
  </tr>
</table>

<div class="kaki">
  <p class="nama">PT. SUMBER MASANDA JAYA</p>
  <p class="alamat">
    Jalan Raya Bangsri RT.001 RW.001 Desa Bangsri, Kecamatan Bulakamba, Kabupaten Brebes,
    Jawa Tengah - 52253. Telp. 0283-6180088, 0283-6180400
  </p>
</div>

</body></html>
<?php
        return ob_get_clean();
    }
}
