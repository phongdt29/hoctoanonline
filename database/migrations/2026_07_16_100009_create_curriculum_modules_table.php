<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SPEC §2.5 — 4 phase: 1 on nen tang | 2 cung co | 3 nang cao | 4 luyen de.
// Phase 1 PHAI uu tien weak_topics cua classification (SPEC §3.2).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_id')->constrained('curricula')->cascadeOnDelete();
            $table->unsignedTinyInteger('phase');     // 1..4
            $table->string('topic', 80);
            $table->integer('module_order');

            $table->index(['curriculum_id', 'phase', 'module_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_modules');
    }
};
