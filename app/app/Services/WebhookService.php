<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /** Webhook 활성화 여부 */
    public function isEnabled(): bool
    {
        return Setting::get('webhook_enabled', '0') === '1'
            && !empty(Setting::get('webhook_url'));
    }

    /** 메시지 전송 (Slack / Mattermost 공통 포맷) */
    public function send(string $text): bool
    {
        if (!$this->isEnabled()) return false;

        $url = Setting::get('webhook_url');

        try {
            $response = Http::timeout(5)->post($url, ['text' => $text]);
            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('Webhook 전송 실패: ' . $e->getMessage());
            return false;
        }
    }

    /** 보고서 반려 알림 */
    public function notifyRejected(string $userName, string $week): void
    {
        $this->send("⚠️ **보고서 반려 알림**\n{$userName}님의 {$week} 주간보고가 반려되었습니다. 수정 후 재제출해 주세요.");
    }

    /** 미제출자 일괄 알림 */
    public function notifyNotSubmitted(array $userNames, string $weekLabel): void
    {
        if (empty($userNames)) return;
        $list = implode(', ', $userNames);
        $this->send("📋 **미제출 알림** — {$weekLabel}\n아직 주간보고를 제출하지 않은 팀원: {$list}");
    }
}
