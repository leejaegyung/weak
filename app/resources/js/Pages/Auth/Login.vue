<template>
  <div style="min-height:100vh;background:#FFF8EE url('/images/login-bg.png') center / cover no-repeat fixed;display:flex;align-items:center;justify-content:center;padding:24px;">
    <div style="width:100%;max-width:380px;">
      <!-- 헤더 -->
      <div style="text-align:center;margin-bottom:28px;">
        <div style="display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;background:#FD4401;border:2px solid #1A1100;border-radius:12px;box-shadow:3px 3px 0 #1A1100;margin-bottom:12px;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6"/>
          </svg>
        </div>
        <h1 style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:24px;font-weight:800;color:#1A1100;letter-spacing:-0.02em;">주간업무보고</h1>
        <p style="font-size:13px;color:#9A8F7A;margin-top:4px;">업무 보고 시스템에 로그인하세요</p>
      </div>

      <!-- 가입 신청 완료 메시지 -->
      <div v-if="$page.props.flash?.success"
        style="background:#DCFCE7;border:2px solid #16A34A;border-radius:12px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#16A34A;font-weight:600;text-align:center;">
        ✓ {{ $page.props.flash.success }}
      </div>

      <!-- 로그인 폼 -->
      <div style="background:#fff;border:2px solid #1A1100;border-radius:16px;box-shadow:4px 4px 0 #1A1100;padding:28px;">
        <form @submit.prevent="submit" style="display:flex;flex-direction:column;gap:16px;">
          <div>
            <label style="font-size:11px;font-weight:700;color:#9A8F7A;display:block;margin-bottom:5px;">아이디</label>
            <input v-model="form.username" type="text" autocomplete="username" class="input-field"
              placeholder="아이디를 입력하세요" autofocus
              :style="form.errors.username ? 'border-color:#FD4401;' : ''" />
            <p v-if="form.errors.username" style="font-size:11px;color:#FD4401;margin-top:4px;">{{ form.errors.username }}</p>
          </div>

          <div>
            <label style="font-size:11px;font-weight:700;color:#9A8F7A;display:block;margin-bottom:5px;">비밀번호</label>
            <input v-model="form.password" type="password" autocomplete="current-password" class="input-field"
              placeholder="비밀번호를 입력하세요"
              :style="form.errors.password ? 'border-color:#FD4401;' : ''" />
            <p v-if="form.errors.password" style="font-size:11px;color:#FD4401;margin-top:4px;">{{ form.errors.password }}</p>
          </div>

          <button type="submit" :disabled="form.processing" class="btn-primary"
            style="width:100%;justify-content:center;margin-top:4px;">
            {{ form.processing ? '로그인 중...' : '로그인' }}
          </button>
        </form>
      </div>

      <!-- 카카오 로그인 구분선 -->
      <div style="display:flex;align-items:center;gap:10px;margin-top:18px;">
        <div style="flex:1;height:2px;background:rgba(26,17,0,0.35);"></div>
        <span style="font-size:11px;font-weight:700;color:#1A1100;white-space:nowrap;text-shadow:0 0 5px #FFF8EE,0 1px 2px rgba(255,255,255,0.9);">또는</span>
        <div style="flex:1;height:2px;background:rgba(26,17,0,0.35);"></div>
      </div>

      <!-- 카카오 로그인 버튼 -->
      <a href="/auth/kakao?intent=login"
        style="display:flex;align-items:center;justify-content:center;gap:10px;width:100%;background:#FEE500;color:#1A1100;border:2px solid #1A1100;border-radius:14px;padding:13px;font-size:14px;font-weight:800;text-decoration:none;box-shadow:3px 3px 0 #1A1100;margin-top:14px;transition:all 0.1s;font-family:'Space Grotesk','Noto Sans KR',sans-serif;"
        @mouseenter="e=>{e.currentTarget.style.transform='translate(-1px,-1px)';e.currentTarget.style.boxShadow='4px 4px 0 #1A1100';}"
        @mouseleave="e=>{e.currentTarget.style.transform='none';e.currentTarget.style.boxShadow='3px 3px 0 #1A1100';}">
        <svg width="20" height="20" viewBox="0 0 512 512" fill="#1A1100">
          <path d="M255.5 48C141.1 48 48 126.1 48 222.3c0 64.3 40.5 120.8 101.3 153.2l-21.7 80.6c-1.9 7.2 5.8 13.1 12.2 9.1L233.8 401c7.1.8 14.4 1.2 21.7 1.2 114.4 0 207.5-78.1 207.5-174.3S369.9 48 255.5 48z"/>
        </svg>
        카카오 로그인
      </a>
      <p style="font-size:11px;font-weight:600;color:#4A3F2A;text-align:center;margin-top:8px;text-shadow:0 0 5px #FFF8EE,0 1px 2px rgba(255,255,255,0.9);">처음이라면 자동으로 가입 신청됩니다 (관리자 승인 필요)</p>

      <!-- 회원가입 링크 -->
      <div style="text-align:center;margin-top:14px;">
        <span style="font-size:13px;font-weight:600;color:#4A3F2A;text-shadow:0 0 5px #FFF8EE,0 1px 2px rgba(255,255,255,0.9);">계정이 없으신가요?</span>
        <Link href="/register" style="font-size:13px;font-weight:800;color:#D93A01;margin-left:6px;text-decoration:none;text-shadow:0 0 5px #FFF8EE,0 1px 2px rgba(255,255,255,0.9);">회원가입 신청</Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'

defineOptions({ layout: null })

const form = useForm({
  username: '',
  password: '',
})

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  })
}
</script>
