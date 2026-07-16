<?php

use App\Models\AttendanceSession;
use App\Models\LearningRiskScore;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\StudentActivityLog;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\RiskScoreService;

/*
 * Ticket M1/M2/M4 — DoD:
 *  M2: online 90 nhung 8 phut tuong tac -> "online 90 - hoc thuc 8 - tap trung thap" + partial.
 *  M4: risk score dung trong so config §3.6 + phan 3 muc.
 */

beforeEach(function () {
    $this->seed();
    $this->user = User::where('email', 'student1@hoctoan.test')->first();
    $this->student = $this->user->student;
    $this->lesson = $this->student->activeCurriculum->lessons->firstWhere('status', 'unlocked')
        ?? $this->student->activeCurriculum->lessons->first();
    $this->lesson->update(['status' => 'unlocked']);
});

function makeSession(Student $student, Lesson $lesson, $start, $end): AttendanceSession
{
    return AttendanceSession::create([
        'student_id' => $student->id,
        'lesson_id' => $lesson->id,
        'scheduled_start_time' => $start,
        'actual_start_time' => $start,
        'actual_end_time' => $end,
        'attendance_status' => 'absent_pending',
    ]);
}

// ---------- M1 ----------

it('M1: nhan batch event -> bulk insert + tao session', function () {
    $res = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/activity/events', [
        'lesson_id' => $this->lesson->id,
        'events' => [
            ['event_type' => 'lesson_open', 'event_time' => now()->toIso8601String()],
            ['event_type' => 'section_view', 'event_time' => now()->addMinute()->toIso8601String()],
        ],
    ])->assertOk();

    expect($res->json('data.recorded'))->toBe(2)
        ->and(StudentActivityLog::where('session_id', $res->json('data.session_id'))->count())->toBe(2);
});

it('M1: bo qua event khong hop le', function () {
    $res = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/activity/events', [
        'lesson_id' => $this->lesson->id,
        'events' => [
            ['event_type' => 'lesson_open', 'event_time' => now()->toIso8601String()],
            ['event_type' => 'HACK_EVENT', 'event_time' => now()->toIso8601String()],
        ],
    ])->assertOk();

    expect($res->json('data.recorded'))->toBe(1);   // chi lesson_open hop le
});

it('M1: khong ghi event cho lesson cua hoc sinh khac', function () {
    $other = User::where('email', 'student8@hoctoan.test')->first();
    $other->student->update(['status' => 'learning']);

    $this->actingAs($other, 'sanctum')->postJson('/api/v1/activity/events', [
        'lesson_id' => $this->lesson->id,   // lesson cua student1
        'events' => [['event_type' => 'lesson_open']],
    ])->assertStatus(403);
});

// ---------- M2 ----------

it('M2: effective time chi cong gap <= 3 phut, bo idle', function () {
    $start = now()->subMinutes(30);
    $session = makeSession($this->student, $this->lesson, $start, now());

    // Event: 0, +2p (tinh 2), +2p (tinh 2), roi idle +20p (KHONG tinh), +1p (KHONG tinh vi gap >3)
    $t = $start->copy();
    foreach ([0, 2, 4, 24, 25] as $offset) {
        StudentActivityLog::create([
            'session_id' => $session->id,
            'event_type' => 'answer_submit',
            'event_time' => $start->copy()->addMinutes($offset),
        ]);
    }

    $effective = app(AttendanceService::class)->computeEffectiveMinutes($session);

    // Gap: 0->2 (2), 2->4 (2), 4->24 (20, BO vi >3), 24->25 (1). Tong = 5 phut.
    expect($effective)->toBe(5);
});

