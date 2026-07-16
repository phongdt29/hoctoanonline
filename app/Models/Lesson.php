<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * SPEC §2.5 — lesson theo buoi.
 * Lesson dau `unlocked`, con lai `locked`; mo tuan tu khi lesson truoc `completed`.
 * Policy chan vao lesson `locked` (ticket L1).
 */
class Lesson extends Model
{
    use HasFactory;

    public $timestamps = false;

    public const STATUS_LOCKED      = 'locked';
    public const STATUS_UNLOCKED    = 'unlocked';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED   = 'completed';

    protected $fillable = [
        'module_id', 'lesson_order', 'title', 'theory_content', 'status',
    ];

    protected function casts(): array
    {
        return [
            'lesson_order' => 'integer',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CurriculumModule::class, 'module_id');
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }

    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class);
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class);
    }

    public function isAccessible(): bool
    {
        return $this->status !== self::STATUS_LOCKED;
    }
}
