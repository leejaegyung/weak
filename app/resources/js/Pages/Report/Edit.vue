<template>
  <AppLayout page-title="보고서 수정">
    <!-- 헤더 -->
    <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
      <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
          <div style="background:#FDCB40;border:2px solid #1A1100;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
          </div>
          <div>
            <h1 style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:26px;font-weight:700;letter-spacing:-0.03em;">보고서 수정</h1>
            <p style="color:#9A8F7A;font-size:13px;">{{ report.week }}</p>
          </div>
        </div>
      </div>
      <div style="display:flex;gap:8px;">
        <Link :href="`/reports/${report.id}`" class="btn-secondary">취소</Link>
        <button type="button" @click="submit" :disabled="form.processing" class="btn-primary">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
          {{ form.processing ? '저장 중...' : '수정 저장' }}
        </button>
      </div>
    </div>

    <form @submit.prevent="submit" style="display:flex;flex-direction:column;gap:18px;">
      <!-- 보고 기간 -->
      <div class="card" style="background:#FFF0A0;">
        <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:14px;font-weight:700;margin-bottom:16px;">보고 기간</div>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
          <div>
            <label style="font-size:11px;color:#9A8F7A;font-weight:700;display:block;margin-bottom:6px;">이번 주 시작</label>
            <input type="date" v-model="form.curr_start" class="input-field" style="background:#fff;" />
          </div>
          <div>
            <label style="font-size:11px;color:#9A8F7A;font-weight:700;display:block;margin-bottom:6px;">이번 주 종료</label>
            <input type="date" v-model="form.curr_end" class="input-field" style="background:#fff;" />
          </div>
          <div>
            <label style="font-size:11px;color:#9A8F7A;font-weight:700;display:block;margin-bottom:6px;">다음 주 시작</label>
            <input type="date" v-model="form.next_start" class="input-field" style="background:#fff;" />
          </div>
          <div>
            <label style="font-size:11px;color:#9A8F7A;font-weight:700;display:block;margin-bottom:6px;">다음 주 종료</label>
            <input type="date" v-model="form.next_end" class="input-field" style="background:#fff;" />
          </div>
        </div>
      </div>

      <!-- 지원: 이번 주 | 다음 주 (2열) -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
        <SupportSection title="이번 주 결과 — 지원" v-model="form.jiWon_curr" />
        <SupportSection title="다음 주 계획 — 지원" v-model="form.jiWon_next" />
      </div>

      <!-- 내부작업 (전체 폭) -->
      <SupportSection title="내부작업" v-model="form.naebu" />

      <!-- Todo -->
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
          <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:14px;font-weight:700;">Todo 목록</div>
          <button type="button" @click="addTodo" class="btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:5px;">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>추가
          </button>
        </div>
        <div v-if="form.todo_items.length === 0" style="text-align:center;padding:16px 0;color:#9A8F7A;font-size:13px;">항목을 추가하세요</div>
        <div style="display:flex;flex-direction:column;gap:8px;">
          <div v-for="(t, idx) in form.todo_items" :key="idx" style="display:flex;gap:8px;align-items:flex-start;">
            <input type="checkbox" v-model="t.done" style="accent-color:#FD4401;width:16px;height:16px;cursor:pointer;flex-shrink:0;margin-top:9px;" />
            <textarea v-model="t.content" rows="1"
              @input="e => { e.target.style.height='auto'; e.target.style.height=e.target.scrollHeight+'px' }"
              class="input-field todo-textarea"
              :style="{ textDecoration: t.done ? 'line-through' : 'none', flex:1, resize:'none', overflow:'hidden', minHeight:'36px', lineHeight:'1.55' }"
              placeholder="할 일을 입력하세요"
              @keydown.enter.prevent />
            <button type="button" @click="removeTodo(idx)"
              style="background:none;border:none;cursor:pointer;color:#9A8F7A;padding:4px;border-radius:6px;flex-shrink:0;transition:color 0.1s;"
              @mouseenter="e=>e.currentTarget.style.color='#DC2626'"
              @mouseleave="e=>e.currentTarget.style.color='#9A8F7A'">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
          </div>
        </div>
      </div>

      <!-- 공유 (전체 폭) -->
      <SupportSection title="공유" v-model="form.gongyu" />

      <!-- 기타 (전체 폭) -->
      <SupportSection title="기타" v-model="form.gita" />

      <!-- 특이사항 / 요청사항 -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;padding-bottom:16px;">
        <div class="card">
          <label style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:14px;font-weight:700;display:block;margin-bottom:12px;">특이사항</label>
          <textarea v-model="form.notes" rows="4" class="input-field" style="resize:vertical;line-height:1.65;" placeholder="이번 주 특이사항을 입력해주세요"></textarea>
        </div>
        <div class="card">
          <label style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:14px;font-weight:700;display:block;margin-bottom:12px;">요청사항</label>
          <textarea v-model="form.requests" rows="4" class="input-field" style="resize:vertical;line-height:1.65;" placeholder="관리자에게 전달할 요청사항"></textarea>
        </div>
      </div>
    </form>
  </AppLayout>
