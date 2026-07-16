<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SPEC §2.6 — hanh vi trong buoi hoc. Nguon tinh effective_study_time.
//
// BANG NAY GHI RAT NHIEU (vai chuc den vai tram event/hoc sinh/buoi).
// MOI QUERY PHAI KEM DIEU KIEN THOI GIAN. Index [session_id, event_time] la bat buoc.
// Khi du lieu lon -> partition theo thang (roadmap R4).
//
// MariaDB 10.4: `json` = alias cua LONGTEXT, khong index/CHECK duoc ben trong.
// Neu can loc theo field trong metadata -> tach ra cot rieng, dung dua vao JSON functions.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')
                ->constrained('student_attendance_sessions')
                ->cascadeOnDelete();

            $table->enum('event_type', [
                'lesson_open', 'section_view', 'exercise_start', 'answer_submit',
                'hint_request', 'chat_message', 'tab_inactive', 'quiz_submit',
            ]);
            $table->dateTime('event_time', 3);        // precision ms
            $table->json('metadata')->nullable();

            $table->index(['session_id', 'event_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_activity_logs');
    }
};
