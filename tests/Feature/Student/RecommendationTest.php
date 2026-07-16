<?php

use App\Models\Lesson;
use App\Models\QuizAttempt;
use App\Models\Student;
use App\Models\User;
use App\Services\RecommendationService;
use Illuminate\Support\Facades\DB;

/*
 * Ticket L3 — DoD: yeu topic A khong bi day sang B · thay tin hieu -> recommendation doi ·
 * diem < 5 -> curriculum co lesson on moi chen vao.
 * Ticket L4 — DoD: dashboard 1 request (khong N+1).
 */

beforeEach(function () {
    $this->seed();
    $this->user = User::where('email', 'student1@hoctoan.test')->first();
    $this->student = $this->user->student;
});

function addQuizAttempt(Student $student, float $score, array $errorByTopic = []): void
{
    $quiz = $student->activeCurriculum->lessons->first()->quiz;

    QuizAttempt::create([
        'quiz_id' => $quiz->id,
        'student_id' => $student->id,
        'score' => $score,
        'error_analysis' => ['by_topic' => $errorByTopic],
        'suggestion' => $score >= 5 ? 'hoc_tiep' : 'on_lai',
        'started_at' => now()->subMinutes(20),
        'expires_at' => now()->subMinutes(5),
        'submitted_at' => now()->subMinutes(6),
    ]);
}

it('L3: tra du 3 cau (new/review/reinforce) + priority + mix', function () {
    $rec = app(RecommendationService::class)->recommend($this->student);

    expect($rec)->toHaveKeys(['new_content', 'review_content', 'reinforce_content', 'priority', 'mix'])
        ->and($rec['mix'])->toHaveKeys(['review', 'new', 'reinforce']);
});

it('L3: mix mac dinh 20/60/20 khi chua co diem gan nhat', function () {
    // Xoa het quiz attempt de khong co diem.
    QuizAttempt::where('student_id', $this->student->id)->delete();

    $rec = app(RecommendationService::class)->recommend($this->student);

    expect($rec['mix']['review'])->toBe(20)
        ->and($rec['mix']['new'])->toBe(60)
        ->and($rec['mix']['reinforce'])->toBe(20);
});

it('DoD L3: diem thap -> mix nghieng ve on lai (review_first)', function () {
    QuizAttempt::where('student_id', $this->student->id)->delete();
    addQuizAttempt($this->student, 4.0, ['phan_so' => ['wrong' => 3, 'total' => 5]]);

    $rec = app(RecommendationService::class)->recommend($this->student->fresh());

    expect($rec['priority'])->toBe('review_first')
        ->and($rec['mix']['review'])->toBeGreaterThan($rec['mix']['new']);   // on > moi
});

it('DoD L3: diem cao -> nghieng bai moi (new_first)', function () {
    QuizAttempt::where('student_id', $this->student->id)->delete();
    addQuizAttempt($this->student, 9.0);

    $rec = app(RecommendationService::class)->recommend($this->student->fresh());

    expect($rec['priority'])->toBe('new_first')
        ->and($rec['mix']['new'])->toBeGreaterThan($rec['mix']['review']);
});

it('DoD L3: hai hoc sinh cung diem nhung khac hanh vi -> recommendation khac', function () {
    QuizAttempt::where('student_id', $this->student->id)->delete();

    // HS A: diem 8.5, on dinh (2 buoi gan giong nhau).
    addQuizAttempt($this->student, 8.5);
    addQuizAttempt($this->student, 8.0);
    $recStable = app(RecommendationService::class)->recommend($this->student->fresh());

    // HS B (cung student, reset): diem 8.5 nhung KHONG on dinh (dao dong manh).
    QuizAttempt::where('student_id', $this->student->id)->delete();
    addQuizAttempt($this->student, 8.5);
    addQuizAttempt($this->student, 3.0);   // buoi truoc rot manh -> bat on
    $recUnstable = app(RecommendationService::class)->recommend($this->student->fresh());

    // Cung diem gan nhat (8.5) nhung do on dinh khac -> mix reinforce khac nhau.
    expect($recStable['mix']['reinforce'])->not->toBe($recUnstable['mix']['reinforce']);
});

it('DoD L3: diem < 5 -> chen buoi on moi vao curriculum (curriculum dong)', function () {
    QuizAttempt::where('student_id', $this->student->id)->delete();

    $before = Lesson::whereIn('module_id', $this->student->activeCurriculum->modules->pluck('id'))->count();

    addQuizAttempt($this->student, 3.5);
    app(RecommendationService::class)->recommend($this->student->fresh());

    $after = Lesson::whereIn('module_id', $this->student->activeCurriculum->modules->pluck('id'))->count();

    // Co it nhat 1 lesson on moi duoc chen.
    expect($after)->toBeGreaterThan($before);

    // Buoi chen la "On tap ..."
    expect(Lesson::where('title', 'like', 'Ôn tập%')->exists())->toBeTrue();
});

it('L3: khong chen buoi on trung lap khi goi nhieu lan', function () {
    QuizAttempt::where('student_id', $this->student->id)->delete();
    addQuizAttempt($this->student, 3.5);

    $svc = app(RecommendationService::class);
    $svc->recommend($this->student->fresh());
    $countAfterFirst = Lesson::where('title', 'like', 'Ôn tập%')->count();

    $svc->recommend($this->student->fresh());
    $countAfterSecond = Lesson::where('title', 'like', 'Ôn tập%')->count();

    expect($countAfterSecond)->toBe($countAfterFirst);
});

it('DoD L4: dashboard build trong 1 request, khong N+1', function () {
    DB::enableQueryLog();

    app(\App\Services\StudentDashboardService::class)->build($this->student);

    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Nguong hop ly: gom curriculum+modules+lessons + vai query tin hieu.
    // Neu N+1 (query moi lesson) thi se vuot xa nguong nay.
    expect($queries)->toBeLessThan(20);
});

it('L4: dashboard API tra du cac khoi', function () {
    $res = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/dashboard/student')->assertOk();

    $res->assertJsonStructure([
        'data' => [
            'completion_percent', 'sessions_done', 'sessions_remaining',
            'avg_quiz_score', 'weak_topics', 'points_balance', 'streak_days',
            'today_recommendation' => ['new_content', 'review_content', 'reinforce_content', 'priority', 'mix'],
        ],
    ]);
});

it('L4: completion_percent tinh dung tu lesson completed', function () {
    $res = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/dashboard/student')->json('data');

    // student1 seed: 13/18 completed = 72%
    expect($res['completion_percent'])->toBe(72)
        ->and($res['sessions_done'])->toBe(13)
        ->and($res['sessions_remaining'])->toBe(5);
});

it('L4: recommendation API rieng cung chay', function () {
    $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/recommendations/today')
        ->assertOk()
        ->assertJsonPath('data.priority', fn ($p) => in_array($p, ['new_first', 'review_first']));
});
