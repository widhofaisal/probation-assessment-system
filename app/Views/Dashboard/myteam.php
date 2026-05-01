<?php $this->extend('layouts/main'); $this->section('content'); ?>

<!-- Header -->
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Tim Saya</h2>
        <p class="text-gray-500 mt-1 text-sm">Kelola dan nilai karyawan probation dalam tim Anda</p>
    </div>
    <div class="flex items-center gap-3">
        <span class="px-4 py-2 bg-blue-50 text-blue-700 rounded-xl text-sm font-semibold">
            <i class="fas fa-users mr-1.5"></i><?= $stats['total_team_members'] ?> Anggota
        </span>
        <?php if ($stats['pending_evaluation'] > 0): ?>
            <span class="px-4 py-2 bg-yellow-50 text-yellow-700 rounded-xl text-sm font-semibold">
                <i class="fas fa-clock mr-1.5"></i><?= $stats['pending_evaluation'] ?> Menunggu Penilaian
            </span>
        <?php endif; ?>
    </div>
</div>

<!-- Team Members Table -->
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b">
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">NIK</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Nama</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Departemen</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Posisi</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($employees)): ?>
                    <?php foreach ($employees as $emp): ?>
                        <tr class="border-b hover:bg-gray-50 transition">
                            <td class="px-6 py-4 font-medium text-gray-900 text-sm"><?= htmlspecialchars($emp['nik']) ?></td>
                            <td class="px-6 py-4 text-gray-800 text-sm"><?= htmlspecialchars($emp['nama']) ?></td>
                            <td class="px-6 py-4 text-gray-600 text-sm"><?= htmlspecialchars($emp['departemen']) ?></td>
                            <td class="px-6 py-4 text-gray-600 text-sm"><?= htmlspecialchars($emp['posisi']) ?></td>
                            <td class="px-6 py-4">
                                <?php
                                $es = $emp['eval_status'];
                                [$badgeClass, $badgeText, $badgeIcon] = match($es) {
                                    'perlu_dinilai'  => ['bg-yellow-100 text-yellow-700', 'Perlu Dinilai',         'fa-clock'],
                                    'belum_waktunya' => ['bg-gray-100 text-gray-500',     'Belum Waktunya',        'fa-hourglass-half'],
                                    'sudah_dinilai'  => ['bg-blue-100 text-blue-700',     'Sudah Dinilai (ke-1)',  'fa-check-circle'],
                                    'selesai_dinilai'=> ['bg-purple-100 text-purple-700', 'Selesai (2/2)',         'fa-check-double'],
                                    'lulus'          => ['bg-green-100 text-green-700',   'Lulus',                 'fa-trophy'],
                                    'tidak-lulus'    => ['bg-red-100 text-red-700',       'Tidak Lulus',           'fa-times-circle'],
                                    'warning'        => ['bg-orange-100 text-orange-700', 'Warning',               'fa-exclamation-triangle'],
                                    default          => ['bg-gray-100 text-gray-500',     ucfirst($es),            'fa-circle'],
                                };
                                ?>
                                <div>
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $badgeClass ?>">
                                        <i class="fas <?= $badgeIcon ?> mr-1"></i><?= $badgeText ?>
                                    </span>
                                    <?php if ($emp['total_penilaian'] > 0 && $es !== 'selesai_dinilai'): ?>
                                        <p class="text-xs text-gray-400 mt-1">
                                            Penilaian ke-<?= $emp['total_penilaian'] ?> dilakukan
                                            <?php if ($emp['hari_sejak_eval'] !== null): ?>
                                                <?= $emp['hari_sejak_eval'] ?> hari lalu
                                            <?php endif; ?>
                                            <?php if (!empty($emp['sisa_hari'])): ?>
                                                · penilaian ke-<?= $emp['total_penilaian'] + 1 ?> dalam ~<?= $emp['sisa_hari'] ?> hari
                                            <?php endif; ?>
                                        </p>
                                    <?php elseif ($es === 'selesai_dinilai'): ?>
                                        <p class="text-xs text-gray-400 mt-1">Menunggu keputusan HRD</p>
                                    <?php elseif ($es === 'belum_waktunya' && !empty($emp['sisa_hari'])): ?>
                                        <p class="text-xs text-gray-400 mt-1">Penilaian ke-1 dalam ~<?= $emp['sisa_hari'] ?> hari</p>
                                    <?php elseif ($es === 'perlu_dinilai'): ?>
                                        <p class="text-xs text-yellow-600 mt-1 font-medium">
                                            Penilaian ke-<?= $emp['total_penilaian'] + 1 ?> sudah bisa dilakukan
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($emp['perlu_dinilai']): ?>
                                    <?php
                                    // Hitung tanggal otomatis sesuai siklus
                                    $siklus = $emp['total_penilaian'] + 1;
                                    if ($siklus === 1) {
                                        // Siklus ke-1: mulai probation s/d +45 hari
                                        $tglMulai   = $emp['mulai_probation'] ?? '';
                                        $tglSelesai = $emp['mulai_probation']
                                            ? (new \DateTime($emp['mulai_probation']))->modify('+45 days')->format('Y-m-d')
                                            : '';
                                    } else {
                                        // Siklus ke-2: sehari setelah penilaian ke-1 s/d akhir probation
                                        $tglMulai   = $emp['last_eval_date']
                                            ? (new \DateTime($emp['last_eval_date']))->modify('+1 day')->format('Y-m-d')
                                            : '';
                                        $tglSelesai = $emp['akhir_probation'] ?? '';
                                    }
                                    ?>
                                    <button
                                            data-id="<?= $emp['id'] ?>"
                                            data-nik="<?= htmlspecialchars($emp['nik'], ENT_QUOTES) ?>"
                                            data-nama="<?= htmlspecialchars($emp['nama'], ENT_QUOTES) ?>"
                                            data-dept="<?= htmlspecialchars($emp['departemen'], ENT_QUOTES) ?>"
                                            data-posisi="<?= htmlspecialchars($emp['posisi'], ENT_QUOTES) ?>"
                                            data-mulai="<?= htmlspecialchars($tglMulai, ENT_QUOTES) ?>"
                                            data-selesai="<?= htmlspecialchars($tglSelesai, ENT_QUOTES) ?>"
                                            onclick="openEvalModalFromBtn(this)"
                                            class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg transition flex items-center gap-1.5">
                                        <i class="fas fa-star text-xs"></i> Nilai (ke-<?= $siklus ?>)
                                    </button>
                                <?php else: ?>
                                    <span class="text-gray-400 text-sm">Sudah dinilai</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                            <i class="fas fa-users text-4xl mb-3 block text-gray-300"></i>
                            <p class="font-medium">Belum ada anggota tim</p>
                            <p class="text-sm mt-1">Hubungi HRD untuk menambahkan karyawan ke tim Anda</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ========== MODAL PENILAIAN 3-STEP ========== -->
