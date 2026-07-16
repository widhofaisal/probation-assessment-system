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
                        <i class="fas fa-users"></i>
                        <span class="text-sm font-medium">Team Leader · <?= htmlspecialchars($user['departemen'] ?? '') ?></span>
                    </div>
                    <div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm px-3 py-1.5 rounded-lg">
                        <i class="fas fa-calendar"></i>
                        <span class="text-sm font-medium"><?= (new \DateTime('now', new \DateTimeZone('Asia/Jakarta')))->format('d/m/Y') ?></span>
                    </div>
                </div>
                <p class="text-white/80 text-base max-w-2xl leading-relaxed">
                    Nilai dan pantau perkembangan karyawan probation tim Anda.
                </p>
            </div>
            <div class="hidden lg:flex flex-col items-end gap-3">
                <div class="w-32 h-32 rounded-2xl bg-white/10 backdrop-blur-md border-4 border-white/20 flex items-center justify-center shadow-2xl">
                    <i class="fas fa-user-tie text-white text-5xl"></i>
                </div>
                <a href="/team" class="flex items-center gap-2 bg-white/20 hover:bg-white/30 text-white px-4 py-2.5 rounded-xl font-semibold text-sm transition">
                    <i class="fas fa-users"></i> Lihat Tim Saya
                </a>
            </div>
        </div>
        <div class="lg:hidden mt-4">
            <a href="/team" class="inline-flex items-center gap-2 bg-white/20 hover:bg-white/30 text-white px-4 py-2.5 rounded-xl font-semibold text-sm transition">
                <i class="fas fa-users"></i> Lihat Tim Saya
            </a>
        </div>
        <div class="absolute top-0 right-0 w-80 h-80 bg-white/5 rounded-full blur-3xl -mr-40 -mt-40"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-white/5 rounded-full blur-3xl -ml-32 -mb-32"></div>
    </div>
</div>

<!-- Statistics -->
<h2 class="text-2xl font-bold text-gray-900 mb-2">Dashboard Overview</h2>
<p class="text-gray-600 mb-8">Ringkasan data tim Anda</p>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow p-6 border-t-4 border-blue-500">
        <p class="text-gray-500 text-sm">Anggota Tim</p>
        <p class="text-3xl font-bold text-gray-900 mt-2"><?= $stats['total_team_members'] ?></p>
        <a href="/team" class="text-blue-600 text-xs mt-2 inline-block hover:underline">Lihat semua →</a>
    </div>
    <div class="bg-white rounded-xl shadow p-6 border-t-4 border-yellow-500">
        <p class="text-gray-500 text-sm">Menunggu Penilaian</p>
        <p class="text-3xl font-bold text-gray-900 mt-2"><?= $stats['pending_evaluation'] ?></p>
        <?php if ($stats['pending_evaluation'] > 0): ?>
            <a href="/team" class="text-yellow-600 text-xs mt-2 inline-block hover:underline">Nilai sekarang →</a>
        <?php endif; ?>
    </div>
    <div class="bg-white rounded-xl shadow p-6 border-t-4 border-green-500">
        <p class="text-gray-500 text-sm">Total Penilaian</p>
        <p class="text-3xl font-bold text-gray-900 mt-2"><?= $stats['total_evaluations'] ?></p>
    </div>
</div>

