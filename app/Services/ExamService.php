<?php

namespace App\Services;

use App\Models\AiLog;
use App\Models\Exam;

/**
 * De thi trac nghiem — AI sinh cau hoi 4 lua chon (SPEC pattern nhu AuthoringService).
 *
 * Ma de: tron thu tu cau + tron lua chon moi cau, DETERMINISTIC theo (exam_id, code)
 * bang PRNG xorshift rieng -> khong dung mt_rand toan cuc, in lai cung ma de luon giong.
 * Cham tu dong: so sanh bai lam voi dap an cua dung ma de.
 */
class ExamService
{
    public function __construct(private readonly AiProviderService $ai) {}

    private const SCHEMA = [
        'type' => 'object',
        'required' => ['questions'],
        'properties' => [
            'questions' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'required' => ['content', 'options', 'correct', 'difficulty'],
                    'properties' => [
                        'content'    => ['type' => 'string', 'description' => 'Câu hỏi, công thức LaTeX $...$'],
                        'options'    => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Đúng 4 lựa chọn'],
                        'correct'    => ['type' => 'integer', 'description' => 'Chỉ số (0-3) của lựa chọn đúng'],
                        'difficulty' => ['type' => 'string', 'enum' => ['easy', 'medium', 'hard']],
                        'topic'      => ['type' => 'string'],
                    ],
                ],
            ],
        ],
    ];

    /** Sinh de bang AI, luu content, danh dau ready. Nem exception neu loi (job retry). */
    public function generate(Exam $exam): void
    {
        $result = $this->ai->chat(AiLog::FEATURE_AUTHORING, $this->buildPrompt($exam), self::SCHEMA);

        $exam->update([
            'content'      => ['questions' => $this->normalize($result['questions'] ?? [])],
            'status'       => Exam::STATUS_READY,
            'error'        => null,
            'generated_at' => now(),
        ]);
    }

    /**
     * Chuan hoa: moi cau dung 4 lua chon, correct trong 0-3.
     *
     * @param  array<int,array<string,mixed>>  $items
     * @return array<int,array<string,mixed>>
     */
    private function normalize(array $items): array
    {
        $out = [];

        foreach ($items as $q) {
            $options = array_values(array_filter(
                array_map(fn ($o) => trim((string) $o), $q['options'] ?? []),
                fn ($o) => $o !== '',
            ));
            if (count($options) < 2 || trim((string) ($q['content'] ?? '')) === '') {
                continue;   // cau hong -> bo
            }
            // Ep dung 4 lua chon: thieu thi bu, thua thi cat.
            while (count($options) < 4) {
                $options[] = '—';
            }
            $options = array_slice($options, 0, 4);

            $correct = (int) ($q['correct'] ?? 0);
            $correct = max(0, min(3, $correct));

            $out[] = [
                'content'    => trim((string) $q['content']),
                'options'    => $options,
                'correct'    => $correct,
                'difficulty' => in_array($q['difficulty'] ?? '', ['easy', 'medium', 'hard'], true) ? $q['difficulty'] : 'medium',
                'topic'      => trim((string) ($q['topic'] ?? '')),
            ];
        }

        return $out;
    }

    /**
     * Ma de: tra ve cau hoi da tron + dap an theo `code`.
     * code rong / 'goc' -> giu nguyen thu tu goc.
     *
     * @return array{questions: array<int,array<string,mixed>>, key: array<int,string>}
     */
    public static function variant(Exam $exam, string $code = ''): array
    {
        $questions = $exam->questions();
        $letters = ['A', 'B', 'C', 'D'];

        if ($code === '' || mb_strtolower($code) === 'goc') {
            $key = array_map(fn ($q) => $letters[$q['correct']] ?? 'A', $questions);

            return ['questions' => $questions, 'key' => $key];
        }

        $seed = (crc32($exam->id.':'.$code) | 1) & 0xFFFFFFFF;
        $order = self::seededShuffle(range(0, count($questions) - 1), $seed);

        $variantQuestions = [];
        $key = [];

        foreach ($order as $pos => $qi) {
            $q = $questions[$qi];
            // Tron lua chon cua rieng cau nay (seed khac cho moi cau).
            $optOrder = self::seededShuffle([0, 1, 2, 3], ($seed + $qi * 2654435761) & 0xFFFFFFFF);
            $newOptions = [];
            $newCorrect = 0;
            foreach ($optOrder as $newIdx => $oldIdx) {
                $newOptions[$newIdx] = $q['options'][$oldIdx] ?? '—';
                if ($oldIdx === $q['correct']) {
                    $newCorrect = $newIdx;
                }
            }
            $variantQuestions[] = ['content' => $q['content'], 'options' => $newOptions,
                'correct' => $newCorrect, 'difficulty' => $q['difficulty'], 'topic' => $q['topic']];
            $key[$pos] = $letters[$newCorrect];
        }

        return ['questions' => $variantQuestions, 'key' => $key];
    }

    /**
     * Cham tu dong: so sanh bai lam voi dap an cua ma de.
     * $answers: chuoi "ABCD..." hoac mang ['A','C',...]. Bo qua khoang trang, khong phan biet hoa/thuong.
     *
     * @param  string|array<int,string>  $answers
     * @return array{score: float, correct_count: int, total: int, detail: array<int,array{given:string,key:string,ok:bool}>}
     */
    public static function grade(Exam $exam, string $code, string|array $answers): array
    {
        $key = self::variant($exam, $code)['key'];
        $total = count($key);

        $given = is_array($answers)
            ? array_map(fn ($a) => strtoupper(trim((string) $a)), $answers)
            : str_split(strtoupper(preg_replace('/[^A-Da-d]/', '', $answers) ?? ''));

        $detail = [];
        $correctCount = 0;

        for ($i = 0; $i < $total; $i++) {
            $g = $given[$i] ?? '';
            $ok = $g !== '' && $g === $key[$i];
            if ($ok) {
                $correctCount++;
            }
            $detail[$i] = ['given' => $g ?: '—', 'key' => $key[$i], 'ok' => $ok];
        }

        $score = $total > 0 ? round($correctCount / $total * 10, 2) : 0;

        return ['score' => $score, 'correct_count' => $correctCount, 'total' => $total, 'detail' => $detail];
    }

    /**
     * Fisher-Yates deterministic bang xorshift32 (khong dung mt_rand toan cuc).
     *
     * @param  array<int,int>  $arr
     * @return array<int,int>
     */
    private static function seededShuffle(array $arr, int $seed): array
    {
        $s = $seed !== 0 ? $seed : 1;
        $next = function () use (&$s): int {
            $s ^= ($s << 13) & 0xFFFFFFFF;
            $s ^= ($s >> 17);
            $s ^= ($s << 5) & 0xFFFFFFFF;
            $s &= 0xFFFFFFFF;

            return $s;
        };

        for ($i = count($arr) - 1; $i > 0; $i--) {
            $j = $next() % ($i + 1);
            [$arr[$i], $arr[$j]] = [$arr[$j], $arr[$i]];
        }

        return $arr;
    }

    private function buildPrompt(Exam $exam): string
    {
        $topics = $exam->topics ? "Chủ đề: {$exam->topics}." : 'Bao quát các chủ đề trọng tâm của lớp.';
        $diff = ['easy' => 'dễ', 'medium' => 'trung bình', 'hard' => 'khó', 'mixed' => 'phân bố đủ dễ/trung bình/khó']
            [$exam->difficulty] ?? 'phân bố đủ mức';

        return <<<PROMPT
        Bạn là giáo viên Toán. Hãy soạn một ĐỀ KIỂM TRA TRẮC NGHIỆM cho học sinh lớp {$exam->grade} Việt Nam.
        {$topics}
        Số câu: {$exam->question_count}. Độ khó: {$diff}.

        Yêu cầu MỖI câu:
        - "content": câu hỏi rõ ràng, công thức toán viết LaTeX trong \$...\$ (vd \$x^2+1\$).
        - "options": ĐÚNG 4 lựa chọn (mảng 4 chuỗi), chỉ MỘT đáp án đúng, các phương án nhiễu hợp lý.
        - "correct": chỉ số 0-3 của lựa chọn đúng.
        - "difficulty": easy | medium | hard.
        - "topic": chủ đề của câu (slug hoặc tiếng Việt ngắn).
        - Nội dung tiếng Việt, bám chương trình lớp {$exam->grade}, không lặp câu.

        QUAN TRỌNG — kiểm tra lại trước khi trả lời:
        - TỰ GIẢI lại từng câu, đảm bảo "correct" trỏ ĐÚNG lựa chọn đúng (lỗi lệch chỉ số là lỗi hay gặp — phải tránh).
        - Các phương án nhiễu phải SAI nhưng hợp lý (nên là các lỗi học sinh hay mắc), không có 2 đáp án cùng đúng.
        - Mỗi câu chỉ có đúng 1 đáp án đúng.

        Trả về JSON đúng schema (mảng questions).
        PROMPT;
    }
}
