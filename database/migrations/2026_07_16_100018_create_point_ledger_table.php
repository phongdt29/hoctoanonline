<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SPEC §2.8 — so cai diem thuong. APPEND-ONLY.
//
// CLAUDE.md quy tac #5: KHONG duoc ton tai bat ky code path update/delete nao.
// Model PointLedger override update()/delete() -> nem RuntimeException (ticket F3).
// Vi vay bang chi co created_at, KHONG co updated_at (ban ghi khong bao gio doi).
// Ten bang giu SO IT theo dung SPEC (`point_ledger`, khong phai `point_ledgers`).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students');
            $table->integer('amount');                 // duong hoac am
            $table->enum('reason', [
                'assessment_complete', 'quiz_score', 'assignment_graded', 'admin_adjustment',
            ]);
            $table->unsignedBigInteger('ref_id')->nullable();   // id cua quiz_attempt/assignment...
            $table->dateTime('created_at')->useCurrent();       // dateTime: xem ghi chu o assessments

            $table->index('student_id');
            $table->index(['student_id', 'reason', 'ref_id']);  // chong ghi diem trung (double)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_ledger');
    }
};
