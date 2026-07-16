<?php

use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use App\Models\Curriculum;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\StudentClassification;
use App\Models\User;
use App\Services\ClassificationService;
use Illuminate\Support\Facades\Http;

/*
 * Ticket C4 + C5 — DoD:
 *  - gpa 8.5 nhung lam test sai 60% -> final_level != gioi (tang 2 thang tang 1).
 *  - topic_abilities co du moi topic trong de.
 *  - student yeu phan_so -> phase 1 co module phan_so.
 *  - moi lesson >= 3 exercise du 3 muc + dung 1 quiz. Chi 1 lesson unlocked.
 *  - E2E: submit -> (queue sync) -> co curriculum.
 */

beforeEach(function () {
    $this->seed(\Database\Seeders\AiProviderSeeder::class);
});

function makeAssessmentFor(Student $student, array $questions): Assessment
{
    $assessment = Assessment::create([
        'student_id' => $student->id,
        'status' => Assessment::STATUS_IN_PROGRESS,
        'started_at' => now(),
    ]);

    foreach ($questions as $i => $q) {
        AssessmentQuestion::create([
            'assessment_id' => $assessment->id,
            'question_order' => $i + 1,
            'type' => $q['type'] ?? 'multiple_choice',
            'topic' => $q['topic'],
            'difficulty' => $q['difficulty'] ?? 'medium',
            'content' => "Câu {$i}",
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => ['value' => 'A'],
            'student_answer' => ['value' => $q['answer']],
            'time_spent_seconds' => $q['time'] ?? 60,
        ]);
    }

    return $assessment->load('questions', 'student');
}

function studentWithGpa(float $gpa, int $grade = 8): Student
{
    $user = User::create([
        'name' => 'HS', 'email' => 'hs.'.uniqid().'@hoctoan.test', 'password' => 'password', 'role' => 'student',
    ]);

    return Student::create([
        'user_id' => $user->id, 'full_name' => 'HS', 'grade' => $grade,
        'self_assessed_level' => 'gioi', 'math_gpa' => $gpa, 'status' => 'assessed',
        'invite_code' => 'HT'.strtoupper(substr(uniqid(), -6)),
    ]);
}

/** Fake Gemini tra ket qua phan loai cho truoc. */
function fakeClassify(array $override = []): void
{
    $json = json_encode(array_merge([
        'overall_ability' => 45,
        'self_learning_level' => 40,
        'processing_speed' => 50,
        'final_level' => 'trung_binh',
        'weak_topics' => ['phan_so'],
        'topic_abilities' => [
            ['topic' => 'phan_so', 'ability' => 30, 'error_rate' => 70.0],
            ['topic' => 'so_nguyen', 'ability' => 55, 'error_rate' => 40.0],
        ],
    ], $override));

    Http::fake(['*' => Http::response([
        'candidates' => [['content' => ['parts' => [['text' => $json]]]]],
    ])]);
}

it('tang 1: base_level suy dung tu gpa theo nguong config', function () {
    $service = app(ClassificationService::class);

    expect($service->baseLevel(4.5))->toBe('trung_binh')
        ->and($service->baseLevel(7.0))->toBe('kha')
        ->and($service->baseLevel(9.0))->toBe('gioi')
        ->and($service->baseLevel(5.0))->toBe('trung_binh')   // bien: <= 5
        ->and($service->baseLevel(8.0))->toBe('kha');          // bien: <= 8
});

it('DoD C4: gpa 8.5 nhung test sai 60% -> final_level KHAC gioi (tang 2 thang tang 1)', function () {
    // AI (tang 2) nhin bai test that -> ha xuong trung_binh du gpa cao.
    fakeClassify(['final_level' => 'trung_binh']);

    $student = studentWithGpa(8.5);
    $assessment = makeAssessmentFor($student, [
        ['topic' => 'phan_so', 'answer' => 'B'],    // sai
        ['topic' => 'phan_so', 'answer' => 'C'],    // sai
        ['topic' => 'phan_so', 'answer' => 'D'],    // sai
        ['topic' => 'so_nguyen', 'answer' => 'A'],  // dung
        ['topic' => 'so_nguyen', 'answer' => 'A'],  // dung
    ]);
    // cham thu cong: 3/5 sai = 60%
    $assessment->questions->each(fn ($q) => $q->update(['is_correct' => $q->student_answer['value'] === 'A']));
    $assessment->update(['status' => Assessment::STATUS_GRADED, 'score' => 4.0]);

    $classification = app(ClassificationService::class)->classify($assessment);

    expect($classification->base_level)->toBe('gioi')          // tang 1 tu gpa 8.5
        ->and($classification->final_level)->not->toBe('gioi')  // tang 2 ha xuong
        ->and($classification->aiOverrodeBaseLevel())->toBeTrue();
});

