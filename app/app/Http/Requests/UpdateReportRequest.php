<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReportRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
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
}
