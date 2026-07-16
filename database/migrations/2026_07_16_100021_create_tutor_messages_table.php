<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SPEC §2.8 — tin nhan trong hoi thoai.
// Frontend polling `GET .../messages?after_id={lastId}` moi 3s -> index (conversation_id, id).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutor_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')
                ->constrained('tutor_conversations')
                ->cascadeOnDelete();
            $table->enum('sender', ['student', 'ai']);
            $table->text('content');
            $table->timestamps();

            $table->index(['conversation_id', 'id']);   // phuc vu polling after_id
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor_messages');
    }
};
