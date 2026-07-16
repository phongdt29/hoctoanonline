<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SPEC §2.5 — bai tap 3 muc do: De -> Trung binh -> Kho.
// Moi lesson phai co >= 3 exercise du ca 3 muc (DoD ticket C5).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->enum('difficulty', ['easy', 'medium', 'hard']);
            $table->text('content');
            $table->json('answer');

            $table->index(['lesson_id', 'difficulty']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
