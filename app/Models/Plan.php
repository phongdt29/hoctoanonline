<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Ticket R3 — goi cuoc. */
class Plan extends Model
{
    protected $fillable = ['name', 'price', 'duration_days', 'features', 'is_active'];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'duration_days' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
