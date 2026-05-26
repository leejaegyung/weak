<?php

use App\Models\Setting;
use App\Services\WebhookService;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// ── 매일 지정 시간 팀 일정 Webhook 자동 발송 ──
Schedule::call(function () {
    $enabled = Setting::get('webhook_daily_enabled', '0');
    $time    = Setting::get('webhook_daily_time', '09:00');

    if ($enabled !== '1') return;

    $now = Carbon::now()->timezone('Asia/Seoul');

    // 설정 시간과 일치할 때만 발송
    if ($now->format('H:i') !== $time) return;

    // 주말(토·일)은 발송하지 않음
    if ($now->isWeekend()) return;

    app(WebhookService::class)->sendDailySchedule($now->toDateString());

})->everyMinute()->timezone('Asia/Seoul');
