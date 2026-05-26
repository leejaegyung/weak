<template>
  <AppLayout page-title="메일(SMTP) 설정">
    <!-- 헤더 -->
    <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:12px;margin-bottom:24px;max-width:680px;margin-left:auto;margin-right:auto;">
      <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
          <div style="background:#16A34A;border:2px solid #1A1100;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
            </svg>
          </div>
          <h1 style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:26px;font-weight:700;letter-spacing:-0.03em;">메일(SMTP) 설정</h1>
        </div>
        <p style="color:#9A8F7A;font-size:13px;margin-left:42px;">주간보고 메일 전송에 사용할 SMTP 서버와 수신자를 설정합니다</p>
      </div>
    </div>

    <form @submit.prevent="save" style="display:flex;flex-direction:column;gap:18px;max-width:680px;margin:0 auto;width:100%;">

      <!-- SMTP 서버 설정 -->
      <div class="card">
        <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:14px;font-weight:700;margin-bottom:18px;display:flex;align-items:center;gap:8px;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
          SMTP 서버 설정
        </div>

        <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:12px;margin-bottom:12px;">
          <div>
            <label class="field-label">SMTP 호스트</label>
            <input v-model="form.smtp_host" type="text" class="input-field" placeholder="smtp.gmail.com" />
          </div>
          <div>
            <label class="field-label">포트</label>
            <input v-model="form.smtp_port" type="number" class="input-field" placeholder="587" min="1" max="65535" />
          </div>
          <div>
            <label class="field-label">암호화</label>
            <select v-model="form.smtp_encryption" class="input-field">
              <option value="tls">TLS (권장)</option>
              <option value="ssl">SSL</option>
              <option value="none">없음</option>
            </select>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div>
            <label class="field-label">계정 (사용자명)</label>
            <input v-model="form.smtp_username" type="text" class="input-field" placeholder="noreply@company.com" autocomplete="username" />
          </div>
          <div>
            <label class="field-label">비밀번호</label>
            <input v-model="form.smtp_password" type="password" class="input-field" placeholder="변경 시에만 입력" autocomplete="current-password" />
            <p style="font-size:11px;color:#9A8F7A;margin-top:4px;">비워두면 기존 비밀번호를 유지합니다</p>
          </div>
        </div>
      </div>

      <!-- 발신자 정보 -->
      <div class="card">
        <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:14px;font-weight:700;margin-bottom:18px;display:flex;align-items:center;gap:8px;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/></svg>
          발신자 정보
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div>
            <label class="field-label">발신자 이름</label>
            <input v-model="form.smtp_from_name" type="text" class="input-field" placeholder="SE팀 주간보고 시스템" />
          </div>
          <div>
            <label class="field-label">발신자 이메일</label>
            <input v-model="form.smtp_from_address" type="email" class="input-field" placeholder="noreply@company.com" />
          </div>
        </div>
      </div>

      <!-- 기본 수신자 -->
      <div class="card">
        <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:14px;font-weight:700;margin-bottom:18px;display:flex;align-items:center;gap:8px;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          기본 수신자 설정
        </div>
        <p style="font-size:12px;color:#9A8F7A;margin-bottom:16px;">보고서 전송 시 기본으로 사용할 수신자입니다. 전송 시 모달에서 수정 가능합니다.</p>

        <div style="margin-bottom:14px;">
          <label class="field-label">받는 사람 (팀장 메일)</label>
          <input v-model="form.mail_to" type="email" class="input-field" placeholder="teamlead@company.com" />
        </div>

        <div>
          <label class="field-label" style="margin-bottom:8px;display:block;">참조 (CC) — 선택</label>
          <div style="display:flex;flex-direction:column;gap:6px;">
            <div v-for="(_, i) in form.mail_cc" :key="i" style="display:flex;gap:6px;align-items:center;">
              <input v-model="form.mail_cc[i]" type="email" class="input-field" style="flex:1;" :placeholder="`참조 ${i+1} 이메일`" />
              <button type="button" @click="removeCc(i)"
                style="background:none;border:none;cursor:pointer;color:#9A8F7A;padding:4px;border-radius:6px;flex-shrink:0;transition:color 0.1s;"
                @mouseenter="e=>e.currentTarget.style.color='#DC2626'"
                @mouseleave="e=>e.currentTarget.style.color='#9A8F7A'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
              </button>
            </div>
          </div>
          <button type="button" @click="addCc"
            style="display:inline-flex;align-items:center;gap:5px;margin-top:8px;background:none;border:1.5px dashed #C5BAA8;border-radius:8px;padding:5px 12px;font-size:12px;color:#9A8F7A;cursor:pointer;font-family:inherit;transition:all 0.1s;"
            @mouseenter="e=>{e.currentTarget.style.borderColor='#FD4401';e.currentTarget.style.color='#FD4401';}"
            @mouseleave="e=>{e.currentTarget.style.borderColor='#C5BAA8';e.currentTarget.style.color='#9A8F7A';}">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
            참조 추가
          </button>
        </div>
      </div>

      <!-- 연결 테스트 -->
      <div class="card" style="background:#F0FDF4;border-color:#16A34A;">
        <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:14px;font-weight:700;margin-bottom:14px;color:#15803D;">
          📡 연결 테스트
        </div>
        <p style="font-size:12px;color:#166534;margin-bottom:14px;">설정을 저장한 뒤 테스트 메일을 발송해 연결을 확인하세요.</p>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
          <input v-model="testEmail" type="email" class="input-field" style="flex:1;max-width:280px;background:#fff;" placeholder="테스트 메일 받을 주소" />
          <button type="button" @click="sendTest" :disabled="testLoading || !testEmail"
            style="display:inline-flex;align-items:center;gap:6px;background:#16A34A;color:#fff;border:2px solid #1A1100;border-radius:10px;padding:8px 18px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:2px 2px 0 #1A1100;transition:all 0.1s;white-space:nowrap;"
            :style="{ opacity: (testLoading || !testEmail) ? 0.6 : 1 }">
            <svg v-if="testLoading" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation:spin 1s linear infinite;"><path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" opacity=".25"/><path d="M12 3a9 9 0 0 1 9 9"/></svg>
            <svg v-else width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13M22 2L15 22l-4-9-9-4 20-7z"/></svg>
            {{ testLoading ? '전송 중...' : '테스트 메일 발송' }}
          </button>
        </div>
        <div v-if="testResult" :style="{
          marginTop:'10px', padding:'8px 14px', borderRadius:'8px', fontSize:'12px', fontWeight:'600',
          background: testResult.ok ? '#DCFCE7' : '#FEE2E2',
          color: testResult.ok ? '#15803D' : '#DC2626',
          border: `1.5px solid ${testResult.ok ? '#16A34A' : '#DC2626'}`,
        }">
          {{ testResult.ok ? '✅' : '❌' }} {{ testResult.message }}
        </div>
      </div>

      <!-- 저장 버튼 -->
      <div style="display:flex;justify-content:flex-end;padding-bottom:16px;">
        <button type="submit" :disabled="form.processing" class="btn-primary">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
          {{ form.processing ? '저장 중...' : '설정 저장' }}
        </button>
      </div>
    </form>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  smtp_host:         { type: String, default: '' },
  smtp_port:         { type: String, default: '587' },
  smtp_encryption:   { type: String, default: 'tls' },
  smtp_username:     { type: String, default: '' },
  smtp_from_address: { type: String, default: '' },
  smtp_from_name:    { type: String, default: '주간업무보고 시스템' },
  mail_to:           { type: String, default: '' },
  mail_cc:           { type: Array,  default: () => [] },
})

