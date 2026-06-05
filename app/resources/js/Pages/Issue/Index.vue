<template>
  <AppLayout page-title="요구/이슈">

    <!-- 헤더 -->
    <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
      <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
          <div style="background:#7C3AED;border:2px solid #1A1100;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
          </div>
          <h1 style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:26px;font-weight:700;letter-spacing:-0.03em;">요구/이슈</h1>
        </div>
        <p style="color:#9A8F7A;font-size:13px;margin-left:42px;">시스템 요구사항 및 이슈를 등록하고 관리합니다</p>
      </div>
      <button @click="showForm=!showForm"
        style="background:#7C3AED;color:#fff;border:2px solid #1A1100;border-radius:10px;padding:9px 18px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:2px 2px 0 #1A1100;display:inline-flex;align-items:center;gap:6px;transition:all 0.1s;"
        @mouseenter="e=>{e.currentTarget.style.transform='translate(-1px,-1px)';e.currentTarget.style.boxShadow='3px 3px 0 #1A1100';}"
        @mouseleave="e=>{e.currentTarget.style.transform='none';e.currentTarget.style.boxShadow='2px 2px 0 #1A1100';}">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
        새 이슈 등록
      </button>
    </div>

    <!-- 작성 폼 -->
    <Transition name="form-slide">
      <div v-if="showForm" class="card" style="padding:22px;margin-bottom:20px;">
        <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:14px;font-weight:800;margin-bottom:14px;display:flex;align-items:center;gap:8px;">
          <span style="background:#EDE9FE;border:2px solid #7C3AED;border-radius:8px;width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </span>
          이슈/요구사항 등록
        </div>
        <form @submit.prevent="submitIssue">
          <div style="margin-bottom:12px;">
            <label style="display:block;font-size:12px;font-weight:700;color:#4A3F2A;margin-bottom:5px;">제목 *</label>
            <input v-model="newTitle" required maxlength="200" placeholder="이슈 또는 요구사항 제목을 입력하세요"
              style="width:100%;background:#fff;border:2px solid #1A1100;border-radius:10px;padding:9px 13px;font-size:13px;font-family:inherit;outline:none;transition:border-color 0.12s;box-sizing:border-box;"
              @focus="e=>e.target.style.borderColor='#7C3AED'"
              @blur="e=>e.target.style.borderColor='#1A1100'" />
          </div>
          <div style="margin-bottom:16px;">
            <label style="display:block;font-size:12px;font-weight:700;color:#4A3F2A;margin-bottom:5px;">상세 내용 *</label>
            <textarea v-model="newContent" required maxlength="2000" rows="4"
              placeholder="요청 사항 또는 이슈 상황을 구체적으로 설명해 주세요. 담당자가 처리할 수 있도록 충분한 정보를 포함해 주세요."
              style="width:100%;background:#fff;border:2px solid #1A1100;border-radius:10px;padding:9px 13px;font-size:13px;font-family:inherit;outline:none;resize:vertical;transition:border-color 0.12s;box-sizing:border-box;"
              @focus="e=>e.target.style.borderColor='#7C3AED'"
              @blur="e=>e.target.style.borderColor='#1A1100'"></textarea>
            <div style="font-size:11px;color:#9A8F7A;margin-top:4px;">AI가 내용을 검토하고 명확성을 자동으로 평가합니다.</div>
          </div>
          <div style="display:flex;gap:8px;justify-content:flex-end;">
            <button type="button" @click="resetForm" class="btn-secondary" :disabled="submitting">취소</button>
            <button type="submit" :disabled="submitting"
              style="background:#7C3AED;color:#fff;border:2px solid #1A1100;border-radius:10px;padding:8px 20px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:2px 2px 0 #1A1100;display:inline-flex;align-items:center;gap:6px;transition:all 0.1s;"
              @mouseenter="e=>{if(!submitting){e.currentTarget.style.transform='translate(-1px,-1px)';e.currentTarget.style.boxShadow='3px 3px 0 #1A1100';}}"
              @mouseleave="e=>{e.currentTarget.style.transform='none';e.currentTarget.style.boxShadow='2px 2px 0 #1A1100';}">
              <svg v-if="submitting" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation:spin 1s linear infinite;"><path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" opacity=".25"/><path d="M12 3a9 9 0 0 1 9 9"/></svg>
              <svg v-else width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
              {{ submitting ? 'AI 검토 중...' : '등록하기' }}
            </button>
          </div>
        </form>
      </div>
    </Transition>

    <!-- 이슈 목록 -->
    <div v-if="issues.length === 0" class="card" style="padding:48px;text-align:center;color:#9A8F7A;">
      <div style="font-size:32px;margin-bottom:12px;">📋</div>
      <div style="font-size:14px;font-weight:600;">등록된 이슈/요구사항이 없습니다</div>
      <div style="font-size:12px;margin-top:4px;">위의 버튼을 눌러 새 이슈를 등록해 보세요</div>
    </div>

    <div v-else style="display:flex;flex-direction:column;gap:12px;">
      <div v-for="issue in issues" :key="issue.id" class="card" style="padding:20px;">
        <!-- 이슈 헤더 -->
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:10px;">
          <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:0;">
            <div :style="{ background: avatarColor(issue.user_id), width:'28px', height:'28px', borderRadius:'50%', border:'2px solid #1A1100', display:'flex', alignItems:'center', justifyContent:'center', color:'#fff', fontSize:'11px', fontWeight:'700', flexShrink:0 }">
              {{ issue.user_name?.charAt(0) ?? '?' }}
            </div>
            <div style="flex:1;min-width:0;">
              <div style="font-size:14px;font-weight:700;color:#1A1100;margin-bottom:2px;word-break:break-word;">{{ issue.title }}</div>
              <div style="font-size:11px;color:#9A8F7A;">{{ issue.user_name }} · {{ issue.created_at }}</div>
            </div>
          </div>
          <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
            <!-- 상태 배지 -->
            <span :style="statusStyle(issue.status)" style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:99px;border:1.5px solid;white-space:nowrap;">
              {{ statusLabel(issue.status) }}
            </span>
            <!-- 관리자: 상태 변경 -->
            <select v-if="isAdmin" v-model="issue.status" @change="changeStatus(issue)"
              style="border:2px solid #1A1100;border-radius:8px;padding:3px 6px;font-size:11px;font-weight:700;font-family:inherit;background:#fff;cursor:pointer;outline:none;">
              <option value="pending">대기</option>
              <option value="processing">처리 중</option>
              <option value="resolved">해결됨</option>
              <option value="unclear">재작성 요청</option>
            </select>
            <!-- 삭제 -->
            <button v-if="isAdmin || issue.user_id === currentUserId" @click="doDelete(issue)"
              style="background:none;border:none;cursor:pointer;color:#D0C9BC;padding:5px;border-radius:6px;display:flex;align-items:center;transition:color 0.1s;"
              @mouseenter="e=>e.currentTarget.style.color='#DC2626'"
              @mouseleave="e=>e.currentTarget.style.color='#D0C9BC'"
              title="삭제">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- 내용 -->
        <div style="font-size:13px;color:#4A3F2A;line-height:1.7;white-space:pre-wrap;margin-bottom:12px;padding-left:38px;word-break:break-word;">{{ issue.content }}</div>

        <!-- AI 응답 -->
        <div v-if="issue.claude_response" :style="aiBoxStyle(issue.status)" style="border-radius:10px;padding:12px 14px;font-size:12px;line-height:1.7;display:flex;gap:10px;align-items:flex-start;border:1.5px solid;">
          <div style="font-size:16px;flex-shrink:0;margin-top:1px;">🤖</div>
          <div>
            <div style="font-weight:700;margin-bottom:3px;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;">AI 검토 결과</div>
            <div style="white-space:pre-wrap;word-break:break-word;">{{ issue.claude_response }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- 삭제 확인 모달 -->
    <div v-if="deleteTarget"
      style="position:fixed;inset:0;background:rgba(26,17,0,0.45);display:flex;align-items:center;justify-content:center;z-index:100;backdrop-filter:blur(3px);padding:16px;"
      @click.self="deleteTarget=null">
      <div class="card" style="width:360px;max-width:100%;padding:28px;text-align:center;">
        <div style="width:44px;height:44px;background:#FEE2E2;border:2px solid #DC2626;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
          </svg>
        </div>
        <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:15px;font-weight:800;margin-bottom:8px;">이슈를 삭제할까요?</div>
        <div style="font-size:13px;color:#9A8F7A;margin-bottom:20px;">이 작업은 되돌릴 수 없습니다.</div>
        <div style="display:flex;gap:8px;justify-content:center;">
          <button @click="deleteTarget=null" class="btn-secondary">취소</button>
          <button @click="confirmDelete"
            style="background:#DC2626;color:#fff;border:2px solid #DC2626;border-radius:10px;padding:8px 20px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;">
            삭제하기
          </button>
        </div>
      </div>
    </div>

  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  issues: { type: Array, default: () => [] },
})

