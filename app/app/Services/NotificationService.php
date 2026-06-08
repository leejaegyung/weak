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

    /** 최근 알림 목록 (최대 20개) */
    public function recent(int $userId): array
    {
        return Notification::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(function ($n) {
                $link = $n->link;
                // 삭제된 보고서 링크는 null로 무효화 — 알림 자체는 항상 표시
                if ($link && preg_match('/^\/reports\/(\d+)$/', $link, $m)) {
                    if (!\App\Models\WeeklyReport::where('id', $m[1])->exists()) {
                        $link = null;
                    }
                }
                return [
                    'id'         => $n->id,
                    'type'       => $n->type,
                    'title'      => $n->title,
                    'body'       => $n->body,
                    'link'       => $link,
                    'is_read'    => $n->read_at !== null,
                    'created_at' => $n->created_at->diffForHumans(),
                ];
            })->values()->toArray();
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
