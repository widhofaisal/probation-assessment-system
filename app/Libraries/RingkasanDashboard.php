<?php

namespace App\Libraries;

use App\Models\EvaluationDecisionModel;

/**
 * Angka-angka yang tampil di dashboard, dihitung terpisah dari cara
 * menampilkannya.
 *
 * Dashboard sebelumnya praktis menyalin isi menu "Daftar Penilaian": tabel yang
 * sama, tombol yang sama, hanya kolomnya lebih sedikit. Kelas ini menggantinya
 * dengan ringkasan — berapa yang sedang berjalan, siapa yang menunggu tindakan,
 * dan ke mana arah datanya — sedangkan daftar rincinya tetap tinggal di menunya
 * masing-masing.
 *
 * Semua fungsi di sini murni: barisan basis data dioper dari controller, tidak
 * ada query di dalam. Itu membuat kesimpulannya bisa diuji tanpa basis data,
 * dan menjaga jumlah query dashboard tetap tiga apa pun banyaknya karyawan.
 */
final class RingkasanDashboard
{
    /** Ambang "probation segera berakhir" pada kartu tindakan HRD. */
    private const AMBANG_BERAKHIR_HARI = 14;

    /** Panjang daftar contoh pada tiap kartu tindakan. */
    private const CONTOH_MAKS = 5;

    // =================================================================
    // HRD
    // =================================================================

