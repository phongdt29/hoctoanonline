<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SPEC §2.4 + §3.1 — phan loai 2 tang.
 *
 * base_level  = suy tu math_gpa  -> CHI THAM KHAO, khong dung sinh giao trinh.
 * final_level = AI hieu chinh    -> QUYET DINH.
 * Giao trinh PHAI sinh tu topicAbilities (vector nang luc), khong tu final_level.
 */
class StudentClassification extends Model
{
    use HasFactory;

    public const LEVEL_TRUNG_BINH = 'trung_binh';
    public const LEVEL_KHA        = 'kha';
    public const LEVEL_GIOI       = 'gioi';

    protected $fillable = [
        'student_id', 'assessment_id', 'overall_ability', 'self_learning_level',
        'processing_speed', 'base_level', 'final_level', 'weak_topics',
    ];

    protected function casts(): array
    {
        return [
            'weak_topics'         => 'array',
            'overall_ability'     => 'integer',
            'self_learning_level' => 'integer',
            'processing_speed'    => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /** Nang luc theo tung chuyen de — thu quyet dinh noi dung giao trinh. */
    public function topicAbilities(): HasMany
    {
        return $this->hasMany(ClassificationTopicAbility::class, 'classification_id');
    }

    public function curricula(): HasMany
    {
        return $this->hasMany(Curriculum::class, 'classification_id');
    }

    /** Tang 2 co lat nguoc tang 1 khong — dung cho test/audit thuat toan. */
    public function aiOverrodeBaseLevel(): bool
    {
        return $this->base_level !== $this->final_level;
    }
}
