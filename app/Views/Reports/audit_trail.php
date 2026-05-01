<?php $this->extend('layouts/main'); $this->section('content'); ?>

<div class="max-w-6xl mx-auto">
    <div class="bg-white rounded-xl shadow">
        <div class="px-6 py-4 border-b">
            <h3 class="text-lg font-bold text-gray-900">Audit Trail - Riwayat Perubahan Data</h3>
            <p class="text-sm text-gray-600 mt-1">Semua perubahan data dicatat untuk keperluan compliance dan audit</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b">
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Tanggal & Waktu</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">User</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Aksi</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Tabel</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Record ID</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Keterangan</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($logs)): ?>
                        <?php foreach ($logs as $log): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap">
                                    <?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($log['user_nama'] ?? 'System') ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars('User ID: ' . $log['user_id']) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $actionClass = match($log['action']) {
                                        'CREATE' => 'bg-green-100 text-green-700',
                                        'UPDATE' => 'bg-blue-100 text-blue-700',
                                        'DELETE' => 'bg-red-100 text-red-700',
                                        'LOGIN' => 'bg-purple-100 text-purple-700',
                                        'LOGOUT' => 'bg-gray-100 text-gray-700',
                                        default => 'bg-gray-100 text-gray-700'
                                    };
                                    ?>
                                    <span class="px-2 py-1 rounded text-xs font-semibold <?= $actionClass ?>">
                                        <?= htmlspecialchars($log['action']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    <code class="bg-gray-100 px-2 py-1 rounded text-xs">
                                        <?= htmlspecialchars($log['table_name']) ?>
                                    </code>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 font-medium">
                                    <?= htmlspecialchars($log['record_id']) ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    <?= htmlspecialchars($log['description']) ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <code class="text-xs bg-gray-100 px-2 py-1 rounded">
                                        <?= htmlspecialchars($log['ip_address']) ?>
                                    </code>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                <i class="fas fa-inbox text-3xl mb-2"></i>
                                <p>Tidak ada audit log</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
        <div class="bg-white rounded-xl shadow p-4 border-t-4 border-green-500">
            <p class="text-sm text-gray-600">Total Aksi Dibuat</p>
            <p class="text-2xl font-bold text-green-600"><?= count(array_filter($logs, fn($l) => $l['action'] === 'CREATE')) ?></p>
        </div>
        <div class="bg-white rounded-xl shadow p-4 border-t-4 border-blue-500">
            <p class="text-sm text-gray-600">Total Aksi Diubah</p>
            <p class="text-2xl font-bold text-blue-600"><?= count(array_filter($logs, fn($l) => $l['action'] === 'UPDATE')) ?></p>
        </div>
        <div class="bg-white rounded-xl shadow p-4 border-t-4 border-red-500">
            <p class="text-sm text-gray-600">Total Aksi Dihapus</p>
            <p class="text-2xl font-bold text-red-600"><?= count(array_filter($logs, fn($l) => $l['action'] === 'DELETE')) ?></p>
        </div>
        <div class="bg-white rounded-xl shadow p-4 border-t-4 border-purple-500">
            <p class="text-sm text-gray-600">Total Login</p>
            <p class="text-2xl font-bold text-purple-600"><?= count(array_filter($logs, fn($l) => $l['action'] === 'LOGIN')) ?></p>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>