</template>

<script setup>
import { onMounted, nextTick } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SupportSection from '@/Components/SupportSection.vue'

// 기존 데이터 로드 시 Todo textarea 높이 자동 맞춤
onMounted(() => {
  nextTick(() => {
    document.querySelectorAll('.todo-textarea').forEach(el => {
      el.style.height = 'auto'
      el.style.height = el.scrollHeight + 'px'
    })
  })
})

const props = defineProps({
  report: { type: Object, required: true },
})

const splitByCat = (arr, cat) =>
  (arr || []).filter(i => i.category === cat)
             .map(i => ({ title: i.title || i.content || '', sub_items: i.sub_items || [] }))

const form = useForm({
  curr_start: props.report.curr_start?.substring(0, 10) ?? '',
  curr_end:   props.report.curr_end?.substring(0, 10)   ?? '',
  next_start: props.report.next_start?.substring(0, 10) ?? '',
  next_end:   props.report.next_end?.substring(0, 10)   ?? '',
  jiWon_curr: splitByCat(props.report.curr_work, '지원'),
  jiWon_next: splitByCat(props.report.next_plan, '지원'),
  naebu:      splitByCat(props.report.curr_work, '내부작업'),
  gongyu:     splitByCat(props.report.curr_work, '공유'),
  gita:       splitByCat(props.report.curr_work, '기타'),
  todo_items: props.report.todo_items ?? [],
  notes:      props.report.notes      ?? '',
  requests:   props.report.requests   ?? '',
})

const addTodo    = () => form.todo_items.push({ content: '', done: false })
const removeTodo = (idx) => form.todo_items.splice(idx, 1)
const submit     = () => form.transform(data => ({
  curr_start: data.curr_start,
  curr_end:   data.curr_end,
  next_start: data.next_start,
  next_end:   data.next_end,
  curr_work: [
    ...data.jiWon_curr.map(i => ({ title: i.title, content: i.title, category: '지원',    sub_items: i.sub_items })),
    ...data.naebu.map(i      => ({ title: i.title, content: i.title, category: '내부작업', sub_items: i.sub_items })),
    ...data.gongyu.map(i     => ({ title: i.title, content: i.title, category: '공유',    sub_items: i.sub_items })),
    ...data.gita.map(i       => ({ title: i.title, content: i.title, category: '기타',    sub_items: i.sub_items })),
  ],
  next_plan:  data.jiWon_next.map(i => ({ title: i.title, content: i.title, category: '지원', sub_items: i.sub_items })),
  todo_items: data.todo_items,
  notes:      data.notes,
  requests:   data.requests,
})).put(`/reports/${props.report.id}`)
</script>
