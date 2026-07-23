<?php

namespace App\Services\Ai;

use App\Models\AiProvider;

/**
 * Hop dong chung cho moi adapter AI (Gemini, Claude, ...).
 * AiProviderService chon adapter theo base_url cua provider.
 *
 * generate() PHAI tra ve:
 *   ['text' => string, 'raw' => array]
 * trong do raw NEN chua 'usageMetadata' dang Gemini
 *   ['promptTokenCount', 'candidatesTokenCount', 'totalTokenCount']
 * de AiProviderService::writeLog ghi token thong nhat.
 */
interface AiClient
{
    /**
     * @param  array|null  $schema  JSON Schema — neu co, ep tra JSON dung cau truc.
     * @param  array|null  $image   ['data' => base64, 'mime' => 'image/png'] — vision.
     * @return array{text: string, raw: array}
     */
    public function generate(AiProvider $provider, string $prompt, ?array $schema, ?string $model = null, ?array $image = null): array;
}
