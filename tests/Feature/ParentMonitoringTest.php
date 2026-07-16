<?php

use App\Jobs\CloseAttendanceSessionJob;
use App\Jobs\SendParentNotificationJob;
use App\Models\AttendanceSession;
use App\Models\ParentNotification;
use App\Models\Student;
use App\Models\User;

/*
 * Ticket M3 — flow vang 4 buoc (late -> absent_pending -> absent -> notify).
 * Ticket M4 — Parent dashboard 6 khoi + parent A khong xem con parent B.
 */

beforeEach(function () {
    $this->seed();
});

function studentWithLesson(): array
{
    $user = User::where('email', 'student1@hoctoan.test')->first();
    $student = $user->student;
    $lesson = $student->activeCurriculum->lessons->first();

    return [$user, $student, $lesson];
}

it('M3: flow vang chuyen trang thai theo moc thoi gian', function () {
    [, $student, $lesson] = studentWithLesson();
    $late = config('hoctoan.attendance.late_after_min');

    // Buoi len lich cach day (late + 5) phut, chua vao -> phai thanh `late`.
    $session = AttendanceSession::create([
        'student_id' => $student->id,
        'lesson_id' => $lesson->id,
        'scheduled_start_time' => now()->subMinutes($late + 5),
        'actual_start_time' => null,
        'attendance_status' => 'absent_pending',
    ]);

    app(CloseAttendanceSessionJob::class)->handle(app(\App\Services\AttendanceService::class));

    expect($session->fresh()->attendance_status)->toBe('late');
});

it('DoD M3: het khung gio khong vao -> chot absent + thong bao phu huynh', function () {
    [, $student, $lesson] = studentWithLesson();
    $pending = config('hoctoan.attendance.absent_pending_after_min');

    $session = AttendanceSession::create([
        'student_id' => $student->id,
        'lesson_id' => $lesson->id,
        'scheduled_start_time' => now()->subMinutes($pending * 2 + 10),   // qua het khung gio
        'actual_start_time' => null,
        'attendance_status' => 'absent_pending',
    ]);

    $before = ParentNotification::where('student_id', $student->id)->count();

    // Chay job (chuoi dispatch se chay sync trong test).
    app(CloseAttendanceSessionJob::class)->handle(app(\App\Services\AttendanceService::class));

    expect($session->fresh()->attendance_status)->toBe('absent');

    // student1 co parent1 link -> co thong bao vang.
    expect(ParentNotification::where('student_id', $student->id)
        ->where('notification_type', 'absent')->count())->toBeGreaterThan($before);
});

it('M3: SendParentNotificationJob gui toi tat ca phu huynh da link', function () {
    [, $student] = studentWithLesson();

    app(SendParentNotificationJob::class, [
        'studentId' => $student->id,
        'type' => 'absent',
        'title' => 'Test',
        'content' => 'Test content',
    ])->handle();

    // student1 -> parent1 (1 phu huynh link).
    expect(ParentNotification::where('student_id', $student->id)->where('title', 'Test')->count())
        ->toBe($student->parents->count());
});

it('M4: parent dashboard hien du 6 khoi', function () {
    $parentUser = User::where('email', 'parent1@hoctoan.test')->first();

    $this->actingAs($parentUser)->get('/parent')
        ->assertOk()
        ->assertSee('Lịch học hôm nay')
        ->assertSee('Trạng thái tham gia')
        ->assertSee('Thời gian học thật')
        ->assertSee('Kết quả buổi học')
        ->assertSee('Cảnh báo')
        ->assertSee('Gợi ý can thiệp');
});

it('M4: khoi dau la den tin hieu risk (chip mau + chu)', function () {
    $parentUser = User::where('email', 'parent1@hoctoan.test')->first();

    // student1 co risk seed = 35 -> can_theo_doi (vang).
    $this->actingAs($parentUser)->get('/parent')
        ->assertOk()
        ->assertSee('Cần theo dõi');
});

it('DoD M4: parent A KHONG xem duoc con cua parent B', function () {
    // parent2 link student2,3. parent1 link student1.
    $parent1 = User::where('email', 'parent1@hoctoan.test')->first();

    // parent1 vao dashboard -> chi thay con cua minh (student1), khong co student2.
    $html = $this->actingAs($parent1)->get('/parent')->getContent();

    $student2 = Student::whereHas('user', fn ($q) => $q->where('email', 'student2@hoctoan.test'))->first();

    // Ep xem con cua parent2 qua query param -> bi BO QUA, ve con cua chinh minh (student1).
    $html = $this->actingAs($parent1)->get('/parent?child='.$student2->id)
        ->assertOk()->getContent();

    $student1 = Student::whereHas('user', fn ($q) => $q->where('email', 'student1@hoctoan.test'))->first();

    // Van hien con cua minh, khong phai student2.
    expect($html)->toContain($student1->full_name)
        ->and($html)->not->toContain($student2->full_name);
});

it('M4: link con qua invite_code', function () {
    // Tao parent moi chua link ai.
    $user = User::create([
        'name' => 'PH Moi', 'email' => 'phmoi@hoctoan.test', 'password' => 'password', 'role' => 'parent',
    ]);
    \App\Models\ParentAccount::create([
        'user_id' => $user->id, 'full_name' => 'PH Moi', 'phone' => '0900000000',
        'relation_to_student' => 'bo',
    ]);

    $student = Student::whereHas('user', fn ($q) => $q->where('email', 'student5@hoctoan.test'))->first();

    $this->actingAs($user)->post('/parent/link-student', ['invite_code' => $student->invite_code])
        ->assertRedirect();

    expect($user->parentAccount->children()->whereKey($student->id)->exists())->toBeTrue();
});

it('M4: invite_code sai -> bao loi', function () {
    $parentUser = User::where('email', 'parent1@hoctoan.test')->first();

    $this->actingAs($parentUser)->post('/parent/link-student', ['invite_code' => 'SAI999'])
        ->assertSessionHasErrors('invite_code');
});
