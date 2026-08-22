<?php

use CodeIgniter\Boot;
use Config\Paths;

/*
 *---------------------------------------------------------------
 * CHECK PHP VERSION
 *---------------------------------------------------------------
 */

$minPhpVersion = '8.2'; // If you update this, don't forget to update `spark`.
if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
    $message = sprintf(
        'Your PHP version must be %s or higher to run CodeIgniter. Current version: %s',
        $minPhpVersion,
        PHP_VERSION,
    );

    header('HTTP/1.1 503 Service Unavailable.', true, 503);
    echo $message;

    exit(1);
}

/*
 *---------------------------------------------------------------
 * SET THE CURRENT DIRECTORY
 *---------------------------------------------------------------
 */

// Path to the front controller (this file)
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// Ensure the current directory is pointing to the front controller's directory
if (getcwd() . DIRECTORY_SEPARATOR !== FCPATH) {
    chdir(FCPATH);
}

/*
 *---------------------------------------------------------------
 * AUTO-DETECT APPLICATION LAYOUT  (plug-n-play: lokal & shared hosting)
 *---------------------------------------------------------------
 * Local (php spark serve / XAMPP):  app/ vendor/ writable/  sejajar dengan public/
 * Shared hosting yang mengunci PHP ke web root (open_basedir):
 *                                   app/ vendor/ writable/  di DALAM folder publik
 */
$pathsCandidates = [
    FCPATH . '..' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Paths.php', // local layout
    FCPATH . 'app' . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Paths.php',                              // server layout
];

$pathsFile = null;
foreach ($pathsCandidates as $candidate) {
    if (is_file($candidate)) {
        $pathsFile = $candidate;
        break;
    }
}

if ($pathsFile === null) {
    header('HTTP/1.1 503 Service Unavailable.', true, 503);
    echo 'Bootstrap error: Config/Paths.php tidak ditemukan. Pastikan folder app/ ada di sebelah public/, atau di dalam folder publik bila web root tidak bisa dipindah.';
    exit(1);
}

require $pathsFile;

$paths = new Paths();

/*
 *---------------------------------------------------------------
 * ENVIRONMENT CONFIG (hybrid loader)
 *---------------------------------------------------------------
 * Prioritas:
 *   1. env.local.php  (di FCPATH atau parent FCPATH)
 *      -> dipakai di shared hosting yang mematikan putenv() atau mengunci
 *         PHP ke web root lewat open_basedir.
 *      -> file ini meng-set $_ENV[...] langsung; CI4 env() membaca $_ENV sebagai fallback.
 *   2. .env standar    -> dibaca CI4 DotEnv otomatis (lokal).
 *
 * env.local.php WAJIB di-gitignore (berisi kredensial).
 */
$envLocalCandidates = [
    FCPATH . 'env.local.php',                       // server: di htdocs/
    dirname(FCPATH) . DIRECTORY_SEPARATOR . 'env.local.php', // lokal: di project root
];
foreach ($envLocalCandidates as $envLocal) {
    if (is_file($envLocal)) {
        require $envLocal;
        break;
    }
}

// LOAD THE FRAMEWORK BOOTSTRAP FILE
require $paths->systemDirectory . '/Boot.php';

exit(Boot::bootWeb($paths));
