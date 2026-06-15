<?php

namespace App\Services;

use Carbon\Carbon;
use Yasumi\Holiday;
use Yasumi\Yasumi;

/**
 * 한국 공휴일(대체공휴일 포함) 조회 서비스.
 *
 * Yasumi(순수 PHP) 기반이라 OS의 ICU/locale에 의존하지 않으며
 * Windows·Linux에서 동일한 결과를 보장한다. 음력 공휴일(설날·추석·
 * 부처님오신날)과 대체공휴일 규칙이 라이브러리에 내장돼 있다.
 */
class HolidayService
{
    /**
     * 날짜 범위(Y-m-d ~ Y-m-d, 양끝 포함)의 공휴일 맵을 반환.
     * 관공서가 쉬는 '공휴일'(TYPE_OFFICIAL)만 포함하며 대체공휴일도 포함된다.
     *
     * @return array<string,string> [ 'YYYY-MM-DD' => '공휴일명' ]
     */
    public function between(string $start, string $end): array
    {
        $startDate = Carbon::parse($start)->format('Y-m-d');
        $endDate   = Carbon::parse($end)->format('Y-m-d');
        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $map      = [];
        $startYr  = (int) substr($startDate, 0, 4);
        $endYr    = (int) substr($endDate, 0, 4);

        for ($year = $startYr; $year <= $endYr; $year++) {
            $provider = Yasumi::create('SouthKorea', $year, 'ko_KR');
            foreach ($provider->getHolidays() as $holiday) {
                if ($holiday->getType() !== Holiday::TYPE_OFFICIAL) {
                    continue; // 공휴일(쉬는 날)만 표기
                }
                $date = $holiday->format('Y-m-d');
                if ($date >= $startDate && $date <= $endDate) {
                    $map[$date] = $holiday->getName();
                }
            }
        }

        ksort($map);

        return $map;
    }
}
