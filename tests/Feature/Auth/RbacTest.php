<?php

use App\Models\ParentAccount;
use App\Models\Student;
use App\Models\User;

/*
 * Ticket A3 — DoD: ma tran quyen. student goi /admin/* -> 403.
 * parent A xem con cua parent B -> 403.
 */

beforeEach(function () {
    $this->seed();
});

function userOf(string $email): User
{
    return User::where('email', $email)->firstOrFail();
}

it('chua dang nhap -> moi trang can auth deu day ve login', function (string $path) {
    $this->get($path)->assertRedirect(route('login'));
})->with(['/dashboard', '/parent', '/teacher/classes', '/admin', '/onboarding']);

it('DoD A3: moi vai tro chi vao duoc trang cua minh', function (string $email, string $path, int $status) {
    $this->actingAs(userOf($email))->get($path)->assertStatus($status);
})->with([
    // student1 (da learning) vao duoc trang hoc sinh
    ['student1@hoctoan.test', '/dashboard', 200],
    // ...nhung bi chan khoi moi trang cua vai tro khac
    ['student1@hoctoan.test', '/admin', 403],
    ['student1@hoctoan.test', '/parent', 403],
    ['student1@hoctoan.test', '/teacher/classes', 403],

    ['parent1@hoctoan.test', '/parent', 200],
    ['parent1@hoctoan.test', '/admin', 403],
    ['parent1@hoctoan.test', '/dashboard', 403],

    ['teacher1@hoctoan.test', '/teacher/classes', 200],
    ['teacher1@hoctoan.test', '/admin', 403],
    ['teacher1@hoctoan.test', '/parent', 403],

    ['admin@hoctoan.test', '/admin', 200],
    ['admin@hoctoan.test', '/parent', 403],
    ['admin@hoctoan.test', '/teacher/classes', 403],
]);

it('EnsureStudentAssessed: chua onboard -> bi day ve onboarding', function () {
    // student2 dang o `registered`
    $this->actingAs(userOf('student2@hoctoan.test'))
        ->get('/dashboard')
        ->assertRedirect(route('onboarding'));
});

it('EnsureStudentAssessed: da onboard nhung chua assessed -> bi day sang assessment', function () {
    $student = userOf('student2@hoctoan.test')->student;
    $student->update(['status' => Student::STATUS_ONBOARDED]);

    $this->actingAs(userOf('student2@hoctoan.test'))
        ->get('/dashboard')
        ->assertRedirect(route('assessment'));
});

it('EnsureStudentAssessed: tu `assessed` tro di thi cho qua', function (string $status) {
    $student = userOf('student2@hoctoan.test')->student;
    $student->update(['status' => $status]);

    $this->actingAs(userOf('student2@hoctoan.test'))->get('/dashboard')->assertOk();
})->with([
    Student::STATUS_ASSESSED,
    Student::STATUS_CLASSIFIED,
    Student::STATUS_CURRICULUM_ACTIVE,
    Student::STATUS_LEARNING,
]);

it('DoD A3: parent A KHONG xem duoc con cua parent B', function () {
    $parentA = userOf('parent1@hoctoan.test')->parentAccount;
    $parentB = userOf('parent2@hoctoan.test')->parentAccount;

    $childOfB = $parentB->children->first();

    // canView() la nen tang cua Policy o ticket M4.
    expect($parentA->canView($childOfB))->toBeFalse()
        ->and($parentB->canView($childOfB))->toBeTrue();
});

it('parent chi thay dung cac con da link, khong thay hoc sinh khac', function () {
    $parent2 = userOf('parent2@hoctoan.test')->parentAccount;

    // Seed: parent2 link 2 con (student2, student3)
    expect($parent2->children)->toHaveCount(2);

    Student::all()->each(function (Student $student) use ($parent2) {
        $linked = $parent2->children->contains($student->id);
        expect($parent2->canView($student))->toBe($linked);
    });
});

it('middleware role nhan nhieu vai tro: admin,staff deu vao /admin duoc', function () {
    $staff = User::create([
        'name' => 'Nhan vien',
        'email' => 'staff@hoctoan.test',
        'password' => 'password',
        'role' => User::ROLE_STAFF,
    ]);

    $this->actingAs($staff)->get('/admin')->assertOk();
    $this->actingAs(userOf('admin@hoctoan.test'))->get('/admin')->assertOk();
});

it('api /v1/me can sanctum token', function () {
    $this->getJson('/api/v1/me')->assertStatus(401);

    $user = userOf('student1@hoctoan.test');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.email', 'student1@hoctoan.test')
        ->assertJsonPath('data.role', 'student');
});
