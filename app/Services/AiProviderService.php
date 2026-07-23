<?php

namespace App\Services;

use App\Models\AiLog;
use App\Models\AiProvider;
use App\Services\Ai\AiClient;
use App\Services\Ai\AiException;
use App\Services\Ai\AiResult;
use App\Services\Ai\ClaudeClient;
use App\Services\Ai\GeminiClient;
use App\Services\Ai\JsonSchemaValidator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * SPEC §3.8 + CLAUDE.md #3 — cong duy nhat de goi AI.
 *
 * BAT BUOC:
 *  - Chon provider status=active theo priority ASC; loi -> failover provider ke.
 *  - Ghi ai_logs MOI call (feature, latency, status). Khong co ai_logs = call khong hop le.
 *  - chat() ep JSON output + validate schema; sai schema -> retry 1 lan roi throw.
 *  - Safety filter input/output (noi dung hop lua tuoi) truoc khi tra.
 *  - Call AI dai chay trong Job, KHONG trong request (trach nhiem cua caller).
 */
class AiProviderService
{
    public function __construct(
        private readonly GeminiClient $gemini,
        private readonly ClaudeClient $claude,
        private readonly JsonSchemaValidator $validator,
    ) {}

    /**
     * Chon adapter theo base_url: chua "anthropic" -> Claude, con lai -> Gemini.
     * Provider Gemini san co khong doi hanh vi (base_url = generativelanguage...).
     */
    private function clientFor(AiProvider $provider): AiClient
    {
        return str_contains(strtolower($provider->base_url), 'anthropic')
            ? $this->claude
            : $this->gemini;
    }

    /**
     * Goi AI tra JSON theo schema. Tra mang da validate.
     *
     * @param  string      $feature  AiLog::FEATURE_*
     * @param  array       $schema   JSON Schema output bat buoc
     * @param  int|null    $studentId  de gan ai_logs.student_id
     */
    public function chat(string $feature, string $prompt, array $schema, ?int $studentId = null, ?array $image = null): array
    {
        if (($bad = $this->screenInput($prompt)) !== null) {
            $this->logFiltered($feature, $studentId, $prompt, $bad);
            throw new AiException("Noi dung dau vao bi safety filter chan: {$bad}");
        }

        $result = $this->run($feature, $prompt, $schema, $studentId, $image);

        if ($result->json === null) {
            throw new AiException('AI khong tra ve JSON hop le sau khi retry.');
        }

        return $result->json;
    }

    /**
     * Ticket I3 — OCR anh de toan qua Gemini vision. Tra JSON theo schema.
     * @param array $image ['data' => base64, 'mime' => 'image/png']
     */
    public function vision(string $feature, string $prompt, array $image, array $schema, ?int $studentId = null): array
    {
        return $this->chat($feature, $prompt, $schema, $studentId, $image);
    }

    /** Goi AI tra text tu do (tutor chat, solver). Van log + safety filter. */
    public function text(string $feature, string $prompt, ?int $studentId = null): string
    {
        if (($bad = $this->screenInput($prompt)) !== null) {
            $this->logFiltered($feature, $studentId, $prompt, $bad);
            throw new AiException("Noi dung dau vao bi safety filter chan: {$bad}");
        }

        return $this->run($feature, $prompt, null, $studentId)->text;
    }

    /**
     * Vong lap failover: thu tung provider theo priority. Provider dau loi -> provider ke.
     * Het provider ma van loi -> AiException.
     */
    private function run(string $feature, string $prompt, ?array $schema, ?int $studentId, ?array $image = null): AiResult
    {
        $providers = AiProvider::usable()->get();

        if ($providers->isEmpty()) {
            throw new AiException('Khong co AI provider nao dang active.');
        }

        $lastError = null;

        foreach ($providers as $provider) {
            try {
                return $this->callWithSchemaRetry($provider, $feature, $prompt, $schema, $studentId, $image);
            } catch (Throwable $e) {
                $lastError = $e;
                Log::warning('AI provider that bai, thu provider ke', [
                    'provider_id' => $provider->id,
                    'feature' => $feature,
                    'error' => $e->getMessage(),
                ]);
                // sang provider tiep theo (failover)
            }
        }

        throw new AiException(
            'Moi AI provider deu that bai. Loi cuoi: '.($lastError?->getMessage() ?? 'khong ro'),
            previous: $lastError,
        );
    }