<div id="evalModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeEvalModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[92vh] flex flex-col modal-enter">

        <!-- Modal Header -->
        <div class="px-6 pt-5 pb-4 border-b flex-shrink-0">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Form Penilaian Probation</h3>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Evaluasi kinerja karyawan:
                        <span id="eval_subtitle" class="text-blue-600 font-semibold"></span>
                    </p>
                </div>
                <button onclick="closeEvalModal()" class="text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 transition flex-shrink-0">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Step Progress Indicator -->
            <div class="flex items-center">
                <!-- Step 1 -->
                <div class="flex flex-col items-center" style="min-width:80px">
                    <div id="si_1" class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300 bg-blue-600 text-white">1</div>
                    <span class="text-xs font-semibold mt-1 text-blue-600" id="sl_1">Data Karyawan</span>
                </div>
                <div id="line_1" class="flex-1 h-0.5 mb-4 bg-gray-200 transition-all duration-300 mx-1"></div>
                <!-- Step 2 -->
                <div class="flex flex-col items-center" style="min-width:60px">
                    <div id="si_2" class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300 bg-gray-200 text-gray-500">2</div>
                    <span class="text-xs font-medium mt-1 text-gray-400" id="sl_2">Penilaian</span>
                </div>
                <div id="line_2" class="flex-1 h-0.5 mb-4 bg-gray-200 transition-all duration-300 mx-1"></div>
                <!-- Step 3 -->
                <div class="flex flex-col items-center" style="min-width:60px">
                    <div id="si_3" class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300 bg-gray-200 text-gray-500">3</div>
                    <span class="text-xs font-medium mt-1 text-gray-400" id="sl_3">Ringkasan</span>
                </div>
            </div>
        </div>

        <!-- Scrollable Step Content -->
        <div class="overflow-y-auto flex-1" id="evalScrollArea">

            <!-- ===== STEP 1: Data Karyawan ===== -->
            <div id="evalStep1" class="p-6">
                <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                    <h4 class="text-lg font-bold text-gray-900 mb-5">Data Karyawan</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1.5">Nama Lengkap</label>
                            <input type="text" id="e1_nama" readonly
                                   class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-50 text-gray-700 text-sm cursor-default">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1.5">NIK</label>
                            <input type="text" id="e1_nik" readonly
                                   class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-50 text-gray-700 text-sm cursor-default">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1.5">Departemen</label>
                            <input type="text" id="e1_dept" readonly
                                   class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-50 text-gray-700 text-sm cursor-default">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1.5">Posisi</label>
                            <input type="text" id="e1_posisi" readonly
                                   class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-50 text-gray-700 text-sm cursor-default">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1.5">Tanggal Mulai Penilaian</label>
                            <input type="date" id="e1_mulai"
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-gray-700 text-sm transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1.5">Tanggal Selesai Penilaian</label>
                            <input type="date" id="e1_selesai"
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-gray-700 text-sm transition">
                        </div>
                    </div>
                    <div class="flex justify-end mt-6">
                        <button onclick="goToStep(2)"
                                class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm rounded-xl transition flex items-center gap-2">
                            Lanjut ke Penilaian <i class="fas fa-arrow-right text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ===== STEP 2: Penilaian ===== -->
            <div id="evalStep2" class="hidden p-6 space-y-6">
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
                $gIdx = 0;
                ?>
                <?php foreach ($aspects as $category => $items): ?>
                    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                        <div class="px-5 py-3.5 border-b bg-gray-50">
                            <h5 class="font-bold text-gray-900"><?= htmlspecialchars($category) ?></h5>
                        </div>
                        <div class="p-5 space-y-5">
                            <?php foreach ($items as $aspek): ?>
                                <div data-aspect-idx="<?= $gIdx ?>">
                                    <p class="text-sm text-gray-700 mb-2"><?= htmlspecialchars($aspek) ?></p>
                                    <div class="flex gap-1.5 flex-wrap">
                                        <?php for ($s = 0; $s <= 10; $s++): ?>
                                            <button type="button"
                                                    id="sb_<?= $gIdx ?>_<?= $s ?>"
                                                    onclick="setScore(<?= $gIdx ?>, <?= $s ?>)"
                                                    class="score-btn w-10 h-10 rounded-lg font-bold text-sm transition-all
                                                           <?= $s === 0 ? 'bg-red-500 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
                                                <?= $s ?>
                                            </button>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <?php $gIdx++; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Catatan -->
                <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Catatan Team Leader</label>
                    <textarea id="eval_catatan" rows="3"
                              placeholder="Tuliskan catatan atau observasi tentang kinerja karyawan selama masa probation..."
                              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm resize-none transition"></textarea>
                </div>

                <div class="flex justify-between">
                    <button onclick="goToStep(1)"
                            class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-sm rounded-xl transition flex items-center gap-2">
                        <i class="fas fa-arrow-left text-xs"></i> Kembali
                    </button>
                    <button onclick="goToStep(3)"
                            class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm rounded-xl transition flex items-center gap-2">
                        Lihat Ringkasan <i class="fas fa-arrow-right text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- ===== STEP 3: Ringkasan ===== -->
            <div id="evalStep3" class="hidden p-6">
                <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                    <h4 class="text-lg font-bold text-gray-900 mb-5">Ringkasan Penilaian</h4>

                    <!-- Average score -->
                    <div class="bg-gradient-to-br from-gray-50 to-blue-50 rounded-xl p-5 text-center mb-6 border border-gray-100">
                        <p class="text-sm text-gray-500 mb-1">Nilai Rata-rata</p>
                        <p id="summary_avg" class="text-5xl font-bold text-gray-900 my-1">0.00</p>
                        <p class="text-sm text-gray-400">dari skala 10</p>
                    </div>

                    <!-- Per-category breakdown -->
                    <div id="summary_body" class="space-y-5"></div>
                </div>

                <div class="flex justify-between mt-6">
                    <button onclick="goToStep(2)"
                            class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-sm rounded-xl transition flex items-center gap-2">
                        <i class="fas fa-arrow-left text-xs"></i> Kembali
                    </button>
                    <button onclick="submitEvaluation()"
                            class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm rounded-xl transition flex items-center gap-2">
                        <i class="fas fa-paper-plane text-xs"></i> Submit Penilaian
                    </button>
                </div>
            </div>
        </div><!-- end scroll area -->
    </div><!-- end modal box -->
