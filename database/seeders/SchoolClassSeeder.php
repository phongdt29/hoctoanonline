<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Ticket F4 — 2 lop cho 2 teacher, moi lop co assignment de test luong nop bai (T2). */
class SchoolClassSeeder extends Seeder
{
    public function run(): void
    {
        $teacher1 = User::where('email', 'teacher1@hoctoan.test')->firstOrFail();
        $teacher2 = User::where('email', 'teacher2@hoctoan.test')->firstOrFail();

        $class7 = SchoolClass::create([
            'teacher_id' => $teacher1->id,
            'name'       => 'Toan 7 - Lop A',
            'grade'      => 7,
        ]);

        $class8 = SchoolClass::create([
            'teacher_id' => $teacher2->id,
            'name'       => 'Toan 8 - Lop B',
            'grade'      => 8,
        ]);

        $class7->students()->attach(Student::where('grade', 7)->pluck('id'));
        $class8->students()->attach(Student::where('grade', 8)->pluck('id'));

        Assignment::create([
            'class_id' => $class7->id,
            'title'    => 'Bai tap phan so - tuan 1',
            'content'  => 'Lam bai 1 den 5 trang 24.',
            'due_at'   => now()->addDays(3),
        ]);

        Assignment::create([
            'class_id' => $class8->id,
            'title'    => 'On tap so nguyen',
            'content'  => 'Hoan thanh phieu bai tap so nguyen.',
            'due_at'   => now()->addDays(5),
        ]);
    }
}
