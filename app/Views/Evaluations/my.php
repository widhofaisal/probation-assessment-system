<?php $this->extend('layouts/main'); $this->section('content'); ?>

<!-- Info Team Member -->
<div class="mb-6 bg-white rounded-xl shadow p-5 flex flex-wrap items-center gap-4">
    <div class="flex items-center gap-3">
        <div class="w-12 h-12 bg-gradient-to-br from-blue-600 to-green-600 rounded-full flex items-center justify-center flex-shrink-0">
            <span class="text-white font-bold text-lg"><?= strtoupper(substr($employee['nama'], 0, 1)) ?></span>
        </div>
        <div>
            <p class="font-bold text-gray-900"><?= htmlspecialchars($employee['nama']) ?></p>
            <p class="text-sm text-gray-500"><?= htmlspecialchars($employee['nik']) ?> · <?= htmlspecialchars($employee['posisi']) ?> · <?= htmlspecialchars($employee['departemen']) ?></p>
        </div>
    </div>
    <div class="ml-auto flex flex-wrap items-center gap-3">
        <div class="text-center px-4 py-2 bg-gray-50 rounded-lg">
            <p class="text-xs text-gray-500">Total Penilaian</p>
            <p class="text-xl font-bold text-gray-900"><?= count($evaluations) ?></p>
        </div>
        <?php if (!empty($evaluations)): ?>
        <div class="text-center px-4 py-2 bg-blue-50 rounded-lg">
            <p class="text-xs text-gray-500">Rata-rata Nilai</p>
            <p class="text-xl font-bold text-blue-700"><?= number_format(array_sum(array_column($evaluations, 'nilai_total')) / count($evaluations), 2) ?></p>
        </div>
        <?php endif; ?>
        <div class="text-center px-4 py-2 rounded-lg
            <?php
            if ($employee['status'] === 'lulus') echo 'bg-green-50';
            elseif ($employee['status'] === 'tidak-lulus') echo 'bg-red-50';
            elseif ($employee['status'] === 'warning') echo 'bg-orange-50';
            else echo 'bg-yellow-50';
            ?>">
            <p class="text-xs text-gray-500">Status</p>
            <p class="text-sm font-bold
                <?php
                if ($employee['status'] === 'lulus') echo 'text-green-700';
                elseif ($employee['status'] === 'tidak-lulus') echo 'text-red-700';
                elseif ($employee['status'] === 'warning') echo 'text-orange-700';
                else echo 'text-yellow-700';
                ?>">
                <?= ucfirst(str_replace('-', ' ', $employee['status'])) ?>
            </p>
        </div>
    </div>
</div>

<?php if (empty($evaluations)): ?>
    <div class="bg-white rounded-xl shadow p-12 text-center text-gray-400">
        <i class="fas fa-file-alt text-5xl mb-4 block text-gray-200"></i>
        <p class="font-medium text-gray-600">Belum ada penilaian</p>
        <p class="text-sm mt-1">Penilaian akan muncul di sini setelah Team Leader Anda menilai.</p>
    </div>