<!-- Recent Evaluations grouped by employee -->
<div class="bg-white rounded-xl shadow">
    <div class="px-6 py-4 border-b flex items-center justify-between">
        <h3 class="text-lg font-bold text-gray-900">Penilaian Terbaru</h3>
        <a href="/team" class="text-blue-600 hover:text-blue-700 text-sm font-medium">Lihat Semua →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b">
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Nama Team Member</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Penilaian 1</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Penilaian 2</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($evalByEmployee)): ?>
                    <?php foreach ($evalByEmployee as $grp): ?>
                        <?php $e1 = $grp['eval1']; $e2 = $grp['eval2']; ?>
                        <tr class="border-b hover:bg-gray-50 transition">
                            <td class="px-6 py-4 font-medium text-gray-900"><?= htmlspecialchars($grp['employee_nama']) ?></td>
                            <!-- Penilaian 1 -->
                            <td class="px-6 py-4">
                                <?php if ($e1): ?>
                                    <div class="flex flex-col gap-1">
                                        <span class="font-bold <?= $e1['nilai_total'] >= 8 ? 'text-green-600' : ($e1['nilai_total'] >= 6 ? 'text-blue-600' : 'text-red-600') ?>">
                                            <?= htmlspecialchars($e1['nilai_total']) ?>
                                        </span>
                                        <p class="text-xs text-gray-400"><?= date('d/m/Y', strtotime($e1['tanggal_penilaian'])) ?></p>
                                        <div class="flex gap-1 flex-wrap">
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
                                    <span class="text-gray-300 text-xs">-</span>
                                <?php endif; ?>
                            </td>
                            <!-- Penilaian 2 -->
                            <td class="px-6 py-4">
                                <?php if ($e2): ?>
                                    <div class="flex flex-col gap-1">
                                        <span class="font-bold <?= $e2['nilai_total'] >= 8 ? 'text-green-600' : ($e2['nilai_total'] >= 6 ? 'text-blue-600' : 'text-red-600') ?>">
                                            <?= htmlspecialchars($e2['nilai_total']) ?>
                                        </span>
                                        <p class="text-xs text-gray-400"><?= date('d/m/Y', strtotime($e2['tanggal_penilaian'])) ?></p>
                                        <div class="flex gap-1 flex-wrap">
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
                            <!-- PDF Semua -->
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1.5">
                                    <?php if ($e1 && $e2): ?>
                                        <button onclick="pdfDownload('/reports/pdf-all/<?= $grp['employee_id'] ?>')"
                                           class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-lg transition flex items-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-file-pdf text-xs"></i> PDF Semua
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($e1): ?>
                                        <button onclick="hapusPenilaian(<?= $e1['id'] ?>, '<?= htmlspecialchars($grp['employee_nama'], ENT_QUOTES) ?> (ke-1)')"
                                                class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-semibold rounded-lg transition flex items-center gap-1.5">
                                            <i class="fas fa-trash text-xs"></i> Hapus Ke-1
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($e2): ?>
                                        <button onclick="hapusPenilaian(<?= $e2['id'] ?>, '<?= htmlspecialchars($grp['employee_nama'], ENT_QUOTES) ?> (ke-2)')"
                                                class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-semibold rounded-lg transition flex items-center gap-1.5">
                                            <i class="fas fa-trash text-xs"></i> Hapus Ke-2
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-6 py-10 text-center text-gray-400">
                            <i class="fas fa-file-alt text-3xl mb-3 block text-gray-300"></i>
                            Belum ada penilaian yang dibuat
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal konfirmasi hapus -->
<div id="modalHapus" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="tutupModalHapus()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
        <div class="text-center">
            <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-trash text-red-600 text-xl"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Hapus Penilaian?</h3>
            <p class="text-sm text-gray-500 mb-1">Anda akan menghapus penilaian untuk:</p>
            <p id="hapusNamaKaryawan" class="font-semibold text-gray-800 mb-4"></p>
            <p class="text-xs text-red-500 mb-6">Tindakan ini tidak dapat dibatalkan.</p>
            <div class="flex gap-3">
                <button onclick="tutupModalHapus()"
                        class="flex-1 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-sm rounded-xl transition">
                    Batal
                </button>
                <button id="btnKonfirmasiHapus"
                        class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-semibold text-sm rounded-xl transition">
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var hapusTargetId = null;

function hapusPenilaian(id, nama) {
    hapusTargetId = id;
    document.getElementById('hapusNamaKaryawan').textContent = nama;
    document.getElementById('modalHapus').classList.remove('hidden');
}

function tutupModalHapus() {
    document.getElementById('modalHapus').classList.add('hidden');
    hapusTargetId = null;
}

document.getElementById('btnKonfirmasiHapus').addEventListener('click', function() {
    if (!hapusTargetId) return;
    var btn = this;
    btn.disabled = true;
    btn.textContent = 'Menghapus...';

    fetch('/evaluations/' + hapusTargetId, {
        method: 'DELETE',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ?
                            document.querySelector('meta[name="csrf-token"]').content : ''
        }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            tutupModalHapus();
            window.location.reload();
        } else {
            alert(data.error || 'Gagal menghapus penilaian');
            btn.disabled = false;
            btn.textContent = 'Ya, Hapus';
        }
    })
    .catch(function() {
        alert('Terjadi kesalahan. Silakan coba lagi.');
        btn.disabled = false;
        btn.textContent = 'Ya, Hapus';
    });
});
</script>

<?php $this->endSection(); ?>
