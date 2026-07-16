<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SPEC §2.6 + §3.5 — phien hoc & diem danh 3 trang thai.
 *
 * present | partial | absent la 3 trang thai CHOT.
 * late | absent_pending la trang thai TRUNG GIAN cua flow vang mat:
 *   T+15' chua vao -> late ; T+30' -> absent_pending ; het khung gio -> chot absent.
 */
class AttendanceSession extends Model
{
    use HasFactory;

    protected $table = 'student_attendance_sessions';

    public $timestamps = false;

    public const STATUS_PRESENT        = 'present';
    public const STATUS_PARTIAL        = 'partial';
    public const STATUS_ABSENT         = 'absent';
    public const STATUS_LATE           = 'late';
    public const STATUS_ABSENT_PENDING = 'absent_pending';

    protected $fillable = [
        'student_id', 'lesson_id', 'scheduled_start_time', 'actual_start_time',
        'actual_end_time', 'attendance_status', 'effective_study_minutes',
        'idle_minutes', 'completion_rate',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_start_time'    => 'datetime',
            'actual_start_time'       => 'datetime',
            'actual_end_time'         => 'datetime',
            'effective_study_minutes' => 'integer',
            'idle_minutes'            => 'integer',
            'completion_rate'         => 'decimal:2',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(StudentActivityLog::class, 'session_id');
    }

    /** Tong thoi gian online (ke ca khong tuong tac). */
    public function onlineMinutes(): int
    {
        if (! $this->actual_start_time || ! $this->actual_end_time) {
            return 0;
        }

        return (int) $this->actual_start_time->diffInMinutes($this->actual_end_time);
    }

    /**
     * Muc tap trung = hoc thuc / online.
     * SPEC §3.5 yeu cau hien thi "online X phut — hoc thuc Y phut — muc tap trung Y/X".
     */
    public function focusRatio(): float
    {
        $online = $this->onlineMinutes();

        return $online > 0 ? round($this->effective_study_minutes / $online, 2) : 0.0;
    }
}
