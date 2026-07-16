<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\ParentAccount;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditService;
use App\Support\RoleRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Ticket A1 — dang ky.
 *
 * Chi tao User + ho so rong (status=registered). 12 truong ho so do ONBOARDING (C2) thu,
 * dung theo state machine SPEC §1: registered -> onboarded -> ...
 */
class RegisterController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function show(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Transaction: User va ho so phai cung sinh ra hoac cung khong.
        // Neu tao User xong roi loi o Student, se con lai tai khoan khong ho so
        // -> dang nhap duoc nhung ket o luong onboarding.
        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],   // cast 'hashed' o Model tu bam
                'role' => $data['role'],
                'status' => 'active',
            ]);

            if ($user->role === User::ROLE_STUDENT) {
                Student::create([
                    'user_id' => $user->id,
                    'full_name' => $data['name'],
                    'status' => Student::STATUS_REGISTERED,
                ]);
            } else {
                ParentAccount::create([
                    'user_id' => $user->id,
                    'full_name' => $data['name'],
                    'phone' => '',                       // phu huynh bo sung o /parent/link-student
                    'relation_to_student' => 'nguoi_giam_ho',
                ]);
            }

            return $user;
        });

        $this->audit->log(AuditService::ACTION_REGISTER, $user, 'users', $user->id, [
            'role' => $user->role,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->to(RoleRedirect::for($user));
    }
}
