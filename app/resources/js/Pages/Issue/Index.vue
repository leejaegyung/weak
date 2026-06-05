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
      <div style="display:flex;gap:8px;align-items:center;">
        <!-- MD 내보내기 (관리자만) -->
        <a v-if="isAdmin" href="/issues/export/md"
          class="btn-secondary btn-sm"
          style="display:inline-flex;align-items:center;gap:5px;text-decoration:none;"
          title="이슈/요구사항을 LLM 하네스용 MD 파일로 내보내기">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
          </svg>
          요구사항.md
        </a>
        <button @click="openWriteModal"
          style="background:#7C3AED;color:#fff;border:2px solid #1A1100;border-radius:10px;padding:9px 18px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:2px 2px 0 #1A1100;display:inline-flex;align-items:center;gap:6px;transition:all 0.1s;"
          @mouseenter="e=>{e.currentTarget.style.transform='translate(-1px,-1px)';e.currentTarget.style.boxShadow='3px 3px 0 #1A1100';}"
          @mouseleave="e=>{e.currentTarget.style.transform='none';e.currentTarget.style.boxShadow='2px 2px 0 #1A1100';}">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
          글쓰기
        </button>
      </div>
    </div>

    <!-- 게시판 테이블 -->
    <div class="card" style="overflow:hidden;padding:0;">
      <!-- 테이블 헤더 -->
      <div style="display:grid;grid-template-columns:60px 1fr 100px 120px 90px 44px;padding:10px 20px;border-bottom:2px solid #1A1100;background:#F5EDDB;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#9A8F7A;font-family:'Space Grotesk','Noto Sans KR',sans-serif;">
        <span style="text-align:center;">번호</span>
        <span>제목</span>
        <span>작성자</span>
        <span>상태</span>
        <span>등록일</span>
        <span></span>
      </div>

      <!-- 빈 목록 -->
      <div v-if="issues.length === 0" style="padding:64px 20px;text-align:center;color:#9A8F7A;">
        <div style="font-size:28px;margin-bottom:10px;">📋</div>
        <div style="font-size:13px;font-weight:600;margin-bottom:4px;">등록된 이슈/요구사항이 없습니다</div>
        <div style="font-size:12px;">위의 글쓰기 버튼을 눌러 새 이슈를 등록해 보세요</div>
      </div>

      <!-- 게시글 행 -->
      <template v-else>
        <div v-for="(issue, i) in issues" :key="issue.id">
          <!-- 목록 행 -->
          <div
            style="display:grid;grid-template-columns:60px 1fr 100px 120px 90px 44px;padding:14px 20px;align-items:center;cursor:pointer;transition:background 0.12s;"
            :style="{ borderBottom: expandedId !== issue.id && i < issues.length-1 ? '1.5px solid #F5EDDB' : 'none' }"
            @click="toggleExpand(issue.id)"
            @mouseenter="e=>e.currentTarget.style.background='#FFF8EE'"
            @mouseleave="e=>e.currentTarget.style.background='transparent'">

            <!-- 번호 -->
            <div style="text-align:center;font-family:'Space Grotesk',sans-serif;font-size:13px;font-weight:700;color:#9A8F7A;">
              {{ issues.length - i }}
            </div>

            <!-- 제목 + 내용 미리보기 -->
            <div style="min-width:0;">
              <div style="display:flex;align-items:center;gap:8px;margin-bottom:2px;">
                <span style="font-size:13px;font-weight:700;color:#1A1100;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ issue.title }}</span>
                <span v-if="issue.status === 'unclear'"
                  style="font-size:10px;font-weight:700;background:#FEF3C7;color:#B45309;border:1px solid #FCD34D;border-radius:4px;padding:1px 6px;flex-shrink:0;">재작성 필요</span>
              </div>
              <div style="font-size:11px;color:#9A8F7A;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ issue.content }}</div>
            </div>

            <!-- 작성자 -->
            <div style="display:flex;align-items:center;gap:7px;">
              <div :style="{ background: avatarColor(issue.user_id), width:'22px', height:'22px', borderRadius:'50%', border:'1.5px solid #1A1100', display:'flex', alignItems:'center', justifyContent:'center', color:'#fff', fontSize:'10px', fontWeight:'700', flexShrink:0 }">
                {{ issue.user_name?.charAt(0) ?? '?' }}
              </div>
              <span style="font-size:12px;font-weight:600;color:#4A3F2A;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ issue.user_name }}</span>
            </div>

            <!-- 상태 -->
            <div>
              <span :style="statusStyle(issue.status)" style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:99px;border:1.5px solid;white-space:nowrap;display:inline-block;">
                {{ statusLabel(issue.status) }}
              </span>
            </div>

            <!-- 날짜 -->
            <span style="font-size:12px;color:#9A8F7A;">{{ issue.created_at.substring(0, 10) }}</span>

            <!-- 펼치기 아이콘 -->
            <div style="display:flex;justify-content:center;">
              <svg :style="{ transform: expandedId === issue.id ? 'rotate(180deg)' : 'none', transition:'transform 0.2s', color:'#9A8F7A' }"
                width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"/>
              </svg>
            </div>
          </div>

          <!-- 펼쳐진 상세 내용 -->
          <Transition name="expand">
            <div v-if="expandedId === issue.id"
              style="background:#FAFAF8;border-top:1.5px solid #E8E0D0;border-bottom:2px solid #1A1100;padding:20px 24px;">

              <!-- 본문 -->
              <div style="margin-bottom:14px;">
                <div style="font-size:11px;font-weight:700;color:#9A8F7A;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;">내용</div>
                <div style="font-size:13px;color:#1A1100;line-height:1.75;white-space:pre-wrap;word-break:break-word;background:#fff;border:1.5px solid #E8E0D0;border-radius:10px;padding:14px 16px;">{{ issue.content }}</div>
              </div>

              <!-- AI 검토 결과 -->
              <div v-if="issue.claude_response" :style="aiBoxStyle(issue.status)" style="border-radius:10px;padding:14px 16px;margin-bottom:14px;border:1.5px solid;display:flex;gap:12px;align-items:flex-start;">
                <span style="font-size:20px;flex-shrink:0;">🤖</span>
                <div>
                  <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:5px;opacity:0.7;">AI 검토 결과</div>
                  <div style="font-size:13px;line-height:1.7;white-space:pre-wrap;word-break:break-word;">{{ issue.claude_response }}</div>
                </div>
              </div>

              <!-- 관리자 액션 -->
              <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
                <div v-if="isAdmin" style="display:flex;align-items:center;gap:8px;">
                  <span style="font-size:12px;font-weight:700;color:#4A3F2A;">상태 변경:</span>
                  <select v-model="issue.status" @change="changeStatus(issue)"
                    style="border:2px solid #1A1100;border-radius:8px;padding:5px 10px;font-size:12px;font-weight:700;font-family:inherit;background:#fff;cursor:pointer;outline:none;">
                    <option value="pending">⏳ 대기</option>
                    <option value="processing">🔄 처리 중</option>
                    <option value="resolved">✅ 해결됨</option>
                    <option value="unclear">⚠️ 재작성 요청</option>
                  </select>
                </div>
                <div v-else style="flex:1;"></div>
                <button v-if="isAdmin || issue.user_id === currentUserId" @click.stop="doDelete(issue)"
                  style="background:#FEE2E2;color:#DC2626;border:2px solid #DC2626;border-radius:9px;padding:6px 14px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:5px;transition:all 0.1s;"
                  @mouseenter="e=>{e.currentTarget.style.background='#DC2626';e.currentTarget.style.color='#fff';}"
                  @mouseleave="e=>{e.currentTarget.style.background='#FEE2E2';e.currentTarget.style.color='#DC2626';}">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                  </svg>
                  삭제
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </template>
    </div>

    <!-- 글쓰기 모달 -->
    <div v-if="showWriteModal"
      style="position:fixed;inset:0;background:rgba(26,17,0,0.45);display:flex;align-items:center;justify-content:center;z-index:100;backdrop-filter:blur(3px);padding:16px;"
      @click.self="closeWriteModal">
      <div class="card" style="width:560px;max-width:100%;max-height:90vh;padding:0;overflow:hidden;display:flex;flex-direction:column;">
        <!-- 모달 헤더 -->
        <div style="padding:18px 22px;border-bottom:2px solid #1A1100;background:#F5EDDB;display:flex;align-items:center;gap:12px;flex-shrink:0;">
          <div style="width:38px;height:38px;background:#EDE9FE;border:2px solid #1A1100;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </div>
          <div style="flex:1;">
            <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:15px;font-weight:800;">이슈/요구사항 등록</div>
            <div style="font-size:11px;color:#9A8F7A;margin-top:1px;">AI가 내용을 검토하고 명확성을 자동 평가합니다</div>
          </div>
          <button @click="closeWriteModal" style="background:none;border:none;cursor:pointer;color:#9A8F7A;padding:4px;display:flex;align-items:center;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
          </button>
        </div>

        <!-- 폼 -->
        <div style="flex:1;overflow-y:auto;padding:22px;">
          <form @submit.prevent="submitIssue">
            <div style="margin-bottom:14px;">
              <label style="display:block;font-size:12px;font-weight:700;color:#4A3F2A;margin-bottom:6px;">제목 <span style="color:#DC2626;">*</span></label>
              <input v-model="newTitle" required maxlength="200"
                placeholder="이슈 또는 요구사항 제목을 입력하세요"
                style="width:100%;background:#fff;border:2px solid #1A1100;border-radius:10px;padding:10px 14px;font-size:13px;font-family:inherit;outline:none;transition:border-color 0.12s;box-sizing:border-box;"
                @focus="e=>e.target.style.borderColor='#7C3AED'"
                @blur="e=>e.target.style.borderColor='#1A1100'" />
            </div>
            <div style="margin-bottom:18px;">
              <label style="display:block;font-size:12px;font-weight:700;color:#4A3F2A;margin-bottom:6px;">상세 내용 <span style="color:#DC2626;">*</span></label>
              <textarea v-model="newContent" required maxlength="2000" rows="6"
                placeholder="요청 사항 또는 이슈 상황을 구체적으로 설명해 주세요.&#10;담당자가 처리할 수 있도록 충분한 정보(발생 시점, 재현 방법, 기대 동작 등)를 포함해 주세요."
                style="width:100%;background:#fff;border:2px solid #1A1100;border-radius:10px;padding:10px 14px;font-size:13px;font-family:inherit;outline:none;resize:vertical;transition:border-color 0.12s;box-sizing:border-box;"
                @focus="e=>e.target.style.borderColor='#7C3AED'"
                @blur="e=>e.target.style.borderColor='#1A1100'"></textarea>
              <div style="font-size:11px;color:#9A8F7A;margin-top:4px;">{{ newContent.length }} / 2000자</div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
              <button type="button" @click="closeWriteModal" class="btn-secondary" :disabled="submitting">취소</button>
              <button type="submit" :disabled="submitting || !newTitle.trim() || !newContent.trim()"
                style="background:#7C3AED;color:#fff;border:2px solid #1A1100;border-radius:10px;padding:9px 22px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:2px 2px 0 #1A1100;display:inline-flex;align-items:center;gap:7px;transition:all 0.1s;"
                :style="{ opacity: submitting || !newTitle.trim() || !newContent.trim() ? 0.55 : 1 }">
                <svg v-if="submitting" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation:spin 1s linear infinite;"><path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" opacity=".25"/><path d="M12 3a9 9 0 0 1 9 9"/></svg>
                <svg v-else width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                {{ submitting ? 'AI 검토 중...' : '등록하기' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- 삭제 확인 모달 -->
    <div v-if="deleteTarget"
      style="position:fixed;inset:0;background:rgba(26,17,0,0.45);display:flex;align-items:center;justify-content:center;z-index:110;backdrop-filter:blur(3px);padding:16px;"
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
            style="background:#DC2626;color:#fff;border:2px solid #DC2626;border-radius:10px;padding:8px 22px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;">
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

// 게시판 아코디언
const expandedId = ref(null)
const toggleExpand = (id) => {
  expandedId.value = expandedId.value === id ? null : id
}

// 글쓰기 모달
const showWriteModal = ref(false)
const newTitle       = ref('')
const newContent     = ref('')
const submitting     = ref(false)

const openWriteModal  = () => { showWriteModal.value = true }
const closeWriteModal = () => {
  showWriteModal.value = false
  newTitle.value   = ''
  newContent.value = ''
}

const submitIssue = () => {
  if (submitting.value) return
  submitting.value = true
  router.post('/issues', { title: newTitle.value, content: newContent.value }, {
    onSuccess: () => { closeWriteModal() },
    onFinish:  () => { submitting.value = false },
  })
}

// 삭제
const deleteTarget = ref(null)
const doDelete = (issue) => { deleteTarget.value = issue }
const confirmDelete = () => {
  if (!deleteTarget.value) return
  router.delete(`/issues/${deleteTarget.value.id}`, {
    onSuccess: () => { deleteTarget.value = null; expandedId.value = null },
  })
}

// 상태 변경 (관리자)
const changeStatus = (issue) => {
  router.post(`/issues/${issue.id}/status`, { status: issue.status }, { preserveState: true })
}

// 유틸
const AVATAR_COLORS = ['#FD4401','#16a34a','#2563eb','#9333ea','#d97706','#0891b2','#dc2626','#65a30d']
const avatarColor = (id) => AVATAR_COLORS[(id ?? 0) % AVATAR_COLORS.length]

const statusLabel = (s) => ({
  pending:    '⏳ 대기',
  processing: '🔄 처리 중',
  resolved:   '✅ 해결됨',
  unclear:    '⚠️ 재작성',
})[s] ?? s

const statusStyle = (s) => ({
  pending:    { background:'#F3F4F6', color:'#6B7280', borderColor:'#D1D5DB' },
  processing: { background:'#DBEAFE', color:'#1D4ED8', borderColor:'#93C5FD' },
  resolved:   { background:'#DCFCE7', color:'#15803D', borderColor:'#86EFAC' },
  unclear:    { background:'#FEF3C7', color:'#B45309', borderColor:'#FCD34D' },
})[s] ?? { background:'#F3F4F6', color:'#6B7280', borderColor:'#D1D5DB' }

const aiBoxStyle = (s) => ({
  pending:    { background:'#F9FAFB', color:'#374151', borderColor:'#E5E7EB' },
  processing: { background:'#EFF6FF', color:'#1E40AF', borderColor:'#BFDBFE' },
  resolved:   { background:'#F0FDF4', color:'#166534', borderColor:'#BBF7D0' },
  unclear:    { background:'#FFFBEB', color:'#92400E', borderColor:'#FDE68A' },
})[s] ?? { background:'#F9FAFB', color:'#374151', borderColor:'#E5E7EB' }
</script>

<style scoped>
.expand-enter-active, .expand-leave-active { transition: all 0.2s ease; overflow: hidden; }
.expand-enter-from, .expand-leave-to { opacity: 0; transform: translateY(-6px); }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
