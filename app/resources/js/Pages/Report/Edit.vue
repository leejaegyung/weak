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
        <button type="button" @click="submit" :disabled="submitting || form.processing" class="btn-primary"
          :style="{ opacity: (submitting || form.processing) ? 0.7 : 1, cursor: (submitting || form.processing) ? 'not-allowed' : 'pointer' }">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
          {{ (submitting || form.processing) ? '저장 중...' : '수정 저장' }}
        </button>
      </div>
    </div>

    <form @submit.prevent="submit" style="display:flex;flex-direction:column;gap:18px;">

      <!-- 내 주간 일정 (팀 일정판 연동) — 토글 -->
      <div class="card" style="padding:0;overflow:hidden;">
        <!-- 섹션 헤더 (클릭으로 토글) -->
        <div @click="showSchedule = !showSchedule"
          style="padding:12px 18px;background:#F5EDDB;display:flex;align-items:center;gap:8px;cursor:pointer;user-select:none;transition:background 0.1s;"
          :style="{ borderBottom: showSchedule ? '2px solid #1A1100' : 'none' }"
          @mouseenter="e => e.currentTarget.style.background='#EDE3C8'"
          @mouseleave="e => e.currentTarget.style.background='#F5EDDB'">
          <div style="background:#FDCB40;border:2px solid #1A1100;border-radius:8px;width:28px;height:28px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
          </div>
          <span style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:14px;font-weight:800;">내 주간 일정</span>
          <span style="font-size:11px;color:#9A8F7A;">— 저장 시 팀 일정판에 자동 반영됩니다</span>
          <!-- 열림/닫힘 화살표 -->
          <svg style="margin-left:auto;flex-shrink:0;transition:transform 0.2s;"
            :style="{ transform: showSchedule ? 'rotate(180deg)' : 'rotate(0deg)' }"
            width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9A8F7A" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 12 15 18 9"/>
          </svg>
        </div>

        <!-- 일정 그리드 테이블 (토글) -->
        <table v-if="showSchedule" style="width:100%;border-collapse:collapse;">
          <colgroup>
            <col style="width:56px;" />
            <col /><col /><col /><col /><col />
          </colgroup>
          <thead>
            <tr style="background:#FDFAF5;border-bottom:1.5px solid #E8E0D0;">
              <th style="padding:7px 6px;border-right:2px solid #1A1100;"></th>
              <th v-for="day in ['월','화','수','목','금']" :key="day"
                style="padding:7px 4px;text-align:center;font-family:'Space Grotesk',sans-serif;font-size:12px;font-weight:700;color:#9A8F7A;border-right:1.5px solid #E8E0D0;">
                {{ day }}
              </th>
            </tr>
          </thead>
          <tbody>
            <!-- 금주 -->
            <tr style="border-bottom:2px solid #1A1100;">
              <td style="padding:10px 6px;text-align:center;font-size:12px;font-weight:800;background:#FFF8EE;border-right:2px solid #1A1100;white-space:nowrap;color:#1A1100;vertical-align:middle;">
                금주
              </td>
              <td v-for="date in currDates" :key="'c-'+date"
                style="padding:8px 6px;border-right:1.5px solid #E8E0D0;vertical-align:top;">
                <div style="font-size:10px;color:#9A8F7A;font-weight:600;text-align:center;margin-bottom:5px;">{{ fmtDateOnly(date) }}</div>
                <div @click="openSchedModal(date, '금주')"
                  style="min-height:34px;border:1.5px solid #E8E0D0;border-radius:8px;padding:5px 8px;font-size:12px;color:#4A3F2A;cursor:pointer;background:#fff;display:flex;flex-direction:column;align-items:flex-start;justify-content:flex-start;gap:2px;transition:all 0.12s;line-height:1.5;"
                  @mouseenter="e=>{e.currentTarget.style.borderColor='#FDCB40';e.currentTarget.style.background='#FFFBF0';}"
                  @mouseleave="e=>{e.currentTarget.style.borderColor='#E8E0D0';e.currentTarget.style.background='#fff';}">
                  <template v-if="schedules[date]">
                    <template v-for="slot in parsedSchedCell(schedules[date]).slots" :key="slot.time + slot.status">
                      <div :style="{ display:'flex', alignItems:'center', gap:'2px', padding:'1px 6px', borderRadius:'2px', fontSize:'10px', fontWeight:'800', background: slot.status && SCHED_STATUS_MAP[slot.status] ? SCHED_STATUS_MAP[slot.status].bg : '#FFEDD5', color: slot.status && SCHED_STATUS_MAP[slot.status] ? SCHED_STATUS_MAP[slot.status].color : '#C2410C', border:'1px solid '+(slot.status && SCHED_STATUS_MAP[slot.status] ? SCHED_STATUS_MAP[slot.status].border : '#FDBA74'), width:'100%', overflow:'hidden' }">
                        <span style="font-size:9px;opacity:0.6;white-space:nowrap;">({{ slot.time }})</span>
                        <span v-if="slot.status && SCHED_STATUS_MAP[slot.status]" style="flex-shrink:0;">{{ SCHED_STATUS_MAP[slot.status].icon }}</span>
                        <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ [slot.status, slot.sites.join(', ')].filter(Boolean).join(': ') }}</span>
                      </div>
                    </template>
                    <div v-if="parsedSchedCell(schedules[date]).content" style="display:flex;align-items:center;gap:2px;padding:1px 5px;border-radius:2px;font-size:10px;font-weight:700;background:#CFFAFE;color:#0E7490;border:1px solid #67E8F9;width:100%;">
                      ✏ {{ parsedSchedCell(schedules[date]).content }}
                    </div>
                  </template>
                  <span v-else style="color:#C5BAA8;font-size:11px;">+ 추가</span>
                </div>
              </td>
            </tr>
            <!-- 차주 -->
            <tr>
              <td style="padding:10px 6px;text-align:center;font-size:12px;font-weight:800;background:#FFF8EE;border-right:2px solid #1A1100;white-space:nowrap;color:#1A1100;vertical-align:middle;">
                차주
              </td>
              <td v-for="date in nextDates" :key="'n-'+date"
                style="padding:8px 6px;border-right:1.5px solid #E8E0D0;vertical-align:top;">
                <div style="font-size:10px;color:#9A8F7A;font-weight:600;text-align:center;margin-bottom:5px;">{{ fmtDateOnly(date) }}</div>
                <div @click="openSchedModal(date, '차주')"
                  style="min-height:34px;border:1.5px solid #E8E0D0;border-radius:8px;padding:5px 8px;font-size:12px;color:#4A3F2A;cursor:pointer;background:#fff;display:flex;flex-direction:column;align-items:flex-start;justify-content:flex-start;gap:2px;transition:all 0.12s;line-height:1.5;"
                  @mouseenter="e=>{e.currentTarget.style.borderColor='#FDCB40';e.currentTarget.style.background='#FFFBF0';}"
                  @mouseleave="e=>{e.currentTarget.style.borderColor='#E8E0D0';e.currentTarget.style.background='#fff';}">
                  <template v-if="schedules[date]">
                    <template v-for="slot in parsedSchedCell(schedules[date]).slots" :key="slot.time + slot.status">
                      <div :style="{ display:'flex', alignItems:'center', gap:'2px', padding:'1px 6px', borderRadius:'2px', fontSize:'10px', fontWeight:'800', background: slot.status && SCHED_STATUS_MAP[slot.status] ? SCHED_STATUS_MAP[slot.status].bg : '#FFEDD5', color: slot.status && SCHED_STATUS_MAP[slot.status] ? SCHED_STATUS_MAP[slot.status].color : '#C2410C', border:'1px solid '+(slot.status && SCHED_STATUS_MAP[slot.status] ? SCHED_STATUS_MAP[slot.status].border : '#FDBA74'), width:'100%', overflow:'hidden' }">
                        <span style="font-size:9px;opacity:0.6;white-space:nowrap;">({{ slot.time }})</span>
                        <span v-if="slot.status && SCHED_STATUS_MAP[slot.status]" style="flex-shrink:0;">{{ SCHED_STATUS_MAP[slot.status].icon }}</span>
                        <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ [slot.status, slot.sites.join(', ')].filter(Boolean).join(': ') }}</span>
                      </div>
                    </template>
                    <div v-if="parsedSchedCell(schedules[date]).content" style="display:flex;align-items:center;gap:2px;padding:1px 5px;border-radius:2px;font-size:10px;font-weight:700;background:#CFFAFE;color:#0E7490;border:1px solid #67E8F9;width:100%;">
                      ✏ {{ parsedSchedCell(schedules[date]).content }}
                    </div>
                  </template>
                  <span v-else style="color:#C5BAA8;font-size:11px;">+ 추가</span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- ── 일정 입력 모달 ── -->
      <Transition name="sched-modal-fade">
        <div v-if="schedModalVisible"
          style="position:fixed;inset:0;background:rgba(26,17,0,0.45);display:flex;align-items:center;justify-content:center;z-index:400;backdrop-filter:blur(3px);padding:16px;"
          @click.self="closeSchedModal">
          <div style="width:460px;max-width:100%;max-height:90vh;background:#FFF8EE;border:2px solid #1A1100;border-radius:18px;box-shadow:6px 6px 0 #1A1100;overflow:hidden;display:flex;flex-direction:column;">

            <!-- 모달 헤더 -->
            <div style="padding:16px 22px;background:#F5EDDB;border-bottom:2px solid #1A1100;display:flex;justify-content:space-between;align-items:center;flex-shrink:0;">
              <div>
                <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:15px;font-weight:800;color:#1A1100;">
                  {{ schedModalWeek }} 일정 입력
                </div>
                <div style="font-size:12px;color:#9A8F7A;margin-top:2px;">
                  {{ schedModalDate }} ({{ schedModalDayKr }})
                </div>
              </div>
              <button type="button" @click="closeSchedModal"
                style="background:none;border:none;cursor:pointer;color:#9A8F7A;padding:4px;border-radius:6px;transition:color 0.1s;"
                @mouseenter="e=>e.currentTarget.style.color='#DC2626'"
                @mouseleave="e=>e.currentTarget.style.color='#9A8F7A'">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
              </button>
            </div>

            <!-- 모달 바디 -->
            <div style="padding:20px 22px;display:flex;flex-direction:column;gap:12px;overflow-y:auto;flex:1;min-height:0;">

              <!-- 시간 선택 -->
              <div style="background:#F5EDDB;border:1.5px solid #D0C9BC;border-radius:10px;padding:10px 12px;">
                <div style="display:flex;align-items:center;gap:5px;margin-bottom:8px;">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#9A8F7A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                  <span style="font-size:11px;color:#9A8F7A;font-weight:700;letter-spacing:0.03em;">시간</span>
                  <span style="font-size:10px;color:#C5BAA8;">시간대별로 각각 등록됩니다</span>
                </div>
                <div style="display:flex;gap:6px;">
                  <button v-for="t in ['종일','오전','오후']" :key="t" type="button" @click="schedModalTime = t"
                    :style="{
                      padding:'5px 16px', borderRadius:'4px', fontSize:'12px', fontWeight:'700',
                      border: schedModalTime === t ? '2px solid #1A1100' : '2px solid #D0C9BC',
                      background: schedModalTime === t ? '#1A1100' : '#fff',
                      color: schedModalTime === t ? '#FDCB40' : '#6B5E4A',
                      cursor:'pointer', fontFamily:'inherit', transition:'all 0.12s', position:'relative',
                    }">
                    {{ t }}
                    <span v-if="schedModalAllSlots[t].status" :style="{
                      position:'absolute', top:'-5px', right:'-5px',
                      width:'8px', height:'8px', borderRadius:'50%',
                      background: SCHED_STATUS_MAP[schedModalAllSlots[t].status]?.border ?? '#9A8F7A',
                      border:'1.5px solid #fff',
                    }"></span>
                  </button>
                </div>
                <!-- 등록된 슬롯 미리보기 -->
                <div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:4px;">
                  <template v-for="t in ['종일','오전','오후']" :key="t">
                    <div v-if="schedModalAllSlots[t].status && SCHED_STATUS_MAP[schedModalAllSlots[t].status]"
                      :style="{
                        display:'inline-flex', alignItems:'center', gap:'3px',
                        padding:'2px 7px', borderRadius:'4px', fontSize:'11px', fontWeight:'700',
                        background: SCHED_STATUS_MAP[schedModalAllSlots[t].status].bg,
                        color: SCHED_STATUS_MAP[schedModalAllSlots[t].status].color,
                        border: '1.5px solid ' + SCHED_STATUS_MAP[schedModalAllSlots[t].status].border,
                      }">
                      <span style="opacity:0.6;font-size:10px;">({{ t }})</span>
                      {{ SCHED_STATUS_MAP[schedModalAllSlots[t].status].icon }} {{ schedModalAllSlots[t].status }}
                    </div>
                  </template>
                  <div v-if="schedModalContent.trim()" style="display:inline-flex;align-items:center;gap:2px;padding:2px 7px;border-radius:4px;font-size:11px;font-weight:700;background:#CFFAFE;color:#0E7490;border:1.5px solid #67E8F9;">
                    ✏ {{ schedModalContent }}
                  </div>
                </div>
              </div>

              <!-- 상태 -->
              <div style="background:#F8F7FF;border:1.5px solid #E0DCF5;border-radius:10px;padding:10px 12px;">
                <div style="display:flex;align-items:center;gap:5px;margin-bottom:8px;">
                  <span style="font-size:11px;color:#9A8F7A;font-weight:700;letter-spacing:0.03em;">상태</span>
                  <span style="font-size:10px;color:#B8B0C8;">{{ schedModalTime }} · 하나만 선택</span>
                </div>
                <div style="display:flex;gap:7px;flex-wrap:wrap;">
                  <button v-for="tag in SCHED_QUICK_TAGS" :key="tag.label" type="button"
                    @click="toggleSchedStatus(tag.label)"
                    :style="{
                      display:'inline-flex', alignItems:'center', gap:'5px',
                      padding:'5px 13px', borderRadius:'4px', fontSize:'12px', fontWeight:'700',
                      border: schedModalAllSlots[schedModalTime].status === tag.label ? '2px solid #1A1100' : '2px solid #D0C9BC',
                      background: schedModalAllSlots[schedModalTime].status === tag.label ? tag.bg : '#fff',
                      color: schedModalAllSlots[schedModalTime].status === tag.label ? tag.color : '#9A8F7A',
                      cursor:'pointer', fontFamily:'inherit', transition:'all 0.12s',
                      boxShadow: schedModalAllSlots[schedModalTime].status === tag.label ? '2px 2px 0 #1A1100' : 'none',
                    }">
                    <span>{{ tag.icon }}</span>{{ tag.label }}
                  </button>
                </div>
              </div>

              <!-- 내 사이트 -->
              <div v-if="mySites.length"
                style="background:#FFFBF0;border:1.5px solid #E8E0D0;border-radius:10px;padding:10px 12px;">
                <div style="display:flex;align-items:center;gap:5px;margin-bottom:8px;">
                  <span style="font-size:11px;color:#9A8F7A;font-weight:700;letter-spacing:0.03em;">내 사이트</span>
                  <span style="font-size:10px;color:#C5BAA8;">{{ schedModalTime }}</span>
                </div>
                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                  <button v-for="site in mySites" :key="site" type="button"
                    @click="toggleSchedSite(site)"
                    :style="{
                      display:'inline-flex', alignItems:'center', gap:'4px',
                      padding:'4px 12px', borderRadius:'4px', fontSize:'12px', fontWeight:'700',
                      border: schedModalAllSlots[schedModalTime].sites.includes(site) ? '2px solid #1A1100' : '2px solid #D0C9BC',
                      background: schedModalAllSlots[schedModalTime].sites.includes(site) ? '#FDCB40' : '#fff',
                      color: schedModalAllSlots[schedModalTime].sites.includes(site) ? '#1A1100' : '#6B5E4A',
                      cursor:'pointer', fontFamily:'inherit', transition:'all 0.12s',
                      boxShadow: schedModalAllSlots[schedModalTime].sites.includes(site) ? '2px 2px 0 #1A1100' : 'none',
                    }">
                    {{ site }}
                  </button>
                </div>
              </div>

              <!-- 내용 -->
              <div style="background:#F0F9FF;border:1.5px solid #BAE6FD;border-radius:10px;padding:10px 12px;">
                <div style="display:flex;align-items:center;gap:5px;margin-bottom:8px;">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#0369A1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                  </svg>
                  <span style="font-size:11px;color:#0369A1;font-weight:700;letter-spacing:0.03em;">내용</span>
                  <span style="font-size:10px;color:#7DD3FC;">단일 칩으로 표시됩니다</span>
                </div>
                <div v-if="schedModalContent.trim()" style="margin-bottom:8px;">
                  <div style="display:inline-flex;align-items:center;gap:3px;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;background:#CFFAFE;color:#0E7490;border:1.5px solid #67E8F9;">
                    <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    {{ schedModalContent }}
                  </div>
                </div>
                <textarea
                  v-model="schedModalContent"
                  rows="2"
                  placeholder="내용을 입력하세요 (줄바꿈 없이 하나의 칩)"
                  style="width:100%;background:#fff;border:1.5px solid #BAE6FD;border-radius:8px;padding:8px 11px;color:#1A1100;font-size:13px;font-family:inherit;outline:none;resize:none;line-height:1.65;transition:border-color 0.12s;"
                  @focus="e=>e.target.style.borderColor='#0369A1'"
                  @blur="e=>e.target.style.borderColor='#BAE6FD'"
                  @keydown.enter.prevent
                  @keydown.ctrl.enter.prevent="saveSchedModal"
                  @keydown.meta.enter.prevent="saveSchedModal"
                ></textarea>
              </div>
              <p style="font-size:11px;color:#9A8F7A;margin-top:4px;">모두 비워두면 해당 날짜 일정이 삭제됩니다 &nbsp;·&nbsp; Ctrl+Enter로 빠르게 저장</p>
            </div>

            <!-- 모달 푸터 -->
            <div style="padding:14px 22px;background:#F5EDDB;border-top:2px solid #1A1100;display:flex;justify-content:space-between;align-items:center;flex-shrink:0;">
              <button v-if="schedules[schedModalDate]" type="button" @click="deleteSchedModal"
                style="display:inline-flex;align-items:center;gap:5px;background:#FEE2E2;color:#DC2626;border:2px solid #DC2626;border-radius:8px;padding:6px 12px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;transition:all 0.1s;"
                @mouseenter="e=>{e.currentTarget.style.background='#DC2626';e.currentTarget.style.color='#fff';}"
                @mouseleave="e=>{e.currentTarget.style.background='#FEE2E2';e.currentTarget.style.color='#DC2626';}">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                  <path d="M10 11v6M14 11v6"/>
                </svg>
                삭제
              </button>
              <div v-else></div>
              <div style="display:flex;gap:8px;">
                <button type="button" @click="closeSchedModal"
                  style="display:inline-flex;align-items:center;gap:5px;background:#fff;color:#1A1100;border:2px solid #1A1100;border-radius:8px;padding:6px 14px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;transition:all 0.1s;"
                  @mouseenter="e=>{e.currentTarget.style.background='#F5EDDB';}"
                  @mouseleave="e=>{e.currentTarget.style.background='#fff';}">
                  취소
                </button>
                <button type="button" @click="saveSchedModal"
                  style="display:inline-flex;align-items:center;gap:5px;background:#FDCB40;color:#1A1100;border:2px solid #1A1100;border-radius:8px;padding:6px 16px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:2px 2px 0 #1A1100;transition:all 0.1s;"
                  @mouseenter="e=>{e.currentTarget.style.transform='translate(-1px,-1px)';e.currentTarget.style.boxShadow='3px 3px 0 #1A1100';}"
                  @mouseleave="e=>{e.currentTarget.style.transform='none';e.currentTarget.style.boxShadow='2px 2px 0 #1A1100';}">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                  저장
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>

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
      <div class="card" style="padding:0;overflow:hidden;">
        <!-- 헤더 -->
        <div style="padding:10px 14px 10px 18px;border-bottom:2px solid #1A1100;background:#F5EDDB;font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:13px;font-weight:700;display:flex;align-items:center;justify-content:space-between;gap:8px;">
          <span>Todo 목록</span>
        </div>
        <div style="padding:12px 16px;display:flex;flex-direction:column;gap:8px;">
          <div v-if="form.todo_items.length === 0" style="text-align:center;padding:16px 0;color:#9A8F7A;font-size:13px;">항목을 추가하세요</div>
          <!-- todo item 각각 -->
          <div v-for="(t, idx) in form.todo_items" :key="idx"
            style="border:1.5px solid #E8E0D0;border-radius:10px;padding:10px 12px 8px;background:#FDFAF5;margin-bottom:0;">
            <!-- 번호 + checkbox + 텍스트 -->
            <div style="display:flex;gap:7px;align-items:center;margin-bottom:6px;">
              <span style="font-size:11px;color:#9A8F7A;font-weight:700;flex-shrink:0;min-width:18px;text-align:right;">{{ idx+1 }}.</span>
              <input type="checkbox" v-model="t.done" style="accent-color:#FD4401;width:15px;height:15px;cursor:pointer;flex-shrink:0;" />
              <textarea v-model="t.content" rows="1"
                @input="e => { e.target.style.height='auto'; e.target.style.height=e.target.scrollHeight+'px' }"
                class="input-field todo-textarea"
                :style="{ textDecoration: t.done ? 'line-through' : 'none', flex:1, resize:'none', overflow:'hidden', minHeight:'36px', lineHeight:'1.55', fontWeight:'700', fontSize:'13px' }"
                placeholder="할 일을 입력하세요" />
              <button type="button" @click="removeTodo(idx)"
                style="background:none;border:none;cursor:pointer;color:#D0C9BC;padding:4px;border-radius:6px;flex-shrink:0;"
                @mouseenter="e=>e.currentTarget.style.color='#DC2626'"
                @mouseleave="e=>e.currentTarget.style.color='#D0C9BC'">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
              </button>
            </div>
            <!-- sub_items (내용) -->
            <div style="margin-left:40px;display:flex;flex-direction:column;gap:3px;">
              <div v-for="(sub, sIdx) in (t.sub_items || [])" :key="sIdx"
                style="display:flex;gap:6px;align-items:flex-start;">
                <span style="color:#9A8F7A;font-size:12px;flex-shrink:0;margin-top:7px;">-</span>
                <textarea :value="typeof sub === 'string' ? sub : sub.content"
                  @input="e => { updateTodoSub(idx, sIdx, e.target.value); e.target.style.height='auto'; e.target.style.height=e.target.scrollHeight+'px' }"
                  class="input-field"
                  rows="1"
                  placeholder="내용"
                  style="flex:1;resize:none;overflow:hidden;min-height:28px;font-size:12px;line-height:1.5;padding-top:4px;padding-bottom:4px;color:#4A3F2A;" />
                <button type="button" @click="removeTodoSub(idx, sIdx)"
                  style="background:none;border:none;cursor:pointer;color:#D0C9BC;padding:3px;border-radius:5px;flex-shrink:0;"
                  @mouseenter="e=>e.currentTarget.style.color='#DC2626'"
                  @mouseleave="e=>e.currentTarget.style.color='#D0C9BC'">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
              </div>
              <button type="button" @click="addTodoSub(idx)"
                style="display:inline-flex;align-items:center;gap:4px;background:none;border:1.5px dashed #D0C9BC;border-radius:6px;padding:3px 9px;font-size:11px;color:#9A8F7A;cursor:pointer;font-family:inherit;font-weight:600;align-self:flex-start;margin-top:2px;"
                @mouseenter="e=>{e.currentTarget.style.borderColor='#FD4401';e.currentTarget.style.color='#FD4401';}"
                @mouseleave="e=>{e.currentTarget.style.borderColor='#D0C9BC';e.currentTarget.style.color='#9A8F7A';}">
                <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                내용 추가
              </button>
            </div>
          </div>
          <!-- 전체 항목 추가 버튼 -->
          <button type="button" @click="addTodo"
            style="display:inline-flex;align-items:center;gap:5px;background:rgba(26,17,0,0.04);border:1.5px dashed #C5BAA8;border-radius:8px;padding:6px 14px;font-size:12px;color:#9A8F7A;cursor:pointer;font-family:inherit;font-weight:600;transition:all 0.1s;"
            @mouseenter="e=>{e.currentTarget.style.borderColor='#FD4401';e.currentTarget.style.color='#FD4401';e.currentTarget.style.background='rgba(253,68,1,0.04)';}"
            @mouseleave="e=>{e.currentTarget.style.borderColor='#C5BAA8';e.currentTarget.style.color='#9A8F7A';e.currentTarget.style.background='rgba(26,17,0,0.04)';}">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
            항목 추가
          </button>
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
          <textarea v-model="form.notes" rows="1"
            @input="e => { e.target.style.height='auto'; e.target.style.height=e.target.scrollHeight+'px' }"
            class="input-field auto-resize-ta"
            style="resize:none;overflow:hidden;min-height:80px;line-height:1.65;"
            placeholder="이번 주 특이사항을 입력해주세요"></textarea>
        </div>
        <div class="card">
          <label style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:14px;font-weight:700;display:block;margin-bottom:12px;">요청사항</label>
          <textarea v-model="form.requests" rows="1"
            @input="e => { e.target.style.height='auto'; e.target.style.height=e.target.scrollHeight+'px' }"
            class="input-field auto-resize-ta"
            style="resize:none;overflow:hidden;min-height:80px;line-height:1.65;"
            placeholder="관리자에게 전달할 요청사항"></textarea>
        </div>
      </div>
    </form>
  </AppLayout>
