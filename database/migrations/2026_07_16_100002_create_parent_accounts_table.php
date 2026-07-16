<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SPEC §2.7 — tai khoan phu huynh. Email/password nam o `users` (role=parent);
// bang nay chi giu thong tin phu.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('full_name', 150);
            $table->string('phone', 20);
            $table->enum('relation_to_student', ['bo', 'me', 'nguoi_giam_ho']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_accounts');
    }
};
