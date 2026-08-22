<?php
/**
 * Dashboard HRD.
 *
 * Bukan salinan menu "Daftar Penilaian": di sini tidak ada satu pun tabel
 * penilaian per orang. Yang ditampilkan adalah keadaan prosesnya — apa yang
 * menunggu tindakan HRD, sudah sampai mana tiap Team Member berjalan, dan
 * bagaimana sebaran hasilnya. Setiap kartu menautkan ke menu yang benar untuk
 * mengerjakannya.
 *
 * Semua angka datang dari App\Libraries\RingkasanDashboard::hrd().
 */
$this->extend('layouts/main');
$this->section('content');

$r        = $ringkasan;
$tindakan = $r['tindakan'];
$totalTindakan = $tindakan['menunggu_keputusan']['jumlah']
               + $tindakan['sk_belum_terbit']['jumlah']
               + $tindakan['segera_berakhir']['jumlah'];

?>

<?= $this->include('partials/dashboard_hero') ?>

<!-- ============================================================
     1. PERLU TINDAKAN — satu-satunya bagian yang menuntut kerja
     ============================================================ -->
<div class="flex items-baseline justify-between mb-3">
    <div>
        <h2 class="text-lg font-bold text-gray-900">Perlu Tindakan Anda</h2>
        <p class="text-sm text-gray-500">Hal yang tertahan di meja HRD dan menghambat Team Member</p>
    </div>
    <?php if ($totalTindakan === 0): ?>
        <span class="text-xs font-semibold text-green-700 bg-green-50 px-3 py-1 rounded-full">
            <i class="fas fa-check mr-1"></i>Tidak ada yang tertunda
        </span>
    <?php endif; ?>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
    <?php
    // [judul, jumlah, ikon, warna, tautan, keterangan, daftar contoh, penyusun baris]
    $kartu = [
        [
            'judul'   => 'Menunggu Keputusan',
            'jumlah'  => $tindakan['menunggu_keputusan']['jumlah'],
            'ikon'    => 'fa-gavel',
            'warna'   => 'amber',
            'tautan'  => '/evaluations',
            'ket'     => 'Sudah dinilai 2 kali, status akhirnya belum Anda tetapkan',
            'daftar'  => $tindakan['menunggu_keputusan']['daftar'],
            'sisi'    => static fn($x) => $x['sejak'] . ' hari',
        ],
        [
            'judul'   => 'SK Belum Terbit',
            'jumlah'  => $tindakan['sk_belum_terbit']['jumlah'],
            'ikon'    => 'fa-file-contract',
            'warna'   => 'purple',
            'tautan'  => '/evaluations',
            'ket'     => 'Sudah diputuskan Lulus, tapi suratnya belum bisa dicetak',
            'daftar'  => $tindakan['sk_belum_terbit']['daftar'],
            'sisi'    => static fn($x) => '',
        ],
        [
            'judul'   => 'Probation Segera Berakhir',
            'jumlah'  => $tindakan['segera_berakhir']['jumlah'],
            'ikon'    => 'fa-hourglass-end',
            'warna'   => 'red',
            'tautan'  => '/employees',
            'ket'     => 'Masa probation habis dalam 14 hari atau sudah terlewat',
            'daftar'  => $tindakan['segera_berakhir']['daftar'],
            'sisi'    => static fn($x) => $x['sisa'] < 0 ? 'lewat ' . abs($x['sisa']) . ' hari' : 'sisa ' . $x['sisa'] . ' hari',
        ],
        [
            'judul'   => 'Belum Dibuka Member',
            'jumlah'  => $tindakan['belum_dibuka']['jumlah'],
            'ikon'    => 'fa-eye-slash',
            'warna'   => 'blue',
            'tautan'  => '/evaluations',
            'ket'     => 'Hasil penilaian yang belum pernah dibuka Team Member-nya',
            'daftar'  => [],
            'sisi'    => static fn($x) => '',
        ],
    ];

    foreach ($kartu as $k):
        $aktif = $k['jumlah'] > 0;
        ?>
        <a href="<?= $k['tautan'] ?>"
           class="flex flex-col h-full bg-white rounded-xl shadow p-5 border-l-4 border-<?= $aktif ? $k['warna'] : 'gray' ?>-500 hover:shadow-lg transition">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-700"><?= $k['judul'] ?></p>
                    <p class="text-3xl font-bold <?= $aktif ? 'text-' . $k['warna'] . '-600' : 'text-gray-300' ?> mt-1"><?= $k['jumlah'] ?></p>
                </div>
                <span class="p-2.5 rounded-lg bg-<?= $aktif ? $k['warna'] : 'gray' ?>-100 shrink-0">
                    <i class="fas <?= $k['ikon'] ?> <?= $aktif ? 'text-' . $k['warna'] . '-600' : 'text-gray-400' ?>"></i>
                </span>
            </div>
            <p class="text-xs text-gray-500 mt-2 leading-snug"><?= $k['ket'] ?></p>
            <?php if (!empty($k['daftar'])): ?>
                <ul class="mt-auto pt-3 border-t space-y-1">
                    <?php foreach (array_slice($k['daftar'], 0, 3) as $x): ?>
                        <li class="flex items-center justify-between gap-2 text-xs">
                            <span class="text-gray-700 truncate"><?= htmlspecialchars($x['nama']) ?></span>
                            <span class="text-gray-400 shrink-0"><?= htmlspecialchars(($k['sisi'])($x)) ?></span>
                        </li>
                    <?php endforeach; ?>
                    <?php if ($k['jumlah'] > 3): ?>
                        <li class="text-xs text-gray-400">+<?= $k['jumlah'] - 3 ?> lainnya</li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- ============================================================
     2. PERJALANAN PROBATION + HASIL AKHIR
     ============================================================ -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

    <!-- Corong: sudah sampai mana tiap orang -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-bold text-gray-900">Perjalanan Probation</h3>
        <p class="text-sm text-gray-500 mb-5">Posisi <?= $r['total'] ?> Team Member pada tahapan prosesnya</p>

        <?php if ($r['total'] === 0): ?>
            <p class="text-sm text-gray-400 py-8 text-center">Belum ada Team Member terdaftar.</p>
        <?php else: ?>
            <!-- Satu batang, empat ruas — proporsi seluruh Team Member -->
            <div class="flex w-full h-4 rounded-full overflow-hidden bg-gray-100 mb-5">
                <?php foreach ($r['corong'] as $c): ?>
                    <?php if ($c['jumlah'] > 0): ?>
                        <div class="<?= $c['warna'] ?>" style="width: <?= $c['persen'] ?>%"
                             title="<?= htmlspecialchars($c['label']) ?>: <?= $c['jumlah'] ?> orang"></div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="space-y-3">
                <?php foreach ($r['corong'] as $c): ?>
                    <div class="flex items-center gap-3">
                        <span class="w-2.5 h-2.5 rounded-full <?= $c['warna'] ?> shrink-0"></span>
                        <span class="text-sm text-gray-700 w-44 shrink-0"><?= htmlspecialchars($c['label']) ?></span>
                        <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full <?= $c['warna'] ?> rounded-full" style="width: <?= $c['persen'] ?>%"></div>
                        </div>
                        <span class="text-sm font-bold text-gray-900 w-8 text-right"><?= $c['jumlah'] ?></span>
                        <span class="text-xs text-gray-400 w-12 text-right"><?= $c['persen'] ?>%</span>
                    </div>
                <?php endforeach; ?>
            </div>

            <p class="text-xs text-gray-400 mt-5 leading-relaxed">
                Tiap Team Member dinilai maksimal <?= \App\Libraries\SiklusProbation::MAKS_PENILAIAN ?> kali,
                berjarak <?= \App\Libraries\SiklusProbation::INTERVAL_HARI ?> hari. Setelah lengkap, giliran HRD yang menetapkan status akhirnya.
            </p>

            <!-- Tiga angka penutup: keadaan proses hari ini dalam satu baris -->
            <div class="grid grid-cols-3 gap-3 mt-5 pt-5 border-t">
                <?php
                $ringkas = [
                    ['Masih berjalan',   $r['berjalan'],                            'text-gray-900'],
                    ['Perlu dinilai',    $tindakan['perlu_dinilai']['jumlah'],      $tindakan['perlu_dinilai']['jumlah'] > 0 ? 'text-amber-600' : 'text-gray-900'],
                    ['Sudah diputuskan', $r['hasil']['diputuskan'],                 'text-green-600'],
                ];
                foreach ($ringkas as [$label, $jumlah, $teks]):
                    ?>
                    <div class="text-center">
                        <p class="text-2xl font-bold <?= $teks ?>"><?= $jumlah ?></p>
                        <p class="text-xs text-gray-500 mt-0.5"><?= $label ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Donat hasil akhir -->
    <?php
    $h   = $r['hasil'];
    $seg = [
        ['Lulus',           $h['lulus'],       '#22c55e'],
        ['Tidak Lulus',     $h['tidak_lulus'], '#ef4444'],
        ['Warning',         $h['warning'],     '#f59e0b'],
        ['Masih Probation', $h['berjalan'],    '#94a3b8'],
    ];
    $totalSeg = array_sum(array_column($seg, 1));
    $keliling = 2 * M_PI * 54;
    $offset   = 0;
    ?>
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-bold text-gray-900">Hasil Akhir</h3>
        <p class="text-sm text-gray-500 mb-4">Status seluruh Team Member</p>

        <div class="relative w-40 h-40 mx-auto mb-5">
            <svg viewBox="0 0 128 128" class="w-40 h-40 -rotate-90">
                <circle cx="64" cy="64" r="54" fill="none" stroke="#e5e7eb" stroke-width="16"></circle>
                <?php foreach ($seg as [$label, $jumlah, $warna]): ?>
                    <?php if ($jumlah > 0 && $totalSeg > 0): ?>
                        <?php $panjang = $keliling * $jumlah / $totalSeg; ?>
                        <circle cx="64" cy="64" r="54" fill="none" stroke="<?= $warna ?>" stroke-width="16"
                                stroke-dasharray="<?= round($panjang, 2) ?> <?= round($keliling - $panjang, 2) ?>"
                                stroke-dashoffset="<?= round(-$offset, 2) ?>"></circle>
                        <?php $offset += $panjang; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
                <?php if ($h['tingkat_kelulusan'] !== null): ?>
                    <span class="text-3xl font-bold text-gray-900"><?= $h['tingkat_kelulusan'] ?>%</span>
                    <span class="text-xs text-gray-500">tingkat kelulusan</span>
                <?php else: ?>
                    <span class="text-sm text-gray-400 text-center px-6">Belum ada<br>keputusan</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="space-y-2">
            <?php foreach ($seg as [$label, $jumlah, $warna]): ?>
                <div class="flex items-center gap-2 text-sm">
                    <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background: <?= $warna ?>"></span>
                    <span class="text-gray-600 flex-1"><?= $label ?></span>
                    <span class="font-bold text-gray-900"><?= $jumlah ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if ($h['diputuskan'] > 0): ?>
            <p class="text-xs text-gray-400 mt-4">Dari <?= $h['diputuskan'] ?> Team Member yang sudah diputuskan.</p>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================
     3. MUTU NILAI + TREN
     ============================================================ -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

    <!-- Sebaran nilai -->
    <?php $n = $r['nilai']; ?>
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-bold text-gray-900">Sebaran Nilai</h3>
        <p class="text-sm text-gray-500 mb-5"><?= $n['jumlah'] ?> lembar penilaian, standar kelulusan &ge; <?= \App\Libraries\SiklusProbation::NILAI_LULUS ?></p>

        <?php if ($n['jumlah'] === 0): ?>
            <p class="text-sm text-gray-400 py-8 text-center">Belum ada penilaian yang masuk.</p>
        <?php else: ?>
            <div class="flex flex-wrap items-center gap-6 mb-5">
                <div>
                    <p class="text-xs text-gray-500">Rata-rata</p>
                    <p class="text-4xl font-bold <?= \App\Libraries\SiklusProbation::bandNilai($n['rata'])['teks'] ?>"><?= number_format((float) $n['rata'], 2) ?></p>
                </div>
                <div class="text-sm space-y-1">
                    <p class="text-gray-500">Tertinggi <span class="font-semibold text-gray-900"><?= number_format((float) $n['tertinggi'], 2) ?></span></p>
                    <p class="text-gray-500">Terendah <span class="font-semibold text-gray-900"><?= number_format((float) $n['terendah'], 2) ?></span></p>
                </div>
                <div class="text-sm">
                    <p class="text-gray-500">Di bawah standar</p>
                    <p class="text-xl font-bold <?= $n['di_bawah_standar'] > 0 ? 'text-red-600' : 'text-green-600' ?>"><?= $n['di_bawah_standar'] ?> lembar</p>
                </div>
            </div>

            <div class="space-y-3">
                <?php foreach ($n['band'] as $b): ?>
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-700 w-28 shrink-0"><?= htmlspecialchars($b['label']) ?></span>
                        <div class="flex-1 h-2.5 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full <?= $b['warna'] ?> rounded-full" style="width: <?= $b['persen'] ?>%"></div>
                        </div>
                        <span class="text-sm font-bold text-gray-900 w-8 text-right"><?= $b['jumlah'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tren penilaian -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-bold text-gray-900">Penilaian Masuk</h3>
        <p class="text-sm text-gray-500 mb-5">Banyaknya lembar penilaian 6 bulan terakhir</p>

        <div class="flex items-end justify-between gap-3 h-40 px-1">
            <?php foreach ($r['tren'] as $t): ?>
                <div class="flex-1 flex flex-col items-center justify-end h-full">
                    <span class="text-xs font-semibold text-gray-700 mb-1"><?= $t['jumlah'] ?></span>
                    <div class="w-full rounded-t-lg <?= $t['jumlah'] > 0 ? 'bg-blue-500' : 'bg-gray-200' ?>"
                         style="height: <?= max(2, (int) $t['persen']) ?>%"></div>
                    <span class="text-xs text-gray-500 mt-2"><?= htmlspecialchars($t['label']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ============================================================
     4. RINGKASAN PER KELOMPOK
     ============================================================ -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- Per departemen -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h3 class="text-lg font-bold text-gray-900">Per Departemen</h3>
            <p class="text-sm text-gray-500">Jumlah Team Member dan mutu nilainya</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b text-xs uppercase tracking-wide text-gray-600">
                        <th class="px-6 py-3 text-left font-semibold">Departemen</th>
                        <th class="px-4 py-3 text-center font-semibold">Member</th>
                        <th class="px-4 py-3 text-center font-semibold">Lulus</th>
                        <th class="px-6 py-3 text-right font-semibold">Rata-rata</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($r['departemen'] as $d): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-6 py-3 text-gray-800"><?= htmlspecialchars($d['nama']) ?></td>
                            <td class="px-4 py-3 text-center text-gray-700"><?= $d['jumlah'] ?></td>
                            <td class="px-4 py-3 text-center text-gray-700"><?= $d['lulus'] ?></td>
                            <td class="px-6 py-3 text-right font-semibold <?= \App\Libraries\SiklusProbation::bandNilai($d['rata'])['teks'] ?>">
                                <?= $d['rata'] === null ? '—' : number_format((float) $d['rata'], 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($r['departemen'])): ?>
                        <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">Belum ada data</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Per team leader -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-gray-900">Progres per Team Leader</h3>
                <p class="text-sm text-gray-500">Siapa yang masih punya penilaian tertunggak</p>
            </div>
            <a href="/users" class="text-blue-600 hover:text-blue-700 text-sm font-medium shrink-0">Kelola →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b text-xs uppercase tracking-wide text-gray-600">
                        <th class="px-6 py-3 text-left font-semibold">Team Leader</th>
                        <th class="px-4 py-3 text-center font-semibold">Anggota</th>
                        <th class="px-4 py-3 text-center font-semibold">Perlu Dinilai</th>
                        <th class="px-6 py-3 text-right font-semibold">Rata-rata</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($r['leader'] as $l): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-6 py-3 text-gray-800"><?= htmlspecialchars($l['nama']) ?></td>
                            <td class="px-4 py-3 text-center text-gray-700"><?= $l['anggota'] ?></td>
                            <td class="px-4 py-3 text-center">
                                <?php if ($l['perlu_dinilai'] > 0): ?>
                                    <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 font-semibold text-xs"><?= $l['perlu_dinilai'] ?></span>
                                <?php else: ?>
                                    <span class="text-gray-300">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-3 text-right font-semibold <?= \App\Libraries\SiklusProbation::bandNilai($l['rata'])['teks'] ?>">
                                <?= $l['rata'] === null ? '—' : number_format((float) $l['rata'], 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($r['leader'])): ?>
                        <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">Belum ada data</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>
