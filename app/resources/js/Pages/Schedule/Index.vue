<template>
  <AppLayout page-title="팀 일정판">
    <!-- 헤더 -->
    <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
      <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
          <div style="background:#FDCB40;border:2px solid #1A1100;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M8 2v3M16 2v3M3.5 9.5h17M3 6.5h18a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V7.5a1 1 0 0 1 1-1z"/>
            </svg>
          </div>
          <div>
            <h1 style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:26px;font-weight:700;letter-spacing:-0.03em;">팀 주간 일정판</h1>
            <p style="color:#9A8F7A;font-size:13px;">
              금주 {{ fmtRange(currDates[0], currDates[4]) }} &nbsp;·&nbsp; 차주 {{ fmtRange(nextDates[0], nextDates[4]) }}
            </p>
          </div>
        </div>
      </div>

      <!-- 주차 네비게이션 -->
      <div style="display:flex;gap:8px;align-items:center;">
        <Link :href="`/schedules?week=${prevWeek}`" class="btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:5px;">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
          이전 주
        </Link>
        <Link v-if="!isCurrentWeek" href="/schedules"
          style="background:#FD4401;color:#fff;border:2px solid #1A1100;border-radius:8px;padding:5px 14px;font-size:12px;font-weight:700;display:flex;align-items:center;text-decoration:none;">
          오늘
        </Link>
        <div v-else
          style="background:#FD4401;color:#fff;border:2px solid #1A1100;border-radius:8px;padding:5px 14px;font-size:12px;font-weight:700;">
          이번 주
        </div>
        <Link :href="`/schedules?week=${nextWeek}`" class="btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:5px;">
          다음 주
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
        </Link>
      </div>
    </div>

    <!-- 안내 -->
    <p style="font-size:12px;color:#9A8F7A;margin-bottom:12px;">
      셀을 클릭하면 편집할 수 있습니다 (본인 행만 수정 가능{{ isAdmin ? ', 관리자는 전체 수정 가능' : '' }})
      <span v-if="isAdmin" style="margin-left:8px;color:#FDCB40;background:#1A1100;border-radius:4px;padding:1px 7px;font-size:11px;font-weight:700;">
        ≡ 드래그하여 순서 변경 가능
      </span>
    </p>

    <!-- 팀 일정 그리드 -->
    <div class="card" style="overflow:auto;padding:0;">
      <table style="width:100%;border-collapse:collapse;min-width:900px;">
        <!-- 주차 헤더 -->
        <thead>
          <tr style="background:#F5EDDB;border-bottom:2px solid #1A1100;">
            <th :style="nameColStyle">이름</th>
            <th colspan="5" style="padding:8px 14px;text-align:center;font-size:12px;font-weight:700;color:#1A1100;border-right:2px solid #1A1100;font-family:'Space Grotesk','Noto Sans KR',sans-serif;">
              금 주
            </th>
            <th colspan="5" style="padding:8px 14px;text-align:center;font-size:12px;font-weight:700;color:#1A1100;font-family:'Space Grotesk','Noto Sans KR',sans-serif;">
              차 주
            </th>
          </tr>
          <!-- 날짜 헤더 -->
          <tr style="background:#F5EDDB;border-bottom:2px solid #1A1100;">
            <th :style="nameColStyle" style="padding:8px 14px;"></th>
            <th v-for="(date, i) in [...currDates, ...nextDates]" :key="date"
              :style="{
                padding:'8px 6px',
                textAlign:'center',
                fontSize:'12px',
                fontWeight:'700',
                borderRight: i < 9 ? (i===4 ? '2px solid #1A1100' : '1.5px solid rgba(26,17,0,0.15)') : 'none',
                background: isToday(date) ? '#FFF0A0' : 'transparent',
                minWidth: '80px',
              }">
              <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;">{{ DAY_KR[i % 5] }}</div>
              <div style="font-size:11px;color:#9A8F7A;margin-top:2px;">{{ date.substring(5).replace('-','/') }}</div>
            </th>
          </tr>
        </thead>

        <!-- 팀원 행 -->
        <tbody>
          <tr v-for="(user, ui) in orderedUsers" :key="user.id"
            :draggable="isAdmin"
            :style="{
              borderBottom: ui < orderedUsers.length-1 ? '2px solid #1A1100' : 'none',
              transition: 'opacity 0.15s',
              opacity: dragSrcIdx === ui ? '0.4' : '1',
              background: dragOverIdx === ui && dragSrcIdx !== ui ? '#FFF0A0' : 'transparent',
            }"
            @dragstart="onDragStart($event, ui)"
            @dragover.prevent="onDragOver(ui)"
            @dragleave="onDragLeave"
            @drop.prevent="onDrop(ui)"
            @dragend="onDragEnd">

            <!-- 이름 열 -->
            <td style="padding:10px 14px;border-right:2px solid #1A1100;background:#F5EDDB;vertical-align:middle;white-space:nowrap;">
              <div style="display:flex;align-items:center;gap:8px;">
                <!-- 드래그 핸들 (관리자만) -->
                <div v-if="isAdmin"
                  style="cursor:grab;color:#C5BAA8;flex-shrink:0;padding:2px;display:flex;align-items:center;user-select:none;"
                  title="드래그하여 순서 변경">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                    <rect x="4" y="4" width="16" height="2" rx="1"/>
                    <rect x="4" y="11" width="16" height="2" rx="1"/>
                    <rect x="4" y="18" width="16" height="2" rx="1"/>
                  </svg>
                </div>
                <!-- 이름 + 아바타 (보고서 있으면 클릭 이동) -->
                <div
                  @click.stop="weekReportMap[user.id] && router.get(`/reports/${weekReportMap[user.id]}`)"
                  :style="{ display:'flex', alignItems:'center', gap:'6px', cursor: weekReportMap[user.id] ? 'pointer' : 'default' }"
                  :title="weekReportMap[user.id] ? '주간보고 보기' : '이번 주 보고서 없음'">
                  <div :style="{ width:'28px', height:'28px', borderRadius:'50%', background: avatarColor(user.id), border:'2px solid #1A1100', display:'flex', alignItems:'center', justifyContent:'center', color:'#fff', fontSize:'11px', fontWeight:'700', flexShrink:0, fontFamily:'\'Space Grotesk\',sans-serif' }">
                    {{ user.name.charAt(0) }}
                  </div>
                  <div>
                    <div :style="{ fontSize:'12px', fontWeight:'700', textDecoration: weekReportMap[user.id] ? 'underline' : 'none', textDecorationColor:'#9A8F7A', textUnderlineOffset:'2px' }">{{ user.name }}</div>
                    <div v-if="user.position" style="font-size:10px;color:#9A8F7A;">{{ user.position }}</div>
                  </div>
                  <!-- 보고서 있음 표시 -->
                  <span v-if="weekReportMap[user.id]"
                    style="font-size:9px;background:#DBEAFE;color:#1D6FE9;border:1px solid #1D6FE9;border-radius:4px;padding:1px 5px;font-weight:700;flex-shrink:0;">
                    보고서
                  </span>
                </div>
              </div>
            </td>

            <!-- 날짜 셀들 -->
            <td v-for="(date, di) in [...currDates, ...nextDates]" :key="date"
              :style="{
                borderRight: di < 9 ? (di===4 ? '2px solid #1A1100' : '1.5px solid rgba(26,17,0,0.1)') : 'none',
                background: isToday(date) ? '#FFF0A0' : 'transparent',
                padding:'4px',
                verticalAlign:'top',
                minWidth:'80px',
              }">
              <textarea
                v-model="localSchedules[user.id][date]"
                rows="3"
                :disabled="!canEdit(user.id)"
                :placeholder="canEdit(user.id) ? '일정 입력...' : ''"
                @blur="saveCell(user.id, date)"
                style="width:100%;border:2px solid transparent;border-radius:6px;padding:5px 7px;background:transparent;color:#1A1100;font-size:11px;font-family:inherit;outline:none;resize:none;line-height:1.5;transition:border-color 0.1s,background 0.1s;"
                :style="!canEdit(user.id) ? { cursor:'default', color:'#4A3F2A' } : {}"
                @focus="e=>{ if(canEdit(user.id)){e.target.style.background='#fff';e.target.style.borderColor='#FD4401';} }"
                @blur.capture="e=>{ e.target.style.background='transparent';e.target.style.borderColor='transparent'; }"
              ></textarea>
              <!-- 저장 상태 표시 -->
              <div style="height:14px;text-align:right;padding-right:4px;">
                <span v-if="savingKey===`${user.id}:${date}`" style="font-size:10px;color:#9A8F7A;">저장 중...</span>
                <span v-else-if="savedKey===`${user.id}:${date}`" style="font-size:10px;color:#16A34A;font-weight:700;">✓</span>
              </div>
            </td>
          </tr>

          <!-- 사용자 없을 때 -->
          <tr v-if="orderedUsers.length === 0">
            <td colspan="11" style="padding:48px;text-align:center;color:#9A8F7A;font-size:13px;">등록된 팀원이 없습니다</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- 순서 저장 알림 -->
    <Transition name="toast">
      <div v-if="orderSaved"
        style="position:fixed;bottom:24px;right:24px;background:#1A1100;color:#FDCB40;border-radius:12px;padding:10px 20px;font-size:13px;font-weight:700;font-family:'Space Grotesk','Noto Sans KR',sans-serif;box-shadow:4px 4px 0 rgba(0,0,0,0.3);z-index:200;display:flex;align-items:center;gap:8px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
        팀원 순서가 저장되었습니다
      </div>
    </Transition>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  users:          { type: Array,   default: () => [] },
  teamSchedules:  { type: Object,  default: () => ({}) },
  currDates:      { type: Array,   default: () => [] },
  nextDates:      { type: Array,   default: () => [] },
  weekStart:      { type: String,  default: '' },
  currentUserId:  { type: Number,  default: 0 },
  isAdmin:        { type: Boolean, default: false },
  prevWeek:       { type: String,  default: '' },
  nextWeek:       { type: String,  default: '' },
  isCurrentWeek:  { type: Boolean, default: true },
  weekReportMap:  { type: Object,  default: () => ({}) },
})

