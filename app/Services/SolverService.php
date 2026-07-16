<?php

namespace App\Services;

use App\Models\AiLog;
use App\Models\SolverRequest;
use App\Models\Student;
use App\Services\Ai\AiException;

/**
 * Ticket I2 + SPEC §3.4 + CLAUDE.md #6 — Solver, CHONG LE THUOC DAP AN.
 *
 * THU TU BAT BUOC (khong bao gio lo dap an o lan goi dau):
 *   1. hint mo    — goi y huong lam, KHONG ra dap an.
 *   2. more-hint  — goi y sau hon, toi da config.max_hints lan.
 *   3. full-solution — chi khi hoc sinh CHU DONG bam "xem loi giai".
 *
 * Flag answer_dependency khi ty le reveal-khong-thu-lam cao (theo doi xem hoc sinh
 * co dang hoc hay chi xin dap an).
 */
class SolverService
{
    public function __construct(private readonly AiProviderService $ai) {}

    /** Buoc 1: tao request + hint mo dau. KHONG lo dap an. */
    public function startText(Student $student, string $problem): array
    {
        $request = SolverRequest::create([
            'student_id' => $student->id,
            'input_type' => SolverRequest::INPUT_TEXT,
            'problem_text' => $problem,
            'hint_count' => 0,
            'solution_revealed' => false,
        ]);

        $hint = $this->generateHint($request, level: 1);

        return ['request' => $request->fresh(), 'hint' => $hint];
    }

    /** Buoc 2: hint sau hon. Tu choi khi da het luot (max_hints). */
    public function moreHint(SolverRequest $request): array
    {
        if (! $request->canRequestMoreHint()) {
            throw new AiException('Đã hết lượt gợi ý. Bạn thử tự làm, hoặc xem lời giải đầy đủ nhé.');
        }

        $request->increment('hint_count');
        $hint = $this->generateHint($request->fresh(), level: $request->hint_count + 1);

        return ['request' => $request->fresh(), 'hint' => $hint];
    }

    /** Buoc 3: full loi giai — CHI khi hoc sinh chu dong yeu cau. */
    public function fullSolution(SolverRequest $request): array
    {
        $prompt = <<<PROMPT
        Giải bài toán sau theo từng bước rõ ràng, dễ hiểu cho học sinh:

        {$request->problem_text}

        Trình bày step-by-step, mỗi bước một dòng, kết luận đáp án cuối cùng.
        PROMPT;

        $solution = $this->ai->text(AiLog::FEATURE_SOLVER, $prompt, $request->student_id);

        $request->update(['solution_revealed' => true]);

        return ['request' => $request->fresh(), 'solution' => $solution];
    }

    /** Bai tuong tu de luyen them (SPEC §2 module 7). */
    public function similar(SolverRequest $request): string
    {
        $prompt = <<<PROMPT
        Tạo MỘT bài toán tương tự (cùng dạng, khác số) với bài sau, kèm đáp án ở cuối:

        {$request->problem_text}
        PROMPT;

        return $this->ai->text(AiLog::FEATURE_SOLVER, $prompt, $request->student_id);
    }

    /**
     * Phat hien le thuoc dap an: ty le request bam full-solution ma KHONG xin hint nao
     * (nhay vao dap an luon) trong 7 ngay gan day.
     */
    public function answerDependencyRate(Student $student): float
    {
        $recent = SolverRequest::where('student_id', $student->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->get();

        if ($recent->isEmpty()) {
            return 0.0;
        }

        $jumpedToAnswer = $recent
            ->where('solution_revealed', true)
            ->where('hint_count', 0)   // xem loi giai ma khong thu hint nao
            ->count();

        return round($jumpedToAnswer / $recent->count(), 2);
    }

    /** Sinh hint. level cang cao goi y cang cu the — nhung KHONG BAO GIO ra dap an cuoi. */
    private function generateHint(SolverRequest $request, int $level): string
    {
        $depth = $level === 1
            ? 'một gợi ý MỞ về hướng suy nghĩ, chỉ ra nên bắt đầu từ đâu'
            : 'một gợi ý CỤ THỂ HƠN về bước tiếp theo';

        $prompt = <<<PROMPT
        Học sinh đang làm bài toán sau và cần gợi ý (KHÔNG phải lời giải):

        {$request->problem_text}

        Hãy cho {$depth}.
        TUYỆT ĐỐI KHÔNG đưa ra đáp án cuối cùng. Chỉ gợi mở để học sinh tự làm.
        Trả lời ngắn gọn, thân thiện, 1-2 câu.
        PROMPT;

        return $this->ai->text(AiLog::FEATURE_SOLVER, $prompt, $request->student_id);
    }
}
