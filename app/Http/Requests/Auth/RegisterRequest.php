<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Ticket A1 — dang ky.
 *
 * Chi thu email/password/ho ten/vai tro. 12 truong ho so do ONBOARDING (C2) thu.
 * CHI cho dang ky role student|parent — teacher/staff/admin do admin tao (T3),
 * neu khong ai cung tu dang ky duoc tai khoan admin.
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['required', Rule::in([User::ROLE_STUDENT, User::ROLE_PARENT])],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'họ và tên',
            'email' => 'email',
            'password' => 'mật khẩu',
            'role' => 'vai trò',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email này đã được đăng ký.',
            'password.confirmed' => 'Nhập lại mật khẩu chưa khớp.',
            'password.min' => 'Mật khẩu phải từ :min ký tự.',
            'role.in' => 'Vai trò không hợp lệ.',
        ];
    }
}
