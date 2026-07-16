<?php

namespace App\Services;

use App\Models\PointLedger;
use App\Models\QuizAttempt;
use App\Models\Student;
use App\Models\StudentBadge;
use Illuminate\Support\Facades\DB;

/**
 * Ticket R2 — gamification: badge, streak, leaderboard. Dua tren point_ledger + quiz.
 */
class GamificationService
{
    /** Dinh nghia huy hieu: code => [ten, dieu kien]. */
    public const BADGES = [
        'first_quiz' => 'Bài quiz đầu tiên',
        'perfect_quiz' => 'Quiz điểm tuyệt đối',
        'streak_7' => 'Học 7 ngày liên tục',
        'points_100' => 'Đạt 100 điểm',
        'points_500' => 'Đạt 500 điểm',
    ];

    /**
     * Kiem tra & trao huy hieu moi cho hoc sinh (goi sau moi su kien: nop quiz, ...).
     * Idempotent: unique [student, code] chan trao trung.
     *
     * @return string[] cac huy hieu VUA dat (de hien toast).
     */
    public function checkBadges(Student $student): array
    {
        $earned = [];
        $has = StudentBadge::where('student_id', $student->id)->pluck('code')->all();

        $quizCount = QuizAttempt::where('student_id', $student->id)->whereNotNull('submitted_at')->count();
        $hasPerfect = QuizAttempt::where('student_id', $student->id)->where('score', 10)->exists();
        $points = $student->points_balance;

        $checks = [
            'first_quiz' => $quizCount >= 1,
            'perfect_quiz' => $hasPerfect,
            'streak_7' => $student->streak_days >= 7,
            'points_100' => $points >= 100,
            'points_500' => $points >= 500,
        ];

        foreach ($checks as $code => $qualifies) {
            if ($qualifies && ! in_array($code, $has, true)) {
                StudentBadge::create([
                    'student_id' => $student->id,
                    'code' => $code,
                    'earned_at' => now(),
                ]);
                $earned[] = $code;
            }
        }

        return $earned;
    }

    /**
     * Leaderboard theo diem trong khoang thoi gian (mac dinh tuan nay).
     * Dua tren point_ledger (nguon su that), khong dua points_balance (co the lech).
     *
     * @return array<int, array{rank:int, student_id:int, name:string, points:int}>
     */
    public function leaderboard(int $limit = 10, ?int $sinceDays = 7): array
    {
        $query = PointLedger::query()
            ->selectRaw('student_id, SUM(amount) as total')
            ->groupBy('student_id')
            ->orderByDesc('total')
            ->limit($limit);

        if ($sinceDays !== null) {
            $query->where('created_at', '>=', now()->subDays($sinceDays));
        }

        $rows = $query->get();

        // Nap ten 1 lan (tranh N+1).
        $students = Student::whereIn('id', $rows->pluck('student_id'))->get()->keyBy('id');

        return $rows->values()->map(fn ($row, $i) => [
            'rank' => $i + 1,
            'student_id' => $row->student_id,
            'name' => $students[$row->student_id]?->full_name ?? '—',
            'points' => (int) $row->total,
        ])->all();
    }

    /**
     * Cap nhat streak khi hoc sinh co hoat dong hoc hom nay.
     * Hoc lien tiep -> +1; nghi 1 ngay -> reset ve 1.
     */
    public function touchStreak(Student $student): int
    {
        $lastActive = QuizAttempt::where('student_id', $student->id)
            ->whereNotNull('submitted_at')
            ->where('submitted_at', '<', today())
            ->latest('submitted_at')
            ->value('submitted_at');

        $streak = $student->streak_days;

        if ($lastActive && $lastActive->isYesterday()) {
            $streak++;
        } elseif (! $lastActive || ! $lastActive->isToday()) {
            $streak = max(1, $streak);   // giu it nhat 1 khi co hoat dong hom nay
        }

        $student->update(['streak_days' => $streak]);

        return $streak;
    }
}
