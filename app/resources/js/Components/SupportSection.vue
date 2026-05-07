<template>
  <div ref="rootRef" class="card" style="padding:0;overflow:hidden;">
    <div style="padding:10px 14px 10px 18px;border-bottom:2px solid #1A1100;background:#F5EDDB;font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:13px;font-weight:700;display:flex;align-items:center;justify-content:space-between;gap:8px;">
      <span>{{ title }}</span>
      <div style="display:flex;gap:3px;align-items:center;">
        <!-- 복사 버튼 -->
        <button type="button" @click="handleCopy"
          :title="copied ? '복사됨!' : '항목 전체 복사'"
          :style="{
            background: copied ? '#FDCB40' : 'transparent',
            border: copied ? '1.5px solid #1A1100' : '1.5px solid transparent',
            borderRadius:'7px', padding:'3px 8px', cursor:'pointer',
            display:'inline-flex', alignItems:'center', gap:'4px',
            fontSize:'11px', fontWeight:'700', color: copied ? '#1A1100' : '#9A8F7A',
            fontFamily:'inherit', transition:'all 0.15s',
          }"
          @mouseenter="e=>{ if(!copied){ e.currentTarget.style.background='rgba(26,17,0,0.07)'; e.currentTarget.style.color='#1A1100'; e.currentTarget.style.borderColor='#C5BAA8'; } }"
          @mouseleave="e=>{ if(!copied){ e.currentTarget.style.background='transparent'; e.currentTarget.style.color='#9A8F7A'; e.currentTarget.style.borderColor='transparent'; } }">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
          </svg>
          <span v-if="copied">복사됨!</span>
          <span v-else>복사</span>
        </button>

        <!-- 구분선 -->
        <span style="width:1px;height:14px;background:#C5BAA8;border-radius:1px;margin:0 2px;"></span>

        <!-- 붙여넣기 버튼 -->
        <button type="button" @click="handlePaste"
          title="복사한 항목 붙여넣기"
          :disabled="!canPaste"
          :style="{
            border: '1.5px solid transparent',
            borderRadius:'7px', padding:'3px 8px',
            cursor: canPaste ? 'pointer' : 'not-allowed',
            display:'inline-flex', alignItems:'center', gap:'4px',
            fontSize:'11px', fontWeight:'700',
            color: canPaste ? '#9A8F7A' : '#D0C9BC',
            background: 'transparent',
            fontFamily:'inherit', transition:'all 0.15s',
            opacity: canPaste ? 1 : 0.45,
          }"
          @mouseenter="e=>{ if(canPaste){ e.currentTarget.style.background='rgba(26,17,0,0.07)'; e.currentTarget.style.color='#1A1100'; e.currentTarget.style.borderColor='#C5BAA8'; } }"
          @mouseleave="e=>{ if(canPaste){ e.currentTarget.style.background='transparent'; e.currentTarget.style.color='#9A8F7A'; e.currentTarget.style.borderColor='transparent'; } }">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
          </svg>
          붙여넣기
        </button>

        <!-- 구분선 -->
        <span style="width:1px;height:14px;background:#C5BAA8;border-radius:1px;margin:0 2px;"></span>

        <!-- 취소(비우기) 버튼 -->
        <button type="button" @click="handleCancel"
          title="항목 전체 비우기"
          :disabled="!modelValue.length"
          :style="{
            border: '1.5px solid transparent',
            borderRadius:'7px', padding:'3px 8px',
            cursor: modelValue.length ? 'pointer' : 'not-allowed',
            display:'inline-flex', alignItems:'center', gap:'4px',
            fontSize:'11px', fontWeight:'700',
            color: modelValue.length ? '#9A8F7A' : '#D0C9BC',
            background: 'transparent',
            fontFamily:'inherit', transition:'all 0.15s',
            opacity: modelValue.length ? 1 : 0.45,
          }"
          @mouseenter="e=>{ if(modelValue.length){ e.currentTarget.style.background='rgba(220,38,38,0.07)'; e.currentTarget.style.color='#DC2626'; e.currentTarget.style.borderColor='#FECACA'; } }"
          @mouseleave="e=>{ if(modelValue.length){ e.currentTarget.style.background='transparent'; e.currentTarget.style.color='#9A8F7A'; e.currentTarget.style.borderColor='transparent'; } }">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="3 6 5 6 21 6"/>
            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
            <path d="M10 11v6M14 11v6"/>
            <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
          </svg>
          취소
        </button>
      </div>
    </div>

    <div style="padding:12px 16px;display:flex;flex-direction:column;gap:8px;">

      <!-- 항목 목록 -->
      <div v-for="(item, idx) in modelValue" :key="idx"
        style="border:1.5px solid #E8E0D0;border-radius:10px;padding:10px 12px 8px;background:#FDFAF5;">

        <!-- 번호 + 항목명 입력 -->
        <div style="display:flex;gap:7px;align-items:center;margin-bottom:8px;">
          <span style="font-size:11px;color:#9A8F7A;font-family:'Space Grotesk',sans-serif;font-weight:700;flex-shrink:0;min-width:18px;text-align:right;">
            {{ idx + 1 }}.
          </span>

          <!-- input + 드롭다운 버튼 묶음 -->
          <div :ref="el => { if (el) containerRefs[idx] = el }"
            style="flex:1;display:flex;gap:4px;align-items:center;">
            <textarea
              :ref="el => { if (el) titleRefs[idx] = el }"
              v-model="item.title"
              @input="e => { onInputChange(idx); e.target.style.height='auto'; e.target.style.height=e.target.scrollHeight+'px' }"
              @focus="onTitleFocus(idx)"
              @blur="onTitleBlur"
              @keydown.enter.prevent="pickFirst(idx)"
              @keydown.escape="closeAll"
              @keydown.arrow-down.prevent="hoverIdx = Math.min(hoverIdx + 1, activeSuggestions.length - 1)"
              @keydown.arrow-up.prevent="hoverIdx = Math.max(hoverIdx - 1, 0)"
              rows="1"
              class="input-field"
              placeholder="항목명"
              style="flex:1;font-weight:700;font-size:13px;resize:none;overflow:hidden;min-height:36px;line-height:1.55;" />

            <!-- 드롭다운 토글 버튼 (▼) -->
            <button v-if="suggestions.length" type="button"
              @mousedown.prevent="toggleDropdown(idx)"
              title="저장된 항목 목록 보기"
              style="flex-shrink:0;background:#F0EBE0;border:1.5px solid #C5BAA8;border-radius:7px;width:28px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all 0.12s;"
              @mouseenter="e=>{e.currentTarget.style.background='#E8E0D0';e.currentTarget.style.borderColor='#9A8F7A';}"
              @mouseleave="e=>{e.currentTarget.style.background='#F0EBE0';e.currentTarget.style.borderColor='#C5BAA8';}">
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#6B5E4A" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                :style="{ transform: dropdownOpen && focusedTitleIdx === idx ? 'rotate(180deg)' : 'rotate(0deg)', transition:'transform 0.15s' }">
                <polyline points="6 9 12 15 18 9"/>
              </svg>
            </button>
          </div>

          <!-- 항목 삭제 버튼 -->
          <button type="button" @click="removeItem(idx)"
            style="background:none;border:none;cursor:pointer;color:#D0C9BC;padding:4px;border-radius:6px;flex-shrink:0;transition:color 0.1s;"
            @mouseenter="e=>e.currentTarget.style.color='#DC2626'"
            @mouseleave="e=>e.currentTarget.style.color='#D0C9BC'">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
          </button>
        </div>

        <!-- 세부 항목 (- bullet) -->
        <div style="margin-left:25px;display:flex;flex-direction:column;gap:4px;">
          <div v-for="(sub, sIdx) in (item.sub_items || [])" :key="sIdx"
            style="display:flex;gap:6px;align-items:flex-start;">
            <span style="color:#9A8F7A;font-size:12px;font-weight:700;flex-shrink:0;margin-top:7px;width:10px;">-</span>
            <textarea
              v-model="item.sub_items[sIdx]"
              @input="(e) => { autoResize(e.target); emitUpdate() }"
              class="input-field"
              rows="1"
              placeholder="세부 내용"
              style="flex:1;resize:none;overflow:hidden;min-height:32px;font-size:12px;line-height:1.5;padding-top:5px;padding-bottom:5px;color:#4A3F2A;" />
            <button type="button" @click="removeSubItem(idx, sIdx)"
              style="background:none;border:none;cursor:pointer;color:#D0C9BC;padding:4px;border-radius:6px;flex-shrink:0;transition:color 0.1s;margin-top:3px;"
              @mouseenter="e=>e.currentTarget.style.color='#DC2626'"
              @mouseleave="e=>e.currentTarget.style.color='#D0C9BC'">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
          </div>

          <!-- 세부항목 추가 버튼 -->
          <button type="button" @click="addSubItem(idx)"
            style="display:inline-flex;align-items:center;gap:4px;background:none;border:1.5px dashed #D0C9BC;border-radius:6px;padding:3px 9px;font-size:11px;color:#9A8F7A;cursor:pointer;font-family:inherit;font-weight:600;transition:all 0.1s;align-self:flex-start;margin-top:2px;"
            @mouseenter="e=>{e.currentTarget.style.borderColor='#FD4401';e.currentTarget.style.color='#FD4401';}"
            @mouseleave="e=>{e.currentTarget.style.borderColor='#D0C9BC';e.currentTarget.style.color='#9A8F7A';}">
            <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
            세부항목 추가
          </button>
        </div>
      </div>

      <!-- 항목 추가 버튼 -->
      <button type="button" @click="addItem"
        style="display:inline-flex;align-items:center;gap:5px;background:rgba(26,17,0,0.04);border:1.5px dashed #C5BAA8;border-radius:8px;padding:6px 14px;font-size:12px;color:#9A8F7A;cursor:pointer;font-family:inherit;font-weight:600;transition:all 0.1s;align-self:flex-start;"
        @mouseenter="e=>{e.currentTarget.style.borderColor='#FD4401';e.currentTarget.style.color='#FD4401';e.currentTarget.style.background='rgba(253,68,1,0.04)';}"
        @mouseleave="e=>{e.currentTarget.style.borderColor='#C5BAA8';e.currentTarget.style.color='#9A8F7A';e.currentTarget.style.background='rgba(26,17,0,0.04)';}">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
        항목 추가
      </button>

      <p v-if="error" style="font-size:12px;color:#FD4401;margin:0;">{{ error }}</p>
    </div>
  </div>

  <!-- ── 자동완성 드롭다운 (body에 텔레포트 → overflow 잘림 없음) ── -->
  <Teleport to="body">
    <div v-if="showDropdown && activeSuggestions.length"
      :style="{
        position: 'fixed',
        top: dropPos.top + 'px',
        left: dropPos.left + 'px',
        width: dropPos.width + 'px',
        background: '#fff',
        border: '2px solid #1A1100',
        borderRadius: '10px',
        boxShadow: '4px 4px 0 #1A1100',
        zIndex: 9999,
        maxHeight: '240px',
        overflowY: 'auto',
        fontFamily: '\'Space Grotesk\',\'Noto Sans KR\',sans-serif',
      }">
      <!-- 드롭다운 헤더 (전체 목록일 때) -->
      <div v-if="dropdownOpen"
        style="padding:6px 14px;font-size:10px;font-weight:800;color:#9A8F7A;letter-spacing:0.06em;text-transform:uppercase;border-bottom:1.5px solid #F0EBE0;background:#FDFAF5;">
        저장된 항목
      </div>
      <div v-for="(s, si) in activeSuggestions" :key="si"
        @mousedown.prevent="selectSuggestion(focusedTitleIdx, s)"
        :style="{
          padding: '9px 14px',
          fontSize: '12px',
          fontWeight: '600',
          color: '#1A1100',
          cursor: 'pointer',
          display: 'flex',
          alignItems: 'center',
          gap: '8px',
          background: hoverIdx === si ? '#FFF0A0' : 'transparent',
          transition: 'background 0.07s',
        }"
        @mouseenter="hoverIdx = si"
        @mouseleave="hoverIdx = -1">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#9A8F7A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
        </svg>
        <!-- 타이핑 연관어 하이라이트 -->
        <span v-if="!dropdownOpen && currentQuery">
          <template v-for="(part, pi) in highlight(s, currentQuery)" :key="pi">
            <strong v-if="part.match" style="color:#FD4401;font-weight:800;">{{ part.text }}</strong>
            <span v-else>{{ part.text }}</span>
          </template>
        </span>
        <span v-else>{{ s }}</span>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onBeforeUnmount, watch } from 'vue'

