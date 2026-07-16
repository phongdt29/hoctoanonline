<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Ticket R2 — huy hieu hoc sinh dat duoc. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('code', 50);          // first_quiz, streak_7, perfect_quiz...
            $table->dateTime('earned_at');

            // Moi huy hieu chi dat 1 lan.
            $table->unique(['student_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_badges');
    }
};
