<?php
/**
 * Dark Mode — dimuat di dalam <head> semua layout.
 *
 * Berisi 3 bagian:
 *   1. Skrip pre-paint  : memasang class .dark sebelum halaman digambar (anti-kedip)
 *   2. Stylesheet       : peta warna Tailwind "light" -> "dark"
 *   3. Skrip toggle     : fungsi toggleTheme() + sinkronisasi ikon tombol
 *
 * Catatan: semua view memakai utility Tailwind versi terang (bg-white, text-gray-900, ...)
 * tanpa varian dark:. Karena itu tema gelap dikerjakan lewat lapisan override terpusat
 * di bawah ini — selector html.dark selalu lebih spesifik daripada utility Tailwind,
 * sehingga tidak perlu !important dan tidak perlu menyentuh tiap file view.
 */
?>
<script>
/* Pre-paint: jalan sebelum <body> dirender supaya tidak ada kedipan putih. */
(function () {
    try {
        var saved = localStorage.getItem('theme');
        if (saved !== 'dark' && saved !== 'light') {
            saved = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        if (saved === 'dark') document.documentElement.classList.add('dark');
    } catch (e) {}
})();
</script>

<style>
/* ============================================================================
   DARK MODE
   Semua aturan diberi prefix html.dark sehingga tampilan terang tidak berubah.
   ========================================================================= */
html.dark {
    color-scheme: dark;

    --dk-bg:          #0f172a;  /* latar halaman            */
    --dk-surface:     #1e293b;  /* kartu / panel (bg-white) */
    --dk-raised:      #263449;  /* panel di dalam kartu     */
    --dk-raised-2:    #334155;
    --dk-border:      #334155;
    --dk-text:        #e8eef7;
    --dk-text-2:      #cbd5e1;
    --dk-text-3:      #aab7c9;
    --dk-text-4:      #94a3b8;
    --dk-text-5:      #6d7c94;
}

/* Transisi halus, hanya aktif sesaat ketika tombol toggle ditekan. */
html.theme-transition,
html.theme-transition *,
html.theme-transition *::before,
html.theme-transition *::after {
    transition: background-color .25s ease, border-color .25s ease, color .25s ease;
}

/* ---------- Latar halaman ------------------------------------------------ */
html.dark body.app-body { background-color: var(--dk-bg); color: var(--dk-text); }

/* ---------- Permukaan netral --------------------------------------------- */
html.dark .bg-white    { background-color: var(--dk-surface); }
html.dark .bg-gray-50  { background-color: #223047; }
html.dark .bg-gray-100 { background-color: var(--dk-raised); }
html.dark .bg-gray-200 { background-color: var(--dk-raised-2); }
html.dark .bg-gray-300 { background-color: #3d4c63; }

html.dark .hover\:bg-white:hover    { background-color: #2b3b52; }
html.dark .hover\:bg-gray-50:hover  { background-color: #223047; }
html.dark .hover\:bg-gray-100:hover { background-color: #2b3b52; }
html.dark .hover\:bg-gray-200:hover { background-color: #34455e; }
html.dark .hover\:bg-gray-300:hover { background-color: #3d4f6a; }

/* ---------- Teks netral --------------------------------------------------- */
html.dark .text-gray-900 { color: var(--dk-text); }
html.dark .text-gray-800 { color: #dfe7f2; }
html.dark .text-gray-700 { color: var(--dk-text-2); }
html.dark .text-gray-600 { color: var(--dk-text-3); }
html.dark .text-gray-500 { color: var(--dk-text-4); }
html.dark .text-gray-400 { color: #8494ab; }
html.dark .text-gray-300 { color: var(--dk-text-5); }
html.dark .text-gray-200 { color: var(--dk-text-4); }

html.dark .hover\:text-gray-600:hover { color: var(--dk-text-2); }
html.dark .hover\:text-gray-700:hover { color: var(--dk-text); }
html.dark .hover\:text-gray-900:hover { color: #ffffff; }

/* ---------- Garis tepi ---------------------------------------------------- */
/* Preflight Tailwind memberi semua elemen border-color abu terang (dipakai oleh
   class polos seperti border-b / border-t / divide-y). Ditimpa global, lalu
   border berwarna eksplisit dikembalikan di bawahnya (spesifisitas lebih tinggi). */
html.dark *,
html.dark *::before,
html.dark *::after { border-color: var(--dk-border); }

html.dark .border-gray-100 { border-color: #2b3a52; }
html.dark .border-gray-200 { border-color: var(--dk-border); }
html.dark .border-gray-300 { border-color: #3a4a63; }
html.dark .border-white    { border-color: #ffffff; }
html.dark .border-white\/20 { border-color: rgb(255 255 255 / .2); }

html.dark .border-blue-200   { border-color: rgb(59 130 246 / .35); }
html.dark .border-blue-400   { border-color: #60a5fa; }
html.dark .border-blue-500   { border-color: #3b82f6; }
html.dark .border-green-200  { border-color: rgb(34 197 94 / .35); }
html.dark .border-green-400  { border-color: #4ade80; }
html.dark .border-green-500  { border-color: #22c55e; }
html.dark .border-red-200    { border-color: rgb(239 68 68 / .35); }
html.dark .border-red-400    { border-color: #f87171; }
html.dark .border-red-500    { border-color: #ef4444; }
html.dark .border-amber-200  { border-color: rgb(245 158 11 / .35); }
html.dark .border-amber-500  { border-color: #f59e0b; }
html.dark .border-yellow-500 { border-color: #eab308; }
html.dark .border-purple-500 { border-color: #a855f7; }
html.dark .border-orange-500 { border-color: #f97316; }

/* ---------- Latar berwarna lembut (badge, chip, alert) -------------------- */
html.dark .bg-blue-50     { background-color: rgb(59 130 246 / .10); }
html.dark .bg-blue-100    { background-color: rgb(59 130 246 / .18); }
html.dark .bg-blue-50\/50 { background-color: rgb(59 130 246 / .08); }
html.dark .bg-green-50    { background-color: rgb(34 197 94 / .10); }
html.dark .bg-green-100   { background-color: rgb(34 197 94 / .18); }
html.dark .bg-red-50      { background-color: rgb(239 68 68 / .10); }
html.dark .bg-red-100     { background-color: rgb(239 68 68 / .18); }
html.dark .bg-yellow-50   { background-color: rgb(234 179 8 / .12); }
html.dark .bg-yellow-100  { background-color: rgb(234 179 8 / .20); }
html.dark .bg-amber-50    { background-color: rgb(245 158 11 / .12); }
html.dark .bg-amber-100   { background-color: rgb(245 158 11 / .20); }
html.dark .bg-purple-50   { background-color: rgb(168 85 247 / .12); }
html.dark .bg-purple-100  { background-color: rgb(168 85 247 / .20); }
html.dark .bg-orange-50   { background-color: rgb(249 115 22 / .12); }
html.dark .bg-orange-100  { background-color: rgb(249 115 22 / .20); }

html.dark .hover\:bg-blue-100:hover   { background-color: rgb(59 130 246 / .28); }
html.dark .hover\:bg-blue-200:hover   { background-color: rgb(59 130 246 / .34); }
html.dark .hover\:bg-green-100:hover  { background-color: rgb(34 197 94 / .28); }
html.dark .hover\:bg-red-50:hover     { background-color: rgb(239 68 68 / .18); }
html.dark .hover\:bg-red-100:hover    { background-color: rgb(239 68 68 / .28); }
html.dark .hover\:bg-yellow-100:hover { background-color: rgb(234 179 8 / .28); }
html.dark .hover\:bg-purple-100:hover { background-color: rgb(168 85 247 / .28); }
html.dark .hover\:bg-purple-200:hover { background-color: rgb(168 85 247 / .34); }

/* ---------- Teks berwarna (dicerahkan agar kontras di latar gelap) -------- */
html.dark .text-blue-500   { color: #60a5fa; }
html.dark .text-blue-600   { color: #60a5fa; }
html.dark .text-blue-700   { color: #7fb8ff; }
html.dark .text-blue-800   { color: #93c5fd; }
html.dark .text-blue-900   { color: #bfdbfe; }
html.dark .text-green-500  { color: #4ade80; }
html.dark .text-green-600  { color: #4ade80; }
html.dark .text-green-700  { color: #6ee7a5; }
html.dark .text-green-800  { color: #86efac; }
html.dark .text-red-500    { color: #f87171; }
html.dark .text-red-600    { color: #f87171; }
html.dark .text-red-700    { color: #fca5a5; }
html.dark .text-red-800    { color: #fecaca; }
html.dark .text-yellow-500 { color: #facc15; }
html.dark .text-yellow-600 { color: #fbbf24; }
html.dark .text-yellow-700 { color: #fcd34d; }
html.dark .text-amber-500  { color: #fbbf24; }
html.dark .text-amber-700  { color: #fcd34d; }
html.dark .text-amber-800  { color: #fde68a; }
html.dark .text-amber-900  { color: #fef3c7; }
html.dark .text-purple-600 { color: #c084fc; }
html.dark .text-purple-700 { color: #d8b4fe; }
html.dark .text-orange-600 { color: #fb923c; }
html.dark .text-orange-700 { color: #fdba74; }

html.dark .hover\:text-blue-700:hover { color: #93c5fd; }
html.dark .hover\:text-blue-800:hover { color: #bfdbfe; }
html.dark .hover\:text-red-700:hover  { color: #fca5a5; }

/* ---------- Gradien terang (banner ringan) ------------------------------- */
html.dark .from-gray-50 {
    --tw-gradient-from: #1c2740 var(--tw-gradient-from-position);
    --tw-gradient-to: rgb(28 39 64 / 0) var(--tw-gradient-to-position);
    --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to);
}
html.dark .from-blue-50 {
    --tw-gradient-from: #17263f var(--tw-gradient-from-position);
    --tw-gradient-to: rgb(23 38 63 / 0) var(--tw-gradient-to-position);
    --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to);
}
html.dark .from-green-50 {
    --tw-gradient-from: #142e26 var(--tw-gradient-from-position);
    --tw-gradient-to: rgb(20 46 38 / 0) var(--tw-gradient-to-position);
    --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to);
}
html.dark .from-red-50 {
    --tw-gradient-from: #331d24 var(--tw-gradient-from-position);
    --tw-gradient-to: rgb(51 29 36 / 0) var(--tw-gradient-to-position);
    --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to);
}
html.dark .to-blue-50    { --tw-gradient-to: #17263f var(--tw-gradient-to-position); }
html.dark .to-sky-50     { --tw-gradient-to: #14283c var(--tw-gradient-to-position); }
html.dark .to-emerald-50 { --tw-gradient-to: #122e28 var(--tw-gradient-to-position); }
html.dark .to-rose-50    { --tw-gradient-to: #331d27 var(--tw-gradient-to-position); }

/* ---------- Elemen form --------------------------------------------------- */
html.dark input:not([type="checkbox"]):not([type="radio"]):not([type="range"]),
html.dark select,
html.dark textarea {
    background-color: #16202f;
    color: var(--dk-text);
    border-color: #3a4a63;
}
html.dark input::placeholder,
html.dark textarea::placeholder { color: var(--dk-text-5); }
html.dark option { background-color: var(--dk-surface); color: var(--dk-text); }
html.dark input[type="checkbox"],
html.dark input[type="radio"] { accent-color: #3b82f6; }
html.dark input[type="date"]::-webkit-calendar-picker-indicator,
html.dark input[type="month"]::-webkit-calendar-picker-indicator { filter: invert(1) opacity(.7); }
html.dark input:disabled,
html.dark select:disabled,
html.dark textarea:disabled { background-color: #131b28; color: var(--dk-text-5); }

/* ---------- Bayangan (dipertegas agar kartu tetap terbaca) ---------------- */
html.dark .shadow-sm  { --tw-shadow: 0 1px 2px 0 rgb(0 0 0 / .4); }
html.dark .shadow     { --tw-shadow: 0 1px 3px 0 rgb(0 0 0 / .5), 0 1px 2px -1px rgb(0 0 0 / .5); }
html.dark .shadow-md  { --tw-shadow: 0 4px 6px -1px rgb(0 0 0 / .5), 0 2px 4px -2px rgb(0 0 0 / .5); }
html.dark .shadow-lg  { --tw-shadow: 0 10px 15px -3px rgb(0 0 0 / .55), 0 4px 6px -4px rgb(0 0 0 / .55); }
html.dark .shadow-xl  { --tw-shadow: 0 20px 25px -5px rgb(0 0 0 / .6), 0 8px 10px -6px rgb(0 0 0 / .6); }
html.dark .shadow-2xl { --tw-shadow: 0 25px 50px -12px rgb(0 0 0 / .7); }

/* ---------- Komponen khusus aplikasi -------------------------------------- */
html.dark .sidebar-active {
    background-color: rgb(59 130 246 / .16);
    border-left-color: #60a5fa;
}
html.dark ::selection { background-color: rgb(59 130 246 / .4); color: #ffffff; }

/* Tombol toggle tema — ikon matahari selalu kuning, apa pun class warnanya. */
[data-theme-icon].fa-sun { color: #fbbf24; }
</style>

<script>
/* ===== TOGGLE DARK / LIGHT MODE ===== */
function applyTheme(theme) {
    var isDark = (theme === 'dark');
    document.documentElement.classList.toggle('dark', isDark);

    /* Tukar hanya class ikonnya, class lain (ukuran/warna) dipertahankan. */
    var icons = document.querySelectorAll('[data-theme-icon]');
    for (var i = 0; i < icons.length; i++) {
        icons[i].classList.toggle('fa-sun', isDark);
        icons[i].classList.toggle('fa-moon', !isDark);
    }
    var labels = document.querySelectorAll('[data-theme-label]');
    for (var j = 0; j < labels.length; j++) {
        labels[j].textContent = isDark ? 'Mode Terang' : 'Mode Gelap';
    }
    var btns = document.querySelectorAll('[data-theme-toggle]');
    var hint = isDark ? 'Ganti ke Mode Terang' : 'Ganti ke Mode Gelap';
    for (var k = 0; k < btns.length; k++) {
        btns[k].setAttribute('title', hint);
        btns[k].setAttribute('aria-label', hint);
        btns[k].setAttribute('aria-pressed', isDark ? 'true' : 'false');
    }
}

function toggleTheme() {
    var next = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
    try { localStorage.setItem('theme', next); } catch (e) {}

    var root = document.documentElement;
    root.classList.add('theme-transition');
    applyTheme(next);
    setTimeout(function () { root.classList.remove('theme-transition'); }, 320);
}

/* Sinkronkan ikon tombol setelah DOM siap (skrip ini jalan di <head>). */
document.addEventListener('DOMContentLoaded', function () {
    applyTheme(document.documentElement.classList.contains('dark') ? 'dark' : 'light');
});

/* Ikut berubah bila tema diganti di tab lain. */
window.addEventListener('storage', function (e) {
    if (e.key === 'theme' && (e.newValue === 'dark' || e.newValue === 'light')) {
        applyTheme(e.newValue);
    }
});
</script>
