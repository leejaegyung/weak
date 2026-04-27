<template>
  <div class="card" style="padding:0;overflow:hidden;">
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
          <div style="flex:1;position:relative;">
            <input
              :ref="el => { if (el) titleRefs[idx] = el }"
              v-model="item.title"
              @input="emitUpdate"
              @focus="onTitleFocus(idx)"
              @blur="onTitleBlur"
              @keydown.enter.prevent
              @keydown.escape="suggestVisible = false"
              type="text"
              class="input-field"
              placeholder="항목명"
              style="width:100%;font-weight:700;font-size:13px;" />
            <!-- 자동완성 드롭다운 -->
            <div v-if="suggestVisible && focusedTitleIdx === idx && activeSuggestions.length"
              style="position:absolute;top:calc(100% + 4px);left:0;right:0;background:#fff;border:2px solid #1A1100;border-radius:10px;box-shadow:3px 3px 0 #1A1100;z-index:100;overflow:hidden;">
              <div v-for="(s, si) in activeSuggestions" :key="si"
                @mousedown.prevent="selectSuggestion(idx, s)"
                style="padding:8px 14px;font-size:12px;font-weight:600;color:#1A1100;cursor:pointer;display:flex;align-items:center;gap:7px;transition:background 0.08s;"
                @mouseenter="e=>e.currentTarget.style.background='#FFF0A0'"
                @mouseleave="e=>e.currentTarget.style.background='transparent'">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#9A8F7A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                </svg>
                {{ s }}
              </div>
            </div>
          </div>
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
</template>

<script setup>
import { ref, computed, nextTick } from 'vue'

const props = defineProps({
  title:       { type: String,  default: '' },
  modelValue:  { type: Array,   default: () => [] },
  error:       { type: String,  default: '' },
  canPaste:    { type: Boolean, default: false },
  suggestions: { type: Array,   default: () => [] },   // 자동완성 후보 목록 (mySites)
})
const emit = defineEmits(['update:modelValue', 'copy', 'paste', 'cancel'])

// 복사 피드백
const copied = ref(false)
let copiedTimer = null

const handleCopy = () => {
  emit('copy')
  copied.value = true
  if (copiedTimer) clearTimeout(copiedTimer)
  copiedTimer = setTimeout(() => { copied.value = false }, 2000)
}

const handlePaste = () => {
  if (!props.canPaste) return
  emit('paste')
}

const handleCancel = () => {
  if (!props.modelValue.length) return
  emit('update:modelValue', [])
  emit('cancel')
}

const titleRefs = ref({})

// ── 자동완성 ──────────────────────────────────────────
const focusedTitleIdx = ref(-1)   // 현재 포커스된 타이틀 input 인덱스
const suggestVisible  = ref(false)

const activeSuggestions = computed(() => {
  if (focusedTitleIdx.value < 0 || !props.suggestions?.length) return []
  const query = (props.modelValue[focusedTitleIdx.value]?.title ?? '').toLowerCase().trim()
  if (!query) return props.suggestions.slice(0, 8)
  return props.suggestions.filter(s => s.toLowerCase().includes(query)).slice(0, 8)
})

const onTitleFocus = (idx) => {
  focusedTitleIdx.value = idx
  suggestVisible.value  = true
}

const onTitleBlur = () => {
  // mousedown으로 선택 처리 후 blur → 약간 딜레이
  setTimeout(() => { suggestVisible.value = false; focusedTitleIdx.value = -1 }, 150)
}

const selectSuggestion = (idx, text) => {
  const arr = props.modelValue.map((item, i) => {
    if (i !== idx) return item
    return { ...item, title: text }
  })
  emit('update:modelValue', arr)
  suggestVisible.value  = false
  focusedTitleIdx.value = -1
}

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
  const arr = props.modelValue.map((item, i) => {
    if (i !== idx) return item
    return { ...item, sub_items: [...(item.sub_items || []), ''] }
  })
  emit('update:modelValue', arr)
}

const removeSubItem = (idx, sIdx) => {
  const arr = props.modelValue.map((item, i) => {
    if (i !== idx) return item
    const subs = [...(item.sub_items || [])]
    subs.splice(sIdx, 1)
    return { ...item, sub_items: subs }
  })
  emit('update:modelValue', arr)
}
</script>
