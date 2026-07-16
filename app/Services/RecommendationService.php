<?php

namespace App\Services;

use App\Models\Curriculum;
use App\Models\Lesson;
use App\Models\QuizAttempt;
use App\Models\SolverRequest;
use App\Models\Student;

/**
 * Ticket L3 + SPEC §3.3 — goi y buoi hoc, MULTI-SIGNAL, KHONG tuyen tinh.
 *
 * Loi ma tai lieu goc phe binh: "chi nhin diem quiz cuoi buoi" -> gia ca nhan hoa.
 * Cach dung: cham theo NHIEU tin hieu (SPEC §3.3):
 *   quiz gan nhat, so lan xin hint, sai-roi-sua-dung, thoi gian lam, loi lap theo topic,
 *   do quen (spaced repetition), do on dinh 3-5 buoi.
 *
 * Moi buoi tra loi DONG THOI 3 cau:
 *   new_content (60%) · review_content (20% phan de quen) · reinforce_content (20% loi gan day)
 * Ty le 20/60/20 la MAC DINH; diem thap -> dieu chinh nghieng ve on lai (curriculum DONG).
 */
class RecommendationService
{
    public function __construct(private readonly CurriculumService $curricula) {}

    /**
     * Tra goi y buoi hoc hom nay. Neu diem quiz gan nhat < review_min ->
     * chen 1 buoi on vao curriculum (chung minh curriculum khong lap san cung).
     *
     * @return array{new_content: array, review_content: array, reinforce_content: array, priority: string, mix: array}
     */
    public function recommend(Student $student): array
    {
        $curriculum = $student->activeCurriculum;

        if (! $curriculum) {
            return $this->emptyPlan();
        }

        $signals = $this->collectSignals($student);
        $mix = $this->computeMix($signals);

        // Diem thap -> chen buoi on (curriculum dong).
        if ($signals['recent_quiz_score'] !== null
            && $signals['recent_quiz_score'] < config('hoctoan.quiz.review_min')
            && ! empty($signals['weak_topics'])) {
            $this->maybeInsertReviewLesson($curriculum, $signals['weak_topics'][0]);
        }

        return [
            'new_content' => $this->pickNewLessons($curriculum, $mix['new']),
            'review_content' => $this->pickReviewTopics($signals, $mix['review']),
            'reinforce_content' => $this->pickReinforceTopics($signals, $mix['reinforce']),
            'priority' => $mix['priority'],
            'mix' => $mix,
            'signals' => $signals,
        ];
    }

