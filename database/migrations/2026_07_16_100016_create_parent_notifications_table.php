<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SPEC §2.7 — lich su thong bao gui phu huynh.
// notification_type de string (khong enum) vi danh sach con mo rong theo R1
// (notification da kenh) — enum se phai migrate moi lan them loai moi.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('parent_accounts');
            $table->foreignId('student_id')->constrained('students');

            // session_start | session_done | absent | alert_high | weekly_report ...
            $table->string('notification_type', 50);
            $table->string('title', 200);
            $table->text('content');
            $table->enum('channel', ['in_app', 'email', 'sms', 'push'])->default('in_app');

            // dateTime: tranh ON UPDATE ngam cua MariaDB lam sent_at nhay khi danh dau da doc.
            $table->dateTime('sent_at');
            $table->dateTime('read_at')->nullable();

            $table->index(['parent_id', 'sent_at']);
            $table->index('notification_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_notifications');
    }
};
