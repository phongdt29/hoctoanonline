<?php

use App\Jobs\GenerateExamJob;
use App\Models\Exam;
use App\Models\User;
use App\Services\ExamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(Database\Seeders\AiProviderSeeder::class));

function examAdmin(): User
{
    return User::create(['name' => 'Ad', 'email' => 'ex.'.uniqid().'@ht.test', 'password' => 'password', 'role' => User::ROLE_ADMIN]);
}

function geminiExam(string $json): array
{
    return ['candidates' => [['content' => ['parts' => [['text' => $json]]]]]];
}

/** 3 cau MCQ hop le. */
function fakeExamJson(): string
{
    return json_encode(['questions' => [
        ['content' => '$2+2=?$', 'options' => ['3', '4', '5', '6'], 'correct' => 1, 'difficulty' => 'easy', 'topic' => 'cong'],
        ['content' => '$3\times 3=?$', 'options' => ['6', '9', '12', '3'], 'correct' => 1, 'difficulty' => 'easy', 'topic' => 'nhan'],
        ['content' => '$10-4=?$', 'options' => ['5', '6', '7', '8'], 'correct' => 1, 'difficulty' => 'easy', 'topic' => 'tru'],
    ]]);
}

function readyExam(): Exam
{
    $e = Exam::create(['title' => 'KT', 'grade' => 6, 'question_count' => 3, 'status' => 'generating']);
    Http::fake(['*' => Http::response(geminiExam(fakeExamJson()))]);
    (new GenerateExamJob($e->id))->handle(app(ExamService::class));

    return $e->fresh();
}

it('tao de -> dispatch job', function () {
    Queue::fake();
    $this->actingAs(examAdmin())
        ->post(route('admin.exams.store'), ['title' => 'KT 15p', 'grade' => 6, 'difficulty' => 'mixed', 'question_count' => 10])
        ->assertRedirect();
    expect(Exam::first()->status)->toBe('generating');
    Queue::assertPushed(GenerateExamJob::class);
});

it('job sinh de: chuan hoa 4 lua chon, correct 0-3, status ready', function () {
    $e = readyExam();
    expect($e->status)->toBe('ready')->and($e->questions())->toHaveCount(3);
    foreach ($e->questions() as $q) {
        expect($q['options'])->toHaveCount(4)
            ->and($q['correct'])->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(3);
    }
});

it('ma de Goc: giu thu tu, dap an dung', function () {
    $e = readyExam();
    $v = ExamService::variant($e, 'goc');
    expect($v['questions'])->toHaveCount(3)
        ->and($v['key'])->toBe(['B', 'B', 'B']);   // ca 3 cau correct=1 -> B
});

it('ma de tron: DETERMINISTIC + dap an van tro dung lua chon goc', function () {
    $e = readyExam();
    $v1 = ExamService::variant($e, '101');
    $v2 = ExamService::variant($e, '101');
    expect($v1)->toBe($v2);   // cung ma de -> giong het (in lai khong doi)

    // Voi moi cau trong ban tron, lua chon tai vi tri `key` phai la lua chon dung ('4','9','6').
    $letters = ['A' => 0, 'B' => 1, 'C' => 2, 'D' => 3];
    $correctTexts = ['4', '9', '6'];   // dap an dung cua 3 cau goc
    foreach ($v1['questions'] as $i => $q) {
        $keyIdx = $letters[$v1['key'][$i]];
        expect($q['options'][$keyIdx])->toBeIn($correctTexts);
    }
});

it('cham tu dong: bai lam = dap an -> 10 diem; sai 1 cau -> 6.67', function () {
    $e = readyExam();
    $key = ExamService::variant($e, 'goc')['key'];      // ['B','B','B']

    $perfect = ExamService::grade($e, 'goc', implode('', $key));
    expect($perfect['score'])->toBe(10.0)->and($perfect['correct_count'])->toBe(3);

    $oneWrong = ExamService::grade($e, 'goc', 'BBA');   // cau 3 sai
    expect($oneWrong['correct_count'])->toBe(2)->and($oneWrong['score'])->toBe(6.67);
});

it('cham qua HTTP tra ket qua ve trang', function () {
    $e = readyExam();
    $this->actingAs(examAdmin())
        ->post(route('admin.exams.grade', $e), ['code' => 'goc', 'answers' => 'BBB'])
        ->assertRedirect(route('admin.exams.show', $e))
        ->assertSessionHas('grade_result');
});

it('trang in de + dap an render', function () {
    $e = readyExam();
    $admin = examAdmin();
    $this->actingAs($admin)->get(route('admin.exams.print', $e).'?code=101&sheet=de')
        ->assertOk()->assertSee('Mã đề', false);
    $this->actingAs($admin)->get(route('admin.exams.print', $e).'?code=101&sheet=key')
        ->assertOk()->assertSee('Đáp án', false);
});

it('hoc sinh khong vao duoc de thi (403)', function () {
    $s = User::create(['name' => 'S', 'email' => 'sx.'.uniqid().'@ht.test', 'password' => 'password', 'role' => User::ROLE_STUDENT]);
    $this->actingAs($s)->get(route('admin.exams'))->assertForbidden();
});
