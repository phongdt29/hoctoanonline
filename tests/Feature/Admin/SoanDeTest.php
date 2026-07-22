<?php

use App\Models\AiLog;
use App\Models\Assessment;
use App\Models\Curriculum;
use App\Models\CurriculumModule;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\StudentClassification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(Database\Seeders\AiProviderSeeder::class);
});

/** Fake body Gemini: candidates[0].content.parts[0].text = JSON. */
function geminiJson(string $json): array
{
    return ['candidates' => [['content' => ['parts' => [['text' => $json]]]]]];
}

function makeAdmin(): User
{
    return User::create([
        'name' => 'Admin', 'email' => 'a.'.uniqid().'@ht.test',
        'password' => 'password', 'role' => User::ROLE_ADMIN,
    ]);
}

function makeLesson(): Lesson
{
    $user = User::create([
        'name' => 'HS', 'email' => 's.'.uniqid().'@ht.test',
        'password' => 'password', 'role' => User::ROLE_STUDENT,
    ]);
    $student = Student::create([
        'user_id' => $user->id, 'full_name' => 'Hoc Sinh', 'date_of_birth' => '2011-01-01',
        'address' => 'HN', 'phone' => '0900000000', 'school_name' => 'THCS X', 'grade' => 8,
        'self_assessed_level' => 'kha', 'math_gpa' => 7.0,
        'status' => Student::STATUS_CURRICULUM_ACTIVE, 'invite_code' => strtoupper(substr(uniqid(), -8)),
    ]);
    $assessment = Assessment::create([
        'student_id' => $student->id, 'status' => Assessment::STATUS_GRADED,
        'score' => 7.0, 'started_at' => now()->subHour(),
    ]);
    $classification = StudentClassification::create([
        'student_id' => $student->id, 'assessment_id' => $assessment->id,
        'overall_ability' => 60, 'self_learning_level' => 50,
        'processing_speed' => 50, 'base_level' => 'kha', 'final_level' => 'kha', 'weak_topics' => ['phan_so'],
    ]);
    $curriculum = Curriculum::create([
        'student_id' => $student->id, 'classification_id' => $classification->id,
        'status' => 'active', 'goal' => 'x', 'planned_sessions' => 20,
    ]);
    $module = CurriculumModule::create([
        'curriculum_id' => $curriculum->id, 'phase' => Curriculum::PHASE_ON_NEN_TANG,
        'topic' => 'phan_so', 'module_order' => 1,
    ]);

    return Lesson::create([
        'module_id' => $module->id, 'lesson_order' => 1, 'title' => 'Phân số',
        'theory_content' => 'Lý thuyết.', 'status' => Lesson::STATUS_UNLOCKED,
    ]);
}

it('AI sinh de: tao Exercise tu Gemini + ghi ai_logs feature authoring', function () {
    $lesson = makeLesson();
    Http::fake(['*' => Http::response(geminiJson(json_encode([
        'exercises' => [
            ['difficulty' => 'easy',   'content' => 'Rút gọn $\frac{2}{4}$', 'answer' => '$\frac{1}{2}$'],
            ['difficulty' => 'medium', 'content' => 'Tính $\frac{1}{2}+\frac{1}{3}$', 'answer' => '$\frac{5}{6}$'],
        ],
    ])))]);

    $this->actingAs(makeAdmin())
        ->post(route('admin.lessons.ai-generate', $lesson), [
            'topic' => 'Phân số', 'grade' => 8, 'difficulty' => 'medium', 'count' => 2,
        ])
        ->assertRedirect(route("admin.lessons.edit", $lesson));

    expect($lesson->exercises()->count())->toBe(2);
    expect(Exercise::where('lesson_id', $lesson->id)->where('difficulty', 'easy')->first()->answer)
        ->toBe(['value' => '$\frac{1}{2}$']);
    expect(AiLog::where('feature', AiLog::FEATURE_AUTHORING)->where('status', 'ok')->exists())->toBeTrue();
});

it('OCR anh: nhan dien de tu anh tao Exercise', function () {
    $lesson = makeLesson();
    Http::fake(['*' => Http::response(geminiJson(json_encode([
        'exercises' => [
            ['content' => 'Giải $x^2-5x+6=0$', 'answer' => '$x=2;x=3$'],
        ],
    ])))]);

    $this->actingAs(makeAdmin())
        ->post(route('admin.lessons.ocr', $lesson), [
            'image' => UploadedFile::fake()->image('de.jpg', 400, 300),
        ])
        ->assertRedirect(route("admin.lessons.edit", $lesson));

    expect($lesson->exercises()->count())->toBe(1);
    expect($lesson->exercises()->first()->content)->toContain('x^2-5x+6');
});

