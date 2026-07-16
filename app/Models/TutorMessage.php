<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** SPEC §2.8 — tin nhan. Frontend polling `?after_id=` moi 3s (ticket I1). */
class TutorMessage extends Model
{
    use HasFactory;

    public const SENDER_STUDENT = 'student';
    public const SENDER_AI      = 'ai';

    protected $fillable = [
        'conversation_id', 'sender', 'content',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(TutorConversation::class, 'conversation_id');
    }

    /** Phuc vu polling: chi lay tin nhan moi hon lastId. */
    public function scopeAfterId(Builder $query, ?int $afterId): Builder
    {
        return $afterId ? $query->where('id', '>', $afterId) : $query;
    }
}
