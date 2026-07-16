<?php

namespace App\Services;

use App\Jobs\SendParentNotificationJob;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ParentNotification;
use App\Models\PointLedger;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

/**
 * Ticket T1 + T2 — luong bai tap giao vien <-> hoc sinh.
 *
 * T2 la "mat xich 45%": giao vien giao & cham duoc, nhung hoc sinh chua nop duoc.
 * Chuoi day du: giao -> HOC SINH NOP -> cham -> point_ledger -> notify parent.
 */
class AssignmentService
{
    public function __construct(private readonly PointLedgerService $points) {}

    /**
     * Hoc sinh nop bai. Nop lan 2 = CAP NHAT (unique [assignment_id, student_id]),
     * khong tao ban ghi trung (DoD T2).
     */
    public function submit(Assignment $assignment, Student $student, array $data): AssignmentSubmission
    {
        return AssignmentSubmission::updateOrCreate(
            ['assignment_id' => $assignment->id, 'student_id' => $student->id],
            [
                'content' => $data['content'] ?? null,
                'file_url' => $data['file_url'] ?? null,
                'submitted_at' => now(),
                // Nop lai sau khi da cham -> xoa diem cu de cham lai (khong giu diem cu).
                'score' => null,
                'graded_at' => null,
                'feedback' => null,
            ],
        );
    }

    /**
     * Giao vien cham. -> point_ledger (idempotent theo submission) -> notify parent.
     */
    public function grade(AssignmentSubmission $submission, float $score, ?string $feedback): AssignmentSubmission
    {
        return DB::transaction(function () use ($submission, $score, $feedback) {
            $submission->update([
                'score' => $score,
                'feedback' => $feedback,
                'graded_at' => now(),
            ]);

            // Ghi diem (idempotent theo ref_id = submission id).
            $this->points->record(
                $submission->student,
                (int) round($score * 2),
                PointLedger::REASON_ASSIGNMENT_GRADED,
                $submission->id,
            );

            // Thong bao phu huynh: con da co diem bai tap.
            SendParentNotificationJob::dispatch(
                $submission->student_id,
                ParentNotification::TYPE_SESSION_DONE,
                'Con có điểm bài tập mới',
                "Con vừa được chấm bài \"{$submission->assignment->title}\": {$score}/10.",
            );

            return $submission->fresh();
        });
    }
}
