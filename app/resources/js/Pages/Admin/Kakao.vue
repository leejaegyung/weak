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
        <p style="color:#9A8F7A;font-size:13px;">카카오톡 API로 미제출 알림을 발송합니다</p>
      </div>
    </div>

    <div style="max-width:700px;display:flex;flex-direction:column;gap:18px;">

      <!-- 연결 상태 배너 -->
      <div :style="{
        display:'flex', alignItems:'center', gap:'12px',
        padding:'14px 18px', borderRadius:'14px', border:'2px solid #1A1100',
        background: isConnected ? '#DCFCE7' : '#FEF3C7',
      }">
        <div :style="{
          width:'36px', height:'36px', borderRadius:'10px', flexShrink:0,
          background: isConnected ? '#16A34A' : '#D97706',
          border:'2px solid #1A1100',
          display:'flex', alignItems:'center', justifyContent:'center',
        }">
          <svg v-if="isConnected" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
          <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0zM12 9v4M12 17h.01"/></svg>
        </div>
        <div style="flex:1;">
          <div style="font-size:13px;font-weight:700;color:#1A1100;">
            {{ isConnected ? '카카오 계정 연동됨' : '카카오 계정 미연동' }}
          </div>
          <div style="font-size:11px;color:#4A3F2A;margin-top:2px;">
            <span v-if="isConnected && connectedAt">{{ connectedAt }} 연동</span>
            <span v-else-if="isConnected">연동 완료</span>
            <span v-else>아래 단계를 따라 카카오 계정을 연동하세요</span>
          </div>
        </div>
        <button v-if="isConnected" type="button" @click="doDisconnect"
          style="background:#FEE2E2;color:#DC2626;border:2px solid #DC2626;border-radius:8px;padding:6px 12px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;transition:all 0.1s;white-space:nowrap;"
          @mouseenter="e=>{e.currentTarget.style.background='#DC2626';e.currentTarget.style.color='#fff';}"
          @mouseleave="e=>{e.currentTarget.style.background='#FEE2E2';e.currentTarget.style.color='#DC2626';}">
          연동 해제
        </button>
      </div>

      <!-- ── 순서도 ── -->
      <div class="card">
        <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:15px;font-weight:800;margin-bottom:20px;display:flex;align-items:center;gap:8px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          기능 적용 순서
        </div>

        <div style="display:flex;flex-direction:column;gap:0;">

          <!-- Step 1 -->
          <div style="display:flex;gap:14px;">
            <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0;">
              <div style="width:32px;height:32px;border-radius:50%;background:#FDCB40;border:2px solid #1A1100;display:flex;align-items:center;justify-content:center;font-family:'Space Grotesk',sans-serif;font-size:13px;font-weight:800;color:#1A1100;flex-shrink:0;">1</div>
              <div style="width:2px;flex:1;background:#E8E0D0;margin:4px 0;min-height:24px;"></div>
            </div>
            <div style="padding-bottom:20px;flex:1;">
              <div style="font-size:13px;font-weight:700;color:#1A1100;margin-bottom:6px;">카카오 개발자 앱 생성</div>
              <div style="font-size:12px;color:#4A3F2A;line-height:1.7;background:#FFF8EE;border-radius:10px;padding:10px 14px;border:1.5px solid #E8E0D0;">
                <a href="https://developers.kakao.com" target="_blank" style="color:#1D4ED8;font-weight:700;">developers.kakao.com</a> 접속 →
                <strong>내 애플리케이션</strong> → <strong>애플리케이션 추가하기</strong><br>
                앱 이름(예: 주간업무보고), 사업자명 입력 후 저장
              </div>
            </div>
          </div>

          <!-- Step 2 -->
          <div style="display:flex;gap:14px;">
            <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0;">
              <div style="width:32px;height:32px;border-radius:50%;background:#FDCB40;border:2px solid #1A1100;display:flex;align-items:center;justify-content:center;font-family:'Space Grotesk',sans-serif;font-size:13px;font-weight:800;color:#1A1100;flex-shrink:0;">2</div>
              <div style="width:2px;flex:1;background:#E8E0D0;margin:4px 0;min-height:24px;"></div>
            </div>
            <div style="padding-bottom:20px;flex:1;">
              <div style="font-size:13px;font-weight:700;color:#1A1100;margin-bottom:6px;">REST API 키 복사</div>
              <div style="font-size:12px;color:#4A3F2A;line-height:1.7;background:#FFF8EE;border-radius:10px;padding:10px 14px;border:1.5px solid #E8E0D0;">
                앱 설정 → <strong>앱 키</strong> 탭 → <strong>REST API 키</strong> 복사<br>
                아래 입력란에 붙여넣기 후 저장
              </div>
            </div>
          </div>

          <!-- Step 3 -->
          <div style="display:flex;gap:14px;">
            <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0;">
              <div style="width:32px;height:32px;border-radius:50%;background:#FDCB40;border:2px solid #1A1100;display:flex;align-items:center;justify-content:center;font-family:'Space Grotesk',sans-serif;font-size:13px;font-weight:800;color:#1A1100;flex-shrink:0;">3</div>
              <div style="width:2px;flex:1;background:#E8E0D0;margin:4px 0;min-height:24px;"></div>
            </div>
            <div style="padding-bottom:20px;flex:1;">
              <div style="font-size:13px;font-weight:700;color:#1A1100;margin-bottom:6px;">리다이렉트 URI 등록</div>
              <div style="font-size:12px;color:#4A3F2A;line-height:1.7;background:#FFF8EE;border-radius:10px;padding:10px 14px;border:1.5px solid #E8E0D0;">
                앱 설정 → <strong>카카오 로그인</strong> → 활성화 ON<br>
                <strong>Redirect URI</strong>에 아래 주소 추가:
                <div style="margin-top:6px;background:#1A1100;color:#FDCB40;border-radius:8px;padding:6px 12px;font-family:monospace;font-size:11px;word-break:break-all;">
                  {{ redirectUri }}
                </div>
              </div>
            </div>
          </div>

          <!-- Step 4 -->
          <div style="display:flex;gap:14px;">
            <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0;">
              <div style="width:32px;height:32px;border-radius:50%;background:#FDCB40;border:2px solid #1A1100;display:flex;align-items:center;justify-content:center;font-family:'Space Grotesk',sans-serif;font-size:13px;font-weight:800;color:#1A1100;flex-shrink:0;">4</div>
              <div style="width:2px;flex:1;background:#E8E0D0;margin:4px 0;min-height:24px;"></div>
            </div>
            <div style="padding-bottom:20px;flex:1;">
              <div style="font-size:13px;font-weight:700;color:#1A1100;margin-bottom:6px;">동의항목 설정</div>
              <div style="font-size:12px;color:#4A3F2A;line-height:1.7;background:#FFF8EE;border-radius:10px;padding:10px 14px;border:1.5px solid #E8E0D0;">
                앱 설정 → <strong>동의항목</strong> → <strong>카카오톡 메시지 전송</strong>(talk_message) 선택 동의로 설정
              </div>
            </div>
          </div>

          <!-- Step 5 -->
          <div style="display:flex;gap:14px;">
            <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0;">
              <div style="width:32px;height:32px;border-radius:50%;background:#16A34A;border:2px solid #1A1100;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
              </div>
            </div>
            <div style="flex:1;">
              <div style="font-size:13px;font-weight:700;color:#1A1100;margin-bottom:6px;">카카오 계정 인증 → 완료!</div>
              <div style="font-size:12px;color:#4A3F2A;line-height:1.7;background:#FFF8EE;border-radius:10px;padding:10px 14px;border:1.5px solid #E8E0D0;">
                아래 <strong>REST API 키 저장</strong> 후 <strong>카카오 계정 인증</strong> 버튼 클릭<br>
                인증 완료 시 관리자 카카오 계정으로 나에게 보내기로 알림 발송됩니다
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- REST API 키 설정 -->
      <form @submit.prevent="saveKey" class="card">
        <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:15px;font-weight:800;margin-bottom:20px;display:flex;align-items:center;gap:8px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
          REST API 키
        </div>

        <div style="margin-bottom:16px;">
          <label style="font-size:11px;color:#9A8F7A;font-weight:700;display:block;margin-bottom:6px;">카카오 REST API 키</label>
          <div style="display:flex;gap:8px;">
            <input v-model="keyForm.rest_api_key" type="text" class="input-field"
              placeholder="카카오 개발자 콘솔에서 복사한 REST API 키"
              style="flex:1;font-family:monospace;" />
            <button type="submit" :disabled="keyForm.processing" class="btn-primary" style="white-space:nowrap;flex-shrink:0;">
              {{ keyForm.processing ? '저장 중...' : '저장' }}
            </button>
          </div>
        </div>

        <!-- 카카오 계정 인증 버튼 -->
        <div style="padding-top:16px;border-top:2px solid #F5EDDB;">
          <div style="font-size:12px;color:#9A8F7A;margin-bottom:10px;">
            REST API 키 저장 후 아래 버튼으로 카카오 계정을 연동하세요
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a :href="`/admin/settings/kakao/auth`"
              style="display:inline-flex;align-items:center;gap:8px;background:#FEE500;color:#1A1100;border:2px solid #1A1100;border-radius:10px;padding:9px 18px;font-size:13px;font-weight:800;cursor:pointer;font-family:inherit;box-shadow:2px 2px 0 #1A1100;text-decoration:none;transition:all 0.1s;"
              @mouseenter="e=>{e.currentTarget.style.transform='translate(-1px,-1px)';e.currentTarget.style.boxShadow='3px 3px 0 #1A1100';}"
              @mouseleave="e=>{e.currentTarget.style.transform='none';e.currentTarget.style.boxShadow='2px 2px 0 #1A1100';}">
              <svg width="16" height="16" viewBox="0 0 512 512" fill="#1A1100">
                <path d="M255.5 48C141.1 48 48 126.1 48 222.3c0 64.3 40.5 120.8 101.3 153.2l-21.7 80.6c-1.9 7.2 5.8 13.1 12.2 9.1L233.8 401c7.1.8 14.4 1.2 21.7 1.2 114.4 0 207.5-78.1 207.5-174.3S369.9 48 255.5 48z"/>
              </svg>
              카카오 계정 인증
            </a>
            <button v-if="isConnected" type="button" @click="testSend" :disabled="testLoading"
              class="btn-secondary"
              style="display:inline-flex;align-items:center;gap:6px;">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13M22 2L15 22l-4-9-9-4 20-7z"/></svg>
              {{ testLoading ? '발송 중...' : '테스트 메시지 발송' }}
            </button>
          </div>
          <p v-if="testResult" :style="{ fontSize:'11px', marginTop:'8px', fontWeight:'600', color: testResult==='ok' ? '#16A34A' : '#DC2626' }">
            {{ testResult === 'ok' ? '✓ 카카오톡으로 테스트 메시지가 발송되었습니다!' : '✕ 발송 실패. 연동 상태를 확인해 주세요.' }}
          </p>
        </div>
      </form>

      <!-- 미제출 알림 수동 발송 -->
      <div class="card">
        <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:15px;font-weight:800;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          미제출 알림 발송
        </div>
        <p style="font-size:12px;color:#9A8F7A;margin-bottom:16px;">
          해당 주차에 보고서를 제출하지 않은 팀원 목록을 카카오톡(나에게 보내기)으로 발송합니다.
        </p>

        <div v-if="!isConnected"
          style="background:#FEF3C7;border:2px solid #D97706;border-radius:10px;padding:12px 16px;font-size:12px;color:#92400E;font-weight:600;">
          ⚠️ 카카오 계정이 연동되지 않았습니다. 위 단계를 완료해 주세요.
        </div>

        <div v-else style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
          <div style="flex:1;min-width:160px;">
            <label style="font-size:11px;color:#9A8F7A;font-weight:700;display:block;margin-bottom:6px;">주차 시작일 (월요일)</label>
            <input type="date" v-model="alertWeekStart" class="input-field" />
          </div>
          <button @click="sendAlert" :disabled="!alertWeekStart || alertLoading"
            style="background:#FEE500;color:#1A1100;border:2px solid #1A1100;border-radius:12px;padding:9px 18px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:2px 2px 0 #1A1100;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;flex-shrink:0;"
            :style="{ opacity: !alertWeekStart || alertLoading ? 0.5 : 1 }">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13M22 2L15 22l-4-9-9-4 20-7z"/></svg>
            {{ alertLoading ? '발송 중...' : '미제출 알림 발송' }}
          </button>
        </div>
        <p v-if="alertMessage" :style="{ fontSize:'12px', marginTop:'10px', fontWeight:'600', color: alertOk ? '#16A34A' : '#DC2626' }">
          {{ alertMessage }}
        </p>
      </div>

      <!-- 참고 안내 -->
      <div style="background:#F0F9FF;border:2px solid #BAE6FD;border-radius:14px;padding:16px 18px;">
        <div style="font-size:12px;font-weight:700;color:#0369A1;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          알림 방식 안내
        </div>
        <ul style="font-size:12px;color:#0C4A6E;line-height:2;margin:0;padding-left:16px;">
          <li>현재 방식: <strong>나에게 보내기</strong> — 관리자 카카오 계정으로 발송됩니다</li>
          <li>친구에게 직접 보내기는 카카오 비즈 채널 심사 필요 (v2.0 예정)</li>
          <li>테스트 메시지는 관리자 본인 카카오톡으로 수신됩니다</li>
        </ul>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  rest_api_key: { type: String,  default: '' },
  is_connected: { type: Boolean, default: false },
  connected_at: { type: String,  default: '' },
  redirect_uri:  { type: String,  default: '' },
})

const isConnected = ref(props.is_connected)
const connectedAt = ref(props.connected_at)
const redirectUri  = ref(props.redirect_uri)

// REST API 키 저장 폼
const keyForm = useForm({ rest_api_key: props.rest_api_key })
const saveKey = () => keyForm.post('/admin/settings/kakao')

// 테스트 발송
const testLoading = ref(false)
const testResult  = ref(null)
const testSend = async () => {
  testLoading.value = true
  testResult.value  = null
  try {
    const res = await window.axios.post('/admin/settings/kakao/test')
    testResult.value = res.data.ok ? 'ok' : 'fail'
  } catch { testResult.value = 'fail' }
  finally  { testLoading.value = false }
}

// 연동 해제
const doDisconnect = () => {
  if (!confirm('카카오 연동을 해제하시겠습니까?')) return
  router.post('/admin/settings/kakao/disconnect', {}, {
    onSuccess: () => { isConnected.value = false; connectedAt.value = '' },
  })
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
  } catch { alertMessage.value = '발송 실패. 카카오 연동 상태를 확인해 주세요.' }
  finally  { alertLoading.value = false }
}
</script>
