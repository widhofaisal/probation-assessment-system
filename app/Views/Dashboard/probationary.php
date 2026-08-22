<?php

use App\Libraries\RingkasanDashboard;
use App\Libraries\SiklusProbation;
use App\Libraries\SuratKeputusan;
use App\Models\EvaluationDecisionModel;

/**
 * Dashboard Team Member.
 *
 * Pertanyaan yang dijawab halaman ini: masa probation saya sudah sampai mana,
 * apa yang sedang ditunggu, dan berapa nilai saya. Rincian tiap lembar
 * penilaian — aspek, alasan, catatan Team Leader — ada di menu "Hasil Evaluasi",
 * jadi tidak diulang di sini.
 *
 * Semua angka datang dari App\Libraries\RingkasanDashboard::member().
 */
$this->extend('layouts/main');
$this->section('content');

$r  = $ringkasan;
$p  = $r['progres'];
$sk = $r['sk'];


[$statusKelas, $statusIkon] = match ($r['status']) {
    'lulus'       => ['bg-green-100 text-green-700', 'fa-trophy'],
    'tidak-lulus' => ['bg-red-100 text-red-700', 'fa-circle-xmark'],
    'warning'     => ['bg-amber-100 text-amber-700', 'fa-triangle-exclamation'],
    default       => ['bg-blue-100 text-blue-700', 'fa-spinner'],
};
?>

<?= $this->include('partials/dashboard_hero') ?>

