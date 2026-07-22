<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Services\AuthoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Soan bai / soan de — admin sua noi dung lesson + bai tap, ho tro cong thuc toan.
 *
 * 3 cach soan de:
 *   - Thu cong: them/sua/xoa tung cau + dap an trong form (MathLive).
 *   - AI sinh de: aiGenerate() -> AuthoringService::generate().
 *   - OCR anh:    ocr() -> AuthoringService::ocr().
 * Ca AI lan OCR deu tao Exercise that roi hien trong editor de review/sua.
 *
 * Cong thuc luu dang LaTeX ($...$); MathJax o layout render khi hien thi.
 */
class LessonController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $lessons = Lesson::query()
            ->with(['module.curriculum.student'])
            ->when($q !== '', fn ($query) => $query->where('title', 'like', "%{$q}%"))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.lessons', ['lessons' => $lessons, 'q' => $q]);
    }

    public function edit(Lesson $lesson): View
    {
        $lesson->load(['exercises' => fn ($q) => $q->orderBy('difficulty')->orderBy('id'), 'module.curriculum.student']);

        return view('admin.lesson-edit', ['lesson' => $lesson]);
    }

    public function update(Request $request, Lesson $lesson): RedirectResponse
    {
        $data = $request->validate([
            'title'                  => ['required', 'string', 'max:200'],
            'theory_content'         => ['required', 'string', 'max:20000'],
            'exercises'              => ['array'],
            'exercises.*.content'    => ['nullable', 'string', 'max:5000'],
            'exercises.*.answer'     => ['nullable', 'string', 'max:5000'],
            'exercises.*.difficulty' => ['nullable', 'in:easy,medium,hard'],
            'exercises.*._delete'    => ['nullable'],
        ]);

        $lesson->update([
            'title'          => $data['title'],
            'theory_content' => $data['theory_content'],
        ]);

        $this->syncExercises($lesson, $data['exercises'] ?? []);

        return redirect()
            ->route('admin.lessons.edit', $lesson)
            ->with('status', 'Đã lưu bài học.');
    }

    /** AI sinh de tu chu de/lop/do kho. */
    public function aiGenerate(Request $request, Lesson $lesson, AuthoringService $authoring): RedirectResponse
    {
        $data = $request->validate([
            'topic'      => ['required', 'string', 'max:200'],
            'grade'      => ['required', 'integer', 'min:1', 'max:12'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
            'count'      => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        try {
            $count = $authoring->generate($lesson, $data);
        } catch (\Throwable $e) {
            return back()->with('error', 'AI tạo đề lỗi: '.mb_substr($e->getMessage(), 0, 180));
        }

        return redirect()
            ->route('admin.lessons.edit', $lesson)
            ->with('status', "AI đã tạo {$count} câu — kiểm tra lại rồi bấm Lưu bài học.");
    }

    /** Nhan dien de tu anh (OCR vision). */
    public function ocr(Request $request, Lesson $lesson, AuthoringService $authoring): RedirectResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        $file = $request->file('image');

        try {
            $count = $authoring->ocr($lesson, [
                'data' => base64_encode(file_get_contents($file->getRealPath())),
                'mime' => $file->getMimeType(),
            ]);
        } catch (\Throwable $e) {
            return back()->with('error', 'Nhận diện ảnh lỗi: '.mb_substr($e->getMessage(), 0, 180));
        }

        return redirect()
            ->route('admin.lessons.edit', $lesson)
            ->with('status', "Đã nhận diện {$count} câu từ ảnh — kiểm tra lại rồi bấm Lưu bài học.");
    }

    /**
     * Nhap nhanh nhieu cau: moi dong 1 cau. Khong dung AI -> tuc thi.
     * Cu phap moi dong:  [de|tb|kho] Noi dung de bai | dap an
     * (tien to do kho va phan "| dap an" deu tuy chon)
     */
    public function bulk(Request $request, Lesson $lesson, AuthoringService $authoring): RedirectResponse
    {
        $data = $request->validate([
            'bulk' => ['required', 'string', 'max:20000'],
        ]);

        $items = $this->parseBulk($data['bulk']);

        if (empty($items)) {
            return back()->with('error', 'Không đọc được câu nào — mỗi dòng một câu.');
        }

        $count = $authoring->createExercises($lesson, $items, Exercise::DIFFICULTY_MEDIUM);

        return redirect()
            ->route('admin.lessons.edit', $lesson)
            ->with('status', "Đã thêm {$count} câu — kiểm tra lại rồi bấm Lưu bài học.");
    }

    /** AI sinh cac cau tuong tu mot cau da co. */
    public function similar(Request $request, Lesson $lesson, AuthoringService $authoring): RedirectResponse
    {
        $data = $request->validate([
            'source'     => ['required', 'string', 'max:5000'],
            'count'      => ['required', 'integer', 'min:1', 'max:10'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
        ]);

        try {
            $count = $authoring->similar($lesson, $data['source'], $data['count'], $data['difficulty']);
        } catch (\Throwable $e) {
            return back()->with('error', 'Tạo câu tương tự lỗi: '.mb_substr($e->getMessage(), 0, 180));
        }

        return redirect()
            ->route('admin.lessons.edit', $lesson)
            ->with('status', "Đã tạo {$count} câu tương tự — kiểm tra lại rồi bấm Lưu bài học.");
    }

    /**
     * Tach van ban nhieu dong thanh danh sach cau.
     *
     * @return array<int,array<string,string>>
     */
    private function parseBulk(string $text): array
    {
        $map = ['de' => Exercise::DIFFICULTY_EASY, 'dễ' => Exercise::DIFFICULTY_EASY,
            'tb' => Exercise::DIFFICULTY_MEDIUM, 'kho' => Exercise::DIFFICULTY_HARD, 'khó' => Exercise::DIFFICULTY_HARD];

        $items = [];

        foreach (preg_split('/\R/u', $text) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $difficulty = Exercise::DIFFICULTY_MEDIUM;

            // Tien to do kho:  [kho] ...
            if (preg_match('/^\[\s*([^\]]+)\s*\]\s*(.*)$/u', $line, $m)) {
                $key = mb_strtolower(trim($m[1]));
                $difficulty = $map[$key] ?? Exercise::DIFFICULTY_MEDIUM;
                $line = trim($m[2]);
            }

            // Bo so thu tu dau dong: "1." / "Câu 3:" -> nguoi dung hay dan kem
            $line = preg_replace('/^(câu\s*)?\d+\s*[.):]\s*/iu', '', $line) ?? $line;

            // Tach dap an sau dau |
            $answer = '';
            if (str_contains($line, '|')) {
                $parts = explode('|', $line);
                $answer = trim(array_pop($parts));
                $line = trim(implode('|', $parts));
            }

            if ($line === '') {
                continue;
            }

            $items[] = ['difficulty' => $difficulty, 'content' => $line, 'answer' => $answer];
        }

        return $items;
    }

    /**
     * Dong bo bai tap tu form: cap nhat cau cu, tao cau moi, xoa cau danh dau.
     * Key so = Exercise da co; key khac (new_*) = cau moi them tren trinh duyet.
     *
     * @param  array<int|string,array<string,mixed>>  $rows
     */
    private function syncExercises(Lesson $lesson, array $rows): void
    {
        foreach ($rows as $key => $row) {
            $isExisting = is_numeric($key);
            $markedDelete = ! empty($row['_delete']);
            $content = trim((string) ($row['content'] ?? ''));

            if ($isExisting && $markedDelete) {
                $lesson->exercises()->whereKey($key)->delete();

                continue;
            }

            if ($content === '') {
                continue;   // bo qua dong rong (vd cau moi chua nhap)
            }

            $difficulty = $row['difficulty'] ?? Exercise::DIFFICULTY_MEDIUM;
            $attrs = [
                'difficulty' => $difficulty,
                'content'    => $content,
                'answer'     => ['value' => trim((string) ($row['answer'] ?? ''))],
            ];

            if ($isExisting) {
                // Load model roi update de cast 'answer' (array->json) duoc ap dung.
                $exercise = $lesson->exercises()->whereKey($key)->first();
                $exercise?->update($attrs);
            } else {
                $lesson->exercises()->create($attrs);
            }
        }
    }
}
