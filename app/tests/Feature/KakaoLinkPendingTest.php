<?php

namespace Tests\Feature;

use App\Services\KakaoLinkService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * 카카오 연결 대기 정보의 보관·만료 검증.
 *
 * 이 정보에는 카카오 액세스 토큰이 들어 있고, 이 값을 들고 있는 세션은
 * 비밀번호만 맞히면 임의의 계정에 카카오를 붙일 수 있다.
 * 따라서 유효 시간이 지나면 반드시 사라져야 한다.
 */
class KakaoLinkPendingTest extends TestCase
{
    private KakaoLinkService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new KakaoLinkService();
    }

    public function test_보관한_정보를_그대로_돌려준다(): void
    {
        $this->service->stash([
            'kakao_id'      => '123456789',
            'access_token'  => 'access-token',
            'refresh_token' => 'refresh-token',
            'channel_uuid'  => 'channel-uuid',
            'nickname'      => '김성민',
        ]);

        $pending = $this->service->peek();

        $this->assertNotNull($pending);
        $this->assertSame('123456789', $pending['kakao_id']);
        $this->assertSame('access-token', $pending['access_token']);
        $this->assertSame('김성민', $pending['nickname']);
    }

    public function test_보관한_적이_없으면_null_이다(): void
    {
        $this->assertNull($this->service->peek());
    }

    public function test_유효_시간이_지나면_사라진다(): void
    {
        $this->service->stash([
            'kakao_id'     => '123456789',
            'access_token' => 'access-token',
            'nickname'     => '김성민',
        ]);

        // 유효 시간(10분)을 막 넘긴 시점
        Carbon::setTestNow(now()->addMinutes(11));

        $this->assertNull($this->service->peek());
        // 만료된 정보는 세션에서도 제거되어야 한다
        $this->assertNull(session('kakao_pending_link'));

        Carbon::setTestNow();
    }

    public function test_유효_시간_이내면_유지된다(): void
    {
        $this->service->stash([
            'kakao_id'     => '123456789',
            'access_token' => 'access-token',
            'nickname'     => '김성민',
        ]);

        Carbon::setTestNow(now()->addMinutes(9));

        $this->assertNotNull($this->service->peek());

        Carbon::setTestNow();
    }

    public function test_폐기하면_더_이상_읽히지_않는다(): void
    {
        $this->service->stash([
            'kakao_id'     => '123456789',
            'access_token' => 'access-token',
            'nickname'     => '김성민',
        ]);

        $this->service->forget();

        $this->assertNull($this->service->peek());
    }
}
