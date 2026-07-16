<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AdminProviderController;
use App\Http\Controllers\Api\AssessmentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\SolverController;
use App\Http\Controllers\Api\StudentAssignmentController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\TutorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API AJAX — SPEC §5
|--------------------------------------------------------------------------
| Prefix /api/v1, tra JSON { data, message }, bao ve bang auth:sanctum.
| Cac endpoint that duoc bo sung dan theo ticket C3..T3.
|
| Luu y: file nay `install:api` le ra phai tao nhung khong tao — dung tay.
*/

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('/me', function (Request $request) {
        return response()->json([
            'data' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'role' => $request->user()->role,
            ],
            'message' => 'OK',
        ]);
    })->name('api.me');

    // Ticket C3 — bai danh gia dau vao (chi student)
    Route::middleware('role:student')->group(function () {
        Route::post('/assessments/start', [AssessmentController::class, 'start'])->name('api.assessments.start');
        Route::put('/assessments/{assessment}/save', [AssessmentController::class, 'save'])->name('api.assessments.save');
        Route::post('/assessments/{assessment}/submit', [AssessmentController::class, 'submit'])->name('api.assessments.submit');
        Route::get('/assessments/{assessment}/result', [AssessmentController::class, 'result'])->name('api.assessments.result');

        // Ticket L2 — quiz (timer server-side)
        Route::post('/quizzes/{quiz}/start', [QuizController::class, 'start'])->name('api.quizzes.start');
        Route::post('/quizzes/{quiz}/submit', [QuizController::class, 'submit'])->name('api.quizzes.submit');

        // Ticket L3/L4 — dashboard + recommendation
        Route::get('/dashboard/student', [DashboardController::class, 'student'])->name('api.dashboard.student');
        Route::get('/recommendations/today', [DashboardController::class, 'today'])->name('api.recommendations.today');

        // Ticket I2 — Solver text (3 buoc chong le thuoc dap an)
        Route::post('/solver/text', [SolverController::class, 'text'])->name('api.solver.text');
        Route::post('/solver/{solverRequest}/more-hint', [SolverController::class, 'moreHint'])->name('api.solver.more-hint');
        Route::post('/solver/{solverRequest}/full-solution', [SolverController::class, 'fullSolution'])->name('api.solver.full-solution');
        Route::get('/solver/{solverRequest}/similar', [SolverController::class, 'similar'])->name('api.solver.similar');

        // Ticket I1 — Tutor chat (polling)
        Route::post('/tutor/conversations', [TutorController::class, 'createConversation'])->name('api.tutor.create');
        Route::post('/tutor/conversations/{conversation}/messages', [TutorController::class, 'sendMessage'])->name('api.tutor.send');
        Route::get('/tutor/conversations/{conversation}/messages', [TutorController::class, 'messages'])->name('api.tutor.messages');

        // Ticket M1 — activity tracking (batch)
        Route::post('/activity/events', [ActivityController::class, 'store'])->name('api.activity.events');

        // Ticket T2 — hoc sinh nhan & nop bai
        Route::get('/student/assignments', [StudentAssignmentController::class, 'index'])->name('api.student.assignments');
        Route::post('/student/assignments/{assignment}/submit', [StudentAssignmentController::class, 'submit'])->name('api.student.assignments.submit');
    });

    // Ticket T1/T2 — giao vien gradebook + cham (NGOAI group role:student)
    Route::middleware('role:teacher')->group(function () {
        Route::get('/teacher/classes/{class}/gradebook', [TeacherController::class, 'gradebook'])->name('api.teacher.gradebook');
        Route::post('/teacher/submissions/{submission}/grade', [TeacherController::class, 'grade'])->name('api.teacher.grade');
    });

    // Ticket T3 — admin: CRUD AI provider
    Route::middleware('role:admin,staff')->group(function () {
        Route::get('/admin/ai-providers', [AdminProviderController::class, 'index'])->name('api.admin.providers.index');
        Route::post('/admin/ai-providers', [AdminProviderController::class, 'store'])->name('api.admin.providers.store');
        Route::put('/admin/ai-providers/{provider}', [AdminProviderController::class, 'update'])->name('api.admin.providers.update');
        Route::delete('/admin/ai-providers/{provider}', [AdminProviderController::class, 'destroy'])->name('api.admin.providers.destroy');
    });
});
