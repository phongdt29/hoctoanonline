<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SPEC §2.7 — tai khoan phu huynh.
 * 1 phu huynh CO THE co nhieu con (qua parent_student_links).
 */
class ParentAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'full_name', 'phone', 'relation_to_student',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Cac con da link. Policy: parent CHI xem duoc con trong quan he nay. */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'parent_student_links', 'parent_id', 'student_id')
            ->withPivot('linked_via')
            ->withTimestamps();
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(ParentNotification::class, 'parent_id');
    }

    /** Co quyen xem hoc sinh nay khong — dung trong Policy. */
    public function canView(Student $student): bool
    {
        return $this->children()->whereKey($student->getKey())->exists();
    }
}
