<?php
/**
 * Kepala dashboard — dipakai ketiga peran supaya bentuknya seragam.
 *
 * Sengaja jauh lebih ringkas daripada banner lama: dashboard ini isinya
 * ringkasan, jadi ruang layar lebih berguna untuk angkanya daripada untuk
 * sapaan setinggi setengah layar.
 *
 * Dipanggil dengan:
 *   $this->include('partials/dashboard_hero')
 * dan membaca $greeting, $user, $heroChips (list [ikon, teks]), $heroDesc.
 */
$chips = $heroChips ?? [];
?>
<div class="mb-6 rounded-2xl bg-gradient-to-br from-blue-700 via-blue-600 to-green-600 text-white shadow-lg px-6 py-5 sm:px-7 sm:py-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="text-sm text-white/75"><?= htmlspecialchars($greeting ?? 'Selamat Datang') ?>,</p>
            <h1 class="text-2xl sm:text-3xl font-bold mt-0.5 text-white truncate"><?= htmlspecialchars($user['nama'] ?? '') ?></h1>
            <?php if (!empty($chips)): ?>
                <div class="flex flex-wrap items-center gap-2 mt-3">
                    <?php foreach ($chips as [$ikon, $teks]): ?>
                        <span class="inline-flex items-center gap-1.5 bg-white/15 px-2.5 py-1 rounded-lg text-xs font-medium text-white/90">
                            <i class="fas <?= htmlspecialchars($ikon) ?>"></i><?= htmlspecialchars($teks) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="text-right shrink-0">
            <p class="text-xs text-white/70">Hari ini</p>
            <p class="text-sm font-semibold text-white">
                <?= \App\Libraries\RingkasanDashboard::tanggalPendek(date('Y-m-d')) ?>
            </p>
        </div>
    </div>
    <?php if (!empty($heroDesc)): ?>
        <p class="text-white/80 text-sm mt-4 max-w-3xl leading-relaxed"><?= htmlspecialchars($heroDesc) ?></p>
    <?php endif; ?>
</div>
