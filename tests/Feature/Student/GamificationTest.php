<?php

use App\Models\PointLedger;
use App\Models\Student;
use App\Models\StudentBadge;
use App\Models\User;
use App\Services\GamificationService;

/*
 * Ticket R2 — badge (idempotent), streak, leaderboard.
 */

beforeEach(function () {
    $this->seed();
    $this->student = User::where('email', 'student1@hoctoan.test')->first()->student;
});

it('R2: trao huy hieu khi du dieu kien', function () {
    // student1 seed co points 233 -> dat points_100.
    $earned = app(GamificationService::class)->checkBadges($this->student);

    expect($earned)->toContain('points_100')
        ->and(StudentBadge::where('student_id', $this->student->id)->where('code', 'points_100')->exists())->toBeTrue();
});

it('R2: khong trao huy hieu trung (idempotent)', function () {
    $svc = app(GamificationService::class);

    $svc->checkBadges($this->student);
    $countAfterFirst = StudentBadge::where('student_id', $this->student->id)->count();

    $svc->checkBadges($this->student);   // goi lai
    $countAfterSecond = StudentBadge::where('student_id', $this->student->id)->count();

    expect($countAfterSecond)->toBe($countAfterFirst);
});

it('R2: huy hieu points_500 chi dat khi du 500 diem', function () {
    // student1 co 233 -> chua dat points_500.
    app(GamificationService::class)->checkBadges($this->student);
    expect(StudentBadge::where('student_id', $this->student->id)->where('code', 'points_500')->exists())->toBeFalse();

    // Them diem cho du 500.
    PointLedger::create(['student_id' => $this->student->id, 'amount' => 300, 'reason' => 'admin_adjustment']);
    $this->student->update(['points_balance' => 533]);

    app(GamificationService::class)->checkBadges($this->student->fresh());
    expect(StudentBadge::where('student_id', $this->student->id)->where('code', 'points_500')->exists())->toBeTrue();
});

it('R2: streak_7 khi hoc 7 ngay lien tiep', function () {
    $this->student->update(['streak_days' => 7]);

    app(GamificationService::class)->checkBadges($this->student->fresh());

    expect(StudentBadge::where('student_id', $this->student->id)->where('code', 'streak_7')->exists())->toBeTrue();
});

it('DoD R2: leaderboard xep hang theo diem, khong N+1', function () {
    \Illuminate\Support\Facades\DB::enableQueryLog();

    $board = app(GamificationService::class)->leaderboard(limit: 10, sinceDays: null);

    $queries = count(\Illuminate\Support\Facades\DB::getQueryLog());
    \Illuminate\Support\Facades\DB::disableQueryLog();

    expect($board)->not->toBeEmpty()
        ->and($board[0])->toHaveKeys(['rank', 'student_id', 'name', 'points'])
        ->and($board[0]['rank'])->toBe(1)
        // Xep giam dan: hang 1 >= hang 2.
        ->and($board[0]['points'])->toBeGreaterThanOrEqual($board[count($board) - 1]['points'])
        // Khong N+1: 1 query leaderboard + 1 query nap ten = 2.
        ->and($queries)->toBeLessThan(5);
});

it('R2: API badges tra danh sach kem trang thai earned', function () {
    app(GamificationService::class)->checkBadges($this->student);

    $user = User::where('email', 'student1@hoctoan.test')->first();
    $res = $this->actingAs($user, 'sanctum')->getJson('/api/v1/gamification/badges')->assertOk();

    $badges = collect($res->json('data'));
    expect($badges->firstWhere('code', 'points_100')['earned'])->toBeTrue()
        ->and($badges->firstWhere('code', 'points_500')['earned'])->toBeFalse();
});

it('R2: API leaderboard', function () {
    $user = User::where('email', 'student1@hoctoan.test')->first();

    $this->actingAs($user, 'sanctum')->getJson('/api/v1/gamification/leaderboard')
        ->assertOk()
        ->assertJsonStructure(['data' => [['rank', 'student_id', 'name', 'points']]]);
});