it('OCR tu choi file khong phai anh (422)', function () {
    $lesson = makeLesson();
    $this->actingAs(makeAdmin())
        ->post(route('admin.lessons.ocr', $lesson), [
            'image' => UploadedFile::fake()->create('de.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors('image');
});

it('CRUD thu cong: sua noi dung+dap an, them cau moi, xoa cau', function () {
    $lesson = makeLesson();
    $keep = Exercise::create(['lesson_id' => $lesson->id, 'difficulty' => 'easy', 'content' => 'Cũ', 'answer' => ['value' => 'a']]);
    $del  = Exercise::create(['lesson_id' => $lesson->id, 'difficulty' => 'hard', 'content' => 'Xoá', 'answer' => ['value' => 'b']]);

    $this->actingAs(makeAdmin())
        ->put(route('admin.lessons.update', $lesson), [
            'title' => 'Tiêu đề mới',
            'theory_content' => 'Lý thuyết $a^2$.',
            'exercises' => [
                $keep->id => ['difficulty' => 'medium', 'content' => 'Đã sửa $x$', 'answer' => 'đáp án mới'],
                $del->id  => ['content' => 'Xoá', '_delete' => '1'],
                'new_1'   => ['difficulty' => 'hard', 'content' => 'Câu mới $y$', 'answer' => 'z'],
            ],
        ])
        ->assertRedirect(route("admin.lessons.edit", $lesson));

    expect($lesson->fresh()->title)->toBe('Tiêu đề mới');
    expect(Exercise::find($del->id))->toBeNull();                 // da xoa
    $keep->refresh();
    expect($keep->content)->toBe('Đã sửa $x$')
        ->and($keep->difficulty)->toBe('medium')
        ->and($keep->answer)->toBe(['value' => 'đáp án mới']);
    expect($lesson->exercises()->where('content', 'Câu mới $y$')->exists())->toBeTrue();   // cau moi
});

it('nhap nhanh: moi dong 1 cau, tach dap an bang |, tien to do kho, bo so thu tu', function () {
    $lesson = makeLesson();

    $this->actingAs(makeAdmin())
        ->post(route('admin.lessons.bulk', $lesson), ['bulk' => <<<'TXT'
            Rút gọn $\frac{2}{4}$ | $\frac{1}{2}$
            [khó] Giải $x^2-5x+6=0$ | x=2; x=3
            Câu 3: Tính diện tích hình tròn bán kính 3

            [dễ] Tính $1+1$
            TXT])
        ->assertRedirect(route('admin.lessons.edit', $lesson));

    expect($lesson->exercises()->count())->toBe(4);          // dong trong bi bo qua

    $hard = $lesson->exercises()->where('difficulty', 'hard')->first();
    expect($hard->content)->toBe('Giải $x^2-5x+6=0$')        // tien to [khó] da tach khoi de
        ->and($hard->answer)->toBe(['value' => 'x=2; x=3']); // dap an sau dau |

    expect($lesson->exercises()->where('content', 'Tính diện tích hình tròn bán kính 3')->exists())
        ->toBeTrue();                                        // "Câu 3:" da bi bo
    expect($lesson->exercises()->where('difficulty', 'easy')->first()->content)->toBe('Tính $1+1$');
});

it('nhap nhanh: o trong -> loi validate', function () {
    $lesson = makeLesson();
    $this->actingAs(makeAdmin())
        ->post(route('admin.lessons.bulk', $lesson), ['bulk' => "   \n  \n"])   // TrimStrings -> rong
        ->assertSessionHasErrors('bulk');
    expect($lesson->exercises()->count())->toBe(0);
});

it('nhap nhanh: co chu nhung khong dong nao thanh cau -> bao loi', function () {
    $lesson = makeLesson();
    $this->actingAs(makeAdmin())
        ->post(route('admin.lessons.bulk', $lesson), ['bulk' => "1.\nCâu 2:\n3)"])   // chi co so thu tu
        ->assertSessionHas('error');
    expect($lesson->exercises()->count())->toBe(0);
});

it('AI tao cau tuong tu tu mot cau mau', function () {
    $lesson = makeLesson();
    Http::fake(['*' => Http::response(geminiJson(json_encode([
        'exercises' => [
            ['difficulty' => 'hard', 'content' => 'Giải $x^2-7x+12=0$', 'answer' => '$x=3;x=4$'],
            ['difficulty' => 'hard', 'content' => 'Giải $x^2-9x+20=0$', 'answer' => '$x=4;x=5$'],
        ],
    ])))]);

    $this->actingAs(makeAdmin())
        ->post(route('admin.lessons.similar', $lesson), [
            'source' => 'Giải $x^2-5x+6=0$', 'count' => 2, 'difficulty' => 'hard',
        ])
        ->assertRedirect(route('admin.lessons.edit', $lesson));

    expect($lesson->exercises()->count())->toBe(2)
        ->and($lesson->exercises()->where('difficulty', 'hard')->count())->toBe(2);
    expect(AiLog::where('feature', AiLog::FEATURE_AUTHORING)->where('status', 'ok')->exists())->toBeTrue();
});

it('hoc sinh khong vao duoc trang soan de (403)', function () {
    $lesson = makeLesson();
    $student = User::create([
        'name' => 'S', 'email' => 'x.'.uniqid().'@ht.test', 'password' => 'password', 'role' => User::ROLE_STUDENT,
    ]);
    $this->actingAs($student)->get(route('admin.lessons.edit', $lesson))->assertForbidden();
});