    /**
     * @param array $employees baris employees (+ team_leader_nama)
     * @param array $penilaian seluruh baris penilaian
     * @param array $keputusan seluruh baris penilaian_keputusan
     */
    public static function hrd(array $employees, array $penilaian, array $keputusan, ?string $hariIni = null): array
    {
        $perKaryawan = self::petaPenilaian($penilaian);

        $kepPerKaryawan = [];
        foreach ($keputusan as $k) {
            $kepPerKaryawan[(int) $k['employee_id']] = $k;
        }

        $corong = [
            SiklusProbation::TAHAP_BELUM     => 0,
            SiklusProbation::TAHAP_SATU_KALI => 0,
            SiklusProbation::TAHAP_MENUNGGU  => 0,
            SiklusProbation::TAHAP_SELESAI   => 0,
        ];
        $hasil = ['lulus' => 0, 'tidak_lulus' => 0, 'warning' => 0, 'berjalan' => 0];

        $menungguKeputusan = [];
        $segeraBerakhir    = [];
        $perluDinilai      = 0;
        $nilaiSemua        = [];
        $perDept           = [];
        $perLeader         = [];

        foreach ($employees as $e) {
            $id     = (int) $e['id'];
            $rekap  = $perKaryawan[$id] ?? null;
            $jumlah = $rekap['jumlah'] ?? 0;
            $kep    = $kepPerKaryawan[$id] ?? null;
            $status = (string) ($e['status'] ?? 'pending');

            $corong[SiklusProbation::tahap($status, $jumlah, $kep !== null)]++;

            match ($status) {
                'lulus'       => $hasil['lulus']++,
                'tidak-lulus' => $hasil['tidak_lulus']++,
                'warning'     => $hasil['warning']++,
                default       => $hasil['berjalan']++,
            };

            // --- Ringkasan per departemen dan per Team Leader ---------
            $dept = trim((string) ($e['departemen'] ?? '')) ?: 'Tanpa Departemen';
            $perDept[$dept] ??= ['nama' => $dept, 'jumlah' => 0, 'lulus' => 0, 'nilai' => []];
            $perDept[$dept]['jumlah']++;
            if ($status === 'lulus') {
                $perDept[$dept]['lulus']++;
            }

            $leader = trim((string) ($e['team_leader_nama'] ?? '')) ?: 'Belum ada Team Leader';
            $perLeader[$leader] ??= ['nama' => $leader, 'anggota' => 0, 'perlu_dinilai' => 0, 'lengkap' => 0, 'nilai' => []];
            $perLeader[$leader]['anggota']++;
            if ($jumlah >= SiklusProbation::MAKS_PENILAIAN) {
                $perLeader[$leader]['lengkap']++;
            }

            foreach ($rekap['nilai'] ?? [] as $n) {
                $nilaiSemua[]              = $n;
                $perDept[$dept]['nilai'][]   = $n;
                $perLeader[$leader]['nilai'][] = $n;
            }

            if ($status !== 'pending') {
                continue;
            }

            // --- Yang masih berjalan: apa yang menunggu tindakan? -----
            $jadwal = SiklusProbation::jadwal(
                $e['mulai_probation'] ?? null,
                $jumlah,
                $rekap['terakhir'] ?? null,
                $hariIni
            );
            if ($jadwal['status'] === SiklusProbation::PERLU_DINILAI) {
                $perluDinilai++;
                $perLeader[$leader]['perlu_dinilai']++;
            }

            if ($jumlah >= SiklusProbation::MAKS_PENILAIAN && $kep === null) {
                $menungguKeputusan[] = [
                    'id'     => $id,
                    'nama'   => $e['nama'] ?? '',
                    'nik'    => $e['nik'] ?? '',
                    'sejak'  => SiklusProbation::selisihHari($rekap['terakhir'] ?? null, $hariIni),
                ];
            }

            $sisa = self::sisaProbation($e, $hariIni);
            if ($sisa !== null && $sisa <= self::AMBANG_BERAKHIR_HARI) {
                $segeraBerakhir[] = [
                    'id'     => $id,
                    'nama'   => $e['nama'] ?? '',
                    'nik'    => $e['nik'] ?? '',
                    'sisa'   => $sisa,
                    'dinilai' => $jumlah,
                ];
            }
        }

        // Keputusan lulus yang SK-nya belum bisa terbit — nomor SK atau tanggal
        // pengangkatannya masih kosong, jadi Team Member belum menerima apa pun.
        $namaKaryawan  = array_column($employees, 'nama', 'id');
        $nikKaryawan   = array_column($employees, 'nik', 'id');
        $skBelumTerbit = [];
        foreach ($keputusan as $k) {
            if (($k['status_akhir'] ?? '') !== 'lulus' || EvaluationDecisionModel::skSiap($k)) {
                continue;
            }
            $id = (int) $k['employee_id'];
            if (!isset($namaKaryawan[$id])) {
                continue;                       // karyawannya sudah dihapus
            }
            $skBelumTerbit[] = [
                'id'   => $id,
                'nama' => $namaKaryawan[$id],
                'nik'  => $nikKaryawan[$id] ?? '',
                'sebab' => trim((string) ($k['nomor_sk'] ?? '')) === '' ? 'Nomor SK belum diisi' : 'Tanggal pengangkatan belum diisi',
            ];
        }

        usort($menungguKeputusan, static fn($a, $b) => $b['sejak'] <=> $a['sejak']);
        usort($segeraBerakhir, static fn($a, $b) => $a['sisa'] <=> $b['sisa']);

        $belumDibuka = count(array_filter($penilaian, static fn($p) => empty($p['dilihat_at'])));
        $diputuskan  = $hasil['lulus'] + $hasil['tidak_lulus'] + $hasil['warning'];

        return [
            'total'    => count($employees),
            'berjalan' => $hasil['berjalan'],
            'tindakan' => [
                'menunggu_keputusan' => ['jumlah' => count($menungguKeputusan), 'daftar' => array_slice($menungguKeputusan, 0, self::CONTOH_MAKS)],
                'sk_belum_terbit'    => ['jumlah' => count($skBelumTerbit),     'daftar' => array_slice($skBelumTerbit, 0, self::CONTOH_MAKS)],
                'segera_berakhir'    => ['jumlah' => count($segeraBerakhir),    'daftar' => array_slice($segeraBerakhir, 0, self::CONTOH_MAKS)],
                'belum_dibuka'       => ['jumlah' => $belumDibuka],
                'perlu_dinilai'      => ['jumlah' => $perluDinilai],
            ],
            'corong' => self::corong($corong, count($employees)),
            'hasil'  => $hasil + [
                'diputuskan'        => $diputuskan,
                'tingkat_kelulusan' => $diputuskan > 0 ? (int) round($hasil['lulus'] / $diputuskan * 100) : null,
            ],
            'nilai'      => self::distribusiNilai($nilaiSemua),
            'tren'       => self::tren($penilaian, 6, $hariIni),
            'departemen' => self::rapikanKelompok($perDept, 'jumlah'),
            'leader'     => self::rapikanKelompok($perLeader, 'anggota'),
        ];
    }

