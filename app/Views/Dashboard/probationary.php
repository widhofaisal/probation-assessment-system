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
                        <span class="text-sm font-medium"><?= htmlspecialchars($user['posisi'] ?? '') ?></span>
                    </div>
                    <div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm px-3 py-1.5 rounded-lg">
                        <i class="fas fa-calendar"></i>
                        <span class="text-sm font-medium"><?= (new \DateTime('now', new \DateTimeZone('Asia/Jakarta')))->format('d/m/Y') ?></span>
                    </div>
                </div>
                <p class="text-white/80 text-base max-w-2xl leading-relaxed">
                    Pantau perkembangan masa probasi Anda di sini.
                </p>
            </div>
            <div class="hidden lg:block">
                <div class="w-32 h-32 rounded-2xl bg-white/10 backdrop-blur-md border-4 border-white/20 flex items-center justify-center shadow-2xl">
                    <i class="fas fa-user text-white text-5xl"></i>
                </div>
            </div>
        </div>
        <div class="absolute top-0 right-0 w-80 h-80 bg-white/5 rounded-full blur-3xl -mr-40 -mt-40"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-white/5 rounded-full blur-3xl -ml-32 -mb-32"></div>
    </div>
</div>

<!-- Dashboard heading -->
<h2 class="text-2xl font-bold text-gray-900 mb-2">Dashboard Overview</h2>
<p class="text-gray-600 mb-8">Informasi masa probasi Anda</p>

<?php if (!empty($sk)): ?>
    <!-- Surat Keputusan — terbit sendiri setelah HRD memutuskan Lulus -->
    <div class="mb-8 bg-white rounded-xl shadow border-l-4 border-green-500 p-6">
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
                            <span class="font-semibold text-gray-900 ml-1"><?= htmlspecialchars(\App\Models\EvaluationDecisionModel::nomorSkLengkap($sk)) ?></span>
                        </div>
                        <div>
                            <span class="text-gray-500">Diangkat terhitung</span>
                            <span class="font-semibold text-gray-900 ml-1"><?= htmlspecialchars(\App\Libraries\SuratKeputusan::tanggalPanjang($sk['tanggal_diangkat'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <a href="/reports/sk/<?= (int)$employee['id'] ?>" target="_blank"
               class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition flex items-center gap-2">
                <i class="fas fa-download"></i> Lihat / Unduh SK
            </a>
        </div>
    </div>
<?php endif; ?>

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
                        <th class="px-6 py-3 text-left text-sm font-semibold">Tanda Terima</th>
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
                                <?php if (empty($eval['dilihat_at'])): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 text-blue-700 border border-blue-200 rounded-full text-xs font-medium">
                                        <i class="fas fa-circle text-[8px]"></i>Hasil baru — belum Anda buka
                                    </span>
                                <?php else: ?>
                                    <?= ack_badge_member($eval) ?>
                                <?php endif; ?>
                            </td>
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
