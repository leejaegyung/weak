<template>
  <AppLayout page-title="보고서 목록">
    <!-- 헤더 -->
    <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
      <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
          <div style="background:#FD4401;border:2px solid #1A1100;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M16 13H8M16 17H8M10 9H8"/>
            </svg>
          </div>
          <h1 style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:26px;font-weight:700;letter-spacing:-0.03em;">보고서 목록</h1>
        </div>
        <p style="color:#9A8F7A;font-size:13px;margin-left:42px;">팀원들의 주간 보고서를 확인하세요</p>
      </div>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <!-- 주차 네비게이션 -->
        <div style="display:flex;align-items:center;gap:6px;">
          <button @click="goWeek(prevWeek)"
            class="btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:4px;">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
            이전
          </button>
          <div style="background:#FFF0A0;border:2px solid #1A1100;border-radius:10px;padding:6px 14px;font-size:12px;font-weight:800;font-family:'Space Grotesk','Noto Sans KR',sans-serif;min-width:100px;text-align:center;box-shadow:2px 2px 0 #1A1100;">
            {{ weekLabel }}
            <div style="font-size:10px;font-weight:400;color:#9A8F7A;margin-top:1px;">{{ fmtShort(weekStart) }} ~ {{ fmtShort(weekEnd) }}</div>
          </div>
          <button v-if="!isCurrentWeek" @click="goWeek('')"
            style="background:#FD4401;color:#fff;border:2px solid #1A1100;border-radius:8px;padding:5px 12px;font-size:11px;font-weight:700;cursor:pointer;font-family:inherit;">
            이번 주
          </button>
          <button @click="goWeek(nextWeek)"
            class="btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:4px;">
            다음
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
          </button>
        </div>

        <!-- 미제출 알림 (관리자만) -->
        <button v-if="isAdmin" @click="sendNotSubmittedAlert"
          :disabled="alertSending"
          class="btn-secondary btn-sm"
          style="display:inline-flex;align-items:center;gap:5px;">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13M22 2L15 22l-4-9-9-4 20-7z"/></svg>
          {{ alertSending ? '발송 중...' : '미제출 알림' }}
        </button>

        <!-- Excel 다운로드 (관리자만) -->
        <a v-if="isAdmin" :href="`/export/weekly?curr_start=${weekStart}`"
          class="btn-secondary btn-sm"
          style="display:inline-flex;align-items:center;gap:5px;text-decoration:none;">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>
          </svg>
          Excel
        </a>

        <Link href="/reports/create" class="btn-primary">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
          보고서 작성
        </Link>
      </div>
    </div>

    <!-- 통계 카드 4개 -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px;">
      <div v-for="stat in statsCards" :key="stat.label"
        class="card"
        :style="{ background: stat.bg, padding:'18px 20px', display:'flex', alignItems:'center', gap:'14px', cursor:'pointer', transition:'transform 0.1s,box-shadow 0.1s' }"
        @click="setFilter(stat.filter)"
        @mouseenter="e=>{e.currentTarget.style.transform='translate(-2px,-2px)';e.currentTarget.style.boxShadow='6px 6px 0 #1A1100';}"
        @mouseleave="e=>{e.currentTarget.style.transform='none';e.currentTarget.style.boxShadow='4px 4px 0 #1A1100';}">
        <div style="width:44px;height:44px;border-radius:10px;background:rgba(26,17,0,0.08);border:2px solid #1A1100;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <span style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:20px;font-weight:800;">{{ stat.val }}</span>
        </div>
        <span style="font-weight:600;font-size:13px;">{{ stat.label }}</span>
      </div>
    </div>

    <!-- 검색 + 필터 탭 -->
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:16px;">
      <div style="position:relative;flex:1;max-width:280px;">
        <span style="position:absolute;left:11px;top:50%;transform:translateY(-50%);pointer-events:none;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9A8F7A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/></svg>
        </span>
        <input v-model="searchInput" @input="onSearch"
          placeholder="이름 또는 기간 검색..."
          style="width:100%;background:#fff;border:2px solid #1A1100;border-radius:10px;padding:8px 12px 8px 32px;color:#1A1100;font-size:13px;font-family:inherit;outline:none;transition:border-color 0.12s;"
          @focus="e=>e.target.style.borderColor='#FD4401'"
          @blur="e=>e.target.style.borderColor='#1A1100'" />
      </div>
      <button v-for="f in filterBtns" :key="f.val" @click="setFilter(f.val)"
        :style="{
          background: activeFilter===f.val ? '#FD4401' : '#fff',
          color: activeFilter===f.val ? '#fff' : '#1A1100',
          border: '2px solid #1A1100',
          borderRadius: '10px',
          padding: '7px 16px',
          fontSize: '12px',
          fontWeight: '700',
          cursor: 'pointer',
          fontFamily: 'inherit',
          boxShadow: activeFilter===f.val ? '2px 2px 0 #1A1100' : 'none',
          transition: 'all 0.12s',
        }">{{ f.label }}</button>
    </div>

    <!-- 테이블 -->
    <div class="card" style="overflow:hidden;padding:0;">
      <div style="display:grid;grid-template-columns:2fr 2fr 1fr 1fr 1fr 1.5fr 44px;padding:10px 20px;border-bottom:2px solid #1A1100;background:#F5EDDB;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#9A8F7A;font-family:'Space Grotesk','Noto Sans KR',sans-serif;">
        <span>작성자</span><span>보고 기간</span><span>제출일</span><span>업무</span><span>Todo</span><span>상태</span><span></span>
      </div>

      <div v-for="(r, i) in reports" :key="r.id ?? `ns-${r.user?.id}`"
        style="display:grid;grid-template-columns:2fr 2fr 1fr 1fr 1fr 1.5fr 44px;padding:14px 20px;align-items:center;transition:background 0.12s;"
        :style="{
          borderBottom: i < reports.length-1 ? '1.5px solid #F5EDDB' : 'none',
          cursor: r.status === 'not_submitted' ? 'default' : 'pointer',
          opacity: r.status === 'not_submitted' ? '0.75' : '1',
        }"
        @click="r.status !== 'not_submitted' && router.get(`/reports/${r.id}`)"
        @mouseenter="e=>{ if(r.status !== 'not_submitted') e.currentTarget.style.background='#FFF8EE' }"
        @mouseleave="e=>e.currentTarget.style.background='transparent'">

        <div style="display:flex;align-items:center;gap:10px;">
          <div :style="{ background: avatarColor(r.user?.id ?? r.user_id), width:'30px', height:'30px', borderRadius:'50%', border:'2px solid #1A1100', display:'flex', alignItems:'center', justifyContent:'center', color:'#fff', fontSize:'12px', fontWeight:'700', flexShrink:0, fontFamily:'\'Space Grotesk\',sans-serif' }">
            {{ r.user?.name?.charAt(0) ?? '?' }}
          </div>
          <div>
            <div style="font-size:13px;font-weight:700;">{{ r.user?.name ?? '-' }}</div>
            <div style="font-size:11px;color:#9A8F7A;">{{ r.week ?? '-' }}</div>
          </div>
        </div>
        <span style="font-size:12px;color:#4A3F2A;">
          {{ r.curr_start ? `${fmt(r.curr_start)} ~ ${fmt(r.curr_end)}` : '-' }}
        </span>
        <span style="font-size:12px;font-weight:600;">{{ r.submitted_at ? String(r.submitted_at).substring(0,10) : '-' }}</span>
        <span style="font-family:'Space Grotesk',sans-serif;font-size:15px;font-weight:700;">{{ r.curr_work?.length ?? '-' }}</span>
        <span style="font-family:'Space Grotesk',sans-serif;font-size:15px;font-weight:700;">{{ r.todo_items?.length ?? '-' }}</span>
        <div style="display:flex;align-items:center;gap:6px;">
          <span :class="statusBadge(r.status)">{{ r.status_label }}</span>
        </div>
        <!-- 삭제 버튼 (미제출 행 제외, 관리자 또는 본인 보고서만) -->
        <div @click.stop style="display:flex;justify-content:center;">
          <button v-if="r.status !== 'not_submitted' && (isAdmin || r.user_id === currentUserId)"
            @click="confirmDelete(r)"
            style="background:none;border:none;cursor:pointer;color:#D0C9BC;padding:5px;border-radius:6px;transition:color 0.1s;display:flex;align-items:center;"
            @mouseenter="e=>e.currentTarget.style.color='#DC2626'"
            @mouseleave="e=>e.currentTarget.style.color='#D0C9BC'"
            title="삭제">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
            </svg>
          </button>
        </div>
      </div>

      <div v-if="reports.length === 0"
        style="padding:48px 20px;text-align:center;color:#9A8F7A;font-size:13px;">검색 결과가 없습니다</div>
    </div>

    <!-- 삭제 확인 모달 -->
    <div v-if="deleteTarget"
      style="position:fixed;inset:0;background:rgba(26,17,0,0.45);display:flex;align-items:center;justify-content:center;z-index:100;backdrop-filter:blur(3px);"
      @click.self="deleteTarget=null">
      <div class="card" style="width:380px;padding:28px;text-align:center;">
        <div style="width:48px;height:48px;background:#FEE2E2;border:2px solid #DC2626;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
          </svg>
        </div>
        <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:16px;font-weight:800;color:#1A1100;margin-bottom:8px;">보고서를 삭제할까요?</div>
        <div style="font-size:13px;color:#9A8F7A;line-height:1.7;margin-bottom:22px;">
          <strong style="color:#1A1100;">{{ deleteTarget?.user?.name }}</strong>님의
          <strong style="color:#1A1100;">{{ deleteTarget?.week }}</strong> 보고서가<br>
          영구적으로 삭제됩니다. 되돌릴 수 없습니다.
        </div>
        <div style="display:flex;gap:8px;justify-content:center;">
          <button @click="deleteTarget=null" class="btn-secondary">취소</button>
          <button @click="doDelete"
            style="background:#DC2626;color:#fff;border:2px solid #DC2626;border-radius:10px;padding:8px 20px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:2px 2px 0 #991B1B;transition:all 0.1s;">
            삭제하기
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  reports:       { type: Array,   default: () => [] },
  counts:        { type: Object,  default: () => ({}) },
  filters:       { type: Object,  default: () => ({}) },
  weekStart:     { type: String,  default: '' },
  weekEnd:       { type: String,  default: '' },
  weekLabel:     { type: String,  default: '' },
  prevWeek:      { type: String,  default: '' },
  nextWeek:      { type: String,  default: '' },
  isCurrentWeek: { type: Boolean, default: true },
})