const form = useForm({
  smtp_host:         props.smtp_host,
  smtp_port:         props.smtp_port,
  smtp_encryption:   props.smtp_encryption,
  smtp_username:     props.smtp_username,
  smtp_password:     '',
  smtp_from_address: props.smtp_from_address,
  smtp_from_name:    props.smtp_from_name,
  mail_to:           props.mail_to,
  mail_cc:           [...props.mail_cc],
})

const addCc    = () => form.mail_cc.push('')
const removeCc = (i) => form.mail_cc.splice(i, 1)

const save = () => form.post('/admin/settings/smtp')

// 테스트 메일
const testEmail  = ref('')
const testLoading = ref(false)
const testResult  = ref(null)

const sendTest = async () => {
  if (!testEmail.value) return
  testLoading.value = true
  testResult.value  = null
  try {
    const res = await window.axios.post('/admin/settings/smtp/test', {
      test_email: testEmail.value,
    })
    testResult.value = { ok: true, message: res.data.message }
  } catch (e) {
    testResult.value = {
      ok: false,
      message: e.response?.data?.message ?? '전송 실패. SMTP 설정을 확인해 주세요.',
    }
  } finally {
    testLoading.value = false
  }
}
</script>

<style scoped>
.field-label {
  font-size: 11px;
  color: #9A8F7A;
  font-weight: 700;
  display: block;
  margin-bottom: 6px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
