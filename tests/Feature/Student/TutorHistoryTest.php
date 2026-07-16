<?php
use App\Models\Student;
use App\Models\TutorConversation;
use App\Models\TutorMessage;
use App\Models\User;

beforeEach(fn () => $this->seed(\Database\Seeders\AiProviderSeeder::class));

function studentWithChat(): array {
    $u = User::create(['name'=>'HS','email'=>'hs.th'.rand(1000,9999).'@t.test','password'=>'password','role'=>'student']);
    $s = Student::create(['user_id'=>$u->id,'full_name'=>'HS','grade'=>8,'status'=>'learning']);
    return [$u, $s];
}

it('current: chua co chat -> tao moi, messages rong', function () {
    [$u] = studentWithChat();
    $res = test()->actingAs($u, 'sanctum')->getJson('/api/v1/tutor/current')->assertOk();
    expect($res->json('data.conversation_id'))->not->toBeNull()
        ->and($res->json('data.messages'))->toBe([]);
});

it('current: tra ve lich su cuoc tro chuyen gan nhat', function () {
    [$u, $s] = studentWithChat();
    $conv = TutorConversation::create(['student_id'=>$s->id]);
    TutorMessage::create(['conversation_id'=>$conv->id,'sender'=>'student','content'=>'Câu hỏi 1']);
    TutorMessage::create(['conversation_id'=>$conv->id,'sender'=>'ai','content'=>'Trả lời 1']);

    $res = test()->actingAs($u, 'sanctum')->getJson('/api/v1/tutor/current')->assertOk();

    expect($res->json('data.conversation_id'))->toBe($conv->id)
        ->and($res->json('data.messages'))->toHaveCount(2)
        ->and($res->json('data.messages.0.content'))->toBe('Câu hỏi 1')
        ->and($res->json('data.messages.1.sender'))->toBe('ai');
});

it('current: khong lay duoc chat cua hoc sinh khac', function () {
    [, $s1] = studentWithChat();
    $conv = TutorConversation::create(['student_id'=>$s1->id]);
    TutorMessage::create(['conversation_id'=>$conv->id,'sender'=>'student','content'=>'Bí mật']);

    [$u2] = studentWithChat();
    $res = test()->actingAs($u2, 'sanctum')->getJson('/api/v1/tutor/current')->assertOk();

    // student2 -> conversation moi cua chinh minh, khong thay 'Bí mật'.
    expect($res->json('data.conversation_id'))->not->toBe($conv->id)
        ->and(collect($res->json('data.messages'))->pluck('content'))->not->toContain('Bí mật');
});
