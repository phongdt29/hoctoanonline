<?php

use App\Models\AiProvider;
use App\Models\LearningRiskScore;
use App\Models\PointLedger;
use App\Models\Student;
use App\Models\StudentClassification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/*
 * Ticket F4 — DoD: seed chay sach + login duoc bang account seed.
 *
 * Test nay chay DatabaseSeeder that (khong mock) de bat loi seed som,
 * vi F5..C6 deu dua vao du lieu nay.
 */

beforeEach(function () {
    $this->seed();
});

it('DoD F4: login duoc bang moi account seed', function (string $email) {
    expect(Auth::attempt(['email' => $email, 'password' => 'password']))->toBeTrue();
})->with([
    'admin@hoctoan.test',
    'teacher1@hoctoan.test',
    'teacher2@hoctoan.test',
    'student1@hoctoan.test',
    'student10@hoctoan.test',
    'parent1@hoctoan.test',
    'parent5@hoctoan.test',
]);

it('tu choi sai mat khau', function () {
    expect(Auth::attempt(['email' => 'student1@hoctoan.test', 'password' => 'sai-mat-khau']))
        ->toBeFalse();
});

it('seed dung so luong tung vai tro', function () {
    expect(User::where('role', User::ROLE_ADMIN)->count())->toBe(2)   // admin@hoctoan.test + admin@gmail.com
        ->and(User::where('role', User::ROLE_TEACHER)->count())->toBe(2)
        ->and(User::where('role', User::ROLE_STUDENT)->count())->toBe(10)
        ->and(User::where('role', User::ROLE_PARENT)->count())->toBe(5);
});

it('10 hoc sinh chia dung 3 muc hoc luc: 3 trung_binh, 4 kha, 3 gioi', function () {
    expect(Student::where('self_assessed_level', 'trung_binh')->count())->toBe(3)
        ->and(Student::where('self_assessed_level', 'kha')->count())->toBe(4)
        ->and(Student::where('self_assessed_level', 'gioi')->count())->toBe(3);
});

it('5 phu huynh deu da link it nhat 1 con', function () {
    User::where('role', User::ROLE_PARENT)->get()->each(function (User $user) {
        expect($user->parentAccount->children)->not->toBeEmpty();
    });
});

it('co 2 ai_provider de test failover theo priority', function () {
    $providers = AiProvider::usable()->get();

    expect($providers)->toHaveCount(2)
        ->and($providers->first()->priority)->toBe(1)
        ->and($providers->last()->priority)->toBe(2);
});

it('api key cua provider khong lo ra khi serialize', function () {
    $json = AiProvider::first()->toArray();

    expect($json)->not->toHaveKey('api_key_encrypted');
});

it('student1 co curriculum voi 13/18 lesson completed', function () {
    $student = Student::whereHas('user', fn ($q) => $q->where('email', 'student1@hoctoan.test'))->first();
    $lessons = $student->activeCurriculum->lessons;

    expect($lessons)->toHaveCount(18)
        ->and($lessons->where('status', 'completed'))->toHaveCount(13)
        ->and($lessons->where('status', 'unlocked'))->toHaveCount(1)
        ->and($lessons->where('status', 'locked'))->toHaveCount(4);
});

it('student1 co du 2 tuan attendance voi ca 3 trang thai', function () {
    $student  = Student::whereHas('user', fn ($q) => $q->where('email', 'student1@hoctoan.test'))->first();
    $sessions = $student->attendanceSessions;

    expect($sessions)->toHaveCount(14)
        ->and($sessions->where('attendance_status', 'present'))->toHaveCount(7)
        ->and($sessions->where('attendance_status', 'partial'))->toHaveCount(4)
        ->and($sessions->where('attendance_status', 'absent'))->toHaveCount(3);
});

it('co buoi minh hoa "online 90 phut nhung hoc thuc 8 phut"', function () {
    $student = Student::whereHas('user', fn ($q) => $q->where('email', 'student1@hoctoan.test'))->first();

    $session = $student->attendanceSessions
        ->firstWhere('effective_study_minutes', 8);

    expect($session)->not->toBeNull()
        ->and($session->onlineMinutes())->toBe(90)
        ->and($session->focusRatio())->toBeLessThan(0.1)
        ->and($session->attendance_status)->toBe('partial');
});

it('student1: phan loai tang 2 lat nguoc tang 1', function () {
    $student = Student::whereHas('user', fn ($q) => $q->where('email', 'student1@hoctoan.test'))->first();

    $classification = $student->latestClassification;

    expect($classification->base_level)->toBe(StudentClassification::LEVEL_TRUNG_BINH)
        ->and($classification->final_level)->toBe(StudentClassification::LEVEL_KHA)
        ->and($classification->aiOverrodeBaseLevel())->toBeTrue()
        ->and($classification->topicAbilities)->toHaveCount(5);
});

it('risk score cua student1 tinh dung cong thuc spec §3.6', function () {
    $student = Student::whereHas('user', fn ($q) => $q->where('email', 'student1@hoctoan.test'))->first();
    $risk    = $student->latestRiskScore;

    $weights  = config('hoctoan.risk_weights');
    $expected = 0.0;

    foreach ($risk->components as $key => $rate) {
        $expected += $weights[$key] * $rate;
    }

    expect($risk->risk_score)->toBe((int) round($expected))
        ->and($risk->level)->toBe(LearningRiskScore::LEVEL_CAN_THEO_DOI)
        ->and($risk->components)->toHaveKeys(array_keys($weights));
});

it('points_balance khop tong point_ledger', function () {
    $student = Student::whereHas('user', fn ($q) => $q->where('email', 'student1@hoctoan.test'))->first();

    expect($student->points_balance)
        ->toBe((int) PointLedger::where('student_id', $student->id)->sum('amount'));
});
