<?php

namespace App\Http\Middleware;

use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id'       => $request->user()->id,
                    'name'     => $request->user()->name,
                    'username' => $request->user()->username,
                    'role'     => $request->user()->role,
                    'position' => $request->user()->position,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],
            'currentWeek'  => fn () => $this->getCurrentWeekData(),
            'pendingCount' => fn () => $request->user()?->isAdmin()
                ? User::where('registration_status', 'pending')->count()
                : 0,
            'unreadCount'  => fn () => $request->user()
                ? Notification::where('user_id', $request->user()->id)->whereNull('read_at')->count()
                : 0,
        ];
    }

    private function getCurrentWeekData(): array
    {
        $now     = Carbon::now();
        $monday  = $now->copy()->startOfWeek(Carbon::MONDAY);
        $friday  = $monday->copy()->addDays(4);

        $month       = $monday->month;
        $weekOfMonth = (int) ceil($monday->day / 7);

        // 오늘이 주중 몇 번째 날인지 (1=월 ~ 5=금, 주말은 5)
        $dayOfWeek = min($now->dayOfWeekIso, 5);

        return [
            'label'      => $month . '월 ' . $weekOfMonth . '주차',
            'dateRange'  => $monday->format('m/d') . ' - ' . $friday->format('m/d'),
            'curr_start' => $monday->format('Y-m-d'),
            'curr_end'   => $friday->format('Y-m-d'),
            'dayOfWeek'  => $dayOfWeek,   // 1~5
            'progress'   => ($dayOfWeek / 5) * 100,
        ];
    }
}
