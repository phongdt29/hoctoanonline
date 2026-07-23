<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * De thi / kiem tra trac nghiem — AI sinh cau hoi 4 lua chon.
 * Toan bo cau hoi luu trong `content` (JSON): {questions:[{content,options[4],correct,difficulty,topic}]}.
 * Ma de (tron thu tu) + cham tu dong deu suy ra tu content, khong luu du.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->unsignedTinyInteger('grade');
            $table->string('topics', 255)->nullable();       // chu de (ngan cach dau phay)
            $table->enum('difficulty', ['easy', 'medium', 'hard', 'mixed'])->default('mixed');
            $table->unsignedTinyInteger('question_count')->default(10);
            $table->enum('status', ['draft', 'generating', 'ready', 'failed'])->default('draft');
            $table->text('error')->nullable();
            $table->longText('content')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('generated_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'grade']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