    /**
     * Thu thap tat ca tin hieu (SPEC §3.3). Day la thu phan biet L3 voi
     * "chi nhin diem" — hai hoc sinh cung diem nhung khac hanh vi -> khac goi y.
     */
    private function collectSignals(Student $student): array
    {
        $window = config('hoctoan.recommendation.stability_window_sessions');

        $recentAttempts = QuizAttempt::where('student_id', $student->id)
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->limit($window)
            ->get();

        $recentScore = $recentAttempts->first()?->score;

        // Do on dinh: do lech diem giua cac buoi gan day (thap = on dinh).
        $scores = $recentAttempts->pluck('score')->map(fn ($s) => (float) $s);
        $stability = $scores->count() >= 2 ? $this->stdDev($scores->all()) : 0.0;

        // Loi lap theo topic: gop error_analysis cac buoi gan day.
        $errorByTopic = [];
        foreach ($recentAttempts as $attempt) {
            foreach ($attempt->error_analysis['by_topic'] ?? [] as $topic => $stat) {
                $errorByTopic[$topic] = ($errorByTopic[$topic] ?? 0) + ($stat['wrong'] ?? 0);
            }
        }
        arsort($errorByTopic);

        // So lan xin hint gan day (solver) — tin hieu le thuoc / dang co gang.
        $hintCount = SolverRequest::where('student_id', $student->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->sum('hint_count');

        $weakTopics = $student->latestClassification?->weak_topics ?? [];

        return [
            'recent_quiz_score' => $recentScore !== null ? (float) $recentScore : null,
            'avg_recent_score' => $scores->avg(),
            'stability' => round($stability, 2),
            'repeated_error_topics' => array_keys($errorByTopic),
            'hint_count_7d' => (int) $hintCount,
            'weak_topics' => $weakTopics,
            'sessions_analyzed' => $recentAttempts->count(),
        ];
    }

    /**
     * Tinh ty le mix. Mac dinh 20/60/20 (config). Diem thap -> nghieng ve on lai;
     * diem cao + on dinh -> nghieng ve bai moi. Tra lai ca `priority`.
     */
    private function computeMix(array $signals): array
    {
        $base = config('hoctoan.session_mix');   // review/new/reinforce
        $score = $signals['recent_quiz_score'];
        $newLessonMin = config('hoctoan.quiz.new_lesson_min');
        $reviewMin = config('hoctoan.quiz.review_min');

        // Chua co diem -> giu mac dinh.
        if ($score === null) {
            return array_merge($base, ['priority' => 'new_first']);
        }

        if ($score < $reviewMin) {
            // Diem thap: uu tien on lai (dao nguoc nghieng ve review/reinforce).
            return ['review' => 40, 'new' => 30, 'reinforce' => 30, 'priority' => 'review_first'];
        }

        if ($score < $newLessonMin) {
            // Trung binh: bai moi + 30% on (SPEC rule nen).
            return ['review' => 30, 'new' => 55, 'reinforce' => 15, 'priority' => 'new_first'];
        }

        // Diem cao: nghieng bai moi, nhung neu KHONG on dinh thi tang cung co.
        if ($signals['stability'] > 1.5) {
            return ['review' => 20, 'new' => 55, 'reinforce' => 25, 'priority' => 'new_first'];
        }

        return array_merge($base, ['priority' => 'new_first']);
    }

    private function pickNewLessons(Curriculum $curriculum, int $ratio): array
    {
        $next = Lesson::query()
            ->whereIn('module_id', $curriculum->modules()->pluck('id'))
            ->whereIn('status', [Lesson::STATUS_UNLOCKED, Lesson::STATUS_IN_PROGRESS])
            ->orderBy('module_id')->orderBy('lesson_order')
            ->limit(2)->get();

        return [
            'ratio' => $ratio,
            'lessons' => $next->map(fn ($l) => ['id' => $l->id, 'title' => $l->title])->all(),
        ];
    }

    private function pickReviewTopics(array $signals, int $ratio): array
    {
        // Phan "de quen": weak_topics chua duoc cung co gan day.
        return ['ratio' => $ratio, 'topics' => array_slice($signals['weak_topics'], 0, 2)];
    }

    private function pickReinforceTopics(array $signals, int $ratio): array
    {
        // Phan "loi gan day": topic sai nhieu nhat trong cac buoi gan nhat.
        return ['ratio' => $ratio, 'topics' => array_slice($signals['repeated_error_topics'], 0, 2)];
    }

    private function maybeInsertReviewLesson(Curriculum $curriculum, string $topic): void
    {
        // Khong chen trung: neu da co buoi on unlocked cho topic nay thi thoi.
        $exists = Lesson::query()
            ->whereIn('module_id', $curriculum->modules()->where('topic', $topic)->pluck('id'))
            ->where('status', Lesson::STATUS_UNLOCKED)
            ->where('title', 'like', 'Ôn tập%')
            ->exists();

        if (! $exists) {
            $this->curricula->insertReviewLesson(
                $curriculum,
                $topic,
                'Ôn tập '.str_replace('_', ' ', $topic),
                'Buổi ôn chèn thêm do kết quả quiz gần đây thấp.',
            );
        }
    }

    private function stdDev(array $values): float
    {
        $n = count($values);
        if ($n < 2) {
            return 0.0;
        }

        $mean = array_sum($values) / $n;
        $variance = array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $values)) / $n;

        return sqrt($variance);
    }

    private function emptyPlan(): array
    {
        return [
            'new_content' => ['ratio' => 60, 'lessons' => []],
            'review_content' => ['ratio' => 20, 'topics' => []],
            'reinforce_content' => ['ratio' => 20, 'topics' => []],
            'priority' => 'new_first',
            'mix' => config('hoctoan.session_mix'),
            'signals' => [],
        ];
    }
}
