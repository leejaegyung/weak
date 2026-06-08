<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use App\Services\KakaoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class KakaoAuthController extends Controller
{
    public function __construct(private KakaoService $kakao) {}

    /**
     * 카카오 OAuth 시작
     * ?intent=login  — 로그인 (게스트도 접근 가능)
     * ?intent=connect — 기존 계정에 카카오 연결 (로그인 필요)
     */
    public function redirect(Request $request): RedirectResponse
    {
        $intent = $request->query('intent', 'login');

        if (empty(Setting::get('kakao_rest_api_key'))) {
            $to = $intent === 'connect' ? '/profile' : '/login';
            return redirect($to)->with('error', '카카오 앱이 아직 설정되지 않았습니다. 관리자에게 문의하세요.');
        }

        if ($intent === 'connect') {
            if (!auth()->check()) return redirect('/login');
            session(['kakao_connect_user_id' => auth()->id()]);
        }

        session(['kakao_intent' => $intent]);

        $redirectUri = route('auth.kakao.callback');
        // register intent: 카카오 계정 재선택 강제 (이전 로그인 세션 자동 사용 방지)
        $reauthenticate = $intent === 'register';
        return redirect()->away($this->kakao->getUserAuthUrl($redirectUri, $reauthenticate));
    }

    /**
     * 카카오 OAuth 콜백
     * — intent=login  : 카카오 ID로 사용자 찾아 로그인
     * — intent=connect: 현재 로그인된 사용자에 카카오 연결
     */
    public function callback(Request $request): RedirectResponse
    {
        $intent = session()->pull('kakao_intent', 'login');
        // register는 login과 동일하게 처리
        if ($intent === 'register') $intent = 'login';

        // 사용자가 취소하거나 에러 발생
        if ($request->has('error')) {
            $to = $intent === 'connect' ? '/profile' : '/login';
            return redirect($to)->with('error', '카카오 인증이 취소되었습니다.');
        }

        $code        = $request->input('code');
        $redirectUri = route('auth.kakao.callback');

        // 인가 코드 → 토큰 교환
        $tokenData = $this->kakao->exchangeUserCode($code, $redirectUri);
        if (!$tokenData) {
            $to = $intent === 'connect' ? '/profile' : '/login';
            return redirect($to)->with('error', '카카오 인증에 실패했습니다. 다시 시도해 주세요.');
        }

        // 카카오 프로필 (ID) 조회
        $profile = $this->kakao->getUserProfile($tokenData['access_token']);
        $kakaoId = $profile ? (string) ($profile['id'] ?? '') : '';

        // 채널 UUID 조회 (채널 공개 ID 설정 시 자동 수집)
        $channelUuid     = null;
        $channelPublicId = Setting::get('kakao_channel_public_id', '');
        if (!empty($channelPublicId)) {
            $channelUuid = $this->kakao->getUserChannelUuid($tokenData['access_token'], $channelPublicId);
        }

        // ── 카카오 연결 (기존 계정에 연동) ──────────────────────────
        if ($intent === 'connect') {
            $userId = session()->pull('kakao_connect_user_id');
            $user   = $userId ? User::find($userId) : auth()->user();

            if (!$user) {
                return redirect('/profile')->with('error', '사용자 정보를 찾을 수 없습니다.');
            }

            // 다른 계정에 이미 연결된 kakao_id 인지 확인
            if ($kakaoId && User::where('kakao_id', $kakaoId)->where('id', '!=', $user->id)->exists()) {
                return redirect('/profile')->with('error', '이미 다른 계정에 연결된 카카오 계정입니다.');
            }

            $user->update([
                'kakao_id'            => $kakaoId ?: null,
                'kakao_access_token'  => $tokenData['access_token'],
                'kakao_refresh_token' => $tokenData['refresh_token'] ?? '',
                'kakao_channel_uuid'  => $channelUuid,
            ]);

            $msg = $channelUuid
                ? '✅ 카카오 계정이 연결되었습니다! 채널 알림도 수신됩니다.'
                : '✅ 카카오 계정이 연결되었습니다! 이제 카카오로 로그인할 수 있습니다.';
            return redirect('/profile')->with('success', $msg);
        }

        // ── 카카오 로그인 ────────────────────────────────────────────
        if (empty($kakaoId)) {
            return redirect('/login')->with('error', '카카오 프로필을 가져올 수 없습니다.');
        }

        $user = User::where('kakao_id', $kakaoId)->first();

        // ── 연동 계정 없음 → 카카오 정보로 자동 가입 신청 ──────────────
        if (!$user) {
            $nickname = $profile['properties']['nickname']
                     ?? $profile['kakao_account']['profile']['nickname']
                     ?? '카카오사용자';

            // kakao_{id} 형태로 고유 username 생성
            $username = 'kakao_' . $kakaoId;

            User::create([
                'name'                => $nickname,
                'username'            => $username,
                'password'            => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(32)),
                'role'                => 'user',
                'is_active'           => false,
                'registration_status' => 'pending',
                'kakao_id'            => $kakaoId,
                'kakao_access_token'  => $tokenData['access_token'],
                'kakao_refresh_token' => $tokenData['refresh_token'] ?? '',
                'kakao_channel_uuid'  => $channelUuid,
            ]);

            return redirect('/login')->with('success',
                "카카오 가입 신청이 완료되었습니다! ({$nickname}) 관리자 승인 후 카카오 로그인이 가능합니다."
            );
        }

        if (!$user->is_active) {
            return redirect('/login')->with('error', '비활성화된 계정입니다. 관리자에게 문의하세요.');
        }

        if ($user->registration_status === 'pending') {
            return redirect('/login')->with('error', '가입 신청이 아직 승인 대기 중입니다. 관리자 승인 후 이용할 수 있습니다.');
        }

        if ($user->registration_status !== 'approved') {
            return redirect('/login')->with('error', '가입이 거절된 계정입니다. 관리자에게 문의하세요.');
        }

        // 토큰 및 채널 UUID 갱신 저장
        $user->updateQuietly([
            'kakao_access_token'  => $tokenData['access_token'],
            'kakao_refresh_token' => $tokenData['refresh_token'] ?? $user->getRawOriginal('kakao_refresh_token'),
            'kakao_channel_uuid'  => $channelUuid ?? $user->getRawOriginal('kakao_channel_uuid'),
            'last_login_at'       => now(),
        ]);

        auth()->login($user, true);
        $request->session()->regenerate();

        return redirect('/reports');
    }
}
