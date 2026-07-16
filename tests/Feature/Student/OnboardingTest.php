<?php

use App\Models\Student;
use App\Models\User;

/*
 * Ticket C2 — DoD: thieu field -> 422 kem loi inline · xong -> redirect /assessment.
 */

beforeEach(function () {
    $this->seed();
});

function newStudent(): User
{
    $user = User::create([
        'name' => 'HS Moi', 'email' => 'hsmoi@hoctoan.test', 'password' => 'password', 'role' => 'student',
    ]);
    Student::create(['user_id' => $user->id, 'full_name' => 'HS Moi', 'status' => 'registered']);

    return $user;
}

function validPayload(array $override = []): array
{
    return array_merge([
        'full_name' => 'Nguyen Van A',
        'date_of_birth' => '2011-05-01',
        'address' => 'Ha Noi',
        'phone' => '0912345678',
        'school_name' => 'THCS Chu Van An',
        'grade' => 8,
        'self_assessed_level' => 'kha',
        'math_gpa' => 7.5,
        'tutor_gender' => 'co',
        'favorite_color' => '#2563EB',
        'interests' => ['Game', 'Âm nhạc'],
    ], $override);
}

it('DoD C2: onboarding day du -> luu 12 truong, sinh invite_code, redirect /assessment', function () {
    $user = newStudent();

    $this->actingAs($user)
        ->post('/onboarding', validPayload())
        ->assertRedirect(route('assessment'));

    $student = $user->student->fresh();

    expect($student->status)->toBe(Student::STATUS_ONBOARDED)
        ->and($student->grade)->toBe(8)
        ->and((float) $student->math_gpa)->toBe(7.5)
        ->and($student->tutor_gender)->toBe('co')
        ->and($student->favorite_color)->toBe('#2563EB')
        ->and($student->interests)->toBe(['Game', 'Âm nhạc'])
        ->and($student->invite_code)->not->toBeNull()
        ->and($student->invite_code)->toStartWith('HT');
});

it('DoD C2: thieu field -> 422 voi danh sach loi', function () {
    $user = newStudent();

    $this->actingAs($user)
        ->post('/onboarding', validPayload(['full_name' => '', 'grade' => null, 'math_gpa' => null]))
        ->assertSessionHasErrors(['full_name', 'grade', 'math_gpa']);

    expect($user->student->fresh()->status)->toBe(Student::STATUS_REGISTERED);
});

it('grade ngoai 6..12 bi tu choi', function (int $grade) {
    $user = newStudent();

    $this->actingAs($user)
        ->post('/onboarding', validPayload(['grade' => $grade]))
        ->assertSessionHasErrors('grade');
})->with([5, 13, 0]);

it('math_gpa ngoai 0..10 bi tu choi', function (float $gpa) {
    $user = newStudent();

    $this->actingAs($user)
        ->post('/onboarding', validPayload(['math_gpa' => $gpa]))
        ->assertSessionHasErrors('math_gpa');
})->with([-1, 11, 15.5]);

it('favorite_color ngoai bang 10 mau bi tu choi (chong hex tu do)', function () {
    $user = newStudent();

    $this->actingAs($user)
        ->post('/onboarding', validPayload(['favorite_color' => '#ff0000']))
        ->assertSessionHasErrors('favorite_color');
});

it('invite_code la duy nhat va on dinh khi onboard lai', function () {
    $user = newStudent();

    $this->actingAs($user)->post('/onboarding', validPayload());
    $code1 = $user->student->fresh()->invite_code;

    // Onboard lai (vd sua ho so) khong duoc doi invite_code -> link parent-child khong hong.
    $this->actingAs($user)->post('/onboarding', validPayload(['grade' => 9]));
    $code2 = $user->student->fresh()->invite_code;

    expect($code2)->toBe($code1)
        ->and($user->student->fresh()->grade)->toBe(9);
});

it('phu huynh khong onboard duoc (chi student)', function () {
    $parent = User::where('email', 'parent1@hoctoan.test')->first();

    $this->actingAs($parent)->post('/onboarding', validPayload())->assertStatus(403);
});

it('trang onboarding hien thi form', function () {
    $this->actingAs(newStudent())->get('/onboarding')->assertOk()->assertSee('Hoàn thiện hồ sơ');
});
