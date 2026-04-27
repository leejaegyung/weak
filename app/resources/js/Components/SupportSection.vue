<template>
  <div class="card" style="padding:0;overflow:hidden;">
    <div style="padding:12px 18px;border-bottom:2px solid #1A1100;background:#F5EDDB;font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:13px;font-weight:700;">
      {{ title }}
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
          <input
            :ref="el => { if (el) titleRefs[idx] = el }"
            v-model="item.title"
            @input="emitUpdate"
            @keydown.enter.prevent
            type="text"
            class="input-field"
            placeholder="항목명"
            style="flex:1;font-weight:700;font-size:13px;" />
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
import { ref, nextTick } from 'vue'

const props = defineProps({
  title:      { type: String, default: '' },
  modelValue: { type: Array,  default: () => [] },
  error:      { type: String, default: '' },
})
const emit = defineEmits(['update:modelValue'])

const titleRefs = ref({})

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
