<?php

use App\Models\Student;
use App\Models\TutorConversation;
use App\Models\TutorMessage;
use App\Models\User;
use Illuminate\Support\Facades\Http;

/*
 * Ticket I1 — DoD: doi tab -> polling dung (client) · persona co/thay khac nhau ·
 * moi message co ai_logs.
 */

beforeEach(function () {
    $this->seed(\Database\Seeders\AiProviderSeeder::class);
});

function tutorStudent(string $gender): array
{
    $user = User::create([
        'name' => 'HS', 'email' => 'hs.'.uniqid().'@hoctoan.test', 'password' => 'password', 'role' => 'student',
    ]);
    $student = Student::create([
        'user_id' => $user->id, 'full_name' => 'HS', 'grade' => 8,
        'tutor_gender' => $gender, 'self_assessed_level' => 'kha', 'status' => 'learning',
    ]);

    return [$user, $student];
}

function fakeTutorReply(string $text): void
{
    Http::fake(['*' => Http::response([
        'candidates' => [['content' => ['parts' => [['text' => $text]]]]],
    ])]);
}

it('I1: tao conversation + gui tin -> co tin AI tra loi', function () {
    fakeTutorReply('Chào em, cô giúp gì nào?');
    [$user] = tutorStudent('co');

    $convId = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/tutor/conversations', ['title' => 'Hỏi bài'])
        ->json('data.id');

    $res = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/tutor/conversations/{$convId}/messages", ['content' => 'Phân số là gì ạ?'])
        ->assertOk();

    expect($res->json('data.sender'))->toBe('ai')
        ->and($res->json('data.content'))->toBe('Chào em, cô giúp gì nào?');

    // Luu ca tin hoc sinh + tin AI.
    expect(TutorMessage::where('conversation_id', $convId)->count())->toBe(2);
});

it('DoD I1: persona co va thay khac nhau (prompt inject xung ho)', function () {
    $sent = [];
    Http::fake(function ($request) use (&$sent) {
        $sent[] = $request->data()['contents'][0]['parts'][0]['text'];

        return Http::response(['candidates' => [['content' => ['parts' => [['text' => 'ok']]]]]]);
    });

    [$userCo] = tutorStudent('co');
    [$userThay] = tutorStudent('thay');

    $convCo = $this->actingAs($userCo, 'sanctum')->postJson('/api/v1/tutor/conversations')->json('data.id');
    $this->actingAs($userCo, 'sanctum')->postJson("/api/v1/tutor/conversations/{$convCo}/messages", ['content' => 'hi']);

    $convThay = $this->actingAs($userThay, 'sanctum')->postJson('/api/v1/tutor/conversations')->json('data.id');
    $this->actingAs($userThay, 'sanctum')->postJson("/api/v1/tutor/conversations/{$convThay}/messages", ['content' => 'hi']);

    // Prompt co chua "cô", prompt thay chua "thầy".
    expect($sent[0])->toContain('CÔ')
        ->and($sent[1])->toContain('THẦY');
});

it('DoD I1: polling after_id chi tra tin moi hon', function () {
    fakeTutorReply('reply');
    [$user] = tutorStudent('co');

    $convId = $this->actingAs($user, 'sanctum')->postJson('/api/v1/tutor/conversations')->json('data.id');
    $this->actingAs($user, 'sanctum')->postJson("/api/v1/tutor/conversations/{$convId}/messages", ['content' => 'câu 1']);

    // Lay het tin (2 tin: student + ai).
    $all = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/tutor/conversations/{$convId}/messages")->json('data');
    expect($all)->toHaveCount(2);

    $lastId = collect($all)->last()['id'];

    // Poll after_id = lastId -> chua co tin moi.
    $none = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/tutor/conversations/{$convId}/messages?after_id={$lastId}")->json('data');
    expect($none)->toHaveCount(0);

    // Gui them -> poll thay 2 tin moi.
    $this->actingAs($user, 'sanctum')->postJson("/api/v1/tutor/conversations/{$convId}/messages", ['content' => 'câu 2']);
    $new = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/tutor/conversations/{$convId}/messages?after_id={$lastId}")->json('data');
    expect($new)->toHaveCount(2);
});

it('DoD I1: moi message co ai_logs', function () {
    fakeTutorReply('reply');
    [$user] = tutorStudent('co');

    $convId = $this->actingAs($user, 'sanctum')->postJson('/api/v1/tutor/conversations')->json('data.id');
    $this->actingAs($user, 'sanctum')->postJson("/api/v1/tutor/conversations/{$convId}/messages", ['content' => 'hi']);

    expect(\App\Models\AiLog::where('feature', \App\Models\AiLog::FEATURE_TUTOR_CHAT)->exists())->toBeTrue();
});

it('I1: khong xem/gui tin conversation cua nguoi khac', function () {
    fakeTutorReply('reply');
    [$user] = tutorStudent('co');
    $convId = $this->actingAs($user, 'sanctum')->postJson('/api/v1/tutor/conversations')->json('data.id');

    [$other] = tutorStudent('thay');
    $this->actingAs($other, 'sanctum')
        ->getJson("/api/v1/tutor/conversations/{$convId}/messages")
        ->assertStatus(403);
});