const page          = usePage()
const isAdmin       = computed(() => page.props.auth?.user?.role === 'admin')
const currentUserId = computed(() => page.props.auth?.user?.id)

const activeFilter = ref(props.filters?.status ?? 'all')

// 삭제
const deleteTarget = ref(null)
const confirmDelete = (r) => { deleteTarget.value = r }
const doDelete = () => {
  if (!deleteTarget.value) return
  router.delete(`/reports/${deleteTarget.value.id}`, {
    onSuccess: () => { deleteTarget.value = null },
  })
}
const searchInput  = ref(props.filters?.search ?? '')

const fmtShort = (d) => d ? String(d).substring(5, 10).replace('-', '/') : '-'

const AVATAR_COLORS = ['#FD4401','#16a34a','#2563eb','#9333ea','#d97706','#0891b2']
const avatarColor = (id) => AVATAR_COLORS[(id ?? 0) % AVATAR_COLORS.length]

const statsCards = computed(() => [
  { label:'전체 보고서', val: props.counts.all          ?? 0, bg:'#FDCB40', filter:'all' },
  { label:'미제출',      val: props.counts.notSubmitted ?? 0, bg:'#FFF0A0', filter:'not_submitted' },
  { label:'제출됨',      val: props.counts.submitted    ?? 0, bg:'#DBEAFE', filter:'submitted' },
  { label:'반려됨',      val: props.counts.rejected     ?? 0, bg:'#FEE2E2', filter:'rejected' },
])

