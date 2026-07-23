<?php

use App\Models\AiLog;
use App\Models\AiProvider;
use App\Services\Ai\ClaudeClient;
use App\Services\AiProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

/**
 * Kiem chung adapter Claude bang Http::fake (dung request/header/parse/token + dinh tuyen).
 * KHONG goi Claude that — can API key Anthropic de test end-to-end.
 */
function claudeProvider(): AiProvider
{
    $p = new AiProvider([
        'name' => 'Claude', 'base_url' => 'https://api.anthropic.com/v1',
        'models' => ['default' => 'claude-haiku-4-5'], 'status' => 'active', 'priority' => 0,
    ]);
    $p->setApiKey('sk-ant-test');
    $p->save();

    return $p;
}

function anthropicResponse(string $text, int $in = 12, int $out = 7): array
{
    return ['content' => [['type' => 'text', 'text' => $text]], 'usage' => ['input_tokens' => $in, 'output_tokens' => $out]];
}

it('ClaudeClient: gui /messages dung header + parse text + chuan hoa token', function () {
    Http::fake(['*' => Http::response(anthropicResponse('{"a":1}', 12, 7))]);

    $result = app(ClaudeClient::class)->generate(claudeProvider(), 'Xin chào', ['type' => 'object']);

    expect($result['text'])->toBe('{"a":1}')
        ->and($result['raw']['usageMetadata'])->toBe([
            'promptTokenCount' => 12, 'candidatesTokenCount' => 7, 'totalTokenCount' => 19,
        ]);

    Http::assertSent(fn ($req) => str_contains($req->url(), '/messages')
        && $req->hasHeader('x-api-key', 'sk-ant-test')
        && $req->hasHeader('anthropic-version', '2023-06-01')
        && $req['model'] === 'claude-haiku-4-5');
});

it('ClaudeClient: vision dat anh truoc text', function () {
    Http::fake(['*' => Http::response(anthropicResponse('{"ok":true}'))]);

    app(ClaudeClient::class)->generate(claudeProvider(), 'Đọc ảnh', ['type' => 'object'],
        image: ['data' => base64_encode('x'), 'mime' => 'image/png']);

    Http::assertSent(function ($req) {
        $content = $req['messages'][0]['content'];

        return $content[0]['type'] === 'image'
            && $content[0]['source']['media_type'] === 'image/png'
            && $content[1]['type'] === 'text';
    });
});

it('AiProviderService dinh tuyen sang Claude khi base_url la anthropic + ghi token', function () {
    Http::fake(['*' => Http::response(anthropicResponse('{"value":42}', 20, 8))]);
    claudeProvider();

    $out = app(AiProviderService::class)->chat(
        AiLog::FEATURE_AUTHORING, 'prompt',
        ['type' => 'object', 'properties' => ['value' => ['type' => 'integer']], 'required' => ['value']],
    );

    expect($out)->toBe(['value' => 42]);

    $log = AiLog::latest('id')->first();
    expect($log->status)->toBe('ok')
        ->and($log->total_tokens)->toBe(28)
        ->and($log->prompt_tokens)->toBe(20);
});

it('AiProviderService van dung Gemini khi base_url KHONG phai anthropic', function () {
    Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => '{"value":1}']]]]]])]);

    $p = new AiProvider([
        'name' => 'Gemini', 'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
        'models' => ['default' => 'gemini-flash-latest'], 'status' => 'active', 'priority' => 0,
    ]);
    $p->setApiKey('AIza-test');
    $p->save();

    $out = app(AiProviderService::class)->chat(
        AiLog::FEATURE_AUTHORING, 'prompt',
        ['type' => 'object', 'properties' => ['value' => ['type' => 'integer']], 'required' => ['value']],
    );

    expect($out)->toBe(['value' => 1]);
    // Gemini goi endpoint generativelanguage, khong phai anthropic
    Http::assertSent(fn ($req) => str_contains($req->url(), 'generativelanguage'));
});
