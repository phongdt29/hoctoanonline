<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Ticket R2 — huy hieu. */
class StudentBadge extends Model
{
    public $timestamps = false;

    protected $fillable = ['student_id', 'code', 'earned_at'];

    protected function casts(): array
    {
        return ['earned_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
