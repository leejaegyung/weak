<template>
  <AppLayout page-title="개인설정">
    <div style="max-width:720px;display:flex;flex-direction:column;gap:20px;">

      <!-- 헤더 -->
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
        <div style="background:#FDCB40;border:2px solid #1A1100;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/>
          </svg>
        </div>
        <div>
          <h1 style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:24px;font-weight:700;letter-spacing:-0.03em;">개인설정</h1>
          <p style="color:#9A8F7A;font-size:13px;">기본 정보 수정 및 점검 사이트 관리</p>
        </div>
      </div>

      <!-- ── 기본 정보 ── -->
      <div class="card">
        <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:14px;font-weight:800;margin-bottom:18px;display:flex;align-items:center;gap:8px;">
          <span>👤</span> 기본 정보
        </div>

        <form @submit.prevent="submitProfile">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
            <div>
              <label style="font-size:11px;font-weight:700;color:#9A8F7A;display:block;margin-bottom:6px;">아이디 (수정 불가)</label>
              <input type="text" :value="profileUser.username" disabled
                class="input-field" style="background:#F5EDDB;color:#9A8F7A;cursor:not-allowed;" />
            </div>
            <div>
              <label style="font-size:11px;font-weight:700;color:#9A8F7A;display:block;margin-bottom:6px;">이름 <span style="color:#FD4401;">*</span></label>
              <input type="text" v-model="infoForm.name" class="input-field" placeholder="이름을 입력하세요" required />
              <p v-if="infoErrors.name" style="font-size:11px;color:#DC2626;margin-top:4px;">{{ infoErrors.name }}</p>
            </div>
            <div>
              <label style="font-size:11px;font-weight:700;color:#9A8F7A;display:block;margin-bottom:6px;">직급</label>
              <input type="text" v-model="infoForm.position" class="input-field" placeholder="사원, 대리, 과장, 팀장 등" />
            </div>
            <div>
              <label style="font-size:11px;font-weight:700;color:#9A8F7A;display:block;margin-bottom:6px;">권한</label>
              <input type="text" :value="profileUser.role === 'admin' ? '관리자' : '일반사원'" disabled
                class="input-field" style="background:#F5EDDB;color:#9A8F7A;cursor:not-allowed;" />
            </div>
          </div>

          <!-- 비밀번호 변경 (토글) -->
          <div style="margin-top:4px;">
            <button type="button" @click="showPwSection = !showPwSection"
              style="display:inline-flex;align-items:center;gap:5px;background:none;border:1.5px dashed #C5BAA8;border-radius:8px;padding:6px 14px;font-size:12px;color:#9A8F7A;cursor:pointer;font-family:inherit;font-weight:600;transition:all 0.1s;"
              @mouseenter="e=>{e.currentTarget.style.borderColor='#1A1100';e.currentTarget.style.color='#1A1100';}"
              @mouseleave="e=>{e.currentTarget.style.borderColor='#C5BAA8';e.currentTarget.style.color='#9A8F7A';}">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
              {{ showPwSection ? '비밀번호 변경 취소' : '비밀번호 변경' }}
            </button>

            <div v-if="showPwSection" style="margin-top:14px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
              <div>
                <label style="font-size:11px;font-weight:700;color:#9A8F7A;display:block;margin-bottom:6px;">현재 비밀번호 <span style="color:#FD4401;">*</span></label>
                <input type="password" v-model="infoForm.current_password" class="input-field" placeholder="현재 비밀번호" autocomplete="current-password" />
                <p v-if="infoErrors.current_password" style="font-size:11px;color:#DC2626;margin-top:4px;">{{ infoErrors.current_password }}</p>
              </div>
              <div>
                <label style="font-size:11px;font-weight:700;color:#9A8F7A;display:block;margin-bottom:6px;">새 비밀번호 <span style="color:#FD4401;">*</span></label>
                <input type="password" v-model="infoForm.new_password" class="input-field" placeholder="6자 이상" autocomplete="new-password" />
                <p v-if="infoErrors.new_password" style="font-size:11px;color:#DC2626;margin-top:4px;">{{ infoErrors.new_password }}</p>
              </div>
              <div>
                <label style="font-size:11px;font-weight:700;color:#9A8F7A;display:block;margin-bottom:6px;">비밀번호 확인 <span style="color:#FD4401;">*</span></label>
                <input type="password" v-model="infoForm.new_password_confirmation" class="input-field" placeholder="동일하게 입력" autocomplete="new-password" />
              </div>
            </div>
          </div>

          <div style="margin-top:18px;display:flex;justify-content:flex-end;">
            <button type="submit" :disabled="infoSaving"
              style="display:inline-flex;align-items:center;gap:6px;background:#FDCB40;color:#1A1100;border:2px solid #1A1100;border-radius:10px;padding:8px 20px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:2px 2px 0 #1A1100;transition:all 0.1s;"
              @mouseenter="e=>{e.currentTarget.style.transform='translate(-1px,-1px)';e.currentTarget.style.boxShadow='3px 3px 0 #1A1100';}"
              @mouseleave="e=>{e.currentTarget.style.transform='none';e.currentTarget.style.boxShadow='2px 2px 0 #1A1100';}">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
              {{ infoSaving ? '저장 중...' : '저장' }}
            </button>
          </div>
        </form>
      </div>

      <!-- ── 점검 사이트 관리 ── -->
      <div class="card">
        <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:14px;font-weight:800;margin-bottom:6px;display:flex;align-items:center;gap:8px;">
          <span>🔗</span> 점검 사이트 목록
        </div>
        <p style="font-size:12px;color:#9A8F7A;margin-bottom:16px;">
          등록한 사이트명은 보고서 작성 항목명 자동완성 및 팀 일정 추가 팝업에서 빠르게 선택할 수 있습니다.
        </p>

        <!-- 사이트 목록 -->
        <div style="display:flex;flex-direction:column;gap:7px;margin-bottom:14px;">
          <div v-if="localSites.length === 0" style="text-align:center;padding:20px;color:#9A8F7A;font-size:13px;border:1.5px dashed #D0C9BC;border-radius:10px;">
            등록된 사이트가 없습니다
          </div>
          <div v-for="site in localSites" :key="site.id"
            style="display:flex;align-items:center;gap:10px;background:#F5EDDB;border:2px solid #1A1100;border-radius:10px;padding:9px 14px;box-shadow:2px 2px 0 #1A1100;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#9A8F7A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
            </svg>
            <span style="flex:1;font-size:13px;font-weight:600;color:#1A1100;">{{ site.name }}</span>
            <button type="button" @click="deleteSite(site)"
              style="background:none;border:none;cursor:pointer;color:#C5BAA8;padding:2px;border-radius:5px;transition:color 0.1s;"
              @mouseenter="e=>e.currentTarget.style.color='#DC2626'"
              @mouseleave="e=>e.currentTarget.style.color='#C5BAA8'">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
          </div>
        </div>

        <!-- 사이트 추가 폼 -->
        <div style="display:flex;gap:8px;">
          <input
            v-model="newSiteName"
            type="text"
            class="input-field"
            placeholder="사이트명 입력 (예: 키미 안토넬리, G-SAM)"
            style="flex:1;"
            @keydown.enter.prevent="addSite"
            maxlength="100"
          />
          <button type="button" @click="addSite" :disabled="!newSiteName.trim() || siteAdding"
            style="display:inline-flex;align-items:center;gap:5px;background:#1A1100;color:#FDCB40;border:2px solid #1A1100;border-radius:10px;padding:8px 16px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;transition:all 0.1s;white-space:nowrap;"
            :style="{ opacity: !newSiteName.trim() || siteAdding ? 0.5 : 1, cursor: !newSiteName.trim() || siteAdding ? 'not-allowed' : 'pointer' }">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
            추가
          </button>
        </div>
      </div>

    </div>
    <!-- 토스트 -->
    <Transition name="toast">
      <div v-if="toast"
        style="position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#1A1100;color:#FDCB40;border-radius:12px;padding:10px 22px;font-size:13px;font-weight:700;font-family:'Space Grotesk','Noto Sans KR',sans-serif;box-shadow:4px 4px 0 rgba(0,0,0,0.3);z-index:200;display:flex;align-items:center;gap:8px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
        {{ toast }}
      </div>
    </Transition>
  </AppLayout>