    // =================================================================
    // TEAM LEADER
    // =================================================================

    /**
     * @param array $employees anggota tim leader ini
     * @param array $penilaian penilaian yang dibuat leader ini
     */
    public static function teamLeader(array $employees, array $penilaian, ?string $hariIni = null): array
    {
        $perKaryawan = self::petaPenilaian($penilaian);

        $jadwalTim  = [];
        $anggota    = [];
        $nilaiSemua = [];
        $hasil      = ['lulus' => 0, 'tidak_lulus' => 0, 'warning' => 0, 'berjalan' => 0];
        $perlu      = 0;
        $menunggu   = 0;

        foreach ($employees as $e) {
            $id     = (int) $e['id'];
            $rekap  = $perKaryawan[$id] ?? null;
            $jumlah = $rekap['jumlah'] ?? 0;
            $status = (string) ($e['status'] ?? 'pending');

            match ($status) {
                'lulus'       => $hasil['lulus']++,
                'tidak-lulus' => $hasil['tidak_lulus']++,
                'warning'     => $hasil['warning']++,
                default       => $hasil['berjalan']++,
            };

            foreach ($rekap['nilai'] ?? [] as $n) {
                $nilaiSemua[] = $n;
            }

            $jadwal = $status === 'pending'
                ? SiklusProbation::jadwal($e['mulai_probation'] ?? null, $jumlah, $rekap['terakhir'] ?? null, $hariIni)
                : ['status' => 'final', 'sisa_hari' => null, 'hari_sejak_eval' => null];

            if ($jadwal['status'] === SiklusProbation::PERLU_DINILAI) {
                $perlu++;
            }
            if ($jadwal['status'] === SiklusProbation::SELESAI_DINILAI) {
                $menunggu++;
            }

            $progres = SiklusProbation::progres($e['mulai_probation'] ?? null, $e['akhir_probation'] ?? null, $hariIni);

            $baris = [
                'id'        => $id,
                'nik'       => $e['nik'] ?? '',
                'nama'      => $e['nama'] ?? '',
                'posisi'    => $e['posisi'] ?? '',
                'status'    => $status,
                'jadwal'    => $jadwal['status'],
                'sisa_hari' => $jadwal['sisa_hari'],
                'dinilai'   => $jumlah,
                'nilai1'    => $rekap['ke1'] ?? null,
                'nilai2'    => $rekap['ke2'] ?? null,
                'progres'   => $progres,
            ];

            $anggota[] = $baris;

            if ($jadwal['status'] === SiklusProbation::PERLU_DINILAI
                || $jadwal['status'] === SiklusProbation::BELUM_WAKTUNYA
                || $jadwal['status'] === SiklusProbation::SUDAH_DINILAI) {
                $jadwalTim[] = $baris;
            }
        }

        // Yang sudah jatuh tempo di atas, sisanya menurut hari tersisa.
        usort($jadwalTim, static function ($a, $b) {
            $ua = $a['jadwal'] === SiklusProbation::PERLU_DINILAI ? 0 : 1;
            $ub = $b['jadwal'] === SiklusProbation::PERLU_DINILAI ? 0 : 1;

            return $ua === $ub ? (($a['sisa_hari'] ?? 999) <=> ($b['sisa_hari'] ?? 999)) : $ua <=> $ub;
        });

        // Yang masa probationnya paling dekat berakhir tampil lebih dulu.
        usort($anggota, static fn($a, $b) => $b['progres']['persen'] <=> $a['progres']['persen']);

        return [
            'total'              => count($employees),
            'perlu_dinilai'      => $perlu,
            'menunggu_keputusan' => $menunggu,
            'belum_dibuka'       => count(array_filter($penilaian, static fn($p) => empty($p['dilihat_at']))),
            'total_penilaian'    => count($penilaian),
            'jadwal'             => $jadwalTim,
            'anggota'            => $anggota,
            'hasil'              => $hasil,
            'nilai'              => self::distribusiNilai($nilaiSemua),
        ];
    }

