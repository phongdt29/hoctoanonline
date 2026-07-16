<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RecommendationService;
use App\Services\StudentDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Ticket L3/L4 — API dashboard + recommendation. */
class DashboardController extends Controller
{
    public function __construct(
        private readonly StudentDashboardService $dashboard,
        private readonly RecommendationService $recommendations,
    ) {}

    /** GET /api/v1/dashboard/student */
    public function student(Request $request): JsonResponse
    {
        $student = $request->user()->student;
        abort_if($student === null, 403);

        return response()->json([
            'data' => $this->dashboard->build($student),
            'message' => 'OK',
        ]);
    }

    /** GET /api/v1/recommendations/today */
    public function today(Request $request): JsonResponse
    {
        $student = $request->user()->student;
        abort_if($student === null, 403);

        return response()->json([
            'data' => $this->recommendations->recommend($student),
            'message' => 'OK',
        ]);
    }
}
