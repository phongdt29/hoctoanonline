<?php

namespace App\Services;

use App\Models\AiLog;
use App\Models\Exercise;
use App\Models\Lesson;
use Illuminate\Support\Facades\DB;

/**
 * Soan de — AI ho tro tao bai tap cho lesson (SPEC §3.8, cung pattern CurriculumService).
 *
 * Hai luong:
 *   - generate(): sinh de tu chu de/lop/do kho.
 *   - ocr():      nhan dien de tu anh (Gemini vision).
 * Ca hai tao Exercise that vao lesson roi tra ve so cau — admin review/sua/xoa trong editor.
 *
 * Cong thuc toan yeu cau AI tra ve dang LaTeX giua $...$ de MathJax render.
 */
class AuthoringService
{
    public function __construct(private readonly AiProviderService $ai) {}

    /** Schema sinh de: mang exercises, moi cau co difficulty + content + answer (deu la string). */
    private const GEN_SCHEMA = [
        'type' => 'object',
        'properties' => [
            'exercises' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'difficulty' => ['type' => 'string', 'enum' => ['easy', 'medium', 'hard']],
                        'content'    => ['type' => 'string', 'description' => 'Đề bài, công thức đặt trong $...$'],
                        'answer'     => ['type' => 'string', 'description' => 'Đáp án/lời giải ngắn'],
                    ],
                    'required' => ['difficulty', 'content', 'answer'],
                ],
            ],
        ],
        'required' => ['exercises'],
    ];

    /** Schema OCR: chi can content (dap an co the rong vi de giay khong phai luc nao cung co). */
    private const OCR_SCHEMA = [
        'type' => 'object',
        'properties' => [
            'exercises' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'content' => ['type' => 'string', 'description' => 'Đề bài nhận diện, công thức LaTeX $...$'],
                        'answer'  => ['type' => 'string', 'description' => 'Đáp án nếu ảnh có, không thì để trống', 'nullable' => true],
                    ],
                    'required' => ['content'],
                ],
            ],
        ],
        'required' => ['exercises'],
    ];

    /**
     * Sinh de bang AI. Tra ve so cau da tao.
     *
     * @param  array{topic:string,grade:int,difficulty:string,count:int}  $params
     */
    public function generate(Lesson $lesson, array $params): int
    {
        $result = $this->ai->chat(
            AiLog::FEATURE_AUTHORING,
            $this->buildGeneratePrompt($params),
            self::GEN_SCHEMA,
        );

        return $this->createExercises($lesson, $result['exercises'] ?? [], $params['difficulty']);
    }

    /**
     * Nhan dien de tu anh (base64 + mime). Tra ve so cau da tao.
     *
     * @param  array{data:string,mime:string}  $image
     */
    public function ocr(Lesson $lesson, array $image): int
    {
        $prompt = <<<'TXT'
            Đây là ảnh chứa đề toán (có thể nhiều câu). Hãy:
            - Nhận diện từng câu hỏi/bài tập riêng biệt.
            - Chuyển mọi công thức toán sang LaTeX, đặt giữa $...$ (hoặc $$...$$ nếu là công thức riêng dòng).
            - Giữ nguyên tiếng Việt, sửa lỗi OCR hiển nhiên.
            - Nếu ảnh có sẵn đáp án thì điền vào answer, không có thì để trống.
            Trả về JSON đúng schema.
            TXT;

        $result = $this->ai->vision(AiLog::FEATURE_AUTHORING, $prompt, $image, self::OCR_SCHEMA);

        // De tu anh khong co do kho -> mac dinh medium.
        return $this->createExercises($lesson, $result['exercises'] ?? [], Exercise::DIFFICULTY_MEDIUM);
    }

    /**
     * Sinh cac cau TUONG TU mot cau da co — nhanh nhat de dung bo de bien the.
     * Giu nguyen dang toan, chi doi so lieu/ngu canh.
     */
    public function similar(Lesson $lesson, string $source, int $count, string $difficulty): int
    {
        $prompt = <<<TXT
            Đây là một bài toán mẫu:
            "{$source}"

            Hãy soạn {$count} bài tập TƯƠNG TỰ: giữ nguyên dạng toán và cách hỏi,
            chỉ thay số liệu / ngữ cảnh để học sinh luyện thêm.

            Yêu cầu:
            - Mỗi bài gồm đề bài (content) và đáp án/lời giải ngắn (answer).
            - Công thức viết bằng LaTeX giữa \$...\$ (vd \$x^2+1\$).
            - difficulty = "{$difficulty}" cho tất cả các câu.
            - Nội dung tiếng Việt, không lặp lại y hệt bài mẫu.
            - TỰ GIẢI lại mỗi bài để chắc chắn "answer" khớp đúng với đề.

            Trả về JSON đúng schema (mảng exercises).
            TXT;

        $result = $this->ai->chat(AiLog::FEATURE_AUTHORING, $prompt, self::GEN_SCHEMA);

        return $this->createExercises($lesson, $result['exercises'] ?? [], $difficulty);
    }

    /**
     * Tao cac Exercise. answer luu dang ['value' => ...] dong bo voi CurriculumService.
     *
     * @param  array<int,array<string,mixed>>  $items
     */
    public function createExercises(Lesson $lesson, array $items, string $defaultDifficulty): int
    {
        if (empty($items)) {
            return 0;
        }

        return DB::transaction(function () use ($lesson, $items, $defaultDifficulty) {
            $created = 0;

            foreach ($items as $item) {
                $content = trim((string) ($item['content'] ?? ''));
                if ($content === '') {
                    continue;
                }

                $difficulty = $item['difficulty'] ?? $defaultDifficulty;
                if (! in_array($difficulty, [Exercise::DIFFICULTY_EASY, Exercise::DIFFICULTY_MEDIUM, Exercise::DIFFICULTY_HARD], true)) {
                    $difficulty = $defaultDifficulty;
                }

                $lesson->exercises()->create([
                    'difficulty' => $difficulty,
                    'content'    => $content,
                    'answer'     => ['value' => trim((string) ($item['answer'] ?? ''))],
                ]);
                $created++;
            }

            return $created;
        });
    }

    /** @param  array{topic:string,grade:int,difficulty:string,count:int}  $params */
    private function buildGeneratePrompt(array $params): string
    {
        $topic      = $params['topic'];
        $grade       = $params['grade'];
        $count      = $params['count'];
        $difficulty = $params['difficulty'];
        $diffVi = ['easy' => 'dễ', 'medium' => 'trung bình', 'hard' => 'khó'][$difficulty] ?? $difficulty;

        return <<<TXT
            Bạn là giáo viên Toán. Hãy soạn {$count} bài tập cho học sinh lớp {$grade} Việt Nam.
            Chủ đề: {$topic}.
            Mức độ: {$diffVi} (difficulty = "{$difficulty}").

            Yêu cầu:
            - Mỗi bài tập gồm đề bài (content) và đáp án/lời giải ngắn (answer).
            - Mọi công thức toán viết bằng LaTeX, đặt giữa \$...\$ (vd \$x^2+1\$), công thức riêng dòng dùng \$\$...\$\$.
            - Nội dung bằng tiếng Việt, phù hợp chương trình lớp {$grade}.
            - Đa dạng, không lặp lại đề.
            - TỰ GIẢI lại mỗi bài để chắc chắn "answer" khớp đúng với đề (kiểm tra kỹ phép tính trước khi trả lời).

            Trả về JSON đúng schema (mảng exercises).
            TXT;
    }
}
