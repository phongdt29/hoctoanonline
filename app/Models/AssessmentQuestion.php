<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SPEC §2.3 — cau hoi bai danh gia.
 * `time_spent_seconds` la input BAT BUOC cua phan loai tang 2 (SPEC §3.1).
 */
class AssessmentQuestion extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'assessment_id', 'question_order', 'type', 'topic', 'difficulty',
        'content', 'options', 'correct_answer', 'student_answer',
        'is_correct', 'time_spent_seconds',
    ];

    protected function casts(): array
    {
        return [
            'options'            => 'array',
            'correct_answer'     => 'array',
            'student_answer'     => 'array',
            'is_correct'         => 'boolean',
            'time_spent_seconds' => 'integer',
            'question_order'     => 'integer',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }
}
