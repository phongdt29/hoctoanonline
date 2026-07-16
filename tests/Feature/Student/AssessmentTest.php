<?php

use App\Models\Assessment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Http;

/*
 * Ticket C3 — DoD: refresh khong mat bai · moi cau co time_spent_seconds > 0 ·
 * de dung 5-10 cau.
 */

beforeEach(function () {
    $this->seed(\Database\Seeders\AiProviderSeeder::class);

    $user = User::create([
        'name' => 'HS', 'email' => 'hs.asm@hoctoan.test', 'password' => 'password', 'role' => 'student',
    ]);
    $this->student = Student::create([
        'user_id' => $user->id, 'full_name' => 'HS', 'grade' => 8,
        'self_assessed_level' => 'kha', 'math_gpa' => 7.0, 'status' => 'onboarded',
    ]);
    $this->user = $user;
});

/** Gia lap Gemini tra ve de 8 cau du chu de. */
function fakeAssessmentGen(int $count = 8): void
{
    $questions = [];
    $topics = ['phan_so', 'so_nguyen', 'ty_le_thuc', 'bieu_thuc', 'hinh_hoc'];

    for ($i = 0; $i < $count; $i++) {
        $questions[] = [
            'type' => $i % 2 === 0 ? 'multiple_choice' : 'essay',
            'topic' => $topics[$i % count($topics)],
            'difficulty' => ['easy', 'medium', 'hard'][$i % 3],
            'content' => "Câu {$i}: nội dung.",
            'options' => $i % 2 === 0 ? ['A', 'B', 'C', 'D'] : [],
            'correct_answer' => 'A',
        ];
    }

    $json = json_encode(['questions' => $questions]);
    Http::fake(['*' => Http::response([
        'candidates' => [['content' => ['parts' => [['text' => $json]]]]],
    ])]);
}

it('DoD C3: start sinh de dung 5-10 cau', function () {
    fakeAssessmentGen(8);

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/assessments/start')
        ->assertOk();

    $questions = $response->json('data.questions');

    expect(count($questions))->toBeGreaterThanOrEqual(5)
        ->and(count($questions))->toBeLessThanOrEqual(10);
});

it('start KHONG tra correct_answer ve client (chong lo dap an)', function () {
    fakeAssessmentGen();

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/assessments/start')->assertOk();

    expect($response->json('data.questions.0'))->not->toHaveKey('correct_answer');
    // Nhung DB van luu correct_answer de C4 cham.
    expect(Assessment::first()->questions->first()->correct_answer)->toBe(['value' => 'A']);
});

it('DoD C3: refresh (goi start lai) khong mat bai dang lam', function () {
    fakeAssessmentGen();

    $first = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/assessments/start')->json('data.id');

    // Goi start lan 2 — phai tra dung bai cu, khong tao bai moi.
    $second = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/assessments/start')->json('data.id');

    expect($second)->toBe($first)
        ->and(Assessment::count())->toBe(1);
});

it('DoD C3: save ghi student_answer va time_spent_seconds', function () {
    fakeAssessmentGen();

    $data = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/assessments/start')->json('data');
    $assessmentId = $data['id'];
    $q1 = $data['questions'][0]['id'];
    $q2 = $data['questions'][1]['id'];

    $this->actingAs($this->user, 'sanctum')
        ->putJson("/api/v1/assessments/{$assessmentId}/save", [
            'answers' => [
                $q1 => ['answer' => 'B', 'time_spent_seconds' => 45],
                $q2 => ['answer' => 'Đáp án tự luận', 'time_spent_seconds' => 120],
            ],
        ])->assertOk();

    $assessment = Assessment::find($assessmentId);
    $saved = $assessment->questions->keyBy('id');

    expect($saved[$q1]->student_answer)->toBe(['value' => 'B'])
        ->and($saved[$q1]->time_spent_seconds)->toBe(45)
        ->and($saved[$q2]->time_spent_seconds)->toBe(120);
});

it('time_spent cong don, khong ghi de nho hon (quay lai cau cu)', function () {
    fakeAssessmentGen();

    $data = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/assessments/start')->json('data');
    $id = $data['id'];
    $q1 = $data['questions'][0]['id'];

    $this->actingAs($this->user, 'sanctum')->putJson("/api/v1/assessments/{$id}/save", [
        'answers' => [$q1 => ['answer' => 'A', 'time_spent_seconds' => 60]],
    ]);

    // Autosave sau do gui thoi gian nho hon (vd reset dong ho client) — khong duoc lam mat 60.
    $this->actingAs($this->user, 'sanctum')->putJson("/api/v1/assessments/{$id}/save", [
        'answers' => [$q1 => ['answer' => 'A', 'time_spent_seconds' => 30]],
    ]);

    expect(Assessment::find($id)->questions->first()->time_spent_seconds)->toBe(60);
});

it('khong xem/luu duoc bai cua hoc sinh khac', function () {
    fakeAssessmentGen();
    $id = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/assessments/start')->json('data.id');

    $other = User::create([
        'name' => 'HS2', 'email' => 'hs2.asm@hoctoan.test', 'password' => 'password', 'role' => 'student',
    ]);
    Student::create(['user_id' => $other->id, 'full_name' => 'HS2', 'status' => 'onboarded']);

    $this->actingAs($other, 'sanctum')
        ->putJson("/api/v1/assessments/{$id}/save", ['answers' => []])
        ->assertStatus(403);
});

it('moi call sinh de deu ghi ai_logs', function () {
    fakeAssessmentGen();

    $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/assessments/start');

    expect(\App\Models\AiLog::where('feature', \App\Models\AiLog::FEATURE_ASSESSMENT_GEN)->exists())->toBeTrue();
});
