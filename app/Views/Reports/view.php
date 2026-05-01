<?php $this->extend('layouts/main'); $this->section('content'); ?>

<div class="max-w-5xl mx-auto">
    <!-- Report Header -->
    <div class="mb-6 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl p-6 shadow-lg">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold mb-1">Laporan Penilaian Probation</h1>
                <p class="text-blue-100">PT Sumber Masanda Jaya</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-blue-100 mb-1">Tanggal Laporan</p>
                <p class="text-2xl font-bold"><?= date('d/m/Y', strtotime($evaluation['tanggal_penilaian'])) ?></p>
            </div>
        </div>
    </div>

    <!-- Employee & Evaluation Info -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="font-bold text-gray-900 mb-4">Informasi Karyawan</h3>
            <div class="space-y-3">
                <div>
                    <p class="text-xs text-gray-600">NIK</p>
                    <p class="font-semibold text-gray-900"><?= htmlspecialchars($evaluation['nik']) ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-600">Nama</p>
                    <p class="font-semibold text-gray-900"><?= htmlspecialchars($evaluation['nama']) ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-600">Departemen</p>
                    <p class="font-semibold text-gray-900"><?= htmlspecialchars($evaluation['departemen']) ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-600">Posisi</p>
                    <p class="font-semibold text-gray-900"><?= htmlspecialchars($evaluation['posisi']) ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="font-bold text-gray-900 mb-4">Informasi Penilaian</h3>
            <div class="space-y-3">
                <div>
                    <p class="text-xs text-gray-600">Team Leader</p>
                    <p class="font-semibold text-gray-900"><?= htmlspecialchars($evaluation['team_leader_nama']) ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-600">Tanggal Penilaian</p>
                    <p class="font-semibold text-gray-900"><?= date('d/m/Y H:i', strtotime($evaluation['tanggal_penilaian'])) ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-600">Status</p>
                    <span class="inline-block px-2 py-1 rounded text-xs font-semibold
                        <?= $evaluation['status'] === 'submitted' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                        <?= htmlspecialchars(ucfirst($evaluation['status'])) ?>
                    </span>
                </div>
                <div class="pt-2 border-t">
                    <p class="text-xs text-gray-600">Nilai Total</p>
                    <p class="text-3xl font-bold text-blue-600"><?= htmlspecialchars($evaluation['nilai_total']) ?>/10</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Scores -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <?php foreach ($categoryScores as $cat): ?>
            <div class="bg-white rounded-xl shadow p-4 border-t-4 border-blue-500">
                <p class="text-sm text-gray-600 mb-2"><?= htmlspecialchars($cat['kategori']) ?></p>
                <p class="text-3xl font-bold text-blue-600"><?= number_format($cat['average'], 2) ?></p>
                <p class="text-xs text-gray-600 mt-2">
                    <?php
                    $score = $cat['average'];
                    if ($score >= 8) echo 'Sangat Baik ✓';
                    elseif ($score >= 6) echo 'Baik ✓';
                    else echo 'Perlu Perbaikan';
                    ?>
                </p>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Detailed Evaluation Table -->
    <div class="bg-white rounded-xl shadow mb-6 overflow-hidden">
        <div class="px-6 py-4 border-b bg-gray-50">
            <h3 class="text-lg font-bold text-gray-900">Detail Penilaian</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Kategori</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Aspek</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900">Nilai</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Alasan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($details as $detail): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                <?= htmlspecialchars($detail['kategori']) ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <?= htmlspecialchars($detail['aspek']) ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-block px-3 py-1 rounded-full font-bold text-sm
                                    <?= $detail['nilai'] >= 8 ? 'bg-green-100 text-green-700' : ($detail['nilai'] >= 6 ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700') ?>">
                                    <?= htmlspecialchars($detail['nilai']) ?>/10
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <?= htmlspecialchars($detail['alasan'] ?? '-') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Notes -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow p-6">
            <h4 class="font-bold text-gray-900 mb-3">Catatan Team Leader</h4>
            <p class="text-gray-700 whitespace-pre-line text-sm">
                <?= htmlspecialchars($evaluation['catatan_team_leader'] ?? '(Tidak ada catatan)') ?>
            </p>
        </div>
        <div class="bg-white rounded-xl shadow p-6">
            <h4 class="font-bold text-gray-900 mb-3">Catatan HRD</h4>
            <p class="text-gray-700 whitespace-pre-line text-sm">
                <?= htmlspecialchars($evaluation['catatan_hrd'] ?? '(Belum ada catatan HRD)') ?>
            </p>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-3 mb-6">
        <a href="/reports/pdf/<?= $evaluation['id'] ?>" target="_blank"
           class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium">
            <i class="fas fa-file-pdf mr-2"></i> Download PDF
        </a>
        <a href="/evaluations" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>
</div>

<?php $this->endSection(); ?>
