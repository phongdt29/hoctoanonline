<?php

use App\Models\AiLog;
use App\Models\AiProvider;
use App\Services\Ai\AiException;
use App\Services\AiProviderService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

/*
 * Ticket C1 — DoD: provider 1 loi -> tu dung provider 2 · moi call co ai_logs ·
 * AI tra JSON sai schema -> retry 1 lan roi throw.
 */

beforeEach(function () {
    $this->seed(\Database\Seeders\AiProviderSeeder::class);
    $this->service = app(AiProviderService::class);
});

/** Body Gemini generateContent thanh cong voi text cho truoc. */
function geminiOk(string $text): array
{
    return ['candidates' => [['content' => ['parts' => [['text' => $text]]]]]];
}

$schema = [
    'type' => 'object',
    'required' => ['level', 'score'],
    'properties' => [
        'level' => ['type' => 'string', 'enum' => ['trung_binh', 'kha', 'gioi']],
        'score' => ['type' => 'integer'],
    ],
];

it('chat() tra JSON da validate khi provider tra dung schema', function () use ($schema) {
    Http::fake(['*' => Http::response(geminiOk('{"level":"kha","score":72}'))]);

    $result = $this->service->chat(AiLog::FEATURE_CURRICULUM, 'prompt', $schema);

    expect($result)->toBe(['level' => 'kha', 'score' => 72]);
});

it('DoD C1: provider 1 loi -> tu failover sang provider 2', function () use ($schema) {
    $seq = Http::sequence()
        ->push('loi may chu', 500)                       // provider 1
        ->push(geminiOk('{"level":"gioi","score":90}'), 200);  // provider 2

    Http::fake(['*' => $seq]);

    $result = $this->service->chat(AiLog::FEATURE_CURRICULUM, 'prompt', $schema);

    expect($result['level'])->toBe('gioi');

    // Co ban ghi loi cho provider 1 VA ban ghi ok cho provider 2.
    expect(AiLog::where('status', AiLog::STATUS_ERROR)->exists())->toBeTrue()
        ->and(AiLog::where('status', AiLog::STATUS_OK)->exists())->toBeTrue();
});

it('DoD C1: moi call deu ghi ai_logs', function () use ($schema) {
    // Tao student that vi ai_logs.student_id co FK sang students.
    $user = \App\Models\User::create([
        'name' => 'HS', 'email' => 'hs.ai@hoctoan.test', 'password' => 'password', 'role' => 'student',
    ]);
    $student = \App\Models\Student::create([
        'user_id' => $user->id, 'full_name' => 'HS', 'status' => 'registered',
    ]);

    Http::fake(['*' => Http::response(geminiOk('{"level":"kha","score":50}'))]);

    expect(AiLog::count())->toBe(0);

    $this->service->chat(AiLog::FEATURE_GRADING, 'prompt', $schema, studentId: $student->id);

    $log = AiLog::first();
    expect($log)->not->toBeNull()
        ->and($log->feature)->toBe(AiLog::FEATURE_GRADING)
        ->and($log->student_id)->toBe($student->id)
        ->and($log->status)->toBe(AiLog::STATUS_OK)
        ->and($log->latency_ms)->toBeGreaterThanOrEqual(0);
});

it('DoD C1: JSON sai schema -> retry 1 lan roi throw', function () use ($schema) {
    // Ca 2 provider, moi provider thu 2 lan (schema retry) = 4 lan deu thieu field `score`.
    Http::fake(['*' => Http::response(geminiOk('{"level":"kha"}'))]);

    expect(fn () => $this->service->chat(AiLog::FEATURE_CURRICULUM, 'prompt', $schema))
        ->toThrow(AiException::class);

    // 2 provider x 2 lan = 4 ban ghi error.
    expect(AiLog::where('status', AiLog::STATUS_ERROR)->count())->toBe(4);
});

it('retry lan 2 dung schema -> thanh cong, khong throw', function () use ($schema) {
    $seq = Http::sequence()
        ->push(geminiOk('{"level":"kha"}'), 200)             // lan 1: thieu score
        ->push(geminiOk('{"level":"kha","score":60}'), 200); // lan 2: du -> ok

    Http::fake(['*' => $seq]);

    $result = $this->service->chat(AiLog::FEATURE_CURRICULUM, 'prompt', $schema);

    expect($result['score'])->toBe(60);
});

it('het provider ma van loi -> AiException', function () use ($schema) {
    Http::fake(['*' => Http::response('loi', 503)]);

    expect(fn () => $this->service->chat(AiLog::FEATURE_CURRICULUM, 'prompt', $schema))
        ->toThrow(AiException::class, 'Moi AI provider deu that bai');
});

it('khong co provider active -> AiException', function () use ($schema) {
    AiProvider::query()->update(['status' => AiProvider::STATUS_DISABLED]);

    expect(fn () => $this->service->chat(AiLog::FEATURE_CURRICULUM, 'prompt', $schema))
        ->toThrow(AiException::class, 'Khong co AI provider');
});

it('text() tra text tu do va van ghi log', function () {
    Http::fake(['*' => Http::response(geminiOk('Chào em, cô giúp gì nào?'))]);

    $text = $this->service->text(AiLog::FEATURE_TUTOR_CHAT, 'xin chao');

    expect($text)->toBe('Chào em, cô giúp gì nào?')
        ->and(AiLog::where('feature', AiLog::FEATURE_TUTOR_CHAT)->where('status', 'ok')->exists())->toBeTrue();
});

it('safety filter chan input trong blocklist va ghi log filtered', function () use ($schema) {
    config(['hoctoan.ai_blocklist' => ['tuphucchodenman']]);

    expect(fn () => $this->service->chat(AiLog::FEATURE_SOLVER, 'giai cau nay tuphucchodenman', $schema))
        ->toThrow(AiException::class);

    expect(AiLog::where('status', AiLog::STATUS_FILTERED)->exists())->toBeTrue();
    Http::assertNothingSent();   // bi chan truoc khi goi provider
});

it('safety filter chan output nhay cam', function () use ($schema) {
    config(['hoctoan.ai_blocklist' => ['badword']]);
    Http::fake(['*' => Http::response(geminiOk('cau tra loi co badword'))]);

    expect(fn () => $this->service->text(AiLog::FEATURE_SOLVER, 'cau hoi sach'))
        ->toThrow(AiException::class);

    expect(AiLog::where('status', AiLog::STATUS_FILTERED)->exists())->toBeTrue();
});

it('chon provider theo priority ASC: provider 1 (priority nho) truoc', function () use ($schema) {
    // Provider 1 tra ok luon -> provider 2 khong bao gio duoc goi.
    Http::fake(['*' => Http::response(geminiOk('{"level":"kha","score":55}'))]);

    $this->service->chat(AiLog::FEATURE_CURRICULUM, 'prompt', $schema);

    $usedProviderId = AiLog::where('status', 'ok')->first()->provider_id;
    $priority1 = AiProvider::where('priority', 1)->first()->id;

    expect($usedProviderId)->toBe($priority1);
});

it('gui dung header x-goog-api-key voi key da giai ma', function () use ($schema) {
    Http::fake(['*' => Http::response(geminiOk('{"level":"kha","score":55}'))]);

    $this->service->chat(AiLog::FEATURE_CURRICULUM, 'prompt', $schema);

    $expectedKey = Crypt::decrypt(AiProvider::where('priority', 1)->first()->api_key_encrypted);

    Http::assertSent(fn ($request) => $request->hasHeader('x-goog-api-key', $expectedKey));
});
