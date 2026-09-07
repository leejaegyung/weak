<template>
  <div style="min-height:100vh;background:#FFF8EE url('/images/login-bg.png') center / cover no-repeat fixed;display:flex;align-items:center;justify-content:center;padding:24px;">
    <div style="width:100%;max-width:400px;">
      <!-- 헤더 -->
      <div style="text-align:center;margin-bottom:24px;">
        <div style="display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;background:#FEE500;border:2px solid #1A1100;border-radius:12px;box-shadow:3px 3px 0 #1A1100;margin-bottom:12px;">
          <svg width="24" height="24" viewBox="0 0 512 512" fill="#1A1100">
            <path d="M255.5 48C141.1 48 48 126.1 48 222.3c0 64.3 40.5 120.8 101.3 153.2l-21.7 80.6c-1.9 7.2 5.8 13.1 12.2 9.1L233.8 401c7.1.8 14.4 1.2 21.7 1.2 114.4 0 207.5-78.1 207.5-174.3S369.9 48 255.5 48z"/>
          </svg>
        </div>
        <h1 style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:22px;font-weight:800;color:#1A1100;letter-spacing:-0.02em;">카카오 계정 연결</h1>
        <p style="font-size:13px;color:#4A3F2A;font-weight:600;margin-top:6px;text-shadow:0 0 5px #FFF8EE,0 1px 2px rgba(255,255,255,0.9);">
          <strong>{{ nickname }}</strong> 님, 카카오 인증이 완료되었습니다
        </p>
      </div>

      <!-- 안내 -->
      <div style="background:#FFF0A0;border:2px solid #FDCB40;border-radius:12px;padding:12px 16px;margin-bottom:16px;font-size:12px;color:#4A3F2A;line-height:1.7;">
        아직 이 카카오 계정에 연결된 사내 계정이 없습니다.<br>
        <strong>기존 계정의 아이디·비밀번호를 한 번만 확인</strong>하면 연결이 완료되며,
        다음부터는 카카오 버튼만 눌러 로그인할 수 있습니다.
      </div>

      <!-- 본인 확인 폼 -->
      <div style="background:#fff;border:2px solid #1A1100;border-radius:16px;box-shadow:4px 4px 0 #1A1100;padding:26px;">
        <form @submit.prevent="submit" style="display:flex;flex-direction:column;gap:16px;">
          <div>
            <label style="font-size:11px;font-weight:700;color:#9A8F7A;display:block;margin-bottom:5px;">아이디</label>
            <input v-model="form.username" type="text" autocomplete="username" class="input-field"
              placeholder="사내 계정 아이디" autofocus
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
            {{ form.processing ? '연결 중...' : '기존 계정에 연결하기' }}
          </button>
        </form>
      </div>

      <!-- 구분선 -->
      <div style="display:flex;align-items:center;gap:10px;margin-top:18px;">
        <div style="flex:1;height:2px;background:rgba(26,17,0,0.35);"></div>
        <span style="font-size:11px;font-weight:700;color:#1A1100;white-space:nowrap;text-shadow:0 0 5px #FFF8EE,0 1px 2px rgba(255,255,255,0.9);">사내 계정이 없다면</span>
        <div style="flex:1;height:2px;background:rgba(26,17,0,0.35);"></div>
      </div>

      <!-- 신규 가입 신청 -->
      <button type="button" @click="submitRegister" :disabled="registerForm.processing" class="btn-secondary"
        style="width:100%;justify-content:center;margin-top:14px;">
        {{ registerForm.processing ? '신청 중...' : '처음입니다 — 새로 가입 신청' }}
      </button>
      <p style="font-size:11px;font-weight:600;color:#4A3F2A;text-align:center;margin-top:8px;text-shadow:0 0 5px #FFF8EE,0 1px 2px rgba(255,255,255,0.9);">
        가입 신청 후 관리자 승인을 받아야 로그인할 수 있습니다
      </p>

      <!-- 취소 -->
      <div style="text-align:center;margin-top:16px;">
        <Link href="/login"
          style="font-size:12px;font-weight:700;color:#9A8F7A;text-decoration:none;text-shadow:0 0 5px #FFF8EE,0 1px 2px rgba(255,255,255,0.9);">
          ← 로그인 화면으로 돌아가기
        </Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'

defineOptions({ layout: null })

defineProps({
  nickname: { type: String, default: '카카오 사용자' },
})

const form = useForm({
  username: '',
  password: '',
})

const submit = () => {
  form.post('/auth/kakao/link', {
    onFinish: () => form.reset('password'),
  })
}

const registerForm = useForm({})

const submitRegister = () => {
  registerForm.post('/auth/kakao/register')
}
</script>
