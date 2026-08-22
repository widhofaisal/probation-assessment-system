<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

/*
 * KONTROL AKSES
 * -------------
 * Setiap route di bawah ini dijaga filter, bukan lagi mengandalkan pengecekan
 * manual di dalam controller:
 *
 *   'auth'        -> wajib sudah login
 *   'role:hrd'    -> wajib login DAN role-nya hrd
 *   'role:a,b'    -> wajib login DAN role-nya salah satu dari a atau b
 *
 * Definisinya ada di app/Filters/AuthFilter.php dan app/Filters/RoleFilter.php,
 * didaftarkan di app/Config/Filters.php.
 *
 * Pengecekan role yang sudah ada di controller sengaja TIDAK dihapus. Filter
 * menjadi gerbang utama, pengecekan di controller menjadi lapisan kedua - dan
 * untuk beberapa endpoint controller memang masih memeriksa hal yang tidak bisa
 * diketahui filter, yaitu kepemilikan data (misal: seorang Team Leader hanya
 * boleh membuka penilaian milik timnya sendiri).
 *
 * Route baru WAJIB diberi filter. Tanpa filter, route tersebut terbuka untuk
 * siapa saja termasuk pengunjung yang belum login.
 */

// Halaman awal - mengarahkan ke dashboard sesuai role. Tidak difilter karena
// justru bertugas mengarahkan tamu ke halaman login.
$routes->get('/', static function () {
    if (session()->has('user_id')) {
        $role = session()->get('role');
        $redirectUrl = match ($role) {
            'hrd' => '/dashboard/hrd',
            'team-leader' => '/dashboard/team-leader',
            'probationary-employee' => '/dashboard/probationary',
            default => '/auth/login',
        };

        return redirect()->to($redirectUrl);
    }

    return redirect()->to('/auth/login');
});

// ===== AUTH ROUTES =====
// Sengaja tanpa filter 'auth' - kalau difilter, halaman login ikut terkunci
// dan tidak ada seorang pun yang bisa masuk.
$routes->group('auth', static function ($routes) {
    $routes->get('login', 'AuthController::login');
    $routes->post('login', 'AuthController::processLogin');
    $routes->get('logout', 'AuthController::logout');
    $routes->get('session', 'AuthController::checkSession');
});

// ===== PROFILE ROUTES =====
// Semua role boleh mengelola profilnya sendiri.
$routes->group('profile', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'ProfileController::index');
    $routes->post('update', 'ProfileController::update');
    $routes->get('change-password', 'ProfileController::changePassword');
    $routes->post('update-password', 'ProfileController::updatePassword');
});

// ===== DASHBOARD ROUTES =====
$routes->group('dashboard', static function ($routes) {
    $routes->get('hrd', 'DashboardController::hrd', ['filter' => 'role:hrd']);
    $routes->get('team-leader', 'DashboardController::teamLeader', ['filter' => 'role:team-leader']);
    $routes->get('probationary', 'DashboardController::probationary', ['filter' => 'role:probationary-employee']);

    // Menyesuaikan isi statistik dengan role pemanggil, jadi cukup 'auth'.
    $routes->get('stats', 'DashboardController::stats', ['filter' => 'auth']);
});

$routes->get('team', 'DashboardController::myTeam', ['filter' => 'role:team-leader']);

// ===== USERS ROUTES =====
// Pengelolaan akun sepenuhnya milik HRD.
$routes->group('users', ['filter' => 'role:hrd'], static function ($routes) {
    $routes->get('/', 'UsersController::index');
    $routes->post('store', 'UsersController::store');
    $routes->post('(:num)', 'UsersController::update/$1');
    $routes->post('(:num)/reset-password', 'UsersController::resetPassword/$1');
    $routes->delete('(:num)', 'UsersController::delete/$1');
});

// ===== EMPLOYEES ROUTES =====
$routes->group('employees', ['filter' => 'role:hrd'], static function ($routes) {
    $routes->get('/', 'EmployeesController::index');
    $routes->get('(:num)', 'EmployeesController::show/$1');
    $routes->post('store', 'EmployeesController::store');
    $routes->post('(:num)', 'EmployeesController::update/$1');
    $routes->delete('(:num)', 'EmployeesController::delete/$1');
});

// Dipakai untuk mengisi dropdown di beberapa halaman, jadi tidak dibatasi HRD.
$routes->get('employees/by-department/(:any)', 'EmployeesController::getByDepartment/$1', ['filter' => 'auth']);

// ===== EVALUATIONS ROUTES =====
$routes->group('evaluations', static function ($routes) {
    $routes->get('/', 'EvaluationsController::index', ['filter' => 'role:hrd']);
    $routes->post('(:num)/keputusan', 'EvaluationsController::storeKeputusan/$1', ['filter' => 'role:hrd']);

    $routes->get('my', 'EvaluationsController::myEvaluations', ['filter' => 'role:probationary-employee']);

    $routes->post('store', 'EvaluationsController::store', ['filter' => 'role:team-leader']);
    $routes->get('(:num)/edit', 'EvaluationsController::edit/$1', ['filter' => 'role:team-leader']);
    $routes->post('(:num)', 'EvaluationsController::update/$1', ['filter' => 'role:team-leader']);
    $routes->delete('(:num)', 'EvaluationsController::destroy/$1', ['filter' => 'role:team-leader']);

    // Semua role boleh membuka, tapi kepemilikan datanya diperiksa di dalam
    // controller - Team Leader dan Team Member hanya boleh melihat miliknya.
    $routes->get('(:num)', 'EvaluationsController::show/$1', ['filter' => 'auth']);

    // Daftar aspek penilaian dan status keputusan: tidak memuat data pribadi,
    // cukup dibatasi ke pengguna yang sudah login.
    $routes->get('aspects', 'EvaluationsController::getAspects', ['filter' => 'auth']);
    $routes->get('keputusan-status', 'EvaluationsController::keputusanStatus', ['filter' => 'auth']);
});

// ===== REPORTS ROUTES =====
$routes->group('reports', static function ($routes) {
    // Kepemilikan berkas diperiksa di dalam ReportsController.
    $routes->get('evaluation/(:num)', 'ReportsController::view/$1', ['filter' => 'auth']);
    $routes->get('pdf/(:num)', 'ReportsController::generatePdf/$1', ['filter' => 'auth']);
    $routes->get('pdf-all/(:num)', 'ReportsController::pdfAll/$1', ['filter' => 'auth']);
    $routes->get('sk/(:num)', 'ReportsController::sk/$1', ['filter' => 'auth']);

    // Ekspor seluruh data hanya untuk HRD.
    $routes->get('export-csv', 'ReportsController::exportCsv', ['filter' => 'role:hrd']);
    $routes->get('export-employees-csv', 'ReportsController::exportEmployeesCsv', ['filter' => 'role:hrd']);
});
