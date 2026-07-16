<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SPEC §2.5 — giao trinh ca nhan hoa, sinh tu classification (khong tu 1 nhan).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curricula', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students');
            $table->foreignId('classification_id')->constrained('student_classifications');
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->string('goal')->nullable();
            $table->integer('planned_sessions');
            $table->timestamps();

            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curricula');
    }
};
