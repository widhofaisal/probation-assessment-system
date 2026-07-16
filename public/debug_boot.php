<?php
/**
 * DEBUG BOOT — cek struktur folder & loading. HAPUS setelah dipakai.
 * Plug-n-play: auto-detect layout lokal (app/ sibling) vs server (app/ di dalam htdocs/).
 */
define('SETUP_KEY', 'rahasia123');
if (!isset($_GET['key']) || $_GET['key'] !== SETUP_KEY) {
    http_response_code(403);
    die('403 Forbidden');
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

$FCPATH = __DIR__ . DIRECTORY_SEPARATOR;

// Deteksi layout
$layouts = [
    'local (sibling)' => $FCPATH . '..' . DIRECTORY_SEPARATOR,
    'server (inside htdocs)' => $FCPATH,
];
$detectedLabel = null;
$base          = null;
foreach ($layouts as $label => $b) {
    if (is_file($b . 'app/Config/Paths.php')) {
        $detectedLabel = $label;
        $base = $b;
        break;
    }
}

echo '<pre>';
echo "PHP Version       : " . PHP_VERSION . "\n";
echo "FCPATH            : {$FCPATH}\n";
echo "Detected layout   : " . ($detectedLabel ?: '❌ TIDAK TERDETEKSI') . "\n";
echo "Resolved base     : " . ($base ?: '-') . "\n\n";

if (!$base) {
    echo "❌ app/Config/Paths.php tidak ditemukan baik di '../app' maupun './app'.\n";
    echo "</pre>";
    exit;
}

// Cek file penting
$files = [
    'app/Config/Paths.php',
    'vendor/autoload.php',
    'vendor/codeigniter4/framework/system/Boot.php',
    'writable/',
];
foreach ($files as $f) {
    $full = $base . $f;
    $exists = (file_exists($full) || is_dir($full)) ? '✅' : '❌';
    echo "$exists {$f}\n";
}

// env files
echo "\n--- ENV files ---\n";
$envCandidates = [
    $base . '.env',
    $FCPATH . 'env.local.php',
    dirname($FCPATH) . DIRECTORY_SEPARATOR . 'env.local.php',
];
foreach ($envCandidates as $e) {
    echo (is_file($e) ? '✅' : '❌') . " {$e}\n";
}

echo "\n--- Coba load Paths.php ---\n";
try {
    require $base . 'app/Config/Paths.php';
    $paths = new Config\Paths();
    echo "✅ Paths loaded\n";
    echo "  systemDirectory : " . $paths->systemDirectory . "  " . (is_dir($paths->systemDirectory) ? '✅' : '❌') . "\n";
    echo "  appDirectory    : " . $paths->appDirectory . "  " . (is_dir($paths->appDirectory) ? '✅' : '❌') . "\n";
    echo "  writableDirectory: " . $paths->writableDirectory . "  " . (is_dir($paths->writableDirectory) ? '✅' : '❌') . "\n";
} catch (\Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n--- Coba load vendor/autoload.php ---\n";
try {
    require $base . 'vendor/autoload.php';
    echo "✅ Autoload OK\n";
} catch (\Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n--- Disabled functions ---\n";
echo ini_get('disable_functions') ?: '(none)';

echo '</pre>';
echo '<p style="color:red;font-weight:bold">HAPUS FILE INI DARI SERVER SETELAH CEK!</p>';
