<?php

namespace App\Http\Controllers\ParentPortal;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\ParentDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Ticket M4 — Parent dashboard 6 khoi + link con qua invite_code. */
class DashboardController extends Controller
{
    public function __construct(private readonly ParentDashboardService $dashboard) {}

    public function show(Request $request): View
    {
        $parent = $request->user()->parentAccount;
        $children = $parent?->children ?? collect();

        // Chon con dang xem. Neu param `child` khong thuoc danh sach con da link
        // (parent A thu xem con parent B) -> BO QUA param, ve con dau. Khong cho
        // param la lam vo view hay lo du lieu con nguoi khac.
        $requested = $request->integer('child');
        $selected = ($requested ? $children->firstWhere('id', $requested) : null)
            ?? $children->first();

        return view('parent.dashboard', [
            'parent' => $parent,
            'children' => $children,
            'selected' => $selected,
            'data' => $selected ? $this->dashboard->forChild($selected) : null,
        ]);
    }

    /** POST /parent/link-student — link con qua invite_code (SPEC §5). */
    public function linkStudent(Request $request)
    {
        $parent = $request->user()->parentAccount;

        $data = $request->validate([
            'invite_code' => ['required', 'string'],
        ]);

        $student = Student::where('invite_code', $data['invite_code'])->first();

        if (! $student) {
            return back()->withErrors(['invite_code' => 'Mã mời không đúng. Kiểm tra lại với con nhé.']);
        }

        // Da link roi thi thoi (khong trung).
        if (! $parent->children()->whereKey($student->id)->exists()) {
            $parent->children()->attach($student->id, ['linked_via' => 'invite_code']);
        }

        return redirect()->route('parent.dashboard', ['child' => $student->id])
            ->with('status', 'Đã liên kết với con thành công.');
    }
}
