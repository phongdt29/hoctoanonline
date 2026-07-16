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

            // Luc DANG KY (ticket A1) chi co full_name + email + password.
            // 8 truong duoi do ONBOARDING (ticket C2) dien -> phai nullable, neu khong
            // trang thai `registered` cua state machine SPEC §1 khong the ton tai.
            // Rang buoc bat buoc (grade 6..12, math_gpa 0..10) enforce o FormRequest
            // cua C2, khong o schema.
            $table->string('full_name', 150);
            $table->date('date_of_birth')->nullable();
            $table->string('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('school_name', 150)->nullable();
            $table->unsignedTinyInteger('grade')->nullable();      // 6..12
            $table->enum('self_assessed_level', ['trung_binh', 'kha', 'gioi'])->nullable();
            $table->decimal('math_gpa', 4, 2)->nullable();         // 0..10 — CHI THAM KHAO (SPEC §3.1)

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

            // Sinh o ONBOARDING (C2), khong phai luc dang ky -> nullable.
            // MySQL/MariaDB cho phep nhieu NULL trong cot unique nen van chan trung ma that.
            $table->string('invite_code', 10)->nullable()->unique();   // parent link
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
