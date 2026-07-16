<?php

use App\Models\Lesson;
use App\Models\PointLedger;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Http;

/*
 * Ticket L1 + L2 — DoD:
 *  L1: vao lesson locked -> 403.
 *  L2: sua gio client -> server van chot dung · submit sau het gio -> chan ·
 *      diem vao ledger dung 1 lan (khong double) · client KHONG thay dap an.
 */

beforeEach(function () {
    $this->seed();
    $this->user = User::where('email', 'student1@hoctoan.test')->first();
    $this->student = $this->user->student;
});

/** Fake Gemini sinh 5 cau quiz, dap an dung deu la 'A'. */
function fakeQuizGen(): void
{
    $questions = [];
    for ($i = 0; $i < 5; $i++) {
        $questions[] = [
            'topic' => 'phan_so',
            'content' => "Câu {$i}",
            'options' => ['1', '2', '3', '4'],
            'correct' => 'A',
        ];
    }
    Http::fake(['*' => Http::response([
        'candidates' => [['content' => ['parts' => [['text' => json_encode(['questions' => $questions])]]]]],
    ])]);
}

/** Lay 1 lesson unlocked cua student1 + quiz cua no. */
function unlockedQuiz(Student $student): Quiz
{
    $lesson = $student->activeCurriculum->lessons->firstWhere('status', 'unlocked')
        ?? $student->activeCurriculum->lessons->firstWhere('status', 'completed');
    $lesson->update(['status' => 'unlocked']);

    return $lesson->quiz;
}

// ---------- L1 ----------

it('DoD L1: vao lesson locked -> 403', function () {
    $locked = $this->student->activeCurriculum->lessons->firstWhere('status', 'locked');

    $this->actingAs($this->user)->get(route('lessons.show', $locked))->assertStatus(403);
});

it('L1: vao lesson unlocked cua minh -> 200, danh dau in_progress', function () {
    $lesson = $this->student->activeCurriculum->lessons->firstWhere('status', 'locked');
    $lesson->update(['status' => 'unlocked']);

    $this->actingAs($this->user)->get(route('lessons.show', $lesson))->assertOk();

    expect($lesson->fresh()->status)->toBe('in_progress');
});

it('L1: khong vao duoc lesson cua hoc sinh khac', function () {
    // Lesson cua student1
    $lesson = $this->student->activeCurriculum->lessons->first();
    $lesson->update(['status' => 'unlocked']);

    $other = User::where('email', 'student8@hoctoan.test')->first();
    $other->student->update(['status' => 'learning']);

    $this->actingAs($other)->get(route('lessons.show', $lesson))->assertStatus(403);
});

// ---------- L2 ----------

it('L2: start tra expires_at + cau hoi KHONG kem dap an', function () {
    fakeQuizGen();
    $quiz = unlockedQuiz($this->student);

    $res = $this->actingAs($this->user, 'sanctum')
        ->postJson(route('api.quizzes.start', $quiz))->assertOk();

    $data = $res->json('data');
    expect($data['expires_at'])->not->toBeNull()
        ->and($data['questions'])->toHaveCount(5);

    // Cau hoi tra ve KHONG duoc chua 'correct'.
    foreach ($data['questions'] as $q) {
        expect($q)->not->toHaveKey('correct');
    }
});

it('DoD L2: cham dung server-side, client khong the tu khai dap an', function () {
    fakeQuizGen();   // dap an dung deu 'A'
    $quiz = unlockedQuiz($this->student);

    $start = $this->actingAs($this->user, 'sanctum')->postJson(route('api.quizzes.start', $quiz))->json('data');
    $attemptId = $start['attempt_id'];

    // Hoc sinh tra dung 3/5 cau (cau 0,1,2 = A).
    $res = $this->actingAs($this->user, 'sanctum')->postJson(route('api.quizzes.submit', $quiz), [
        'attempt_id' => $attemptId,
        'answers' => [0 => 'A', 1 => 'A', 2 => 'A', 3 => 'B', 4 => 'C'],
    ])->assertOk();

    // 3/5 = 6.0 diem. Server tu tinh, khong tin client.
    expect((float) $res->json('data.score'))->toBe(6.0);
});

