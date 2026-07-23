<?php

use App\Models\Curriculum;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Syllabus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function assignAdmin(): User
{
    return User::create(['name' => 'Ad', 'email' => 'aa.'.uniqid().'@ht.test', 'password' => 'password', 'role' => User::ROLE_ADMIN]);
}

function newStudentFor(): Student
{
    $u = User::create(['name' => 'HS', 'email' => 'hs.'.uniqid().'@ht.test', 'password' => 'password', 'role' => User::ROLE_STUDENT]);

    return Student::create([
        'user_id' => $u->id, 'full_name' => 'Bé An', 'date_of_birth' => '2015-01-01',
        'address' => 'HN', 'phone' => '0900000000', 'school_name' => 'Tiểu học X', 'grade' => 3,
        'self_assessed_level' => 'kha', 'math_gpa' => 7.0,
        'status' => Student::STATUS_ONBOARDED, 'invite_code' => strtoupper(substr(uniqid(), -8)),
    ]);
}

function readySyllabus(): Syllabus
{
    return Syllabus::create([
        'title' => 'Toán 3', 'grade' => 3, 'planned_sessions' => 4, 'status' => 'ready',
        'content' => [
            'goal' => 'Phép nhân chia', 'planned_sessions' => 4,
            'modules' => [
                ['phase' => 1, 'topic' => 'Nhân chia', 'lessons' => [
                    ['title' => 'Bảng nhân 2', 'theory' => 'Lý thuyết $2\times x$', 'exercises' => [
                        ['difficulty' => 'easy', 'content' => '$2\times 3=?$', 'answer' => '6'],
                        ['difficulty' => 'medium', 'content' => '...', 'answer' => 'x'],
                        ['difficulty' => 'hard', 'content' => '...', 'answer' => 'y'],
                    ]],
                    ['title' => 'Bảng nhân 3', 'theory' => 'Lý thuyết', 'exercises' => []],
                ]],
            ],
        ],
    ]);
}

it('gan giao trinh: clone thanh lo trinh that, mo bai dau, hoc sinh -> learning', function () {
    $syllabus = readySyllabus();
    $student = newStudentFor();

    $this->actingAs(assignAdmin())
        ->post(route('admin.syllabi.assign', $syllabus), ['student_id' => $student->id])
        ->assertRedirect();

    $curriculum = Curriculum::where('student_id', $student->id)->where('status', 'active')->first();
    expect($curriculum)->not->toBeNull()
        ->and($curriculum->modules()->count())->toBe(1);

    $lessons = Lesson::whereIn('module_id', $curriculum->modules()->pluck('id'))->orderBy('id')->get();
    expect($lessons)->toHaveCount(2)
        ->and($lessons->first()->title)->toBe('Bảng nhân 2')
        ->and($lessons->first()->theory_content)->toContain('2\times x')
        ->and($lessons->first()->status)->toBe(Lesson::STATUS_UNLOCKED)     // bai dau mo
        ->and($lessons->last()->status)->toBe(Lesson::STATUS_LOCKED);       // bai sau khoa

    // Bai co exercises -> clone du; moi bai co quiz.
    expect($lessons->first()->exercises()->count())->toBe(3)
        ->and($lessons->first()->quiz()->exists())->toBeTrue();

    $student->refresh();
    expect($student->status)->toBe(Student::STATUS_LEARNING);
});

it('gan lai: lo trinh cu bi archive, chi con 1 active', function () {
    $syllabus = readySyllabus();
    $student = newStudentFor();
    $admin = assignAdmin();

    $this->actingAs($admin)->post(route('admin.syllabi.assign', $syllabus), ['student_id' => $student->id]);
    $this->actingAs($admin)->post(route('admin.syllabi.assign', $syllabus), ['student_id' => $student->id]);

    expect(Curriculum::where('student_id', $student->id)->where('status', 'active')->count())->toBe(1)
        ->and(Curriculum::where('student_id', $student->id)->where('status', 'archived')->count())->toBe(1);
});

it('khong gan duoc giao trinh chua san sang', function () {
    $syllabus = Syllabus::create(['title' => 'X', 'grade' => 3, 'status' => 'generating']);
    $student = newStudentFor();

    $this->actingAs(assignAdmin())
        ->post(route('admin.syllabi.assign', $syllabus), ['student_id' => $student->id])
        ->assertSessionHas('error');

    expect(Curriculum::where('student_id', $student->id)->exists())->toBeFalse();
});
