<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KakaoService
{
    const AUTH_URL    = 'https://kauth.kakao.com/oauth/authorize';
    const TOKEN_URL   = 'https://kauth.kakao.com/oauth/token';
    const MESSAGE_URL = 'https://kapi.kakao.com/v2/api/talk/memo/default/send';

    /** 연동 여부 */
    public function isConnected(): bool
    {
        return !empty(Setting::get('kakao_access_token'));
    }

    /** OAuth 인증 URL 생성 */
    public function getAuthUrl(string $redirectUri): string
    {
        $params = http_build_query([
            'client_id'     => Setting::get('kakao_rest_api_key', ''),
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => 'talk_message',
        ]);
        return self::AUTH_URL . '?' . $params;
    }

    /** 인가 코드 → Access Token 교환 */
    public function exchangeCode(string $code, string $redirectUri): bool
    {
        try {
            $response = Http::timeout(10)->asForm()->post(self::TOKEN_URL, [
                'grant_type'   => 'authorization_code',
                'client_id'    => Setting::get('kakao_rest_api_key', ''),
                'redirect_uri' => $redirectUri,
                'code'         => $code,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Setting::set('kakao_access_token',  $data['access_token']  ?? '');
                Setting::set('kakao_refresh_token', $data['refresh_token'] ?? '');
                Setting::set('kakao_connected_at',  now()->format('Y-m-d H:i:s'));
                return true;
            }
            Log::warning('카카오 코드 교환 실패: ' . $response->body());
        } catch (\Throwable $e) {
            Log::error('카카오 토큰 교환 예외: ' . $e->getMessage());
        }
        return false;
    }

    /** Access Token 갱신 */
    public function refreshToken(): bool
    {
        $refreshToken = Setting::get('kakao_refresh_token', '');
        if (empty($refreshToken)) return false;

        try {
            $response = Http::timeout(10)->asForm()->post(self::TOKEN_URL, [
                'grant_type'    => 'refresh_token',
                'client_id'     => Setting::get('kakao_rest_api_key', ''),
                'refresh_token' => $refreshToken,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Setting::set('kakao_access_token', $data['access_token']);
                if (!empty($data['refresh_token'])) {
                    Setting::set('kakao_refresh_token', $data['refresh_token']);
                }
                return true;
            }
        } catch (\Throwable $e) {
            Log::error('카카오 토큰 갱신 예외: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * 나에게 보내기 (관리자 카카오 계정으로 메시지 발송)
     * — 토큰 만료(401) 시 자동 갱신 후 재시도
     */
    public function sendMessage(string $text): bool
    {
        $accessToken = Setting::get('kakao_access_token', '');
        if (empty($accessToken)) return false;

        $result = $this->doSend($accessToken, $text);

        if ($result === 401) {
            // 토큰 만료 → 갱신 후 재시도
            if ($this->refreshToken()) {
                $accessToken = Setting::get('kakao_access_token', '');
                $result = $this->doSend($accessToken, $text);
            }
        }

        return $result === 200;
    }

    private function doSend(string $accessToken, string $text): int
    {
        try {
            $templateObject = json_encode([
                'object_type' => 'text',
                'text'        => $text,
                'link'        => [
                    'web_url'        => config('app.url'),
                    'mobile_web_url' => config('app.url'),
                ],
            ], JSON_UNESCAPED_UNICODE);

            $response = Http::timeout(10)
                ->withHeaders(['Authorization' => 'Bearer ' . $accessToken])
                ->asForm()
                ->post(self::MESSAGE_URL, ['template_object' => $templateObject]);

            return $response->status();
        } catch (\Throwable $e) {
            Log::error('카카오 메시지 발송 예외: ' . $e->getMessage());
            return 500;
        }
    }

    /** 연동 해제 */
    public function disconnect(): void
    {
        Setting::set('kakao_access_token',  '');
        Setting::set('kakao_refresh_token', '');
        Setting::set('kakao_connected_at',  '');
    }
}
