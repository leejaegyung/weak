<template>
  <div style="min-height:100vh;background:#FFF8EE url('/images/login-bg.png') center / cover no-repeat fixed;display:flex;align-items:center;justify-content:center;padding:24px;">
    <div style="width:100%;max-width:380px;">
      <!-- 헤더 -->
      <div style="text-align:center;margin-bottom:28px;">
        <img src="/favicon.svg" alt="SE"
          style="display:inline-block;width:48px;height:48px;border:2px solid #1A1100;border-radius:12px;box-shadow:3px 3px 0 #1A1100;margin-bottom:12px;" />
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
        <button type="button" @click="openRegister"
          style="font-size:13px;font-weight:800;color:#D93A01;margin-left:6px;background:none;border:none;cursor:pointer;font-family:inherit;padding:0;text-shadow:0 0 5px #FFF8EE,0 1px 2px rgba(255,255,255,0.9);">회원가입 신청</button>
      </div>
    </div>

    <!-- 회원가입 신청 모달 -->
    <div v-if="showRegisterModal"
      style="position:fixed;inset:0;background:rgba(26,17,0,0.45);display:flex;align-items:center;justify-content:center;z-index:100;backdrop-filter:blur(3px);padding:16px;"
      @click.self="showRegisterModal=false">
      <div class="card" style="width:440px;max-width:100%;max-height:92vh;padding:0;overflow:hidden;display:flex;flex-direction:column;">
        <!-- 모달 헤더 -->
        <div style="padding:18px 24px;border-bottom:2px solid #1A1100;background:#F5EDDB;display:flex;align-items:center;gap:12px;flex-shrink:0;">
          <div style="width:42px;height:42px;background:#FDCB40;border:2px solid #1A1100;border-radius:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM19 8v6M22 11h-6"/>
            </svg>
          </div>
          <div>
            <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:16px;font-weight:800;color:#1A1100;">회원가입 신청</div>
            <div style="font-size:12px;color:#9A8F7A;margin-top:2px;">가입 신청 후 관리자 승인이 필요합니다</div>
          </div>
        </div>

        <!-- 모달 바디 -->
        <form @submit.prevent="submitRegister" style="padding:22px 24px;display:flex;flex-direction:column;gap:14px;overflow-y:auto;flex:1;min-height:0;">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <!-- 이름 -->
            <div>
              <label style="font-size:11px;font-weight:700;color:#9A8F7A;display:block;margin-bottom:5px;">이름 *</label>
              <input v-model="registerForm.name" type="text" class="input-field" placeholder="홍길동"
                :style="registerForm.errors.name ? 'border-color:#FD4401;' : ''" />
              <p v-if="registerForm.errors.name" style="font-size:11px;color:#FD4401;margin-top:4px;">{{ registerForm.errors.name }}</p>
            </div>
            <!-- 직급 -->
            <div>
              <label style="font-size:11px;font-weight:700;color:#9A8F7A;display:block;margin-bottom:5px;">직급</label>
              <input v-model="registerForm.position" type="text" class="input-field" placeholder="예: 대리" />
            </div>
          </div>

          <!-- 아이디 -->
          <div>
            <label style="font-size:11px;font-weight:700;color:#9A8F7A;display:block;margin-bottom:5px;">아이디 *</label>
            <input v-model="registerForm.username" type="text" autocomplete="username" class="input-field" placeholder="영문+숫자 조합"
              :style="registerForm.errors.username ? 'border-color:#FD4401;' : ''" />
            <p v-if="registerForm.errors.username" style="font-size:11px;color:#FD4401;margin-top:4px;">{{ registerForm.errors.username }}</p>
          </div>

          <!-- 이메일 -->
          <div>
            <label style="font-size:11px;font-weight:700;color:#9A8F7A;display:block;margin-bottom:5px;">이메일</label>
            <input v-model="registerForm.email" type="email" autocomplete="email" class="input-field" placeholder="example@company.com" />
          </div>

          <!-- 비밀번호 -->
          <div>
            <label style="font-size:11px;font-weight:700;color:#9A8F7A;display:block;margin-bottom:5px;">비밀번호 * <span style="font-weight:400;">(6자 이상)</span></label>
            <input v-model="registerForm.password" type="password" autocomplete="new-password" class="input-field" placeholder="비밀번호"
              :style="registerForm.errors.password ? 'border-color:#FD4401;' : ''" />
            <p v-if="registerForm.errors.password" style="font-size:11px;color:#FD4401;margin-top:4px;">{{ registerForm.errors.password }}</p>
          </div>

          <!-- 비밀번호 확인 -->
          <div>
            <label style="font-size:11px;font-weight:700;color:#9A8F7A;display:block;margin-bottom:5px;">비밀번호 확인 *</label>
            <input v-model="registerForm.password_confirmation" type="password" autocomplete="new-password" class="input-field" placeholder="비밀번호 재입력"
              :style="registerForm.errors.password_confirmation ? 'border-color:#FD4401;' : ''" />
            <p v-if="registerForm.errors.password_confirmation" style="font-size:11px;color:#FD4401;margin-top:4px;">{{ registerForm.errors.password_confirmation }}</p>
          </div>

          <!-- 안내문 -->
          <div style="background:#FFF0A0;border:1.5px solid #FDCB40;border-radius:10px;padding:10px 14px;font-size:12px;color:#4A3F2A;line-height:1.6;">
            ⚠ 가입 신청 후 관리자가 승인해야 로그인할 수 있습니다.
          </div>

          <!-- 카카오 간편 가입 -->
          <div style="display:flex;align-items:center;gap:10px;">
            <div style="flex:1;height:1.5px;background:#E8E0D0;"></div>
            <span style="font-size:11px;color:#9A8F7A;white-space:nowrap;">또는 간편 가입</span>
            <div style="flex:1;height:1.5px;background:#E8E0D0;"></div>
          </div>
          <a href="/auth/kakao?intent=register"
            style="display:flex;align-items:center;justify-content:center;gap:8px;width:100%;background:#FEE500;color:#1A1100;border:2px solid #1A1100;border-radius:12px;padding:11px;font-size:13px;font-weight:800;text-decoration:none;box-shadow:2px 2px 0 #1A1100;font-family:'Space Grotesk','Noto Sans KR',sans-serif;">
            <svg width="18" height="18" viewBox="0 0 512 512" fill="#1A1100">
              <path d="M255.5 48C141.1 48 48 126.1 48 222.3c0 64.3 40.5 120.8 101.3 153.2l-21.7 80.6c-1.9 7.2 5.8 13.1 12.2 9.1L233.8 401c7.1.8 14.4 1.2 21.7 1.2 114.4 0 207.5-78.1 207.5-174.3S369.9 48 255.5 48z"/>
            </svg>
            카카오로 간편 가입
          </a>
        </form>

        <!-- 모달 푸터 -->
        <div style="padding:16px 24px;border-top:2px solid #1A1100;background:#F5EDDB;display:flex;gap:8px;justify-content:flex-end;flex-shrink:0;">
          <button type="button" @click="showRegisterModal=false" class="btn-secondary" :disabled="registerForm.processing">취소</button>
          <button type="button" @click="submitRegister" :disabled="registerForm.processing" class="btn-primary">
            {{ registerForm.processing ? '신청 중...' : '가입 신청하기' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

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

// ── 회원가입 신청 모달 ──────────────────────────────
const showRegisterModal = ref(false)

const registerForm = useForm({
  name:                  '',
  username:              '',
  email:                 '',
  password:              '',
  password_confirmation: '',
  position:              '',
})

const openRegister = () => {
  registerForm.clearErrors()
  showRegisterModal.value = true
}

const submitRegister = () => {
  registerForm.post('/register', {
    preserveScroll: true,
    onSuccess: () => { showRegisterModal.value = false },
    onFinish:  () => registerForm.reset('password', 'password_confirmation'),
  })
}
</script>
