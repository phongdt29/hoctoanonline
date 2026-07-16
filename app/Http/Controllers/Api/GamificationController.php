<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentBadge;
use App\Services\GamificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Ticket R2 — API gamification: badge cua minh + leaderboard. */
class GamificationController extends Controller
{
    public function __construct(private readonly GamificationService $gamification) {}

    /** GET /api/v1/gamification/badges */
    public function badges(Request $request): JsonResponse
    {
        $student = $request->user()->student;
        abort_if($student === null, 403);

        $earned = StudentBadge::where('student_id', $student->id)->pluck('code', 'earned_at');

        $badges = collect(GamificationService::BADGES)->map(fn ($name, $code) => [
            'code' => $code,
            'name' => $name,
            'earned' => StudentBadge::where('student_id', $student->id)->where('code', $code)->exists(),
        ])->values();

        return response()->json(['data' => $badges, 'message' => 'OK']);
    }

    /** GET /api/v1/gamification/leaderboard */
    public function leaderboard(Request $request): JsonResponse
    {
        abort_if($request->user()->student === null, 403);

        return response()->json([
            'data' => $this->gamification->leaderboard(limit: 10, sinceDays: 7),
            'message' => 'OK',
        ]);
    }
}
