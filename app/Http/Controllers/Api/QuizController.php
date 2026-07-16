<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\QuizService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ticket L2 — API quiz. Timer server-side.
 * Client nhan expires_at + cau hoi (KHONG kem dap an). Nop -> server tu cham.
 */
class QuizController extends Controller
{
    public function __construct(private readonly QuizService $quizzes) {}

    /** POST /api/v1/quizzes/{quiz}/start */
    public function start(Request $request, Quiz $quiz): JsonResponse
    {
        $student = $this->student($request);
        $this->authorizeQuiz($quiz, $student);

        $attempt = $this->quizzes->start($quiz, $student);

        return response()->json([
            'data' => [
                'attempt_id' => $attempt->id,
                'expires_at' => $attempt->expires_at->toIso8601String(),
                'server_now' => now()->toIso8601String(),   // client dong bo lech gio
                'questions' => $attempt->questionsForClient(),
            ],
            'message' => 'Bắt đầu làm quiz.',
        ]);
    }

    /** POST /api/v1/quizzes/{quiz}/submit */
    public function submit(Request $request, Quiz $quiz): JsonResponse
    {
        $student = $this->student($request);

        $data = $request->validate([
            'attempt_id' => ['required', 'integer'],
            'answers' => ['array'],
        ]);

        $attempt = QuizAttempt::where('id', $data['attempt_id'])
            ->where('quiz_id', $quiz->id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        $result = $this->quizzes->submit($attempt, $data['answers'] ?? []);

        return response()->json([
            'data' => [
                'score' => (float) $result->score,
                'suggestion' => $result->suggestion,
                'error_analysis' => $result->error_analysis,
            ],
            'message' => $result->error_analysis['expired'] ?? false
                ? 'Đã hết giờ — bài được chấm theo phần bạn kịp làm.'
                : 'Đã nộp bài.',
        ]);
    }

    private function student(Request $request): \App\Models\Student
    {
        $student = $request->user()->student;
        abort_if($student === null, 403);

        return $student;
    }

    private function authorizeQuiz(Quiz $quiz, \App\Models\Student $student): void
    {
        // Quiz phai thuoc lesson trong curriculum cua chinh hoc sinh, va lesson mo khoa.
        $lesson = $quiz->lesson;
        $owns = $lesson->module?->curriculum?->student_id === $student->id;

        abort_unless($owns && $lesson->isAccessible(), 403, 'Bạn chưa mở được bài này.');
    }
}
