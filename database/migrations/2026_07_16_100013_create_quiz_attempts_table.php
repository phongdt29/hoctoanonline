<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SPEC §2.5 + §L2 — luot lam quiz.
// `expires_at` la CHOT CHONG GIAN LAN: server tao luc start, server kiem tra luc
// submit. Client chi hien thi dem nguoc (CLAUDE.md quy tac #7).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes');
            $table->foreignId('student_id')->constrained('students');

            $table->decimal('score', 4, 2)->nullable();
            $table->json('error_analysis')->nullable();
            $table->enum('suggestion', ['hoc_tiep', 'on_lai'])->nullable();

            // dateTime (khong phai timestamp): xem ghi chu trong migration assessments.
            // Rieng bang nay co 2 cot NOT NULL nen loi hien ra ngay (MariaDB 1067).
            $table->dateTime('started_at');
            $table->dateTime('expires_at');            // server-side timer
            $table->dateTime('submitted_at')->nullable();

            $table->index(['student_id', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
