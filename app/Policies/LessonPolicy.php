<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\User;

/**
 * Ticket L1 — quyen truy cap lesson.
 * Hoc sinh chi vao lesson: (1) cua chinh minh, (2) khong o trang thai locked.
 */
class LessonPolicy
{
    public function view(User $user, Lesson $lesson): bool
    {
        $student = $user->student;

        if (! $student) {
            return false;
        }

        // Lesson thuoc curriculum cua chinh hoc sinh nay?
        $ownsLesson = $lesson->module?->curriculum?->student_id === $student->id;

        return $ownsLesson && $lesson->isAccessible();
    }
}
