<?php

use App\Jobs\GenerateSyllabusJob;
use App\Models\Syllabus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(Database\Seeders\AiProviderSeeder::class);
});

function syllabusAdmin(): User
{
    return User::create([
        'name' => 'Admin', 'email' => 'sa.'.uniqid().'@ht.test',
        'password' => 'password', 'role' => User::ROLE_ADMIN,
    ]);
}

/** Body Gemini gia: candidates[0].content.parts[0].text = JSON. */
function geminiSyllabus(string $json): array
{
    return ['candidates' => [['content' => ['parts' => [['text' => $json]]]]]];
}

/** JSON giao trinh mau hop le theo CurriculumService::SCHEMA. */
function fakeSyllabusJson(): string
{
    return json_encode([
        'goal' => 'Nắm vững hàm số bậc nhất',
        'planned_sessions' => 12,
        'modules' => [
            [
                'phase' => 1, 'topic' => 'Ôn nền tảng',
                'lessons' => [
                    [
                        'title' => 'Khái niệm hàm số',
                        'theory' => 'Hàm số $y=ax+b$ với $a\neq 0$.',
                        'exercises' => [
                            ['difficulty' => 'easy', 'content' => 'Tính $f(2)$ với $f(x)=2x+1$', 'answer' => '5'],
                            ['difficulty' => 'medium', 'content' => 'Vẽ đồ thị $y=x-3$', 'answer' => 'đường thẳng'],
                            // thieu 'hard' -> service phai tu bu du 3 muc
                        ],
                    ],
                ],
            ],
        ],
    ]);
}

it('tao giao trinh -> dispatch job (khong sinh ngay trong request)', function () {
    Queue::fake();

    $this->actingAs(syllabusAdmin())
        ->post(route('admin.syllabi.store'), [
            'title' => 'Toán 9 — Hàm số', 'grade' => 9, 'topic' => 'Hàm số bậc nhất',
        ])
        ->assertRedirect();

    $s = Syllabus::first();
    expect($s->status)->toBe('generating')
        ->and($s->grade)->toBe(9);
    Queue::assertPushed(GenerateSyllabusJob::class);
});

it('job sinh day du: content dung khung + du 3 muc bai tap + status ready', function () {
    Http::fake(['*' => Http::response(geminiSyllabus(fakeSyllabusJson()))]);

    $s = Syllabus::create([
        'title' => 'Toán 9', 'grade' => 9, 'status' => 'generating',
    ]);

    (new GenerateSyllabusJob($s->id))->handle(app(App\Services\SyllabusService::class));

    $s->refresh();
    expect($s->status)->toBe('ready')
        ->and($s->planned_sessions)->toBe(12)
        ->and($s->content['modules'])->toHaveCount(1)
        ->and($s->lessonCount())->toBe(1);

    // Bai dau: thieu 'hard' trong input -> service bu du 3 muc easy/medium/hard.
    $exercises = $s->content['modules'][0]['lessons'][0]['exercises'];
    expect(collect($exercises)->pluck('difficulty')->all())->toBe(['easy', 'medium', 'hard']);
});

it('job that bai -> failed() danh dau status=failed + ly do', function () {
    $s = Syllabus::create(['title' => 'X', 'grade' => 9, 'status' => 'generating']);

    (new GenerateSyllabusJob($s->id))->failed(new RuntimeException('AI het key'));

    $s->refresh();
    expect($s->status)->toBe('failed')
        ->and($s->error)->toContain('AI het key');
});

it('trang xem: dang sinh hien spinner, xong hien noi dung', function () {
    Http::fake(['*' => Http::response(geminiSyllabus(fakeSyllabusJson()))]);
    $admin = syllabusAdmin();

    $s = Syllabus::create(['title' => 'Toán 9', 'grade' => 9, 'status' => 'generating']);
    $this->actingAs($admin)->get(route('admin.syllabi.show', $s))
        ->assertOk()->assertSee('đang soạn giáo trình', false);

    (new GenerateSyllabusJob($s->id))->handle(app(App\Services\SyllabusService::class));
    $this->actingAs($admin)->get(route('admin.syllabi.show', $s->fresh()))
        ->assertOk()->assertSee('Khái niệm hàm số');
});

it('hoc sinh khong vao duoc trang giao trinh (403)', function () {
    $student = User::create([
        'name' => 'S', 'email' => 'st.'.uniqid().'@ht.test', 'password' => 'password', 'role' => User::ROLE_STUDENT,
    ]);
    $this->actingAs($student)->get(route('admin.syllabi'))->assertForbidden();
});
