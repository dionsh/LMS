<?php

declare(strict_types=1);

/*
 * Every URL of the application, in one place.
 *
 * - URLs are Albanian without diacritics; controllers are English.
 * - Access is decided here, on route GROUPS, so a new page cannot forget it:
 *     auth              signed in
 *     password.changed  no longer using the school-issued temporary password
 *     role:…            the right role (anything else → 403)
 * - All POST routes are CSRF-protected automatically by the router.
 */

use App\Controllers\Account\ProfileController;
use App\Controllers\Admin\ClassController as AdminClasses;
use App\Controllers\Admin\DashboardController as AdminDashboard;
use App\Controllers\Admin\StudentController as AdminStudents;
use App\Controllers\Admin\TeacherController as AdminTeachers;
use App\Controllers\Auth\LoginController;
use App\Controllers\Auth\PasswordController;
use App\Controllers\Dev\StyleGuideController;
use App\Controllers\Dev\SystemController;
use App\Controllers\Site\HomeController;
use App\Controllers\Student\DashboardController as StudentDashboard;
use App\Controllers\Teacher\DashboardController as TeacherDashboard;
use App\Core\Router;

/** @var Router $router */

// ---------------------------------------------------------------------
// Public website
// ---------------------------------------------------------------------
$router->get('/', [HomeController::class, 'index'], 'home');

// ---------------------------------------------------------------------
// Signing in and out (there is no sign-up: accounts come from the school)
// ---------------------------------------------------------------------
$router->group(['middleware' => ['guest']], function (Router $r): void {
    $r->get('/hyr', [LoginController::class, 'show'], 'login');
    $r->post('/hyr', [LoginController::class, 'login'], 'login.submit');
});

$router->post('/dil', [LoginController::class, 'logout'], 'logout');

$router->group(['middleware' => ['auth']], function (Router $r): void {
    $r->get('/ndrysho-fjalekalimin', [PasswordController::class, 'show'], 'password.first');
    $r->post('/ndrysho-fjalekalimin', [PasswordController::class, 'update'], 'password.first.submit');
});

// ---------------------------------------------------------------------
// Portal — shared by every role
// ---------------------------------------------------------------------
$router->group(['middleware' => ['auth', 'password.changed']], function (Router $r): void {
    $r->get('/paneli', [ProfileController::class, 'home'], 'dashboard');
    $r->get('/profili', [ProfileController::class, 'show'], 'profile');
    $r->post('/profili', [ProfileController::class, 'update'], 'profile.update');
    $r->post('/profili/fjalekalimi', [ProfileController::class, 'updatePassword'], 'profile.password');
});

// ---------------------------------------------------------------------
// Portal — one area per role
// ---------------------------------------------------------------------
$router->group(['prefix' => '/nxenesi', 'middleware' => ['auth', 'password.changed', 'role:student']], function (Router $r): void {
    $r->get('/', [StudentDashboard::class, 'index'], 'student.dashboard');
});

$router->group(['prefix' => '/mesimdhenesi', 'middleware' => ['auth', 'password.changed', 'role:teacher']], function (Router $r): void {
    $r->get('/', [TeacherDashboard::class, 'index'], 'teacher.dashboard');
});

$router->group(['prefix' => '/admin', 'middleware' => ['auth', 'password.changed', 'role:admin']], function (Router $r): void {
    $r->get('/', [AdminDashboard::class, 'index'], 'admin.dashboard');
    $r->get('/klasat', [AdminClasses::class, 'index'], 'admin.classes');
    $r->get('/mesimdhenesit', [AdminTeachers::class, 'index'], 'admin.teachers');
    $r->get('/nxenesit', [AdminStudents::class, 'index'], 'admin.students');
});

// ---------------------------------------------------------------------
// Development tools — never registered in production
// ---------------------------------------------------------------------
if (config('app.env') === 'development') {
    $router->get('/_sistemi', [SystemController::class, 'index'], 'dev.system');
    $router->post('/_sistemi/csrf', [SystemController::class, 'csrfCheck'], 'dev.csrf');

    $router->get('/_stilet', [StyleGuideController::class, 'index'], 'dev.styleguide');
    $router->get('/_stilet/portali', [StyleGuideController::class, 'portal'], 'dev.portal');
}
