<?php

namespace App\Libraries;

/**
 * Aturan siklus penilaian masa probation — satu-satunya tempat angkanya ditulis.
 *
 * Sebelumnya aturan ini hidup sebagai angka lepas di dalam
 * DashboardController::myTeam(), sehingga halaman "Tim Saya" dan dashboard bisa
 * berbeda kesimpulan tentang orang yang sama. Semua yang perlu tahu kapan
 * seseorang harus dinilai sekarang membaca dari sini.
 *
 * Aturannya, sesuai formulir kertasnya:
 *   - Masa probation 3 bulan, dinilai paling banyak 2 kali.
 *   - Penilaian ke-1 baru boleh dilakukan 45 hari setelah mulai probation,
 *     penilaian ke-2 45 hari setelah penilaian ke-1.
 *   - Setelah 2 kali dinilai, giliran HRD yang memutuskan — Team Leader tidak
 *     punya pekerjaan lagi atas orang itu.
 *   - Standar kelulusan nilai rata-rata >= 6 (lihat panduan norma pada form).
 */
final class SiklusProbation
{
    /** Jarak minimum antar penilaian, dan antara mulai probation dan penilaian ke-1. */
    public const INTERVAL_HARI = 45;

    /** Banyaknya penilaian selama satu masa probation. */
    public const MAKS_PENILAIAN = 2;

    /** Panjang masa probation bila akhir_probation tidak diisi. */
    public const DURASI_HARI = 90;

    /** Nilai rata-rata minimum untuk lulus, sesuai panduan norma pada form. */
    public const NILAI_LULUS = 6.0;

    // --- Status penilaian berikutnya -------------------------------------
    public const PERLU_DINILAI   = 'perlu_dinilai';    // sudah waktunya dinilai
    public const BELUM_WAKTUNYA  = 'belum_waktunya';   // belum pernah dinilai, belum 45 hari
    public const SUDAH_DINILAI   = 'sudah_dinilai';    // baru 1x, menunggu jatuh tempo ke-2
    public const SELESAI_DINILAI = 'selesai_dinilai';  // 2x, menunggu keputusan HRD

    // --- Tahap perjalanan seorang Team Member ----------------------------
    public const TAHAP_BELUM     = 'belum_dinilai';
    public const TAHAP_SATU_KALI = 'dinilai_1';
    public const TAHAP_MENUNGGU  = 'menunggu_keputusan';
    public const TAHAP_SELESAI   = 'selesai';

    /**
     * Kapan seorang Team Member harus dinilai berikutnya.
     *
     * Hanya untuk yang masa probationnya masih berjalan; karyawan yang statusnya
     * sudah final tidak lagi masuk siklus dan pemanggilnya yang menyaring itu.
     *
     * @param  ?string $mulaiProbation  tanggal mulai probation (Y-m-d)
     * @param  int     $jumlahPenilaian banyaknya penilaian yang sudah ada
     * @param  ?string $tanggalTerakhir tanggal penilaian terakhir, bila ada
     * @return array{status:string, sisa_hari:?int, hari_sejak_eval:?int}
     *         sisa_hari = berapa hari lagi sampai boleh dinilai (0 bila sudah bisa)
     */
    public static function jadwal(
        ?string $mulaiProbation,
        int $jumlahPenilaian,
        ?string $tanggalTerakhir,
        ?string $hariIni = null
    ): array {
        if ($jumlahPenilaian >= self::MAKS_PENILAIAN) {
            return ['status' => self::SELESAI_DINILAI, 'sisa_hari' => null, 'hari_sejak_eval' => null];
        }

        // Belum pernah dinilai — hitung dari tanggal mulai probation. Tanggal
        // mulai yang masih di masa depan menghasilkan selisih negatif, dan itu
        // memang berarti "belum waktunya", bukan "sudah lewat 45 hari".
        if ($jumlahPenilaian === 0) {
            $hariBerjalan = $mulaiProbation === null
                ? self::INTERVAL_HARI               // tanpa tanggal mulai, jangan tahan penilaiannya
                : self::selisihHari($mulaiProbation, $hariIni);

            return self::hasil($hariBerjalan, self::BELUM_WAKTUNYA, null);
        }

        // Sudah dinilai sekali — hitung dari penilaian terakhir.
        $hariSejak = self::selisihHari($tanggalTerakhir, $hariIni);

        return self::hasil($hariSejak, self::SUDAH_DINILAI, $hariSejak);
    }

