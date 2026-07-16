<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SPEC §2.8 + §3.8 — log MOI call AI (CLAUDE.md quy tac #3).
// FK provider_id tro sang `ai_providers` (khong phai `providers`) nen phai chi ro ten bang.
// nullOnDelete: xoa provider khong duoc lam mat lich su log.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->nullable()
                ->constrained('ai_providers')->nullOnDelete();
            $table->foreignId('student_id')->nullable()
                ->constrained('students')->nullOnDelete();

            // assessment_gen | grading | curriculum | tutor_chat | solver | recommendation
            $table->string('feature', 50);
            $table->json('request_json');
            $table->json('response_json')->nullable();
            $table->integer('latency_ms')->nullable();
            $table->enum('status', ['ok', 'error', 'filtered']);
            $table->dateTime('created_at')->useCurrent();     // dateTime: xem ghi chu o assessments

            $table->index(['feature', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_logs');
    }
};
