<?php

use App\Jobs\SendParentNotificationJob;
use App\Jobs\SendWeeklyReportJob;
use App\Mail\ParentNotificationMail;
use App\Models\ParentNotification;
use App\Models\Student;
use App\Models\User;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Support\Facades\Mail;

/*
 * Ticket R1 — notification da kenh: in_app + email, weekly report.
 */

beforeEach(function () {
    Mail::fake();
    $this->seed();
    // student1 -> parent1 da link.
    $this->student = User::where('email', 'student1@hoctoan.test')->first()->student;
});

it('R1: dispatcher gui in_app -> ghi parent_notifications channel in_app', function () {
    $parent = $this->student->parents->first();

    app(NotificationDispatcher::class)->send(
        $parent, $this->student->id, 'absent', 'Test', 'Noi dung', ['in_app'],
    );

    expect(ParentNotification::where('channel', 'in_app')->where('title', 'Test')->exists())->toBeTrue();
    Mail::assertNothingQueued();
});

it('R1: dispatcher gui email -> gui mail + ghi channel email', function () {
    $parent = $this->student->parents->first();

    app(NotificationDispatcher::class)->send(
        $parent, $this->student->id, 'absent', 'Test Email', 'Noi dung', ['email'],
    );

    expect(ParentNotification::where('channel', 'email')->where('title', 'Test Email')->exists())->toBeTrue();
    Mail::assertQueued(ParentNotificationMail::class);
});

it('R1: gui nhieu kenh cung luc (in_app + email)', function () {
    $parent = $this->student->parents->first();

    app(NotificationDispatcher::class)->send(
        $parent, $this->student->id, 'alert_high', 'Canh bao', 'Noi dung', ['in_app', 'email'],
    );

    expect(ParentNotification::where('title', 'Canh bao')->count())->toBe(2);   // 2 ban ghi (2 kenh)
    Mail::assertQueued(ParentNotificationMail::class, 1);
});

it('R1: kenh khong ho tro -> nem loi', function () {
    $parent = $this->student->parents->first();

    expect(fn () => app(NotificationDispatcher::class)->send(
        $parent, $this->student->id, 'x', 'T', 'C', ['sms'],   // chua implement sms
    ))->toThrow(InvalidArgumentException::class);
});

it('R1: SendParentNotificationJob dung dispatcher, gui toi moi phu huynh', function () {
    app(SendParentNotificationJob::class, [
        'studentId' => $this->student->id,
        'type' => 'absent',
        'title' => 'Vang hoc',
        'content' => 'Con vang',
        'channels' => ['in_app'],
    ])->handle(app(NotificationDispatcher::class));

    expect(ParentNotification::where('student_id', $this->student->id)->where('title', 'Vang hoc')->count())
        ->toBe($this->student->parents->count());
});

it('DoD R1: weekly report gui in_app + email voi tom tat tuan', function () {
    app(SendWeeklyReportJob::class, ['studentId' => $this->student->id])
        ->handle(app(NotificationDispatcher::class));

    $report = ParentNotification::where('student_id', $this->student->id)
        ->where('notification_type', 'weekly_report')->get();

    // 1 phu huynh x 2 kenh = 2 ban ghi.
    expect($report->count())->toBe($this->student->parents->count() * 2)
        ->and($report->first()->content)->toContain('Tuần qua');

    Mail::assertQueued(ParentNotificationMail::class);
});
