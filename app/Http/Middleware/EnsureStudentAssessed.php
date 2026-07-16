<?php

namespace App\Http\Middleware;

use App\Models\Student;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ticket A3 + SPEC §1 — chan vao curriculum khi chua `assessed`.
 *
 * State machine KHONG cho nhay coc:
 *   registered -> onboarding | onboarded -> assessment | tu assessed tro di -> cho qua
 *
 * Ly do nghiep vu: giao trinh sinh tu ket qua phan loai. Vao curriculum khi chua
 * lam bai danh gia thi khong co gi de sinh — day chinh la mat xich ma tai lieu
 * goc canh bao "chi can phan loai sai o buoc dau, toan bo phia sau se lech".
 */
class EnsureStudentAssessed
{
    public function handle(Request $request, Closure $next): Response
    {
        $student = $request->user()?->student;

        if (! $student || $student->status === Student::STATUS_REGISTERED) {
            return redirect()->route('onboarding')
                ->with('status', 'Hoàn thành hồ sơ trước khi bắt đầu học nhé.');
        }

        if (! $student->hasReachedStatus(Student::STATUS_ASSESSED)) {
            return redirect()->route('assessment')
                ->with('status', 'Làm bài kiểm tra đầu vào để A.I xây lộ trình cho bạn.');
        }

        return $next($request);
    }
}
