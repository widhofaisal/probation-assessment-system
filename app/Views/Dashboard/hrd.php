<?php $this->extend('layouts/main'); $this->section('content'); ?>

<!-- Welcome Banner -->
<div class="mb-8 relative overflow-hidden rounded-2xl">
    <div class="p-8 bg-gradient-to-br from-green-600 via-blue-600 to-blue-700 text-white shadow-xl rounded-2xl">
        <div class="relative z-10 flex items-start justify-between flex-wrap gap-6">
            <div class="flex-1">
                <div class="inline-block px-4 py-1.5 bg-white/20 backdrop-blur-sm rounded-full mb-4">
                    <p class="text-sm font-medium text-white/90"><?= htmlspecialchars($greeting) ?></p>
                </div>
                <h1 class="text-4xl lg:text-5xl font-bold mb-3 text-white"><?= htmlspecialchars($user['nama']) ?></h1>
                <div class="flex flex-wrap items-center gap-3 text-white/90 mb-4">
                    <div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm px-3 py-1.5 rounded-lg">
                        <i class="fas fa-id-card"></i>
                        <span class="text-sm font-medium">NIK: <?= htmlspecialchars($user['nik']) ?></span>
                    </div>
                    <div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm px-3 py-1.5 rounded-lg">
                        <i class="fas fa-briefcase"></i>
                        <span class="text-sm font-medium"><?= htmlspecialchars($user['posisi']) ?></span>
                    </div>
                    <div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm px-3 py-1.5 rounded-lg">
                        <i class="fas fa-calendar"></i>
                        <span class="text-sm font-medium"><?= date('d/m/Y') ?></span>
                    </div>
                </div>
                <p class="text-white/80 text-base max-w-2xl leading-relaxed">
                    Selamat datang di Sistem Penilaian Probation PT Sumber Masanda Jaya. Kelola seluruh data karyawan probation dengan mudah dan efisien.
                </p>
            </div>
            <div class="hidden lg:block">
                <div class="relative">
                    <div class="w-32 h-32 rounded-2xl bg-white/10 backdrop-blur-md border-4 border-white/20 flex items-center justify-center shadow-2xl">
                        <i class="fas fa-users text-white text-5xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="absolute top-0 right-0 w-80 h-80 bg-white/5 rounded-full blur-3xl -mr-40 -mt-40"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-white/5 rounded-full blur-3xl -ml-32 -mb-32"></div>
    </div>
</div>

<!-- Statistics Cards -->
<h2 class="text-2xl font-bold text-gray-900 mb-2">Dashboard Overview</h2>
<p class="text-gray-600 mb-8">Ringkasan data karyawan probation</p>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Employees -->
    <div class="bg-white rounded-xl shadow p-6 border-t-4 border-blue-500">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-gray-600 text-sm font-medium">Total Karyawan</p>
                <p class="text-3xl font-bold text-gray-900 mt-2"><?= $stats['total_employees'] ?></p>
            </div>
            <div class="p-3 bg-blue-100 rounded-lg">
                <i class="fas fa-users text-blue-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Pending -->
    <div class="bg-white rounded-xl shadow p-6 border-t-4 border-yellow-500">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-gray-600 text-sm font-medium">Pending</p>
                <p class="text-3xl font-bold text-gray-900 mt-2"><?= $stats['pending'] ?></p>
            </div>
            <div class="p-3 bg-yellow-100 rounded-lg">
                <i class="fas fa-hourglass-half text-yellow-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Lulus -->
    <div class="bg-white rounded-xl shadow p-6 border-t-4 border-green-500">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-gray-600 text-sm font-medium">Lulus</p>
                <p class="text-3xl font-bold text-gray-900 mt-2"><?= $stats['lulus'] ?></p>
            </div>
            <div class="p-3 bg-green-100 rounded-lg">
                <i class="fas fa-check-circle text-green-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Tidak Lulus -->
    <div class="bg-white rounded-xl shadow p-6 border-t-4 border-red-500">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-gray-600 text-sm font-medium">Tidak Lulus</p>
                <p class="text-3xl font-bold text-gray-900 mt-2"><?= $stats['tidak_lulus'] ?></p>
            </div>
            <div class="p-3 bg-red-100 rounded-lg">
                <i class="fas fa-times-circle text-red-600 text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="mb-8">
    <h3 class="text-lg font-bold text-gray-900 mb-4">Tindakan Cepat</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="/employees" class="bg-white hover:shadow-lg rounded-xl shadow p-6 transition flex items-center gap-4">
            <div class="p-4 bg-blue-100 rounded-lg">
                <i class="fas fa-plus text-blue-600 text-2xl"></i>
            </div>
            <div>
                <h4 class="font-semibold text-gray-900">Tambah Karyawan</h4>
                <p class="text-sm text-gray-600">Tambahkan karyawan baru ke sistem</p>
            </div>
        </a>

        <a href="/employees" class="bg-white hover:shadow-lg rounded-xl shadow p-6 transition flex items-center gap-4">
            <div class="p-4 bg-green-100 rounded-lg">
                <i class="fas fa-list text-green-600 text-2xl"></i>
            </div>
            <div>
                <h4 class="font-semibold text-gray-900">Kelola Data</h4>
                <p class="text-sm text-gray-600">Lihat dan edit data karyawan</p>
            </div>
        </a>

        <a href="/evaluations" class="bg-white hover:shadow-lg rounded-xl shadow p-6 transition flex items-center gap-4">
            <div class="p-4 bg-purple-100 rounded-lg">
                <i class="fas fa-file-alt text-purple-600 text-2xl"></i>
            </div>
            <div>
                <h4 class="font-semibold text-gray-900">Lihat Penilaian</h4>
                <p class="text-sm text-gray-600">Review semua penilaian karyawan</p>
            </div>
        </a>
    </div>
</div>

<!-- Recent Evaluations -->
<div class="bg-white rounded-xl shadow">
    <div class="px-6 py-4 border-b flex justify-between items-center">
        <h3 class="text-lg font-bold text-gray-900">Penilaian Terbaru</h3>
        <a href="/evaluations" class="text-blue-600 hover:text-blue-700 text-sm font-medium">Lihat Semua →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b">
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">NIK</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Nama Karyawan</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Team Leader</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Nilai</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentEvaluations as $eval): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900"><?= htmlspecialchars($eval['nik']) ?></td>
                        <td class="px-6 py-4 text-sm text-gray-700"><?= htmlspecialchars($eval['nama']) ?></td>
                        <td class="px-6 py-4 text-sm text-gray-700"><?= htmlspecialchars($eval['team_leader_nama']) ?></td>
                        <td class="px-6 py-4 text-sm font-semibold text-gray-900"><?= htmlspecialchars($eval['nilai_total']) ?></td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium <?= $eval['status'] === 'submitted' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                                <?= htmlspecialchars(ucfirst(str_replace('-', ' ', $eval['status']))) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <a href="/evaluations/<?= $eval['id'] ?>" class="text-blue-600 hover:text-blue-700 font-medium">Lihat</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $this->endSection(); ?>
