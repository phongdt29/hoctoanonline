<?php
use App\Models\User;
use App\Services\Payment\PaymentGatewayFactory;

beforeEach(fn () => $this->seed());

it('trang pricing hien cac goi hoc', function () {
    $user = User::where('email', 'student1@hoctoan.test')->first();
    $this->actingAs($user)->get('/pricing')
        ->assertOk()
        ->assertSee('Gói 1 tháng')
        ->assertSee('Thanh toán VNPAY');   // VNPAY da cau hinh -> hien
});

it('chi hien cong da cau hinh — cong bi bo credentials thi khong hien', function () {
    // Cho MoMo VE trang thai CHUA cau hinh (doc lap voi .env), de test deterministic.
    config([
        'payment.momo.partner_code' => null,
        'payment.momo.access_key' => null,
        'payment.momo.secret_key' => null,
    ]);

    $configured = app(PaymentGatewayFactory::class)->configured();
    expect($configured)->toContain('vnpay')
        ->and($configured)->not->toContain('momo');

    $user = User::where('email', 'student1@hoctoan.test')->first();
    $this->actingAs($user)->get('/pricing')
        ->assertOk()
        ->assertDontSee('Thanh toán MoMo');
});

it('checkout qua cong chua cau hinh bi tu choi 422', function () {
    // Bo credentials MoMo -> cong chua cau hinh -> checkout momo bi chan.
    config([
        'payment.momo.partner_code' => null,
        'payment.momo.access_key' => null,
        'payment.momo.secret_key' => null,
    ]);

    $user = User::where('email', 'student1@hoctoan.test')->first();
    $plan = \App\Models\Plan::first();

    $this->actingAs($user)
        ->post(route('payment.checkout', $plan), ['gateway' => 'momo'])
        ->assertStatus(422);
});

it('seed co 3 goi active', function () {
    expect(\App\Models\Plan::where('is_active', true)->count())->toBe(3);
});

it('phu huynh khong vao trang pricing (chi student)', function () {
    $parent = User::where('email', 'parent1@hoctoan.test')->first();
    $this->actingAs($parent)->get('/pricing')->assertStatus(403);
});
