<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SPEC §2.8 — hoi thoai voi AI Tutor (persona thay/co theo students.tutor_gender).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutor_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students');
            $table->string('title')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor_conversations');
    }
};
