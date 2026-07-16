<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SPEC §2.6 — hanh vi trong buoi hoc. Nguon tinh effective_study_time.
 *
 * BANG GHI RAT NHIEU: moi query PHAI kem dieu kien thoi gian (dung scope
 * betweenTime() ben duoi). Khong bao gio query bang nay khong co WHERE thoi gian.
 */
class StudentActivityLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    /** Event duoc coi la "co tuong tac that" khi tinh effective_study_time. */
    public const ACTIVE_EVENTS = [
        'lesson_open', 'section_view', 'exercise_start', 'answer_submit',
        'hint_request', 'chat_message', 'quiz_submit',
    ];

    /** tab_inactive KHONG phai tuong tac — no danh dau bat dau khoang idle. */
    public const EVENT_TAB_INACTIVE = 'tab_inactive';

    protected $fillable = [
        'session_id', 'event_type', 'event_time', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'event_time' => 'datetime',
            'metadata'   => 'array',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class, 'session_id');
    }

    /** Bat buoc kem dieu kien thoi gian khi query bang nay. */
    public function scopeBetweenTime(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('event_time', [$from, $to]);
    }

    public function isActiveEvent(): bool
    {
        return in_array($this->event_type, self::ACTIVE_EVENTS, true);
    }
}
