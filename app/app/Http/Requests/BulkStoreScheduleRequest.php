<?php

namespace App\Http\Requests;

use App\Services\ScheduleService;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 팀 일정판 — 기간 일괄 등록/삭제 요청 검증.
 * 일괄 처리는 본인 일정에만 적용된다.
 */
class BulkStoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $times = ScheduleService::TIME_ORDER;
        if ($this->boolean('delete')) {
            $times[] = ScheduleService::TIME_ALL;
        }

        return [
            // 날짜를 직접 고른 경우(dates) 또는 기간으로 지정한 경우(start_date~end_date) 중 하나
            'dates'        => ['nullable', 'array', 'max:' . ScheduleService::BULK_MAX_DAYS],
            'dates.*'      => ['required', 'date_format:Y-m-d'],
            'start_date'   => ['nullable', 'required_without:dates', 'date_format:Y-m-d'],
            'end_date'     => ['nullable', 'required_without:dates', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'time'         => ['required', Rule::in($times)],
            'status'       => ['nullable', 'string', 'max:20'],
            'sites'        => ['nullable', 'array', 'max:20'],
            'sites.*'      => ['string', 'max:100'],
            'content'      => ['nullable', 'string', 'max:500'],
            'skip_weekend' => ['nullable', 'boolean'],
            'skip_holiday' => ['nullable', 'boolean'],
            'delete'       => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'dates.max'                       => '한 번에 처리할 수 있는 날짜는 최대 ' . ScheduleService::BULK_MAX_DAYS . '일입니다.',
            'start_date.required_without'     => '시작일을 선택하세요.',
            'end_date.required_without'       => '종료일을 선택하세요.',
            'end_date.after_or_equal'         => '종료일은 시작일과 같거나 이후여야 합니다.',
            'time.required'       => '시간대를 선택하세요.',
            'time.in'             => '올바른 시간대를 선택하세요.',
            'content.max'         => '내용은 500자 이내로 입력하세요.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // 기간 상한 검사 (기간 지정 방식일 때만)
            if ($this->input('start_date') && $this->input('end_date')) {
                $start = Carbon::parse($this->input('start_date'))->startOfDay();
                $end   = Carbon::parse($this->input('end_date'))->startOfDay();
                $days  = (int) abs($start->diffInDays($end)) + 1;

                if ($days > ScheduleService::BULK_MAX_DAYS) {
                    $validator->errors()->add(
                        'end_date',
                        '한 번에 등록할 수 있는 기간은 최대 ' . ScheduleService::BULK_MAX_DAYS . '일입니다.'
                    );
                }
            }

            // 등록 모드는 상태·사이트·내용 중 하나 이상 필요
            if (! $this->boolean('delete')
                && trim((string) $this->input('status')) === ''
                && ! $this->input('sites')
                && trim((string) $this->input('content')) === ''
            ) {
                $validator->errors()->add('status', '상태·사이트·내용 중 하나 이상을 입력하세요.');
            }
        });
    }
}
