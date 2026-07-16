<?php

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ParentNotification;
use App\Models\PointLedger;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;

/*
 * Ticket T1 + T2 — DoD: chuoi day du giao -> nop -> cham -> diem vao ledger ->
 * phu huynh nhan thong bao. Nop 2 lan -> cap nhat, khong tao ban ghi trung.
 */

beforeEach(function () {
    $this->seed();
    // student1 (grade 7) thuoc lop "Toan 7 - Lop A" cua teacher1.
    $this->teacher = User::where('email', 'teacher1@hoctoan.test')->first();
    $this->class = SchoolClass::where('teacher_id', $this->teacher->id)->first();
    $this->studentUser = User::where('email', 'student1@hoctoan.test')->first();
    $this->student = $this->studentUser->student;
    $this->assignment = $this->class->assignments->first();
});

it('T2: hoc sinh thay danh sach bai cua lop minh', function () {
    $res = $this->actingAs($this->studentUser, 'sanctum')
        ->getJson('/api/v1/student/assignments')->assertOk();

    expect($res->json('data'))->not->toBeEmpty()
        ->and($res->json('data.0.title'))->toBe($this->assignment->title)
        ->and($res->json('data.0.submitted'))->toBeFalse();
});

it('DoD T2: hoc sinh nop bai duoc', function () {
    $res = $this->actingAs($this->studentUser, 'sanctum')
        ->postJson("/api/v1/student/assignments/{$this->assignment->id}/submit", [
            'content' => 'Bài làm của em: đáp án là 42.',
        ])->assertOk();

    expect(AssignmentSubmission::where('assignment_id', $this->assignment->id)
        ->where('student_id', $this->student->id)->exists())->toBeTrue();
});

it('T2: nop bai rong -> 422', function () {
    $this->actingAs($this->studentUser, 'sanctum')
        ->postJson("/api/v1/student/assignments/{$this->assignment->id}/submit", [])
        ->assertStatus(422);
});

it('DoD T2: nop 2 lan -> cap nhat, khong tao ban ghi trung', function () {
    $submit = fn ($content) => $this->actingAs($this->studentUser, 'sanctum')
        ->postJson("/api/v1/student/assignments/{$this->assignment->id}/submit", ['content' => $content]);

    $submit('Bài làm lần 1');
    $submit('Bài làm lần 2 (sửa lại)');

    $subs = AssignmentSubmission::where('assignment_id', $this->assignment->id)
        ->where('student_id', $this->student->id)->get();

    expect($subs)->toHaveCount(1)   // khong trung
        ->and($subs->first()->content)->toBe('Bài làm lần 2 (sửa lại)');
});

it('T2: khong nop duoc bai cua lop minh khong tham gia', function () {
    // student8 (grade 11) khong thuoc lop Toan 7.
    $other = User::where('email', 'student8@hoctoan.test')->first();
    $other->student->update(['status' => 'learning']);

    $this->actingAs($other, 'sanctum')
        ->postJson("/api/v1/student/assignments/{$this->assignment->id}/submit", ['content' => 'x'])
        ->assertStatus(403);
});

it('DoD T2: CHUOI DAY DU giao -> nop -> cham -> ledger -> notify parent', function () {
    // 1. Hoc sinh nop
    $this->actingAs($this->studentUser, 'sanctum')
        ->postJson("/api/v1/student/assignments/{$this->assignment->id}/submit", ['content' => 'Bài làm']);

    $submission = AssignmentSubmission::where('assignment_id', $this->assignment->id)
        ->where('student_id', $this->student->id)->first();

    $ledgerBefore = PointLedger::where('student_id', $this->student->id)->count();
    $notifBefore = ParentNotification::where('student_id', $this->student->id)->count();

    // 2. Giao vien cham
    $this->actingAs($this->teacher, 'sanctum')
        ->postJson("/api/v1/teacher/submissions/{$submission->id}/grade", [
            'score' => 8.5,
            'feedback' => 'Làm tốt, trình bày rõ.',
        ])->assertOk();

    $submission->refresh();

    // 3. Diem vao submission
    expect((float) $submission->score)->toBe(8.5)
        ->and($submission->isGraded())->toBeTrue();

    // 4. Diem vao point_ledger (reason assignment_graded)
    expect(PointLedger::where('student_id', $this->student->id)
        ->where('reason', 'assignment_graded')->where('ref_id', $submission->id)->exists())->toBeTrue()
        ->and(PointLedger::where('student_id', $this->student->id)->count())->toBeGreaterThan($ledgerBefore);

    // 5. Phu huynh nhan thong bao
    expect(ParentNotification::where('student_id', $this->student->id)->count())->toBeGreaterThan($notifBefore);
});

it('T2: cham lai khong double diem trong ledger', function () {
    $this->actingAs($this->studentUser, 'sanctum')
        ->postJson("/api/v1/student/assignments/{$this->assignment->id}/submit", ['content' => 'x']);
    $submission = AssignmentSubmission::first();

    $grade = fn ($s) => $this->actingAs($this->teacher, 'sanctum')
        ->postJson("/api/v1/teacher/submissions/{$submission->id}/grade", ['score' => $s]);

    $grade(7.0);
    $grade(8.0);   // cham lai

    // Chi 1 but toan cho submission nay (idempotent theo ref_id).
    expect(PointLedger::where('reason', 'assignment_graded')->where('ref_id', $submission->id)->count())
        ->toBe(1);
});

it('DoD T1: giao vien chi cham bai lop minh', function () {
    $this->actingAs($this->studentUser, 'sanctum')
        ->postJson("/api/v1/student/assignments/{$this->assignment->id}/submit", ['content' => 'x']);
    $submission = AssignmentSubmission::first();

    // teacher2 (khong phai chu lop) cham -> 403.
    $teacher2 = User::where('email', 'teacher2@hoctoan.test')->first();
    $this->actingAs($teacher2, 'sanctum')
        ->postJson("/api/v1/teacher/submissions/{$submission->id}/grade", ['score' => 5])
        ->assertStatus(403);
});

it('T1: gradebook chi teacher chu lop xem duoc', function () {
    $this->actingAs($this->teacher, 'sanctum')
        ->getJson("/api/v1/teacher/classes/{$this->class->id}/gradebook")
        ->assertOk();

    $teacher2 = User::where('email', 'teacher2@hoctoan.test')->first();
    $this->actingAs($teacher2, 'sanctum')
        ->getJson("/api/v1/teacher/classes/{$this->class->id}/gradebook")
        ->assertStatus(403);
});
