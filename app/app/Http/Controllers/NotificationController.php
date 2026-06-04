<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notificationService) {}

    /** 최근 알림 목록 (JSON) */
    public function index(): JsonResponse
    {
        $notifications = $this->notificationService->recent(Auth::id());
        return response()->json($notifications);
    }

    /** 단일 읽음 처리 */
    public function markRead(int $id): JsonResponse
    {
        $this->notificationService->markRead($id, Auth::id());
        return response()->json(['ok' => true]);
    }

    /** 전체 읽음 처리 */
    public function markAllRead(): JsonResponse
    {
        $this->notificationService->markAllRead(Auth::id());
        return response()->json(['ok' => true]);
    }

    /** 단일 알림 삭제 */
    public function destroy(int $id): JsonResponse
    {
        $this->notificationService->delete($id, Auth::id());
        return response()->json(['ok' => true]);
    }
}
