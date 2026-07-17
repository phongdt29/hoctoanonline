<?php
use App\Models\AiLog;
use App\Models\User;
use App\Services\AdminAnalyticsService;
use Illuminate\Support\Facades\Http;

beforeEach(fn () => $this->seed());

it('AI call luu so token tu usageMetadata', function () {
    $this->seed(\Database\Seeders\AiProviderSeeder::class);
    Http::fake(['*' => Http::response([
        'candidates' => [['content' => ['parts' => [['text' => '{"a":"x"}']]]]],
        'usageMetadata' => ['promptTokenCount' => 100, 'candidatesTokenCount' => 40, 'totalTokenCount' => 150],
    ])]);

    $schema = ['type'=>'object','required'=>['a'],'properties'=>['a'=>['type'=>'string']]];
    app(\App\Services\AiProviderService::class)->chat('tutor_chat', 'p', $schema);

    $log = AiLog::where('status', 'ok')->latest('id')->first();
    expect($log->prompt_tokens)->toBe(100)
        ->and($log->completion_tokens)->toBe(40)
        ->and($log->total_tokens)->toBe(150);
});

it('tokenStats tinh dung tong + chi phi', function () {
    AiLog::create(['feature'=>'assessment_gen','status'=>'ok','request_json'=>[],'prompt_tokens'=>1000,'completion_tokens'=>500,'total_tokens'=>1500]);

    config(['hoctoan.ai_pricing' => ['input_usd_per_1m'=>0.30,'output_usd_per_1m'=>2.50,'usd_to_vnd'=>25000]]);
    $t = app(AdminAnalyticsService::class)->tokenStats();

    // output = max(500, 1500-1000)=500. cost = 1000/1e6*0.3 + 500/1e6*2.5 = 0.0003+0.00125=0.00155
    expect($t['all_time']['total'])->toBe(1500)
        ->and($t['cost_all_usd'])->toBe(0.0016)   // round 4
        ->and($t['cost_all_vnd'])->toBe((int) round(0.00155 * 25000));
});

it('chi phi tinh ca token thinking (total > prompt+completion)', function () {
    // Model 2.5: total=234, prompt=9, completion=5 -> output tinh phi = 234-9=225.
    AiLog::create(['feature'=>'tutor_chat','status'=>'ok','request_json'=>[],'prompt_tokens'=>9,'completion_tokens'=>5,'total_tokens'=>234]);

    config(['hoctoan.ai_pricing' => ['input_usd_per_1m'=>0.30,'output_usd_per_1m'=>2.50,'usd_to_vnd'=>25000]]);
    $t = app(AdminAnalyticsService::class)->tokenStats();

    $expected = 9/1e6*0.30 + 225/1e6*2.50;   // dung output = total-prompt
    expect($t['cost_all_usd'])->toBe(round($expected, 4));
});

it('report admin hien khoi chi phi token', function () {
    AiLog::create(['feature'=>'tutor_chat','status'=>'ok','request_json'=>[],'prompt_tokens'=>100,'completion_tokens'=>50,'total_tokens'=>200]);
    $admin = User::where('email','admin@hoctoan.test')->first();
    $this->actingAs($admin)->get('/admin')
        ->assertOk()
        ->assertSee('token A.I')
        ->assertSee('Chi phí ước tính');
});
