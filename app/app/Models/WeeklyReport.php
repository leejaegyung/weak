<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeeklyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'week', 'status',
        'curr_start', 'curr_end',
        'next_start', 'next_end',
        'curr_work', 'next_plan',
        'todo_items', 'notes', 'requests',
        'submitted_at', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'curr_start'   => 'date',
            'curr_end'     => 'date',
            'next_start'   => 'date',
            'next_end'     => 'date',
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

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'submitted' => '제출됨',
            'reviewed'  => '검토완료',
            'rejected'  => '반려됨',
            default     => '작성 중',
        };
    }
}