it('DoD L2: submit sau khi het gio -> khong tinh cau nao dung (server chot gio)', function () {
    fakeQuizGen();
    $quiz = unlockedQuiz($this->student);

    $start = $this->actingAs($this->user, 'sanctum')->postJson(route('api.quizzes.start', $quiz))->json('data');
    $attemptId = $start['attempt_id'];

    // Gia lap het gio: day expires_at ve qua khu (mo phong "sua gio" o phia server).
    QuizAttempt::find($attemptId)->update(['expires_at' => now()->subMinute()]);

    $res = $this->actingAs($this->user, 'sanctum')->postJson(route('api.quizzes.submit', $quiz), [
        'attempt_id' => $attemptId,
        'answers' => [0 => 'A', 1 => 'A', 2 => 'A', 3 => 'A', 4 => 'A'],   // tra dung het
    ])->assertOk();

    // Het gio -> khong cau nao duoc tinh -> 0 diem, du client gui dap an dung het.
    expect((float) $res->json('data.score'))->toBe(0.0)
        ->and($res->json('data.error_analysis.expired'))->toBeTrue();
});

it('DoD L2: diem vao ledger dung 1 lan, submit lai khong double', function () {
    fakeQuizGen();
    $quiz = unlockedQuiz($this->student);

    $start = $this->actingAs($this->user, 'sanctum')->postJson(route('api.quizzes.start', $quiz))->json('data');
    $attemptId = $start['attempt_id'];

    $before = PointLedger::where('student_id', $this->student->id)->count();

    $submit = fn () => $this->actingAs($this->user, 'sanctum')->postJson(route('api.quizzes.submit', $quiz), [
        'attempt_id' => $attemptId,
        'answers' => [0 => 'A', 1 => 'A', 2 => 'A', 3 => 'A', 4 => 'A'],   // 5/5 = 10 diem
    ]);

    $submit()->assertOk();
    $submit()->assertOk();   // nop lan 2

    $after = PointLedger::where('student_id', $this->student->id)
        ->where('reason', PointLedger::REASON_QUIZ_SCORE)
        ->where('ref_id', $attemptId)
        ->count();

    // Chi 1 but toan cho attempt nay, du submit 2 lan.
    expect($after)->toBe(1);
});

it('L2: quiz >= 8 -> mo khoa lesson ke tiep', function () {
    fakeQuizGen();

    // Lay lesson locked dau tien, mo no ra lam quiz.
    $lessons = $this->student->activeCurriculum->lessons()->orderBy('lessons.id')->get();
    $target = $lessons->firstWhere('status', 'locked');
    $target->update(['status' => 'unlocked']);
    $quiz = $target->quiz;

    $countLockedBefore = $this->student->activeCurriculum->lessons()->where('status', 'locked')->count();

    $start = $this->actingAs($this->user, 'sanctum')->postJson(route('api.quizzes.start', $quiz))->json('data');

    $this->actingAs($this->user, 'sanctum')->postJson(route('api.quizzes.submit', $quiz), [
        'attempt_id' => $start['attempt_id'],
        'answers' => [0 => 'A', 1 => 'A', 2 => 'A', 3 => 'A', 4 => 'A'],   // 10 diem
    ])->assertOk();

    // Lesson lam quiz -> completed, va co 1 lesson locked duoc mo (locked giam 1).
    expect($target->fresh()->status)->toBe('completed')
        ->and($this->student->activeCurriculum->lessons()->where('status', 'locked')->count())
        ->toBeLessThan($countLockedBefore);
});

it('L2: khong start duoc quiz cua lesson locked', function () {
    fakeQuizGen();
    $locked = $this->student->activeCurriculum->lessons->firstWhere('status', 'locked');

    $this->actingAs($this->user, 'sanctum')
        ->postJson(route('api.quizzes.start', $locked->quiz))
        ->assertStatus(403);
});

it('L2: start lai khi dang co attempt mo -> tra dung attempt cu', function () {
    fakeQuizGen();
    $quiz = unlockedQuiz($this->student);

    $first = $this->actingAs($this->user, 'sanctum')->postJson(route('api.quizzes.start', $quiz))->json('data.attempt_id');
    $second = $this->actingAs($this->user, 'sanctum')->postJson(route('api.quizzes.start', $quiz))->json('data.attempt_id');

    expect($second)->toBe($first)
        ->and(QuizAttempt::where('quiz_id', $quiz->id)->count())->toBe(1);
});
