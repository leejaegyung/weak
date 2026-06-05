<template>
  <AppLayout page-title="API 키 관리">

    <!-- 헤더 -->
    <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:12px;margin-bottom:24px;max-width:720px;margin-left:auto;margin-right:auto;">
      <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
          <div style="background:#7C3AED;border:2px solid #1A1100;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>
            </svg>
          </div>
          <h1 style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:26px;font-weight:700;letter-spacing:-0.03em;">API 키 관리</h1>
        </div>
        <p style="color:#9A8F7A;font-size:13px;margin-left:42px;">외부 서비스 연동에 사용되는 API 키를 안전하게 관리합니다</p>
      </div>
    </div>

    <!-- API 서비스 카드 목록 -->
    <div style="display:flex;flex-direction:column;gap:16px;max-width:720px;margin:0 auto;width:100%;">
      <div v-for="svc in localServices" :key="svc.id" class="card" style="padding:0;overflow:hidden;">

        <!-- 카드 헤더 -->
        <div style="padding:18px 22px;border-bottom:2px solid #1A1100;background:#F5EDDB;display:flex;align-items:center;gap:14px;">
          <!-- 서비스 아이콘 -->
          <div style="width:40px;height:40px;background:#EDE9FE;border:2px solid #1A1100;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg v-if="svc.id === 'anthropic'" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9.5 2A2.5 2.5 0 0 1 12 4.5v15a2.5 2.5 0 0 1-4.96-.46 2.5 2.5 0 0 1 .37-4.51A2.5 2.5 0 0 1 9.5 9.5"/>
              <path d="M14.5 2A2.5 2.5 0 0 0 12 4.5v15a2.5 2.5 0 0 0 4.96-.46 2.5 2.5 0 0 0-.37-4.51A2.5 2.5 0 0 0 14.5 9.5"/>
            </svg>
          </div>
          <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:2px;">
              <span style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:15px;font-weight:800;">{{ svc.name }}</span>
              <!-- 상태 배지 -->
              <span v-if="svc.is_set"
                style="font-size:10px;font-weight:700;background:#DCFCE7;color:#15803D;border:1.5px solid #86EFAC;border-radius:99px;padding:2px 8px;flex-shrink:0;">
                ✓ 설정됨
              </span>
              <span v-else
                style="font-size:10px;font-weight:700;background:#FEF3C7;color:#B45309;border:1.5px solid #FCD34D;border-radius:99px;padding:2px 8px;flex-shrink:0;">
                미설정
              </span>
            </div>
            <div style="font-size:12px;color:#9A8F7A;">{{ svc.description }}</div>
          </div>
          <!-- 문서 링크 -->
          <a :href="svc.docs_url" target="_blank" rel="noopener noreferrer"
            style="display:inline-flex;align-items:center;gap:4px;font-size:11px;color:#7C3AED;font-weight:700;text-decoration:none;background:#EDE9FE;border:1.5px solid #C4B5FD;border-radius:8px;padding:5px 10px;white-space:nowrap;transition:all 0.1s;"
            @mouseenter="e=>{e.currentTarget.style.background='#DDD6FE';}"
            @mouseleave="e=>{e.currentTarget.style.background='#EDE9FE';}">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14L21 3"/>
            </svg>
            키 발급
          </a>
        </div>

        <!-- 카드 본문 -->
        <div style="padding:20px 22px;">

          <!-- 현재 키 표시 -->
          <div style="margin-bottom:18px;">
            <label style="display:block;font-size:11px;font-weight:700;color:#9A8F7A;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:8px;">현재 API 키</label>
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="flex:1;background:#F5EDDB;border:2px solid #1A1100;border-radius:10px;padding:10px 14px;font-size:13px;font-family:'Space Grotesk',monospace;color:#4A3F2A;letter-spacing:0.05em;min-height:40px;display:flex;align-items:center;">
                <span v-if="svc.is_set">{{ svc.key_preview }}</span>
                <span v-else style="color:#C8BFA8;font-style:italic;font-family:'Noto Sans KR',sans-serif;font-size:12px;">API 키가 설정되지 않았습니다</span>
              </div>
              <!-- 연결 테스트 -->
              <button v-if="svc.is_set" @click="testKey(svc)"
                :disabled="svc._testing"
                style="background:#fff;border:2px solid #1A1100;border-radius:10px;padding:9px 14px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:6px;transition:all 0.1s;white-space:nowrap;box-shadow:2px 2px 0 #1A1100;flex-shrink:0;"
                :style="{ opacity: svc._testing ? 0.6 : 1 }"
                @mouseenter="e=>{if(!svc._testing){e.currentTarget.style.transform='translate(-1px,-1px)';e.currentTarget.style.boxShadow='3px 3px 0 #1A1100';}}"
                @mouseleave="e=>{e.currentTarget.style.transform='none';e.currentTarget.style.boxShadow='2px 2px 0 #1A1100';}">
                <svg v-if="svc._testing" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation:spin 1s linear infinite;"><path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" opacity=".25"/><path d="M12 3a9 9 0 0 1 9 9"/></svg>
                <svg v-else width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                {{ svc._testing ? '테스트 중...' : '연결 테스트' }}
              </button>
            </div>
            <!-- 테스트 결과 -->
            <Transition name="fade">
              <div v-if="svc._testResult" :style="{
                marginTop:'8px',
                padding:'8px 12px',
                borderRadius:'8px',
                fontSize:'12px',
                fontWeight:'700',
                border:'1.5px solid',
                background: svc._testResult.ok ? '#DCFCE7' : '#FEE2E2',
                color:       svc._testResult.ok ? '#15803D'  : '#DC2626',
                borderColor: svc._testResult.ok ? '#86EFAC'  : '#FCA5A5',
              }">
                {{ svc._testResult.ok ? '✓' : '✕' }} {{ svc._testResult.message }}
              </div>
            </Transition>
          </div>

          <!-- 키 변경 폼 (토글) -->
          <div>
            <button v-if="!svc._editing" @click="startEdit(svc)"
              style="background:#7C3AED;color:#fff;border:2px solid #1A1100;border-radius:10px;padding:8px 16px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:2px 2px 0 #1A1100;display:inline-flex;align-items:center;gap:6px;transition:all 0.1s;margin-right:8px;"
              @mouseenter="e=>{e.currentTarget.style.transform='translate(-1px,-1px)';e.currentTarget.style.boxShadow='3px 3px 0 #1A1100';}"
              @mouseleave="e=>{e.currentTarget.style.transform='none';e.currentTarget.style.boxShadow='2px 2px 0 #1A1100';}">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>
              </svg>
              {{ svc.is_set ? 'API 키 변경' : 'API 키 설정' }}
            </button>

            <button v-if="svc.is_set && !svc._editing" @click="clearKey(svc)"
              style="background:#FEE2E2;color:#DC2626;border:2px solid #DC2626;border-radius:10px;padding:8px 14px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:5px;transition:all 0.1s;"
              @mouseenter="e=>{e.currentTarget.style.background='#DC2626';e.currentTarget.style.color='#fff';}"
              @mouseleave="e=>{e.currentTarget.style.background='#FEE2E2';e.currentTarget.style.color='#DC2626';}">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
              키 삭제
            </button>

            <!-- 입력 폼 -->
            <Transition name="expand">
              <div v-if="svc._editing" style="background:#F5EDDB;border:2px solid #1A1100;border-radius:12px;padding:16px;margin-top:4px;">
                <label style="display:block;font-size:12px;font-weight:700;color:#4A3F2A;margin-bottom:8px;">
                  새 API 키 입력
                </label>
                <div style="display:flex;gap:8px;">
                  <div style="flex:1;position:relative;">
                    <input
                      :id="`key-input-${svc.id}`"
                      v-model="svc._newKey"
                      :type="svc._showKey ? 'text' : 'password'"
                      :placeholder="`${svc.name} API 키를 입력하세요`"
                      autocomplete="off"
                      style="width:100%;background:#fff;border:2px solid #1A1100;border-radius:10px;padding:10px 40px 10px 14px;font-size:13px;font-family:'Space Grotesk',monospace;outline:none;transition:border-color 0.12s;box-sizing:border-box;"
                      @focus="e=>e.target.style.borderColor='#7C3AED'"
                      @blur="e=>e.target.style.borderColor='#1A1100'" />
                    <button type="button" @click="svc._showKey = !svc._showKey"
                      style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9A8F7A;display:flex;align-items:center;padding:2px;">
                      <svg v-if="!svc._showKey" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                      <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24M1 1l22 22"/></svg>
                    </button>
                  </div>
                  <button @click="saveKey(svc)"
                    :disabled="!svc._newKey.trim() || svc._saving"
                    style="background:#1A1100;color:#fff;border:2px solid #1A1100;border-radius:10px;padding:10px 18px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:6px;transition:all 0.1s;white-space:nowrap;flex-shrink:0;"
                    :style="{ opacity: !svc._newKey.trim() || svc._saving ? 0.5 : 1 }">
                    <svg v-if="svc._saving" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation:spin 1s linear infinite;"><path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" opacity=".25"/><path d="M12 3a9 9 0 0 1 9 9"/></svg>
                    <svg v-else width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                    {{ svc._saving ? '저장 중...' : '저장' }}
                  </button>
                  <button @click="cancelEdit(svc)"
                    class="btn-secondary"
                    style="flex-shrink:0;">
                    취소
                  </button>
                </div>
                <p style="font-size:11px;color:#9A8F7A;margin-top:8px;">API 키는 데이터베이스에 암호화 없이 저장됩니다. 서버 접근이 제한된 환경에서만 사용하세요.</p>
              </div>
            </Transition>
          </div>
        </div>
      </div>

      <!-- 향후 추가 예정 플레이스홀더 -->
      <div class="card" style="padding:20px 22px;opacity:0.5;border-style:dashed;">
        <div style="display:flex;align-items:center;gap:14px;">
          <div style="width:40px;height:40px;background:#F3F4F6;border:2px dashed #D1D5DB;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9A8F7A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
          </div>
          <div>
            <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:14px;font-weight:700;color:#9A8F7A;margin-bottom:2px;">추가 API 서비스 (예정)</div>
            <div style="font-size:12px;color:#C8BFA8;">향후 더 많은 외부 API 연동이 지원될 예정입니다</div>
          </div>
        </div>
      </div>
    </div>

  </AppLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  apiServices: { type: Array, default: () => [] },
})

