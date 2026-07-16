<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Student\AssessmentPageController;
use App\Http\Controllers\Student\CurriculumPageController;
use App\Http\Controllers\Student\DashboardController;
use App\Http\Controllers\Student\LessonController;
use App\Http\Controllers\Student\OnboardingController;
use App\Models\Student;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->to(\App\Support\RoleRedirect::for(auth()->user()))
        : view('welcome');
})->name('home');

// Ticket P3 — health check cho monitoring (DB + queue). Cong khai (khong auth).
Route::get('/healthz', \App\Http\Controllers\HealthController::class)->name('healthz');

/*
|--------------------------------------------------------------------------
| Auth — ticket A1 + A2
|--------------------------------------------------------------------------
| throttle:5,1 cho login (DoD A1: sai pass 5 lan -> 429) va cho forgot-password
| (chan do email de tim tai khoan ton tai).
*/
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');

    // A2 — quen / dat lai mat khau
    Route::get('/forgot-password', [ResetPasswordController::class, 'showRequestForm'])
        ->name('password.request');
    Route::post('/forgot-password', [ResetPasswordController::class, 'sendResetLink'])
        ->middleware('throttle:5,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])
        ->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'resetPassword'])
        ->middleware('throttle:5,1')
        ->name('password.update');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Dich redirect theo vai tro (ticket A1)
|--------------------------------------------------------------------------
| Hien la stub — moi trang se duoc thay o dung ticket ghi ben duoi.
| Ton tai o day de DoD A1 "vao dung trang theo role" kiem chung duoc that.
*/
// Hoc sinh
Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');

    Route::get('/assessment', [AssessmentPageController::class, 'show'])->name('assessment');
    Route::get('/assessment/{assessment}/result', [AssessmentPageController::class, 'result'])
        ->name('assessment.result');

    // `assessed`: chan vao khi chua lam bai danh gia — giao trinh sinh tu ket qua
    // phan loai, chua co thi khong co gi de hien.
    Route::middleware('assessed')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');
        Route::get('/curriculum', [CurriculumPageController::class, 'show'])->name('curriculum');
        Route::get('/lessons/{lesson}', [LessonController::class, 'show'])->name('lessons.show');
    });
});

// Phu huynh
Route::middleware(['auth', 'role:parent'])->group(function () {
    Route::get('/parent', [\App\Http\Controllers\ParentPortal\DashboardController::class, 'show'])
        ->name('parent.dashboard');
    Route::post('/parent/link-student', [\App\Http\Controllers\ParentPortal\DashboardController::class, 'linkStudent'])
        ->name('parent.link-student');
});

// Giao vien
Route::middleware(['auth', 'role:teacher'])->group(function () {
    Route::view('/teacher/classes', 'stub', ['title' => 'Lớp của tôi', 'ticket' => 'T1'])
        ->name('teacher.classes');
});

// Admin + staff
Route::middleware(['auth', 'role:admin,staff'])->group(function () {
    Route::view('/admin', 'stub', ['title' => 'Quản trị', 'ticket' => 'T3'])
        ->name('admin.home');
});

/*
|--------------------------------------------------------------------------
| Style guide (ticket F5)
|--------------------------------------------------------------------------
| Trang kiem tra design system. CHI local — UI-DESIGN-SPEC §6.5.
| Dat trong dieu kien environment de route KHONG TON TAI o production,
| thay vi chi an bang middleware.
|
| Phai co ca 'testing': phpunit.xml set APP_ENV=testing nen isLocal() = false,
| route se khong dang ky va test se an 404.
*/
if (app()->environment(['local', 'testing'])) {
    Route::get('/style-guide', function () {
        // Dung du lieu seed that (student1) de style guide phan anh dung thuc te.
        $student = Student::with('activeCurriculum')->first();
        $lessons = $student?->activeCurriculum?->lessons ?? collect();

        return view('style-guide', [
            'active' => 'dashboard',
            'themeColor' => $student?->favorite_color,
            'lessons' => $lessons,
            'justDoneId' => $lessons->firstWhere('status', 'unlocked')?->id,
        ]);
    })->name('style-guide');
}
