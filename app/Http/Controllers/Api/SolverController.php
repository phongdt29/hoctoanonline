<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SolverRequest;
use App\Services\SolverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ticket I2 — API Solver text. 3 buoc chong le thuoc dap an.
 * Lan goi dau (POST text) chi tra HINT, khong bao gio dap an.
 */
class SolverController extends Controller
{
    public function __construct(private readonly SolverService $solver) {}

    /** POST /api/v1/solver/text */
    public function text(Request $request): JsonResponse
    {
        $student = $this->student($request);

        $data = $request->validate([
            'problem' => ['required', 'string', 'max:2000'],
        ]);

        $result = $this->solver->startText($student, $data['problem']);

        return response()->json([
            'data' => [
                'request_id' => $result['request']->id,
                'hint' => $result['hint'],
                'hint_count' => $result['request']->hint_count,
                'can_more_hint' => $result['request']->canRequestMoreHint(),
                // KHONG co truong 'solution' o buoc nay.
            ],
            'message' => 'Gợi ý cho bạn đây — thử làm trước nhé.',
        ]);
    }

    /** POST /api/v1/solver/image — nhan anh de toan, OCR qua Gemini vision (I3). */
    public function image(Request $request): JsonResponse
    {
        $student = $this->student($request);

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],   // 5MB
        ]);

        $file = $request->file('image');

        // Luu storage (local dev / s3 prod qua FILESYSTEM_DISK).
        $path = $file->store('solver-images');

        $result = $this->solver->startImage($student, [
            'data' => base64_encode(file_get_contents($file->getRealPath())),
            'mime' => $file->getMimeType(),
        ], $path);

        return response()->json([
            'data' => [
                'request_id' => $result['request']->id,
                'parsed_text' => $result['parsed_text'],
                'confidence' => $result['confidence'],
                'needs_confirmation' => $result['needs_confirmation'],
                'hint' => $result['hint'],   // null neu can confirm truoc
            ],
            'message' => $result['needs_confirmation']
                ? 'Ảnh hơi khó đọc. Xác nhận đề mình đọc được có đúng không nhé.'
                : 'Gợi ý cho bạn đây.',
        ]);
    }

    /** POST /api/v1/solver/{solverRequest}/confirm-image */
    public function confirmImage(Request $request, SolverRequest $solverRequest): JsonResponse
    {
        $this->authorizeOwner($request, $solverRequest);

        $data = $request->validate([
            'corrected_text' => ['nullable', 'string', 'max:2000'],
        ]);

        $result = $this->solver->confirmImage($solverRequest, $data['corrected_text'] ?? null);

        return response()->json([
            'data' => [
                'request_id' => $result['request']->id,
                'problem_text' => $result['request']->problem_text,
                'hint' => $result['hint'],
            ],
            'message' => 'Bắt đầu giải nhé.',
        ]);
    }

    /** POST /api/v1/solver/{solverRequest}/more-hint */
    public function moreHint(Request $request, SolverRequest $solverRequest): JsonResponse
    {
        $this->authorizeOwner($request, $solverRequest);

        $result = $this->solver->moreHint($solverRequest);

        return response()->json([
            'data' => [
                'hint' => $result['hint'],
                'hint_count' => $result['request']->hint_count,
                'can_more_hint' => $result['request']->canRequestMoreHint(),
            ],
            'message' => 'Gợi ý thêm.',
        ]);
    }

    /** POST /api/v1/solver/{solverRequest}/full-solution */
    public function fullSolution(Request $request, SolverRequest $solverRequest): JsonResponse
    {
        $this->authorizeOwner($request, $solverRequest);

        $result = $this->solver->fullSolution($solverRequest);

        return response()->json([
            'data' => ['solution' => $result['solution'], 'solution_revealed' => true],
            'message' => 'Lời giải đầy đủ.',
        ]);
    }

    /** GET /api/v1/solver/{solverRequest}/similar */
    public function similar(Request $request, SolverRequest $solverRequest): JsonResponse
    {
        $this->authorizeOwner($request, $solverRequest);

        return response()->json([
            'data' => ['problem' => $this->solver->similar($solverRequest)],
            'message' => 'Bài tương tự để luyện thêm.',
        ]);
    }

    private function student(Request $request): \App\Models\Student
    {
        $student = $request->user()->student;
        abort_if($student === null, 403);

        return $student;
    }

    private function authorizeOwner(Request $request, SolverRequest $solverRequest): void
    {
        abort_unless(
            $solverRequest->student_id === $request->user()->student?->id,
            403,
        );
    }
}