const props = defineProps({
  title:       { type: String,  default: '' },
  modelValue:  { type: Array,   default: () => [] },
  error:       { type: String,  default: '' },
  canPaste:    { type: Boolean, default: false },
  suggestions: { type: Array,   default: () => [] },
})
const emit = defineEmits(['update:modelValue', 'copy', 'paste', 'cancel'])

// ── 복사 피드백 ───────────────────────────────────────
const copied = ref(false)
let copiedTimer = null

const handleCopy = () => {
  emit('copy')
  copied.value = true
  if (copiedTimer) clearTimeout(copiedTimer)
  copiedTimer = setTimeout(() => { copied.value = false }, 2000)
}
const handlePaste = () => { if (props.canPaste) emit('paste') }
const handleCancel = () => {
  if (!props.modelValue.length) return
  emit('update:modelValue', [])
  emit('cancel')
}

// ── Ref 모음 ─────────────────────────────────────────
const rootRef       = ref(null)
const titleRefs     = ref({})
const containerRefs = ref({})

// 모든 textarea 높이 일괄 재계산 (초기 로드 시 기존 데이터 높이 맞춤)
// nextTick → rAF → setTimeout 3단계로 브라우저 레이아웃/폰트 로드 완료 후 높이 재계산 보장
const resizeAllTextareas = () => {
  const doResize = () => {
    if (!rootRef.value) return
    rootRef.value.querySelectorAll('textarea').forEach(el => {
      el.style.height = 'auto'
      el.style.height = el.scrollHeight + 'px'
    })
  }
  nextTick(() => {
    doResize()
    requestAnimationFrame(() => {
      doResize()
      setTimeout(doResize, 300)
    })
  })
}

