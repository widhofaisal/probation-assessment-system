<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Default route - redirect to appropriate dashboard or login
$routes->get('/', function () {
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

// ===== PROFILE ROUTES =====
$routes->get('profile', 'ProfileController::index');
$routes->post('profile/update', 'ProfileController::update');
$routes->get('profile/change-password', 'ProfileController::changePassword');
$routes->post('profile/update-password', 'ProfileController::updatePassword');

// ===== AUTH ROUTES =====
$routes->group('auth', static function ($routes) {
    $routes->get('login', 'AuthController::login');
    $routes->post('login', 'AuthController::processLogin');
    $routes->get('logout', 'AuthController::logout');
    $routes->get('session', 'AuthController::checkSession');
});

// ===== DASHBOARD ROUTES =====
$routes->group('dashboard', static function ($routes) {
    $routes->get('hrd', 'DashboardController::hrd');
    $routes->get('team-leader', 'DashboardController::teamLeader');
    $routes->get('probationary', 'DashboardController::probationary');
    $routes->get('stats', 'DashboardController::stats');
});

$routes->get('team', 'DashboardController::myTeam');

// ===== USERS ROUTES =====
$routes->group('users', static function ($routes) {
    $routes->get('/', 'UsersController::index');
    $routes->post('store', 'UsersController::store');
    $routes->post('(:num)', 'UsersController::update/$1');
    $routes->post('(:num)/reset-password', 'UsersController::resetPassword/$1');
    $routes->delete('(:num)', 'UsersController::delete/$1');
});

// ===== EMPLOYEES ROUTES =====
$routes->group('employees', static function ($routes) {
    $routes->get('/', 'EmployeesController::index');
    $routes->get('(:num)', 'EmployeesController::show/$1');
    $routes->post('store', 'EmployeesController::store');
    $routes->post('(:num)', 'EmployeesController::update/$1');
    $routes->delete('(:num)', 'EmployeesController::delete/$1');
    $routes->get('by-department/(:any)', 'EmployeesController::getByDepartment/$1');
});

// ===== EVALUATIONS ROUTES =====
$routes->group('evaluations', static function ($routes) {
    $routes->get('/', 'EvaluationsController::index');
    $routes->get('my', 'EvaluationsController::myEvaluations');
    $routes->post('store', 'EvaluationsController::store');
    $routes->get('(:num)', 'EvaluationsController::show/$1');
    $routes->get('(:num)/edit', 'EvaluationsController::edit/$1');
    $routes->post('(:num)', 'EvaluationsController::update/$1');
    $routes->delete('(:num)', 'EvaluationsController::destroy/$1');
    $routes->get('aspects', 'EvaluationsController::getAspects');
});

// ===== REPORTS ROUTES =====
$routes->group('reports', static function ($routes) {
    $routes->get('evaluation/(:num)', 'ReportsController::view/$1');
    $routes->get('pdf/(:num)', 'ReportsController::generatePdf/$1');
    $routes->get('pdf-all/(:num)', 'ReportsController::pdfAll/$1');
    $routes->get('export-csv', 'ReportsController::exportCsv');
    $routes->get('export-employees-csv', 'ReportsController::exportEmployeesCsv');
});
