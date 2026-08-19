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
                        <span class="text-sm font-medium"><?= (new \DateTime('now', new \DateTimeZone('Asia/Jakarta')))->format('d/m/Y') ?></span>
                    </div>
                </div>
                <p class="text-white/80 text-base max-w-2xl leading-relaxed">
                    Kelola seluruh data Team Member probation dengan mudah dan efisien.
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
<p class="text-gray-600 mb-8">Ringkasan data Team Member probation</p>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
    <!-- Total Employees -->
    <div class="bg-white rounded-xl shadow p-6 border-t-4 border-blue-500">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-gray-600 text-sm font-medium">Total Team Member</p>
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

    <!-- Belum dilihat Team Member -->
    <a href="/evaluations" class="bg-white rounded-xl shadow p-6 border-t-4 border-gray-400 hover:shadow-lg transition">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-gray-600 text-sm font-medium">Belum Dilihat Member</p>
                <p class="text-3xl font-bold text-gray-900 mt-2"><?= $stats['belum_dilihat'] ?></p>
            </div>
            <div class="p-3 bg-gray-100 rounded-lg">
                <i class="fas fa-eye-slash text-gray-500 text-2xl"></i>
            </div>
        </div>
    </a>
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
                <h4 class="font-semibold text-gray-900">Tambah Team Member</h4>
                <p class="text-sm text-gray-600">Tambahkan Team Member baru ke sistem</p>
            </div>
        </a>

        <a href="/employees" class="bg-white hover:shadow-lg rounded-xl shadow p-6 transition flex items-center gap-4">
            <div class="p-4 bg-green-100 rounded-lg">
                <i class="fas fa-list text-green-600 text-2xl"></i>
            </div>
            <div>
                <h4 class="font-semibold text-gray-900">Kelola Data Team Member</h4>
                <p class="text-sm text-gray-600">Lihat dan edit data Team Member</p>
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

<!-- Recent Evaluations grouped by employee -->
<div class="bg-white rounded-xl shadow">
    <div class="px-6 py-4 border-b flex justify-between items-center">
        <h3 class="text-lg font-bold text-gray-900">Penilaian Terbaru</h3>
        <a href="/evaluations" class="text-blue-600 hover:text-blue-700 text-sm font-medium">Lihat Semua →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b bg-gray-50">
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">NIK</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Nama Team Member</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Team Leader</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Penilaian 1</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Penilaian 2</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($evalByEmployee)): ?>
                    <?php foreach ($evalByEmployee as $grp): ?>
                        <?php $e1 = $grp['eval1']; $e2 = $grp['eval2']; ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900"><?= htmlspecialchars($grp['nik']) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-700"><?= htmlspecialchars($grp['nama']) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?= htmlspecialchars($grp['team_leader_nama']) ?></td>
                            <!-- Penilaian 1 -->
                            <td class="px-6 py-4">
                                <?php if ($e1): ?>
                                    <div class="flex flex-col gap-1">
                                        <span class="font-bold <?= $e1['nilai_total'] >= 8 ? 'text-green-600' : ($e1['nilai_total'] >= 6 ? 'text-blue-600' : 'text-red-600') ?>"><?= $e1['nilai_total'] ?></span>
                                        <div><?= ack_badge($e1) ?></div>
                                        <div class="flex gap-1">
                                            <a href="/evaluations/<?= $e1['id'] ?>" class="px-2 py-0.5 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs rounded-lg">Detail</a>
                                            <button onclick="pdfDownload('/reports/pdf/<?= $e1['id'] ?>')" class="px-2 py-0.5 bg-red-50 hover:bg-red-100 text-red-700 text-xs rounded-lg cursor-pointer">PDF</button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-300 text-xs">-</span>
                                <?php endif; ?>
                            </td>
                            <!-- Penilaian 2 -->
                            <td class="px-6 py-4">
                                <?php if ($e2): ?>
                                    <div class="flex flex-col gap-1">
                                        <span class="font-bold <?= $e2['nilai_total'] >= 8 ? 'text-green-600' : ($e2['nilai_total'] >= 6 ? 'text-blue-600' : 'text-red-600') ?>"><?= $e2['nilai_total'] ?></span>
                                        <div><?= ack_badge($e2) ?></div>
                                        <div class="flex gap-1">
                                            <a href="/evaluations/<?= $e2['id'] ?>" class="px-2 py-0.5 bg-purple-50 hover:bg-purple-100 text-purple-700 text-xs rounded-lg">Detail</a>
                                            <button onclick="pdfDownload('/reports/pdf/<?= $e2['id'] ?>')" class="px-2 py-0.5 bg-red-50 hover:bg-red-100 text-red-700 text-xs rounded-lg cursor-pointer">PDF</button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-300 text-xs">Belum ada</span>
                                <?php endif; ?>
                            </td>
                            <!-- Aksi -->
                            <td class="px-6 py-4">
                                <?php if ($e1 && $e2): ?>
                                    <button onclick="pdfDownload('/reports/pdf-all/<?= $grp['employee_id'] ?>')"
                                       class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-lg transition flex items-center gap-1.5 whitespace-nowrap cursor-pointer">
                                        <i class="fas fa-file-pdf text-xs"></i> PDF Semua
                                    </button>
                                <?php elseif ($e1): ?>
                                    <span class="text-gray-400 text-xs">Penilaian 2 belum ada</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-400">
                            <i class="fas fa-inbox text-3xl mb-2 block text-gray-300"></i>
                            Belum ada penilaian
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $this->endSection(); ?>