it('DoD M2: online 90 nhung hoc thuc 8 -> partial + tap trung thap', function () {
    $start = now()->subMinutes(90);
    $end = now();
    $session = makeSession($this->student, $this->lesson, $start, $end);

    // 8 event cach nhau 1 phut o dau (hoc thuc ~7-8 phut), roi im lang 82 phut.
    for ($i = 0; $i <= 8; $i++) {
        StudentActivityLog::create([
            'session_id' => $session->id,
            'event_type' => 'section_view',
            'event_time' => $start->copy()->addMinutes($i),
        ]);
    }

    $svc = app(AttendanceService::class);
    // Khong nop quiz -> partial.
    $session = $svc->finalize($session, hasQuizSubmit: false);

    expect($session->onlineMinutes())->toBe(90)
        ->and($session->effective_study_minutes)->toBeLessThanOrEqual(10)
        ->and($session->attendance_status)->toBe('partial');

    $summary = $svc->focusSummary($session);
    expect($summary)->toContain('online 90 phút')
        ->and($summary)->toContain('mức tập trung thấp');
});

it('M2: du thoi luong + co nop quiz -> present', function () {
    $start = now()->subMinutes(45);
    $session = makeSession($this->student, $this->lesson, $start, now());

    // Event lien tuc moi 2 phut trong 40 phut -> effective ~40 (> 70% cua 45 = 31.5).
    for ($i = 0; $i <= 40; $i += 2) {
        StudentActivityLog::create([
            'session_id' => $session->id,
            'event_type' => 'exercise_start',
            'event_time' => $start->copy()->addMinutes($i),
        ]);
    }

    $session = app(AttendanceService::class)->finalize($session, hasQuizSubmit: true);

    expect($session->attendance_status)->toBe('present');
});

it('M2: khong vao / khong tuong tac -> absent', function () {
    $session = AttendanceSession::create([
        'student_id' => $this->student->id,
        'lesson_id' => $this->lesson->id,
        'scheduled_start_time' => now()->subHour(),
        'actual_start_time' => null,
        'attendance_status' => 'absent_pending',
    ]);

    $session = app(AttendanceService::class)->finalize($session, hasQuizSubmit: false);

    expect($session->attendance_status)->toBe('absent');
});

// ---------- M4 ----------

it('DoD M4: risk score tinh dung trong so config §3.6', function () {
    // Xoa attendance seed cua student1, tao kich ban ro rang.
    AttendanceSession::where('student_id', $this->student->id)->delete();

    $lesson = $this->lesson;
    // 4 buoi trong 7 ngay: 2 absent, 1 partial (completion 30), 1 present (completion 90).
    makeSession($this->student, $lesson, now()->subDays(1), now()->subDays(1))
        ->update(['attendance_status' => 'absent', 'actual_start_time' => null, 'completion_rate' => 0]);
    makeSession($this->student, $lesson, now()->subDays(2), now()->subDays(2))
        ->update(['attendance_status' => 'absent', 'actual_start_time' => null, 'completion_rate' => 0]);
    makeSession($this->student, $lesson, now()->subDays(3), now()->subDays(3))
        ->update(['attendance_status' => 'partial', 'completion_rate' => 30]);
    makeSession($this->student, $lesson, now()->subDays(4), now()->subDays(4))
        ->update(['attendance_status' => 'present', 'completion_rate' => 90]);

    $risk = app(RiskScoreService::class)->compute($this->student);

    // Kiem tra: risk = tong(weight * rate) khop components luu.
    $weights = config('hoctoan.risk_weights');
    $expected = 0.0;
    foreach ($risk->components as $k => $rate) {
        $expected += $weights[$k] * $rate;
    }

    expect($risk->risk_score)->toBe((int) round($expected))
        ->and($risk->components)->toHaveKeys(array_keys($weights))
        ->and((float) $risk->components['absenteeism'])->toEqualWithDelta(50.0, 0.01);   // 2/4 absent = 50%
});

it('M4: phan 3 muc xanh/vang/do theo nguong', function () {
    expect(LearningRiskScore::levelFor(20))->toBe('on_dinh')
        ->and(LearningRiskScore::levelFor(30))->toBe('on_dinh')
        ->and(LearningRiskScore::levelFor(45))->toBe('can_theo_doi')
        ->and(LearningRiskScore::levelFor(60))->toBe('can_theo_doi')
        ->and(LearningRiskScore::levelFor(80))->toBe('nguy_co_cao');
});
