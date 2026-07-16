<?php

namespace Database\Seeders;

use App\Models\AiProvider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;

/**
 * SPEC §2.8 — 2 provider mau de test failover theo priority.
 *
 * Key la GIA (dev only). Key that KHONG BAO GIO nam trong seed/git —
 * nhap qua admin UI (ticket T3), luu bang Crypt::encrypt.
 */
class AiProviderSeeder extends Seeder
{
    public function run(): void
    {
        AiProvider::create([
            'name'              => 'Provider chinh (dev)',
            'base_url'          => 'https://api.example-primary.test/v1',
            'api_key_encrypted' => Crypt::encrypt('sk-dev-fake-primary-0001'),
            'models'            => ['default' => 'model-pro', 'fast' => 'model-mini'],
            'status'            => AiProvider::STATUS_ACTIVE,
            'priority'          => 1,
        ]);

        AiProvider::create([
            'name'              => 'Provider du phong (dev)',
            'base_url'          => 'https://api.example-fallback.test/v1',
            'api_key_encrypted' => Crypt::encrypt('sk-dev-fake-fallback-0002'),
            'models'            => ['default' => 'model-alt'],
            'status'            => AiProvider::STATUS_ACTIVE,
            'priority'          => 2,   // duoc dung khi provider 1 loi
        ]);
    }
}