onMounted(resizeAllTextareas)
// modelValue가 교체될 때도 재계산 (붙여넣기, 불러오기 등)
watch(() => props.modelValue, resizeAllTextareas, { deep: false })

// ── 드롭다운 위치 ─────────────────────────────────────
const dropPos = ref({ top: 0, left: 0, width: 240 })

const updateDropPos = (idx) => {
  const el = containerRefs.value[idx]
  if (!el) return
  const r = el.getBoundingClientRect()
  dropPos.value = {
    top:   r.bottom + 4,
    left:  r.left,
    width: Math.max(r.width, 200),
  }
}

// ── 자동완성 상태 ─────────────────────────────────────
const focusedTitleIdx = ref(-1)
const suggestVisible  = ref(false)   // 타이핑 연관어 표시
const dropdownOpen    = ref(false)   // ▼ 버튼으로 전체 목록 표시
const currentQuery    = ref('')
const hoverIdx        = ref(-1)

const showDropdown = computed(() =>
  focusedTitleIdx.value >= 0 && (suggestVisible.value || dropdownOpen.value)
)

// 연관어: 타이핑 시 필터, 드롭다운 버튼 시 전체
const activeSuggestions = computed(() => {
  if (!props.suggestions?.length) return []
  if (dropdownOpen.value) return props.suggestions        // 전체 목록
  const q = currentQuery.value.toLowerCase().trim()
  if (!q) return []                                       // 빈 쿼리면 숨김
  return props.suggestions.filter(s => s.toLowerCase().includes(q)).slice(0, 10)
})

