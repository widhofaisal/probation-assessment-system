<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    <title><?= $title ? htmlspecialchars($title) . ' | ' : '' ?>Sistem Penilaian Probation</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' };</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?= $this->include('partials/theme') ?>
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        .sidebar-active { background-color: rgba(0,102,204,0.1); border-left: 3px solid #0066cc; }
        @keyframes toastIn  { from { transform: translateX(110%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes toastOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(110%); opacity: 0; } }
        .toast-enter { animation: toastIn  0.35s cubic-bezier(0.22,1,0.36,1) forwards; }
        .toast-exit  { animation: toastOut 0.28s ease-in forwards; }
        @keyframes toastProgress { from { width: 100%; } to { width: 0%; } }
        .toast-bar { animation: toastProgress var(--dur, 4s) linear forwards; }
        @keyframes modalIn { from { opacity: 0; transform: scale(0.95) translateY(-8px); } to { opacity: 1; transform: scale(1) translateY(0); } }
        .modal-enter { animation: modalIn 0.2s ease-out forwards; }
    </style>
</head>
<body class="app-body bg-gray-50">

<!-- ===== TOAST CONTAINER ===== -->
<div id="toastContainer" class="fixed top-4 right-4 z-[9999] flex flex-col gap-2.5 pointer-events-none" style="width:360px;max-width:calc(100vw - 2rem)"></div>

<!-- ===== CONFIRM MODAL ===== -->
<div id="confirmModal" class="hidden fixed inset-0 z-[9998] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" id="confirmBackdrop"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 modal-enter">
        <div class="flex items-start gap-4 mb-6">
            <div id="confirmIconWrap" class="w-11 h-11 rounded-full flex items-center justify-center flex-shrink-0 bg-red-100">
                <i id="confirmIcon" class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <div class="flex-1 pt-0.5">
                <h3 id="confirmTitle" class="font-bold text-gray-900 mb-1">Konfirmasi</h3>
                <p id="confirmMessage" class="text-sm text-gray-600 leading-relaxed">Apakah Anda yakin?</p>
            </div>
        </div>
        <div class="flex gap-3 justify-end">
            <button id="confirmCancel" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold text-sm transition">
                Batal
            </button>
            <button id="confirmOk" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl font-semibold text-sm transition flex items-center gap-2">
                <i class="fas fa-trash text-xs"></i> Hapus
            </button>
        </div>
    </div>
</div>

<div class="flex h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-lg hidden md:flex flex-col">
        <div class="p-6 border-b">
            <div class="flex items-center gap-2">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center overflow-hidden flex-shrink-0">
                    <img src="/favicon.svg" alt="Logo" class="w-10 h-10">
                </div>
                <div>
                    <h1 class="text-sm font-bold text-gray-900 leading-tight">Sistem Penilaian<br>Probation</h1>
                    <p class="text-xs text-gray-500">PT Sumber Masanda Jaya</p>
                </div>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto p-4">
            <div class="space-y-2">
                <?php if (session()->get('role') === 'hrd'): ?>
                    <a href="/dashboard/hrd" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-100 transition <?= (current_url(true)->getPath() === '/dashboard/hrd') ? 'sidebar-active' : '' ?>">
                        <i class="fas fa-chart-line text-blue-600"></i>
                        <span class="font-medium text-gray-700">Dashboard</span>
                    </a>
                    <a href="/employees" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-100 transition <?= str_starts_with(current_url(true)->getPath(), '/employees') ? 'sidebar-active' : '' ?>">
                        <i class="fas fa-users text-blue-600"></i>
                        <span class="font-medium text-gray-700">Data Team Member</span>
                    </a>
                    <a href="/evaluations" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-100 transition <?= str_starts_with(current_url(true)->getPath(), '/evaluations') ? 'sidebar-active' : '' ?>">
                        <i class="fas fa-file-alt text-blue-600"></i>
                        <span class="font-medium text-gray-700">Daftar Penilaian</span>
                    </a>
                    <a href="/users" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-100 transition <?= str_starts_with(current_url(true)->getPath(), '/users') ? 'sidebar-active' : '' ?>">
                        <i class="fas fa-user-tie text-blue-600"></i>
                        <span class="font-medium text-gray-700">Data Team Leader</span>
                    </a>
                <?php elseif (session()->get('role') === 'team-leader'): ?>
                    <a href="/dashboard/team-leader" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-100 transition">
                        <i class="fas fa-chart-line text-blue-600"></i>
                        <span class="font-medium text-gray-700">Dashboard</span>
                    </a>
                    <a href="/team" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-100 transition">
                        <i class="fas fa-users text-blue-600"></i>
                        <span class="font-medium text-gray-700">Tim Saya</span>
                    </a>
                <?php else: ?>
                    <a href="/dashboard/probationary" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-100 transition">
                        <i class="fas fa-chart-line text-blue-600"></i>
                        <span class="font-medium text-gray-700">Dashboard</span>
                    </a>
                    <?php $belumDilihat = ack_unviewed_count(); ?>
                    <a href="/evaluations/my" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-100 transition">
                        <i class="fas fa-file-alt text-blue-600"></i>
                        <span class="font-medium text-gray-700">Hasil Evaluasi</span>
                        <?php if ($belumDilihat > 0): ?>
                            <span class="ml-auto min-w-[20px] h-5 px-1.5 bg-red-600 text-white text-xs font-bold rounded-full flex items-center justify-center"
                                  title="<?= $belumDilihat ?> hasil penilaian belum Anda buka"><?= $belumDilihat ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            </div>
        </nav>

        <!-- User Profile -->
        <div class="p-4 border-t">
            <div class="bg-gray-50 rounded-lg p-3 mb-3">
                <p class="text-xs text-gray-500">Logged in as</p>
                <p class="font-semibold text-gray-900 text-sm"><?= htmlspecialchars(session()->get('nama')) ?></p>
                <p class="text-xs text-gray-600"><?= session()->get('posisi') ?></p>
            </div>
            <a href="/auth/logout" class="w-full px-4 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition text-sm font-medium flex items-center gap-2">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Header -->
        <header class="bg-white shadow-sm px-6 py-4 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($title ?? 'Dashboard') ?></h2>
            </div>
            <div class="flex items-center gap-3">
                <!-- Dark Mode Toggle -->
                <button type="button" onclick="toggleTheme()" data-theme-toggle
                        title="Ganti ke Mode Gelap" aria-label="Ganti ke Mode Gelap" aria-pressed="false"
                        class="w-9 h-9 rounded-xl hover:bg-gray-100 text-gray-500 flex items-center justify-center transition cursor-pointer">
                    <i class="fas fa-moon" data-theme-icon></i>
                </button>

                <!-- Profile Dropdown -->
                <?php
                $roleLabels = [
                    'hrd' => 'HRD',
                    'team-leader' => 'Team Leader',
                    'probationary-employee' => 'Probationary Employee',
                ];
                $roleLabel = $roleLabels[session()->get('role')] ?? session()->get('role');
                $initials = strtoupper(substr(session()->get('nama'), 0, 1));
                ?>
                <div class="relative" id="profileDropdownWrapper">
                    <button onclick="toggleProfileDropdown()" class="flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-gray-100 transition cursor-pointer">
                        <div class="text-right hidden sm:block">
                            <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars(session()->get('nama')) ?></p>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars($roleLabel) ?></p>
                        </div>
                        <div class="w-9 h-9 bg-gradient-to-br from-blue-600 to-green-600 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-white font-bold text-sm"><?= $initials ?></span>
                        </div>
                        <i class="fas fa-chevron-down text-gray-400 text-xs hidden sm:block"></i>
                    </button>
                    <div id="profileDropdown" class="hidden absolute right-0 top-full mt-2 w-56 bg-white rounded-xl shadow-lg border border-gray-100 z-50 overflow-hidden">
                        <div class="px-4 py-3 border-b bg-gray-50">
                            <p class="font-semibold text-gray-900 text-sm"><?= htmlspecialchars(session()->get('nama')) ?></p>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars(session()->get('nik')) ?> · <?= htmlspecialchars($roleLabel) ?></p>
                        </div>
                        <div class="py-1">
                            <a href="/profile" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-100 transition">
                                <i class="fas fa-user-circle text-blue-500 w-4"></i> Profil Saya
                            </a>
                            <a href="/profile/change-password" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-100 transition">
                                <i class="fas fa-lock text-yellow-500 w-4"></i> Ganti Password
                            </a>
                            <button type="button" onclick="toggleTheme()" data-theme-toggle
                                    class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-100 transition cursor-pointer">
                                <i class="fas fa-moon text-indigo-400 w-4" data-theme-icon></i>
                                <span data-theme-label>Mode Gelap</span>
                            </button>
                        </div>
                        <div class="border-t py-1">
                            <a href="/auth/logout" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition">
                                <i class="fas fa-sign-out-alt w-4"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Mobile menu button -->
                <button onclick="document.querySelector('.mobile-menu').classList.toggle('hidden')" class="md:hidden p-2 hover:bg-gray-100 rounded-lg">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 overflow-auto p-6">
            <?= $this->renderSection('content') ?>
        </main>
    </div>

    <!-- Mobile Menu -->
    <div class="mobile-menu hidden absolute top-0 left-0 right-0 bg-white shadow-lg md:hidden z-50">
        <nav class="p-4 space-y-2">
            <a href="/auth/logout" class="block px-4 py-2 text-red-600 hover:bg-gray-100 rounded-lg">
                <i class="fas fa-sign-out-alt mr-2"></i> Logout
            </a>
        </nav>
    </div>
</div>

<script>
// ===== PROFILE DROPDOWN =====
function toggleProfileDropdown() {
    document.getElementById('profileDropdown').classList.toggle('hidden');
}
document.addEventListener('click', function(e) {
    const w = document.getElementById('profileDropdownWrapper');
    if (w && !w.contains(e.target)) document.getElementById('profileDropdown').classList.add('hidden');
});

// ===== TOAST SYSTEM =====
function showToast(message, type, duration) {
    type = type || 'success';
    duration = (duration === undefined) ? 4000 : duration;
    const cfg = {
        success: { border: 'border-green-500', bar: 'bg-green-500', icon: 'fa-check-circle', ic: 'text-green-500' },
        error:   { border: 'border-red-500',   bar: 'bg-red-500',   icon: 'fa-times-circle', ic: 'text-red-500'   },
        warning: { border: 'border-amber-500', bar: 'bg-amber-500', icon: 'fa-exclamation-triangle', ic: 'text-amber-500' },
        info:    { border: 'border-blue-500',  bar: 'bg-blue-500',  icon: 'fa-info-circle',  ic: 'text-blue-500'  },
    };
    const c = cfg[type] || cfg.info;
    const t = document.createElement('div');
    t.className = 'toast-enter pointer-events-auto bg-white rounded-xl shadow-xl border-l-4 ' + c.border + ' overflow-hidden';
    t.setAttribute('data-toast', '');
    const barHtml = duration > 0
        ? '<div class="h-1 ' + c.bar + ' opacity-50 toast-bar" style="--dur:' + (duration / 1000) + 's"></div>'
        : '<div class="h-1 ' + c.bar + ' opacity-30"></div>';
    t.innerHTML =
        '<div class="flex items-start gap-3 p-4">' +
            '<i class="fas ' + c.icon + ' ' + c.ic + ' text-lg mt-0.5 flex-shrink-0"></i>' +
            '<div class="text-sm text-gray-800 flex-1 leading-relaxed">' + message + '</div>' +
            '<button onclick="dismissToast(this.closest(\'[data-toast]\'))" class="text-gray-400 hover:text-gray-600 ml-1 flex-shrink-0">' +
                '<i class="fas fa-times text-xs"></i>' +
            '</button>' +
        '</div>' + barHtml;
    document.getElementById('toastContainer').appendChild(t);
    if (duration > 0) t._timer = setTimeout(function() { dismissToast(t); }, duration);
    return t;
}

function dismissToast(t) {
    if (!t || t._gone) return;
    t._gone = true;
    clearTimeout(t._timer);
    t.classList.remove('toast-enter');
    t.classList.add('toast-exit');
    setTimeout(function() { t.remove(); }, 300);
}

// ===== CONFIRM MODAL =====
// opts (opsional): { tone: 'red'|'amber', faceIcon, okIcon } — dipakai agar modal
// ini bisa dipakai untuk peringatan biasa, bukan cuma konfirmasi hapus.
var _confirmCb = null;
function showConfirm(message, onConfirm, title, confirmLabel, opts) {
    opts = opts || {};
    var tone = opts.tone === 'amber' ? 'amber' : 'red';

    document.getElementById('confirmTitle').textContent = title || 'Konfirmasi Hapus';
    document.getElementById('confirmMessage').textContent = message;

    document.getElementById('confirmIconWrap').className =
        'w-11 h-11 rounded-full flex items-center justify-center flex-shrink-0 bg-' + tone + '-100';
    document.getElementById('confirmIcon').className =
        'fas ' + (opts.faceIcon || 'fa-exclamation-triangle') + ' text-' + tone + '-600';

    var ok = document.getElementById('confirmOk');
    ok.className = 'px-5 py-2.5 bg-' + tone + '-600 hover:bg-' + tone + '-700 text-white rounded-xl ' +
                   'font-semibold text-sm transition flex items-center gap-2';
    ok.innerHTML = '<i class="fas ' + (opts.okIcon || 'fa-trash') + ' text-xs"></i> ' +
                   (confirmLabel || 'Hapus');

    _confirmCb = onConfirm;
    document.getElementById('confirmModal').classList.remove('hidden');
}
document.getElementById('confirmOk').addEventListener('click', function() {
    document.getElementById('confirmModal').classList.add('hidden');
    if (_confirmCb) _confirmCb();
    _confirmCb = null;
});
document.getElementById('confirmCancel').addEventListener('click', function() {
    document.getElementById('confirmModal').classList.add('hidden');
    _confirmCb = null;
});
document.getElementById('confirmBackdrop').addEventListener('click', function() {
    document.getElementById('confirmModal').classList.add('hidden');
    _confirmCb = null;
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.getElementById('confirmModal').classList.add('hidden');
        _confirmCb = null;
    }
});

// ===== PDF DOWNLOAD WITH TOAST =====
// Semua tombol PDF di aplikasi memanggil fungsi ini, jadi pemeriksaan Keputusan
// HRD cukup dipasang di sini — berlaku untuk HRD, Team Leader, maupun Team Member.
function pdfDownload(url) {
    cekKeputusanHrd(url).then(function (perluKonfirmasi) {
        if (!perluKonfirmasi) {
            mulaiUnduhPdf(url);
            return;
        }

        showConfirm(
            'HRD belum memberikan keputusan untuk penilaian ke-2 ini. PDF tetap bisa ' +
            'diunduh, tetapi bagian "(Diisi oleh Dept. HRD)" akan tercetak kosong.',
            function () { mulaiUnduhPdf(url); },
            'Keputusan HRD belum diisi',
            'Tetap Unduh',
            { tone: 'amber', faceIcon: 'fa-gavel', okIcon: 'fa-file-pdf' }
        );
    });
}

// Tanya server apakah PDF ini memuat lembar ke-2 yang belum diputuskan HRD.
// URL-nya sendiri yang menentukan pertanyaannya: /reports/pdf/<penilaian> atau
// /reports/pdf-all/<team member>. Kalau pengecekan gagal, unduhan tetap jalan —
// peringatan ini tidak boleh jadi penghalang.
function cekKeputusanHrd(url) {
    var m = String(url).match(/\/reports\/pdf(-all)?\/(\d+)/);
    if (!m) return Promise.resolve(false);

    var query = m[1] ? 'employee=' + m[2] : 'eval=' + m[2];

    return fetch('/evaluations/keputusan-status?' + query, { credentials: 'same-origin' })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (j) { return !!(j && j.perlu_konfirmasi); })
        .catch(function () { return false; });
}

