<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ticket L2 — snapshot cau hoi + dap an dung cua quiz, luu O SERVER.
 *
 * Ly do bao mat (CLAUDE.md #7): server PHAI biet dap an dung de tu cham. Neu de
 * client gui "cau nay dung" thi hoc sinh sua duoc -> gian lan. Snapshot chot luc
 * start(), cham luc submit() bang cach so dap an hoc sinh voi snapshot nay.
 *
 * KHONG BAO GIO tra `correct` cua snapshot ve client truoc khi nop.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->json('questions_snapshot')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropColumn('questions_snapshot');
        });
    }
};
