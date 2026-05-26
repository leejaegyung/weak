<template>
  <AppLayout page-title="알림 설정">
    <!-- 헤더 -->
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:24px;max-width:680px;margin-left:auto;margin-right:auto;">
      <div style="background:#F5F3FF;border:2px solid #1A1100;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
          <circle cx="12" cy="12" r="3"/>
        </svg>
      </div>
      <div>
        <h1 style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:26px;font-weight:700;letter-spacing:-0.03em;">알림 설정</h1>
        <p style="color:#9A8F7A;font-size:13px;">Mattermost / Slack Webhook 연동 및 미제출 알림 발송</p>
      </div>
    </div>

    <div style="max-width:680px;display:flex;flex-direction:column;gap:18px;margin:0 auto;width:100%;">

      <!-- Webhook 설정 카드 -->
      <form @submit.prevent="submit" class="card">
        <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:15px;font-weight:800;margin-bottom:20px;display:flex;align-items:center;gap:8px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 7h3a5 5 0 0 1 5 5 5 5 0 0 1-5 5h-3m-6 0H6a5 5 0 0 1-5-5 5 5 0 0 1 5-5h3M8 12h8"/></svg>
          Webhook 설정
        </div>

        <!-- 활성화 토글 -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding:14px 16px;background:#F5EDDB;border-radius:12px;border:2px solid #1A1100;">
          <div>
            <div style="font-size:13px;font-weight:700;">Webhook 활성화</div>
            <div style="font-size:11px;color:#9A8F7A;margin-top:2px;">활성화 시 반려·미제출 알림이 메신저로 발송됩니다</div>
          </div>
          <label style="position:relative;display:inline-flex;align-items:center;cursor:pointer;">
            <input type="checkbox" v-model="form.webhook_enabled" style="opacity:0;width:0;height:0;position:absolute;" />
            <div :style="{
              width:'44px', height:'24px', borderRadius:'12px', border:'2px solid #1A1100',
              background: form.webhook_enabled ? '#FDCB40' : '#E5E7EB',
              transition:'background 0.2s', position:'relative', flexShrink:0,
            }">
              <div :style="{
                position:'absolute', top:'2px',
                left: form.webhook_enabled ? '20px' : '2px',
                width:'16px', height:'16px', borderRadius:'50%',
                background:'#1A1100', transition:'left 0.2s',
              }"></div>
            </div>
          </label>
        </div>

        <!-- Webhook URL -->
        <div style="margin-bottom:20px;">
          <label style="font-size:11px;color:#9A8F7A;font-weight:700;display:block;margin-bottom:6px;">
            Webhook URL
          </label>
          <div style="display:flex;gap:8px;">
            <input v-model="form.webhook_url" type="url" class="input-field"
              placeholder="https://mattermost.example.com/hooks/xxxx"
              style="flex:1;" />
            <button type="button" @click="testWebhook"
              :disabled="!form.webhook_url || testLoading"
              class="btn-secondary btn-sm"
              style="white-space:nowrap;flex-shrink:0;">
              {{ testLoading ? '테스트 중...' : '연결 테스트' }}
            </button>
          </div>
          <p v-if="testResult" :style="{ fontSize:'11px', marginTop:'6px', fontWeight:'600', color: testResult === 'ok' ? '#16A34A' : '#DC2626' }">
            {{ testResult === 'ok' ? '✓ 연결 성공! 메시지가 전송되었습니다.' : '✕ 전송 실패. URL을 확인해 주세요.' }}
          </p>
          <p style="font-size:11px;color:#9A8F7A;margin-top:6px;">Slack / Mattermost Incoming Webhook URL을 입력하세요</p>
        </div>

        <!-- ── 매일 아침 자동 발송 ── -->
        <div style="background:#F0F9FF;border:2px solid #BAE6FD;border-radius:12px;padding:16px;margin-bottom:20px;">
          <div style="font-size:13px;font-weight:700;color:#0369A1;margin-bottom:12px;display:flex;align-items:center;gap:6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0369A1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            매일 아침 팀 일정 자동 발송
          </div>

          <!-- 자동 발송 토글 -->
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <div>
              <div style="font-size:12px;font-weight:700;">자동 발송 활성화</div>
              <div style="font-size:11px;color:#9A8F7A;margin-top:2px;">평일(월~금) 지정 시간에 당일 팀 일정을 자동 발송</div>
            </div>
            <label style="position:relative;display:inline-flex;align-items:center;cursor:pointer;">
              <input type="checkbox" v-model="form.webhook_daily_enabled" style="opacity:0;width:0;height:0;position:absolute;" />
              <div :style="{
                width:'44px', height:'24px', borderRadius:'12px', border:'2px solid #1A1100',
                background: form.webhook_daily_enabled ? '#0EA5E9' : '#E5E7EB',
                transition:'background 0.2s', position:'relative', flexShrink:0,
              }">
                <div :style="{
                  position:'absolute', top:'2px',
                  left: form.webhook_daily_enabled ? '20px' : '2px',
                  width:'16px', height:'16px', borderRadius:'50%',
                  background:'#1A1100', transition:'left 0.2s',
                }"></div>
              </div>
            </label>
          </div>

          <!-- 발송 시간 -->
          <div style="display:flex;align-items:center;gap:12px;">
            <div style="flex:1;">
              <label style="font-size:11px;color:#9A8F7A;font-weight:700;display:block;margin-bottom:5px;">발송 시간</label>
              <input v-model="form.webhook_daily_time" type="time"
                :disabled="!form.webhook_daily_enabled"
                class="input-field"
                style="max-width:140px;"
                :style="{ opacity: form.webhook_daily_enabled ? 1 : 0.45 }" />
            </div>
            <div style="font-size:11px;color:#0369A1;line-height:1.6;padding-top:16px;">
              설정 시간에 당일 팀 일정을<br>항목별로 그룹화하여 발송합니다
            </div>
          </div>

          <!-- 발송 형식 예시 -->
          <div style="margin-top:12px;background:#fff;border:1.5px solid #BAE6FD;border-radius:8px;padding:10px 12px;font-size:11px;color:#1A1100;line-height:1.8;">
            <div style="color:#0369A1;font-weight:700;margin-bottom:4px;">발송 형식 예시</div>
            <div>📅 <strong>2026년 05월 19일(월) 팀 일정</strong></div>
            <div>✈️ 출장: 홍길동, 김철수</div>
            <div>🌐 mbc 제작: 이영희</div>
            <div>🌐 KBS 방송: 홍길동, 박민수</div>
            <div>✏ 주간보고 작성: 김철수</div>
          </div>
        </div>

        <!-- 알림 트리거 안내 -->
        <div style="background:#FFF8EE;border:2px solid #E8E0D0;border-radius:10px;padding:14px 16px;margin-bottom:20px;">
          <div style="font-size:12px;font-weight:700;margin-bottom:8px;color:#4A3F2A;">기타 자동 발송 조건</div>
          <div style="display:flex;flex-direction:column;gap:6px;">
            <div style="display:flex;align-items:center;gap:8px;font-size:12px;color:#4A3F2A;">
              <span style="width:20px;height:20px;background:#FEE2E2;border:1.5px solid #DC2626;border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
              </span>
              보고서 <strong>반려</strong> 시 — 해당 작성자에게 즉시 발송
            </div>
            <div style="display:flex;align-items:center;gap:8px;font-size:12px;color:#4A3F2A;">
              <span style="width:20px;height:20px;background:#FFF0A0;border:1.5px solid #D97706;border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#92400E" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0zM12 9v4M12 17h.01"/></svg>
              </span>
              <strong>미제출</strong> 알림 — 아래 버튼으로 수동 발송
            </div>
          </div>
        </div>

        <button type="submit" :disabled="form.processing" class="btn-primary">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
          {{ form.processing ? '저장 중...' : '설정 저장' }}
        </button>
      </form>

      <!-- 미제출 알림 수동 발송 카드 -->
      <div class="card">
        <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:15px;font-weight:800;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          미제출 알림 발송
        </div>
        <p style="font-size:12px;color:#9A8F7A;margin-bottom:16px;">해당 주차에 보고서를 제출하지 않은 팀원에게 Webhook 알림을 발송합니다.</p>

        <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
          <div style="flex:1;min-width:160px;">
            <label style="font-size:11px;color:#9A8F7A;font-weight:700;display:block;margin-bottom:6px;">주차 시작일 (월요일)</label>
            <input type="date" :value="alertWeekStart" @change="e => alertWeekStart = toMonday(e.target.value)" class="input-field" />
          </div>
          <button @click="sendAlert" :disabled="!alertWeekStart || alertLoading"
            style="background:#FDCB40;color:#1A1100;border:2px solid #1A1100;border-radius:12px;padding:9px 18px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:2px 2px 0 #1A1100;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;flex-shrink:0;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13M22 2L15 22l-4-9-9-4 20-7z"/></svg>
            {{ alertLoading ? '발송 중...' : '미제출 알림 발송' }}
          </button>
        </div>
        <p v-if="alertMessage" style="font-size:12px;margin-top:10px;font-weight:600;"
          :style="{ color: alertOk ? '#16A34A' : '#DC2626' }">
          {{ alertMessage }}
        </p>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  webhook_url:           { type: String,  default: '' },
  webhook_enabled:       { type: Boolean, default: false },
  webhook_daily_enabled: { type: Boolean, default: false },
  webhook_daily_time:    { type: String,  default: '09:00' },
})

