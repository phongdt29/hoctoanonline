<?php

use App\Models\User;

/*
 * Hoi quy: frontend Blade+jQuery goi /api/v1/* bang SESSION cookie (khong phai
 * Bearer token). Neu statefulApi() khong bat, moi call API tu trinh duyet bi 401
 * — dung loi "Chua tao duoc de" ma nguoi dung gap.
 *
 * Test nay dung actingAs(user) (WEB guard/session) roi goi API, KHONG dung
 * actingAs(user,'sanctum') — de bat dung tang xac thuc that cua trinh duyet.
 */

beforeEach(function () {
    $this->seed();
});

it('goi API bang session (web guard) van xac thuc duoc — khong 401', function () {
    $user = User::where('email', 'student1@hoctoan.test')->first();

    // actingAs KHONG kem guard 'sanctum' -> dung session guard nhu trinh duyet.
    $this->actingAs($user)
        ->getJson('/api/v1/dashboard/student')
        ->assertOk();   // neu statefulApi tat -> 401
});

it('API van tu choi nguoi chua dang nhap (401)', function () {
    $this->getJson('/api/v1/dashboard/student')->assertUnauthorized();
});

it('session guard: student goi API cua role khac -> 403 (khong phai 401)', function () {
    $user = User::where('email', 'student1@hoctoan.test')->first();

    $this->actingAs($user)
        ->getJson('/api/v1/admin/analytics')
        ->assertStatus(403);
});
