<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SPEC §2.8 + §3.8 — registry provider AI, failover theo `priority` tang dan.
// api_key_encrypted: Crypt::encrypt, KHONG BAO GIO tra ra response (mask o admin UI).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('base_url');
            $table->text('api_key_encrypted');
            $table->json('models');
            $table->enum('status', ['active', 'disabled'])->default('active');
            $table->unsignedTinyInteger('priority')->default(1);
            $table->timestamps();

            $table->index(['status', 'priority']);   // chon provider active theo priority ASC
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_providers');
    }
};
