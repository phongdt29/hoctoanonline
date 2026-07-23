<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateExamJob;
use App\Models\Exam;
use App\Services\ExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * De thi trac nghiem — AI sinh, in de + dap an theo ma de, cham tu dong.
 * Sinh o nen (GenerateExamJob) giong giao trinh.
 */
class ExamController extends Controller
{
    /** Ma de goi y (Goc + vai ma tron). */
    public const CODES = ['goc', '101', '102', '103', '104'];

    public function index(): View
    {
        return view('admin.exams', ['exams' => Exam::latest()->paginate(20)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'          => ['required', 'string', 'max:200'],
            'grade'          => ['required', 'integer', 'min:1', 'max:12'],
            'topics'         => ['nullable', 'string', 'max:255'],
            'difficulty'     => ['required', 'in:easy,medium,hard,mixed'],
            'question_count' => ['required', 'integer', 'min:5', 'max:40'],
        ]);

        $exam = Exam::create([
            'title'          => $data['title'],
            'grade'          => $data['grade'],
            'topics'         => $data['topics'] ?? null,
            'difficulty'     => $data['difficulty'],
            'question_count' => $data['question_count'],
            'status'         => Exam::STATUS_GENERATING,
            'created_by'     => $request->user()->id,
        ]);

        GenerateExamJob::dispatch($exam->id);

        return redirect()->route('admin.exams.show', $exam)
            ->with('status', 'Đang tạo đề trắc nghiệm bằng AI — trang sẽ tự cập nhật khi xong.');
    }

    public function show(Exam $exam): View
    {
        return view('admin.exam', ['e' => $exam, 'codes' => self::CODES, 'result' => session('grade_result')]);
    }

    /** Trang IN: de (chi cau hoi) hoac dap an (key) theo ma de. */
    public function print(Request $request, Exam $exam): View
    {
        abort_unless($exam->isReady(), 404);

        $code = in_array($request->query('code'), self::CODES, true) ? $request->query('code') : 'goc';
        $sheet = $request->query('sheet') === 'key' ? 'key' : 'de';

        return view('admin.exam-print', [
            'e'       => $exam,
            'code'    => $code,
            'sheet'   => $sheet,
            'variant' => ExamService::variant($exam, $code),
        ]);
    }

    /** Cham nhanh: nhap bai lam -> ra diem. */
    public function grade(Request $request, Exam $exam): RedirectResponse
    {
        abort_unless($exam->isReady(), 404);

        $data = $request->validate([
            'code'    => ['required', 'in:'.implode(',', self::CODES)],
            'answers' => ['required', 'string', 'max:200'],
        ]);

        $result = ExamService::grade($exam, $data['code'], $data['answers']);
        $result['code'] = $data['code'];

        return redirect()->route('admin.exams.show', $exam)->with('grade_result', $result);
    }

    public function retry(Exam $exam): RedirectResponse
    {
        $exam->update(['status' => Exam::STATUS_GENERATING, 'error' => null]);
        GenerateExamJob::dispatch($exam->id);

        return redirect()->route('admin.exams.show', $exam)->with('status', 'Đang tạo lại đề…');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $title = $exam->title;
        $exam->delete();

        return redirect()->route('admin.exams')->with('status', "Đã xoá đề \"{$title}\".");
    }
}
