<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ticket R3 — plans + payments (SPEC §8 R3).
 * Toi gian cho MVP thanh toan: goi cuoc + giao dich. Subscription day du la roadmap.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->integer('price');                 // VND
            $table->unsignedSmallInteger('duration_days');
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students');
            $table->foreignId('plan_id')->constrained('plans');

            $table->string('order_id', 64)->unique();   // vnp_TxnRef
            $table->integer('amount');                   // VND
            $table->string('gateway', 20);               // vnpay | momo
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');

            $table->json('callback_payload')->nullable();  // luu payload da verify
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('plans');
    }
};
