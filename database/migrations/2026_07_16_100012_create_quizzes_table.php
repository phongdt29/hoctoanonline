<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SPEC §2.5 — quiz 15' cuoi buoi. Moi lesson dung 1 quiz (unique lesson_id).
//
// LUU Y: 15 o day la default cua SCHEMA (dung nguyen van SPEC §2.5), khong phai
// nguon su that nghiep vu. Migration phai tat dinh — dung config() lam default se
// khien migrate:fresh tren 2 may ra 2 schema khac nhau khi config doi.
// CurriculumService PHAI set duration_minutes = config('hoctoan.quiz.duration_minutes')
// khi tao quiz (CLAUDE.md quy tac #1).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->unique()->constrained('lessons')->cascadeOnDelete();
            $table->unsignedTinyInteger('duration_minutes')->default(15);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
