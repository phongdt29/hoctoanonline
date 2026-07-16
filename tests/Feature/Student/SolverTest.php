<?php

use App\Models\SolverRequest;
use App\Models\Student;
use App\Models\User;
use App\Services\SolverService;
use Illuminate\Support\Facades\Http;

/*
 * Ticket I2 — DoD: goi lan dau -> response KHONG chua dap an cuoi ·
 * hint thu 3 -> 422 · reveal moi co full solution.
 */

beforeEach(function () {
    $this->seed(\Database\Seeders\AiProviderSeeder::class);
    $user = User::create([
        'name' => 'HS', 'email' => 'hs.solver@hoctoan.test', 'password' => 'password', 'role' => 'student',
    ]);
    $this->student = Student::create([
        'user_id' => $user->id, 'full_name' => 'HS', 'grade' => 8, 'status' => 'learning',
    ]);
    $this->user = $user;
});

function geminiText(string $text): array
{
    return ['candidates' => [['content' => ['parts' => [['text' => $text]]]]]];
}

function fakeAiText(string $text): void
{
    Http::fake(['*' => Http::response(geminiText($text))]);
}

/** Nhieu cau tra loi lien tiep — Http::fake goi 2 lan se MERGE (stub cu thang),
 *  nen phai dung sequence khi 1 test goi AI nhieu lan voi ket qua khac nhau. */
function fakeAiSequence(array $texts): void
{
    $seq = Http::sequence();
    foreach ($texts as $t) {
        $seq->push(geminiText($t));
    }
    Http::fake(['*' => $seq]);
}

it('DoD I2: lan goi dau chi tra HINT, KHONG co truong solution', function () {
    fakeAiText('Gợi ý: hãy thử quy đồng mẫu số trước.');

    $res = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/solver/text', ['problem' => 'Tính 1/2 + 1/3'])
        ->assertOk();

    expect($res->json('data'))->toHaveKey('hint')
        ->and($res->json('data'))->not->toHaveKey('solution')
        ->and($res->json('data.hint_count'))->toBe(0)
        ->and($res->json('data.can_more_hint'))->toBeTrue();
});

it('DoD I2: hint thu 3 bi tu choi (max 2)', function () {
    fakeAiText('Gợi ý.');

    $reqId = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/solver/text', ['problem' => 'Tính 1/2 + 1/3'])
        ->json('data.request_id');

    // Hint lan 1 (hint_count 0 -> 1)
    $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/v1/solver/{$reqId}/more-hint")->assertOk();

    // Hint lan 2 (1 -> 2)
    $r2 = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/v1/solver/{$reqId}/more-hint")->assertOk();
    expect($r2->json('data.can_more_hint'))->toBeFalse();

    // Hint lan 3 -> bi chan (500 AiException, khong con luot).
    $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/v1/solver/{$reqId}/more-hint")
        ->assertStatus(500);

    expect(SolverRequest::find($reqId)->hint_count)->toBe(2);
});

it('DoD I2: full-solution chi co sau khi hoc sinh chu dong yeu cau', function () {
    // 2 call AI: hint (start) roi solution (full) -> sequence.
    fakeAiSequence(['Gợi ý: quy đồng mẫu số.', 'Bước 1: quy đồng. Bước 2: cộng. Đáp án: 5/6.']);

    $reqId = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/solver/text', ['problem' => 'Tính 1/2 + 1/3'])
        ->json('data.request_id');

    expect(SolverRequest::find($reqId)->solution_revealed)->toBeFalse();

    $res = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/v1/solver/{$reqId}/full-solution")->assertOk();

    expect($res->json('data.solution'))->toContain('Đáp án')
        ->and(SolverRequest::find($reqId)->solution_revealed)->toBeTrue();
});

it('I2: bai tuong tu luyen them', function () {
    fakeAiSequence(['Gợi ý.', 'Bài tương tự: Tính 1/4 + 1/5. Đáp án: 9/20.']);

    $reqId = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/solver/text', ['problem' => 'Tính 1/2 + 1/3'])
        ->json('data.request_id');

    $this->actingAs($this->user, 'sanctum')
        ->getJson("/api/v1/solver/{$reqId}/similar")
        ->assertOk()
        ->assertJsonPath('data.problem', fn ($p) => str_contains($p, 'tương tự'));
});

it('I2: khong thao tac duoc solver request cua nguoi khac', function () {
    fakeAiText('Gợi ý.');
    $reqId = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/solver/text', ['problem' => 'x'])
        ->json('data.request_id');

    $other = User::create([
        'name' => 'HS2', 'email' => 'hs2.solver@hoctoan.test', 'password' => 'password', 'role' => 'student',
    ]);
    Student::create(['user_id' => $other->id, 'full_name' => 'HS2', 'status' => 'learning']);

    $this->actingAs($other, 'sanctum')
        ->postJson("/api/v1/solver/{$reqId}/more-hint")
        ->assertStatus(403);
});

it('I2: phat hien le thuoc dap an (nhay vao full solution khong xin hint)', function () {
    fakeAiText('x');

    // Tao 3 request, ca 3 deu reveal ma khong xin hint (hint_count=0).
    foreach (range(1, 3) as $i) {
        $req = app(SolverService::class)->startText($this->student, "bai {$i}");
        app(SolverService::class)->fullSolution($req['request']);
    }

    // 3/3 nhay vao dap an -> ty le le thuoc = 1.0
    expect(app(SolverService::class)->answerDependencyRate($this->student))->toBe(1.0);
});

it('I2: moi call solver deu ghi ai_logs', function () {
    fakeAiText('Gợi ý.');
    $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/solver/text', ['problem' => 'x']);

    expect(\App\Models\AiLog::where('feature', \App\Models\AiLog::FEATURE_SOLVER)->exists())->toBeTrue();
});
