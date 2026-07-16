<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SPEC §2.8 + §3.6 — Learning Risk Score (snapshot theo tung lan tinh).
 * Nguong doc tu config('hoctoan.risk_levels') — CLAUDE.md quy tac #1.
 */
class LearningRiskScore extends Model
{
    use HasFactory;

    public $timestamps = false;

    public const LEVEL_ON_DINH      = 'on_dinh';       // 🟢
    public const LEVEL_CAN_THEO_DOI = 'can_theo_doi';  // 🟡
    public const LEVEL_NGUY_CO_CAO  = 'nguy_co_cao';   // 🔴

    protected $fillable = [
        'student_id', 'risk_score', 'level', 'components', 'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'components'  => 'array',
            'risk_score'  => 'integer',
            'computed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** Quy diem 0..100 ve 3 muc theo nguong config. */
    public static function levelFor(int $score): string
    {
        $levels = config('hoctoan.risk_levels');

        return match (true) {
            $score <= $levels['on_dinh']      => self::LEVEL_ON_DINH,
            $score <= $levels['can_theo_doi'] => self::LEVEL_CAN_THEO_DOI,
            default                            => self::LEVEL_NGUY_CO_CAO,
        };
    }
}
