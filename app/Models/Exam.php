<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * De thi trac nghiem. `content` = {questions:[{content, options[4], correct(0-3), difficulty, topic}]}.
 *
 * Ma de (variant) va dap an duoc suy ra tu content qua ExamService::variant().
 */
class Exam extends Model
{
    use HasFactory;

    public const STATUS_DRAFT      = 'draft';
    public const STATUS_GENERATING = 'generating';
    public const STATUS_READY      = 'ready';
    public const STATUS_FAILED     = 'failed';

    protected $fillable = [
        'title', 'grade', 'topics', 'difficulty', 'question_count',
        'status', 'error', 'content', 'created_by', 'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'grade'          => 'integer',
            'question_count' => 'integer',
            'content'        => 'array',
            'generated_at'   => 'datetime',
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

    /** @return array<int,array<string,mixed>> */
    public function questions(): array
    {
        return $this->content['questions'] ?? [];
    }
}