const DAY_KR = ['월', '화', '수', '목', '금']

const AVATAR_COLORS = ['#FD4401','#16a34a','#2563eb','#9333ea','#d97706','#0891b2','#dc2626','#65a30d']
const avatarColor = (id) => AVATAR_COLORS[id % AVATAR_COLORS.length]

const today = new Date().toISOString().slice(0, 10)
const isToday = (d) => d === today

// 이름 열 공통 스타일 (드래그 핸들 유무에 따라 너비 조정)
const nameColStyle = {
  width: props.isAdmin ? '130px' : '100px',
  padding: '10px 14px',
  textAlign: 'left',
  fontSize: '11px',
  fontWeight: '700',
  color: '#9A8F7A',
  textTransform: 'uppercase',
  letterSpacing: '0.06em',
  borderRight: '2px solid #1A1100',
  fontFamily: '\'Space Grotesk\',\'Noto Sans KR\',sans-serif',
  verticalAlign: 'middle',
}

// ── 순서 관리 ──────────────────────────────────────────
const orderedUsers = ref([...props.users])

const dragSrcIdx  = ref(null)
const dragOverIdx = ref(null)
const orderSaved  = ref(false)
let orderSavedTimer = null

const onDragStart = (e, idx) => {
  dragSrcIdx.value = idx
  e.dataTransfer.effectAllowed = 'move'
}