    // =================================================================
    // TEAM MEMBER
    // =================================================================

    /**
     * @param array  $employee    baris employees milik pemakai
     * @param array  $evaluations penilaian atas dirinya, terbaru dulu
     * @param ?array $keputusan   keputusan lulus bila SK-nya sudah siap
     */
    public static function member(array $employee, array $evaluations, ?array $keputusan, ?string $hariIni = null): array
    {
        $progres = SiklusProbation::progres($employee['mulai_probation'] ?? null, $employee['akhir_probation'] ?? null, $hariIni);
        $status  = (string) ($employee['status'] ?? 'pending');

        // Penilaian disusun ulang menurut nomornya supaya urutannya pasti,
        // berapa pun urutan masuknya dari basis data.
        $perNomor = [];
        foreach ($evaluations as $ev) {
            $nomor = (int) ($ev['nomor_penilaian'] ?? 1);
            $perNomor[$nomor] ??= $ev;
        }
        ksort($perNomor);

        $nilai = [];
        foreach ($perNomor as $ev) {
            if ($ev['nilai_total'] !== null) {
                $nilai[] = (float) $ev['nilai_total'];
            }
        }
        $rata = $nilai === [] ? null : round(array_sum($nilai) / count($nilai), 2);

        // Perjalanan probation sebagai empat langkah, supaya Team Member tahu
        // posisinya sekarang tanpa harus membaca tabel.
        $sudah1 = isset($perNomor[1]);
        $sudah2 = isset($perNomor[2]);
        $final  = $status !== 'pending';

        // Perkiraan jadwal penilaian berikutnya, supaya langkah yang belum
        // terjadi tetap memberi tanggal alih-alih sekadar "menunggu".
        $jatuhTempo = static fn(?string $dari): string => empty($dari)
            ? 'Menunggu Team Leader'
            : 'Perkiraan ' . self::tanggalPendek(date('Y-m-d', strtotime($dari . ' +' . SiklusProbation::INTERVAL_HARI . ' days')));

        $langkah = [
            [
                'label'  => 'Mulai Probation',
                'detail' => self::tanggalPendek($employee['mulai_probation'] ?? null),
                'status' => 'selesai',
            ],
            [
                'label'  => 'Penilaian ke-1',
                'detail' => match (true) {
                    $sudah1 => self::tanggalPendek($perNomor[1]['tanggal_penilaian']),
                    $final  => 'Tidak dilakukan',
                    default => $jatuhTempo($employee['mulai_probation'] ?? null),
                },
                'status' => $sudah1 ? 'selesai' : ($final ? 'lewat' : 'aktif'),
            ],
            [
                'label'  => 'Penilaian ke-2',
                'detail' => match (true) {
                    $sudah2 => self::tanggalPendek($perNomor[2]['tanggal_penilaian']),
                    $final  => 'Tidak dilakukan',
                    $sudah1 => $jatuhTempo($perNomor[1]['tanggal_penilaian']),
                    default => 'Setelah penilaian ke-1',
                },
                'status' => $sudah2 ? 'selesai' : ($final ? 'lewat' : ($sudah1 ? 'aktif' : 'menunggu')),
            ],
            [
                'label'  => 'Keputusan HRD',
                'detail' => $final ? self::labelStatus($status) : ($sudah2 ? 'Sedang ditinjau HRD' : 'Menunggu penilaian lengkap'),
                'status' => $final ? 'selesai' : ($sudah2 ? 'aktif' : 'menunggu'),
            ],
        ];

        return [
            'progres'    => $progres,
            'status'     => $status,
            'langkah'    => $langkah,
            'penilaian'  => array_values($perNomor),
            'rata'       => $rata,
            'band'       => SiklusProbation::bandNilai($rata),
            'belum_dibuka' => count(array_filter($evaluations, static fn($e) => empty($e['dilihat_at']))),
            'sk'         => $keputusan,
        ];
    }

