<template>
  <!-- 보고서 항목(항목명 → 내용 → 세부항목) 3단 목록 — 보고서 열람 모바일 섹션 띠에서 쓴다 -->
  <div v-if="items?.length" style="display:flex;flex-direction:column;gap:10px;">
    <div v-for="(item, i) in items" :key="i">
      <div style="display:flex;gap:7px;align-items:flex-start;">
        <span style="font-size:11px;color:#9A8F7A;font-family:'Space Grotesk',sans-serif;font-weight:700;flex-shrink:0;margin-top:2px;min-width:14px;">{{ i + 1 }}.</span>
        <span style="font-size:13px;line-height:1.65;color:#1A1100;font-weight:700;overflow-wrap:anywhere;">{{ item.title || item.content }}</span>
      </div>
      <div v-if="item.sub_items?.length" style="margin-top:4px;display:flex;flex-direction:column;gap:3px;">
        <template v-for="(sub, si) in item.sub_items" :key="si">
          <div style="display:flex;gap:6px;align-items:flex-start;">
            <span style="color:#9A8F7A;flex-shrink:0;font-size:11px;">-</span>
            <span style="color:#1A1100;font-size:11px;line-height:1.5;white-space:pre-wrap;overflow-wrap:anywhere;"
              v-html="autoLink(typeof sub === 'string' ? sub : sub.content)"></span>
          </div>
          <template v-if="typeof sub !== 'string' && sub.details?.length">
            <div v-for="(detail, di) in sub.details" :key="di" style="display:flex;gap:6px;align-items:flex-start;margin-left:16px;">
              <span style="color:#9A8F7A;flex-shrink:0;font-size:11px;">└</span>
              <span style="color:#1A1100;font-size:11px;line-height:1.5;white-space:pre-wrap;overflow-wrap:anywhere;" v-html="autoLink(detail)"></span>
            </div>
          </template>
        </template>
      </div>
    </div>
  </div>
  <span v-else style="color:#D0C9BC;font-size:12px;">-</span>
</template>

<script setup>
import { autoLink } from '@/utils/autoLink.js'

defineProps({
  items: { type: Array, default: () => [] },
})
</script>
