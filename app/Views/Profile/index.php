<?php $this->extend('layouts/main'); $this->section('content');
$deptOptions = ['Production', 'Quality Control', 'Warehouse', 'Maintenance', 'Administration', 'Finance', 'Human Resources'];
?>

<div class="max-w-3xl mx-auto">

    <!-- Header Card -->
    <div class="mb-6 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl p-6 shadow-lg">
        <div class="flex items-center gap-5">
            <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0">
                <span class="text-white font-bold text-3xl">
                    <?= strtoupper(substr($user['nama'], 0, 1)) ?>
                </span>
            </div>
            <div>
                <h1 class="text-2xl font-bold"><?= htmlspecialchars($user['nama']) ?></h1>
                <p class="text-blue-100 text-sm mt-1"><?= htmlspecialchars($roleLabel) ?></p>
                <p class="text-blue-100 text-sm">NIK: <?= htmlspecialchars($user['nik']) ?></p>
            </div>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-900 mb-5 flex items-center gap-2">
            <i class="fas fa-user-edit text-blue-600"></i>
            Informasi Pribadi
        </h3>

        <form action="/profile/update" method="POST" class="space-y-5">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- NIK (readonly) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">NIK</label>
                    <input type="text" value="<?= htmlspecialchars($user['nik']) ?>" disabled
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 text-sm cursor-not-allowed">
                </div>

                <!-- Role (readonly) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Role</label>
                    <input type="text" value="<?= htmlspecialchars($roleLabel) ?>" disabled
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 text-sm cursor-not-allowed">
                </div>

                <!-- Nama -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" name="nama" value="<?= htmlspecialchars($user['nama']) ?>" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-sm transition">
                </div>

                <!-- Email -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                           placeholder="contoh@email.com"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-sm transition">
                </div>

                <!-- Departemen (dropdown) -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Departemen</label>
                    <select name="departemen"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-sm transition">
                        <option value="">-- Pilih Departemen --</option>
                        <?php foreach ($deptOptions as $d): ?>
                            <option value="<?= $d ?>" <?= ($user['departemen'] ?? '') === $d ? 'selected' : '' ?>><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition">
                    <i class="fas fa-save mr-2"></i> Simpan Perubahan
                </button>
                <a href="/profile/change-password"
                   class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition">
                    <i class="fas fa-lock mr-2"></i> Ganti Password
                </a>
            </div>
        </form>
    </div>

    <!-- Account Info (readonly) -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
            <i class="fas fa-info-circle text-gray-400"></i>
            Informasi Akun
        </h3>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-gray-500">Bergabung</p>
                <p class="font-semibold text-gray-900">
                    <?= $user['created_at'] ? date('d M Y', strtotime($user['created_at'])) : '-' ?>
                </p>
            </div>
            <div>
                <p class="text-gray-500">Terakhir Diperbarui</p>
                <p class="font-semibold text-gray-900">
                    <?= $user['updated_at'] ? date('d M Y, H:i', strtotime($user['updated_at'])) : '-' ?>
                </p>
            </div>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>
