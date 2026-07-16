<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SPEC §2.8 — log MOI call AI (CLAUDE.md quy tac #3).
 * Khong duoc gia lap: khong co ai_logs = call AI do khong hop le.
 */
class AiLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    public const UPDATED_AT = null;

    public const STATUS_OK       = 'ok';
    public const STATUS_ERROR    = 'error';
    public const STATUS_FILTERED = 'filtered';   // bi safety filter chan

    public const FEATURE_ASSESSMENT_GEN  = 'assessment_gen';
    public const FEATURE_GRADING         = 'grading';
    public const FEATURE_CURRICULUM      = 'curriculum';
    public const FEATURE_TUTOR_CHAT      = 'tutor_chat';
    public const FEATURE_SOLVER          = 'solver';
    public const FEATURE_RECOMMENDATION  = 'recommendation';

    protected $fillable = [
        'provider_id', 'student_id', 'feature', 'request_json',
        'response_json', 'latency_ms', 'status',
    ];

    protected function casts(): array
    {
        return [
            'request_json'  => 'array',
            'response_json' => 'array',
            'latency_ms'    => 'integer',
            'created_at'    => 'datetime',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'provider_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