</div>

<!-- Hidden submission form -->
<form id="evaluationForm" action="/evaluations/store" method="POST" class="hidden">
    <?= csrf_field() ?>
    <input type="hidden" name="employee_id" id="form_employee_id">
    <div id="form_hidden_inputs"></div>
</form>

<script>
// ===== Aspect definitions (mirrors PHP) =====
var ASPECTS = [
    { cat: 'Kinerja & Produktivitas', label: 'Kemampuan menyelesaikan tugas tepat waktu' },
    { cat: 'Kinerja & Produktivitas', label: 'Kualitas hasil kerja' },
    { cat: 'Kinerja & Produktivitas', label: 'Inisiatif dalam bekerja' },
    { cat: 'Kinerja & Produktivitas', label: 'Kemampuan problem solving' },
    { cat: 'Kedisiplinan',            label: 'Kehadiran dan ketepatan waktu' },
    { cat: 'Kedisiplinan',            label: 'Kepatuhan terhadap prosedur kerja' },
    { cat: 'Kedisiplinan',            label: 'Penggunaan waktu kerja yang efektif' },
    { cat: 'Sikap & Perilaku',        label: 'Kerjasama dalam tim' },
    { cat: 'Sikap & Perilaku',        label: 'Komunikasi dengan rekan kerja' },
    { cat: 'Sikap & Perilaku',        label: 'Sikap dan etika kerja' },
    { cat: 'Sikap & Perilaku',        label: 'Kemampuan menerima feedback' },
];

