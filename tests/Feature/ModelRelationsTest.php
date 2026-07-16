<?php

use App\Models\Assessment;
use App\Models\Curriculum;
use App\Models\CurriculumModule;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\ParentAccount;
use App\Models\Quiz;
use App\Models\Student;
use App\Models\StudentClassification;
use App\Models\User;

/*
 * Ticket F3 — DoD: Student::first()->activeCurriculum->modules chay duoc.
 * Test nay dung chuoi that: student -> classification -> curriculum -> module
 * -> lesson -> exercise/quiz.
 */

function seedChain(): Student
{
    $user = User::create([
        'name'     => 'Nguyen Van A',
        'email'    => 'chain.'.uniqid().'@hoctoan.test',
        'password' => 'password',
        'role'     => User::ROLE_STUDENT,
    ]);

    $student = Student::create([
        'user_id'             => $user->id,
        'full_name'           => 'Nguyen Van A',
        'date_of_birth'       => '2011-03-15',
        'address'             => 'Ha Noi',
        'phone'               => '0911222333',
        'school_name'         => 'THCS Chu Van An',
        'grade'               => 7,
        'self_assessed_level' => 'kha',
        'math_gpa'            => 7.0,
        'status'              => Student::STATUS_CURRICULUM_ACTIVE,
        'invite_code'         => strtoupper(substr(uniqid(), -8)),
    ]);

    $assessment = Assessment::create([
        'student_id' => $student->id,
        'status'     => Assessment::STATUS_GRADED,
        'score'      => 6.5,
        'started_at' => now()->subHour(),
    ]);

    $classification = StudentClassification::create([
        'student_id'          => $student->id,
        'assessment_id'       => $assessment->id,
        'overall_ability'     => 62,
        'self_learning_level' => 50,
        'processing_speed'    => 45,
        'base_level'          => 'kha',
        'final_level'         => 'trung_binh',      // tang 2 lat nguoc tang 1
        'weak_topics'         => ['phan_so', 'so_nguyen'],
    ]);

    $curriculum = Curriculum::create([
        'student_id'        => $student->id,
        'classification_id' => $classification->id,
        'status'            => 'active',
        'goal'              => 'Cai thien phan so',
        'planned_sessions'  => 20,
    ]);

    $module = CurriculumModule::create([
        'curriculum_id' => $curriculum->id,
        'phase'         => Curriculum::PHASE_ON_NEN_TANG,
        'topic'         => 'phan_so',
        'module_order'  => 1,
    ]);

    $lesson = Lesson::create([
        'module_id'      => $module->id,
        'lesson_order'   => 1,
        'title'          => 'Phan so co ban',
        'theory_content' => 'Ly thuyet toi gian.',
        'status'         => Lesson::STATUS_UNLOCKED,
    ]);

    foreach (['easy', 'medium', 'hard'] as $level) {
        Exercise::create([
            'lesson_id'  => $lesson->id,
            'difficulty' => $level,
            'content'    => "Bai tap muc {$level}",
            'answer'     => ['value' => 1],
        ]);
    }

    Quiz::create([
        'lesson_id'        => $lesson->id,
        'duration_minutes' => config('hoctoan.quiz.duration_minutes'),
    ]);

    return $student;
}

it('DoD F3: activeCurriculum->modules chay duoc', function () {
    seedChain();

    $modules = Student::first()->activeCurriculum->modules;

    expect($modules)->toHaveCount(1)
        ->and($modules->first()->topic)->toBe('phan_so')
        ->and($modules->first()->phase)->toBe(Curriculum::PHASE_ON_NEN_TANG);
});

it('di het chuoi curriculum -> module -> lesson -> exercise + quiz', function () {
    seedChain();

    $lesson = Student::first()->activeCurriculum->modules->first()->lessons->first();

    expect($lesson->title)->toBe('Phan so co ban')
        ->and($lesson->exercises)->toHaveCount(3)
        ->and($lesson->exercises->pluck('difficulty')->sort()->values()->all())
        ->toBe(['easy', 'hard', 'medium'])
        ->and($lesson->quiz)->not->toBeNull()
        ->and($lesson->quiz->duration_minutes)->toBe(15);
});

it('activeCurriculum bo qua curriculum da archived', function () {
    $student = seedChain();

    $student->activeCurriculum->update(['status' => 'archived']);

    expect($student->fresh()->activeCurriculum)->toBeNull();
});

it('cast json ve array dung', function () {
    seedChain();

    $classification = Student::first()->latestClassification;

    expect($classification->weak_topics)->toBeArray()
        ->and($classification->weak_topics)->toBe(['phan_so', 'so_nguyen']);
});

it('phat hien duoc khi AI tang 2 lat nguoc tang 1', function () {
    seedChain();

    $classification = Student::first()->latestClassification;

    expect($classification->base_level)->toBe('kha')
        ->and($classification->final_level)->toBe('trung_binh')
        ->and($classification->aiOverrodeBaseLevel())->toBeTrue();
});

it('1 phu huynh link duoc nhieu con', function () {
    $studentA = seedChain();
    $studentB = seedChain();

    $parentUser = User::create([
        'name'     => 'Phu huynh',
        'email'    => 'parent.'.uniqid().'@hoctoan.test',
        'password' => 'password',
        'role'     => User::ROLE_PARENT,
    ]);

    $parent = ParentAccount::create([
        'user_id'             => $parentUser->id,
        'full_name'           => 'Tran Thi B',
        'phone'               => '0988777666',
        'relation_to_student' => 'me',
    ]);

    $parent->children()->attach([
        $studentA->id => ['linked_via' => 'invite_code'],
        $studentB->id => ['linked_via' => 'admin'],
    ]);

    expect($parent->children)->toHaveCount(2)
        ->and($parent->canView($studentA))->toBeTrue();
});

it('state machine: hasReachedStatus khong cho nhay coc', function () {
    $student = seedChain();   // status = curriculum_active

    expect($student->hasReachedStatus(Student::STATUS_ASSESSED))->toBeTrue()
        ->and($student->hasReachedStatus(Student::STATUS_CURRICULUM_ACTIVE))->toBeTrue()
        ->and($student->hasReachedStatus(Student::STATUS_LEARNING))->toBeFalse();
});
