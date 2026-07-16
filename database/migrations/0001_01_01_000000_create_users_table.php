<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SPEC §2.1. Ho ten cua student nam o students.full_name, cua parent nam o
        // parent_accounts.full_name. Rieng teacher/staff/admin khong co bang profile
        // nen `name` la noi duy nhat luu ten ho -> giu lai cot nay.
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['student', 'parent', 'teacher', 'staff', 'admin']);
            $table->enum('status', ['active', 'disabled'])->default('active');
            $table->rememberToken();
            $table->timestamps();

            $table->index('role');
        });

        // SPEC §2.1 + ticket A2: token hash truoc khi luu, TTL 30' (config
        // hoctoan.reset_token_ttl_min), one-time qua `used_at`.
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('used_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
