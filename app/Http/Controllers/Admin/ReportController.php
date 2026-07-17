<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminAnalyticsService;
use Illuminate\View\View;

/** Trang report tong hop toan bo thong tin he thong cho admin. */
class ReportController extends Controller
{
    public function __construct(private readonly AdminAnalyticsService $analytics) {}

    public function index(): View
    {
        return view('admin.report', [
            'r' => $this->analytics->fullReport(),
        ]);
    }
}
