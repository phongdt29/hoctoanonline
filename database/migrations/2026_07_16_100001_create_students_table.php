<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SPEC §2.2 — ho so hoc sinh (12 truong bat buoc cua module dang ky).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('full_name', 150);
            $table->date('date_of_birth');
            $table->string('address');
            $table->string('phone', 20);
            $table->string('school_name', 150);
            $table->unsignedTinyInteger('grade');                 // 6..12 — validate o FormRequest
            $table->enum('self_assessed_level', ['trung_binh', 'kha', 'gioi']);
            $table->decimal('math_gpa', 4, 2);                    // 0..10 — CHI THAM KHAO (SPEC §3.1)

            $table->enum('tutor_gender', ['thay', 'co'])->default('thay');
            $table->string('favorite_color', 30)->nullable();
            $table->json('interests')->nullable();

            // State machine SPEC §1 — khong nhay coc
            $table->enum('status', [
                'registered', 'onboarded', 'assessed',
                'classified', 'curriculum_active', 'learning',
            ])->default('registered');

            $table->integer('points_balance')->default(0);
            $table->integer('streak_days')->default(0);
            $table->string('invite_code', 10)->unique();          // parent link
            $table->timestamps();

            $table->index('status');
            $table->index('grade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
