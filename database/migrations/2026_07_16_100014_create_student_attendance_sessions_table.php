<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SPEC §2.6 + §3.5 — phien hoc & diem danh 3 trang thai.
// `late` va `absent_pending` la trang thai TRUNG GIAN cua flow vang mat
// (T+15' -> late, T+30' -> absent_pending, het khung gio -> chot absent).
// effective_study_minutes: chi tinh gap giua 2 event hop le <= idle_gap_minutes.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students');
            $table->foreignId('lesson_id')->constrained('lessons');

            $table->dateTime('scheduled_start_time');
            $table->dateTime('actual_start_time')->nullable();
            $table->dateTime('actual_end_time')->nullable();

            $table->enum('attendance_status', [
                'present', 'partial', 'absent', 'late', 'absent_pending',
            ])->default('absent_pending');

            $table->integer('effective_study_minutes')->default(0);   // hoc THUC
            $table->integer('idle_minutes')->default(0);
            $table->decimal('completion_rate', 5, 2)->default(0);

            // Ten index dat tay: ten tu sinh cua Laravel
            // (student_attendance_sessions_student_id_scheduled_start_time_index)
            // dai 65 ky tu, vuot gioi han 64 cua MySQL/MariaDB.
            $table->index(['student_id', 'scheduled_start_time'], 'sas_student_scheduled_idx');
            $table->index('attendance_status', 'sas_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_attendance_sessions');
    }
};
