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
| Ticket R3 — thanh toan VNPAY
| IPN (vnpay-ipn) la server-to-server: KHONG auth, bao ve bang verify signature.
| return la nguoi dung quay ve sau khi thanh toan.
*/
Route::get('/payment/vnpay-ipn', [\App\Http\Controllers\PaymentController::class, 'vnpayIpn'])
    ->name('payment.vnpay-ipn');
Route::post('/payment/momo-ipn', [\App\Http\Controllers\PaymentController::class, 'momoIpn'])
    ->name('payment.momo-ipn');
Route::get('/payment/return', [\App\Http\Controllers\PaymentController::class, 'return'])
    ->name('payment.return');
Route::get('/pricing', [\App\Http\Controllers\PaymentController::class, 'pricing'])
    ->middleware(['auth', 'role:student'])
    ->name('pricing');
Route::post('/payment/checkout/{plan}', [\App\Http\Controllers\PaymentController::class, 'checkout'])
    ->middleware(['auth', 'role:student'])
    ->name('payment.checkout');

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

    // Bo qua bai test (sinh lo trinh demo) — CHI dev/test, khong dang ky o production.
    if (! app()->environment('production')) {
        Route::post('/assessment/skip', [AssessmentPageController::class, 'skip'])->name('assessment.skip');
    }

    // `assessed`: chan vao khi chua lam bai danh gia — giao trinh sinh tu ket qua
    // phan loai, chua co thi khong co gi de hien.
    Route::middleware('assessed')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');
        Route::get('/curriculum', [CurriculumPageController::class, 'show'])->name('curriculum');
        Route::get('/results', [\App\Http\Controllers\Student\ResultController::class, 'show'])->name('results');
        Route::get('/lessons/{lesson}', [LessonController::class, 'show'])->name('lessons.show');
        Route::get('/quiz/{quiz}', [\App\Http\Controllers\Student\QuizPageController::class, 'show'])->name('quiz.show');

        // Trang phu: giai bai, gia su, ca nhan (host UI + JS goi API)
        Route::get('/solver', [\App\Http\Controllers\Student\StudentPageController::class, 'solver'])->name('solver');
        Route::get('/tutor', [\App\Http\Controllers\Student\StudentPageController::class, 'tutor'])->name('tutor');
        Route::get('/profile', [\App\Http\Controllers\Student\StudentPageController::class, 'profile'])->name('profile');
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
    $tc = \App\Http\Controllers\Teacher\TeacherController::class;
    Route::get('/teacher/classes', [$tc, 'classes'])->name('teacher.classes');
    Route::get('/teacher/classes/{class}', [$tc, 'show'])->name('teacher.class');
    Route::post('/teacher/classes/{class}/assignments', [$tc, 'storeAssignment'])->name('teacher.assignment.store');
    Route::get('/teacher/assignments/{assignment}', [$tc, 'submissions'])->name('teacher.assignment');
    Route::post('/teacher/submissions/{submission}/grade', [$tc, 'grade'])->name('teacher.grade');
});

// Admin + staff
Route::middleware(['auth', 'role:admin,staff'])->group(function () {
    Route::get('/admin', [\App\Http\Controllers\Admin\ReportController::class, 'index'])
        ->name('admin.home');
    Route::get('/admin/token-cost', [\App\Http\Controllers\Admin\TokenCostController::class, 'index'])
        ->name('admin.token-cost');
});