// 타이핑 연관어 하이라이트 파싱
const highlight = (text, query) => {
  if (!query) return [{ text, match: false }]
  const parts = []
  const lower = text.toLowerCase()
  const q     = query.toLowerCase()
  let cursor  = 0
  let idx
  while ((idx = lower.indexOf(q, cursor)) !== -1) {
    if (idx > cursor) parts.push({ text: text.slice(cursor, idx), match: false })
    parts.push({ text: text.slice(idx, idx + q.length), match: true })
    cursor = idx + q.length
  }
  if (cursor < text.length) parts.push({ text: text.slice(cursor), match: false })
  return parts
}

const onInputChange = (idx) => {
  emitUpdate()
  currentQuery.value   = props.modelValue[idx]?.title ?? ''
  dropdownOpen.value   = false   // 타이핑 시 전체목록 닫고 연관어로 전환
  suggestVisible.value = true
  hoverIdx.value       = -1
  updateDropPos(idx)
}

const onTitleFocus = (idx) => {
  focusedTitleIdx.value = idx
  currentQuery.value    = props.modelValue[idx]?.title ?? ''
  hoverIdx.value        = -1
  // 포커스만으로는 드롭다운 열지 않음 (타이핑하거나 ▼ 버튼 클릭 시 열림)
}

