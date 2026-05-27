import { chromium } from 'playwright'

const BASE = 'http://localhost:8001'

const browser = await chromium.launch({ headless: false, slowMo: 400 })
const page = await browser.newPage()
page.setDefaultTimeout(15000)

// ── 1. 로그인 ──────────────────────────────────────────
console.log('1. 로그인 페이지 접속')
await page.goto(BASE + '/login')
await page.waitForLoadState('networkidle')
await page.screenshot({ path: 'ss_01_login.png' })

// 계정 정보 입력 (첫 번째 활성 계정 사용)
await page.fill('input[name="username"], input[type="text"]', 'admin')
await page.fill('input[name="password"], input[type="password"]', 'password')
await page.screenshot({ path: 'ss_02_login_filled.png' })

await page.click('button[type="submit"]')
await page.waitForLoadState('networkidle')
await page.screenshot({ path: 'ss_03_after_login.png' })
console.log('   현재 URL:', page.url())

// ── 2. 보고서 작성 페이지 이동 ─────────────────────────
console.log('2. 보고서 작성 페이지')
await page.goto(BASE + '/reports/create')
await page.waitForLoadState('networkidle')
await page.screenshot({ path: 'ss_04_create_page.png' })

// ── 3. 내 주간 일정 섹션 열기 ──────────────────────────
console.log('3. 내 주간일정 토글 클릭')
const schedToggle = page.locator('text=내 주간 일정').first()
await schedToggle.click()
await page.waitForTimeout(400)
await page.screenshot({ path: 'ss_05_schedule_open.png' })

// ── 4. 일정 셀 클릭해서 모달 열기 ─────────────────────
console.log('4. 첫 번째 일정 셀 클릭 (금주 월요일)')
// 금주 행의 첫 번째 td 안의 클릭 가능한 div
const cells = page.locator('table tbody tr:first-child td:not(:first-child) div[style*="cursor:pointer"]')
const cellCount = await cells.count()
console.log('   셀 수:', cellCount)

if (cellCount > 0) {
  await cells.first().click()
  await page.waitForTimeout(400)
  await page.screenshot({ path: 'ss_06_modal_open.png' })

  // 상태 선택 (외근)
  await page.locator('text=외근').first().click()
  await page.waitForTimeout(200)
  await page.screenshot({ path: 'ss_07_status_selected.png' })

  // 저장
  await page.locator('button:has-text("저장")').last().click()
  await page.waitForTimeout(400)
  await page.screenshot({ path: 'ss_08_after_save.png' })
  console.log('   → 칩 저장 완료')
}

// ── 5. 칩 스타일 확인 ─────────────────────────────────
console.log('5. 칩 스타일 검증')
const chip = page.locator('table tbody tr:first-child td:not(:first-child) div[style*="width:100%"]').first()
const chipCount = await chip.count()
console.log('   width:100% 칩 개수:', chipCount)

if (chipCount > 0) {
  const box = await chip.boundingBox()
  console.log('   칩 너비(px):', box?.width)
  const style = await chip.getAttribute('style')
  console.log('   칩 스타일:', style?.substring(0, 120))
}

await page.screenshot({ path: 'ss_09_chip_final.png' })

// ── 6. 전체 스크롤샷 ──────────────────────────────────
await page.evaluate(() => window.scrollTo(0, 200))
await page.waitForTimeout(200)
await page.screenshot({ path: 'ss_10_schedule_section.png' })

console.log('\n✅ 테스트 완료 — 스크린샷 ss_01~10.png 저장됨')
await browser.close()
