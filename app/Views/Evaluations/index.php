<?php $this->extend('layouts/main'); $this->section('content'); ?>

<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Daftar Penilaian Probation</h2>
            <p class="text-gray-600 mt-1">Kelola semua penilaian Team Member probation</p>
        </div>
        <a href="/reports/export-csv" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium">
            <i class="fas fa-download mr-2"></i> Export CSV
        </a>
    </div>

    <!-- Evaluations Table (grouped by employee) -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">NIK</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Nama Team Member</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Team Leader</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Penilaian 1</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Penilaian 2</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($evalsByEmployee)): ?>
                        <?php foreach ($evalsByEmployee as $grp): ?>
                            <?php $e1 = $grp['eval1']; $e2 = $grp['eval2']; ?>
                            <tr class="border-b hover:bg-gray-50 transition">
                                <td class="px-5 py-4 font-medium text-gray-900 text-sm"><?= htmlspecialchars($grp['nik']) ?></td>
                                <td class="px-5 py-4 text-gray-800 text-sm"><?= htmlspecialchars($grp['nama']) ?></td>
                                <td class="px-5 py-4 text-gray-500 text-sm"><?= htmlspecialchars($grp['team_leader_nama']) ?></td>

                                <!-- Penilaian 1 -->
                                <td class="px-5 py-4">
                                    <?php if ($e1): ?>
                                        <div class="flex flex-col gap-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-base font-bold <?= $e1['nilai_total'] >= 8 ? 'text-green-600' : ($e1['nilai_total'] >= 6 ? 'text-blue-600' : 'text-red-600') ?>">
                                                    <?= htmlspecialchars($e1['nilai_total']) ?>
                                                </span>
                                                <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $e1['status'] === 'submitted' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                                                    <?= ucfirst($e1['status']) ?>
                                                </span>
                                            </div>
                                            <p class="text-xs text-gray-400"><?= date('d/m/Y', strtotime($e1['tanggal_penilaian'])) ?></p>
                                            <div class="flex gap-1 flex-wrap mt-0.5">
                                                <a href="/evaluations/<?= $e1['id'] ?>"
                                                   class="px-2 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold rounded-lg transition">
                                                    <i class="fas fa-eye mr-1"></i>Detail
                                                </a>
                                                <button onclick="pdfDownload('/reports/pdf/<?= $e1['id'] ?>')"
                                                   class="px-2 py-1 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-semibold rounded-lg transition cursor-pointer">
                                                    <i class="fas fa-file-pdf mr-1"></i>PDF
                                                </button>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-gray-300 text-xs">Belum ada</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Penilaian 2 -->
                                <td class="px-5 py-4">
                                    <?php if ($e2): ?>
                                        <div class="flex flex-col gap-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-base font-bold <?= $e2['nilai_total'] >= 8 ? 'text-green-600' : ($e2['nilai_total'] >= 6 ? 'text-blue-600' : 'text-red-600') ?>">
                                                    <?= htmlspecialchars($e2['nilai_total']) ?>
                                                </span>
                                                <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $e2['status'] === 'submitted' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                                                    <?= ucfirst($e2['status']) ?>
                                                </span>
                                            </div>
                                            <p class="text-xs text-gray-400"><?= date('d/m/Y', strtotime($e2['tanggal_penilaian'])) ?></p>
                                            <div class="flex gap-1 flex-wrap mt-0.5">
                                                <a href="/evaluations/<?= $e2['id'] ?>"
                                                   class="px-2 py-1 bg-purple-50 hover:bg-purple-100 text-purple-700 text-xs font-semibold rounded-lg transition">
                                                    <i class="fas fa-eye mr-1"></i>Detail
                                                </a>
                                                <button onclick="pdfDownload('/reports/pdf/<?= $e2['id'] ?>')"
                                                   class="px-2 py-1 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-semibold rounded-lg transition cursor-pointer">
                                                    <i class="fas fa-file-pdf mr-1"></i>PDF
                                                </button>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-gray-300 text-xs">Belum ada</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Aksi (PDF Semua) -->
                                <td class="px-5 py-4">
                                    <?php if ($e1 && $e2): ?>
                                        <button onclick="pdfDownload('/reports/pdf-all/<?= $grp['employee_id'] ?>')"
                                           class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-lg transition flex items-center gap-1.5 whitespace-nowrap cursor-pointer">
                                            <i class="fas fa-file-pdf text-xs"></i> PDF Semua
                                        </button>
                                    <?php elseif ($e1): ?>
                                        <span class="text-gray-400 text-xs">Penilaian 2<br>belum ada</span>
                                    <?php else: ?>
                                        <span class="text-gray-300 text-xs">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-3xl mb-2 block text-gray-300"></i>
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
        <div class="bg-white rounded-xl shadow p-4 border-t-4 border-purple-500">
            <p class="text-sm text-gray-600">Total Team Member</p>
            <p class="text-3xl font-bold text-gray-900 mt-1"><?= count($evalsByEmployee) ?></p>
        </div>
        <div class="bg-white rounded-xl shadow p-4 border-t-4 border-green-500">
            <p class="text-sm text-gray-600">Submitted</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">
                <?= count(array_filter($evaluations, fn($e) => $e['status'] === 'submitted')) ?>
            </p>
        </div>
        <div class="bg-white rounded-xl shadow p-4 border-t-4 border-orange-500">
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
