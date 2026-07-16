<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/** SPEC §2.5 — giao trinh ca nhan hoa. */
class Curriculum extends Model
{
    use HasFactory;

    /** Plural cua "curriculum" la "curricula" — Laravel khong tu suy ra dung. */
    protected $table = 'curricula';

    public const PHASE_ON_NEN_TANG = 1;
    public const PHASE_CUNG_CO     = 2;
    public const PHASE_NANG_CAO    = 3;
    public const PHASE_LUYEN_DE    = 4;

    protected $fillable = [
        'student_id', 'classification_id', 'status', 'goal', 'planned_sessions',
    ];

    protected function casts(): array
    {
        return [
            'planned_sessions' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classification(): BelongsTo
    {
        return $this->belongsTo(StudentClassification::class, 'classification_id');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(CurriculumModule::class, 'curriculum_id')
            ->orderBy('phase')
            ->orderBy('module_order');
    }

    /** Tat ca lesson cua giao trinh, xuyen qua module. */
    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(
            Lesson::class,
            CurriculumModule::class,
            'curriculum_id',
            'module_id',
        );
    }
}