    /**
     * '2025-11-15' → '15 Nov 2025'.
     *
     * date('M') mengeluarkan nama bulan Inggris, sedangkan seluruh tampilan
     * aplikasi ini berbahasa Indonesia — jadi nama bulannya disusun sendiri.
     */
    public static function tanggalPendek(?string $tanggal): string
    {
        if (empty($tanggal)) {
            return '-';
        }

        $ts = strtotime($tanggal);
        if ($ts === false) {
            return '-';
        }

        $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        return date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    }

    /** 'tidak-lulus' → 'Tidak Lulus'. */
    public static function labelStatus(string $status): string
    {
        return match ($status) {
            'lulus'       => 'Lulus',
            'tidak-lulus' => 'Tidak Lulus',
            'warning'     => 'Warning',
            default       => 'Masih Probation',
        };
    }

    // =================================================================
    // BAGIAN BERSAMA
    // =================================================================

    /**
     * employee_id → rekap penilaiannya.
     *
     * @return array<int, array{jumlah:int, terakhir:?string, ke1:?array, ke2:?array, nilai:list<float>}>
     */
    private static function petaPenilaian(array $penilaian): array
    {
        $peta = [];
        foreach ($penilaian as $p) {
            $id    = (int) $p['employee_id'];
            $nomor = (int) ($p['nomor_penilaian'] ?? 1);
            $peta[$id] ??= ['jumlah' => 0, 'terakhir' => null, 'ke1' => null, 'ke2' => null, 'nilai' => []];

            $peta[$id]['jumlah']++;
            if ($p['nilai_total'] !== null) {
                $peta[$id]['nilai'][] = (float) $p['nilai_total'];
            }

            $tanggal = $p['tanggal_penilaian'] ?? null;
            if ($tanggal !== null && ($peta[$id]['terakhir'] === null || $tanggal > $peta[$id]['terakhir'])) {
                $peta[$id]['terakhir'] = $tanggal;
            }

            $kunci = $nomor >= 2 ? 'ke2' : 'ke1';
            if ($peta[$id][$kunci] === null) {
                $peta[$id][$kunci] = $p;
            }
        }

        return $peta;
    }

    /** Tahap → baris corong siap tampil, lengkap dengan persentasenya. */
    private static function corong(array $jumlahPerTahap, int $total): array
    {
        $label = [
            SiklusProbation::TAHAP_BELUM     => ['Belum dinilai',        'bg-gray-400'],
            SiklusProbation::TAHAP_SATU_KALI => ['Sudah dinilai 1 kali', 'bg-blue-500'],
            SiklusProbation::TAHAP_MENUNGGU  => ['Menunggu keputusan HRD', 'bg-amber-500'],
            SiklusProbation::TAHAP_SELESAI   => ['Sudah diputuskan',     'bg-green-500'],
        ];

        $out = [];
        foreach ($label as $tahap => [$teks, $warna]) {
            $jumlah = $jumlahPerTahap[$tahap] ?? 0;
            $out[]  = [
                'kunci'  => $tahap,
                'label'  => $teks,
                'warna'  => $warna,
                'jumlah' => $jumlah,
                'persen' => $total > 0 ? (int) round($jumlah / $total * 100) : 0,
            ];
        }

        return $out;
    }

