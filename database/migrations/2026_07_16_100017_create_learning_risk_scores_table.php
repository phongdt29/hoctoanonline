<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SPEC §2.8 + §3.6 — Learning Risk Score.
// Luu theo tung lan tinh (snapshot) de ve duoc xu huong, khong ghi de.
// `components` = breakdown 5 rate, de phu huynh/admin biet vi sao diem cao.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_risk_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students');
            $table->unsignedTinyInteger('risk_score');                 // 0..100
            $table->enum('level', ['on_dinh', 'can_theo_doi', 'nguy_co_cao']);
            $table->json('components');                                // breakdown 5 rate
            $table->dateTime('computed_at');                           // dateTime: xem ghi chu o assessments

            $table->index(['student_id', 'computed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_risk_scores');
    }
};
