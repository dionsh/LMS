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
use App\Controllers\Admin\GradeController as AdminGrades;
use App\Controllers\Admin\RoomController as AdminRooms;
use App\Controllers\Admin\SlipController as AdminSlips;
use App\Controllers\Admin\StudentController as AdminStudents;
use App\Controllers\Admin\SubjectController as AdminSubjects;
use App\Controllers\Admin\TeacherController as AdminTeachers;
use App\Controllers\Admin\UserController as AdminUsers;
use App\Controllers\Admin\YearController as AdminYears;
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

    // Classes: add/edit, their students, and who teaches each subject
    $r->get('/klasat', [AdminClasses::class, 'index'], 'admin.classes');
    $r->get('/klasat/shto', [AdminClasses::class, 'create'], 'admin.classes.create');
    $r->post('/klasat/shto', [AdminClasses::class, 'store'], 'admin.classes.store');
    $r->get('/klasat/{id:\d+}', [AdminClasses::class, 'show'], 'admin.classes.show');
    $r->get('/klasat/{id:\d+}/ndrysho', [AdminClasses::class, 'edit'], 'admin.classes.edit');
    $r->post('/klasat/{id:\d+}/ndrysho', [AdminClasses::class, 'update'], 'admin.classes.update');
    $r->post('/klasat/{id:\d+}/fshij', [AdminClasses::class, 'destroy'], 'admin.classes.destroy');
    $r->post('/klasat/{id:\d+}/lendet', [AdminClasses::class, 'saveSubjects'], 'admin.classes.subjects');
    $r->post('/klasat/{id:\d+}/lendet/shto', [AdminClasses::class, 'addSubject'], 'admin.classes.subjects.add');
    $r->post('/klasat/{id:\d+}/lendet/hiq', [AdminClasses::class, 'removeSubject'], 'admin.classes.subjects.remove');

    // Curriculum: grades and the subjects they study
    $r->get('/plani-mesimor', [AdminGrades::class, 'index'], 'admin.grades');
    $r->post('/plani-mesimor/shto', [AdminGrades::class, 'store'], 'admin.grades.store');
    $r->get('/plani-mesimor/{level:\d+}', [AdminGrades::class, 'edit'], 'admin.grades.edit');
    $r->post('/plani-mesimor/{level:\d+}', [AdminGrades::class, 'update'], 'admin.grades.update');
    $r->post('/plani-mesimor/{level:\d+}/fshij', [AdminGrades::class, 'destroy'], 'admin.grades.destroy');

    // Subjects
    $r->get('/lendet', [AdminSubjects::class, 'index'], 'admin.subjects');
    $r->get('/lendet/shto', [AdminSubjects::class, 'create'], 'admin.subjects.create');
    $r->post('/lendet/shto', [AdminSubjects::class, 'store'], 'admin.subjects.store');
    $r->get('/lendet/{id:\d+}/ndrysho', [AdminSubjects::class, 'edit'], 'admin.subjects.edit');
    $r->post('/lendet/{id:\d+}/ndrysho', [AdminSubjects::class, 'update'], 'admin.subjects.update');
    $r->post('/lendet/{id:\d+}/fshij', [AdminSubjects::class, 'destroy'], 'admin.subjects.destroy');

    // Rooms
    $r->get('/sallat', [AdminRooms::class, 'index'], 'admin.rooms');
    $r->post('/sallat/shto', [AdminRooms::class, 'store'], 'admin.rooms.store');
    $r->get('/sallat/{id:\d+}/ndrysho', [AdminRooms::class, 'edit'], 'admin.rooms.edit');
    $r->post('/sallat/{id:\d+}/ndrysho', [AdminRooms::class, 'update'], 'admin.rooms.update');
    $r->post('/sallat/{id:\d+}/fshij', [AdminRooms::class, 'destroy'], 'admin.rooms.destroy');

    // School years and semesters
    $r->get('/vitet-shkollore', [AdminYears::class, 'index'], 'admin.years');
    $r->post('/vitet-shkollore/shto', [AdminYears::class, 'store'], 'admin.years.store');
    $r->get('/vitet-shkollore/{id:\d+}/ndrysho', [AdminYears::class, 'edit'], 'admin.years.edit');
    $r->post('/vitet-shkollore/{id:\d+}/ndrysho', [AdminYears::class, 'update'], 'admin.years.update');
    $r->post('/vitet-shkollore/{id:\d+}/aktual', [AdminYears::class, 'makeCurrent'], 'admin.years.current');

    // Students
    $r->get('/nxenesit', [AdminStudents::class, 'index'], 'admin.students');
    $r->get('/nxenesit/shto', [AdminStudents::class, 'create'], 'admin.students.create');
    $r->post('/nxenesit/shto', [AdminStudents::class, 'store'], 'admin.students.store');
    $r->post('/nxenesit/fletet', [AdminStudents::class, 'issueForClass'], 'admin.students.slips');

    // Teachers
    $r->get('/mesimdhenesit', [AdminTeachers::class, 'index'], 'admin.teachers');
    $r->get('/mesimdhenesit/shto', [AdminTeachers::class, 'create'], 'admin.teachers.create');
    $r->post('/mesimdhenesit/shto', [AdminTeachers::class, 'store'], 'admin.teachers.store');
    $r->post('/mesimdhenesit/fletet', [AdminTeachers::class, 'issueMissing'], 'admin.teachers.slips');

    // Every account; administrators; the shared edit page
    $r->get('/perdoruesit', [AdminUsers::class, 'index'], 'admin.users');
    $r->get('/perdoruesit/shto', [AdminUsers::class, 'create'], 'admin.users.create');
    $r->post('/perdoruesit/shto', [AdminUsers::class, 'store'], 'admin.users.store');
    $r->get('/perdoruesit/{id:\d+}/ndrysho', [AdminUsers::class, 'edit'], 'admin.users.edit');
    $r->post('/perdoruesit/{id:\d+}/ndrysho', [AdminUsers::class, 'update'], 'admin.users.update');
    $r->post('/perdoruesit/{id:\d+}/fleta', [AdminUsers::class, 'issue'], 'admin.users.slip');
    $r->post('/perdoruesit/{id:\d+}/statusi', [AdminUsers::class, 'status'], 'admin.users.status');

    // Printable login slips (this admin's session only)
    $r->get('/fletet-e-hyrjes/{batch:[a-f0-9]+}', [AdminSlips::class, 'show'], 'admin.slips');
    $r->post('/fletet-e-hyrjes/{batch:[a-f0-9]+}/mbaro', [AdminSlips::class, 'finish'], 'admin.slips.finish');
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