it('DoD C4: topic_abilities co du moi topic xuat hien trong de', function () {
    fakeClassify();

    $student = studentWithGpa(6.0);
    $assessment = makeAssessmentFor($student, [
        ['topic' => 'phan_so', 'answer' => 'A'],
        ['topic' => 'so_nguyen', 'answer' => 'B'],
    ]);
    $assessment->update(['status' => Assessment::STATUS_GRADED, 'score' => 5.0]);

    $classification = app(ClassificationService::class)->classify($assessment);

    $topics = $classification->topicAbilities->pluck('topic')->all();
    expect($topics)->toContain('phan_so')
        ->and($topics)->toContain('so_nguyen');
});

it('phan loai xong chuyen status student -> classified', function () {
    fakeClassify();
    $student = studentWithGpa(6.0);
    $assessment = makeAssessmentFor($student, [['topic' => 'phan_so', 'answer' => 'A']]);
    $assessment->update(['status' => Assessment::STATUS_GRADED, 'score' => 5.0]);

    app(ClassificationService::class)->classify($assessment);

    expect($student->fresh()->status)->toBe(Student::STATUS_CLASSIFIED);
});

it('DoD C5: student yeu phan_so -> phase 1 co module phan_so', function () {
    // Chuoi day du: classify tra weak=phan_so, roi curriculum sinh phase 1 co phan_so.
    $classifyJson = json_encode([
        'overall_ability' => 40, 'self_learning_level' => 40, 'processing_speed' => 45,
        'final_level' => 'trung_binh', 'weak_topics' => ['phan_so'],
        'topic_abilities' => [['topic' => 'phan_so', 'ability' => 25, 'error_rate' => 75.0]],
    ]);
    $curriculumJson = json_encode([
        'goal' => 'Lay lai nen tang phan so',
        'planned_sessions' => 15,
        'modules' => [
            ['phase' => 1, 'topic' => 'phan_so', 'lessons' => [
                ['title' => 'Phan so co ban', 'theory' => 'Ly thuyet', 'exercises' => [
                    ['difficulty' => 'easy', 'content' => 'BT1', 'answer' => '1'],
                    ['difficulty' => 'medium', 'content' => 'BT2', 'answer' => '2'],
                    ['difficulty' => 'hard', 'content' => 'BT3', 'answer' => '3'],
                ]],
            ]],
            ['phase' => 2, 'topic' => 'so_nguyen', 'lessons' => [
                ['title' => 'So nguyen', 'theory' => 'Ly thuyet', 'exercises' => [
                    ['difficulty' => 'easy', 'content' => 'BT1', 'answer' => '1'],
                ]],
            ]],
        ],
    ]);

    Http::fake(['*' => Http::sequence()
        ->push(['candidates' => [['content' => ['parts' => [['text' => $classifyJson]]]]]])
        ->push(['candidates' => [['content' => ['parts' => [['text' => $curriculumJson]]]]]]),
    ]);

    $student = studentWithGpa(4.5);
    $assessment = makeAssessmentFor($student, [['topic' => 'phan_so', 'answer' => 'B']]);
    $assessment->update(['status' => Assessment::STATUS_GRADED, 'score' => 3.0]);

    $classification = app(ClassificationService::class)->classify($assessment);
    app(\App\Services\CurriculumService::class)->generate($classification);

    $curriculum = $student->fresh()->activeCurriculum;
    $phase1 = $curriculum->modules->where('phase', 1);

    expect($phase1->pluck('topic'))->toContain('phan_so')
        ->and($student->fresh()->status)->toBe(Student::STATUS_CURRICULUM_ACTIVE);
});

