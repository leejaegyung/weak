/**
 * v-tooltip — 네오브루탈리즘 스타일 커스텀 툴팁 디렉티브
 *
 * 네이티브 `title` 속성은 CSS 스타일링이 불가능하므로, 앱 디자인(두꺼운
 * 검정 테두리 + 그림자)에 맞춘 단일 툴팁 엘리먼트를 body에 띄워 재사용한다.
 *
 * 사용법:
 *   <button v-tooltip="'드래그하여 순서 변경'">…</button>
 *   <span v-tooltip="dynamicText">…</span>
 *   <span v-tooltip.bottom="'아래에 표시'">…</span>   (기본은 위쪽 표시)
 *
 * 빈 문자열/falsy 값이면 툴팁을 표시하지 않는다.
 */

let tipEl = null          // 싱글톤 툴팁 엘리먼트
let arrowEl = null        // 화살표 엘리먼트
let hideTimer = null
let activeEl = null       // 현재 호버 중인 대상 (스크롤/리사이즈 시 추적)

const GAP = 9             // 대상과 툴팁 사이 간격(px)

function ensureTip() {
  if (tipEl) return
  tipEl = document.createElement('div')
  tipEl.setAttribute('role', 'tooltip')
  Object.assign(tipEl.style, {
    position: 'fixed',
    zIndex: '9999',
    maxWidth: '280px',
    padding: '7px 11px',
    background: '#1A1100',
    color: '#FFF8EE',
    border: '2px solid #1A1100',
    borderRadius: '9px',
    boxShadow: '3px 3px 0 rgba(26,17,0,0.28)',
    fontSize: '12px',
    fontWeight: '600',
    lineHeight: '1.45',
    fontFamily: "'Noto Sans KR','Space Grotesk',sans-serif",
    letterSpacing: '-0.01em',
    whiteSpace: 'normal',
    wordBreak: 'break-word',
    pointerEvents: 'none',
    opacity: '0',
    transform: 'translateY(2px)',
    transition: 'opacity 0.12s ease, transform 0.12s ease',
  })

  arrowEl = document.createElement('div')
  Object.assign(arrowEl.style, {
    position: 'absolute',
    width: '9px',
    height: '9px',
    background: '#1A1100',
    border: '2px solid #1A1100',
    borderTop: 'none',
    borderLeft: 'none',
    transform: 'rotate(45deg)',
  })
  tipEl.appendChild(arrowEl)

  // 텍스트 노드는 별도 span에 (화살표와 분리)
  tipEl._text = document.createElement('span')
  tipEl.insertBefore(tipEl._text, arrowEl)

  document.body.appendChild(tipEl)
}

function position(el, placement) {
  const r = el.getBoundingClientRect()
  const tr = tipEl.getBoundingClientRect()
  const vw = window.innerWidth
  const vh = window.innerHeight

  // 위쪽 공간이 부족하면 자동으로 아래로 뒤집기
  let placeBottom = placement === 'bottom'
  if (!placeBottom && r.top - tr.height - GAP < 4) placeBottom = true
  if (placeBottom && r.bottom + tr.height + GAP > vh - 4) placeBottom = false

  // 가로: 대상 중앙 정렬 후 화면 안으로 보정
  let left = r.left + r.width / 2 - tr.width / 2
  left = Math.max(6, Math.min(left, vw - tr.width - 6))

  const top = placeBottom ? r.bottom + GAP : r.top - tr.height - GAP

  tipEl.style.left = `${Math.round(left)}px`
  tipEl.style.top = `${Math.round(top)}px`

  // 화살표: 대상 중앙을 가리키도록 가로 위치 계산
  const arrowLeft = r.left + r.width / 2 - left - 4.5
  arrowEl.style.left = `${Math.max(8, Math.min(arrowLeft, tr.width - 17))}px`
  if (placeBottom) {
    arrowEl.style.top = '-6px'
    arrowEl.style.bottom = ''
    arrowEl.style.transform = 'rotate(225deg)'
  } else {
    arrowEl.style.top = ''
    arrowEl.style.bottom = '-6px'
    arrowEl.style.transform = 'rotate(45deg)'
  }
}

function show(el) {
  const text = el._tooltipText
  if (!text) return
  clearTimeout(hideTimer)
  ensureTip()
  activeEl = el
  tipEl._text.textContent = text
  // 먼저 보이게 한 뒤(크기 측정 가능) 위치 계산
  tipEl.style.opacity = '0'
  tipEl.style.transform = 'translateY(2px)'
  position(el, el._tooltipPlacement)
  // 다음 프레임에 페이드인
  requestAnimationFrame(() => {
    tipEl.style.opacity = '1'
    tipEl.style.transform = 'translateY(0)'
  })
}

function hide() {
  activeEl = null
  if (!tipEl) return
  tipEl.style.opacity = '0'
  tipEl.style.transform = 'translateY(2px)'
  hideTimer = setTimeout(() => {
    if (!activeEl && tipEl) {
      tipEl.style.left = '-9999px'
      tipEl.style.top = '-9999px'
    }
  }, 140)
}

function onScrollResize() {
  if (activeEl) hide()
}

window.addEventListener('scroll', onScrollResize, true)
window.addEventListener('resize', onScrollResize)

export const tooltip = {
  mounted(el, binding) {
    el._tooltipText = binding.value == null ? '' : String(binding.value)
    el._tooltipPlacement = binding.modifiers.bottom ? 'bottom' : 'top'
    // 네이티브 title 중복 표시 방지
    if (el.getAttribute('title')) el.removeAttribute('title')

    el._tipEnter = () => show(el)
    el._tipLeave = () => hide()
    el.addEventListener('mouseenter', el._tipEnter)
    el.addEventListener('mouseleave', el._tipLeave)
    el.addEventListener('focus', el._tipEnter)
    el.addEventListener('blur', el._tipLeave)
  },
  updated(el, binding) {
    el._tooltipText = binding.value == null ? '' : String(binding.value)
    el._tooltipPlacement = binding.modifiers.bottom ? 'bottom' : 'top'
    if (el.getAttribute('title')) el.removeAttribute('title')
    // 현재 표시 중이면 텍스트 갱신
    if (activeEl === el && tipEl) {
      if (!el._tooltipText) hide()
      else { tipEl._text.textContent = el._tooltipText; position(el, el._tooltipPlacement) }
    }
  },
  beforeUnmount(el) {
    if (activeEl === el) hide()
    el.removeEventListener('mouseenter', el._tipEnter)
    el.removeEventListener('mouseleave', el._tipLeave)
    el.removeEventListener('focus', el._tipEnter)
    el.removeEventListener('blur', el._tipLeave)
  },
}

export default tooltip
