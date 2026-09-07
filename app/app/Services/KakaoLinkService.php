<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * 카카오 계정 ↔ 사내 계정 연결 서비스
 *
 * 카카오는 "가입 수단"이 아니라 "기존 계정에 붙이는 인증 수단"으로 취급한다.
 * 연동되지 않은 카카오로 로그인하면 계정을 자동 생성하지 않고, 본인 확인
 * (아이디·비밀번호)을 거쳐 기존 계정에 연결한다.
 *
 * 이름·닉네임 기반 자동 매칭은 절대 사용하지 않는다.
 * 카카오 닉네임은 누구나 바꿀 수 있어 타인 계정 탈취 경로가 되기 때문이다.
 */
class KakaoLinkService
{
    /** 연결 대기 정보를 담아두는 세션 키 */
    private const SESSION_KEY = 'kakao_pending_link';

    /** 연결 대기 정보 유효 시간 (분) */
    private const TTL_MINUTES = 10;

    /**
     * 카카오 인증 결과를 연결 대기 상태로 보관한다.
     *
     * @param array $kakao kakao_id, access_token, refresh_token, channel_uuid, nickname
     */
    public function stash(array $kakao): void
    {
        session([self::SESSION_KEY => [
            'kakao_id'      => $kakao['kakao_id'],
            'access_token'  => $kakao['access_token'] ?? '',
            'refresh_token' => $kakao['refresh_token'] ?? '',
            'channel_uuid'  => $kakao['channel_uuid'] ?? null,
            'nickname'      => $kakao['nickname'] ?? '카카오 사용자',
            'stashed_at'    => now()->timestamp,
        ]]);
    }

    /** 연결 대기 정보를 반환한다. 없거나 유효 시간이 지났으면 null */
    public function peek(): ?array
    {
        $pending = session(self::SESSION_KEY);

        if (!is_array($pending) || empty($pending['kakao_id'])) {
            return null;
        }

        if (now()->timestamp - ($pending['stashed_at'] ?? 0) > self::TTL_MINUTES * 60) {
            $this->forget();

            return null;
        }

        return $pending;
    }

    /** 연결 대기 정보를 폐기한다 */
    public function forget(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * 본인 확인 후 기존 계정에 카카오를 연결한다.
     *
     * @return array{success: bool, message: string, user: ?User}
     */
    public function linkToExistingAccount(array $pending, string $username, string $password): array
    {
        $user = User::where('username', $username)->first();

        // 아이디 존재 여부가 드러나지 않도록 실패 메시지를 하나로 통일한다
        if (!$user || !Hash::check($password, $user->password)) {
            return $this->fail('아이디 또는 비밀번호가 올바르지 않습니다.');
        }

        if ($user->registration_status === 'pending') {
            return $this->fail('관리자 승인 대기 중인 계정입니다. 승인 후 연결할 수 있습니다.');
        }

        if ($user->registration_status === 'rejected') {
            return $this->fail('가입이 거절된 계정입니다. 관리자에게 문의하세요.');
        }

        if (!$user->is_active) {
            return $this->fail('비활성화된 계정입니다. 관리자에게 문의하세요.');
        }

        $kakaoId = $pending['kakao_id'];

        // 이 계정에 이미 다른 카카오가 붙어 있는 경우
        if (!empty($user->kakao_id) && $user->kakao_id !== $kakaoId) {
            return $this->fail('이 계정에는 이미 다른 카카오 계정이 연결되어 있습니다. 개인설정에서 연결을 해제한 뒤 다시 시도하세요.');
        }

        // 연결 대기 중에 다른 계정이 같은 카카오를 선점한 경우
        if (User::where('kakao_id', $kakaoId)->where('id', '!=', $user->id)->exists()) {
            return $this->fail('이미 다른 계정에 연결된 카카오 계정입니다.');
        }

        $user->update([
            'kakao_id'            => $kakaoId,
            'kakao_access_token'  => $pending['access_token'],
            'kakao_refresh_token' => $pending['refresh_token'],
            'kakao_channel_uuid'  => $pending['channel_uuid'],
            'last_login_at'       => now(),
        ]);

        return ['success' => true, 'message' => '', 'user' => $user];
    }

    /**
     * 사내 계정이 없는 사람의 카카오 가입 신청을 만든다.
     * 관리자 승인 전까지는 로그인할 수 없다.
     */
    public function registerFromKakao(array $pending): User
    {
        return User::create([
            'name'                => $pending['nickname'],
            // 아이디 충돌을 피하기 위해 카카오 ID 기반으로 생성한다
            'username'            => 'kakao_' . $pending['kakao_id'],
            'password'            => Str::random(32),
            'role'                => 'user',
            'is_active'           => false,
            'registration_status' => 'pending',
            'kakao_id'            => $pending['kakao_id'],
            'kakao_access_token'  => $pending['access_token'],
            'kakao_refresh_token' => $pending['refresh_token'],
            'kakao_channel_uuid'  => $pending['channel_uuid'],
        ]);
    }

    /** @return array{success: bool, message: string, user: ?User} */
    private function fail(string $message): array
    {
        return ['success' => false, 'message' => $message, 'user' => null];
    }
}
