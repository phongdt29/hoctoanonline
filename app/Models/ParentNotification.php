<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** SPEC §2.7 + §3.7 — thong bao gui phu huynh. */
class ParentNotification extends Model
{
    use HasFactory;

    public $timestamps = false;

    public const TYPE_SESSION_START = 'session_start';
    public const TYPE_SESSION_DONE  = 'session_done';
    public const TYPE_ABSENT        = 'absent';
    public const TYPE_QUIZ_MISSED   = 'quiz_missed';
    public const TYPE_ALERT_HIGH    = 'alert_high';      // vang 2 buoi lien tiep
    public const TYPE_SCORE_DECLINE = 'score_decline';   // quiz giam 3 buoi lien tiep
    public const TYPE_DAILY_REPORT  = 'daily_report';
    public const TYPE_WEEKLY_REPORT = 'weekly_report';

    public const CHANNEL_IN_APP = 'in_app';
    public const CHANNEL_EMAIL  = 'email';
    public const CHANNEL_SMS    = 'sms';
    public const CHANNEL_PUSH   = 'push';

    protected $fillable = [
        'parent_id', 'student_id', 'notification_type',
        'title', 'content', 'channel', 'sent_at', 'read_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentAccount::class, 'parent_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
