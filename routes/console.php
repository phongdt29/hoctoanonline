<?php

use App\Jobs\CloseAttendanceSessionJob;
use App\Jobs\ComputeRiskScoreJob;
use App\Jobs\SendWeeklyReportJob;
use App\Models\Student;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduler — SPEC §4
|--------------------------------------------------------------------------
| Cron VPS chi 1 dong: * * * * * php artisan schedule:run
| Gio ghi theo UTC (app timezone UTC), chu thich la gio VN.
*/

// Chot diem danh + flow vang (late/absent_pending/absent) — moi 5 phut.
Schedule::job(new CloseAttendanceSessionJob)->everyFiveMinutes();

// Tinh lai risk score toan bo hoc sinh dang hoc — 19:00 UTC = 2:00 sang VN.
Schedule::call(function () {
    Student::where('status', Student::STATUS_LEARNING)
        ->pluck('id')
        ->each(fn ($id) => ComputeRiskScoreJob::dispatch($id));
})->dailyAt('19:00')->name('compute-risk-scores');

// Doi soat points_balance vs point_ledger — 20:00 UTC.
Schedule::command('hoctoan:reconcile-points')->dailyAt('20:00');

// Bao cao tuan phu huynh — 01:00 UTC thu 2 = 8h sang T2 VN (Ticket R1).
Schedule::call(function () {
    Student::whereHas('parents')
        ->pluck('id')
        ->each(fn ($id) => SendWeeklyReportJob::dispatch($id));
})->weeklyOn(1, '01:00')->name('weekly-parent-report');