const form = useForm({
  webhook_url:           props.webhook_url,
  webhook_enabled:       props.webhook_enabled,
  webhook_daily_enabled: props.webhook_daily_enabled,
  webhook_daily_time:    props.webhook_daily_time,
})

const submit = () => form.post('/admin/settings/webhook')

// ── 연결 테스트 ──
const testLoading = ref(false)
const testResult  = ref(null)

const testWebhook = async () => {
  if (!form.webhook_url) return
  testLoading.value = true
  testResult.value  = null
  try {
    const res = await window.axios.post('/admin/settings/webhook/test', { webhook_url: form.webhook_url })
    testResult.value = res.data.ok ? 'ok' : 'fail'
  } catch { testResult.value = 'fail' }
  finally  { testLoading.value = false }
}

// 날짜 → 해당 주 월요일로 보정
const toMonday = (dateStr) => {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  const day = d.getDay()
  const diff = day === 0 ? -6 : 1 - day
  d.setDate(d.getDate() + diff)
  return d.toISOString().slice(0, 10)
}

// ── 미제출 알림 발송 ──
const alertWeekStart = ref('')
const alertLoading   = ref(false)
const alertMessage   = ref('')
const alertOk        = ref(false)

const sendAlert = async () => {
  if (!alertWeekStart.value) return
  alertLoading.value = true
  alertMessage.value = ''
  try {
    const res = await window.axios.post('/admin/settings/notify-not-submitted', { week_start: alertWeekStart.value })
    alertOk.value      = res.data.ok
    alertMessage.value = res.data.message
  } catch { alertMessage.value = '발송 실패. Webhook 설정을 확인해 주세요.' }
  finally  { alertLoading.value = false }
}
</script>
