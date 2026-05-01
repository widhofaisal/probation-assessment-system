<?php $this->extend('layouts/blank'); $this->section('content'); ?>

<div class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-lg p-8 md:p-10">
            <!-- Logo -->
            <div class="flex justify-center mb-6">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-green-600 rounded-xl flex items-center justify-center">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
            </div>

            <!-- Title -->
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Sistem Penilaian Probation</h1>
                <p class="text-gray-600">PT Sumber Masanda Jaya</p>
            </div>

            <!-- Error Message -->
            <?php if ($errorMsg): ?>
                <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                    <?= htmlspecialchars($errorMsg) ?>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form class="space-y-5" action="/auth/login" method="POST">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">NIK / Email</label>
                    <input
                        type="text"
                        name="nik"
                        placeholder="Masukkan NIK atau Email"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input
                        type="password"
                        name="password"
                        placeholder="Masukkan Password"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
                    />
                </div>

                <button
                    type="submit"
                    class="w-full px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition"
                >
                    Login
                </button>
            </form>

            <!-- Demo hint -->
            <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                <p class="text-sm text-blue-800 mb-2"><strong>Demo Accounts:</strong></p>
                <div class="text-xs text-blue-700 space-y-1">
                    <p>• HRD: <code class="bg-white px-2 py-1 rounded">HRD001</code> / <code class="bg-white px-2 py-1 rounded">password123</code></p>
                    <p>• Team Leader: <code class="bg-white px-2 py-1 rounded">TL001</code> / <code class="bg-white px-2 py-1 rounded">password123</code></p>
                    <p>• Probationary Employee: <code class="bg-white px-2 py-1 rounded">EMP001</code> / <code class="bg-white px-2 py-1 rounded">password123</code></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>
