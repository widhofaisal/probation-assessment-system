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

                                <!-- Aksi (PDF Semua + Keputusan HRD) -->
                                <td class="px-5 py-4">
                                    <div class="flex flex-col gap-2 items-start">
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

                                        <?php if ($e1 && $e2): ?>
                                            <?php
                                            $kep = $grp['keputusan'] ?? null;
                                            $kepData = 'data-eval-id="' . $e2['id'] . '"'
                                                     . ' data-nama="' . htmlspecialchars($grp['nama'], ENT_QUOTES) . '"'
                                                     . ' data-nik="' . htmlspecialchars($grp['nik'], ENT_QUOTES) . '"'
                                                     . ' data-diangkat="' . htmlspecialchars((string)($kep['tanggal_diangkat'] ?? ''), ENT_QUOTES) . '"'
                                                     . ' data-diakhiri="' . htmlspecialchars((string)($kep['tanggal_diakhiri'] ?? ''), ENT_QUOTES) . '"'
                                                     . ' data-lain="' . htmlspecialchars((string)($kep['lain_lain'] ?? ''), ENT_QUOTES) . '"'
                                                     . ' data-status="' . htmlspecialchars((string)($kep['status_akhir'] ?? ''), ENT_QUOTES) . '"';
                                            ?>
                                            <?php if ($kep): ?>
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?=
                                                        $kep['status_akhir'] === 'lulus' ? 'bg-green-100 text-green-700'
                                                        : ($kep['status_akhir'] === 'tidak-lulus' ? 'bg-red-100 text-red-700'
                                                        : 'bg-orange-100 text-orange-700') ?>">
                                                        <i class="fas fa-gavel mr-1"></i><?= htmlspecialchars(\App\Models\EvaluationDecisionModel::statusLabel($kep['status_akhir'])) ?>
                                                    </span>
                                                    <button type="button" onclick="openKeputusanModal(this)" <?= $kepData ?>
                                                            class="px-2 py-1 bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-semibold rounded-lg transition cursor-pointer">
                                                        Ubah
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <button type="button" onclick="openKeputusanModal(this)" <?= $kepData ?>
                                                        class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold rounded-lg transition flex items-center gap-1.5 whitespace-nowrap cursor-pointer">
                                                    <i class="fas fa-gavel text-xs"></i> Isi Keputusan HRD
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
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

<!-- ========== MODAL KEPUTUSAN HRD ========== -->
<div id="keputusanModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeKeputusanModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto modal-enter">
        <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-2xl z-10">
            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                <i class="fas fa-gavel text-amber-500"></i> Keputusan Dept. HRD
            </h3>
            <button onclick="closeKeputusanModal()" class="text-gray-400 hover:text-gray-600 transition w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="keputusanForm" action="" method="POST" class="p-6 space-y-5">
            <?= csrf_field() ?>

            <div class="bg-gray-50 rounded-lg px-4 py-3">
                <p class="text-sm font-semibold text-gray-900" id="k_nama">-</p>
                <p class="text-xs text-gray-500 mt-0.5">NIK <span id="k_nik">-</span> · Penilaian ke-2 selesai</p>
            </div>

            <p class="text-sm text-gray-600 leading-relaxed">
                Memperhatikan penilaian tersebut di atas, maka karyawan tersebut dipertimbangkan
                dan atau diputuskan untuk :
            </p>

            <div class="space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 items-center">
                    <label class="text-sm text-gray-700">Diangkat sebagai karyawan tetap per tanggal</label>
                    <input type="date" name="tanggal_diangkat" id="k_diangkat"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm transition">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 items-center">
                    <label class="text-sm text-gray-700">Diakhiri masa kerjanya per tanggal</label>
                    <input type="date" name="tanggal_diakhiri" id="k_diakhiri"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm transition">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 items-center">
                    <label class="text-sm text-gray-700">Lain-lain</label>
                    <input type="text" name="lain_lain" id="k_lain" maxlength="60"
                           placeholder="mis. Diperpanjang 3 bulan"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm transition">
                </div>
                <p class="text-xs text-gray-400">
                    Isi minimal satu baris — hanya baris yang terisi yang dicetak di PDF.
                    "Lain-lain" maksimal 60 karakter agar muat satu baris pada form.
                </p>
            </div>

            <div class="border-t pt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Status akhir masa probation <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <?php foreach ([
                        ['lulus',       'Lulus',       'fa-trophy',                'green'],
                        ['tidak-lulus', 'Tidak Lulus', 'fa-times-circle',          'red'],
                        ['warning',     'Warning',     'fa-exclamation-triangle',  'orange'],
                    ] as [$val, $label, $icon, $color]): ?>
                        <label class="flex items-center gap-2 px-3 py-2.5 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition text-sm">
                            <input type="radio" name="status_akhir" value="<?= $val ?>" class="k_status">
                            <i class="fas <?= $icon ?> text-<?= $color ?>-500"></i>
                            <span class="text-gray-700"><?= $label ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="text-xs text-gray-400 mt-2">
                    Status ini yang dipakai sebagai status probation Team Member — tidak bisa diubah
                    dari halaman Data Team Member.
                </p>
            </div>

            <div class="flex gap-3 pt-2 border-t">
                <button type="submit" class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold rounded-lg transition flex items-center gap-2">
                    <i class="fas fa-save"></i> Simpan Keputusan
                </button>
                <button type="button" onclick="closeKeputusanModal()" class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openKeputusanModal(btn) {
    var d = btn.dataset;

    document.getElementById('keputusanForm').action = '/evaluations/' + d.evalId + '/keputusan';
    document.getElementById('k_nama').textContent    = d.nama || '-';
    document.getElementById('k_nik').textContent     = d.nik || '-';
    document.getElementById('k_diangkat').value      = d.diangkat || '';
    document.getElementById('k_diakhiri').value      = d.diakhiri || '';
    document.getElementById('k_lain').value          = d.lain || '';

    var radios = document.querySelectorAll('.k_status');
    for (var i = 0; i < radios.length; i++) radios[i].checked = radios[i].value === d.status;

    document.getElementById('keputusanModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeKeputusanModal() {
    document.getElementById('keputusanModal').classList.add('hidden');
    document.body.style.overflow = '';
}

document.getElementById('keputusanForm').addEventListener('submit', function (e) {
    var adaBaris = document.getElementById('k_diangkat').value
                || document.getElementById('k_diakhiri').value
                || document.getElementById('k_lain').value.trim();
    var adaStatus = document.querySelector('.k_status:checked');

    if (!adaBaris) {
        e.preventDefault();
        showToast('Isi minimal satu baris keputusan (diangkat / diakhiri / lain-lain)', 'error');
        return;
    }
    if (!adaStatus) {
        e.preventDefault();
        showToast('Pilih status akhir masa probation', 'error');
    }
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeKeputusanModal();
});
</script>

<?php $this->endSection(); ?>
