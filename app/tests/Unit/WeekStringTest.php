<?php

namespace Tests\Unit;

use App\Services\ReportService;
use PHPUnit\Framework\TestCase;

/**
 * ISO 주차 문자열 생성 검증.
 * 연말·연초 경계를 걸친 한 주가 같은 주차 문자열로 안정적으로 유지되는지 확인한다.
 * (과거 버그: 달력 연도 format('Y')를 써서 1/1을 기점으로 주차 문자열이 어긋났음)
 */
class WeekStringTest extends TestCase
{
    private ReportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReportService();
    }

    /** 2024-12-30(월)~2025-01-05(일)은 ISO 2025-W01 — 주중 어느 날이든 동일해야 함 */
    public function test_week_spanning_new_year_is_stable(): void
    {
        $this->assertSame('2025-W01', $this->service->weekString('2024-12-30')); // 월
        $this->assertSame('2025-W01', $this->service->weekString('2025-01-01')); // 수 (연도 경계)
        $this->assertSame('2025-W01', $this->service->weekString('2025-01-05')); // 일
    }

    /** 53주차 연도(2026)의 마지막 주: 2026-12-28(월)~2027-01-03(일)은 ISO 2026-W53 */
    public function test_iso_53rd_week_uses_iso_year(): void
    {
        $this->assertSame('2026-W53', $this->service->weekString('2026-12-28')); // 월
        $this->assertSame('2026-W53', $this->service->weekString('2027-01-01')); // 금 (연도 경계)
    }

    /** 과거 버그(달력 연도)였다면 1/1 이전 날은 "2024-W01"이 됐을 것 — 회귀 방지 */
    public function test_does_not_use_calendar_year_at_boundary(): void
    {
        $this->assertNotSame('2024-W01', $this->service->weekString('2024-12-30'));
    }

    /** 형식은 항상 YYYY-Www, 같은 ISO 주의 평일은 모두 같은 문자열 */
    public function test_format_and_mid_week_consistency(): void
    {
        $mon = $this->service->weekString('2026-06-15');
        $this->assertMatchesRegularExpression('/^\d{4}-W\d{2}$/', $mon);
        $this->assertSame($mon, $this->service->weekString('2026-06-17')); // 같은 주 수요일
        $this->assertSame($mon, $this->service->weekString('2026-06-21')); // 같은 주 일요일
    }
}
