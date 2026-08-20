<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Issue extends Model
{
    protected $fillable = [
        'user_id', 'author_name', 'author_position',
        'title', 'content', 'status', 'claude_response',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** 작성자 이름. 계정이 삭제된 경우 저장해 둔 스냅샷으로 대체한다. */
    public function getAuthorLabelAttribute(): string
    {
        return $this->user?->name ?? $this->author_name ?? '알 수 없음';
    }

    /** 작성자 계정이 삭제되었는지 (화면에 '퇴사' 표기용) */
    public function getAuthorDeletedAttribute(): bool
    {
        return $this->user === null;
    }
}
