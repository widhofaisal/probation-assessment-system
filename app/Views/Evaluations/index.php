<?php $this->extend('layouts/main'); $this->section('content'); ?>

<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Daftar Penilaian Probation</h2>
            <p class="text-gray-600 mt-1">Kelola semua penilaian karyawan probation</p>
        </div>
        <a href="/reports/export-csv" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium">
            <i class="fas fa-download mr-2"></i> Export CSV
        </a>
    </div>

    <!-- Evaluations Table -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b">
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">NIK</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Nama Karyawan</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Team Leader</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Tanggal Penilaian</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Nilai</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($evaluations)): ?>
                        <?php foreach ($evaluations as $eval): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900"><?= htmlspecialchars($eval['nik']) ?></td>
                                <td class="px-6 py-4 text-gray-700"><?= htmlspecialchars($eval['nama']) ?></td>
                                <td class="px-6 py-4 text-gray-700"><?= htmlspecialchars($eval['team_leader_nama']) ?></td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    <?= date('d/m/Y H:i', strtotime($eval['tanggal_penilaian'])) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-lg font-bold
                                        <?php
                                        if ($eval['nilai_total'] >= 8) echo 'text-green-600';
                                        elseif ($eval['nilai_total'] >= 6) echo 'text-blue-600';
                                        else echo 'text-red-600';
                                        ?>">
                                        <?= htmlspecialchars($eval['nilai_total']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium
                                        <?= $eval['status'] === 'submitted' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                                        <?= htmlspecialchars(ucfirst($eval['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <a href="/evaluations/<?= $eval['id'] ?>"
                                           class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                            <i class="fas fa-eye mr-1"></i> Lihat
                                        </a>
                                        <a href="/reports/pdf/<?= $eval['id'] ?>"
                                           class="text-red-600 hover:text-red-700 font-medium text-sm">
                                            <i class="fas fa-file-pdf mr-1"></i> PDF
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                <i class="fas fa-inbox text-3xl mb-2"></i>
                                <p>Tidak ada penilaian</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
        <div class="bg-white rounded-xl shadow p-4 border-t-4 border-blue-500">
            <p class="text-sm text-gray-600">Total Penilaian</p>
            <p class="text-3xl font-bold text-gray-900 mt-1"><?= count($evaluations) ?></p>
        </div>
        <div class="bg-white rounded-xl shadow p-4 border-t-4 border-green-500">
            <p class="text-sm text-gray-600">Submitted</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">
                <?= count(array_filter($evaluations, fn($e) => $e['status'] === 'submitted')) ?>
            </p>
        </div>
        <div class="bg-white rounded-xl shadow p-4 border-t-4 border-yellow-500">
            <p class="text-sm text-gray-600">Draft</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">
                <?= count(array_filter($evaluations, fn($e) => $e['status'] === 'draft')) ?>
            </p>
        </div>
        <div class="bg-white rounded-xl shadow p-4 border-t-4 border-purple-500">
            <p class="text-sm text-gray-600">Nilai Rata-rata</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">
                <?php
                if (!empty($evaluations)) {
                    $avg = array_sum(array_column($evaluations, 'nilai_total')) / count($evaluations);
                    echo number_format($avg, 2);
                } else {
                    echo '0';
                }
                ?>
            </p>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>
