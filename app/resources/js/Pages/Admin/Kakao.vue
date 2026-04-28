<template>
  <AppLayout page-title="카카오 연동">

    <!-- 헤더 -->
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:24px;">
      <div style="background:#FEF9C3;border:2px solid #1A1100;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <svg width="18" height="18" viewBox="0 0 512 512" fill="#3A1D0E">
          <path d="M255.5 48C141.1 48 48 126.1 48 222.3c0 64.3 40.5 120.8 101.3 153.2l-21.7 80.6c-1.9 7.2 5.8 13.1 12.2 9.1L233.8 401c7.1.8 14.4 1.2 21.7 1.2 114.4 0 207.5-78.1 207.5-174.3S369.9 48 255.5 48z"/>
        </svg>
      </div>
      <div>
        <h1 style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:26px;font-weight:700;letter-spacing:-0.03em;">카카오 연동</h1>
        <p style="color:#9A8F7A;font-size:13px;">팀원별 카카오 연동 현황 및 미제출 알림 발송</p>
      </div>
    </div>

    <div style="max-width:700px;display:flex;flex-direction:column;gap:18px;">

      <!-- ── 순서도 ── -->
      <div class="card">
        <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:15px;font-weight:800;margin-bottom:20px;display:flex;align-items:center;gap:8px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          카카오 알림 적용 순서
        </div>

        <div style="display:flex;flex-direction:column;gap:0;">
          <div v-for="(step, idx) in steps" :key="idx" style="display:flex;gap:14px;">
            <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0;">
              <div :style="{
                width:'32px', height:'32px', borderRadius:'50%', border:'2px solid #1A1100',
                display:'flex', alignItems:'center', justifyContent:'center', flexShrink:0,
                background: step.done ? '#16A34A' : '#FDCB40',
                fontFamily:'\'Space Grotesk\',sans-serif', fontSize:'13px', fontWeight:'800', color: step.done ? '#fff' : '#1A1100',
              }">
                <svg v-if="step.done" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                <span v-else>{{ idx + 1 }}</span>
              </div>
              <div v-if="idx < steps.length - 1" style="width:2px;flex:1;background:#E8E0D0;margin:4px 0;min-height:20px;"></div>
            </div>
            <div :style="{ paddingBottom: idx < steps.length-1 ? '20px' : '0', flex:1 }">
              <div style="font-size:13px;font-weight:700;color:#1A1100;margin-bottom:4px;">{{ step.title }}</div>
              <div style="font-size:12px;color:#4A3F2A;line-height:1.7;" v-html="step.desc"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- REST API 키 -->
      <form @submit.prevent="saveKey" class="card">
        <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:15px;font-weight:800;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
          앱 설정 (관리자)
        </div>

        <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:8px;">
          <div>
            <label style="font-size:11px;color:#9A8F7A;font-weight:700;display:block;margin-bottom:6px;">카카오 REST API 키 <span style="color:#FD4401;">*</span></label>
            <input v-model="keyForm.rest_api_key" type="text" class="input-field"
              placeholder="카카오 개발자 콘솔 → 플랫폼 키 → REST API 키"
              style="width:100%;font-family:monospace;" />
          </div>
          <div>
            <label style="font-size:11px;color:#9A8F7A;font-weight:700;display:block;margin-bottom:6px;">
              클라이언트 시크릿
              <span style="font-weight:400;color:#C5BAA8;">(플랫폼 키 → REST API 키 수정 → 클라이언트 시크릿 활성화 시 필수)</span>
            </label>
            <input v-model="keyForm.client_secret" type="text" class="input-field"
              placeholder="클라이언트 시크릿 비활성화 시 비워두세요"
              style="width:100%;font-family:monospace;" />
          </div>
          <div style="display:flex;justify-content:flex-end;">
            <button type="submit" :disabled="keyForm.processing" class="btn-primary" style="white-space:nowrap;">
              {{ keyForm.processing ? '저장 중...' : '저장' }}
            </button>
          </div>
        </div>

        <div style="background:#1A1100;color:#FDCB40;border-radius:10px;padding:10px 14px;font-family:monospace;font-size:11px;word-break:break-all;margin-top:12px;">
          <div style="font-size:10px;color:#9A8F7A;font-family:inherit;margin-bottom:4px;">카카오 개발자 콘솔 리다이렉트 URI 등록 주소</div>
          {{ redirectUri }}
        </div>
      </form>

      <!-- 팀원 카카오 연동 현황 -->
      <div class="card">
        <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:15px;font-weight:800;margin-bottom:6px;display:flex;align-items:center;gap:8px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1D4ED8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          팀원 연동 현황
          <span style="margin-left:auto;font-size:12px;font-weight:600;color:#9A8F7A;">
            {{ connectedCount }}/{{ users.length }}명 연동
          </span>
        </div>

        <div style="display:flex;flex-direction:column;gap:6px;">
          <div v-for="user in users" :key="user.id"
            style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:10px;border:2px solid #E8E0D0;"
            :style="{ background: user.connected ? '#F0FDF4' : '#FAFAF8', borderColor: user.connected ? '#86EFAC' : '#E8E0D0' }">
            <div style="width:32px;height:32px;border-radius:50%;background:#FDCB40;border:2px solid #1A1100;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#1A1100;flex-shrink:0;">
              {{ user.name.charAt(0) }}
            </div>
            <div style="flex:1;">
              <div style="font-size:13px;font-weight:700;color:#1A1100;">{{ user.name }}</div>
              <div v-if="user.position" style="font-size:11px;color:#9A8F7A;">{{ user.position }}</div>
            </div>
            <div v-if="user.connected"
              style="display:inline-flex;align-items:center;gap:4px;background:#DCFCE7;color:#16A34A;border:1.5px solid #86EFAC;border-radius:99px;padding:3px 10px;font-size:11px;font-weight:700;">
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
              연동됨
            </div>
            <div v-else
              style="display:inline-flex;align-items:center;gap:4px;background:#F3F4F6;color:#9CA3AF;border:1.5px solid #E5E7EB;border-radius:99px;padding:3px 10px;font-size:11px;font-weight:700;">
              미연동
            </div>
          </div>

          <div v-if="users.length === 0" style="text-align:center;padding:24px;color:#9A8F7A;font-size:13px;">
            활성화된 팀원이 없습니다
          </div>
        </div>

        <div style="margin-top:12px;padding:10px 14px;background:#FFF8EE;border-radius:10px;border:1.5px solid #E8E0D0;font-size:12px;color:#6B4F1A;line-height:1.7;">
          💡 팀원은 <strong>개인설정 → 카카오 연결하기</strong>를 클릭하여 직접 연동할 수 있습니다
        </div>
      </div>

      <!-- 미제출 알림 발송 -->
      <div class="card">
        <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:15px;font-weight:800;margin-bottom:8px;display:flex;align-items:center;gap:8px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          미제출 알림 발송
        </div>
        <p style="font-size:12px;color:#9A8F7A;margin-bottom:16px;line-height:1.7;">
          해당 주차 미제출자의 카카오톡으로 각자에게 직접 알림을 발송합니다.<br>
          <strong style="color:#D97706;">카카오 미연동 팀원은 발송에서 제외됩니다.</strong>
        </p>

        <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;margin-bottom:12px;">
          <div style="flex:1;min-width:160px;">
            <label style="font-size:11px;color:#9A8F7A;font-weight:700;display:block;margin-bottom:6px;">주차 시작일 (월요일)</label>
            <input type="date" :value="alertWeekStart" @change="e => alertWeekStart = toMonday(e.target.value)" class="input-field" />
          </div>
          <button @click="sendAlert" :disabled="!alertWeekStart || alertLoading"
            style="background:#FEE500;color:#1A1100;border:2px solid #1A1100;border-radius:12px;padding:9px 18px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:2px 2px 0 #1A1100;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;flex-shrink:0;"
            :style="{ opacity: !alertWeekStart || alertLoading ? 0.5 : 1 }">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13M22 2L15 22l-4-9-9-4 20-7z"/></svg>
            {{ alertLoading ? '발송 중...' : '미제출 알림 발송' }}
          </button>
        </div>
        <p v-if="alertMessage" :style="{ fontSize:'12px', fontWeight:'600', color: alertOk ? '#16A34A' : '#D97706' }">
          {{ alertMessage }}
        </p>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  rest_api_key:  { type: String, default: '' },
  client_secret: { type: String, default: '' },
  redirect_uri:  { type: String, default: '' },
  users:         { type: Array,  default: () => [] },
})

