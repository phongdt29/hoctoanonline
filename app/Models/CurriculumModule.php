<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** SPEC §2.5 — module theo phase (1 on nen tang | 2 cung co | 3 nang cao | 4 luyen de). */
class CurriculumModule extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'curriculum_id', 'phase', 'topic', 'module_order',
    ];

    protected function casts(): array
    {
        return [
            'phase'        => 'integer',
            'module_order' => 'integer',
        ];
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class, 'curriculum_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'module_id')->orderBy('lesson_order');
    }
}
