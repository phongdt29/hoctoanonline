<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

/**
 * SPEC §2.8 + §3.8 — registry provider AI, failover theo priority tang dan.
 *
 * api_key_encrypted KHONG BAO GIO lo ra response (DoD ticket T3 — masked).
 * Vi vay dat trong $hidden va chi doc qua apiKey().
 */
class AiProvider extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE   = 'active';
    public const STATUS_DISABLED = 'disabled';

    protected $fillable = [
        'name', 'base_url', 'api_key_encrypted', 'models', 'status', 'priority',
    ];

    /** Khong bao gio serialize key ra JSON. */
    protected $hidden = [
        'api_key_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'models'   => 'array',
            'priority' => 'integer',
        ];
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AiLog::class, 'provider_id');
    }

    /** Thu tu failover: active truoc, priority nho truoc. */
    public function scopeUsable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)->orderBy('priority');
    }

    public function apiKey(): string
    {
        return Crypt::decrypt($this->api_key_encrypted);
    }

    public function setApiKey(string $plain): void
    {
        $this->api_key_encrypted = Crypt::encrypt($plain);
    }

    /** Hien thi cho admin: chi 4 ky tu cuoi. */
    public function maskedApiKey(): string
    {
        $key = $this->apiKey();

        return str_repeat('•', 8).substr($key, -4);
    }
}
