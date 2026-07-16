<?php

namespace App\Support;

use App\Models\Student;
use App\Models\User;

/**
 * Ticket A1 — dieu huong sau dang nhap theo vai tro.
 *
 * student -> /onboarding neu chua onboard, /dashboard neu roi
 * parent  -> /parent
 * teacher -> /teacher/classes
 * admin|staff -> /admin
 *
 * Tach rieng vi ca LoginController lan RegisterController deu dung; de o 1 cho
 * thi doi luong chi phai sua 1 lan.
 */
final class RoleRedirect
{
    public static function for(User $user): string
    {
        return match ($user->role) {
            User::ROLE_STUDENT => self::forStudent($user),
            User::ROLE_PARENT => route('parent.dashboard'),
            User::ROLE_TEACHER => route('teacher.classes'),
            User::ROLE_ADMIN, User::ROLE_STAFF => route('admin.home'),
            default => '/',
        };
    }

    private static function forStudent(User $user): string
    {
        $student = $user->student;

        // Chua co ho so, hoac moi dung o `registered` -> phai onboarding truoc.
        if (! $student || $student->status === Student::STATUS_REGISTERED) {
            return route('onboarding');
        }

        // Da onboard nhung chua lam bai danh gia -> day sang assessment.
        // Trung voi middleware EnsureStudentAssessed (ticket A3).
        if (! $student->hasReachedStatus(Student::STATUS_ASSESSED)) {
            return route('assessment');
        }

        return route('dashboard');
    }
}
