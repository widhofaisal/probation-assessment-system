<?php

use App\Libraries\RingkasanDashboard;
use App\Libraries\SiklusProbation;

/**
 * Dashboard Team Leader.
 *
 * Menjawab dua hal saja: siapa yang harus saya nilai, dan bagaimana tim saya
 * berjalan. Daftar anggota beserta form penilaiannya tetap di menu "Tim Saya" —
 * dashboard ini hanya menunjuk ke sana, tidak menyalin tabelnya.
 *
 * Semua angka datang dari App\Libraries\RingkasanDashboard::teamLeader().
 */
$this->extend('layouts/main');
$this->section('content');

$r = $ringkasan;


/** Rupa badge untuk tiap keadaan jadwal penilaian. */
$rupaJadwal = static function (string $status): array {
    return match ($status) {
        SiklusProbation::PERLU_DINILAI   => ['bg-amber-100 text-amber-700', 'Perlu dinilai', 'fa-clock'],
        SiklusProbation::SUDAH_DINILAI   => ['bg-blue-100 text-blue-700', 'Sudah dinilai 1x', 'fa-check'],
        SiklusProbation::BELUM_WAKTUNYA  => ['bg-gray-100 text-gray-500', 'Belum waktunya', 'fa-hourglass-half'],
        SiklusProbation::SELESAI_DINILAI => ['bg-purple-100 text-purple-700', 'Menunggu HRD', 'fa-gavel'],
        default                          => ['bg-green-100 text-green-700', 'Selesai', 'fa-flag-checkered'],
    };
};
?>

<?= $this->include('partials/dashboard_hero') ?>

<!-- ============================================================
     1. ANGKA POKOK
     ============================================================ -->
<div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
    <?php
    $kartu = [
        ['Perlu Dinilai Sekarang', $r['perlu_dinilai'],      'fa-clock',      'amber',  '/team',        'Sudah jatuh tempo siklus penilaiannya'],
        ['Menunggu Keputusan HRD', $r['menunggu_keputusan'], 'fa-gavel',      'purple', '/team',        'Sudah 2x dinilai, tidak ada tugas Anda lagi'],
        ['Belum Dibuka Member',    $r['belum_dibuka'],       'fa-eye-slash',  'blue',   '/team',        'Hasil yang belum pernah dibuka anggota'],
        ['Total Penilaian Dibuat', $r['total_penilaian'],    'fa-file-alt',   'green',  '/team',        'Lembar penilaian yang sudah Anda kirim'],
    ];
    foreach ($kartu as [$judul, $jumlah, $ikon, $warna, $tautan, $ket]):
        $aktif = $jumlah > 0;
        ?>
        <a href="<?= $tautan ?>" class="block bg-white rounded-xl shadow p-5 border-t-4 border-<?= $aktif ? $warna : 'gray' ?>-500 hover:shadow-lg transition">
            <div class="flex items-start justify-between gap-2">
                <p class="text-sm font-semibold text-gray-700 leading-snug"><?= $judul ?></p>
                <i class="fas <?= $ikon ?> <?= $aktif ? 'text-' . $warna . '-500' : 'text-gray-300' ?>"></i>
            </div>
            <p class="text-3xl font-bold <?= $aktif ? 'text-gray-900' : 'text-gray-300' ?> mt-1"><?= $jumlah ?></p>
            <p class="text-xs text-gray-500 mt-1.5 leading-snug"><?= $ket ?></p>
        </a>
    <?php endforeach; ?>
</div>

<!-- ============================================================
     2. JADWAL PENILAIAN — inti pekerjaan Team Leader
     ============================================================ -->