    /** Goi 1 provider; neu co schema va JSON sai -> retry 1 lan roi bo. */
    private function callWithSchemaRetry(
        AiProvider $provider,
        string $feature,
        string $prompt,
        ?array $schema,
        ?int $studentId,
        ?array $image = null,
    ): AiResult {
        $attempts = $schema === null ? 1 : 2;   // co schema thi cho retry 1 lan
        $lastErrors = [];

        for ($i = 1; $i <= $attempts; $i++) {
            $startedAt = hrtime(true);

            // Loi HTTP (timeout, 5xx, 4xx) van la 1 call da xay ra -> phai co ai_logs
            // (CLAUDE.md #3). Log status=error roi nem lai de run() failover provider ke.
            try {
                $response = $this->clientFor($provider)->generate($provider, $prompt, $schema, null, $image);
            } catch (Throwable $e) {
                $latencyMs = (int) ((hrtime(true) - $startedAt) / 1e6);
                $this->writeLog($feature, $provider->id, $studentId, $prompt, ['error' => $e->getMessage()], $latencyMs, AiLog::STATUS_ERROR);
                throw $e;
            }

            $latencyMs = (int) ((hrtime(true) - $startedAt) / 1e6);
            $text = $response['text'];

            // Safety filter OUTPUT
            if (($bad = $this->screenOutput($text)) !== null) {
                $this->writeLog($feature, $provider->id, $studentId, $prompt, $response['raw'], $latencyMs, AiLog::STATUS_FILTERED);
                throw new AiException("Output bi safety filter chan: {$bad}");
            }

            $json = null;

            if ($schema !== null) {
                $json = $this->decodeJson($text);
                $errors = $json === null
                    ? ['khong parse duoc JSON']
                    : $this->validator->validate($json, $schema);

                if ($errors !== []) {
                    $lastErrors = $errors;
                    $this->writeLog($feature, $provider->id, $studentId, $prompt, $response['raw'], $latencyMs, AiLog::STATUS_ERROR);
                    continue;   // retry
                }
            }

            $this->writeLog($feature, $provider->id, $studentId, $prompt, $response['raw'], $latencyMs, AiLog::STATUS_OK);

            return new AiResult($text, $json, $provider->id, $latencyMs);
        }

        throw new AiException('JSON sai schema sau '.$attempts.' lan: '.implode('; ', $lastErrors));
    }

    private function decodeJson(string $text): ?array
    {
        $trimmed = trim($text);

        // Phong khi model bao JSON trong ```json ... ```
        if (str_starts_with($trimmed, '```')) {
            $trimmed = preg_replace('/^```(?:json)?|```$/m', '', $trimmed);
        }

        $decoded = json_decode(trim($trimmed), true);

        return is_array($decoded) ? $decoded : null;
    }

    /** @return string|null ly do bi chan, hoac null neu sach. */
    private function screenInput(string $prompt): ?string
    {
        return $this->screen($prompt);
    }

    private function screenOutput(string $text): ?string
    {
        return $this->screen($text);
    }

    /**
     * Safety filter toi gian cho noi dung hop lua tuoi (hoc sinh 1-12).
     * Day la lop chan CO BAN — provider that thuong co safety rieng.
     * Danh sach tu kho o config de mo rong ma khong sua code.
     */
    private function screen(string $content): ?string
    {
        foreach (config('hoctoan.ai_blocklist', []) as $term) {
            if ($term !== '' && stripos($content, $term) !== false) {
                return $term;
            }
        }

        return null;
    }

    private function logFiltered(string $feature, ?int $studentId, string $prompt, string $reason): void
    {
        $this->writeLog($feature, null, $studentId, $prompt, ['blocked' => $reason], 0, AiLog::STATUS_FILTERED);
    }

    private function writeLog(
        string $feature,
        ?int $providerId,
        ?int $studentId,
        string $prompt,
        ?array $response,
        int $latencyMs,
        string $status,
    ): void {
        // Trich so token tu usageMetadata cua Gemini (neu co) — de tinh chi phi.
        $usage = $response['usageMetadata'] ?? [];

        AiLog::create([
            'provider_id' => $providerId,
            'student_id' => $studentId,
            'feature' => $feature,
            // Cat prompt dai khi ghi log de ai_logs khong phinh.
            'request_json' => ['prompt' => Str::limit($prompt, 4000)],
            'response_json' => $response,
            'latency_ms' => $latencyMs,
            'status' => $status,
            'prompt_tokens' => $usage['promptTokenCount'] ?? null,
            'completion_tokens' => $usage['candidatesTokenCount'] ?? null,
            'total_tokens' => $usage['totalTokenCount'] ?? null,
        ]);
    }
}
