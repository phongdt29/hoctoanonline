<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Ticket A2 — quen/dat lai mat khau (production, khong phai UI suong).
 *
 * BA RANG BUOC BAO MAT (DoD A2):
 *  1. Token 64 ky tu ngau nhien, HASH truoc khi luu — DB bi lo van khong doi duoc
 *     mat khau cua ai. Chi ban ro gui qua email.
 *  2. TTL 30' — config('hoctoan.reset_token_ttl_min').
 *  3. One-time — danh dau `used_at`, dung lan 2 bi tu choi.
 *
 * So sanh token dung Hash::check (constant-time), khong dung == .
 */
class PasswordResetService
{
    public const TOKEN_BYTES = 32;   // 32 byte -> 64 ky tu hex

    /**
     * Tao token moi cho email. Tra ve ban RO de gui mail (khong bao gio luu ban ro).
     * Moi lan yeu cau se thay token cu -> link cu tu het hieu luc.
     */
    public function createToken(string $email): string
    {
        // random_bytes = CSPRNG. 32 byte -> 64 ky tu hex, dung yeu cau DoD A2.
        $plain = bin2hex(random_bytes(self::TOKEN_BYTES));

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($plain),
                'created_at' => now(),
                'used_at' => null,
            ],
        );

        return $plain;
    }

    /** Token con hieu luc khong: dung email, chua het han, chua dung. */
    public function isValid(string $email, string $plainToken): bool
    {
        $row = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $row || $row->used_at !== null) {
            return false;
        }

        if ($this->hasExpired($row->created_at)) {
            return false;
        }

        return Hash::check($plainToken, $row->token);
    }

    /**
     * Doi mat khau + danh dau token da dung. Tra false neu token khong hop le.
     * Huy toan bo Sanctum token: doi mat khau phai dang xuat moi thiet bi khac.
     */
    public function reset(string $email, string $plainToken, string $newPassword): bool
    {
        if (! $this->isValid($email, $plainToken)) {
            return false;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return false;
        }

        DB::transaction(function () use ($user, $email, $newPassword) {
            $user->update(['password' => $newPassword]);   // cast 'hashed' tu bam
            $user->tokens()->delete();

            DB::table('password_reset_tokens')
                ->where('email', $email)
                ->update(['used_at' => now()]);
        });

        return true;
    }

    private function hasExpired(?string $createdAt): bool
    {
        if ($createdAt === null) {
            return true;
        }

        return Carbon::parse($createdAt)
            ->addMinutes(config('hoctoan.reset_token_ttl_min'))
            ->isPast();
    }
}
