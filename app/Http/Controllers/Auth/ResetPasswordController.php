<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use App\Services\AuditService;
use App\Services\PasswordResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Ticket A2 — quen / dat lai mat khau production. */
class ResetPasswordController extends Controller
{
    public function __construct(
        private readonly PasswordResetService $resets,
        private readonly AuditService $audit,
    ) {}

    public function showRequestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $data = $request->validate(
            ['email' => ['required', 'email']],
            [],
            ['email' => 'email'],
        );

        $user = User::where('email', $data['email'])->first();

        // Chi gui mail khi tai khoan ton tai VA con hoat dong — nhung LUON tra ve
        // cung mot thong bao. Neu bao "email khong ton tai" thi trang nay thanh
        // cong cu do xem email nao da dang ky.
        if ($user && $user->isActive()) {
            $token = $this->resets->createToken($user->email);

            Mail::to($user->email)->send(new ResetPasswordMail(
                resetUrl: route('password.reset', ['token' => $token]).'?email='.urlencode($user->email),
                recipientName: $user->name,
                ttlMinutes: config('hoctoan.reset_token_ttl_min'),
            ));

            $this->audit->log(
                AuditService::ACTION_PASSWORD_RESET_REQUESTED,
                $user,
                'users',
                $user->id,
            );
        }

        return back()->with('status', 'Nếu email tồn tại trong hệ thống, chúng tôi đã gửi link đặt lại mật khẩu. Kiểm tra hộp thư nhé.');
    }

    public function showResetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'password.confirmed' => 'Nhập lại mật khẩu chưa khớp.',
            'password.min' => 'Mật khẩu phải từ :min ký tự.',
        ]);

        $ok = $this->resets->reset($data['email'], $data['token'], $data['password']);

        if (! $ok) {
            // Gop chung 3 truong hop: token sai / het han / da dung.
            // Tach ra se lo trang thai token cho nguoi khong so huu no.
            throw ValidationException::withMessages([
                'email' => 'Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Hãy yêu cầu link mới.',
            ]);
        }

        $user = User::where('email', $data['email'])->first();

        $this->audit->log(AuditService::ACTION_PASSWORD_RESET, $user, 'users', $user?->id);

        return redirect()
            ->route('login')
            ->with('status', 'Đổi mật khẩu thành công. Đăng nhập bằng mật khẩu mới nhé.');
    }
}