var evalScores   = new Array(ASPECTS.length).fill(0);
var evalEmpId    = null;
var currentStep  = 1;

// ===== Open / Close =====
function openEvalModalFromBtn(btn) {
    openEvalModal(
        btn.dataset.id,
        btn.dataset.nik,
        btn.dataset.nama,
        btn.dataset.dept,
        btn.dataset.posisi,
        btn.dataset.mulai,
        btn.dataset.selesai
    );
}

function openEvalModal(id, nik, nama, dept, posisi, mulai, selesai) {
    evalEmpId = id;
    evalScores = new Array(ASPECTS.length).fill(0);

    document.getElementById('eval_subtitle').textContent = nama + ' (' + nik + ')';
    document.getElementById('e1_nama').value   = nama;
    document.getElementById('e1_nik').value    = nik;
    document.getElementById('e1_dept').value   = dept;
    document.getElementById('e1_posisi').value = posisi;
    document.getElementById('e1_mulai').value  = mulai || '';
    document.getElementById('e1_selesai').value = selesai || '';
    document.getElementById('eval_catatan').value = '';

    // Reset all score buttons to initial state (0 selected = red)
    for (var i = 0; i < ASPECTS.length; i++) {
        for (var s = 0; s <= 10; s++) {
            var btn = document.getElementById('sb_' + i + '_' + s);
            if (!btn) continue;
            btn.className = 'score-btn w-10 h-10 rounded-lg font-bold text-sm transition-all ' +
                (s === 0 ? 'bg-red-500 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200');
        }
    }

    goToStep(1, true);
    document.getElementById('evalModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeEvalModal() {
    document.getElementById('evalModal').classList.add('hidden');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeEvalModal();
});

// ===== Step Navigation =====
function goToStep(step, skipValidation) {
    // Validate before leaving Step 2 → 3
    if (!skipValidation && currentStep === 2 && step === 3) {
        var zeros = evalScores.filter(function(s) { return s === 0; }).length;
        if (zeros > 0) {
            showToast(zeros + ' aspek belum diberi nilai. Silakan isi semua aspek terlebih dahulu.', 'warning', 5000);
            return;
        }
    }

    currentStep = step;
    document.getElementById('evalStep1').classList.toggle('hidden', step !== 1);
    document.getElementById('evalStep2').classList.toggle('hidden', step !== 2);
    document.getElementById('evalStep3').classList.toggle('hidden', step !== 3);

    if (step === 3) buildSummary();

    updateStepIndicator(step);
    document.getElementById('evalScrollArea').scrollTop = 0;
}

