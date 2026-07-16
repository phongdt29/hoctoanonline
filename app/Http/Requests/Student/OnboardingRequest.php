<?php

namespace App\Http\Requests\Student;

use App\Models\User;
use App\Support\ThemeColor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Ticket C2 — onboarding: thu 12 truong ho so (module 1).
 * grade 6..12, math_gpa 0..10 (SPEC §2.2). favorite_color phai thuoc bang 10 mau
 * (UI spec §5 — khong cho hex tu do).
 */
class OnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === User::ROLE_STUDENT;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:150'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\s().-]{8,20}$/'],
            'school_name' => ['required', 'string', 'max:150'],
            'grade' => ['required', 'integer', 'between:6,12'],
            'self_assessed_level' => ['required', Rule::in(['trung_binh', 'kha', 'gioi'])],
            'math_gpa' => ['required', 'numeric', 'between:0,10'],
            'tutor_gender' => ['required', Rule::in(['thay', 'co'])],
            'favorite_color' => ['required', Rule::in(ThemeColor::allowedHexes())],
            'interests' => ['nullable', 'array', 'max:10'],
            'interests.*' => ['string', 'max:50'],
        ];
    }

    public function attributes(): array
    {
        return [
            'full_name' => 'họ và tên',
            'date_of_birth' => 'ngày sinh',
            'address' => 'địa chỉ',
            'phone' => 'số điện thoại',
            'school_name' => 'trường học',
            'grade' => 'khối lớp',
            'self_assessed_level' => 'học lực tự đánh giá',
            'math_gpa' => 'điểm trung bình toán',
            'tutor_gender' => 'giáo viên',
            'favorite_color' => 'màu yêu thích',
        ];
    }

    public function messages(): array
    {
        return [
            'grade.between' => 'Khối lớp phải từ 6 đến 12.',
            'math_gpa.between' => 'Điểm trung bình phải từ 0 đến 10.',
            'favorite_color.in' => 'Vui lòng chọn màu trong bảng có sẵn.',
        ];
    }
}