const onTitleBlur = () => {
  setTimeout(() => {
    suggestVisible.value  = false
    dropdownOpen.value    = false
    focusedTitleIdx.value = -1
    hoverIdx.value        = -1
  }, 160)
}

const toggleDropdown = (idx) => {
  if (focusedTitleIdx.value !== idx) {
    focusedTitleIdx.value = idx
  }
  dropdownOpen.value   = !dropdownOpen.value
  suggestVisible.value = false
  hoverIdx.value       = -1
  if (dropdownOpen.value) {
    updateDropPos(idx)
    nextTick(() => titleRefs.value[idx]?.focus())
  }
}

const closeAll = () => {
  suggestVisible.value  = false
  dropdownOpen.value    = false
  focusedTitleIdx.value = -1
  hoverIdx.value        = -1
}

const pickFirst = (idx) => {
  if (hoverIdx.value >= 0 && activeSuggestions.value[hoverIdx.value]) {
    selectSuggestion(idx, activeSuggestions.value[hoverIdx.value])
  }
}

const selectSuggestion = (idx, text) => {
  if (idx < 0) return
  const arr = props.modelValue.map((item, i) =>
    i !== idx ? item : { ...item, title: text }
  )
  emit('update:modelValue', arr)
  currentQuery.value    = text
  suggestVisible.value  = false
  dropdownOpen.value    = false
  focusedTitleIdx.value = -1
  hoverIdx.value        = -1
}

// 바깥 클릭 시 닫기
const onDocClick = (e) => {
  const inContainer = Object.values(containerRefs.value).some(el => el?.contains(e.target))
  if (!inContainer) closeAll()
}
onMounted(()  => document.addEventListener('mousedown', onDocClick))
onBeforeUnmount(() => document.removeEventListener('mousedown', onDocClick))

// ── 공통 유틸 ─────────────────────────────────────────
const autoResize = (el) => {
  if (!el) return
  el.style.height = 'auto'
  el.style.height = el.scrollHeight + 'px'
}

const emitUpdate = () => emit('update:modelValue', [...props.modelValue])

const addItem = async () => {
  emit('update:modelValue', [...props.modelValue, { title: '', sub_items: [] }])
  await nextTick()
  const el = titleRefs.value[props.modelValue.length - 1]
  if (el) el.focus()
}

const removeItem = (idx) => {
  const arr = [...props.modelValue]
  arr.splice(idx, 1)
  emit('update:modelValue', arr)
}

const addSubItem = (idx) => {
  emit('update:modelValue', props.modelValue.map((item, i) =>
    i !== idx ? item : { ...item, sub_items: [...(item.sub_items || []), ''] }
  ))
}

const removeSubItem = (idx, sIdx) => {
  emit('update:modelValue', props.modelValue.map((item, i) => {
    if (i !== idx) return item
    const subs = [...(item.sub_items || [])]
    subs.splice(sIdx, 1)
    return { ...item, sub_items: subs }
  }))
}
</script>
