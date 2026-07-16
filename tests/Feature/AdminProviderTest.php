<?php

use App\Models\AiProvider;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

/*
 * Ticket T3 — DoD: disable provider 1 -> tu dung provider 2 (test that) ·
 * key khong lo ra response (masked).
 */

beforeEach(function () {
    $this->seed();
    $this->admin = User::where('email', 'admin@hoctoan.test')->first();
});

it('T3: admin xem duoc danh sach provider', function () {
    $this->actingAs($this->admin, 'sanctum')
        ->getJson('/api/v1/admin/ai-providers')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('DoD T3: key KHONG lo ra response (chi masked)', function () {
    $res = $this->actingAs($this->admin, 'sanctum')
        ->getJson('/api/v1/admin/ai-providers')->json('data');

    foreach ($res as $provider) {
        expect($provider)->not->toHaveKey('api_key')
            ->and($provider)->not->toHaveKey('api_key_encrypted')
            ->and($provider['api_key_masked'])->toContain('•');
    }
});

it('T3: them provider moi -> key duoc ma hoa', function () {
    $res = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/admin/ai-providers', [
        'name' => 'Provider Test',
        'base_url' => 'https://api.test.com/v1',
        'api_key' => 'sk-secret-real-key-12345',
        'models' => ['default' => 'model-x'],
        'status' => 'active',
        'priority' => 3,
    ])->assertStatus(201);

    $provider = AiProvider::find($res->json('data.id'));

    // Key luu la ma hoa, giai ma ra dung.
    expect($provider->api_key_encrypted)->not->toBe('sk-secret-real-key-12345')
        ->and(Crypt::decrypt($provider->api_key_encrypted))->toBe('sk-secret-real-key-12345');

    // Response khong chua key that.
    expect($res->json('data'))->not->toHaveKey('api_key');
});

it('T3: sua provider khong bat nhap lai key (giu key cu)', function () {
    $provider = AiProvider::first();
    $oldKey = $provider->api_key_encrypted;

    $this->actingAs($this->admin, 'sanctum')->putJson("/api/v1/admin/ai-providers/{$provider->id}", [
        'name' => 'Đổi tên',
        'base_url' => $provider->base_url,
        'models' => $provider->models,
        'status' => 'disabled',
        'priority' => 5,
        // khong gui api_key
    ])->assertOk();

    $provider->refresh();
    expect($provider->name)->toBe('Đổi tên')
        ->and($provider->status)->toBe('disabled')
        ->and($provider->api_key_encrypted)->toBe($oldKey);   // key giu nguyen
});

it('DoD T3: disable provider 1 -> AiProviderService tu dung provider 2', function () {
    // Fake: provider 1 base_url tra ok, provider 2 base_url cung ok.
    Http::fake(['*' => Http::response([
        'candidates' => [['content' => ['parts' => [['text' => '{"x":1}']]]]],
    ])]);

    // Disable provider priority 1.
    $p1 = AiProvider::where('priority', 1)->first();
    $this->actingAs($this->admin, 'sanctum')->putJson("/api/v1/admin/ai-providers/{$p1->id}", [
        'name' => $p1->name, 'base_url' => $p1->base_url, 'models' => $p1->models,
        'status' => 'disabled', 'priority' => 1,
    ])->assertOk();

    // Giờ usable() chỉ còn provider 2.
    $usable = AiProvider::usable()->get();
    expect($usable)->toHaveCount(1)
        ->and($usable->first()->priority)->toBe(2);

    // Goi AI -> dung provider 2 (priority 2).
    $schema = ['type' => 'object', 'required' => ['x'], 'properties' => ['x' => ['type' => 'integer']]];
    app(\App\Services\AiProviderService::class)->chat(\App\Models\AiLog::FEATURE_SOLVER, 'p', $schema);

    $usedId = \App\Models\AiLog::where('status', 'ok')->latest('id')->first()->provider_id;
    expect($usedId)->toBe(AiProvider::where('priority', 2)->first()->id);
});

it('T3: xoa provider', function () {
    $provider = AiProvider::where('priority', 2)->first();

    $this->actingAs($this->admin, 'sanctum')
        ->deleteJson("/api/v1/admin/ai-providers/{$provider->id}")
        ->assertOk();

    expect(AiProvider::find($provider->id))->toBeNull();
});

it('T3: hoc sinh/giao vien KHONG vao duoc admin provider', function () {
    $student = User::where('email', 'student1@hoctoan.test')->first();
    $teacher = User::where('email', 'teacher1@hoctoan.test')->first();

    $this->actingAs($student, 'sanctum')->getJson('/api/v1/admin/ai-providers')->assertStatus(403);
    $this->actingAs($teacher, 'sanctum')->getJson('/api/v1/admin/ai-providers')->assertStatus(403);
});
