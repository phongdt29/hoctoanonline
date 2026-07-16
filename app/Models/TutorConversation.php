<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** SPEC §2.8 — hoi thoai voi AI Tutor (persona thay/co theo students.tutor_gender). */
class TutorConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'title',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TutorMessage::class, 'conversation_id');
    }
}
