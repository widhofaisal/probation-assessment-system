<?php $this->extend('layouts/main'); $this->section('content'); ?>

<?php
$aspects = [
    'Kinerja & Produktivitas' => [
        'Kemampuan menyelesaikan tugas tepat waktu',
        'Kualitas hasil kerja',
        'Inisiatif dalam bekerja',
        'Kemampuan problem solving',
    ],
    'Kedisiplinan' => [
        'Kehadiran dan ketepatan waktu',
        'Kepatuhan terhadap prosedur kerja',
        'Penggunaan waktu kerja yang efektif',
    ],
    'Sikap & Perilaku' => [
        'Kerjasama dalam tim',
        'Komunikasi dengan rekan kerja',
        'Sikap dan etika kerja',
        'Kemampuan menerima feedback',
    ],
];

// Buat map aspek → nilai dari detail yang tersimpan
$nilaiMap = [];
foreach ($details as $d) {
    $nilaiMap[$d['kategori']][$d['aspek']] = [
        'nilai'  => (int)$d['nilai'],
        'alasan' => $d['alasan'] ?? '',
    ];
}
?>

<div class="max-w-3xl mx-auto">

    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Edit Penilaian</h2>
            <p class="text-gray-500 mt-1 text-sm">
                Team Member: <span class="font-semibold text-blue-600"><?= htmlspecialchars($evaluation['nama']) ?></span>
                · Dinilai <?= date('d/m/Y', strtotime($evaluation['tanggal_penilaian'])) ?>
            </p>
        </div>
        <a href="/dashboard/team-leader"
           class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-xl transition">
            <i class="fas fa-arrow-left mr-1.5"></i>Kembali
        </a>
    </div>

    <form action="/evaluations/<?= $evaluation['id'] ?>" method="POST" id="editForm">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="POST">

        <?php $gIdx = 0; ?>
        <?php foreach ($aspects as $category => $items): ?>
            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm mb-4">
                <div class="px-5 py-3.5 border-b bg-gray-50">
                    <h5 class="font-bold text-gray-900"><?= htmlspecialchars($category) ?></h5>
                </div>
                <div class="p-5 space-y-5">
                    <?php foreach ($items as $aspek):
                        $saved = $nilaiMap[$category][$aspek] ?? ['nilai' => 0, 'alasan' => ''];
                        $nilaiSaved = $saved['nilai'];
                    ?>
                        <div>
                            <p class="text-sm text-gray-700 mb-2"><?= htmlspecialchars($aspek) ?></p>
                            <div class="flex gap-1.5 flex-wrap">
                                <?php for ($s = 0; $s <= 10; $s++): ?>
                                    <button type="button"
                                            id="sb_<?= $gIdx ?>_<?= $s ?>"
                                            onclick="setScore(<?= $gIdx ?>, <?= $s ?>)"
                                            class="score-btn w-10 h-10 rounded-lg font-bold text-sm transition-all
                                                   <?= $s === $nilaiSaved
                                                       ? 'bg-blue-600 text-white shadow-md'
                                                       : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
                                        <?= $s ?>
                                    </button>
                                <?php endfor; ?>
                            </div>

                            <!-- Hidden inputs -->
                            <input type="hidden" name="scores[]"                        id="score_<?= $gIdx ?>"  value="<?= $nilaiSaved ?>">
                            <input type="hidden" name="details[<?= $gIdx ?>][kategori]" value="<?= htmlspecialchars($category) ?>">
                            <input type="hidden" name="details[<?= $gIdx ?>][aspek]"    value="<?= htmlspecialchars($aspek) ?>">
                            <input type="hidden" name="details[<?= $gIdx ?>][nilai]"    id="detail_nilai_<?= $gIdx ?>" value="<?= $nilaiSaved ?>">
                            <input type="hidden" name="details[<?= $gIdx ?>][alasan]"   value="<?= htmlspecialchars($saved['alasan']) ?>">
                        </div>
                        <?php $gIdx++; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Catatan -->
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Catatan Team Leader</label>
            <textarea name="catatan_team_leader" rows="3"
                      placeholder="Catatan atau observasi tentang kinerja karyawan..."
                      class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm resize-none transition"><?= htmlspecialchars($evaluation['catatan_team_leader'] ?? '') ?></textarea>
        </div>

        <!-- Nilai rata-rata live -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6 flex items-center justify-between">
            <span class="text-sm font-semibold text-blue-700">Nilai Rata-rata</span>
            <span id="avgDisplay" class="text-2xl font-bold text-blue-700"><?= number_format($evaluation['nilai_total'], 2) ?></span>
        </div>

        <div class="flex gap-3 justify-end">
            <a href="/dashboard/team-leader"
               class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-sm rounded-xl transition">
                Batal
            </a>
            <button type="submit"
                    class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm rounded-xl transition flex items-center gap-2">
                <i class="fas fa-save text-xs"></i> Simpan Perubahan
            </button>
        </div>
    </form>
</div>

<script>
var totalAspects = <?= $gIdx ?>;
var scores = [<?php
    $all = [];
    $gi = 0;
    foreach ($aspects as $category => $items) {
        foreach ($items as $aspek) {
            $all[] = (int)($nilaiMap[$category][$aspek]['nilai'] ?? 0);
            $gi++;
        }
    }
    echo implode(',', $all);
?>];

function setScore(idx, value) {
    scores[idx] = value;
    document.getElementById('score_' + idx).value = value;
    document.getElementById('detail_nilai_' + idx).value = value;

    for (var s = 0; s <= 10; s++) {
        var btn = document.getElementById('sb_' + idx + '_' + s);
        if (!btn) continue;
        btn.className = 'score-btn w-10 h-10 rounded-lg font-bold text-sm transition-all ' +
            (s === value ? 'bg-blue-600 text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200');
    }
    updateAvg();
}

function updateAvg() {
    var sum = scores.reduce(function(a, b) { return a + b; }, 0);
    document.getElementById('avgDisplay').textContent = (sum / totalAspects).toFixed(2);
}
</script>

<?php $this->endSection(); ?>
