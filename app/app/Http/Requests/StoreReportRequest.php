<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'week'                   => ['required', 'string', 'regex:/^\d{4}-W\d{1,2}$/'],
            'curr_start'             => ['required', 'date'],
            'curr_end'               => ['required', 'date', 'after_or_equal:curr_start'],
            'next_start'             => ['required', 'date'],
            'next_end'               => ['required', 'date', 'after_or_equal:next_start'],
            'curr_work'              => ['nullable', 'array'],
            'curr_work.*.category'   => ['nullable', 'string', 'max:50'],
            'curr_work.*.title'      => ['nullable', 'string'],
            'curr_work.*.content'    => ['nullable', 'string'],
            'curr_work.*.sub_items'  => ['nullable', 'array'],
            'next_plan'              => ['nullable', 'array'],
            'next_plan.*.category'   => ['nullable', 'string', 'max:50'],
            'next_plan.*.title'      => ['nullable', 'string'],
            'next_plan.*.content'    => ['nullable', 'string'],
            'next_plan.*.sub_items'  => ['nullable', 'array'],
            'todo_items'             => ['nullable', 'array'],
            'todo_items.*.content'   => ['nullable', 'string'],
            'notes'                  => ['nullable', 'string'],
            'requests'               => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'week.required'       => '주차를 입력하세요.',
            'week.regex'          => '주차 형식이 올바르지 않습니다.',
            'curr_start.required' => '이번 주 시작일을 입력하세요.',
            'curr_end.required'   => '이번 주 종료일을 입력하세요.',
            'next_start.required' => '다음 주 시작일을 입력하세요.',
            'next_end.required'   => '다음 주 종료일을 입력하세요.',
        ];
    }
}
