<?php

use App\Models\AuditLog;
use App\Models\ParentAccount;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\RateLimiter;

/*
 * Ticket A1 — DoD: dang ky -> dang nhap -> vao dung trang theo role.
 * Sai pass 5 lan -> 429. Logout huy session + token.
 */

beforeEach(function () {
    RateLimiter::clear('');
    $this->seed();
});

function login(string $email, string $password = 'password')
{
    return test()->post('/login', ['email' => $email, 'password' => $password]);
}

it('dang ky hoc sinh moi -> tao User + ho so rong, day sang onboarding', function () {
    $this->post('/register', [
        'name' => 'Nguyen Van Test',
        'email' => 'moi@hoctoan.test',
        'password' => 'matkhau123',
        'password_confirmation' => 'matkhau123',
        'role' => 'student',
    ])->assertRedirect(route('onboarding'));

    $user = User::where('email', 'moi@hoctoan.test')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(User::ROLE_STUDENT)
        ->and($user->student)->not->toBeNull()
        // Chua onboard -> status `registered`, 12 truong ho so con trong.
        ->and($user->student->status)->toBe(Student::STATUS_REGISTERED)
        ->and($user->student->grade)->toBeNull()
        ->and($user->student->invite_code)->toBeNull();

    $this->assertAuthenticatedAs($user);
});

it('dang ky phu huynh -> tao ParentAccount, day sang /parent', function () {
    $this->post('/register', [
        'name' => 'Phu Huynh Moi',
        'email' => 'ph@hoctoan.test',
        'password' => 'matkhau123',
        'password_confirmation' => 'matkhau123',
        'role' => 'parent',
    ])->assertRedirect(route('parent.dashboard'));

    expect(ParentAccount::whereHas('user', fn ($q) => $q->where('email', 'ph@hoctoan.test'))->exists())
        ->toBeTrue();
});

it('KHONG cho tu dang ky vai tro admin/teacher/staff', function (string $role) {
    $this->post('/register', [
        'name' => 'Ke Gia Mao',
        'email' => 'hacker@hoctoan.test',
        'password' => 'matkhau123',
        'password_confirmation' => 'matkhau123',
        'role' => $role,
    ])->assertSessionHasErrors('role');

    expect(User::where('email', 'hacker@hoctoan.test')->exists())->toBeFalse();
})->with(['admin', 'teacher', 'staff']);

it('validate dang ky: email trung, mat khau ngan, nhap lai khong khop', function () {
    $this->post('/register', [
        'name' => '',
        'email' => 'student1@hoctoan.test',   // da ton tai
        'password' => '123',                  // qua ngan
        'password_confirmation' => '456',     // khong khop
        'role' => 'student',
    ])->assertSessionHasErrors(['name', 'email', 'password']);
});

it('DoD A1: dang nhap vao dung trang theo tung vai tro', function (string $email, string $routeName) {
    login($email)->assertRedirect(route($routeName));
})->with([
    ['admin@hoctoan.test', 'admin.home'],
    ['teacher1@hoctoan.test', 'teacher.classes'],
    ['parent1@hoctoan.test', 'parent.dashboard'],
    // student1 da `learning` (seed) -> vao thang dashboard
    ['student1@hoctoan.test', 'dashboard'],
    // student2 moi `registered` -> phai onboarding truoc
    ['student2@hoctoan.test', 'onboarding'],
]);

it('sai mat khau -> bao loi chung, khong tiet lo email co ton tai hay khong', function () {
    login('student1@hoctoan.test', 'sai-mat-khau')->assertSessionHasErrors('email');

    // Cung 1 thong bao cho ca email khong ton tai.
    $this->post('/login', ['email' => 'khongcoai@hoctoan.test', 'password' => 'gi-do'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('DoD A1: sai mat khau 5 lan -> lan thu 6 bi 429', function () {
    foreach (range(1, 5) as $i) {
        login('student1@hoctoan.test', 'sai')->assertStatus(302);
    }

    login('student1@hoctoan.test', 'sai')->assertStatus(429);
});

it('tai khoan bi khoa khong dang nhap duoc du dung mat khau', function () {
    User::where('email', 'student1@hoctoan.test')->update(['status' => 'disabled']);

    login('student1@hoctoan.test')->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('DoD A1: logout huy ca session VA sanctum token', function () {
    $user = User::where('email', 'student1@hoctoan.test')->first();
    $user->createToken('mobile');

    expect($user->tokens()->count())->toBe(1);

    $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));

    $this->assertGuest();
    expect($user->fresh()->tokens()->count())->toBe(0);
});

it('ghi audit log cho register / login / login_failed / logout', function () {
    $this->post('/register', [
        'name' => 'Audit Test',
        'email' => 'audit@hoctoan.test',
        'password' => 'matkhau123',
        'password_confirmation' => 'matkhau123',
        'role' => 'student',
    ]);
    expect(AuditLog::where('action', AuditService::ACTION_REGISTER)->exists())->toBeTrue();

    $this->post('/logout');

    login('student1@hoctoan.test', 'sai');
    expect(AuditLog::where('action', AuditService::ACTION_LOGIN_FAILED)->exists())->toBeTrue();

    login('student1@hoctoan.test');
    expect(AuditLog::where('action', AuditService::ACTION_LOGIN)->exists())->toBeTrue();

    $this->post('/logout');
    expect(AuditLog::where('action', AuditService::ACTION_LOGOUT)->exists())->toBeTrue();
});

it('audit log KHONG BAO GIO chua mat khau', function () {
    $this->post('/register', [
        'name' => 'Audit Test',
        'email' => 'audit2@hoctoan.test',
        'password' => 'sieu-bi-mat-123',
        'password_confirmation' => 'sieu-bi-mat-123',
        'role' => 'student',
    ]);

    $logs = AuditLog::all()->pluck('metadata')->toJson();

    expect($logs)->not->toContain('sieu-bi-mat-123');
});

it('trang login/register hien thi duoc', function () {
    $this->get('/login')->assertOk()->assertSee('Đăng nhập');
    $this->get('/register')->assertOk()->assertSee('Tạo tài khoản');
});

it('da dang nhap thi khong vao lai trang login duoc', function () {
    $user = User::where('email', 'student1@hoctoan.test')->first();

    $this->actingAs($user)->get('/login')->assertRedirect();
});
