<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdminAnalyticsService;
use Illuminate\Http\JsonResponse;

/** Ticket R4 — API analytics tong quan cho admin. */
class AdminAnalyticsController extends Controller
{
    public function __construct(private readonly AdminAnalyticsService $analytics) {}

    /** GET /api/v1/admin/analytics */
    public function overview(): JsonResponse
    {
        return response()->json([
            'data' => $this->analytics->overview(),
            'message' => 'OK',
        ]);
    }
}
