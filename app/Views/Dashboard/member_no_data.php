<?php $this->extend('layouts/main'); $this->section('content'); ?>

<div class="flex flex-col items-center justify-center min-h-[60vh]">
    <div class="bg-white rounded-2xl shadow-lg p-10 text-center max-w-md">
        <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-user-clock text-yellow-500 text-3xl"></i>
        </div>
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Data Belum Tersedia</h2>
        <p class="text-gray-500 mb-4">
            Data probation Anda belum diinputkan oleh HRD.
        </p>
        <p class="text-sm text-gray-400">
            Silakan hubungi HRD untuk mendaftarkan data karyawan Anda.
        </p>
        <div class="mt-6 p-4 bg-gray-50 rounded-lg text-left">
            <p class="text-xs text-gray-500 font-medium">Akun Anda:</p>
            <p class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($user['nama']) ?></p>
            <p class="text-xs text-gray-500">NIK: <?= htmlspecialchars($user['nik']) ?></p>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>