function updateStepIndicator(active) {
    for (var s = 1; s <= 3; s++) {
        var circle = document.getElementById('si_' + s);
        var label  = document.getElementById('sl_' + s);
        if (s < active) {
            // Completed
            circle.className = 'w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300 bg-blue-600 text-white';
            circle.innerHTML = '<i class="fas fa-check text-xs"></i>';
            label.className  = 'text-xs font-semibold mt-1 text-blue-600';
        } else if (s === active) {
            // Active
            circle.className = 'w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300 bg-blue-600 text-white';
            circle.innerHTML = s;
            label.className  = 'text-xs font-semibold mt-1 text-blue-600';
        } else {
            // Upcoming
            circle.className = 'w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300 bg-gray-200 text-gray-500';
            circle.innerHTML = s;
            label.className  = 'text-xs font-medium mt-1 text-gray-400';
        }
    }
    // Connector lines
    document.getElementById('line_1').className = 'flex-1 h-0.5 mb-4 transition-all duration-300 mx-1 ' +
        (active > 1 ? 'bg-blue-600' : 'bg-gray-200');
    document.getElementById('line_2').className = 'flex-1 h-0.5 mb-4 transition-all duration-300 mx-1 ' +
        (active > 2 ? 'bg-blue-600' : 'bg-gray-200');
}

// ===== Score Buttons =====
function setScore(idx, value) {
    evalScores[idx] = value;
    for (var s = 0; s <= 10; s++) {
        var btn = document.getElementById('sb_' + idx + '_' + s);
        if (!btn) continue;
        if (s === value) {
            btn.className = 'score-btn w-10 h-10 rounded-lg font-bold text-sm transition-all bg-blue-600 text-white shadow-md';
        } else {
            btn.className = 'score-btn w-10 h-10 rounded-lg font-bold text-sm transition-all bg-gray-100 text-gray-600 hover:bg-gray-200';
        }
    }
}

// ===== Build Summary (Step 3) =====
function buildSummary() {
    var total = evalScores.reduce(function(a, b) { return a + b; }, 0);
    var avg   = (total / ASPECTS.length).toFixed(2);
    document.getElementById('summary_avg').textContent = avg;

    // Group by category
    var cats = {};
    ASPECTS.forEach(function(a, i) {
        if (!cats[a.cat]) cats[a.cat] = [];
        cats[a.cat].push({ label: a.label, score: evalScores[i] });
    });

    var html = '';
    Object.keys(cats).forEach(function(cat) {
        var items  = cats[cat];
        var catAvg = (items.reduce(function(s, x) { return s + x.score; }, 0) / items.length).toFixed(1);
        var color  = parseFloat(catAvg) >= 8 ? 'text-green-600' : parseFloat(catAvg) >= 6 ? 'text-blue-600' : 'text-red-600';
        html += '<div>';
        html += '<div class="flex items-center justify-between mb-2">';
        html += '<h6 class="font-bold text-gray-800">' + cat + '</h6>';
        html += '<span class="font-bold ' + color + '">' + catAvg + '</span>';
        html += '</div>';
        items.forEach(function(item) {
            var sc = item.score;
            var sc_color = sc >= 8 ? 'text-green-600' : sc >= 6 ? 'text-blue-600' : 'text-red-600';
            html += '<div class="flex justify-between py-1.5 border-b border-gray-100 last:border-0">';
            html += '<span class="text-sm text-gray-600">' + item.label + '</span>';
            html += '<span class="text-sm font-semibold ' + sc_color + '">' + sc + '</span>';
            html += '</div>';
        });
        html += '</div>';
    });

    document.getElementById('summary_body').innerHTML = html;
}

// ===== Submit =====
function submitEvaluation() {
    var form = document.getElementById('evaluationForm');
    document.getElementById('form_employee_id').value = evalEmpId;

    // Build all hidden inputs as one string, then set once
    var html = '';
    for (var i = 0; i < ASPECTS.length; i++) {
        html += '<input type="hidden" name="scores[]" value="' + evalScores[i] + '">';
        html += '<input type="hidden" name="alasan[]" value="">';
        html += '<input type="hidden" name="details[' + i + '][kategori]" value="' + escHtml(ASPECTS[i].cat) + '">';
        html += '<input type="hidden" name="details[' + i + '][aspek]" value="' + escHtml(ASPECTS[i].label) + '">';
        html += '<input type="hidden" name="details[' + i + '][nilai]" value="' + evalScores[i] + '">';
        html += '<input type="hidden" name="details[' + i + '][alasan]" value="">';
    }
    html += '<input type="hidden" name="catatan_team_leader" value="' + escHtml(document.getElementById('eval_catatan').value) + '">';
    html += '<input type="hidden" name="tanggal_mulai_penilaian" value="' + escHtml(document.getElementById('e1_mulai').value) + '">';
    html += '<input type="hidden" name="tanggal_selesai_penilaian" value="' + escHtml(document.getElementById('e1_selesai').value) + '">';

    document.getElementById('form_hidden_inputs').innerHTML = html;
    form.submit();
}

function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}
</script>

<?php $this->endSection(); ?>
