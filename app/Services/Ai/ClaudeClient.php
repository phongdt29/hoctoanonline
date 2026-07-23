<?php

namespace App\Services\Ai;

use App\Models\AiProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Adapter Anthropic Claude (Messages API) — cung interface AiClient nhu GeminiClient.
 *
 * Endpoint: POST {base_url}/messages   (base_url = https://api.anthropic.com/v1)
 * Auth:     header x-api-key + anthropic-version: 2023-06-01
 * Body:     { model, max_tokens, messages:[{role:user, content:[...]}] }
 * Response: content[0].text ; usage.{input_tokens, output_tokens}
 *
 * Cach dung: admin them 1 AI provider co base_url chua "anthropic" + key sk-ant-...
 * AiProviderService tu dinh tuyen sang adapter nay.
 *
 * LUU Y: da kiem chung request/parse bang Http::fake; CHUA goi Claude that
 * (can API key Anthropic de test end-to-end).
 */
class ClaudeClient implements AiClient
{
    private const ANTHROPIC_VERSION = '2023-06-01';

    private const DEFAULT_MODEL = 'claude-haiku-4-5';   // re, hop luong sinh de/giao trinh

    public function generate(AiProvider $provider, string $prompt, ?array $schema, ?string $model = null, ?array $image = null): array
    {
        $model ??= $provider->models['default'] ?? self::DEFAULT_MODEL;

        // Co schema -> yeu cau tra JSON thuan (AiProviderService da decode + validate + retry).
        // Claude bam JSON tot; khong dung output_config de tranh rang buoc schema phuc tap.
        $userText = $schema !== null
            ? $prompt."\n\nCHỈ trả về JSON hợp lệ đúng cấu trúc yêu cầu, không kèm giải thích, không bọc trong ```."
            : $prompt;

        $content = [];
        // Vision: anh dat truoc text (giong Gemini).
        if ($image !== null) {
            $content[] = ['type' => 'image', 'source' => [
                'type' => 'base64', 'media_type' => $image['mime'], 'data' => $image['data'],
            ]];
        }
        $content[] = ['type' => 'text', 'text' => $userText];

        $response = $this->http($provider)->post('/messages', [
            'model'      => $model,
            'max_tokens' => (int) config('hoctoan.ai_max_tokens', 8192),
            'messages'   => [['role' => 'user', 'content' => $content]],
        ]);

        $response->throw();

        $data = $response->json();
        $text = $this->extractText($data);

        return ['text' => $text, 'raw' => $this->withUsageMetadata($data)];
    }

    private function http(AiProvider $provider): PendingRequest
    {
        return Http::baseUrl(rtrim($provider->base_url, '/'))
            ->timeout(config('hoctoan.ai_timeout'))
            ->withHeaders([
                'x-api-key'         => $provider->apiKey(),
                'anthropic-version' => self::ANTHROPIC_VERSION,
            ])
            ->acceptJson()
            ->asJson();
    }

    /** Ghep text tu cac block type=text (thuong chi 1). */
    private function extractText(array $data): string
    {
        $parts = [];
        foreach ($data['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                $parts[] = $block['text'] ?? '';
            }
        }

        return trim(implode('', $parts));
    }

    /**
     * Chuan hoa usage cua Claude ve dang Gemini (usageMetadata) de writeLog dung chung.
     * Claude: usage.input_tokens / usage.output_tokens.
     */
    private function withUsageMetadata(array $data): array
    {
        $in = (int) ($data['usage']['input_tokens'] ?? 0);
        $out = (int) ($data['usage']['output_tokens'] ?? 0);

        $data['usageMetadata'] = [
            'promptTokenCount'     => $in,
            'candidatesTokenCount' => $out,
            'totalTokenCount'      => $in + $out,
        ];

        return $data;
    }
}
