<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SPEC §2.5 — lesson theo buoi.
// FK `module_id` tro sang bang `curriculum_modules` (khong phai `modules`)
// nen phai chi ro ten bang trong constrained().
// Lesson dau `unlocked`, con lai `locked`; mo tuan tu khi lesson truoc `completed`.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('curriculum_modules')->cascadeOnDelete();
            $table->integer('lesson_order');
            $table->string('title', 200);
            $table->mediumText('theory_content');
            $table->enum('status', ['locked', 'unlocked', 'in_progress', 'completed'])
                ->default('locked');

            $table->index(['module_id', 'lesson_order']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
