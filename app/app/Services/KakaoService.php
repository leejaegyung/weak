<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KakaoService
{
    const AUTH_URL    = 'https://kauth.kakao.com/oauth/authorize';
    const TOKEN_URL   = 'https://kauth.kakao.com/oauth/token';
    const MESSAGE_URL = 'https://kapi.kakao.com/v2/api/talk/memo/default/send';
    const PROFILE_URL = 'https://kapi.kakao.com/v2/user/me';

    // ─── 앱 설정 (REST API 키) ────────────────────────────────────

    /** 사용자 OAuth 인증 URL 생성 (로그인 / 카카오 연결 공용) */
    public function getUserAuthUrl(string $redirectUri, bool $reauthenticate = false): string
    {
        $params = [
            'client_id'     => Setting::get('kakao_rest_api_key', ''),
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => 'profile_nickname,talk_message',
        ];
        // 회원가입 시 이전 카카오 세션 자동 사용 방지
        if ($reauthenticate) {
            $params['reauthenticate'] = 'true';
        }
        return self::AUTH_URL . '?' . http_build_query($params);
    }

    /** 인가 코드 → Access Token 교환 (사용자용) */
    public function exchangeUserCode(string $code, string $redirectUri): ?array
    {
        $apiKey = Setting::get('kakao_rest_api_key', '');
        Log::debug('[카카오] 토큰 교환 요청', [
            'redirect_uri' => $redirectUri,
            'client_id'    => $apiKey,
            'code_prefix'  => substr($code, 0, 8) . '...',
            'env'          => app()->environment(),
            'ssl_verify'   => !app()->environment('local'),
        ]);

        $params = [
            'grant_type'   => 'authorization_code',
            'client_id'    => $apiKey,
            'redirect_uri' => $redirectUri,
            'code'         => $code,
        ];

        // 클라이언트 시크릿이 설정된 경우 포함
        $clientSecret = Setting::get('kakao_client_secret', '');
        if (!empty($clientSecret)) {
            $params['client_secret'] = $clientSecret;
        }

        try {
            $response = $this->http()->asForm()->post(self::TOKEN_URL, $params);
            Log::debug('[카카오] 토큰 교환 응답', ['status' => $response->status(), 'body' => $response->body()]);
            if ($response->successful()) return $response->json();
            Log::warning('카카오 코드 교환 실패: ' . $response->body());
        } catch (\Throwable $e) {
            Log::error('카카오 코드 교환 예외: ' . $e->getMessage());
        }
        return null;
    }

    /** 카카오 프로필 조회 (id, nickname) */
    public function getUserProfile(string $accessToken): ?array
    {
        try {
            $response = $this->http()
                ->withToken($accessToken)
                ->get(self::PROFILE_URL);
            if ($response->successful()) return $response->json();
        } catch (\Throwable $e) {
            Log::error('카카오 프로필 조회 예외: ' . $e->getMessage());
        }
        return null;
    }

    // ─── 사용자별 메시지 발송 ─────────────────────────────────────

    /**
     * 특정 팀원에게 카카오 메시지 발송 (나에게 보내기 — 해당 사용자의 토큰 사용)
     * 토큰 만료(401) 시 자동 갱신 후 재시도
     */
    public function sendToUser(User $user, string $text): bool
    {
        if (empty($user->kakao_access_token)) return false;

        // $hidden 필드는 직접 쿼리로 가져와야 함
        $token = User::where('id', $user->id)->value('kakao_access_token');
        if (empty($token)) return false;

        $status = $this->doSend($token, $text);

        if ($status === 401) {
            if ($this->refreshUserToken($user)) {
                $token  = User::where('id', $user->id)->value('kakao_access_token');
                $status = $this->doSend($token, $text);
            }
        }

        return $status === 200;
    }

    /** 사용자 Access Token 갱신 */
    public function refreshUserToken(User $user): bool
    {
        $refreshToken = User::where('id', $user->id)->value('kakao_refresh_token');
        if (empty($refreshToken)) return false;

        try {
            $refreshParams = [
                'grant_type'    => 'refresh_token',
                'client_id'     => Setting::get('kakao_rest_api_key', ''),
                'refresh_token' => $refreshToken,
            ];
            $clientSecret = Setting::get('kakao_client_secret', '');
            if (!empty($clientSecret)) {
                $refreshParams['client_secret'] = $clientSecret;
            }
            $response = $this->http()->asForm()->post(self::TOKEN_URL, $refreshParams);

            if ($response->successful()) {
                $data = $response->json();
                $user->updateQuietly([
                    'kakao_access_token'  => $data['access_token'],
                    'kakao_refresh_token' => $data['refresh_token'] ?? $refreshToken,
                ]);
                return true;
            }
        } catch (\Throwable $e) {
            Log::error('카카오 사용자 토큰 갱신 예외: ' . $e->getMessage());
        }
        return false;
    }

    /** 사용자 카카오 연동 해제 */
    public function disconnectUser(User $user): void
    {
        $user->update([
            'kakao_id'            => null,
            'kakao_access_token'  => null,
            'kakao_refresh_token' => null,
        ]);
    }

    // ─── 공통 메시지 발송 ─────────────────────────────────────────

    /** HTTP 클라이언트 (로컬 환경에서 SSL 검증 비활성화) */
    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        $client = Http::timeout(10);
        if (app()->environment('local')) {
            $client = $client->withoutVerifying();
        }
        return $client;
    }

    private function doSend(string $accessToken, string $text): int
    {
        try {
            $templateObject = json_encode([
                'object_type' => 'text',
                'text'        => mb_substr($text, 0, 200), // 카카오 최대 200자
                'link'        => [
                    'web_url'        => config('app.url'),
                    'mobile_web_url' => config('app.url'),
                ],
            ], JSON_UNESCAPED_UNICODE);

            $response = $this->http()
                ->withHeaders(['Authorization' => 'Bearer ' . $accessToken])
                ->asForm()
                ->post(self::MESSAGE_URL, ['template_object' => $templateObject]);

            return $response->status();
        } catch (\Throwable $e) {
            Log::error('카카오 메시지 발송 예외: ' . $e->getMessage());
            return 500;
        }
    }
}
