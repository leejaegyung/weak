<template>
  <div style="min-height:100vh;background:#FFF8EE;display:flex;align-items:center;justify-content:center;padding:24px;">
    <div style="text-align:center;max-width:420px;width:100%;">

      <!-- 아이콘 -->
      <div style="display:inline-flex;align-items:center;justify-content:center;width:80px;height:80px;background:#FFF0A0;border:2px solid #1A1100;border-radius:20px;box-shadow:4px 4px 0 #1A1100;margin-bottom:24px;">
        <svg v-if="status === 403" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
        <svg v-else-if="status === 404" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          <line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/>
        </svg>
        <svg v-else width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
          <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
      </div>

      <!-- 상태 코드 -->
      <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:64px;font-weight:800;color:#1A1100;line-height:1;letter-spacing:-0.04em;margin-bottom:12px;">
        {{ status }}
      </div>

      <!-- 제목 -->
      <h1 style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:20px;font-weight:800;color:#1A1100;margin-bottom:10px;letter-spacing:-0.02em;">
        {{ title }}
      </h1>

      <!-- 설명 -->
      <p style="font-size:14px;color:#9A8F7A;line-height:1.7;margin-bottom:32px;white-space:pre-line;">
        {{ description }}
      </p>

      <!-- 버튼 -->
      <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
        <Link href="/reports"
          style="display:inline-flex;align-items:center;gap:6px;background:#F5EDDB;color:#1A1100;border:2px solid #1A1100;border-radius:12px;padding:10px 20px;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;box-shadow:3px 3px 0 #1A1100;transition:all 0.1s;"
          @mouseenter="e=>{e.currentTarget.style.transform='translate(-1px,-1px)';e.currentTarget.style.boxShadow='4px 4px 0 #1A1100';}"
          @mouseleave="e=>{e.currentTarget.style.transform='none';e.currentTarget.style.boxShadow='3px 3px 0 #1A1100';}">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M15 18l-6-6 6-6"/>
          </svg>
          보고서 목록
        </Link>
        <Link href="/"
          style="display:inline-flex;align-items:center;gap:6px;background:#FD4401;color:#fff;border:2px solid #1A1100;border-radius:12px;padding:10px 20px;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;box-shadow:3px 3px 0 #1A1100;transition:all 0.1s;"
          @mouseenter="e=>{e.currentTarget.style.transform='translate(-1px,-1px)';e.currentTarget.style.boxShadow='4px 4px 0 #1A1100';}"
          @mouseleave="e=>{e.currentTarget.style.transform='none';e.currentTarget.style.boxShadow='3px 3px 0 #1A1100';}">
          홈으로 →
        </Link>
      </div>

    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

defineOptions({ layout: null })

const props = defineProps({
  status: { type: Number, default: 404 },
})

const titles = {
  403: '접근 권한이 없습니다',
  404: '페이지를 찾을 수 없습니다',
  500: '서버 오류가 발생했습니다',
  503: '서비스 점검 중입니다',
}

const descriptions = {
  403: '이 페이지에 접근할 권한이 없습니다.\n관리자에게 문의하세요.',
  404: '요청한 페이지가 삭제되었거나 존재하지 않습니다.\n링크를 다시 확인해 주세요.',
  500: '서버에서 오류가 발생했습니다.\n잠시 후 다시 시도해 주세요.',
  503: '현재 서비스 점검 중입니다.\n잠시 후 다시 방문해 주세요.',
}

const title       = computed(() => titles[props.status]       ?? '오류가 발생했습니다')
const description = computed(() => descriptions[props.status] ?? '잠시 후 다시 시도해 주세요.')
</script>
