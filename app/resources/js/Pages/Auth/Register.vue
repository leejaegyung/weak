<template>
  <div style="min-height:100vh;background:#FFF8EE;display:flex;align-items:center;justify-content:center;padding:24px;">
    <div style="width:100%;max-width:420px;">
      <!-- 헤더 -->
      <div style="text-align:center;margin-bottom:28px;">
        <div style="display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;background:#FDCB40;border:2px solid #1A1100;border-radius:12px;box-shadow:3px 3px 0 #1A1100;margin-bottom:12px;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM19 8v6M22 11h-6"/>
          </svg>
        </div>
        <h1 style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:24px;font-weight:800;color:#1A1100;letter-spacing:-0.02em;">회원가입</h1>
        <p style="font-size:13px;color:#9A8F7A;margin-top:4px;">가입 신청 후 관리자 승인이 필요합니다</p>
      </div>

      <!-- 폼 카드 -->
      <div style="background:#fff;border:2px solid #1A1100;border-radius:16px;box-shadow:4px 4px 0 #1A1100;padding:28px;">
        <form @submit.prevent="submit" style="display:flex;flex-direction:column;gap:14px;">

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <!-- 이름 -->
            <div>
              <label style="font-size:11px;font-weight:700;color:#9A8F7A;display:block;margin-bottom:5px;">이름 *</label>
              <input v-model="form.name" type="text" class="input-field" placeholder="홍길동"
                :style="form.errors.name ? 'border-color:#FD4401;' : ''" />
              <p v-if="form.errors.name" style="font-size:11px;color:#FD4401;margin-top:4px;">{{ form.errors.name }}</p>
            </div>
            <!-- 직급 -->
            <div>
              <label style="font-size:11px;font-weight:700;color:#9A8F7A;display:block;margin-bottom:5px;">직급</label>
              <input v-model="form.position" type="text" class="input-field" placeholder="예: 대리" />
            </div>
          </div>

          <!-- 아이디 -->
          <div>
            <label style="font-size:11px;font-weight:700;color:#9A8F7A;display:block;margin-bottom:5px;">아이디 *</label>
            <input v-model="form.username" type="text" class="input-field" placeholder="영문+숫자 조합"
              :style="form.errors.username ? 'border-color:#FD4401;' : ''" />
            <p v-if="form.errors.username" style="font-size:11px;color:#FD4401;margin-top:4px;">{{ form.errors.username }}</p>
          </div>

          <!-- 이메일 -->
          <div>
            <label style="font-size:11px;font-weight:700;color:#9A8F7A;display:block;margin-bottom:5px;">이메일</label>
            <input v-model="form.email" type="email" class="input-field" placeholder="example@company.com" />
          </div>

          <!-- 비밀번호 -->
          <div>
            <label style="font-size:11px;font-weight:700;color:#9A8F7A;display:block;margin-bottom:5px;">비밀번호 * <span style="font-weight:400;">(6자 이상)</span></label>
            <input v-model="form.password" type="password" class="input-field" placeholder="비밀번호"
              :style="form.errors.password ? 'border-color:#FD4401;' : ''" />
            <p v-if="form.errors.password" style="font-size:11px;color:#FD4401;margin-top:4px;">{{ form.errors.password }}</p>
          </div>

          <!-- 비밀번호 확인 -->
          <div>
            <label style="font-size:11px;font-weight:700;color:#9A8F7A;display:block;margin-bottom:5px;">비밀번호 확인 *</label>
            <input v-model="form.password_confirmation" type="password" class="input-field" placeholder="비밀번호 재입력"
              :style="form.errors.password_confirmation ? 'border-color:#FD4401;' : ''" />
            <p v-if="form.errors.password_confirmation" style="font-size:11px;color:#FD4401;margin-top:4px;">{{ form.errors.password_confirmation }}</p>
          </div>

          <!-- 안내문 -->
          <div style="background:#FFF0A0;border:1.5px solid #FDCB40;border-radius:10px;padding:10px 14px;font-size:12px;color:#4A3F2A;line-height:1.6;">
            ⚠ 가입 신청 후 관리자가 승인해야 로그인할 수 있습니다.
          </div>

          <button type="submit" :disabled="form.processing" class="btn-primary"
            style="width:100%;justify-content:center;margin-top:4px;">
            {{ form.processing ? '신청 중...' : '가입 신청하기' }}
          </button>
        </form>
      </div>

      <!-- 카카오로 간편 가입 -->
      <div style="display:flex;align-items:center;gap:10px;margin-top:18px;">
        <div style="flex:1;height:1.5px;background:#E8E0D0;"></div>
        <span style="font-size:11px;color:#9A8F7A;white-space:nowrap;">또는 간편 가입</span>
        <div style="flex:1;height:1.5px;background:#E8E0D0;"></div>
      </div>

      <a href="/auth/kakao?intent=register"
        style="display:flex;align-items:center;justify-content:center;gap:10px;width:100%;background:#FEE500;color:#1A1100;border:2px solid #1A1100;border-radius:14px;padding:13px;font-size:14px;font-weight:800;text-decoration:none;box-shadow:3px 3px 0 #1A1100;margin-top:14px;font-family:'Space Grotesk','Noto Sans KR',sans-serif;"
        @mouseenter="e=>{e.currentTarget.style.transform='translate(-1px,-1px)';e.currentTarget.style.boxShadow='4px 4px 0 #1A1100';}"
        @mouseleave="e=>{e.currentTarget.style.transform='none';e.currentTarget.style.boxShadow='3px 3px 0 #1A1100';}">
        <svg width="20" height="20" viewBox="0 0 512 512" fill="#1A1100">
          <path d="M255.5 48C141.1 48 48 126.1 48 222.3c0 64.3 40.5 120.8 101.3 153.2l-21.7 80.6c-1.9 7.2 5.8 13.1 12.2 9.1L233.8 401c7.1.8 14.4 1.2 21.7 1.2 114.4 0 207.5-78.1 207.5-174.3S369.9 48 255.5 48z"/>
        </svg>
        카카오로 간편 가입
      </a>
      <p style="font-size:11px;color:#9A8F7A;text-align:center;margin-top:8px;">카카오 닉네임으로 자동 가입 신청 → 관리자 승인 후 이용</p>

      <!-- 로그인 링크 -->
      <div style="text-align:center;margin-top:14px;">
        <span style="font-size:13px;color:#9A8F7A;">이미 계정이 있으신가요?</span>
        <Link href="/login" style="font-size:13px;font-weight:700;color:#FD4401;margin-left:6px;text-decoration:none;">로그인</Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'

defineOptions({ layout: null })

const form = useForm({
  name:                  '',
  username:              '',
  email:                 '',
  password:              '',
  password_confirmation: '',
  position:              '',
})

const submit = () => {
  form.post('/register', {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>
