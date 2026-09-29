<?php

namespace Tests\Feature;

use App\Services\WebhookService;
use ReflectionMethod;
use Tests\TestCase;

/**
 * 당일 근무 현황 표(Webhook 전용) 렌더링 검증.
 *
 * 카카오는 마크다운 표를 렌더링하지 못하므로 줄글 형식(buildDailyMessage)을 그대로 쓴다.
 * 이 테스트는 표 형식만 확인한다.
 */
class WebhookDailyTableTest extends TestCase
{
    private function render(array $groups, string $date = '2026-09-29'): ?string
    {
        $method = new ReflectionMethod(WebhookService::class, 'renderDailyTable');
        $method->setAccessible(true);

        return $method->invoke(new WebhookService(), $groups, $date);
    }

    /** 한 사람의 하루 일정 한 건 */
    private function person(string $name, string $time = '종일', array $sites = []): array
    {
        return [
            'name'  => $name,
            'times' => [$time],
            'sites' => array_map(fn($s) => ['site' => $s, 'time' => $time], $sites),
        ];
    }

    /** 같은 사람이 오전 반차 + 오후 휴가처럼 섞여 들어오면 한 줄로 합친다 */
    public function test_한_사람이_휴가와_반차에_모두_있으면_한_줄로_합친다(): void
    {
        $out = $this->render([
            '휴가' => ['김명현' => $this->person('김명현', '오전')],
            '반차' => ['김명현' => $this->person('김명현', '오후')],
        ]);

        $this->assertStringContainsString('#### 🌴 휴가 · 1명', $out);
        $this->assertStringContainsString('| 김명현 | 반차 오전, 오후 |', $out);
    }

    public function test_상태별로_제목과_인원수를_낸다(): void
    {
        $out = $this->render([
            '외근' => ['신기철' => $this->person('신기철', '종일', ['영주시청'])],
            '휴가' => ['설지섭' => $this->person('설지섭')],
        ]);

        $this->assertStringContainsString('### 🗓️ 금일 근무 현황 — 9월 29일(화)', $out);
        $this->assertStringContainsString('#### 🏢 외근 · 1명', $out);
        $this->assertStringContainsString('#### 🌴 휴가 · 1명', $out);
    }

    public function test_외근은_장소_컬럼을_쓴다(): void
    {
        $out = $this->render(['외근' => ['신기철' => $this->person('신기철', '종일', ['영주시청'])]]);

        $this->assertStringContainsString('| 인원 | 장소 / 내용 |', $out);
        $this->assertStringContainsString('| --- | --- |', $out);
        $this->assertStringContainsString('| 신기철 | 영주시청 |', $out);
    }

    public function test_반일_외근은_이름_뒤에_시간대를_붙인다(): void
    {
        $out = $this->render(['외근' => ['김선호' => $this->person('김선호', '오후', ['SMC 서울시의회'])]]);

        $this->assertStringContainsString('| 김선호 `오후` | SMC 서울시의회 |', $out);
    }

    public function test_반차는_휴가_표_안에_들어간다(): void
    {
        $out = $this->render([
            '반차' => ['김명현' => $this->person('김명현', '오후')],
            '휴가' => ['설지섭' => $this->person('설지섭')],
        ]);

        // 반차 구역은 따로 만들지 않는다
        $this->assertStringNotContainsString('#### 🕐 반차', $out);
        $this->assertStringContainsString('#### 🌴 휴가 · 2명', $out);
        $this->assertStringContainsString('| 인원 | 시간 |', $out);
        $this->assertStringContainsString('| 설지섭 | 종일 |', $out);
        $this->assertStringContainsString('| 김명현 | 반차 오후 |', $out);
    }

    public function test_반차만_있어도_휴가_구역으로_낸다(): void
    {
        $out = $this->render(['반차' => ['김명현' => $this->person('김명현', '오전')]]);

        $this->assertStringContainsString('#### 🌴 휴가 · 1명', $out);
        $this->assertStringContainsString('| 김명현 | 반차 오전 |', $out);
    }

    public function test_오전_오후가_다른_장소면_장소마다_시간대를_붙인다(): void
    {
        $out = $this->render(['외근' => ['박성원' => [
            'name'  => '박성원',
            'times' => ['오후', '오전'],
            'sites' => [
                ['site' => 'KBS DRS', 'time' => '오후'],
                ['site' => 'MBC 상암', 'time' => '오전'],
            ],
        ]]]);

        // 이름에는 칩을 붙이지 않고, 장소를 오전 → 오후 순으로 정렬해 각각 표기한다
        $this->assertStringContainsString('| 박성원 | `오전` MBC 상암, `오후` KBS DRS |', $out);
    }

    public function test_장소가_없는_외근은_대시로_채운다(): void
    {
        $out = $this->render(['외근' => ['홍길동' => $this->person('홍길동')]]);

        $this->assertStringContainsString('| 홍길동 | - |', $out);
    }

    public function test_파이프_문자가_표를_깨지_않는다(): void
    {
        $out = $this->render(['외근' => ['홍길동' => $this->person('홍길동', '종일', ['A동 | B동'])]]);

        $this->assertStringContainsString('| 홍길동 | A동 \\| B동 |', $out);
    }

    public function test_구역_순서는_외근_출장_휴가_이다(): void
    {
        $out = $this->render([
            '휴가' => ['설지섭' => $this->person('설지섭')],
            '외근' => ['신기철' => $this->person('신기철', '종일', ['영주시청'])],
            '반차' => ['김명현' => $this->person('김명현', '오후')],
            '출장' => ['서충희' => $this->person('서충희', '종일', ['EBS'])],
        ]);

        $order = array_map(
            fn($label) => mb_strpos($out, '#### ' . $label),
            ['🏢 외근', '✈️ 출장', '🌴 휴가'],
        );

        $sorted = $order;
        sort($sorted);
        $this->assertSame($sorted, $order, '구역 순서가 외근 · 출장 · 휴가 가 아닙니다');
    }

    /** 일정 원문을 buildUserDayMessage 와 같은 경로로 표까지 렌더링한다 */
    private function renderRaw(string $content, string $title = '### 📅 일정 변경 — 9월 29일(화)'): ?string
    {
        $svc = new WebhookService();

        $parse = new ReflectionMethod(WebhookService::class, 'parseContent');
        $parse->setAccessible(true);
        $entries = $parse->invoke($svc, $content)['entries'];

        $add = new ReflectionMethod(WebhookService::class, 'addToGroups');
        $add->setAccessible(true);
        $groups = [];
        $add->invokeArgs($svc, [&$groups, '이재경', $entries]);

        $render = new ReflectionMethod(WebhookService::class, 'renderDailyTable');
        $render->setAccessible(true);

        return $render->invoke($svc, $groups, '2026-09-29', $title);
    }

    public function test_개인_일정_알림도_같은_표_형식이다(): void
    {
        $out = $this->renderRaw('[오후]외근:Talos 재난 KBS');

        $this->assertStringContainsString('### 📅 일정 변경 — 9월 29일(화)', $out);
        $this->assertStringContainsString('#### 🏢 외근 · 1명', $out);
        $this->assertStringContainsString('| 인원 | 장소 / 내용 |', $out);
        $this->assertStringContainsString('| 이재경 `오후` | Talos 재난 KBS |', $out);
    }

    public function test_개인_일정_알림의_반차도_휴가_구역으로_간다(): void
    {
        $out = $this->renderRaw('[오후]반차');

        $this->assertStringContainsString('#### 🌴 휴가 · 1명', $out);
        $this->assertStringContainsString('| 이재경 | 반차 오후 |', $out);
    }
}