<?php else: ?>
    <div class="space-y-6">
        <?php foreach ($evaluations as $i => $eval): ?>
            <?php
            $details  = $evalDetails[$eval['id']] ?? [];
            $cats     = [];
            foreach ($details as $d) {
                $cats[$d['kategori']][] = $d;
            }
            $siklus = count($evaluations) - $i; // urutan ke-1 = terlama, ke-n = terbaru
            $siklus = $i + 1;
            $nilaiColor = $eval['nilai_total'] >= 8 ? 'text-green-600' : ($eval['nilai_total'] >= 6 ? 'text-blue-600' : 'text-red-600');
            $nilaiBg    = $eval['nilai_total'] >= 8 ? 'from-green-50 to-emerald-50 border-green-200' : ($eval['nilai_total'] >= 6 ? 'from-blue-50 to-sky-50 border-blue-200' : 'from-red-50 to-rose-50 border-red-200');
            ?>
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <!-- Header penilaian -->
                <div class="px-6 py-4 border-b flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-blue-700 font-bold text-sm"><?= $siklus ?></span>
                        </div>
                        <div>
                            <p class="font-bold text-gray-900">Penilaian ke-<?= $siklus ?></p>
                            <p class="text-sm text-gray-500">
                                <i class="fas fa-calendar-alt mr-1"></i>
                                <?= date('d/m/Y H:i', strtotime($eval['tanggal_penilaian'])) ?>
                                &nbsp;·&nbsp;
                                <i class="fas fa-user mr-1"></i>
                                <?= htmlspecialchars($eval['team_leader_nama'] ?? '-') ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="bg-gradient-to-br <?= $nilaiBg ?> border rounded-xl px-5 py-2 text-center">
                            <p class="text-xs text-gray-500">Nilai Total</p>
                            <p class="text-2xl font-bold <?= $nilaiColor ?>"><?= number_format($eval['nilai_total'], 2) ?></p>
                            <p class="text-xs text-gray-400">dari 10</p>
                        </div>
                        <a href="/reports/evaluation/<?= $eval['id'] ?>"
                           class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition flex items-center gap-1.5">
                            <i class="fas fa-print text-xs"></i> Lihat Laporan
                        </a>
                    </div>
                </div>

                <!-- Breakdown per kategori -->
                <?php if (!empty($cats)): ?>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
                            <?php foreach ($cats as $katNama => $items): ?>
                                <?php
                                $katAvg = array_sum(array_column($items, 'nilai')) / count($items);
                                $katColor = $katAvg >= 8 ? 'border-green-400 bg-green-50' : ($katAvg >= 6 ? 'border-blue-400 bg-blue-50' : 'border-red-400 bg-red-50');
                                $katText  = $katAvg >= 8 ? 'text-green-700' : ($katAvg >= 6 ? 'text-blue-700' : 'text-red-700');
                                ?>
                                <div class="border-l-4 <?= $katColor ?> rounded-r-xl px-4 py-3">
                                    <p class="text-xs font-semibold text-gray-600 mb-1"><?= htmlspecialchars($katNama) ?></p>
                                    <p class="text-2xl font-bold <?= $katText ?>"><?= number_format($katAvg, 1) ?></p>
                                    <p class="text-xs text-gray-400"><?= count($items) ?> aspek</p>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Detail aspek (collapsible) -->
                        <details class="group">
                            <summary class="cursor-pointer text-sm font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1.5 select-none list-none">
                                <i class="fas fa-chevron-right text-xs group-open:rotate-90 transition-transform"></i>
                                Lihat Detail Aspek
                            </summary>
                            <div class="mt-4 space-y-4">
                                <?php foreach ($cats as $katNama => $items): ?>
                                    <div>
                                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2"><?= htmlspecialchars($katNama) ?></p>
                                        <div class="space-y-2">
                                            <?php foreach ($items as $d): ?>
                                                <?php
                                                $dColor = $d['nilai'] >= 8 ? 'text-green-600' : ($d['nilai'] >= 6 ? 'text-blue-600' : 'text-red-600');
                                                $dBg    = $d['nilai'] >= 8 ? 'bg-green-50' : ($d['nilai'] >= 6 ? 'bg-gray-50' : 'bg-red-50');
                                                ?>
                                                <div class="flex items-start justify-between <?= $dBg ?> rounded-lg px-4 py-2.5 gap-3">
                                                    <div class="flex-1">
                                                        <p class="text-sm text-gray-700"><?= htmlspecialchars($d['aspek']) ?></p>
                                                        <?php if (!empty($d['alasan'])): ?>
                                                            <p class="text-xs text-red-500 italic mt-0.5">
                                                                <i class="fas fa-comment-alt mr-1"></i><?= htmlspecialchars($d['alasan']) ?>
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <span class="text-lg font-bold <?= $dColor ?> flex-shrink-0"><?= $d['nilai'] ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    </div>
                <?php endif; ?>

                <!-- Catatan Team Leader -->
                <?php if (!empty($eval['catatan_team_leader'])): ?>
                    <div class="px-6 pb-5">
                        <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
                            <p class="text-xs font-semibold text-amber-700 mb-1"><i class="fas fa-sticky-note mr-1"></i>Catatan Team Leader</p>
                            <p class="text-sm text-amber-900"><?= nl2br(htmlspecialchars($eval['catatan_team_leader'])) ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php $this->endSection(); ?>