const page          = usePage()
const isAdmin       = computed(() => page.props.auth?.user?.role === 'admin')
const currentUserId = computed(() => page.props.auth?.user?.id)

const showForm  = ref(false)
const newTitle  = ref('')
const newContent = ref('')
const submitting = ref(false)
const deleteTarget = ref(null)

const resetForm = () => {
  showForm.value  = false
  newTitle.value  = ''
  newContent.value = ''
}

const submitIssue = () => {
  if (submitting.value) return
  submitting.value = true
  router.post('/issues', { title: newTitle.value, content: newContent.value }, {
    onSuccess: () => { resetForm() },
    onFinish: () => { submitting.value = false },
  })
}

const doDelete = (issue) => { deleteTarget.value = issue }
const confirmDelete = () => {
  if (!deleteTarget.value) return
  router.delete(`/issues/${deleteTarget.value.id}`, {
    onSuccess: () => { deleteTarget.value = null },
  })
}

const changeStatus = (issue) => {
  router.post(`/issues/${issue.id}/status`, { status: issue.status }, { preserveState: true })
}

const AVATAR_COLORS = ['#FD4401','#16a34a','#2563eb','#9333ea','#d97706','#0891b2','#dc2626','#65a30d']
const avatarColor = (id) => AVATAR_COLORS[(id ?? 0) % AVATAR_COLORS.length]

