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
        return redirect()->away($this->kakao->getUserAuthUrl($redirectUri));
    }

    /**
     * 카카오 OAuth 콜백
     * — intent=login  : 카카오 ID로 사용자 찾아 로그인
     * — intent=connect: 현재 로그인된 사용자에 카카오 연결
     */
    public function callback(Request $request): RedirectResponse
    {
        $intent = session()->pull('kakao_intent', 'login');

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
            ]);

            return redirect('/profile')->with('success', '✅ 카카오 계정이 연결되었습니다! 이제 카카오로 로그인할 수 있습니다.');
        }

        // ── 카카오 로그인 ────────────────────────────────────────────
        if (empty($kakaoId)) {
            return redirect('/login')->with('error', '카카오 프로필을 가져올 수 없습니다.');
        }

        $user = User::where('kakao_id', $kakaoId)->first();

        if (!$user) {
            return redirect('/login')->with('error', '연동된 계정이 없습니다. 일반 로그인 후 프로필 → 카카오 연결을 먼저 진행해 주세요.');
        }

        if (!$user->is_active) {
            return redirect('/login')->with('error', '비활성화된 계정입니다. 관리자에게 문의하세요.');
        }

        if ($user->registration_status !== 'approved') {
            return redirect('/login')->with('error', '아직 승인되지 않은 계정입니다.');
        }

        // 토큰 갱신 저장
        $user->updateQuietly([
            'kakao_access_token'  => $tokenData['access_token'],
            'kakao_refresh_token' => $tokenData['refresh_token'] ?? $user->getRawOriginal('kakao_refresh_token'),
            'last_login_at'       => now(),
        ]);

        auth()->login($user, true);
        $request->session()->regenerate();

        return redirect('/reports');
    }
}
