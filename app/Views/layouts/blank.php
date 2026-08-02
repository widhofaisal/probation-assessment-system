<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ? htmlspecialchars($title) . ' | ' : '' ?>Sistem Penilaian Probation</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' };</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?= $this->include('partials/theme') ?>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        .gradient-primary {
            background: linear-gradient(135deg, #0066cc 0%, #00a86b 100%);
        }
        .animate-fadeIn {
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body class="app-body bg-gray-50">
    <!-- Dark Mode Toggle -->
    <button type="button" onclick="toggleTheme()" data-theme-toggle
            title="Ganti ke Mode Gelap" aria-label="Ganti ke Mode Gelap" aria-pressed="false"
            class="fixed top-4 right-4 z-50 w-11 h-11 rounded-full bg-white shadow-lg border border-gray-200 text-gray-500 flex items-center justify-center hover:bg-gray-100 transition cursor-pointer">
        <i class="fas fa-moon" data-theme-icon></i>
    </button>

    <?= $this->renderSection('content') ?>
</body>
</html>
