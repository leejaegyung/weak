<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('id');

        return [
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:100', 'unique:users,username,' . $userId],
            'email' => ['nullable', 'email', 'unique:users,email,' . $userId],
            'password' => ['nullable', 'string', 'min:6'],
            'position' => ['nullable', 'string', 'max:100'],
            'role' => ['required', 'in:admin,user'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '이름을 입력하세요.',
            'username.required' => '아이디를 입력하세요.',
            'username.unique' => '이미 사용 중인 아이디입니다.',
            'password.min' => '비밀번호는 최소 6자 이상이어야 합니다.',
            'role.required' => '권한을 선택하세요.',
        ];
    }
}
