<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Giao trinh mau — sinh bang AI, dung chung (khong gan hoc sinh).
 *
 * `content` giu toan bo cau truc da sinh:
 *   ['goal', 'planned_sessions', 'modules' => [['phase','topic','lessons' => [
 *       ['title','theory','exercises' => [['difficulty','content','answer'], ...]], ...]], ...]]
 */
class Syllabus extends Model
{
    use HasFactory;

    protected $table = 'syllabi';

    public const STATUS_DRAFT      = 'draft';
    public const STATUS_GENERATING = 'generating';   // job dang chay
    public const STATUS_READY      = 'ready';
    public const STATUS_FAILED     = 'failed';

    protected $fillable = [
        'title', 'grade', 'topic', 'goal', 'planned_sessions',
        'status', 'error', 'content', 'created_by', 'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'grade'            => 'integer',
            'planned_sessions' => 'integer',
            'content'          => 'array',
            'generated_at'     => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    public function isGenerating(): bool
    {
        return $this->status === self::STATUS_GENERATING;
    }

    /** Tong so bai hoc trong content (dung cho hien thi). */
    public function lessonCount(): int
    {
        $count = 0;
        foreach ($this->content['modules'] ?? [] as $module) {
            $count += count($module['lessons'] ?? []);
        }

        return $count;
    }
}
