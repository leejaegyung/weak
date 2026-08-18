<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 주간보고 메일 전송 이력.
 * "해당 주차 메일이 전송되었는지" 를 팀원 모두가 확인할 수 있도록 남기는 기록.
 */
class WeeklyMailLog extends Model
{
    protected $fillable = [
        'week', 'week_start', 'sent_by', 'sender_name',
        'to_email', 'cc_emails', 'subject', 'report_count', 'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'week_start'   => 'date:Y-m-d',
            'cc_emails'    => 'array',
            'report_count' => 'integer',
            'sent_at'      => 'datetime',
        ];
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /** 보낸 사람 이름 (계정 삭제 시 스냅샷으로 대체) */
    public function getSenderLabelAttribute(): string
    {
        return $this->sender?->name ?? $this->sender_name ?? '-';
    }
}
