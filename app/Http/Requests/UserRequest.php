<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation shared by the three places a user is written:
 * POST /users (create), PUT /users/{user} (update), PUT /profile (own profile).
 */
class UserRequest extends FormRequest
{
    public function rules(): array
    {
        $target = $this->target();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($target)],
            'password' => [$target ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'กรุณากรอกชื่อ',
            'email.required' => 'กรุณากรอกอีเมล',
            'email.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
            'email.unique' => 'อีเมลนี้ถูกใช้งานแล้ว',
            'password.required' => 'กรุณากรอกรหัสผ่าน',
            'password.min' => 'รหัสผ่านต้องมีอย่างน้อย :min ตัวอักษร',
            'password.confirmed' => 'การยืนยันรหัสผ่านไม่ตรงกัน',
        ];
    }

    /**
     * Attributes to save. A blank password on an existing user means "keep the current one";
     * hashing is done by the User model's `hashed` cast.
     *
     * @return array{name: string, email: string, password?: string}
     */
    public function userData(): array
    {
        return array_filter(
            $this->safe()->only(['name', 'email', 'password']),
            fn ($value) => $value !== null && $value !== '',
        );
    }

    /** The user being edited: null when creating. Profile edits target the logged-in user. */
    private function target(): ?User
    {
        return $this->route('user') ?? ($this->isMethod('POST') ? null : $this->user());
    }
}