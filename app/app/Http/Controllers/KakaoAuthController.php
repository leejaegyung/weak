<?php

namespace App\Http\Controllers;

use App\Http\Requests\KakaoLinkRequest;
use App\Models\Setting;
use App\Models\User;
use App\Services\KakaoLinkService;
use App\Services\KakaoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class KakaoAuthController extends Controller
{
    /** 계정 연결 시 비밀번호 시도 허용 횟수 (아이디 + IP 기준) */
    private const LINK_MAX_ATTEMPTS = 5;

    /** 시도 횟수 초기화까지의 시간 (초) */
    private const LINK_DECAY_SECONDS = 60;

    public function __construct(
        private KakaoService $kakao,
        private KakaoLinkService $linkService,
    ) {}

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

        // ── 연동 계정 없음 → 계정을 만들지 않고 본인 확인 화면으로 ──────
        // 카카오 닉네임은 누구나 바꿀 수 있어 이름만으로 기존 계정을 찾아주면
        // 타인 계정을 가로챌 수 있다. 반드시 아이디·비밀번호로 본인을 확인한다.
        if (!$user) {
            $this->linkService->stash([
                'kakao_id'      => $kakaoId,
                'access_token'  => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? '',
                'channel_uuid'  => $channelUuid,
                'nickname'      => $profile['properties']['nickname']
                                ?? $profile['kakao_account']['profile']['nickname']
                                ?? '카카오 사용자',
            ]);

            return redirect()->route('auth.kakao.link');
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

    /**
     * 카카오 계정 연결 안내 화면
     * 아직 어느 계정에도 연결되지 않은 카카오로 로그인했을 때 표시한다.
     */
    public function showLink(): Response|RedirectResponse
    {
        $pending = $this->linkService->peek();

        if (!$pending) {
            return $this->expired();
        }

        return Inertia::render('Auth/KakaoLink', [
            'nickname' => $pending['nickname'],
        ]);
    }

    /**
     * 본인 확인 후 기존 계정에 카카오 연결
     * 비밀번호를 아는 사람만 계정을 가져갈 수 있도록 아이디·비밀번호를 검증한다.
     */
    public function link(KakaoLinkRequest $request): RedirectResponse
    {
        $pending = $this->linkService->peek();

        if (!$pending) {
            return $this->expired();
        }

        // 비밀번호 무차별 대입 차단 (아이디 + IP 기준)
        $throttleKey = 'kakao-link:' . Str::lower($request->input('username')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::LINK_MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'username' => "시도 횟수를 초과했습니다. {$seconds}초 후 다시 시도해 주세요.",
            ])->onlyInput('username');
        }

        $result = $this->linkService->linkToExistingAccount(
            $pending,
            $request->input('username'),
            $request->input('password'),
        );

        if (!$result['success']) {
            RateLimiter::hit($throttleKey, self::LINK_DECAY_SECONDS);

            return back()->withErrors(['username' => $result['message']])->onlyInput('username');
        }

        RateLimiter::clear($throttleKey);
        $this->linkService->forget();

        $user = $result['user'];
        auth()->login($user, true);
        $request->session()->regenerate();

        return redirect($user->defaultPage())
            ->with('success', '카카오 계정이 연결되었습니다. 다음부터 카카오로 바로 로그인할 수 있습니다.');
    }

    /**
     * 사내 계정이 없는 사람의 카카오 가입 신청
     * 관리자 승인 후에야 로그인할 수 있다.
     */
    public function registerFromKakao(): RedirectResponse
    {
        $pending = $this->linkService->peek();

        if (!$pending) {
            return $this->expired();
        }

        $user = $this->linkService->registerFromKakao($pending);
        $this->linkService->forget();

        return redirect('/login')->with('success',
            "카카오 가입 신청이 완료되었습니다! ({$user->name}) 관리자 승인 후 카카오 로그인이 가능합니다."
        );
    }

    /** 연결 대기 정보가 없거나 만료됐을 때의 공통 응답 */
    private function expired(): RedirectResponse
    {
        return redirect('/login')->with('error', '카카오 인증 정보가 만료되었습니다. 다시 시도해 주세요.');
    }
}