</template>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.25s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translate(-50%, 12px); }
</style>

<script setup>
import { ref } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  profileUser: { type: Object, required: true },
  sites:       { type: Array,  default: () => [] },
})

// ── 기본 정보 폼 ──────────────────────────────────────
const showPwSection = ref(false)
const infoSaving    = ref(false)
const infoErrors    = ref({})

const infoForm = ref({
  name:                    props.profileUser.name,
  position:                props.profileUser.position,
  current_password:        '',
  new_password:            '',
  new_password_confirmation: '',
})

const submitProfile = async () => {
  infoSaving.value = true
  infoErrors.value = {}
  try {
    await window.axios.put('/profile', infoForm.value)
    // Inertia redirect 대신 axios → 성공 토스트 표시 후 새로고침
    showToast('개인정보가 저장되었습니다.')
    infoForm.value.current_password        = ''
    infoForm.value.new_password            = ''
    infoForm.value.new_password_confirmation = ''
    showPwSection.value = false
  } catch (e) {
    if (e.response?.data?.errors) {
      infoErrors.value = Object.fromEntries(
        Object.entries(e.response.data.errors).map(([k, v]) => [k, v[0]])
      )
    }
  } finally {
    infoSaving.value = false
  }
}

// ── 점검 사이트 ───────────────────────────────────────
const localSites  = ref([...props.sites])
const newSiteName = ref('')
const siteAdding  = ref(false)

const addSite = async () => {
  const name = newSiteName.value.trim()
  if (!name || siteAdding.value) return
  siteAdding.value = true
  try {
    const res = await window.axios.post('/profile/sites', { name })
    localSites.value.push(res.data)
    newSiteName.value = ''
    showToast('사이트가 추가되었습니다.')
  } catch (e) {
    console.error(e)
  } finally {
    siteAdding.value = false
  }
}

const deleteSite = async (site) => {
  try {
    await window.axios.delete(`/profile/sites/${site.id}`)
    localSites.value = localSites.value.filter(s => s.id !== site.id)
  } catch (e) {
    console.error(e)
  }
}

// ── 토스트 ────────────────────────────────────────────
const toast     = ref('')
const showToast = (msg) => {
  toast.value = msg
  setTimeout(() => { toast.value = '' }, 2200)
}
</script>
