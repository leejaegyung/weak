<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeeklyReport extends Model
{
    use HasFactory;

    protected $appends = ['status_label', 'week_label'];

    protected $fillable = [
        'user_id', 'week', 'status', 'reject_reason',
        'curr_start', 'curr_end',
        'next_start', 'next_end',
        'curr_work', 'next_plan',
        'todo_items', 'notes', 'requests',
        'submitted_at', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'curr_start'   => 'date:Y-m-d',
            'curr_end'     => 'date:Y-m-d',
            'next_start'   => 'date:Y-m-d',
            'next_end'     => 'date:Y-m-d',
            'curr_work'    => 'array',
            'next_plan'    => 'array',
            'todo_items'   => 'array',
            'submitted_at' => 'datetime',
            'reviewed_at'  => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(\App\Models\ReportComment::class, 'report_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'submitted' => '제출됨',
            'reviewed'  => '검토완료',
            'rejected'  => '반려됨',
            default     => '작성 중',
        };
    }

    /** "2026-W22" → "2026년 5월 4주차" */
    public function getWeekLabelAttribute(): string
    {
        if (!$this->week || !str_contains($this->week, '-W')) return $this->week ?? '';
        [$year, $w]  = explode('-W', $this->week);
        $monday      = Carbon::now()->setISODate((int) $year, (int) $w, 1);
        $month       = $monday->month;
        $weekOfMonth = (int) ceil($monday->day / 7);
        return "{$year}년 {$month}월 {$weekOfMonth}주차";
    }
}
