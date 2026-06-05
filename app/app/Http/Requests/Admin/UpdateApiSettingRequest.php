<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApiSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'service' => ['required', 'string', 'in:anthropic,openai,ai_provider,ai_enabled'],
            'api_key' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'service.in' => '지원하지 않는 서비스입니다.',
        ];
    }
}