// Admin thuc — quan ly AI provider (key nhay cam, khong cho staff).
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/ai-providers', [\App\Http\Controllers\Admin\ProviderController::class, 'index'])
        ->name('admin.providers');
    Route::post('/admin/ai-providers', [\App\Http\Controllers\Admin\ProviderController::class, 'store'])
        ->name('admin.providers.store');
    Route::put('/admin/ai-providers/{provider}', [\App\Http\Controllers\Admin\ProviderController::class, 'update'])
        ->name('admin.providers.update');
    Route::delete('/admin/ai-providers/{provider}', [\App\Http\Controllers\Admin\ProviderController::class, 'destroy'])
        ->name('admin.providers.destroy');
    Route::post('/admin/ai-providers/{provider}/test', [\App\Http\Controllers\Admin\ProviderController::class, 'test'])
        ->name('admin.providers.test');

    // Soan bai — sua noi dung lesson + cong thuc toan.
    Route::get('/admin/lessons', [\App\Http\Controllers\Admin\LessonController::class, 'index'])
        ->name('admin.lessons');
    Route::get('/admin/lessons/{lesson}/edit', [\App\Http\Controllers\Admin\LessonController::class, 'edit'])
        ->name('admin.lessons.edit');
    Route::put('/admin/lessons/{lesson}', [\App\Http\Controllers\Admin\LessonController::class, 'update'])
        ->name('admin.lessons.update');
    Route::post('/admin/lessons/{lesson}/ai-generate', [\App\Http\Controllers\Admin\LessonController::class, 'aiGenerate'])
        ->name('admin.lessons.ai-generate');
    Route::post('/admin/lessons/{lesson}/ocr', [\App\Http\Controllers\Admin\LessonController::class, 'ocr'])
        ->name('admin.lessons.ocr');
    Route::post('/admin/lessons/{lesson}/bulk', [\App\Http\Controllers\Admin\LessonController::class, 'bulk'])
        ->name('admin.lessons.bulk');
    Route::post('/admin/lessons/{lesson}/similar', [\App\Http\Controllers\Admin\LessonController::class, 'similar'])
        ->name('admin.lessons.similar');

    // Lên giáo trình bằng AI — thu vien giao trinh mau.
    Route::get('/admin/syllabi', [\App\Http\Controllers\Admin\SyllabusController::class, 'index'])
        ->name('admin.syllabi');
    Route::post('/admin/syllabi', [\App\Http\Controllers\Admin\SyllabusController::class, 'store'])
        ->name('admin.syllabi.store');
    Route::get('/admin/syllabi/{syllabus}', [\App\Http\Controllers\Admin\SyllabusController::class, 'show'])
        ->name('admin.syllabi.show');
    Route::post('/admin/syllabi/{syllabus}/retry', [\App\Http\Controllers\Admin\SyllabusController::class, 'retry'])
        ->name('admin.syllabi.retry');
    Route::post('/admin/syllabi/{syllabus}/assign', [\App\Http\Controllers\Admin\SyllabusController::class, 'assign'])
        ->name('admin.syllabi.assign');
    Route::delete('/admin/syllabi/{syllabus}', [\App\Http\Controllers\Admin\SyllabusController::class, 'destroy'])
        ->name('admin.syllabi.destroy');

    // De thi trac nghiem
    Route::get('/admin/exams', [\App\Http\Controllers\Admin\ExamController::class, 'index'])
        ->name('admin.exams');
    Route::post('/admin/exams', [\App\Http\Controllers\Admin\ExamController::class, 'store'])
        ->name('admin.exams.store');
    Route::get('/admin/exams/{exam}', [\App\Http\Controllers\Admin\ExamController::class, 'show'])
        ->name('admin.exams.show');
    Route::get('/admin/exams/{exam}/print', [\App\Http\Controllers\Admin\ExamController::class, 'print'])
        ->name('admin.exams.print');
    Route::post('/admin/exams/{exam}/grade', [\App\Http\Controllers\Admin\ExamController::class, 'grade'])
        ->name('admin.exams.grade');
    Route::post('/admin/exams/{exam}/retry', [\App\Http\Controllers\Admin\ExamController::class, 'retry'])
        ->name('admin.exams.retry');
    Route::delete('/admin/exams/{exam}', [\App\Http\Controllers\Admin\ExamController::class, 'destroy'])
        ->name('admin.exams.destroy');
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
