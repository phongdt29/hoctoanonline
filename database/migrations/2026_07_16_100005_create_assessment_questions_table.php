<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SPEC §2.3 — cau hoi cua bai danh gia dau vao.
// time_spent_seconds la INPUT BAT BUOC cua tang 2 phan loai (SPEC §3.1).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('assessments')->cascadeOnDelete();
            $table->unsignedTinyInteger('question_order');
            $table->enum('type', ['multiple_choice', 'essay']);
            $table->string('topic', 80)->index();
            $table->enum('difficulty', ['easy', 'medium', 'hard']);
            $table->text('content');
            $table->json('options')->nullable();
            $table->json('correct_answer');
            $table->json('student_answer')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->integer('time_spent_seconds')->default(0);   // BAT BUOC track (SPEC §2.3)

            $table->unique(['assessment_id', 'question_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_questions');
    }
};