it('DoD C5: moi lesson co dung 3 exercise (3 muc) + 1 quiz, chi 1 lesson unlocked', function () {
    $classifyJson = json_encode([
        'overall_ability' => 40, 'self_learning_level' => 40, 'processing_speed' => 45,
        'final_level' => 'trung_binh', 'weak_topics' => ['phan_so'],
        'topic_abilities' => [['topic' => 'phan_so', 'ability' => 25, 'error_rate' => 75.0]],
    ]);
    // AI co tinh tra thieu muc hard o lesson 1 -> service phai tu bu du 3 muc.
    $curriculumJson = json_encode([
        'goal' => 'On tap', 'planned_sessions' => 12,
        'modules' => [
            ['phase' => 1, 'topic' => 'phan_so', 'lessons' => [
                ['title' => 'L1', 'theory' => 'T', 'exercises' => [
                    ['difficulty' => 'easy', 'content' => 'e', 'answer' => '1'],
                    ['difficulty' => 'medium', 'content' => 'm', 'answer' => '2'],
                ]],
                ['title' => 'L2', 'theory' => 'T', 'exercises' => [
                    ['difficulty' => 'hard', 'content' => 'h', 'answer' => '3'],
                ]],
            ]],
        ],
    ]);

    Http::fake(['*' => Http::sequence()
        ->push(['candidates' => [['content' => ['parts' => [['text' => $classifyJson]]]]]])
        ->push(['candidates' => [['content' => ['parts' => [['text' => $curriculumJson]]]]]]),
    ]);

    $student = studentWithGpa(4.5);
    $assessment = makeAssessmentFor($student, [['topic' => 'phan_so', 'answer' => 'B']]);
    $assessment->update(['status' => Assessment::STATUS_GRADED, 'score' => 3.0]);

    $classification = app(ClassificationService::class)->classify($assessment);
    app(\App\Services\CurriculumService::class)->generate($classification);

    $lessons = $student->fresh()->activeCurriculum->lessons;

    // Moi lesson du 3 muc + 1 quiz.
    $lessons->each(function (Lesson $lesson) {
        expect($lesson->exercises)->toHaveCount(3)
            ->and($lesson->exercises->pluck('difficulty')->sort()->values()->all())->toBe(['easy', 'hard', 'medium'])
            ->and($lesson->quiz)->not->toBeNull();
    });

    // Chi 1 lesson unlocked (lesson dau), con lai locked.
    expect($lessons->where('status', 'unlocked'))->toHaveCount(1)
        ->and($lessons->where('status', 'locked')->count())->toBe($lessons->count() - 1);
});

it('DoD C6 (E2E): submit -> chuoi tu dong chay -> co curriculum', function () {
    // Queue sync trong test -> job chay inline, ca chuoi Grade->Classify->Generate.
    $classifyJson = json_encode([
        'overall_ability' => 50, 'self_learning_level' => 45, 'processing_speed' => 55,
        'final_level' => 'kha', 'weak_topics' => ['phan_so'],
        'topic_abilities' => [['topic' => 'phan_so', 'ability' => 40, 'error_rate' => 55.0]],
    ]);
    $curriculumJson = json_encode([
        'goal' => 'On tap', 'planned_sessions' => 12,
        'modules' => [['phase' => 1, 'topic' => 'phan_so', 'lessons' => [
            ['title' => 'L1', 'theory' => 'T', 'exercises' => [
                ['difficulty' => 'easy', 'content' => 'e', 'answer' => '1'],
                ['difficulty' => 'medium', 'content' => 'm', 'answer' => '2'],
                ['difficulty' => 'hard', 'content' => 'h', 'answer' => '3'],
            ]],
        ]]],
    ]);

    Http::fake(['*' => Http::sequence()
        ->push(['candidates' => [['content' => ['parts' => [['text' => $classifyJson]]]]]])
        ->push(['candidates' => [['content' => ['parts' => [['text' => $curriculumJson]]]]]]),
    ]);

    $student = studentWithGpa(6.5);
    $user = $student->user;
    $assessment = makeAssessmentFor($student, [['topic' => 'phan_so', 'answer' => 'A']]);
    // Bai chi co trac nghiem -> cham bang rule, khong goi AI o buoc grade.

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/assessments/{$assessment->id}/submit")
        ->assertOk();

    // Ca chuoi da chay xong (sync).
    $student->refresh();
    expect($student->status)->toBe(Student::STATUS_CURRICULUM_ACTIVE)
        ->and($assessment->fresh()->status)->toBe(Assessment::STATUS_GRADED)
        ->and(StudentClassification::where('student_id', $student->id)->exists())->toBeTrue()
        ->and($student->activeCurriculum)->not->toBeNull()
        ->and($student->activeCurriculum->lessons->count())->toBeGreaterThan(0);
});

it('submit lan 2 khong chay lai chuoi (idempotent)', function () {
    Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => '{}']]]]]])]);

    $student = studentWithGpa(6.5);
    $assessment = makeAssessmentFor($student, [['topic' => 'phan_so', 'answer' => 'A']]);
    $assessment->update(['status' => Assessment::STATUS_SUBMITTED, 'submitted_at' => now()]);

    $this->actingAs($student->user, 'sanctum')
        ->postJson("/api/v1/assessments/{$assessment->id}/submit")
        ->assertOk()
        ->assertJsonPath('message', 'Bài kiểm tra đã được nộp trước đó.');
});
