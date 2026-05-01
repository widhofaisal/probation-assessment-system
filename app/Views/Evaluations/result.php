<?php $this->extend('layouts/main'); $this->section('content'); ?>

<div class="max-w-5xl mx-auto">
    <!-- Header -->
    <div class="mb-6 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl p-6 shadow-lg">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold mb-2">Hasil Penilaian Probation</h1>
                <p class="text-blue-100">Tanggal: <?= date('d/m/Y H:i', strtotime($evaluation['tanggal_penilaian'])) ?></p>
            </div>
            <div class="text-right">
                <p class="text-4xl font-bold"><?= htmlspecialchars($evaluation['nilai_total']) ?></p>
                <p class="text-blue-100">dari 10</p>
            </div>
        </div>
    </div>

    <!-- Employee Info -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow p-4">
            <p class="text-sm text-gray-600">NIK</p>
            <p class="font-bold text-gray-900"><?= htmlspecialchars($evaluation['nik']) ?></p>
        </div>
        <div class="bg-white rounded-xl shadow p-4">
            <p class="text-sm text-gray-600">Nama Karyawan</p>
            <p class="font-bold text-gray-900"><?= htmlspecialchars($evaluation['nama']) ?></p>
        </div>
        <div class="bg-white rounded-xl shadow p-4">
            <p class="text-sm text-gray-600">Team Leader</p>
            <p class="font-bold text-gray-900"><?= htmlspecialchars($evaluation['team_leader_nama']) ?></p>
        </div>
    </div>

    <!-- Category Scores -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <?php foreach ($categoryScores as $cat): ?>
            <div class="bg-white rounded-xl shadow p-6 border-t-4 border-blue-500">
                <p class="text-sm text-gray-600 mb-2"><?= htmlspecialchars($cat['kategori']) ?></p>
                <p class="text-3xl font-bold text-blue-600"><?= number_format($cat['average'], 2) ?></p>
                <div class="mt-2 text-xs">
                    <?php
                    $score = $cat['average'];
                    if ($score >= 8) echo '<span class="text-green-600">Sangat Baik ✓</span>';
                    elseif ($score >= 6) echo '<span class="text-blue-600">Baik ✓</span>';
                    else echo '<span class="text-red-600">Perlu Perbaikan</span>';
                    ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Detailed Scores -->
    <div class="bg-white rounded-xl shadow mb-6 overflow-hidden">
        <div class="px-6 py-4 border-b bg-gray-50">
            <h3 class="text-lg font-bold text-gray-900">Detail Penilaian per Aspek</h3>
        </div>
        <div class="divide-y">
            <?php
            $currentCategory = '';
            foreach ($details as $detail):
            ?>
                <?php if ($currentCategory !== $detail['kategori']): ?>
                    <?php if ($currentCategory !== ''): ?>
                        </div>
                    <?php endif; ?>
                    <div class="px-6 py-4 bg-blue-50 font-bold text-gray-900 flex items-center">
                        <i class="fas fa-folder text-blue-600 mr-2"></i>
                        <?= htmlspecialchars($detail['kategori']) ?>
                    </div>
                    <div class="px-6">
                    <?php $currentCategory = $detail['kategori']; ?>
                <?php endif; ?>

                <div class="py-4 border-b last:border-0">
                    <div class="flex justify-between items-start mb-2">
                        <p class="font-medium text-gray-900"><?= htmlspecialchars($detail['aspek']) ?></p>
                        <span class="px-3 py-1 rounded-full font-bold
                            <?= $detail['nilai'] >= 8 ? 'bg-green-100 text-green-700' : ($detail['nilai'] >= 6 ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700') ?>">
                            <?= htmlspecialchars($detail['nilai']) ?>/10
                        </span>
                    </div>
                    <?php if ($detail['alasan']): ?>
                        <p class="text-sm text-gray-600">
                            <strong>Alasan:</strong> <?= htmlspecialchars($detail['alasan']) ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Notes Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Team Leader Notes -->
        <div class="bg-white rounded-xl shadow p-6">
            <h4 class="font-bold text-gray-900 mb-3 flex items-center">
                <i class="fas fa-user-tie text-blue-600 mr-2"></i>
                Catatan Team Leader
            </h4>
            <p class="text-gray-700 whitespace-pre-line">
                <?= htmlspecialchars($evaluation['catatan_team_leader'] ?? '-') ?>
            </p>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-3 mb-6">
        <a href="/reports/pdf/<?= $evaluation['id'] ?>" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium">
            <i class="fas fa-file-pdf mr-2"></i> Download PDF
        </a>
        <a href="/dashboard/hrd" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>

    <!-- Recommendation -->
    <div class="bg-white rounded-xl shadow p-6 border-l-4 border-blue-600">
        <h4 class="font-bold text-gray-900 mb-2">Rekomendasi</h4>
        <?php
        $score = $evaluation['nilai_total'];
        if ($score >= 7):
        ?>
            <div class="flex items-center text-green-700">
                <i class="fas fa-check-circle text-2xl mr-3"></i>
                <div>
                    <p class="font-semibold">Lulus Probation</p>
                    <p class="text-sm">Karyawan menunjukkan performa yang memuaskan dan dapat diangkat menjadi karyawan tetap.</p>
                </div>
            </div>
        <?php elseif ($score >= 6): ?>
            <div class="flex items-center text-yellow-700">
                <i class="fas fa-exclamation-circle text-2xl mr-3"></i>
                <div>
                    <p class="font-semibold">Probation Lanjutan</p>
                    <p class="text-sm">Karyawan perlu perbaikan dalam beberapa aspek sebelum keputusan final.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="flex items-center text-red-700">
                <i class="fas fa-times-circle text-2xl mr-3"></i>
                <div>
                    <p class="font-semibold">Tidak Lulus Probation</p>
                    <p class="text-sm">Karyawan tidak memenuhi standar minimum yang diperlukan.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php $this->endSection(); ?>