const onDragOver = (idx) => {
  dragOverIdx.value = idx
}

const onDragLeave = () => {
  // dragOver 인덱스는 drop 시 확정, leave 시 초기화하지 않음 (깜빡임 방지)
}

const onDrop = async (targetIdx) => {
  const src = dragSrcIdx.value
  if (src === null || src === targetIdx) {
    dragSrcIdx.value  = null
    dragOverIdx.value = null
    return
  }

  // 로컬 순서 즉시 변경
  const arr = [...orderedUsers.value]
  const [moved] = arr.splice(src, 1)
  arr.splice(targetIdx, 0, moved)
  orderedUsers.value = arr
  dragSrcIdx.value   = null
  dragOverIdx.value  = null

  // 서버에 순서 저장
  try {
    await window.axios.post('/admin/users/reorder', {
      order: arr.map(u => u.id),
    })
    clearTimeout(orderSavedTimer)
    orderSaved.value = true
    orderSavedTimer  = setTimeout(() => { orderSaved.value = false }, 2500)
  } catch (e) {
    console.error('순서 저장 실패', e)
  }
}

const onDragEnd = () => {
  dragSrcIdx.value  = null
  dragOverIdx.value = null
}

// ── 스케줄 편집 ────────────────────────────────────────
const localSchedules = reactive({})
for (const user of props.users) {
  localSchedules[user.id] = {}
  const allDates = [...props.currDates, ...props.nextDates]
  for (const date of allDates) {
    localSchedules[user.id][date] = props.teamSchedules[user.id]?.[date] ?? ''
  }
}

const fmtRange = (start, end) => {
  if (!start || !end) return ''
  return start.substring(5).replace('-', '/') + ' – ' + end.substring(5).replace('-', '/')
}

const canEdit = (userId) => props.isAdmin || userId === props.currentUserId

const savingKey = ref('')
const savedKey  = ref('')

const saveCell = async (userId, date) => {
  if (!canEdit(userId)) return
  const key = `${userId}:${date}`
  savingKey.value = key
  savedKey.value  = ''
  try {
    await window.axios.post('/schedules/upsert', {
      date,
      content:  localSchedules[userId][date],
      user_id:  props.isAdmin ? userId : undefined,
    })
    savedKey.value = key
    setTimeout(() => { if (savedKey.value === key) savedKey.value = '' }, 2000)
  } catch (e) { console.error(e) }
  finally { if (savingKey.value === key) savingKey.value = '' }
}
</script>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.25s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(12px); }
</style>
