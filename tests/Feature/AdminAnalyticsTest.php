<?php

use App\Models\User;

/*
 * Ticket R4 — analytics tong quan cho admin.
 */

beforeEach(function () {
    $this->seed();
    $this->admin = User::where('email', 'admin@hoctoan.test')->first();
});

it('R4: admin xem duoc analytics overview', function () {
    $this->actingAs($this->admin, 'sanctum')
        ->getJson('/api/v1/admin/analytics')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'users' => ['total', 'by_role'],
                'funnel' => ['stages', 'assessment_count', 'conversion_to_learning'],
                'risk' => ['on_dinh', 'can_theo_doi', 'nguy_co_cao'],
                'ai' => ['calls_7d', 'errors_7d', 'avg_latency_ms', 'by_feature'],
                'revenue' => ['paid_count', 'revenue_total', 'revenue_30d'],
            ],
        ]);
});

it('R4: thong ke user dung so luong theo role', function () {
    $data = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/admin/analytics')->json('data');

    // Seed: 10 student, 5 parent, 2 teacher, 2 admin (co ca admin@hoctoan + admin@gmail).
    expect($data['users']['by_role']['student'])->toBe(10)
        ->and($data['users']['by_role']['teacher'])->toBe(2)
        ->and($data['users']['total'])->toBeGreaterThanOrEqual(19);
});

it('R4: funnel phan bo theo state machine', function () {
    $data = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/admin/analytics')->json('data');

    // student1 la learning; con lai registered.
    expect($data['funnel']['stages'])->toHaveKey('learning')
        ->and($data['funnel']['stages']['learning'])->toBeGreaterThanOrEqual(1);
});

it('R4: risk distribution dem dung — student1 la can_theo_doi', function () {
    $data = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/admin/analytics')->json('data');

    // student1 seed risk = 35 -> can_theo_doi.
    expect($data['risk']['can_theo_doi'])->toBeGreaterThanOrEqual(1);
});

it('R4: hoc sinh KHONG xem duoc analytics', function () {
    $student = User::where('email', 'student1@hoctoan.test')->first();

    $this->actingAs($student, 'sanctum')->getJson('/api/v1/admin/analytics')->assertStatus(403);
});
