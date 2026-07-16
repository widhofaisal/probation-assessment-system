<?php
/**
 * CHECK WRITABLE — cek izin folder writable/. HAPUS setelah dipakai.
 * Plug-n-play: cek writable/ di dalam htdocs/ (server) ATAU sibling public/ (lokal).
 */
define('SETUP_KEY', 'rahasia123');
if (!isset($_GET['key']) || $_GET['key'] !== SETUP_KEY) {
    http_response_code(403);
    die('403 Forbidden');
}

$FCPATH = __DIR__ . DIRECTORY_SEPARATOR;

// Deteksi lokasi writable/
$bases = [
    'server (inside htdocs)' => $FCPATH,
    'local (sibling)'        => dirname($FCPATH) . DIRECTORY_SEPARATOR,
];
$base  = null;
$label = null;
foreach ($bases as $l => $b) {
    if (is_dir($b . 'writable')) {
        $label = $l;
        $base  = $b;
        break;
    }
}

echo '<pre>';
echo "FCPATH          : {$FCPATH}\n";
echo "Detected layout : " . ($label ?: '❌ writable/ tidak ditemukan') . "\n";
echo "Base            : " . ($base ?: '-') . "\n\n";

if (!$base) {
    echo "</pre>";
    exit;
}

$dirs = [
    'writable'         => $base . 'writable',
    'writable/session' => $base . 'writable/session',
    'writable/cache'   => $base . 'writable/cache',
    'writable/logs'    => $base . 'writable/logs',
    'writable/uploads' => $base . 'writable/uploads',
];

foreach ($dirs as $name => $path) {
    $exists   = is_dir($path)      ? '✅ exists'   : '❌ not found';
    $writable = is_writable($path) ? '✅ writable' : '❌ NOT writable';
    echo str_pad($name, 22) . " → {$exists}, {$writable}\n";
}

$testFile = $base . 'writable/logs/_write_test.txt';
$written  = @file_put_contents($testFile, 'ok');
if ($written !== false) {
    @unlink($testFile);
    echo "\nWrite test: ✅ Berhasil menulis ke writable/logs/\n";
} else {
    echo "\nWrite test: ❌ GAGAL menulis ke writable/logs/\n";
}
echo '</pre>';
echo '<p style="color:red;font-weight:bold">HAPUS FILE INI DARI SERVER SETELAH CEK!</p>';
