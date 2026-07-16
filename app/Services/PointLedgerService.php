<?php

namespace App\Services;

use App\Models\PointLedger;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

/**
 * SPEC §3 + CLAUDE.md #5 — ghi diem thuong. Cong DUY NHAT de ghi point_ledger.
 *
 * Append-only: chi INSERT. Idempotent theo (student, reason, ref_id) de khong
 * ghi diem trung (DoD L2: diem vao ledger dung 1 lan, khong double).
 * points_balance cua student duoc cong don dong bo trong cung transaction.
 */
class PointLedgerService
{
    /**
     * Ghi 1 but toan. Neu (student, reason, ref_id) da ton tai -> khong ghi lai,
     * tra ban ghi cu (chong double khi retry/submit 2 lan).
     */
    public function record(Student $student, int $amount, string $reason, ?int $refId = null): PointLedger
    {
        return DB::transaction(function () use ($student, $amount, $reason, $refId) {
            if ($refId !== null) {
                $existing = PointLedger::where('student_id', $student->id)
                    ->where('reason', $reason)
                    ->where('ref_id', $refId)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            $entry = PointLedger::create([
                'student_id' => $student->id,
                'amount' => $amount,
                'reason' => $reason,
                'ref_id' => $refId,
            ]);

            // Dong bo points_balance = tong ledger (nguon su that la ledger).
            $student->increment('points_balance', $amount);

            return $entry;
        });
    }

    /** Tinh lai points_balance tu ledger — dung cho command doi soat (SPEC §4). */
    public function reconcile(Student $student): int
    {
        $sum = (int) PointLedger::where('student_id', $student->id)->sum('amount');
        $student->update(['points_balance' => $sum]);

        return $sum;
    }
}
