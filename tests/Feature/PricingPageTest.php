<?php
use App\Models\User;

beforeEach(fn () => $this->seed());

it('trang pricing hien cac goi hoc', function () {
    $user = User::where('email', 'student1@hoctoan.test')->first();
    $this->actingAs($user)->get('/pricing')
        ->assertOk()
        ->assertSee('Gói 1 tháng')
        ->assertSee('Thanh toán VNPAY')
        ->assertSee('Thanh toán MoMo');
});

it('seed co 3 goi active', function () {
    expect(\App\Models\Plan::where('is_active', true)->count())->toBe(3);
});

it('phu huynh khong vao trang pricing (chi student)', function () {
    $parent = User::where('email', 'parent1@hoctoan.test')->first();
    $this->actingAs($parent)->get('/pricing')->assertStatus(403);
});
