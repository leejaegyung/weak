<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:300'],
            'endpoint'    => ['nullable', 'url', 'max:500'],
            'api_key'     => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => '서비스 이름을 입력해 주세요.',
            'api_key.required' => 'API 키를 입력해 주세요.',
            'endpoint.url'     => '엔드포인트 URL 형식이 올바르지 않습니다.',
        ];
    }
}
