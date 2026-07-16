<?php

namespace App\Services\Ai;

/** Ket qua 1 lan goi AI thanh cong. */
final class AiResult
{
    public function __construct(
        public readonly string $text,
        public readonly ?array $json,
        public readonly int $providerId,
        public readonly int $latencyMs,
    ) {}
}
