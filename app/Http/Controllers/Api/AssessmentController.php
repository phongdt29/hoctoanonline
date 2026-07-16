<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GradeAssessmentJob;
use App\Models\Assessment;
use App\Models\Student;
use App\Services\AssessmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ticket C3 — API bai danh gia (SPEC §5).
 * Tra JSON { data, message }. Submit + cham do C4 bo sung.
 */
class AssessmentController extends Controller
{
    public function __construct(private readonly AssessmentService $assessments) {}

    /** POST /api/v1/assessments/start */
    public function start(Request $request): JsonResponse
    {
        $student = $this->student($request);

        $assessment = $this->assessments->start($student);

        return response()->json([
            'data' => $this->present($assessment),
            'message' => 'Đã tạo đề kiểm tra.',
        ]);
    }

    /** PUT /api/v1/assessments/{assessment}/save — autosave 30s */
    public function save(Request $request, Assessment $assessment): JsonResponse
    {
        $this->authorizeOwner($request, $assessment);

        $data = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*.answer' => ['nullable'],
            'answers.*.time_spent_seconds' => ['required', 'integer', 'min:0'],
        ]);

        $this->assessments->saveProgress($assessment, $data['answers']);

        return response()->json(['data' => null, 'message' => 'Đã lưu.']);
    }

    /**
     * POST /api/v1/assessments/{assessment}/submit
     * Chot bai -> dispatch chuoi tu dong Grade -> Classify -> Generate (C4/C5).
     */
    public function submit(Request $request, Assessment $assessment): JsonResponse
    {
        $this->authorizeOwner($request, $assessment);

        if ($assessment->status !== Assessment::STATUS_IN_PROGRESS) {
            return response()->json([
                'data' => ['status' => $assessment->status],
                'message' => 'Bài kiểm tra đã được nộp trước đó.',
            ]);
        }

        // Luu not bai lam gui kem luc submit (neu co).
        if ($request->filled('answers')) {
            $this->assessments->saveProgress($assessment, $request->input('answers'));
        }

        $assessment->update([
            'status' => Assessment::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        // Cham + phan loai + sinh giao trinh chay trong Job (CLAUDE.md #4).
        GradeAssessmentJob::dispatch($assessment->id);

        return response()->json([
            'data' => ['status' => Assessment::STATUS_SUBMITTED],
            'message' => 'Đã nộp bài. A.I đang chấm và xây lộ trình cho bạn.',
        ]);
    }

    /** GET /api/v1/assessments/{assessment}/result */
    public function result(Request $request, Assessment $assessment): JsonResponse
    {
        $this->authorizeOwner($request, $assessment);

        return response()->json([
            'data' => [
                'status' => $assessment->status,
                'score' => $assessment->score,
                'classification' => $assessment->classification,
            ],
            'message' => 'OK',
        ]);
    }

    private function student(Request $request): Student
    {
        $student = $request->user()->student;

        abort_if($student === null, 403, 'Chỉ học sinh mới làm bài kiểm tra.');

        return $student;
    }

    private function authorizeOwner(Request $request, Assessment $assessment): void
    {
        abort_unless(
            $assessment->student_id === $request->user()->student?->id,
            403,
            'Bài kiểm tra này không phải của bạn.',
        );
    }

    private function present(Assessment $assessment): array
    {
        return [
            'id' => $assessment->id,
            'status' => $assessment->status,
            // KHONG tra correct_answer ve client — lo dap an.
            'questions' => $assessment->questions->map(fn ($q) => [
                'id' => $q->id,
                'order' => $q->question_order,
                'type' => $q->type,
                'topic' => $q->topic,
                'content' => $q->content,
                'options' => $q->options,
                'student_answer' => $q->student_answer['value'] ?? null,
                'time_spent_seconds' => $q->time_spent_seconds,
            ])->all(),
        ];
    }
}
