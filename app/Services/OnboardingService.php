<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Support\Str;

/** Ticket C2 — hoan tat onboarding: dien 12 truong, sinh invite_code, chuyen status. */
class OnboardingService
{
    public function complete(Student $student, array $data): Student
    {
        $student->fill($data);

        // invite_code chi sinh 1 lan (idempotent — onboard lai khong doi code
        // de link parent-child da tao khong hong).
        if (empty($student->invite_code)) {
            $student->invite_code = $this->uniqueInviteCode();
        }

        $student->status = Student::STATUS_ONBOARDED;
        $student->save();

        return $student;
    }

    /** Ma 8 ky tu HT + 6 ky tu, chong trung o tang ung dung + unique constraint DB. */
    private function uniqueInviteCode(): string
    {
        do {
            $code = 'HT'.strtoupper(Str::random(6));
        } while (Student::where('invite_code', $code)->exists());

        return $code;
    }
}