    /**
     * Tahap perjalanan seorang Team Member, untuk corong pada dashboard HRD.
     */
    public static function tahap(string $statusKaryawan, int $jumlahPenilaian, bool $adaKeputusan): string
    {
        if ($adaKeputusan || $statusKaryawan !== 'pending') {
            return self::TAHAP_SELESAI;
        }

        if ($jumlahPenilaian >= self::MAKS_PENILAIAN) {
            return self::TAHAP_MENUNGGU;
        }

        return $jumlahPenilaian === 0 ? self::TAHAP_BELUM : self::TAHAP_SATU_KALI;
    }

    /**
     * Seberapa jauh masa probation seseorang sudah berjalan.
     *
     * @return array{total:int, terpakai:int, sisa:int, persen:int, lewat:bool, akhir:?string}
     */
    public static function progres(?string $mulai, ?string $akhir, ?string $hariIni = null): array
    {
        if (empty($mulai)) {
            return ['total' => 0, 'terpakai' => 0, 'sisa' => 0, 'persen' => 0, 'lewat' => false, 'akhir' => null];
        }

        // akhir_probation boleh kosong di basis data — jatuh ke 3 bulan sejak mulai.
        if (empty($akhir)) {
            $akhir = date('Y-m-d', strtotime($mulai . ' +' . self::DURASI_HARI . ' days'));
        }

        $total    = max(1, self::selisihHari($mulai, $akhir));
        $terpakai = self::selisihHari($mulai, $hariIni);
        $lewat    = $terpakai > $total;
        $terpakai = max(0, min($terpakai, $total));

        return [
            'total'    => $total,
            'terpakai' => $terpakai,
            'sisa'     => $total - $terpakai,
            'persen'   => (int) round($terpakai / $total * 100),
            'lewat'    => $lewat,
            'akhir'    => $akhir,
        ];
    }

    /**
     * Golongan mutu sebuah nilai, mengikuti panduan norma pada form penilaian.
     *
     * @return array{kunci:string, label:string, warna:string, teks:string}
     *         warna/teks berisi kelas Tailwind supaya semua tampilan memakai
     *         warna yang sama untuk nilai yang sama.
     */
    public static function bandNilai(?float $nilai): array
    {
        return match (true) {
            $nilai === null => ['kunci' => 'kosong', 'label' => 'Belum dinilai', 'warna' => 'bg-gray-300',   'teks' => 'text-gray-400'],
            $nilai >= 9     => ['kunci' => 'sangat_baik', 'label' => 'Sangat Baik', 'warna' => 'bg-green-500',  'teks' => 'text-green-600'],
            $nilai >= 7     => ['kunci' => 'baik',        'label' => 'Baik',        'warna' => 'bg-blue-500',   'teks' => 'text-blue-600'],
            $nilai >= self::NILAI_LULUS => ['kunci' => 'cukup', 'label' => 'Cukup', 'warna' => 'bg-amber-500',  'teks' => 'text-amber-600'],
            default         => ['kunci' => 'kurang',      'label' => 'Kurang',      'warna' => 'bg-red-500',    'teks' => 'text-red-600'],
        };
    }

    /** Urutan golongan nilai dari terbaik ke terburuk, untuk grafik distribusi. */
    public static function urutanBand(): array
    {
        return ['sangat_baik', 'baik', 'cukup', 'kurang'];
    }

    /**
     * Selisih hari bertanda: positif bila $sampai berada setelah $dari.
     *
     * DateTime::diff()->days selalu positif, sehingga tanggal di masa depan
     * ikut terbaca sebagai "sudah lewat sekian hari". Pembandingan lewat
     * timestamp tengah hari menghindari itu sekaligus tidak terganggu jam.
     */
    public static function selisihHari(?string $dari, ?string $sampai = null): int
    {
        if (empty($dari)) {
            return 0;
        }

        $a = strtotime(date('Y-m-d', strtotime($dari)) . ' 12:00:00');
        $b = strtotime(date('Y-m-d', strtotime($sampai ?: 'today')) . ' 12:00:00');

        if ($a === false || $b === false) {
            return 0;
        }

        return (int) round(($b - $a) / 86400);
    }

    /**
     * Bagian akhir jadwal(): sudah jatuh tempo atau belum.
     */
    private static function hasil(int $hariBerjalan, string $statusBelum, ?int $hariSejakEval): array
    {
        $perlu = $hariBerjalan >= self::INTERVAL_HARI;

        return [
            'status'          => $perlu ? self::PERLU_DINILAI : $statusBelum,
            'sisa_hari'       => $perlu ? 0 : self::INTERVAL_HARI - $hariBerjalan,
            'hari_sejak_eval' => $hariSejakEval,
        ];
    }
}