const redirectUri    = ref(props.redirect_uri)
const connectedCount = computed(() => props.users.filter(u => u.connected).length)

// 순서도 단계
const steps = [
  {
    title: '카카오 개발자 앱 생성 및 REST API 키 발급',
    desc:  '<a href="https://developers.kakao.com" target="_blank" style="color:#1D4ED8;font-weight:700;">developers.kakao.com</a> → 내 애플리케이션 → 앱 추가 → <strong>앱 키 탭에서 REST API 키 복사</strong>',
    done:  !!props.rest_api_key,
  },
  {
    title: '리다이렉트 URI 및 동의항목 설정',
    desc:  '앱 설정 → <strong>카카오 로그인 활성화 ON</strong> → Redirect URI에 아래 주소 등록<br>앱 설정 → <strong>동의항목 → 카카오톡 메시지 전송(talk_message) 선택 동의</strong>',
    done:  false,
  },
  {
    title: '관리자 페이지에 REST API 키 저장',
    desc:  '아래 입력란에 REST API 키 붙여넣기 후 저장',
    done:  !!props.rest_api_key,
  },
  {
    title: '팀원 개인설정에서 카카오 연결',
    desc:  '각 팀원이 로그인 후 <strong>개인설정 → 카카오 계정 연결하기</strong> 클릭 → 카카오 로그인 완료',
    done:  props.users.some(u => u.connected),
  },
  {
    title: '미제출 알림 발송!',
    desc:  '아래 발송 버튼 클릭 → 카카오 연동된 팀원에게 각자의 카카오톡으로 직접 알림 발송',
    done:  false,
  },
]

// REST API 키 저장
const keyForm = useForm({ rest_api_key: props.rest_api_key, client_secret: props.client_secret })
const saveKey = () => keyForm.post('/admin/settings/kakao')

// 날짜 → 해당 주 월요일로 보정
const toMonday = (dateStr) => {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  const day = d.getDay()
  const diff = day === 0 ? -6 : 1 - day
  d.setDate(d.getDate() + diff)
  return d.toISOString().slice(0, 10)
}

// 미제출 알림 발송
const alertWeekStart = ref('')
const alertLoading   = ref(false)
const alertMessage   = ref('')
const alertOk        = ref(false)

const sendAlert = async () => {
  if (!alertWeekStart.value) return
  alertLoading.value = true
  alertMessage.value = ''
  try {
    const res = await window.axios.post('/admin/settings/kakao/send', { week_start: alertWeekStart.value })
    alertOk.value      = res.data.ok
    alertMessage.value = res.data.message
  } catch {
    alertMessage.value = '발송 실패. 설정을 확인해 주세요.'
    alertOk.value = false
  } finally {
    alertLoading.value = false
  }
}
</script>
