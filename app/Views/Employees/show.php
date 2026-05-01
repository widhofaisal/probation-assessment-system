<?php $this->extend('layouts/main'); $this->section('content'); ?>

<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-8">
            <h1 class="text-3xl font-bold"><?= htmlspecialchars($employee['nama']) ?></h1>
            <p class="text-blue-100 mt-1">NIK: <?= htmlspecialchars($employee['nik']) ?></p>
        </div>

        <!-- Content -->
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Left Column -->
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">NIK</label>
                        <p class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($employee['nik']) ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Nama</label>
                        <p class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($employee['nama']) ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Departemen</label>
                        <p class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($employee['departemen']) ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Posisi</label>
                        <p class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($employee['posisi']) ?></p>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">Email</label>
                        <p class="text-lg text-gray-900"><?= htmlspecialchars($employee['email'] ?? '-') ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Mulai Probation</label>
                        <p class="text-lg text-gray-900"><?= date('d/m/Y', strtotime($employee['mulai_probation'])) ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Akhir Probation</label>
                        <p class="text-lg text-gray-900"><?= date('d/m/Y', strtotime($employee['akhir_probation'])) ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Status</label>
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

            <!-- Actions -->
            <div class="flex gap-3 pt-6 border-t">
                <a href="/employees/<?= $employee['id'] ?>/edit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                    <i class="fas fa-edit mr-2"></i> Edit
                </a>
                <button onclick="deleteEmployee(<?= $employee['id'] ?>)" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium">
                    <i class="fas fa-trash mr-2"></i> Hapus
                </button>
                <a href="/employees" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium">
                    <i class="fas fa-arrow-left mr-2"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function deleteEmployee(id) {
    showConfirm('Hapus karyawan ini? Data yang sudah dihapus tidak bisa dikembalikan.', function() {
        fetch('/employees/' + id, { method: 'DELETE' })
            .then(function(r) {
                if (r.ok) {
                    window.location = '/employees';
                } else {
                    showToast('Gagal menghapus karyawan.', 'error');
                }
            })
            .catch(function() { showToast('Terjadi kesalahan jaringan.', 'error'); });
    });
}
</script>

<?php $this->endSection(); ?>
