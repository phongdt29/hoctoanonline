<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * SPEC §2.2 — ho so hoc sinh.
 *
 * State machine (SPEC §1) — KHONG nhay coc:
 * registered -> onboarded -> assessed -> classified -> curriculum_active -> learning
 */
class Student extends Model
{
    use HasFactory;

    public const STATUS_REGISTERED        = 'registered';
    public const STATUS_ONBOARDED         = 'onboarded';
    public const STATUS_ASSESSED          = 'assessed';
    public const STATUS_CLASSIFIED        = 'classified';
    public const STATUS_CURRICULUM_ACTIVE = 'curriculum_active';
    public const STATUS_LEARNING          = 'learning';

    /** Thu tu hop le cua state machine — dung de chan nhay coc. */
    public const STATUS_FLOW = [
        self::STATUS_REGISTERED,
        self::STATUS_ONBOARDED,
        self::STATUS_ASSESSED,
        self::STATUS_CLASSIFIED,
        self::STATUS_CURRICULUM_ACTIVE,
        self::STATUS_LEARNING,
    ];

    protected $fillable = [
        'user_id', 'full_name', 'date_of_birth', 'address', 'phone',
        'school_name', 'grade', 'self_assessed_level', 'math_gpa',
        'tutor_gender', 'favorite_color', 'interests', 'status',
        'points_balance', 'streak_days', 'invite_code',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth'  => 'date',
            'interests'      => 'array',
            'math_gpa'       => 'decimal:2',
            'grade'          => 'integer',
            'points_balance' => 'integer',
            'streak_days'    => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** 1 hoc sinh co the co nhieu phu huynh (bo + me) — SPEC §2.7. */
    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(ParentAccount::class, 'parent_student_links', 'student_id', 'parent_id')
            ->withPivot('linked_via')
            ->withTimestamps();
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function classifications(): HasMany
    {
        return $this->hasMany(StudentClassification::class);
    }

    /** Ket qua phan loai moi nhat — nguon sinh giao trinh. */
    public function latestClassification(): HasOne
    {
        return $this->hasOne(StudentClassification::class)->latestOfMany();
    }

    public function curricula(): HasMany
    {
        return $this->hasMany(Curriculum::class);
    }

    /** Giao trinh dang chay. Moi hoc sinh chi co 1 curriculum status=active. */
    public function activeCurriculum(): HasOne
    {
        return $this->hasOne(Curriculum::class)->where('status', 'active');
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class);
    }

    public function riskScores(): HasMany
    {
        return $this->hasMany(LearningRiskScore::class);
    }

    /** Risk score moi nhat — den tin hieu xanh/vang/do cho phu huynh. */
    public function latestRiskScore(): HasOne
    {
        return $this->hasOne(LearningRiskScore::class)->latestOfMany('computed_at');
    }

    public function pointEntries(): HasMany
    {
        return $this->hasMany(PointLedger::class);
    }

    public function solverRequests(): HasMany
    {
        return $this->hasMany(SolverRequest::class);
    }

    public function tutorConversations(): HasMany
    {
        return $this->hasMany(TutorConversation::class);
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_students', 'student_id', 'class_id');
    }

    public function assignmentSubmissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    /** Da qua buoc `assessed` chua — dung cho middleware EnsureStudentAssessed. */
    public function hasReachedStatus(string $status): bool
    {
        $current = array_search($this->status, self::STATUS_FLOW, true);
        $target  = array_search($status, self::STATUS_FLOW, true);

        return $current !== false && $target !== false && $current >= $target;
    }
}
