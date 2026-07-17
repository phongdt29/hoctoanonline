<?php
use App\Models\User;
use App\Services\AdminAnalyticsService;

beforeEach(fn () => $this->seed());

it('admin xem duoc trang report tong hop', function () {
    $admin = User::where('email', 'admin@hoctoan.test')->first();
    $this->actingAs($admin)->get('/admin')
        ->assertOk()
        ->assertSee('Báo cáo tổng quan')
        ->assertSee('Phễu chuyển đổi')
        ->assertSee('Toàn bộ dữ liệu hệ thống')
        ->assertSee('Top học sinh')
        ->assertSee('Giao dịch gần đây');
});

it('fullReport tra du cac phan', function () {
    $r = app(AdminAnalyticsService::class)->fullReport();
    expect($r)->toHaveKeys(['users','funnel','risk','ai','revenue','system','learning','top_students','recent_payments'])
        ->and($r['system'])->toHaveKeys(['assessments','lessons','quiz_attempts','solver_requests','tutor_messages','badges_earned','classes','plans']);
});

it('top_students xep theo diem giam dan', function () {
    $top = app(AdminAnalyticsService::class)->fullReport()['top_students'];
    expect($top)->not->toBeEmpty()
        ->and($top[0]['points'])->toBeGreaterThanOrEqual($top[count($top)-1]['points']);
});

it('hoc sinh KHONG xem duoc report admin', function () {
    $student = User::where('email', 'student1@hoctoan.test')->first();
    $this->actingAs($student)->get('/admin')->assertStatus(403);
});
