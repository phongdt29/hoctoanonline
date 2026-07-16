<?php

use App\Mail\ResetPasswordMail;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditService;
use App\Services\PasswordResetService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

/*
 * Ticket A2 — DoD: token het han -> tu choi · dung lan 2 -> tu choi ·
 * doi pass xong login duoc bang pass moi.
 */

beforeEach(function () {
    RateLimiter::clear('');
    Mail::fake();
    $this->seed();
});

function requestReset(string $email = 'student1@hoctoan.test')
{
    test()->post('/forgot-password', ['email' => $email]);

    return app(PasswordResetService::class);
}

it('gui mail reset khi email ton tai', function () {
    $this->post('/forgot-password', ['email' => 'student1@hoctoan.test'])
        ->assertSessionHas('status');

    Mail::assertQueued(ResetPasswordMail::class);
});

it('email khong ton tai: KHONG gui mail nhung tra ve cung thong bao', function () {
    $response = $this->post('/forgot-password', ['email' => 'khongcoai@hoctoan.test']);

    // Thong bao giong het truong hop email co that -> khong do duoc email nao da dang ky.
    $response->assertSessionHas('status');
    Mail::assertNothingQueued();
});

it('token luu trong DB phai la HASH, khong phai ban ro', function () {
    $service = app(PasswordResetService::class);
    $plain = $service->createToken('student1@hoctoan.test');

    $stored = DB::table('password_reset_tokens')
        ->where('email', 'student1@hoctoan.test')
        ->value('token');

    // DB bi lo van khong the dung token de doi mat khau.
    expect($stored)->not->toBe($plain)
        ->and($stored)->toStartWith('$2y$')       // bcrypt
        ->and(strlen($plain))->toBe(64);
});

it('DoD A2: doi mat khau xong login duoc bang mat khau moi', function () {
    $service = app(PasswordResetService::class);
    $token = $service->createToken('student1@hoctoan.test');

    $this->post('/reset-password', [
        'token' => $token,
        'email' => 'student1@hoctoan.test',
        'password' => 'matkhaumoi123',
        'password_confirmation' => 'matkhaumoi123',
    ])->assertRedirect(route('login'));

    expect(Auth::attempt(['email' => 'student1@hoctoan.test', 'password' => 'matkhaumoi123']))->toBeTrue();

    Auth::logout();

    // Mat khau cu phai het tac dung.
    expect(Auth::attempt(['email' => 'student1@hoctoan.test', 'password' => 'password']))->toBeFalse();
});

it('DoD A2: token dung lan 2 -> bi tu choi', function () {
    $service = app(PasswordResetService::class);
    $token = $service->createToken('student1@hoctoan.test');

    $this->post('/reset-password', [
        'token' => $token,
        'email' => 'student1@hoctoan.test',
        'password' => 'matkhaumoi123',
        'password_confirmation' => 'matkhaumoi123',
    ])->assertRedirect(route('login'));

    // Lan 2 voi CUNG token
    $this->post('/reset-password', [
        'token' => $token,
        'email' => 'student1@hoctoan.test',
        'password' => 'matkhaukhac999',
        'password_confirmation' => 'matkhaukhac999',
    ])->assertSessionHasErrors('email');

    expect(Auth::attempt(['email' => 'student1@hoctoan.test', 'password' => 'matkhaukhac999']))->toBeFalse();
});

it('DoD A2: token het han sau TTL -> bi tu choi', function () {
    $service = app(PasswordResetService::class);
    $token = $service->createToken('student1@hoctoan.test');

    // Nhay qua moc TTL (config, khong hardcode 30)
    $this->travel(config('hoctoan.reset_token_ttl_min') + 1)->minutes();

    $this->post('/reset-password', [
        'token' => $token,
        'email' => 'student1@hoctoan.test',
        'password' => 'matkhaumoi123',
        'password_confirmation' => 'matkhaumoi123',
    ])->assertSessionHasErrors('email');

    expect(Auth::attempt(['email' => 'student1@hoctoan.test', 'password' => 'matkhaumoi123']))->toBeFalse();
});

it('token con han (truoc TTL) van dung duoc', function () {
    $service = app(PasswordResetService::class);
    $token = $service->createToken('student1@hoctoan.test');

    $this->travel(config('hoctoan.reset_token_ttl_min') - 1)->minutes();

    $this->post('/reset-password', [
        'token' => $token,
        'email' => 'student1@hoctoan.test',
        'password' => 'matkhaumoi123',
        'password_confirmation' => 'matkhaumoi123',
    ])->assertRedirect(route('login'));
});

it('token sai -> bi tu choi', function () {
    app(PasswordResetService::class)->createToken('student1@hoctoan.test');

    $this->post('/reset-password', [
        'token' => str_repeat('a', 64),
        'email' => 'student1@hoctoan.test',
        'password' => 'matkhaumoi123',
        'password_confirmation' => 'matkhaumoi123',
    ])->assertSessionHasErrors('email');
});

it('khong dung token cua email khac', function () {
    $service = app(PasswordResetService::class);
    $token = $service->createToken('student1@hoctoan.test');

    $this->post('/reset-password', [
        'token' => $token,
        'email' => 'student2@hoctoan.test',   // token cua nguoi khac
        'password' => 'matkhaumoi123',
        'password_confirmation' => 'matkhaumoi123',
    ])->assertSessionHasErrors('email');
});

it('yeu cau token moi lam token cu het hieu luc', function () {
    $service = app(PasswordResetService::class);
    $old = $service->createToken('student1@hoctoan.test');
    $new = $service->createToken('student1@hoctoan.test');

    expect($service->isValid('student1@hoctoan.test', $old))->toBeFalse()
        ->and($service->isValid('student1@hoctoan.test', $new))->toBeTrue();
});

it('doi mat khau huy toan bo sanctum token (dang xuat moi thiet bi khac)', function () {
    $user = User::where('email', 'student1@hoctoan.test')->first();
    $user->createToken('mobile');
    $user->createToken('tablet');

    expect($user->tokens()->count())->toBe(2);

    $service = app(PasswordResetService::class);
    $service->reset('student1@hoctoan.test', $service->createToken('student1@hoctoan.test'), 'matkhaumoi123');

    expect($user->fresh()->tokens()->count())->toBe(0);
});

it('ghi audit cho yeu cau reset va reset thanh cong', function () {
    $service = app(PasswordResetService::class);

    $this->post('/forgot-password', ['email' => 'student1@hoctoan.test']);
    expect(AuditLog::where('action', AuditService::ACTION_PASSWORD_RESET_REQUESTED)->exists())->toBeTrue();

    $token = $service->createToken('student1@hoctoan.test');
    $this->post('/reset-password', [
        'token' => $token,
        'email' => 'student1@hoctoan.test',
        'password' => 'matkhaumoi123',
        'password_confirmation' => 'matkhaumoi123',
    ]);

    expect(AuditLog::where('action', AuditService::ACTION_PASSWORD_RESET)->exists())->toBeTrue();
});

it('forgot-password bi rate limit 5 lan/phut', function () {
    foreach (range(1, 5) as $i) {
        $this->post('/forgot-password', ['email' => 'student1@hoctoan.test'])->assertStatus(302);
    }

    $this->post('/forgot-password', ['email' => 'student1@hoctoan.test'])->assertStatus(429);
});

it('trang forgot/reset hien thi duoc', function () {
    $this->get('/forgot-password')->assertOk()->assertSee('Quên mật khẩu');
    $this->get('/reset-password/'.str_repeat('a', 64))->assertOk()->assertSee('Đặt mật khẩu mới');
});