<div class="bg-white rounded-xl shadow mb-8">
    <div class="px-6 py-4 border-b flex items-center justify-between gap-3">
        <div>
            <h3 class="text-lg font-bold text-gray-900">Jadwal Penilaian</h3>
            <p class="text-sm text-gray-500">Urut dari yang paling mendesak · siklus <?= SiklusProbation::INTERVAL_HARI ?> hari</p>
        </div>
        <a href="/team" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition shrink-0">
            <i class="fas fa-star mr-1.5"></i>Buka Tim Saya
        </a>
    </div>

    <?php if (empty($r['jadwal'])): ?>
        <div class="px-6 py-10 text-center text-gray-400">
            <i class="fas fa-mug-hot text-3xl mb-3 block text-gray-300"></i>
            <p class="font-medium text-gray-600">Tidak ada penilaian yang tertunggak</p>
            <p class="text-sm mt-1">Semua anggota tim Anda sudah dinilai sesuai siklusnya.</p>
        </div>
    <?php else: ?>
        <ul class="divide-y">
            <?php foreach ($r['jadwal'] as $a): ?>
                <?php
                [$kelas, $teks, $ikon] = $rupaJadwal($a['jadwal']);
                $mendesak = $a['jadwal'] === SiklusProbation::PERLU_DINILAI;
                ?>
                <li class="px-6 py-4 flex flex-wrap items-center gap-4 hover:bg-gray-50 transition">
                    <div class="w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center shrink-0 font-bold text-gray-600 text-sm">
                        <?= htmlspecialchars(strtoupper(substr($a['nama'], 0, 1))) ?>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-gray-900 truncate"><?= htmlspecialchars($a['nama']) ?></p>
                        <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($a['nik']) ?> · <?= htmlspecialchars($a['posisi']) ?></p>
                    </div>
                    <div class="text-center shrink-0">
                        <p class="text-xs text-gray-500">Penilaian</p>
                        <p class="text-sm font-semibold text-gray-800"><?= $a['dinilai'] ?>/<?= SiklusProbation::MAKS_PENILAIAN ?></p>
                    </div>
                    <div class="shrink-0 text-right w-36">
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $kelas ?>">
                            <i class="fas <?= $ikon ?> mr-1"></i><?= $teks ?>
                        </span>
                        <p class="text-xs text-gray-400 mt-1">
                            <?= $mendesak ? 'Bisa dinilai sekarang' : '~' . (int) $a['sisa_hari'] . ' hari lagi' ?>
                        </p>
                    </div>
                    <!-- Lebarnya dikunci walau tombolnya tidak ada, supaya
                         kolom badge di sebelah kiri tetap lurus antar baris. -->
                    <div class="w-24 shrink-0 text-right">
                        <?php if ($mendesak): ?>
                            <a href="/team" class="inline-block px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg transition">
                                Nilai ke-<?= $a['dinilai'] + 1 ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<!-- ============================================================
     3. PROGRES TIAP ANGGOTA + MUTU NILAI
     ============================================================ -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Garis waktu probation tiap anggota -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-bold text-gray-900">Progres Masa Probation</h3>
        <p class="text-sm text-gray-500 mb-5">Seberapa jauh masa probation tiap anggota sudah berjalan</p>

        <?php if (empty($r['anggota'])): ?>
            <p class="text-sm text-gray-400 py-8 text-center">Belum ada anggota tim. Hubungi HRD untuk menambahkan.</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($r['anggota'] as $a): ?>
                    <?php
                    $p      = $a['progres'];
                    $selesai = $a['status'] !== 'pending';
                    $warna  = $selesai ? 'bg-green-500' : ($p['persen'] >= 90 ? 'bg-red-500' : ($p['persen'] >= 60 ? 'bg-amber-500' : 'bg-blue-500'));
                    ?>
                    <div>
                        <div class="flex items-baseline justify-between gap-3 mb-1.5">
                            <p class="text-sm font-medium text-gray-800 truncate"><?= htmlspecialchars($a['nama']) ?></p>
                            <p class="text-xs text-gray-500 shrink-0">
                                <?php if ($selesai): ?>
                                    <?= htmlspecialchars(RingkasanDashboard::labelStatus($a['status'])) ?>
                                <?php elseif ($p['lewat']): ?>
                                    <span class="text-red-600 font-semibold">Masa probation terlewat</span>
                                <?php else: ?>
                                    hari ke-<?= $p['terpakai'] ?> dari <?= $p['total'] ?> · sisa <?= $p['sisa'] ?> hari
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex-1 h-2.5 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full <?= $warna ?> rounded-full" style="width: <?= $p['persen'] ?>%"></div>
                            </div>
                            <div class="flex gap-1 shrink-0 w-24 justify-end">
                                <?php foreach ([1 => $a['nilai1'], 2 => $a['nilai2']] as $ke => $ev): ?>
                                    <?php if ($ev): ?>
                                        <a href="/evaluations/<?= (int) $ev['id'] ?>"
                                           title="Penilaian ke-<?= $ke ?>"
                                           class="px-1.5 py-0.5 rounded text-xs font-bold bg-gray-100 hover:bg-gray-200 <?= SiklusProbation::bandNilai((float) $ev['nilai_total'])['teks'] ?>">
                                            <?= number_format((float) $ev['nilai_total'], 1) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="px-1.5 py-0.5 rounded text-xs font-bold bg-gray-50 text-gray-300" title="Penilaian ke-<?= $ke ?> belum ada">–</span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="text-xs text-gray-400 mt-5">Dua kotak di kanan adalah nilai penilaian ke-1 dan ke-2 — klik untuk membuka lembarnya.</p>
        <?php endif; ?>
    </div>

    <!-- Mutu nilai + hasil akhir tim -->
    <div class="space-y-6">
        <?php $n = $r['nilai']; ?>
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="text-lg font-bold text-gray-900">Nilai Tim</h3>
            <p class="text-sm text-gray-500 mb-4"><?= $n['jumlah'] ?> lembar penilaian</p>

            <?php if ($n['jumlah'] === 0): ?>
                <p class="text-sm text-gray-400 py-6 text-center">Belum ada penilaian.</p>
            <?php else: ?>
                <div class="text-center mb-5">
                    <p class="text-xs text-gray-500">Rata-rata tim</p>
                    <p class="text-4xl font-bold <?= SiklusProbation::bandNilai($n['rata'])['teks'] ?>"><?= number_format((float) $n['rata'], 2) ?></p>
                    <p class="text-xs text-gray-400 mt-0.5">standar kelulusan &ge; <?= SiklusProbation::NILAI_LULUS ?></p>
                </div>
                <div class="space-y-2.5">
                    <?php foreach ($n['band'] as $b): ?>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-600 w-20 shrink-0"><?= htmlspecialchars($b['label']) ?></span>
                            <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full <?= $b['warna'] ?> rounded-full" style="width: <?= $b['persen'] ?>%"></div>
                            </div>
                            <span class="text-xs font-bold text-gray-900 w-5 text-right"><?= $b['jumlah'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($n['di_bawah_standar'] > 0): ?>
                    <p class="text-xs text-red-600 mt-4 font-medium">
                        <i class="fas fa-triangle-exclamation mr-1"></i><?= $n['di_bawah_standar'] ?> lembar di bawah standar kelulusan
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Hasil Akhir Tim</h3>
            <div class="grid grid-cols-2 gap-3">
                <?php
                $hasil = [
                    ['Lulus',           $r['hasil']['lulus'],       'text-green-600', 'bg-green-50'],
                    ['Tidak Lulus',     $r['hasil']['tidak_lulus'], 'text-red-600',   'bg-red-50'],
                    ['Warning',         $r['hasil']['warning'],     'text-amber-600', 'bg-amber-50'],
                    ['Masih Probation', $r['hasil']['berjalan'],    'text-gray-600',  'bg-gray-50'],
                ];
                foreach ($hasil as [$label, $jumlah, $teks, $latar]):
                    ?>
                    <div class="<?= $latar ?> rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold <?= $teks ?>"><?= $jumlah ?></p>
                        <p class="text-xs text-gray-600 mt-0.5"><?= $label ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>
