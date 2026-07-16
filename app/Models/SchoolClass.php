<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SPEC §2.9 — lop hoc (bang `classes`).
 *
 * NGOAI LE quy uoc dat ten cua CLAUDE.md ("Model PascalCase so it" -> le ra la `Class`):
 * `class` la TU KHOA cua PHP, khong the dat ten class la Class. Dung SchoolClass
 * va map thu cong sang bang `classes`.
 */
class SchoolClass extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'teacher_id', 'name', 'grade',
    ];

    protected function casts(): array
    {
        return [
            'grade' => 'integer',
            // Cast teacher_id -> int: foreign key khong tu cast, se ve string "2"
            // lam so sanh strict `=== $user->id` (int) sai -> Policy tu choi nham.
            'teacher_id' => 'integer',
        ];
    }

    /** Giao vien phu trach — tro thang sang users (teacher khong co bang profile). */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'class_students', 'class_id', 'student_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'class_id');
    }
}