const localServices = reactive(
  props.apiServices.map(svc => ({
    ...svc,
    _editing:    false,
    _newKey:     '',
    _showKey:    false,
    _saving:     false,
    _testing:    false,
    _testResult: null,
  }))
)

const startEdit = (svc) => {
  svc._editing = true
  svc._newKey  = ''
  svc._showKey = false
  svc._testResult = null
}

const cancelEdit = (svc) => {
  svc._editing = false
  svc._newKey  = ''
}

const saveKey = (svc) => {
  if (!svc._newKey.trim() || svc._saving) return
  svc._saving = true

  router.post('/admin/settings/api', { service: svc.id, api_key: svc._newKey.trim() }, {
    onSuccess: () => {
      svc.is_set      = true
      svc.key_preview = svc._newKey.slice(0, 8) + '•••••' + svc._newKey.slice(-4)
      svc._editing    = false
      svc._newKey     = ''
      svc._testResult = null
    },
    onFinish: () => { svc._saving = false },
  })
}

const clearKey = (svc) => {
  if (!confirm(`${svc.name} API 키를 삭제할까요?`)) return

  router.post('/admin/settings/api', { service: svc.id, api_key: '' }, {
    onSuccess: () => {
      svc.is_set      = false
      svc.key_preview = ''
      svc._testResult = null
    },
  })
}

const testKey = async (svc) => {
  if (svc._testing) return
  svc._testing    = true
  svc._testResult = null

  try {
    const res = await window.axios.post('/admin/settings/api/test', { service: svc.id })
    svc._testResult = res.data
  } catch (e) {
    svc._testResult = { ok: false, message: '요청 실패: ' + (e.response?.data?.message ?? e.message) }
  } finally {
    svc._testing = false
  }
}
</script>

<style scoped>
.expand-enter-active, .expand-leave-active { transition: all 0.2s ease; overflow: hidden; }
.expand-enter-from, .expand-leave-to { opacity: 0; max-height: 0; }
.expand-enter-to, .expand-leave-from { max-height: 300px; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
