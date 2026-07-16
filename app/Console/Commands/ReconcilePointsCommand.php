<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Services\PointLedgerService;
use Illuminate\Console\Command;

/**
 * Ticket M3 (SPEC §4) — doi soat points_balance vs tong point_ledger.
 * Chay hang ngay; sua lech neu co (ledger la nguon su that).
 */
class ReconcilePointsCommand extends Command
{
    protected $signature = 'hoctoan:reconcile-points';

    protected $description = 'Đối soát points_balance với tổng point_ledger';

    public function handle(PointLedgerService $points): int
    {
        $fixed = 0;

        Student::query()->chunkById(200, function ($students) use ($points, &$fixed) {
            foreach ($students as $student) {
                $before = $student->points_balance;
                $after = $points->reconcile($student);

                if ($before !== $after) {
                    $fixed++;
                    $this->warn("Student {$student->id}: {$before} -> {$after}");
                }
            }
        });

        $this->info("Đối soát xong. Sửa {$fixed} học sinh lệch điểm.");

        return self::SUCCESS;
    }
}