const filterBtns = [
  { val:'all',           label:'전체' },
  { val:'not_submitted', label:'미제출' },
  { val:'submitted',     label:'제출됨' },
  { val:'rejected',      label:'반려됨' },
]

const fmt = (d) => d ? String(d).substring(0, 10) : '-'

const statusBadge = (s) => ({
  draft:         'badge-draft',
  submitted:     'badge-submitted',
  reviewed:      'badge-reviewed',
  rejected:      'badge-rejected',
  not_submitted: 'badge-not-submitted',
})[s] ?? 'badge-draft'

// ── 미제출 Webhook 알림 ──
const alertSending = ref(false)
const sendNotSubmittedAlert = async () => {
  if (!props.weekStart) return
  alertSending.value = true
  try {
    const res = await window.axios.post('/admin/settings/notify-not-submitted', { week_start: props.weekStart })
    alert(res.data.message)
  } catch { alert('알림 발송 실패. Webhook 설정을 확인해 주세요.') }
  finally { alertSending.value = false }
}

let timer = null
const onSearch  = () => { clearTimeout(timer); timer = setTimeout(applyFilter, 400) }
const setFilter = (f) => { activeFilter.value = f; applyFilter() }
const goWeek    = (week) => {
  router.get('/reports', {
    week:   week || '',
    status: activeFilter.value === 'all' ? '' : activeFilter.value,
    search: searchInput.value,
  }, { preserveState: false, replace: true })
}
const applyFilter = () => {
  router.get('/reports', {
    week:   props.weekStart,
    status: activeFilter.value === 'all' ? '' : activeFilter.value,
    search: searchInput.value,
  }, { preserveState: true, replace: true })
}
</script>
