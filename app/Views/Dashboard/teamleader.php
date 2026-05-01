<?php $this->extend('layouts/main'); $this->section('content'); ?>

<!-- Welcome Banner -->
<div class="mb-8 bg-gradient-to-br from-green-600 via-blue-600 to-blue-700 text-white rounded-2xl p-8 shadow-xl">
    <div class="flex items-start justify-between flex-wrap gap-6">
        <div class="flex-1">
            <h1 class="text-3xl font-bold mb-1"><?= htmlspecialchars($user['nama']) ?></h1>
            <p class="text-white/80 mt-1">Team Leader · <?= htmlspecialchars($user['departemen'] ?? '') ?></p>
        </div>
        <a href="/team" class="flex-shrink-0 flex items-center gap-2 bg-white/20 hover:bg-white/30 text-white px-4 py-2.5 rounded-xl font-semibold text-sm transition">
            <i class="fas fa-users"></i> Lihat Tim Saya
        </a>
    </div>
</div>

<!-- Statistics -->
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

<!-- Recent Evaluations -->
<div class="bg-white rounded-xl shadow">
    <div class="px-6 py-4 border-b flex items-center justify-between">
        <h3 class="text-lg font-bold text-gray-900">Penilaian Terbaru</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b">
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Nama Karyawan</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Nilai</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Tanggal</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recentEvaluations)): ?>
                    <?php foreach ($recentEvaluations as $eval): ?>
                        <tr class="border-b hover:bg-gray-50 transition">
                            <td class="px-6 py-4 font-medium text-gray-900"><?= htmlspecialchars($eval['employee_nama']) ?></td>
                            <td class="px-6 py-4">
                                <span class="text-lg font-bold <?php
                                    if ($eval['nilai_total'] >= 8) echo 'text-green-600';
                                    elseif ($eval['nilai_total'] >= 6) echo 'text-blue-600';
                                    else echo 'text-red-600';
                                ?>"><?= htmlspecialchars($eval['nilai_total']) ?></span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?= date('d/m/Y', strtotime($eval['tanggal_penilaian'])) ?></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <a href="/evaluations/<?= $eval['id'] ?>"
                                       class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-lg transition">
                                        <i class="fas fa-eye mr-1"></i>Detail
                                    </a>
                                    <a href="/evaluations/<?= $eval['id'] ?>/edit"
                                       class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold rounded-lg transition">
                                        <i class="fas fa-edit mr-1"></i>Edit
                                    </a>
                                    <button onclick="hapusPenilaian(<?= $eval['id'] ?>, '<?= htmlspecialchars($eval['employee_nama'], ENT_QUOTES) ?>')"
                                            class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-semibold rounded-lg transition">
                                        <i class="fas fa-trash mr-1"></i>Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-400">
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
