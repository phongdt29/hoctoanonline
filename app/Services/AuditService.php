<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Ghi audit_logs (SPEC §2.8 + CLAUDE.md quy tac #2).
 *
 * Moi endpoint phai goi service nay. KHONG ghi thang vao AuditLog::create()
 * o Controller — de con mot cho duy nhat kiem soat metadata (ip, user agent).
 *
 * KHONG BAO GIO log mat khau/token vao metadata.
 */
class AuditService
{
    public const ACTION_REGISTER = 'register';
    public const ACTION_LOGIN = 'login';
    public const ACTION_LOGIN_FAILED = 'login_failed';
    public const ACTION_LOGOUT = 'logout';
    public const ACTION_PASSWORD_RESET_REQUESTED = 'password_reset_requested';
    public const ACTION_PASSWORD_RESET = 'password_reset';

    /** Khoa nhay cam — tuyet doi khong ghi vao audit metadata. */
    private const REDACTED_KEYS = ['password', 'password_confirmation', 'token', 'api_key'];

    public function log(
        string $action,
        ?User $user = null,
        ?string $entity = null,
        ?int $entityId = null,
        array $metadata = [],
        ?Request $request = null,
    ): AuditLog {
        $request ??= request();

        return AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'metadata' => array_merge($this->scrub($metadata), [
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]),
        ]);
    }

    /** @param array<string, mixed> $metadata */
    private function scrub(array $metadata): array
    {
        foreach (self::REDACTED_KEYS as $key) {
            if (array_key_exists($key, $metadata)) {
                $metadata[$key] = '[redacted]';
            }
        }

        return $metadata;
    }
}