</template>

<style scoped>
.sched-modal-fade-enter-active, .sched-modal-fade-leave-active { transition: all 0.2s ease; }
.sched-modal-fade-enter-from, .sched-modal-fade-leave-to { opacity: 0; transform: scale(0.97); }
</style>

<script setup>
import { ref, reactive, computed, onMounted, nextTick } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SupportSection from '@/Components/SupportSection.vue'

// 기존 데이터 로드 시 모든 자동 높이 textarea 초기화
onMounted(() => {
  const doResize = () => {
    document.querySelectorAll('.todo-textarea, .auto-resize-ta').forEach(el => {
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
})

const props = defineProps({
  report:      { type: Object, required: true },
  mySchedules: { type: Object, default: () => ({}) },
  weekInfo:    { type: Object, default: () => ({}) },
  mySites:     { type: Array,  default: () => [] },
})

// ── 내 주간 일정 ──────────────────────────────────────
const showSchedule = ref(false)
const schedules    = ref({ ...props.mySchedules })

const currDates = computed(() => {
  const start = props.weekInfo?.curr_start ?? form.curr_start
  if (!start) return []
  return Array.from({ length: 5 }, (_, i) => {
    const d = new Date(start + 'T00:00:00')
    d.setDate(d.getDate() + i)
    return d.toISOString().split('T')[0]
  })
})
const nextDates = computed(() => {
  const start = props.weekInfo?.next_start ?? form.next_start
  if (!start) return []
  return Array.from({ length: 5 }, (_, i) => {
    const d = new Date(start + 'T00:00:00')
    d.setDate(d.getDate() + i)
    return d.toISOString().split('T')[0]
  })
})
const fmtDateOnly = (d) => {
  if (!d) return ''
  const dt = new Date(d + 'T00:00:00')
  return `${String(dt.getMonth() + 1).padStart(2, '0')}/${String(dt.getDate()).padStart(2, '0')}`
}

// ── 일정 모달 ─────────────────────────────────────────
const SCHED_QUICK_TAGS = [
  { label: '외근', icon: '🏢', bg: '#DBEAFE', color: '#1D4ED8', border: '#93C5FD' },
  { label: '출장', icon: '✈️', bg: '#EDE9FE', color: '#7C3AED', border: '#C4B5FD' },
  { label: '반차', icon: '🕐', bg: '#FEF9C3', color: '#854D0E', border: '#FDE68A' },
  { label: '휴가', icon: '🌴', bg: '#DCFCE7', color: '#166534', border: '#86EFAC' },
]
const SCHED_STATUS_LABELS = SCHED_QUICK_TAGS.map(t => t.label)
const SCHED_STATUS_MAP    = Object.fromEntries(SCHED_QUICK_TAGS.map(t => [t.label, t]))
const DAY_KR_LIST = ['일', '월', '화', '수', '목', '금', '토']
const SCHED_TIME_ORDER = ['종일', '오전', '오후']

const parsedSchedCell = (text) => {
  if (!text?.trim()) return { slots: [], content: '' }
  const lines = text.trim().split('\n')
  const slots = []
  const contentLines = []
  const hasNewFmt = lines.some(l => /^\[[^\]]+\]/.test(l.trim()))

  if (hasNewFmt) {
    for (const line of lines) {
      const trimmed = line.trim()
      if (!trimmed) continue
      const m = trimmed.match(/^\[([^\]]+)\](.*)$/)
      if (m) {
        const time = m[1]
        const rest = m[2]
        const ci   = rest.indexOf(':')
        let status = '', sites = []
        if (ci >= 0) {
          status = rest.substring(0, ci).trim()
          sites  = rest.substring(ci + 1).split(',').map(s => s.trim()).filter(Boolean)
        } else {
          status = rest.trim()
        }
        slots.push({ time, status, sites })
      } else {
        contentLines.push(trimmed)
      }
    }
  } else {
    const header = lines[0]?.trim() ?? ''
    const ci = header.indexOf(':')
    let legacyStatus = '', legacySites = [], detailStart = 1
    if (ci === 0) {
      legacySites = header.substring(1).split(',').map(s => s.trim()).filter(Boolean)
    } else if (ci > 0) {
      const before = header.substring(0, ci).split(',').map(s => s.trim()).filter(Boolean)
      if (before.every(s => SCHED_STATUS_LABELS.includes(s))) {
        legacyStatus = before[0] ?? ''
        legacySites  = header.substring(ci + 1).split(',').map(s => s.trim()).filter(Boolean)
      } else { detailStart = 0 }
    } else {
      const parts = header.split(',').map(s => s.trim()).filter(Boolean)
      if (parts.length && parts.every(s => SCHED_STATUS_LABELS.includes(s))) legacyStatus = parts[0] ?? ''
      else if (SCHED_STATUS_LABELS.includes(header)) legacyStatus = header
      else detailStart = 0
    }
    if (legacyStatus || legacySites.length) slots.push({ time: '종일', status: legacyStatus, sites: legacySites })
    for (let i = detailStart; i < lines.length; i++) { const t = lines[i].trim(); if (t) contentLines.push(t) }
  }
  return { slots, content: contentLines.join(' ').trim() }
}

const schedModalVisible = ref(false)
const schedModalDate    = ref('')
const schedModalWeek    = ref('')
const schedModalTime    = ref('종일')
const schedModalContent = ref('')
const schedModalAllSlots = reactive({
  '종일': { status: '', sites: [] },
  '오전': { status: '', sites: [] },
  '오후': { status: '', sites: [] },
})

const schedModalDayKr = computed(() => {
  if (!schedModalDate.value) return ''
  const d = new Date(schedModalDate.value + 'T00:00:00')
  return DAY_KR_LIST[d.getDay()]
})

const resetSchedSlots = () => {
  for (const t of SCHED_TIME_ORDER) {
    schedModalAllSlots[t].status = ''
    schedModalAllSlots[t].sites  = []
  }
}

const openSchedModal = (date, week) => {
  schedModalDate.value = date
  schedModalWeek.value = week
  const parsed = parsedSchedCell(schedules.value[date] ?? '')
  resetSchedSlots()
  for (const slot of parsed.slots) {
    if (schedModalAllSlots[slot.time]) {
      schedModalAllSlots[slot.time].status = slot.status
      schedModalAllSlots[slot.time].sites  = [...slot.sites]
    }
  }
  schedModalContent.value = parsed.content
  schedModalTime.value    = '종일'
  schedModalVisible.value = true
}
const closeSchedModal = () => { schedModalVisible.value = false }

const buildSchedContent = () => {
  const lines = []
  for (const time of SCHED_TIME_ORDER) {
    const slot = schedModalAllSlots[time]
    if (slot.status || slot.sites.length) {
      const sitePart = slot.sites.join(',')
      lines.push(`[${time}]${slot.status}${sitePart ? ':' + sitePart : ''}`)
    }
  }
  const c = schedModalContent.value.replace(/\n/g, ' ').trim()
  if (c) lines.push(c)
  return lines.join('\n')
}

const saveSchedModal = () => {
  const content = buildSchedContent()
  if (content) schedules.value[schedModalDate.value] = content
  else         delete schedules.value[schedModalDate.value]
  schedModalVisible.value = false
}
const deleteSchedModal = () => {
  delete schedules.value[schedModalDate.value]
  schedModalVisible.value = false
}

const toggleSchedStatus = (label) => {
  const slot = schedModalAllSlots[schedModalTime.value]
  slot.status = slot.status === label ? '' : label
}
const toggleSchedSite = (site) => {
  const slot = schedModalAllSlots[schedModalTime.value]
  const idx  = slot.sites.indexOf(site)
  if (idx === -1) slot.sites.push(site)
  else            slot.sites.splice(idx, 1)
}
// ─────────────────────────────────────────────────────

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

const addTodo    = () => form.todo_items.push({ content: '', done: false, sub_items: [] })
const removeTodo = (idx) => form.todo_items.splice(idx, 1)
const addTodoSub = (idx) => {
  form.todo_items[idx].sub_items = [...(form.todo_items[idx].sub_items || []), '']
}
const removeTodoSub = (idx, sIdx) => {
  form.todo_items[idx].sub_items.splice(sIdx, 1)
}
const updateTodoSub = (idx, sIdx, val) => {
  const subs = form.todo_items[idx].sub_items || []
  subs[sIdx] = typeof subs[sIdx] === 'string' ? val : { ...subs[sIdx], content: val }
  form.todo_items[idx].sub_items = [...subs]
}

// ── 제출 로딩 상태 ──
const submitting = ref(false)

const submit = async () => {
  if (submitting.value || form.processing) return
  submitting.value = true

  // 일정 저장 먼저
  const allDates = [...currDates.value, ...nextDates.value]
  if (allDates.length) {
    try {
      await Promise.all(
        allDates.map(date =>
          window.axios.post('/schedules/upsert', {
            date,
            content: schedules.value[date] || null,
          })
        )
      )
    } catch (e) {
      console.warn('일정 저장 실패', e)
    }
  }

  // 보고서 저장
  form.transform(data => ({
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
  })).put(`/reports/${props.report.id}`, {
    onError: () => { submitting.value = false },
    onFinish: () => { submitting.value = false },
  })
}
</script>
