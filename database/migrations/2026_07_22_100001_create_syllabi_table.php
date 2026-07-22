<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Giao trinh mau (syllabus) — tao bang AI, DUNG CHUNG, khong gan hoc sinh.
 * Toan bo cau truc (modules -> lessons -> theory + exercises) luu trong cot `content` (JSON),
 * de tach hoan toan khoi bang curricula cua hoc sinh. Sau nay co the "gan" = clone content
 * sang curricula that.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('syllabi', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->unsignedTinyInteger('grade');            // lop 6-12
            $table->string('topic', 200)->nullable();        // chu de chinh
            $table->text('goal')->nullable();                // muc tieu
            $table->unsignedTinyInteger('planned_sessions')->default(0);
            $table->enum('status', ['draft', 'generating', 'ready', 'failed'])->default('draft');
            $table->text('error')->nullable();               // ly do that bai (neu co)
            $table->longText('content')->nullable();         // JSON cau truc giao trinh
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('generated_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'grade']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syllabi');
    }
};