<?php if ($sk): ?>
    <!-- Surat Keputusan — terbit sendiri setelah HRD memutuskan Lulus -->
    <div class="mb-6 bg-white rounded-xl shadow border-l-4 border-green-500 p-6">
        <div class="flex items-start justify-between flex-wrap gap-4">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-green-50 text-green-600 flex items-center justify-center shrink-0">
                    <i class="fas fa-file-contract text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Surat Keputusan Pengangkatan Karyawan Tetap</h3>
                    <p class="text-sm text-gray-600 mt-0.5">
                        Selamat — masa probasi Anda dinyatakan <span class="font-semibold text-green-700">Lulus</span>.
                    </p>
                    <div class="flex flex-wrap gap-x-6 gap-y-1 mt-3 text-sm">
                        <div>
                            <span class="text-gray-500">Nomor</span>
                            <span class="font-semibold text-gray-900 ml-1"><?= htmlspecialchars(EvaluationDecisionModel::nomorSkLengkap($sk)) ?></span>
                        </div>
                        <div>
                            <span class="text-gray-500">Diangkat terhitung</span>
                            <span class="font-semibold text-gray-900 ml-1"><?= htmlspecialchars(SuratKeputusan::tanggalPanjang($sk['tanggal_diangkat'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <a href="/reports/sk/<?= (int) $employee['id'] ?>" target="_blank"
               class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition flex items-center gap-2">
                <i class="fas fa-download"></i> Lihat / Unduh SK
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- ============================================================
     1. PROGRES MASA PROBATION
     ============================================================ -->
<div class="bg-white rounded-xl shadow p-6 mb-6">
    <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
        <div>
            <h3 class="text-lg font-bold text-gray-900">Masa Probation Anda</h3>
            <p class="text-sm text-gray-500">
                <?= htmlspecialchars(RingkasanDashboard::tanggalPendek($employee['mulai_probation'] ?? null)) ?>
                &rarr;
                <?= htmlspecialchars(RingkasanDashboard::tanggalPendek($p['akhir'])) ?>
            </p>
        </div>
        <span class="px-3 py-1.5 rounded-full text-sm font-semibold <?= $statusKelas ?>">
            <i class="fas <?= $statusIkon ?> mr-1.5"></i><?= htmlspecialchars(RingkasanDashboard::labelStatus($r['status'])) ?>
        </span>
    </div>

    <?php if ($p['total'] === 0): ?>
        <p class="text-sm text-gray-400">Tanggal probation Anda belum lengkap di sistem. Hubungi HRD.</p>
    <?php else: ?>
        <div class="flex items-end justify-between mb-2">
            <div>
                <span class="text-4xl font-bold text-gray-900"><?= $p['terpakai'] ?></span>
                <span class="text-gray-500 text-sm">/ <?= $p['total'] ?> hari</span>
            </div>
            <div class="text-right">
                <?php if ($r['status'] !== 'pending'): ?>
                    <p class="text-sm font-semibold text-green-600">Masa probation selesai</p>
                <?php elseif ($p['lewat']): ?>
                    <p class="text-sm font-semibold text-amber-600">Melewati tanggal akhir — menunggu keputusan HRD</p>
                <?php else: ?>
                    <p class="text-sm text-gray-500">Sisa <span class="font-bold text-gray-900"><?= $p['sisa'] ?></span> hari</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full rounded-full <?= $r['status'] === 'pending' ? 'bg-blue-500' : 'bg-green-500' ?>"
                 style="width: <?= $p['persen'] ?>%"></div>
        </div>
        <p class="text-xs text-gray-400 mt-2"><?= $p['persen'] ?>% masa probation sudah berjalan</p>
    <?php endif; ?>
</div>

<!-- ============================================================
     2. LANGKAH — posisi Anda dalam proses
     ============================================================ -->
<div class="bg-white rounded-xl shadow p-6 mb-6">
    <h3 class="text-lg font-bold text-gray-900">Tahapan Proses</h3>
    <p class="text-sm text-gray-500 mb-6">Empat langkah yang dilalui setiap Team Member probation</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php foreach ($r['langkah'] as $i => $l): ?>
            <?php
            [$bulat, $garis, $judul, $isi] = match ($l['status']) {
                'selesai'  => ['bg-green-500 text-white', 'bg-green-500', 'text-gray-900', 'text-gray-500'],
                'aktif'    => ['bg-blue-600 text-white ring-4 ring-blue-100', 'bg-gray-200', 'text-blue-700', 'text-blue-600'],
                'lewat'    => ['bg-gray-200 text-gray-400', 'bg-gray-200', 'text-gray-400', 'text-gray-400'],
                default    => ['bg-gray-100 text-gray-400', 'bg-gray-200', 'text-gray-500', 'text-gray-400'],
            };
            ?>
            <div class="relative">
                <!-- garis penghubung, hanya di layar lebar -->
                <?php if ($i < count($r['langkah']) - 1): ?>
                    <div class="hidden lg:block absolute top-4 left-1/2 w-full h-0.5 <?= $garis ?>"></div>
                <?php endif; ?>
                <div class="relative flex lg:flex-col items-center lg:text-center gap-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0 <?= $bulat ?>">
                        <?= $l['status'] === 'selesai' ? '<i class="fas fa-check"></i>' : $i + 1 ?>
                    </div>
                    <div class="lg:mt-2 min-w-0">
                        <p class="text-sm font-semibold <?= $judul ?>"><?= htmlspecialchars($l['label']) ?></p>
                        <p class="text-xs <?= $isi ?> mt-0.5"><?= htmlspecialchars($l['detail']) ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ============================================================
     3. NILAI SAYA
     ============================================================ -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 bg-white rounded-xl shadow p-6">
        <div class="flex items-baseline justify-between gap-3 mb-5">
            <div>
                <h3 class="text-lg font-bold text-gray-900">Nilai Penilaian</h3>
                <p class="text-sm text-gray-500">Standar kelulusan rata-rata &ge; <?= SiklusProbation::NILAI_LULUS ?> dari skala 10</p>
            </div>
            <a href="/evaluations/my" class="text-blue-600 hover:text-blue-700 text-sm font-medium shrink-0">Lihat rincian →</a>
        </div>

        <?php if (empty($r['penilaian'])): ?>
            <div class="py-10 text-center text-gray-400">
                <i class="fas fa-hourglass-half text-3xl mb-3 block text-gray-300"></i>
                <p class="font-medium text-gray-600">Belum ada penilaian</p>
                <p class="text-sm mt-1">Hasil akan muncul di sini setelah Team Leader Anda menilai.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php for ($ke = 1; $ke <= SiklusProbation::MAKS_PENILAIAN; $ke++): ?>
                    <?php
                    $ev = null;
                    foreach ($r['penilaian'] as $x) {
                        if ((int) ($x['nomor_penilaian'] ?? 1) === $ke) {
                            $ev = $x;
                            break;
                        }
                    }
                    ?>
                    <?php if ($ev): ?>
                        <?php $band = SiklusProbation::bandNilai((float) $ev['nilai_total']); ?>
                        <div class="border rounded-xl p-5">
                            <div class="flex items-start justify-between gap-2 mb-3">
                                <p class="text-sm font-semibold text-gray-700">Penilaian ke-<?= $ke ?></p>
                                <?php if (empty($ev['dilihat_at'])): ?>
                                    <span class="px-2 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded-full text-xs font-medium">Baru</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-5xl font-bold <?= $band['teks'] ?>"><?= number_format((float) $ev['nilai_total'], 2) ?></p>
                            <p class="text-sm font-medium <?= $band['teks'] ?> mt-1"><?= htmlspecialchars($band['label']) ?></p>
                            <p class="text-xs text-gray-400 mt-2">Dinilai <?= htmlspecialchars(RingkasanDashboard::tanggalPendek($ev['tanggal_penilaian'])) ?></p>
                            <a href="/reports/evaluation/<?= (int) $ev['id'] ?>"
                               class="mt-4 inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold rounded-lg transition">
                                <i class="fas fa-eye"></i> Lihat lembar penilaian
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="border border-dashed rounded-xl p-5 flex flex-col items-center justify-center text-center min-h-[13rem]">
                            <i class="fas fa-clock text-2xl text-gray-300 mb-2"></i>
                            <p class="text-sm font-semibold text-gray-500">Penilaian ke-<?= $ke ?></p>
                            <p class="text-xs text-gray-400 mt-1">Belum dilakukan</p>
                        </div>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Rata-rata + info probation -->
    <div class="space-y-6">
        <?php if ($r['rata'] !== null): ?>
            <div class="bg-white rounded-xl shadow p-6 text-center">
                <p class="text-sm text-gray-500">Rata-rata nilai Anda</p>
                <p class="text-5xl font-bold <?= $r['band']['teks'] ?> my-2"><?= number_format((float) $r['rata'], 2) ?></p>
                <p class="text-sm font-medium <?= $r['band']['teks'] ?>"><?= htmlspecialchars($r['band']['label']) ?></p>
                <div class="mt-5">
                    <!-- Garis penanda standar kelulusan dipasang pada posisi
                         nilainya (6 dari 10 = 60%), bukan di tengah, supaya
                         terlihat apakah nilainya sudah melewatinya. -->
                    <div class="relative h-2.5 bg-gray-100 rounded-full">
                        <div class="absolute inset-y-0 left-0 <?= $r['band']['warna'] ?> rounded-full" style="width: <?= min(100, round($r['rata'] * 10)) ?>%"></div>
                        <div class="absolute inset-y-[-3px] w-0.5 bg-gray-500" style="left: <?= SiklusProbation::NILAI_LULUS * 10 ?>%"></div>
                    </div>
                    <div class="relative text-xs text-gray-400 mt-1 h-4">
                        <span class="absolute left-0">0</span>
                        <span class="absolute -translate-x-1/2" style="left: <?= SiklusProbation::NILAI_LULUS * 10 ?>%">standar <?= SiklusProbation::NILAI_LULUS ?></span>
                        <span class="absolute right-0">10</span>
                    </div>
                </div>
                <?php if ($r['rata'] < SiklusProbation::NILAI_LULUS): ?>
                    <p class="text-xs text-red-600 mt-4 font-medium">Masih di bawah standar kelulusan.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Data Anda</h3>
            <dl class="space-y-3 text-sm">
                <?php
                $baris = [
                    'NIK'             => $employee['nik'] ?? '-',
                    'Departemen'      => $employee['departemen'] ?? '-',
                    'Posisi'          => $employee['posisi'] ?? '-',
                    'Mulai Probation' => RingkasanDashboard::tanggalPendek($employee['mulai_probation'] ?? null),
                    'Akhir Probation' => RingkasanDashboard::tanggalPendek($p['akhir']),
                ];
                foreach ($baris as $label => $isi):
                    ?>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500 shrink-0"><?= $label ?></dt>
                        <dd class="font-semibold text-gray-900 text-right truncate"><?= htmlspecialchars((string) $isi) ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>
