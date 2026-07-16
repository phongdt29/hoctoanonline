<?php

namespace Database\Seeders;

use App\Models\AiProvider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;

/**
 * SPEC §2.8 — provider AI. Da chot dung Google Gemini.
 *
 * Seed 2 provider Gemini de test failover theo priority (provider 1 loi -> provider 2).
 * Key lay tu env GEMINI_API_KEY neu co; khong thi dung key GIA (dev, goi that se 4xx —
 * du de chay Http::fake trong test, chua du de goi API that).
 *
 * Key that KHONG BAO GIO nam trong seed/git — nhap qua admin UI (ticket T3) hoac .env.
 */
class AiProviderSeeder extends Seeder
{
    public function run(): void
    {
        $key = env('GEMINI_API_KEY', 'AIza-DEV-FAKE-KEY-thay-bang-key-that');
        $base = 'https://generativelanguage.googleapis.com/v1beta';

        // Dung ALIAS *-latest thay vi ban co dinh (gemini-1.5-flash da bi Google go,
        // gemini-2.5-flash "khong con cho user moi"). Alias luon tro toi model hien
        // hanh -> khong phai sua seed moi khi Google doi version.
        AiProvider::create([
            'name' => 'Gemini Flash (chinh)',
            'base_url' => $base,
            'api_key_encrypted' => Crypt::encrypt($key),
            'models' => ['default' => 'gemini-flash-latest', 'pro' => 'gemini-pro-latest'],
            'status' => AiProvider::STATUS_ACTIVE,
            'priority' => 1,
        ]);

        AiProvider::create([
            'name' => 'Gemini Flash Lite (du phong)',
            'base_url' => $base,
            'api_key_encrypted' => Crypt::encrypt($key),
            'models' => ['default' => 'gemini-flash-lite-latest'],
            'status' => AiProvider::STATUS_ACTIVE,
            'priority' => 2,   // dung khi provider 1 loi
        ]);
    }
}
