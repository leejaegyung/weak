<template>
  <AppLayout page-title="가입 승인 관리">
    <!-- 헤더 -->
    <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
      <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
          <div style="background:#FDCB40;border:2px solid #1A1100;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM19 8v6M22 11h-6"/>
            </svg>
          </div>
          <h1 style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:26px;font-weight:700;letter-spacing:-0.03em;">가입 승인 관리</h1>
        </div>
        <p style="color:#9A8F7A;font-size:13px;margin-left:42px;">
          승인 대기 중인 회원가입 신청 목록입니다
        </p>
      </div>
      <Link href="/admin/users" class="btn-secondary" style="display:inline-flex;align-items:center;gap:5px;">
        ← 사용자 관리
      </Link>
    </div>

    <!-- 대기 목록 -->
    <div class="card" style="overflow:hidden;padding:0;">
      <!-- 헤더 -->
      <div style="display:grid;grid-template-columns:2fr 2fr 1.5fr 1.5fr 2fr;padding:10px 20px;border-bottom:2px solid #1A1100;background:#FFF0A0;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#9A8F7A;font-family:'Space Grotesk','Noto Sans KR',sans-serif;">
        <span>이름</span><span>아이디</span><span>직급</span><span>신청일</span><span>처리</span>
      </div>

      <!-- 대기 없을 때 -->
      <div v-if="pending.length === 0"
        style="padding:64px 20px;text-align:center;color:#9A8F7A;font-size:13px;">
        <div style="font-size:32px;margin-bottom:12px;">🎉</div>
        <div style="font-weight:700;margin-bottom:4px;">승인 대기 중인 신청이 없습니다</div>
        <div style="font-size:12px;">새 회원가입 신청이 들어오면 여기에 표시됩니다</div>
      </div>

      <!-- 대기 목록 -->
      <div v-for="(u, i) in pending" :key="u.id"
        style="display:grid;grid-template-columns:2fr 2fr 1.5fr 1.5fr 2fr;padding:14px 20px;align-items:center;transition:background 0.1s;"
        :style="{ borderBottom: i < pending.length-1 ? '1.5px solid #F5EDDB' : 'none' }"
        @mouseenter="e=>e.currentTarget.style.background='#FFF8EE'"
        @mouseleave="e=>e.currentTarget.style.background='transparent'">

        <!-- 이름 -->
        <div style="display:flex;align-items:center;gap:9px;">
          <div style="width:30px;height:30px;border-radius:50%;background:#FDCB40;border:2px solid #1A1100;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;font-family:'Space Grotesk',sans-serif;">
            {{ u.name.charAt(0) }}
          </div>
          <div>
            <div style="font-size:13px;font-weight:700;">{{ u.name }}</div>
            <div v-if="u.email" style="font-size:11px;color:#9A8F7A;">{{ u.email }}</div>
          </div>
        </div>

        <span style="font-size:12px;color:#4A3F2A;">@{{ u.username }}</span>
        <span style="font-size:12px;font-weight:600;">{{ u.position || '-' }}</span>
        <span style="font-size:12px;color:#9A8F7A;">{{ u.created_at }}</span>

        <!-- 승인 / 거절 버튼 -->
        <div style="display:flex;gap:6px;" @click.stop>
          <button @click="approve(u)"
            style="background:#DCFCE7;color:#16A34A;border:2px solid #16A34A;border-radius:99px;padding:4px 14px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;transition:all 0.1s;"
            @mouseenter="e=>e.currentTarget.style.transform='translate(-1px,-1px)'"
            @mouseleave="e=>e.currentTarget.style.transform='none'">
            ✓ 승인
          </button>
          <button @click="rejectReg(u)"
            style="background:#FEE2E2;color:#DC2626;border:2px solid #DC2626;border-radius:99px;padding:4px 14px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;transition:all 0.1s;"
            @mouseenter="e=>e.currentTarget.style.transform='translate(-1px,-1px)'"
            @mouseleave="e=>e.currentTarget.style.transform='none'">
            ✕ 거절
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineProps({
  pending: { type: Array, default: () => [] },
})

const approve   = (u) => router.post(`/admin/users/${u.id}/approve`)
const rejectReg = (u) => router.post(`/admin/users/${u.id}/reject-registration`)
</script>