    /**
     * Sebaran mutu nilai + rata-ratanya.
     *
     * @param list<float> $nilai
     */
    private static function distribusiNilai(array $nilai): array
    {
        // Satu nilai contoh per golongan supaya label dan warnanya tetap
        // diambil dari SiklusProbation, bukan ditulis ulang di sini.
        $contoh = ['sangat_baik' => 9.5, 'baik' => 7.5, 'cukup' => 6.5, 'kurang' => 3.0];

        $band = [];
        foreach (SiklusProbation::urutanBand() as $kunci) {
            $info         = SiklusProbation::bandNilai($contoh[$kunci]);
            $band[$kunci] = ['kunci' => $kunci, 'label' => $info['label'], 'warna' => $info['warna'],
                             'teks' => $info['teks'], 'jumlah' => 0, 'persen' => 0];
        }

        $diBawahStandar = 0;
        foreach ($nilai as $n) {
            $band[SiklusProbation::bandNilai($n)['kunci']]['jumlah']++;
            if ($n < SiklusProbation::NILAI_LULUS) {
                $diBawahStandar++;
            }
        }

        $total = count($nilai);
        foreach ($band as $kunci => $b) {
            $band[$kunci]['persen'] = $total > 0 ? (int) round($b['jumlah'] / $total * 100) : 0;
        }

        return [
            'jumlah'           => $total,
            'rata'             => $total > 0 ? round(array_sum($nilai) / $total, 2) : null,
            'tertinggi'        => $total > 0 ? max($nilai) : null,
            'terendah'         => $total > 0 ? min($nilai) : null,
            'di_bawah_standar' => $diBawahStandar,
            'band'             => array_values($band),
        ];
    }

    /** Banyaknya penilaian per bulan untuk $bulan bulan terakhir. */
    private static function tren(array $penilaian, int $bulan, ?string $hariIni = null): array
    {
        $namaBulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $acuan     = strtotime(date('Y-m-01', strtotime($hariIni ?: 'today')));

        $ember = [];
        for ($i = $bulan - 1; $i >= 0; $i--) {
            $ts             = strtotime("-{$i} month", $acuan);
            $ember[date('Y-m', $ts)] = ['label' => $namaBulan[(int) date('n', $ts)], 'tahun' => date('Y', $ts), 'jumlah' => 0];
        }

        foreach ($penilaian as $p) {
            $kunci = date('Y-m', strtotime((string) ($p['tanggal_penilaian'] ?? '')));
            if (isset($ember[$kunci])) {
                $ember[$kunci]['jumlah']++;
            }
        }

        $maks = max(1, max(array_column($ember, 'jumlah')));
        foreach ($ember as $kunci => $b) {
            $ember[$kunci]['persen'] = (int) round($b['jumlah'] / $maks * 100);
        }

        return array_values($ember);
    }

    /**
     * Kelompok (departemen / Team Leader) → baris tabel dengan rata-rata nilai,
     * diurutkan dari yang anggotanya terbanyak.
     */
    private static function rapikanKelompok(array $kelompok, string $kunciUrut): array
    {
        foreach ($kelompok as $nama => $k) {
            $nilai = $k['nilai'];
            $kelompok[$nama]['rata'] = $nilai === [] ? null : round(array_sum($nilai) / count($nilai), 2);
            unset($kelompok[$nama]['nilai']);
        }

        $out = array_values($kelompok);
        usort($out, static fn($a, $b) => $b[$kunciUrut] <=> $a[$kunciUrut]);

        return $out;
    }

    /** Sisa hari masa probation, atau null bila tanggalnya tidak lengkap. */
    private static function sisaProbation(array $employee, ?string $hariIni): ?int
    {
        $akhir = $employee['akhir_probation'] ?? null;
        if (empty($akhir)) {
            if (empty($employee['mulai_probation'])) {
                return null;
            }
            $akhir = date('Y-m-d', strtotime($employee['mulai_probation'] . ' +' . SiklusProbation::DURASI_HARI . ' days'));
        }

        return SiklusProbation::selisihHari($hariIni ?: 'today', $akhir);
    }
}
