<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    /** 알림 생성 */
    public function create(int $userId, string $type, string $title, ?string $body = null, ?string $link = null): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'body'    => $body,
            'link'    => $link,
        ]);
    }

    /** 최근 알림 목록 (최대 20개, 삭제된 보고서 링크는 자동 제외) */
    public function recent(int $userId): array
    {
        return Notification::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(40) // 필터 후 20개를 맞추기 위해 여유있게 조회
            ->get()
            ->filter(function ($n) {
                // /reports/{id} 형식의 링크인 경우 보고서 존재 여부 확인
                if ($n->link && preg_match('/^\/reports\/(\d+)$/', $n->link, $m)) {
                    return \App\Models\WeeklyReport::where('id', $m[1])->exists();
                }
                return true;
            })
            ->take(20)
            ->map(fn($n) => [
                'id'         => $n->id,
                'type'       => $n->type,
                'title'      => $n->title,
                'body'       => $n->body,
                'link'       => $n->link,
                'is_read'    => $n->read_at !== null,
                'created_at' => $n->created_at->diffForHumans(),
            ])->values()->toArray();
    }

    /** 단일 알림 삭제 */
    public function delete(int $notificationId, int $userId): bool
    {
        return (bool) Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->delete();
    }

    /** 안 읽은 개수 */
    public function unreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)->whereNull('read_at')->count();
    }

    /** 단일 읽음 처리 */
    public function markRead(int $notificationId, int $userId): void
    {
        Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->update(['read_at' => now()]);
    }

    /** 전체 읽음 처리 */
    public function markAllRead(int $userId): void
    {
        Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
