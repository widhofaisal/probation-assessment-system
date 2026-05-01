<?php $this->extend('layouts/main'); $this->section('content'); ?>

<!-- Welcome Banner -->
<div class="mb-8 bg-gradient-to-br from-green-600 via-blue-600 to-blue-700 text-white rounded-2xl p-8 shadow-xl">
    <h1 class="text-4xl font-bold mb-2"><?= htmlspecialchars($user['nama']) ?></h1>
    <p class="text-white/80">Probationary Employee Dashboard</p>
</div>

<!-- Employee Status Card -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Informasi Probation</h3>
        <div class="space-y-3">
            <div>
                <p class="text-sm text-gray-600">NIK</p>
                <p class="font-semibold text-gray-900"><?= htmlspecialchars($employee['nik']) ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Departemen</p>
                <p class="font-semibold text-gray-900"><?= htmlspecialchars($employee['departemen']) ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Posisi</p>
                <p class="font-semibold text-gray-900"><?= htmlspecialchars($employee['posisi']) ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Periode Probation</h3>
        <div class="space-y-3">
            <div>
                <p class="text-sm text-gray-600">Mulai Probation</p>
                <p class="font-semibold text-gray-900"><?= date('d/m/Y', strtotime($employee['mulai_probation'])) ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Akhir Probation</p>
                <p class="font-semibold text-gray-900"><?= date('d/m/Y', strtotime($employee['akhir_probation'])) ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Status</p>
                <span class="inline-block px-3 py-1 rounded-full text-sm font-medium
                    <?php
                    if ($employee['status'] === 'pending') echo 'bg-yellow-100 text-yellow-700';
                    elseif ($employee['status'] === 'lulus') echo 'bg-green-100 text-green-700';
                    elseif ($employee['status'] === 'tidak-lulus') echo 'bg-red-100 text-red-700';
                    else echo 'bg-orange-100 text-orange-700';
                    ?>">
                    <?= htmlspecialchars(ucfirst(str_replace('-', ' ', $employee['status']))) ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Evaluations History -->
<div class="bg-white rounded-xl shadow">
    <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-bold text-gray-900">Hasil Penilaian</h3>
    </div>
    <?php if (!empty($evaluations)): ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="px-6 py-3 text-left text-sm font-semibold">Tanggal Penilaian</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Nilai</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Team Leader</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($evaluations as $eval): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-6 py-4"><?= date('d/m/Y H:i', strtotime($eval['tanggal_penilaian'])) ?></td>
                            <td class="px-6 py-4 font-semibold text-lg"><?= htmlspecialchars($eval['nilai_total']) ?></td>
                            <td class="px-6 py-4"><?= htmlspecialchars($eval['team_leader_nama']) ?></td>
                            <td class="px-6 py-4">
                                <a href="/reports/evaluation/<?= $eval['id'] ?>" class="text-blue-600 hover:text-blue-700 font-medium">Lihat Detail</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="p-6 text-center text-gray-500">
            <p>Belum ada penilaian untuk Anda.</p>
            <p class="text-sm mt-2">Penilaian akan muncul di sini setelah Team Leader Anda menilai.</p>
        </div>
    <?php endif; ?>
</div>

<?php $this->endSection(); ?>
