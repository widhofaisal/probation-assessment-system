<?php $this->extend('layouts/main'); $this->section('content'); ?>

<?php
$backUrl = match(session()->get('role')) {
    'team-leader'           => '/team',
    'probationary-employee' => '/evaluations/my',
    default                 => '/evaluations',
};
?>

<div class="max-w-5xl mx-auto">
    <!-- Header + Action Buttons -->
    <div class="mb-6 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl p-6 shadow-lg">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h1 class="text-3xl font-bold mb-2">Hasil Penilaian Probation
                    <span class="text-xl font-normal opacity-80">(Ke-<?= $evaluation['nomor_penilaian'] ?? 1 ?>)</span>
                </h1>
                <p class="text-blue-100">Tanggal: <?= date('d/m/Y H:i', strtotime($evaluation['tanggal_penilaian'])) ?></p>
            </div>
            <div class="text-right">
                <p class="text-4xl font-bold"><?= htmlspecialchars($evaluation['nilai_total']) ?></p>
                <p class="text-blue-100">dari 10</p>
            </div>
        </div>
        <!-- Buttons at top -->
        <div class="flex gap-3 flex-wrap">
            <button onclick="pdfDownload('/reports/pdf/<?= $evaluation['id'] ?>')"
               class="px-5 py-2 bg-white text-red-600 hover:bg-red-50 rounded-lg font-semibold text-sm flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-file-pdf"></i> Download PDF
            </button>
            <a href="<?= $backUrl ?>"
               class="px-5 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg font-semibold text-sm flex items-center gap-2 transition">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Employee Info -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow p-4">
            <p class="text-sm text-gray-600">NIK</p>
            <p class="font-bold text-gray-900"><?= htmlspecialchars($evaluation['nik']) ?></p>
        </div>
        <div class="bg-white rounded-xl shadow p-4">
            <p class="text-sm text-gray-600">Nama Team Member</p>
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
</div>

<?php $this->endSection(); ?>
