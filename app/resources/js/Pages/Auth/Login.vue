<template>
  <div style="min-height:100vh;background:#FFF8EE;display:flex;align-items:center;justify-content:center;padding:24px;">
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

      <!-- 회원가입 링크 -->
      <div style="text-align:center;margin-top:16px;">
        <span style="font-size:13px;color:#9A8F7A;">계정이 없으신가요?</span>
        <Link href="/register" style="font-size:13px;font-weight:700;color:#FD4401;margin-left:6px;text-decoration:none;">회원가입 신청</Link>
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
