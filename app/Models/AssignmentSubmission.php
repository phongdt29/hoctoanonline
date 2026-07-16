<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SPEC §2.9 — bai hoc sinh nop (ticket T2).
 * unique [assignment_id, student_id]: nop lan 2 la CAP NHAT, khong tao ban ghi trung.
 */
class AssignmentSubmission extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'assignment_id', 'student_id', 'content', 'file_url',
        'submitted_at', 'score', 'graded_at', 'feedback',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'graded_at'    => 'datetime',
            'score'        => 'decimal:2',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function isGraded(): bool
    {
        return $this->graded_at !== null;
    }
}
