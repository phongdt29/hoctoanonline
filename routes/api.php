<?php

use App\Http\Controllers\Api\AssessmentController;
use App\Http\Controllers\Api\QuizController;
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
    });
});
