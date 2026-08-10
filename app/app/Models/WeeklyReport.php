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
        'user_id', 'author_name', 'author_position',
        'week', 'status', 'reject_reason',
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

    /**
     * 작성자 표시 정보. 계정이 삭제된 경우 저장해 둔 스냅샷으로 대체한다.
     * 화면에서 쓰는 user 페이로드와 형태가 같아 그대로 끼워 넣을 수 있다.
     */
    public function authorPayload(): ?array
    {
        if ($this->user) {
            return [
                'id'               => $this->user->id,
                'name'             => $this->user->name,
                'position'         => $this->user->position,
                'avatar_color'     => $this->user->avatar_color,
                'avatar_image_url' => $this->user->avatar_image_url,
                'is_deleted'       => false,
            ];
        }

        if (!$this->author_name) {
            return null;
        }

        return [
            'id'               => null,
            'name'             => $this->author_name,
            'position'         => $this->author_position,
            'avatar_color'     => null,
            'avatar_image_url' => null,
            'is_deleted'       => true,
        ];
    }

    /** 작성자 이름 (계정 삭제 시 스냅샷) */
    public function getAuthorLabelAttribute(): string
    {
        return $this->user?->name ?? $this->author_name ?? '-';
    }

    /**
     * user 관계가 비어 있으면(=계정 삭제) 스냅샷으로 채워 직렬화한다.
     * 덕분에 화면 쪽 report.user 참조를 그대로 두어도 이름이 계속 표시된다.
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        if (array_key_exists('user', $array) && $array['user'] === null) {
            $array['user'] = $this->authorPayload();
        }

        return $array;
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
