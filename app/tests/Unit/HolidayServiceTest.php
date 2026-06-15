<?php

namespace Tests\Unit;

use App\Services\HolidayService;
use PHPUnit\Framework\TestCase;

/**
 * 한국 공휴일(대체공휴일 포함) 조회 검증.
 * Yasumi(순수 PHP) 기반이라 Windows·Linux에서 동일한 결과를 가진다.
 */
class HolidayServiceTest extends TestCase
{
    private HolidayService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HolidayService();
    }

    /** 음력 공휴일(설날·추석)과 양력 공휴일이 모두 포함된다 */
    public function test_includes_solar_and_lunar_holidays(): void
    {
        $h = $this->service->between('2026-01-01', '2026-12-31');

        $this->assertArrayHasKey('2026-01-01', $h);   // 새해
        $this->assertArrayHasKey('2026-02-17', $h);   // 설날
        $this->assertArrayHasKey('2026-09-25', $h);   // 추석
        $this->assertSame('설날', $h['2026-02-17']);
        $this->assertSame('추석', $h['2026-09-25']);
    }

    /** 대체공휴일이 포함된다 (삼일절이 일요일 → 3/2 대체) */
    public function test_includes_substitute_holiday(): void
    {
        $h = $this->service->between('2026-03-01', '2026-03-31');

        $this->assertArrayHasKey('2026-03-02', $h);
        $this->assertStringContainsString('대체', $h['2026-03-02']);
    }

    /** 범위 밖 날짜는 제외된다 */
    public function test_filters_to_requested_range(): void
    {
        $h = $this->service->between('2026-05-01', '2026-05-31');

        $this->assertArrayHasKey('2026-05-05', $h);   // 어린이날
        $this->assertArrayNotHasKey('2026-06-06', $h); // 현충일(범위 밖)
        $this->assertArrayNotHasKey('2026-01-01', $h);
    }

    /** 연도 경계를 걸친 범위도 두 해를 합쳐 조회한다 */
    public function test_handles_cross_year_range(): void
    {
        $h = $this->service->between('2025-12-29', '2026-01-02');

        $this->assertArrayHasKey('2026-01-01', $h);   // 새해
    }
}