// Stays on current page; fetches PDF in background; opens new tab only when ready.
function mulaiUnduhPdf(url) {
    var loadingToast = showToast('Sedang memproses PDF, mohon tunggu...', 'info', 0);

    fetch(url, { credentials: 'same-origin' })
        .then(function(response) {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            // Penolakan akses dijawab server dengan redirect ke halaman biasa, jadi
            // yang sampai ke sini HTML ber-status 200 — bukan berkas PDF. Tanpa
            // pemeriksaan ini halaman tersebut ikut dibuka di tab baru dan pesan
            // errornya muncul di tempat yang tidak semestinya.
            var tipe = response.headers.get('content-type') || '';
            if (tipe.indexOf('application/pdf') === -1) throw new Error('bukan-pdf');
            return response.blob();
        })
        .then(function(blob) {
            dismissToast(loadingToast);
            var blobUrl = URL.createObjectURL(blob);
            var newWin = window.open(blobUrl, '_blank');
            if (!newWin) {
                // Popup blocked — fallback to programmatic link click
                var a = document.createElement('a');
                a.href = blobUrl;
                a.target = '_blank';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }
            showToast('PDF berhasil dibuka di tab baru!', 'success', 4000);
        })
        .catch(function(e) {
            dismissToast(loadingToast);
            showToast(
                (e && e.message === 'bukan-pdf')
                    ? 'PDF tidak bisa dibuka — permintaan ditolak server (akses ditolak ' +
                      'atau data penilaian tidak ditemukan). Coba muat ulang halaman.'
                    : 'Gagal menghasilkan PDF. Silakan coba lagi.',
                'error', 7000
            );
        });
}

// ===== FLASH MESSAGES =====
<?php if (session()->has('success')): ?>
showToast(<?= json_encode(session()->getFlashdata('success')) ?>, 'success');
<?php endif; ?>
<?php if (session()->has('error')): ?>
showToast(<?= json_encode(session()->getFlashdata('error')) ?>, 'error', 6000);
<?php endif; ?>
<?php if (session()->has('errors')): ?>
var _errs = <?= json_encode(array_values((array)(session()->getFlashdata('errors') ?? []))) ?>;
if (_errs.length) showToast(_errs.join('<br>'), 'error', 7000);
<?php endif; ?>
</script>
</body>
</html>
