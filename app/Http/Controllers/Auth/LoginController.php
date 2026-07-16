<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuditService;
use App\Support\RoleRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Ticket A1 — dang nhap / dang xuat.
 *
 * Rate limit throttle:5,1 dat o route (routes/web.php).
 * Session-based cho web; Sanctum token cap rieng cho AJAX/mobile (SPEC §0).
 */
class LoginController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function show(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $this->audit->log(AuditService::ACTION_LOGIN_FAILED, null, 'users', null, [
                'email' => $request->input('email'),
            ]);

            // Bao loi chung, KHONG noi "email khong ton tai" hay "sai mat khau" —
            // tach 2 truong hop se giup do email nao da dang ky.
            throw ValidationException::withMessages([
                'email' => 'Email hoặc mật khẩu không đúng.',
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        // Tai khoan bi khoa thi khong cho vao, du mat khau dung.
        if (! $user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();

            throw ValidationException::withMessages([
                'email' => 'Tài khoản đã bị khóa. Liên hệ quản trị viên để được hỗ trợ.',
            ]);
        }

        $request->session()->regenerate();

        $this->audit->log(AuditService::ACTION_LOGIN, $user, 'users', $user->id);

        return redirect()->intended(RoleRedirect::for($user));
    }

    public function destroy(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user) {
            // Huy ca session VA Sanctum token — logout ma con token thi
            // AJAX/mobile van goi API duoc (DoD A1).
            $user->tokens()->delete();
            $this->audit->log(AuditService::ACTION_LOGOUT, $user, 'users', $user->id);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
