<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SPEC §2.4 — nang luc THEO TUNG CHUYEN DE (bat buoc).
// Day la thu thay the cho "3 nhan trung_binh/kha/gioi" ma tai lieu goc phe binh:
// giao trinh phai sinh tu vector nang luc nay, khong sinh tu 1 nhan.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classification_topic_abilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classification_id')
                ->constrained('student_classifications')
                ->cascadeOnDelete();

            $table->string('topic', 80);
            $table->unsignedTinyInteger('ability');       // 0..100
            $table->decimal('error_rate', 5, 2);          // % sai theo topic

            $table->unique(['classification_id', 'topic']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classification_topic_abilities');
    }
};
