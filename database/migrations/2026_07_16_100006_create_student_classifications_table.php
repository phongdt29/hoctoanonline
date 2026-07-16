<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SPEC §2.4 + §3.1 — phan loai 2 tang.
// base_level  = tang 1, suy tu math_gpa -> CHI THAM KHAO.
// final_level = tang 2, AI hieu chinh tu bai test that -> QUYET DINH.
// Luu ca hai de do do lech giua tu khai va nang luc thuc te.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_classifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students');
            $table->foreignId('assessment_id')->constrained('assessments');

            $table->unsignedTinyInteger('overall_ability');       // 0..100
            $table->unsignedTinyInteger('self_learning_level');   // muc do tu hoc
            $table->unsignedTinyInteger('processing_speed');      // toc do xu ly bai

            $table->enum('base_level', ['trung_binh', 'kha', 'gioi']);    // tang 1
            $table->enum('final_level', ['trung_binh', 'kha', 'gioi']);   // tang 2 - QUYET DINH

            $table->json('weak_topics');
            $table->timestamps();

            $table->index(['student_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_classifications');
    }
};
