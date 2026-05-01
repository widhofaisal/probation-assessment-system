<?php $this->extend('layouts/main'); $this->section('content'); ?>

<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-5 flex items-center gap-2">
            <i class="fas fa-lock text-yellow-500"></i>
            Ganti Password
        </h3>

        <form action="/profile/update-password" method="POST" class="space-y-5">
            <?= csrf_field() ?>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Password Saat Ini <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="password" name="current_password" id="currentPassword" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-sm transition pr-10">
                    <button type="button" onclick="togglePassword('currentPassword', this)"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-eye text-sm"></i>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Password Baru <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="password" name="new_password" id="newPassword" required minlength="8"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-sm transition pr-10">
                    <button type="button" onclick="togglePassword('newPassword', this)"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-eye text-sm"></i>
                    </button>
                </div>
                <p class="text-xs text-gray-400 mt-1">Minimal 8 karakter</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Konfirmasi Password Baru <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="password" name="confirm_password" id="confirmPassword" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-sm transition pr-10">
                    <button type="button" onclick="togglePassword('confirmPassword', this)"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-eye text-sm"></i>
                    </button>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition">
                    <i class="fas fa-save mr-2"></i> Simpan Password Baru
                </button>
                <a href="/profile"
                   class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition">
                    <i class="fas fa-arrow-left mr-2"></i> Batal
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>

<?php $this->endSection(); ?>
