<?php
/**
 * SETUP ENV — generator env.local.php untuk deployment InfinityFree.
 * HAPUS file ini setelah dipakai.
 *
 * env.local.php meng-inject $_ENV langsung — aman walau putenv() disabled.
 */
define('SETUP_KEY', 'rahasia123'); // Ganti sebelum upload
if (!isset($_GET['key']) || $_GET['key'] !== SETUP_KEY) {
    http_response_code(403);
    die('403 Forbidden');
}

// ================================================================
// EDIT NILAI INI SEBELUM UPLOAD
// ================================================================
$hostname   = 'sql105.infinityfree.com';
$database   = 'if0_41805346_hrd_system';
$username   = 'if0_41805346';
$password   = '1RSETenjJ2ir';
$baseURL    = 'http://probation-assessment-system.infinityfreeapp.com/';
$encKey     = 'aQ9bL7xM2pN8kR5cV1jW4tY6uD3fG0hS';
// ================================================================

$tpl = <<<'PHP'
<?php
// Auto-generated oleh setup_env.php — JANGAN commit.
$_ENV['CI_ENVIRONMENT']            = 'production';
$_ENV['app.baseURL']               = '__BASEURL__';
$_ENV['app.indexPage']             = '';
$_ENV['app.forceGlobalSecureRequests'] = 'false';
$_ENV['app.CSPEnabled']            = 'false';
$_ENV['database.default.hostname'] = '__HOSTNAME__';
$_ENV['database.default.database'] = '__DATABASE__';
$_ENV['database.default.username'] = '__USERNAME__';
$_ENV['database.default.password'] = '__PASSWORD__';
$_ENV['database.default.DBDriver'] = 'MySQLi';
$_ENV['database.default.DBPrefix'] = '';
$_ENV['database.default.port']     = '3306';
$_ENV['database.default.charset']  = 'utf8mb4';
$_ENV['database.default.collation'] = 'utf8mb4_unicode_ci';
$_ENV['encryption.key']            = '__ENCKEY__';
$_ENV['session.driver']            = 'CodeIgniter\\Session\\Handlers\\FileHandler';
$_ENV['session.cookieName']        = 'hrd_session';
$_ENV['session.expiration']        = '28800';
$_ENV['session.matchIP']           = 'false';
$_ENV['session.regenerateDestroy'] = 'true';
$_ENV['logger.threshold']          = '4';
PHP;

$content = strtr($tpl, [
    '__BASEURL__'  => addslashes($baseURL),
    '__HOSTNAME__' => addslashes($hostname),
    '__DATABASE__' => addslashes($database),
    '__USERNAME__' => addslashes($username),
    '__PASSWORD__' => addslashes($password),
    '__ENCKEY__'   => addslashes($encKey),
]);

$target = __DIR__ . DIRECTORY_SEPARATOR . 'env.local.php';

if (file_exists($target)) {
    echo '<p style="color:orange">⚠️ env.local.php sudah ada, akan ditimpa.</p>';
}

if (@file_put_contents($target, $content) !== false) {
    @chmod($target, 0644);
    echo '<p style="color:green;font-weight:bold">✅ env.local.php berhasil dibuat di: ' . htmlspecialchars($target) . '</p>';
    echo '<p style="color:red;font-weight:bold">⚠️ HAPUS setup_env.php SEKARANG dari server!</p>';
} else {
    echo '<p style="color:red">❌ Gagal menulis env.local.php. Cek permission folder.</p>';
    echo '<p>Path: ' . htmlspecialchars($target) . '</p>';
}
