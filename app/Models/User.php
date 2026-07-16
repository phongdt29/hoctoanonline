<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * SPEC §2.1 — tai khoan dang nhap chung cho ca 5 vai tro.
 *
 * Ho ten: student -> students.full_name, parent -> parent_accounts.full_name.
 * Rieng teacher/staff/admin khong co bang profile nen dung `name` o day.
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_STUDENT = 'student';
    public const ROLE_PARENT  = 'parent';
    public const ROLE_TEACHER = 'teacher';
    public const ROLE_STAFF   = 'staff';
    public const ROLE_ADMIN   = 'admin';

    public const ROLES = [
        self::ROLE_STUDENT,
        self::ROLE_PARENT,
        self::ROLE_TEACHER,
        self::ROLE_STAFF,
        self::ROLE_ADMIN,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function parentAccount(): HasOne
    {
        return $this->hasOne(ParentAccount::class);
    }

    /** Lop ma user nay day (chi co nghia khi role=teacher). */
    public function taughtClasses(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'teacher_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