const statusLabel = (s) => ({
  pending:    '⏳ 대기',
  processing: '🔄 처리 중',
  resolved:   '✅ 해결됨',
  unclear:    '⚠️ 재작성 요청',
})[s] ?? s

const statusStyle = (s) => ({
  pending:    { background:'#F3F4F6', color:'#6B7280', borderColor:'#D1D5DB' },
  processing: { background:'#DBEAFE', color:'#1D4ED8', borderColor:'#93C5FD' },
  resolved:   { background:'#DCFCE7', color:'#15803D', borderColor:'#86EFAC' },
  unclear:    { background:'#FEF3C7', color:'#B45309', borderColor:'#FCD34D' },
})[s] ?? { background:'#F3F4F6', color:'#6B7280', borderColor:'#D1D5DB' }

const aiBoxStyle = (s) => ({
  pending:    { background:'#F9FAFB', color:'#6B7280', borderColor:'#E5E7EB' },
  processing: { background:'#EFF6FF', color:'#1E40AF', borderColor:'#BFDBFE' },
  resolved:   { background:'#F0FDF4', color:'#166534', borderColor:'#BBF7D0' },
  unclear:    { background:'#FFFBEB', color:'#92400E', borderColor:'#FDE68A' },
})[s] ?? { background:'#F9FAFB', color:'#6B7280', borderColor:'#E5E7EB' }
</script>

<style scoped>
.form-slide-enter-active, .form-slide-leave-active { transition: all 0.2s ease; overflow: hidden; }
.form-slide-enter-from, .form-slide-leave-to { opacity: 0; transform: translateY(-8px); }

@keyframes spin { to { transform: rotate(360deg); } }
</style>
