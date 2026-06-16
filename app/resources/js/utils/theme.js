/**
 * 화면 테마(기본 / 다크) 관리 유틸
 *
 * - 'default' : 현재 크림색 기본 테마 (필터 없음)
 * - 'dark'    : 색조 보존 인버트 필터로 전 화면을 어둡게 (브랜드색 유지)
 *
 * 테마는 브라우저(localStorage)에 기기별로 저장된다. 서버에 저장하지 않으므로
 * 사용자/권한과 무관하게 이 브라우저에서만 적용된다.
 *
 * FOUC(깜빡임) 방지를 위해 최초 클래스 적용은 app.blade.php 인라인 스크립트가
 * 담당하고, 여기서는 런타임 토글·조회만 한다.
 */
import { ref } from 'vue'

const STORAGE_KEY = 'app-theme'
export const THEMES = ['default', 'dark']

function read() {
  try {
    return localStorage.getItem(STORAGE_KEY) === 'dark' ? 'dark' : 'default'
  } catch {
    return 'default'
  }
}

// 전역 반응형 상태 — 여러 컴포넌트가 같은 값을 공유
export const currentTheme = ref(read())

function apply(theme) {
  const isDark = theme === 'dark'
  document.documentElement.classList.toggle('theme-dark', isDark)
}

export function setTheme(theme) {
  const next = theme === 'dark' ? 'dark' : 'default'
  currentTheme.value = next
  try {
    if (next === 'default') localStorage.removeItem(STORAGE_KEY)
    else localStorage.setItem(STORAGE_KEY, next)
  } catch { /* 저장 불가 시 무시 */ }
  apply(next)
}

// 앱 부팅 시 1회 동기화 (블레이드 스크립트와 상태 일치 보장)
export function initTheme() {
  apply(currentTheme.value)
}
