<template>
  <div>
    <!-- 화면 전용: 상단 컨트롤 바 -->
    <div class="no-print" style="position:fixed;top:0;left:0;right:0;z-index:100;background:#1A1100;padding:10px 24px;display:flex;justify-content:space-between;align-items:center;">
      <div style="color:#FDCB40;font-family:'Space Grotesk',sans-serif;font-weight:800;font-size:15px;">🖨 인쇄 미리보기</div>
      <div style="display:flex;gap:8px;">
        <a :href="`/reports/${report.id}`"
          style="background:#F5EDDB;color:#1A1100;border:none;border-radius:8px;padding:7px 16px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
          ← 돌아가기
        </a>
        <button @click="doPrint"
          style="background:#FDCB40;color:#1A1100;border:none;border-radius:8px;padding:7px 18px;font-size:13px;font-weight:800;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:6px;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
          인쇄하기
        </button>
      </div>
    </div>

    <!-- 보고서 본문 (A4 비율) -->
    <div class="report-wrap">
      <div class="report-page">

        <!-- ① 헤더 -->
        <table class="header-table">
          <tr>
            <td class="report-title-cell" rowspan="2">
              <div class="report-title">주간업무보고</div>
              <div class="report-week">{{ report.week }}</div>
            </td>
            <td class="hdr-label">전주</td>
            <td class="hdr-value">{{ fmtRange(report.curr_start, report.curr_end) }}</td>
            <td class="hdr-label">이름</td>
            <td class="hdr-value name-cell">{{ report.user?.name ?? '-' }}</td>
          </tr>
          <tr>
            <td class="hdr-label">금주</td>
            <td class="hdr-value">{{ fmtRange(report.next_start, report.next_end) }}</td>
            <td class="hdr-label">직급</td>
            <td class="hdr-value">{{ report.user?.position ?? '사원' }}</td>
          </tr>
        </table>

        <!-- ② 본문 테이블 -->
        <table class="body-table">
          <thead>
            <tr>
              <th class="cat-th">구분</th>
              <th class="work-th">
                전주 업무
                <span class="date-hint">({{ fmtShort(report.curr_start) }} ~ {{ fmtShort(report.curr_end) }})</span>
              </th>
              <th class="work-th">
                금주 업무
                <span class="date-hint">({{ fmtShort(report.next_start) }} ~ {{ fmtShort(report.next_end) }})</span>
              </th>
            </tr>
          </thead>
          <tbody>
            <!-- 지원 행: 전주 / 금주 2열 -->
            <tr class="body-row">
              <td class="cat-td">지원</td>
              <td class="work-td">
                <div v-if="currBy['지원']?.length" class="item-list">
                  <div v-for="(item, i) in currBy['지원']" :key="i" class="item-block">
                    <div class="item-title"><span class="item-num">{{ i+1 }}.</span> {{ item.title || item.content }}</div>
                    <div v-for="(sub, si) in (item.sub_items || [])" :key="si" class="sub-item">
                      <span class="sub-dash">-</span> {{ sub }}
                    </div>
                  </div>
                </div>
                <span v-else class="empty-cell">-</span>
              </td>
              <td class="work-td">
                <div v-if="nextBy['지원']?.length" class="item-list">
                  <div v-for="(item, i) in nextBy['지원']" :key="i" class="item-block">
                    <div class="item-title"><span class="item-num">{{ i+1 }}.</span> {{ item.title || item.content }}</div>
                    <div v-for="(sub, si) in (item.sub_items || [])" :key="si" class="sub-item">
                      <span class="sub-dash">-</span> {{ sub }}
                    </div>
                  </div>
                </div>
                <span v-else class="empty-cell">-</span>
              </td>
            </tr>

            <!-- 내부작업 -->
            <tr class="body-row">
              <td class="cat-td">내부작업</td>
              <td colspan="2" class="work-td">
                <div v-if="currBy['내부작업']?.length" class="item-list">
                  <div v-for="(item, i) in currBy['내부작업']" :key="i" class="item-block">
                    <div class="item-title"><span class="item-num">{{ i+1 }}.</span> {{ item.title || item.content }}</div>
                    <div v-for="(sub, si) in (item.sub_items || [])" :key="si" class="sub-item">
                      <span class="sub-dash">-</span> {{ sub }}
                    </div>
                  </div>
                </div>
                <span v-else class="empty-cell">-</span>
              </td>
            </tr>

            <!-- Todo 행 -->
            <tr v-if="pendingTodos.length || doneTodos.length" class="body-row">
              <td class="cat-td todo-cat">Todo 항목<br><span style="font-size:9px;font-weight:400;">(달성미정)</span></td>
              <td colspan="2" class="work-td">
                <div class="todo-grid">
                  <div v-for="(t, i) in report.todo_items" :key="i" class="todo-item">
                    <span class="todo-check" :class="{ done: t.done }">{{ t.done ? '☑' : '☐' }}</span>
                    <span class="todo-text" :class="{ done: t.done }">{{ t.content }}</span>
                  </div>
                </div>
              </td>
            </tr>

            <!-- 공유 -->
            <tr class="body-row">
              <td class="cat-td">공유</td>
              <td colspan="2" class="work-td">
                <div v-if="currBy['공유']?.length" class="item-list">
                  <div v-for="(item, i) in currBy['공유']" :key="i" class="item-block">
                    <div class="item-title"><span class="item-num">{{ i+1 }}.</span> {{ item.title || item.content }}</div>
                    <div v-for="(sub, si) in (item.sub_items || [])" :key="si" class="sub-item">
                      <span class="sub-dash">-</span> {{ sub }}
                    </div>
                  </div>
                </div>
                <span v-else class="empty-cell">-</span>
              </td>
            </tr>

            <!-- 기타 -->
            <tr class="body-row">
              <td class="cat-td">기타</td>
              <td colspan="2" class="work-td">
                <div v-if="currBy['기타']?.length" class="item-list">
                  <div v-for="(item, i) in currBy['기타']" :key="i" class="item-block">
                    <div class="item-title"><span class="item-num">{{ i+1 }}.</span> {{ item.title || item.content }}</div>
                    <div v-for="(sub, si) in (item.sub_items || [])" :key="si" class="sub-item">
                      <span class="sub-dash">-</span> {{ sub }}
                    </div>
                  </div>
                </div>
                <span v-else class="empty-cell">-</span>
              </td>
            </tr>

            <!-- 특이사항 -->
            <tr v-if="report.notes" class="body-row">
              <td class="cat-td">특이사항</td>
              <td colspan="2" class="work-td pre-wrap">{{ report.notes }}</td>
            </tr>

            <!-- 요청사항 -->
            <tr v-if="report.requests" class="body-row">
              <td class="cat-td">요청사항</td>
              <td colspan="2" class="work-td pre-wrap">{{ report.requests }}</td>
            </tr>

            <!-- 빈 행 -->
            <tr v-if="isEmpty">
              <td colspan="3" class="work-td" style="text-align:center;color:#999;padding:32px;">작성된 내용이 없습니다</td>
            </tr>
          </tbody>
        </table>

        <!-- ③ 푸터 -->
        <div class="report-footer no-print">
          <span v-if="report.submitted_at">제출일: <strong>{{ fmt(report.submitted_at) }}</strong></span>
          <span v-if="report.reviewed_at" style="color:#16A34A;">검토일: <strong>{{ fmt(report.reviewed_at) }}</strong></span>
        </div>
      </div>
    </div>

    <style>
      * { box-sizing: border-box; }
      body { background: #e8e4dc; margin: 0; font-family: 'Noto Sans KR', 'Malgun Gothic', sans-serif; }

      .report-wrap {
        padding: 60px 24px 40px;
        display: flex;
        justify-content: center;
      }

      .report-page {
        background: #fff;
        width: 210mm;
        min-height: 297mm;
        box-shadow: 0 4px 32px rgba(0,0,0,0.18);
        padding: 14mm 14mm 12mm;
      }

      /* 헤더 테이블 */
      .header-table {
        width: 100%;
        border-collapse: collapse;
        border: 2px solid #000;
        margin-bottom: 0;
      }
      .report-title-cell {
        padding: 10px 16px;
        border-right: 2px solid #000;
        width: 160px;
      }
      .report-title {
        font-size: 20px;
        font-weight: 900;
        letter-spacing: -0.02em;
        font-family: 'Space Grotesk', 'Noto Sans KR', sans-serif;
      }
      .report-week {
        font-size: 11px;
        color: #777;
        margin-top: 2px;
      }
      .hdr-label {
        background: #F5EDDB;
        border: 1px solid #000;
        padding: 7px 12px;
        font-size: 11px;
        font-weight: 700;
        color: #555;
        white-space: nowrap;
        text-align: center;
        width: 36px;
      }
      .hdr-value {
        border: 1px solid #000;
        padding: 7px 14px;
        font-size: 12px;
        white-space: nowrap;
      }
      .name-cell { font-weight: 700; }

      /* 본문 테이블 */
      .body-table {
        width: 100%;
        border-collapse: collapse;
        border: 2px solid #000;
        border-top: none;
      }
      .cat-th {
        width: 60px;
        background: #F5EDDB;
        border: 1.5px solid #000;
        padding: 8px 4px;
        text-align: center;
        font-size: 11px;
        font-weight: 700;
        color: #555;
        letter-spacing: 0.04em;
      }
      .work-th {
        border: 1.5px solid #000;
        padding: 8px 12px;
        text-align: center;
        font-size: 12px;
        font-weight: 700;
        background: #F5EDDB;
      }
      .date-hint { font-weight: 400; color: #777; font-size: 10px; margin-left: 4px; }
      .body-row { border-bottom: 1.5px solid #000; }
      .cat-td {
        padding: 10px 5px;
        text-align: center;
        font-size: 11px;
        font-weight: 700;
        background: #F5EDDB;
        border-right: 1.5px solid #000;
        vertical-align: top;
        white-space: nowrap;
        line-height: 1.4;
      }
      .todo-cat { background: #F5EDDB; }
      .work-td {
        padding: 10px 12px;
        vertical-align: top;
        border-right: 1px solid #ddd;
        font-size: 12px;
      }
      .work-td:last-child { border-right: none; }
      .pre-wrap { white-space: pre-wrap; line-height: 1.65; }

      /* 항목 */
      .item-list { display: flex; flex-direction: column; gap: 8px; }
      .item-block {}
      .item-title { font-weight: 700; font-size: 12px; line-height: 1.5; color: #111; }
      .item-num { color: #888; font-family: 'Space Grotesk', sans-serif; margin-right: 1px; }
      .sub-item { font-size: 11px; color: #333; line-height: 1.55; margin-left: 0; margin-top: 2px; white-space: pre-wrap; }
      .sub-dash { color: #888; margin-right: 3px; }
      .empty-cell { color: #bbb; font-size: 12px; }

      /* Todo */
      .todo-grid { display: flex; flex-direction: column; gap: 4px; }
      .todo-item { display: flex; gap: 6px; align-items: flex-start; font-size: 11.5px; }
      .todo-check { flex-shrink: 0; font-size: 13px; color: #888; }
      .todo-check.done { color: #16A34A; }
      .todo-text { line-height: 1.5; color: #111; }
      .todo-text.done { text-decoration: line-through; color: #999; }

      /* 푸터 */
      .report-footer { display: flex; gap: 24px; margin-top: 12px; font-size: 11px; color: #888; }
      .report-footer strong { color: #333; }

      /* 인쇄 */
      @media print {
        body { background: #fff; }
        .no-print { display: none !important; }
        .report-wrap { padding: 0; }
        .report-page {
          width: 100%;
          min-height: auto;
          box-shadow: none;
          padding: 8mm 10mm;
        }
        .body-row { page-break-inside: avoid; }
      }
    </style>
  </div>
</template>

<script setup>
const props = defineProps({
  report: { type: Object, required: true },
})

const fmt      = (d) => d ? String(d).substring(0, 10) : ''
const fmtShort = (d) => d ? String(d).substring(5, 10).replace('-', '/') : '-'
const fmtRange = (s, e) => `${fmtShort(s)} ~ ${fmtShort(e)}`

const doPrint  = () => window.print()

const SINGLE_CATS = ['내부작업', '공유', '기타']

const currBy = (() => {
  const map = {}
  for (const item of (props.report.curr_work ?? [])) {
    const cat = item.category ?? '기타'
    if (!map[cat]) map[cat] = []
    map[cat].push(item)
  }
  return map
})()

const nextBy = (() => {
  const map = {}
  for (const item of (props.report.next_plan ?? [])) {
    const cat = item.category ?? '기타'
    if (!map[cat]) map[cat] = []
    map[cat].push(item)
  }
  return map
})()

// 단일 열 카테고리 (데이터에 있는 것만 표시)
const singleCats = (() => {
  const allCats = new Set([...Object.keys(currBy), ...Object.keys(nextBy)])
  allCats.delete('지원')
  return [...SINGLE_CATS.filter(c => allCats.has(c)),
          ...[...allCats].filter(c => !SINGLE_CATS.includes(c))]
})()

const pendingTodos = (props.report.todo_items ?? []).filter(t => !t.done)
const doneTodos    = (props.report.todo_items ?? []).filter(t => t.done)

const isEmpty = !props.report.curr_work?.length
  && !props.report.next_plan?.length
  && !props.report.todo_items?.length
  && !props.report.notes
  && !props.report.requests
</script>
