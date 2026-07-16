<?php

namespace App\Services;

use App\Models\AttendanceSession;
use App\Models\LearningRiskScore;
use App\Models\QuizAttempt;
use App\Models\Student;

/**
 * Ticket M4 + SPEC §3.6 — Learning Risk Score.
 *
 * risk = .30*absenteeism + .20*incomplete_session + .20*low_engagement
 *      + .15*quiz_decline + .15*missed_recommendation   (trong so tu config)
 * 0-30 on_dinh 🟢 | 31-60 can_theo_doi 🟡 | 61-100 nguy_co_cao 🔴
 *
 * Cac rate 0..100. Cua so 7 ngay cho absenteeism.
 */
class RiskScoreService
{
    private const WINDOW_DAYS = 7;

    public function compute(Student $student): LearningRiskScore
    {
        $components = $this->components($student);
        $weights = config('hoctoan.risk_weights');

        $score = 0.0;
        foreach ($components as $key => $rate) {
            $score += $weights[$key] * $rate;
        }

        $rounded = (int) round(max(0, min(100, $score)));

        return LearningRiskScore::create([
            'student_id' => $student->id,
            'risk_score' => $rounded,
            'level' => LearningRiskScore::levelFor($rounded),
            'components' => $components,
            'computed_at' => now(),
        ]);
    }

    /** @return array<string, float> 5 rate 0..100 */
    private function components(Student $student): array
    {
        $sessions = AttendanceSession::where('student_id', $student->id)
            ->where('scheduled_start_time', '>=', now()->subDays(self::WINDOW_DAYS))
            ->get();

        $total = max($sessions->count(), 1);

        $absent = $sessions->where('attendance_status', AttendanceSession::STATUS_ABSENT)->count();
        $partial = $sessions->where('attendance_status', AttendanceSession::STATUS_PARTIAL)->count();

        // low_engagement: trung binh (1 - completion_rate/100) tren cac buoi co vao.
        $attended = $sessions->whereNotNull('actual_start_time');
        $lowEngagement = $attended->isNotEmpty()
            ? $attended->avg(fn ($s) => 100 - (float) $s->completion_rate)
            : 0.0;

        return [
            'absenteeism' => round($absent / $total * 100, 2),
            'incomplete_session' => round($partial / $total * 100, 2),
            'low_engagement' => round($lowEngagement, 2),
            'quiz_decline' => $this->quizDeclineRate($student),
            'missed_recommendation' => $this->missedRecommendationRate($sessions, $total),
        ];
    }

    /** Xu huong diem quiz giam: % dua tren so buoi giam lien tiep gan day. */
    private function quizDeclineRate(Student $student): float
    {
        $scores = QuizAttempt::where('student_id', $student->id)
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->limit(5)
            ->pluck('score')
            ->map(fn ($s) => (float) $s)
            ->values();

        if ($scores->count() < 2) {
            return 0.0;
        }

        // Dem so lan giam giua cac buoi lien tiep (tu moi -> cu).
        $declines = 0;
        for ($i = 0; $i < $scores->count() - 1; $i++) {
            if ($scores[$i] < $scores[$i + 1]) {
                $declines++;
            }
        }

        return round($declines / ($scores->count() - 1) * 100, 2);
    }

    /** Ty le buoi bo quiz (partial vi bo quiz cung tinh vao day). */
    private function missedRecommendationRate(mixed $sessions, int $total): float
    {
        // Buoi khong dat completion tối thiểu -> coi nhu bo qua goi y.
        $missed = $sessions->filter(fn ($s) => (float) $s->completion_rate < 50)->count();

        return round($missed / $total * 100, 2);
    }
}
