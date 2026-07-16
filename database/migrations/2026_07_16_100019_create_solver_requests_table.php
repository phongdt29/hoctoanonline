<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SPEC §2.8 + §3.4 — Solver, chong le thuoc dap an.
// hint_count max 2 (config hoctoan.solver.max_hints); chi bung full loi giai khi
// student bam "xem loi giai" -> solution_revealed = true.
// index [student_id, created_at] phuc vu rate-limit 20 anh/ngay.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solver_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students');
            $table->enum('input_type', ['text', 'image']);
            $table->text('problem_text')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->decimal('ocr_confidence', 5, 2)->nullable();
            $table->unsignedTinyInteger('hint_count')->default(0);
            $table->boolean('solution_revealed')->default(false);
            $table->timestamps();

            $table->index(['student_id', 'created_at']);
            $table->index(['student_id', 'input_type', 'created_at']);  // rate-limit anh/ngay
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solver_requests');
    }
};
