<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SPEC §2.9 — luong HOC SINH NOP BAI.
//
// Day la "mat xich hong" ma tai lieu phan tich chi ra: giao vien giao bai va cham
// bai duoc (0.85) nhung hoc sinh chua nop duoc (0.45) -> dang cham nhung bai khong
// ai nop duoc. Ticket T2 hoan thien chuoi: giao -> nop -> cham -> point_ledger -> notify parent.
//
// unique [assignment_id, student_id]: nop lan 2 la CAP NHAT ban ghi cu,
// khong tao ban ghi trung (DoD ticket T2).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students');

            $table->text('content')->nullable();
            $table->string('file_url', 500)->nullable();

            // dateTime BAT BUOC o day: neu de `timestamp`, MariaDB gan ON UPDATE ngam
            // -> giao vien cham bai (set score) se lam submitted_at nhay ve gio cham,
            // xoa mat gio nop that va lam sai viec doi chieu due_at.
            $table->dateTime('submitted_at');

            $table->decimal('score', 4, 2)->nullable();
            $table->dateTime('graded_at')->nullable();
            $table->text('feedback')->nullable();

            $table->unique(['assignment_id', 'student_id']);
            $table->index(['student_id', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
    }
};
