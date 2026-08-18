<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendWeeklyMailRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'week_start' => ['required', 'date'],
            'to'         => ['required', 'email'],
            'cc'         => ['nullable', 'array'],
            'cc.*'       => ['email'],
            'subject'    => ['required', 'string', 'max:200'],
            'body_intro' => ['nullable', 'string', 'max:2000'],
            'body_main'  => ['nullable', 'string', 'max:5000'],
            'body_outro' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'week_start.required' => '전송할 주차를 선택하세요.',
            'to.required'         => '받는 사람 메일 주소를 입력하세요.',
            'to.email'            => '받는 사람 메일 주소 형식이 올바르지 않습니다.',
            'cc.*.email'          => '참조 메일 주소 형식이 올바르지 않습니다.',
            'subject.required'    => '메일 제목을 입력하세요.',
        ];
    }
}
