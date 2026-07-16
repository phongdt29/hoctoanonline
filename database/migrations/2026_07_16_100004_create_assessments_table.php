<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SPEC §2.3 — bai kiem tra dau vao.
//
// dateTime CHU KHONG PHAI timestamp cho cot thoi gian nghiep vu:
// MariaDB co explicit_defaults_for_timestamp=OFF (MySQL 8 = ON) nen cot `timestamp
// NOT NULL` dau tien bi TU DONG gan "DEFAULT current_timestamp() ON UPDATE
// current_timestamp()". Hau qua: moi lan update row (vd status -> graded) thi
// started_at tu nhay ve NOW, pha huy du lieu thoi gian lam bai — von la input
// bat buoc cua phan loai tang 2 (SPEC §3.1). dateTime khong dinh magic nay,
// va cung tranh luon moc 2038 cua timestamp.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students');
            $table->enum('status', ['in_progress', 'submitted', 'graded'])->default('in_progress');
            $table->decimal('score', 4, 2)->nullable();
            $table->dateTime('started_at');
            $table->dateTime('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
