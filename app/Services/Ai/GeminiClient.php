<?php

namespace App\Services\Ai;

use App\Models\AiProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Adapter Google Gemini (SPEC §3.8).
 *
 * Endpoint: POST {base_url}/models/{model}:generateContent
 * Auth:     header x-goog-api-key
 * Body:     { contents:[{role,parts:[{text}]}], generationConfig:{...} }
 * Response: candidates[0].content.parts[0].text
 *
 * Neu doi nha cung cap sau nay -> viet adapter khac cung interface (chat/text),
 * AiProviderService khong can biet provider la Gemini hay gi.
 */
class GeminiClient
{
    /**
     * @param  array|null  $schema  JSON Schema — neu co, ep Gemini tra JSON dung cau truc.
     * @param  array|null  $image   ['data' => base64, 'mime' => 'image/png'] — Gemini vision (I3).
     * @return array{text: string, raw: array}
     */
    public function generate(AiProvider $provider, string $prompt, ?array $schema, ?string $model = null, ?array $image = null): array
    {
        $model ??= $provider->models['default'] ?? 'gemini-flash-latest';

        $generationConfig = ['temperature' => 0.4];

        if ($schema !== null) {
            // Gemini structured output: ep tra JSON theo schema -> khoi parse markdown fence.
            $generationConfig['responseMimeType'] = 'application/json';
            $generationConfig['responseSchema'] = $this->toGeminiSchema($schema);
        }

        $parts = [['text' => $prompt]];

        // Gemini vision: dinh kem anh dang inline_data base64 (I3 — OCR de toan).
        if ($image !== null) {
            $parts[] = ['inline_data' => ['mime_type' => $image['mime'], 'data' => $image['data']]];
        }

        $response = $this->http($provider)
            ->post("/models/{$model}:generateContent", [
                'contents' => [
                    ['role' => 'user', 'parts' => $parts],
                ],
                'generationConfig' => $generationConfig,
            ]);

        $response->throw();

        $data = $response->json();
        $text = data_get($data, 'candidates.0.content.parts.0.text', '');

        return ['text' => $text, 'raw' => $data];
    }

    private function http(AiProvider $provider): PendingRequest
    {
        return Http::baseUrl(rtrim($provider->base_url, '/'))
            ->timeout(config('hoctoan.ai_timeout'))
            ->withHeaders(['x-goog-api-key' => $provider->apiKey()])
            ->acceptJson()
            ->asJson();
    }

    /**
     * JSON Schema chuan -> subset Gemini chap nhan.
     * Gemini khong an 'additionalProperties'/'$schema' -> loc bo cac khoa la.
     */
    private function toGeminiSchema(array $schema): array
    {
        $allowed = ['type', 'properties', 'items', 'required', 'enum', 'description', 'nullable'];

        $clean = function (array $node) use (&$clean, $allowed): array {
            $out = [];

            foreach ($node as $key => $value) {
                if (! in_array($key, $allowed, true)) {
                    continue;
                }

                if ($key === 'properties' && is_array($value)) {
                    $out['properties'] = array_map(
                        fn ($prop) => is_array($prop) ? $clean($prop) : $prop,
                        $value,
                    );
                } elseif ($key === 'items' && is_array($value)) {
                    $out['items'] = $clean($value);
                } else {
                    $out[$key] = $value;
                }
            }

            return $out;
        };

        return $clean($schema);
    }
}
