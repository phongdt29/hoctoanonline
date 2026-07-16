<?php

use App\Models\SolverRequest;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/*
 * Ticket I3 — DoD: anh thu 21 trong ngay -> 429 (het luot) ·
 * confidence thap -> hien form confirm, KHONG giai ngay.
 */

beforeEach(function () {
    Storage::fake('local');
    $this->seed(\Database\Seeders\AiProviderSeeder::class);
    $user = User::create([
        'name' => 'HS', 'email' => 'hs.img@hoctoan.test', 'password' => 'password', 'role' => 'student',
    ]);
    $this->student = Student::create([
        'user_id' => $user->id, 'full_name' => 'HS', 'grade' => 8, 'status' => 'learning',
    ]);
    $this->user = $user;
});

/** Fake Gemini vision tra OCR ket qua. */
function fakeOcr(string $problemText, int $confidence): void
{
    $json = json_encode(['problem_text' => $problemText, 'confidence' => $confidence]);
    Http::fake(['*' => Http::response([
        'candidates' => [['content' => ['parts' => [['text' => $json]]]]],
    ])]);
}

function uploadImage(): UploadedFile
{
    return UploadedFile::fake()->image('bai-toan.png', 800, 600);
}

it('I3: upload anh -> OCR ra de + luu request', function () {
    fakeOcr('Tính 2 + 3', 95);

    $res = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/solver/image', ['image' => uploadImage()])
        ->assertOk();

    expect($res->json('data.parsed_text'))->toBe('Tính 2 + 3')
        ->and($res->json('data.confidence'))->toBe(95)
        ->and($res->json('data.needs_confirmation'))->toBeFalse()
        ->and(SolverRequest::where('input_type', 'image')->count())->toBe(1);
});

it('DoD I3: confidence thap -> bat confirm, hint = null (khong giai ngay)', function () {
    // Nguong config.ocr_min_confidence = 70. Tra 50 -> duoi nguong.
    fakeOcr('Tinh 2 + 3 (mo)', 50);

    $res = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/solver/image', ['image' => uploadImage()])
        ->assertOk();

    expect($res->json('data.needs_confirmation'))->toBeTrue()
        ->and($res->json('data.hint'))->toBeNull();   // chua giai
});

it('I3: sau confirm (sua de) -> moi bat dau giai', function () {
    fakeOcr('De doc sai', 40);
    $reqId = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/solver/image', ['image' => uploadImage()])
        ->json('data.request_id');

    // Student sua lai de + confirm -> gio moi tra hint.
    Http::fake(['*' => Http::response([
        'candidates' => [['content' => ['parts' => [['text' => 'Gợi ý: cộng hai số.']]]]],
    ])]);

    $res = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/v1/solver/{$reqId}/confirm-image", ['corrected_text' => 'Tính 2 + 3'])
        ->assertOk();

    expect($res->json('data.problem_text'))->toBe('Tính 2 + 3')
        ->and($res->json('data.hint'))->not->toBeNull();
});

it('DoD I3: anh thu 21 trong ngay -> bi chan (het luot 20/ngay)', function () {
    fakeOcr('de', 95);

    // Tao san 20 request anh hom nay.
    for ($i = 0; $i < 20; $i++) {
        SolverRequest::create([
            'student_id' => $this->student->id,
            'input_type' => 'image',
            'problem_text' => "de {$i}",
            'ocr_confidence' => 90,
        ]);
    }

    // Anh thu 21 -> bi chan (500 AiException het luot).
    $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/solver/image', ['image' => uploadImage()])
        ->assertStatus(500);

    // Van chi 20 (khong tao them).
    expect(SolverRequest::where('student_id', $this->student->id)->where('input_type', 'image')->count())->toBe(20);
});

it('I3: tu choi file khong phai anh', function () {
    $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/solver/image', [
            'image' => UploadedFile::fake()->create('virus.pdf', 100, 'application/pdf'),
        ])
        ->assertStatus(422);
});

it('I3: OCR call co ghi ai_logs', function () {
    fakeOcr('de', 90);
    $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/solver/image', ['image' => uploadImage()]);

    expect(\App\Models\AiLog::where('feature', \App\Models\AiLog::FEATURE_SOLVER)->exists())->toBeTrue();
});
