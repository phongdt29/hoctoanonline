<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SPEC §2.5 + ticket L2 — luot lam quiz.
 *
 * CLAUDE.md quy tac #7: timer va anti-cheat QUYET DINH O SERVER.
 * `expires_at` do server tao luc start; luc submit server tu so sanh voi now().
 * Client chi hien thi dem nguoc — sua gio may client khong an thua.
 */
class QuizAttempt extends Model
{
    use HasFactory;

    public $timestamps = false;

    public const SUGGESTION_HOC_TIEP = 'hoc_tiep';
    public const SUGGESTION_ON_LAI   = 'on_lai';

    protected $fillable = [
        'quiz_id', 'student_id', 'score', 'error_analysis',
        'suggestion', 'started_at', 'expires_at', 'submitted_at', 'questions_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'error_analysis'     => 'array',
            'questions_snapshot' => 'array',
            'score'              => 'decimal:2',
            'started_at'         => 'datetime',
            'expires_at'         => 'datetime',
            'submitted_at'       => 'datetime',
        ];
    }

    /** Snapshot cau hoi KHONG kem dap an dung — an toan de tra ve client. */
    public function questionsForClient(): array
    {
        return collect($this->questions_snapshot ?? [])
            ->map(fn ($q, $i) => [
                'index' => $i,
                'topic' => $q['topic'] ?? 'unknown',
                'content' => $q['content'] ?? '',
                'options' => $q['options'] ?? [],
            ])
            ->all();
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** Het gio chua — SERVER la noi quyet dinh, khong tin client. */
    public function hasExpired(): bool
    {
        return now()->greaterThan($this->expires_at);
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }
}
