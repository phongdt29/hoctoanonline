<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** SPEC §2.4 — nang luc theo tung chuyen de (0..100) + ty le sai. */
class ClassificationTopicAbility extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'classification_id', 'topic', 'ability', 'error_rate',
    ];

    protected function casts(): array
    {
        return [
            'ability'    => 'integer',
            'error_rate' => 'decimal:2',
        ];
    }

    public function classification(): BelongsTo
    {
        return $this->belongsTo(StudentClassification::class, 'classification_id');
    }
}
