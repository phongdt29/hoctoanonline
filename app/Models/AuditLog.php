<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** SPEC §2.8 — audit log cho moi endpoint (CLAUDE.md quy tac #2). */
class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'action', 'entity', 'entity_id', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata'   => 'array',
            'entity_id'  => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
