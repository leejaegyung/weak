<template>
  <AppLayout page-title="보고서 작성">

    <!-- ── 제출 중 전체화면 로딩 오버레이 ── -->
    <Transition name="submit-overlay">
      <div v-if="submitting"
        style="position:fixed;inset:0;background:rgba(26,17,0,0.55);display:flex;align-items:center;justify-content:center;z-index:500;backdrop-filter:blur(6px);">
        <div style="background:#FFFDF7;border:2px solid #1A1100;border-radius:20px;box-shadow:4px 4px 0 #1A1100;padding:32px 36px;display:flex;flex-direction:column;align-items:center;gap:20px;min-width:220px;max-width:calc(100vw - 32px);max-height:90vh;overflow-y:auto;">
          <!-- 스피너 -->
          <div style="position:relative;width:56px;height:56px;">
            <svg style="animation:spin 1s linear infinite;" width="56" height="56" viewBox="0 0 56 56" fill="none">
              <circle cx="28" cy="28" r="22" stroke="#E8E0D0" stroke-width="5"/>
              <path d="M28 6 A22 22 0 0 1 50 28" stroke="#FDCB40" stroke-width="5" stroke-linecap="round"/>
            </svg>
            <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
              </svg>
            </div>
          </div>
          <!-- 텍스트 -->
          <div style="text-align:center;">
            <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:16px;font-weight:800;color:#1A1100;letter-spacing:-0.02em;margin-bottom:6px;">{{ submitAction === 'draft' ? '임시 저장 중' : '보고서 제출 중' }}</div>
            <div style="font-size:12px;color:#9A8F7A;line-height:1.6;">일정 동기화 후 보고서를 저장하고 있습니다.<br>잠시만 기다려 주세요...</div>
          </div>
          <!-- 단계 표시 -->
          <div style="display:flex;align-items:center;gap:8px;font-size:11px;font-weight:700;color:#9A8F7A;">
            <span :style="{ color: submitStep >= 1 ? '#16A34A' : '#C5BAA8' }">
              {{ submitStep >= 1 ? '✓' : '○' }} 일정 저장
            </span>
            <span style="color:#E8E0D0;">→</span>
            <span :style="{ color: submitStep >= 2 ? '#16A34A' : submitStep === 1 ? '#FDCB40' : '#C5BAA8' }">
              {{ submitStep >= 2 ? '✓' : '○' }} {{ submitAction === 'draft' ? '임시 저장' : '보고서 제출' }}
            </span>
          </div>
        </div>
      </div>
    </Transition>

    <!-- 이번 주 보고서 중복 경고 팝업 -->
    <div v-if="showDuplicateAlert"
      style="position:fixed;inset:0;background:rgba(26,17,0,0.5);display:flex;align-items:center;justify-content:center;z-index:300;backdrop-filter:blur(4px);padding:16px;">
      <div class="card" style="width:400px;max-width:100%;max-height:90vh;overflow-y:auto;padding:28px;text-align:center;">
        <!-- 아이콘 -->
        <div style="width:56px;height:56px;background:#FFF0A0;border:2px solid #1A1100;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0zM12 9v4M12 17h.01"/>
          </svg>
        </div>
        <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:17px;font-weight:800;color:#1A1100;margin-bottom:8px;letter-spacing:-0.02em;">
          이번 주 보고서가 이미 있습니다
        </div>
        <div style="font-size:13px;color:#9A8F7A;line-height:1.7;margin-bottom:22px;">
          <strong style="color:#1A1100;">{{ existingReport?.week }}</strong> 주차 보고서가 이미 작성되어 있습니다.<br>
          한 주에 보고서는 하나만 작성할 수 있습니다.
        </div>
        <div style="display:flex;gap:8px;justify-content:center;">
          <button @click="showDuplicateAlert = false"
            class="btn-secondary">
            계속 작성
          </button>
          <Link :href="`/reports/${existingReport?.id}`" class="btn-primary"
            style="text-decoration:none;">
            기존 보고서 보기 →
          </Link>
        </div>
      </div>
    </div>
    <!-- 헤더 -->
    <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
      <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
          <div style="background:#FDCB40;border:2px solid #1A1100;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
          </div>
          <h1 style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:26px;font-weight:700;letter-spacing:-0.03em;">보고서 작성</h1>
        </div>
        <p style="color:#9A8F7A;font-size:13px;margin-left:42px;">주간 업무 내용을 작성해 주세요</p>
      </div>
      <div style="display:flex;gap:8px;align-items:center;">
        <!-- 이전 보고서 미리보기 아이콘 -->
        <button v-if="prevReports?.length" type="button" @click="showPrevModal = true"
          v-tooltip="'이전 보고서 불러오기'"
          style="background:#FFF0A0;border:2px solid #1A1100;border-radius:10px;padding:6px 12px;font-size:12px;font-family:inherit;cursor:pointer;color:#1A1100;font-weight:700;display:inline-flex;align-items:center;gap:6px;box-shadow:2px 2px 0 #1A1100;transition:all 0.1s;"
          @mouseenter="e=>{e.currentTarget.style.transform='translate(-1px,-1px)';e.currentTarget.style.boxShadow='3px 3px 0 #1A1100';}"
          @mouseleave="e=>{e.currentTarget.style.transform='none';e.currentTarget.style.boxShadow='2px 2px 0 #1A1100';}">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 3M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
          이전 보고서
        </button>
        <Link href="/reports" class="btn-secondary">취소</Link>
        <!-- 임시 저장 버튼 + 토스트 -->
        <div style="display:inline-flex;align-items:center;gap:8px;position:relative;">
          <button type="button" @click="saveDraft" :disabled="draftSaving || submitting"
            style="display:inline-flex;align-items:center;gap:6px;background:#F5EDDB;color:#1A1100;border:2px solid #1A1100;border-radius:10px;padding:7px 16px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:2px 2px 0 #1A1100;transition:all 0.1s;"
            :style="{ opacity: (draftSaving || submitting) ? 0.6 : 1, cursor: (draftSaving || submitting) ? 'not-allowed' : 'pointer' }"
            @mouseenter="e=>{ if(!draftSaving && !submitting){ e.currentTarget.style.transform='translate(-1px,-1px)'; e.currentTarget.style.boxShadow='3px 3px 0 #1A1100'; } }"
            @mouseleave="e=>{ e.currentTarget.style.transform='none'; e.currentTarget.style.boxShadow='2px 2px 0 #1A1100'; }">
            <svg v-if="!draftSaving" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
            </svg>
            <svg v-else style="animation:spin 0.8s linear infinite;flex-shrink:0;" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
            {{ draftSaving ? '저장 중...' : '임시 저장' }}
          </button>
          <!-- 임시 저장 토스트 메시지 -->
          <transition name="draft-toast">
            <span v-if="draftToast"
              style="font-size:12px;font-weight:600;padding:4px 10px;border-radius:8px;white-space:nowrap;"
              :style="{ background: draftToastOk ? '#D1FAE5' : '#FEE2E2', color: draftToastOk ? '#065F46' : '#991B1B' }">
              {{ draftToast }}
            </span>
          </transition>
        </div>
        <!-- 제출하기 버튼 -->
        <button type="button" @click="submitFinal" :disabled="submitting || form.processing" class="btn-primary"
          :style="{ opacity: (submitting || form.processing) ? 0.7 : 1, cursor: (submitting || form.processing) ? 'not-allowed' : 'pointer' }">
          <svg v-if="!(submitting && submitAction==='submit') && !form.processing" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
          <svg v-else style="animation:spin 0.8s linear infinite;flex-shrink:0;" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
          {{ submitting && submitAction === 'submit' ? '제출 중...' : '제출하기' }}
        </button>
      </div>
    </div>

    <!-- 임시저장 불러오기 배너 -->
    <Transition name="draft-banner">
      <div v-if="hasDraft && !props.existingReport"
        style="display:flex;align-items:center;justify-content:space-between;gap:12px;background:#FFF8EE;border:2px solid #FDCB40;border-radius:12px;padding:10px 16px;margin-bottom:16px;box-shadow:2px 2px 0 #1A1100;">
        <div style="display:flex;align-items:center;gap:8px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#92400E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
          </svg>
          <span style="font-size:13px;font-weight:700;color:#92400E;">임시저장된 내용이 있습니다.</span>
          <span style="font-size:11px;color:#B45309;">불러오기를 클릭하면 저장했던 내용이 채워집니다.</span>
        </div>
        <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
          <button type="button" @click="discardDraft"
            style="background:none;border:1.5px solid #D0C9BC;border-radius:8px;padding:4px 10px;font-size:12px;color:#9A8F7A;cursor:pointer;font-family:inherit;font-weight:600;transition:all 0.1s;"
            @mouseenter="e=>{e.currentTarget.style.borderColor='#DC2626';e.currentTarget.style.color='#DC2626';}"
            @mouseleave="e=>{e.currentTarget.style.borderColor='#D0C9BC';e.currentTarget.style.color='#9A8F7A';}">
            삭제
          </button>
          <button type="button" @click="loadDraft"
            style="background:#FDCB40;color:#1A1100;border:2px solid #1A1100;border-radius:8px;padding:5px 14px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:2px 2px 0 #1A1100;transition:all 0.1s;display:inline-flex;align-items:center;gap:5px;"
            @mouseenter="e=>{e.currentTarget.style.transform='translate(-1px,-1px)';e.currentTarget.style.boxShadow='3px 3px 0 #1A1100';}"
            @mouseleave="e=>{e.currentTarget.style.transform='none';e.currentTarget.style.boxShadow='2px 2px 0 #1A1100';}">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 4v6h6M3.51 15a9 9 0 1 0 .49-4.5"/></svg>
            임시저장 불러오기
          </button>
        </div>
      </div>
    </Transition>

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
          <span style="font-size:11px;color:#9A8F7A;">— 제출 시 팀 일정판에 자동 반영됩니다</span>
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

              <!-- 시간 선택 (라디오) -->
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

              <!-- 상태 (라디오, 단일, 현재 시간대) -->
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

              <!-- 내 사이트 (현재 시간대) -->
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

              <!-- 내용 (전체, 단일 칩) -->
              <div style="background:#F0F9FF;border:1.5px solid #BAE6FD;border-radius:10px;padding:10px 12px;">
                <div style="display:flex;align-items:center;gap:5px;margin-bottom:8px;">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#0369A1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                  </svg>
                  <span style="font-size:11px;color:#0369A1;font-weight:700;letter-spacing:0.03em;">내용</span>
                  <span style="font-size:10px;color:#7DD3FC;">단일 칩으로 표시됩니다</span>
                </div>
                <!-- 미리보기 칩 (하나) -->
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
        <div class="date-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
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

      <!-- 지원: 이번 주 | 다음 주 (2열 → 모바일 1열) -->
      <div class="support-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
        <SupportSection title="이번 주 결과 — 지원" v-model="form.jiWon_curr"
          :canPaste="jiWonClipboard !== null"
          :suggestions="mySites"
          @copy="() => copyJiWon('curr')"
          @paste="() => pasteJiWon('curr')" />
        <SupportSection title="다음 주 계획 — 지원" v-model="form.jiWon_next"
          :canPaste="jiWonClipboard !== null"
          :suggestions="mySites"
          @copy="() => copyJiWon('next')"
          @paste="() => pasteJiWon('next')" />
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

          <!-- todo item 각각 (SupportSection과 동일한 디자인) -->
          <div v-for="(t, idx) in form.todo_items" :key="idx"
            style="border:1.5px solid #E8E0D0;border-radius:10px;padding:10px 12px 8px;background:#FDFAF5;">

            <!-- 번호 + checkbox + textarea + ▼ + × -->
            <div style="display:flex;gap:7px;align-items:center;margin-bottom:8px;">
              <span style="font-size:11px;color:#9A8F7A;font-weight:700;flex-shrink:0;min-width:18px;text-align:right;">{{ idx+1 }}.</span>

              <!-- textarea + ▼ 버튼 묶음 -->
              <div :ref="el => { if (el) todoItemRefs[idx] = el }"
                style="flex:1;display:flex;gap:4px;align-items:center;">
                <!-- 체크박스 -->
                <input type="checkbox" v-model="t.done"
                  style="accent-color:#FD4401;width:15px;height:15px;cursor:pointer;flex-shrink:0;" />
                <!-- 내용 입력 -->
                <textarea v-model="t.content" rows="1"
                  @input="e => { e.target.style.height='auto'; e.target.style.height=e.target.scrollHeight+'px' }"
                  class="input-field todo-textarea"
                  :style="{ textDecoration: t.done ? 'line-through' : 'none', flex:1, resize:'none', overflow:'hidden', minHeight:'36px', lineHeight:'1.55', fontWeight:'700', fontSize:'13px' }"
                  placeholder="항목명" />
                <!-- ▼ 드롭다운 버튼 (mySites 있을 때만) -->
                <button v-if="mySites.length" type="button"
                  @mousedown.prevent="toggleTodoItemDrop(idx)"
                  v-tooltip="'저장된 사이트 목록'"
                  style="flex-shrink:0;background:#F0EBE0;border:1.5px solid #C5BAA8;border-radius:7px;width:28px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all 0.12s;"
                  @mouseenter="e=>{e.currentTarget.style.background='#E8E0D0';e.currentTarget.style.borderColor='#9A8F7A';}"
                  @mouseleave="e=>{e.currentTarget.style.background='#F0EBE0';e.currentTarget.style.borderColor='#C5BAA8';}">
                  <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#6B5E4A" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                    :style="{ transform: todoItemDropIdx === idx ? 'rotate(180deg)' : 'rotate(0deg)', transition:'transform 0.15s' }">
                    <polyline points="6 9 12 15 18 9"/>
                  </svg>
                </button>
              </div>

              <!-- 삭제 버튼 -->
              <button type="button" @click="removeTodo(idx)"
                style="background:none;border:none;cursor:pointer;color:#D0C9BC;padding:4px;border-radius:6px;flex-shrink:0;transition:color 0.1s;"
                @mouseenter="e=>e.currentTarget.style.color='#DC2626'"
                @mouseleave="e=>e.currentTarget.style.color='#D0C9BC'">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
              </button>
            </div>

            <!-- sub_items (내용) -->
            <div style="margin-left:25px;display:flex;flex-direction:column;gap:3px;">
              <div v-for="(sub, sIdx) in (t.sub_items || [])" :key="sIdx"
                style="display:flex;gap:6px;align-items:flex-start;">
                <span style="color:#9A8F7A;font-size:12px;font-weight:700;flex-shrink:0;margin-top:7px;width:10px;">-</span>
                <textarea :value="typeof sub === 'string' ? sub : sub.content"
                  @input="e => { updateTodoSub(idx, sIdx, e.target.value); e.target.style.height='auto'; e.target.style.height=e.target.scrollHeight+'px' }"
                  class="input-field"
                  rows="1"
                  placeholder="내용"
                  style="flex:1;resize:none;overflow:hidden;min-height:32px;font-size:12px;line-height:1.5;padding-top:5px;padding-bottom:5px;color:#4A3F2A;" />
                <button type="button" @click="removeTodoSub(idx, sIdx)"
                  style="background:none;border:none;cursor:pointer;color:#D0C9BC;padding:4px;border-radius:6px;flex-shrink:0;transition:color 0.1s;margin-top:3px;"
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

          <!-- 항목 추가 버튼 — 바로 빈 항목 추가 -->
          <button type="button" @click="addTodo"
            style="display:inline-flex;align-items:center;gap:5px;background:rgba(26,17,0,0.04);border:1.5px dashed #C5BAA8;border-radius:8px;padding:6px 14px;font-size:12px;color:#9A8F7A;cursor:pointer;font-family:inherit;font-weight:600;transition:all 0.1s;align-self:flex-start;"
            @mouseenter="e=>{e.currentTarget.style.borderColor='#FD4401';e.currentTarget.style.color='#FD4401';e.currentTarget.style.background='rgba(253,68,1,0.04)';}"
            @mouseleave="e=>{e.currentTarget.style.borderColor='#C5BAA8';e.currentTarget.style.color='#9A8F7A';e.currentTarget.style.background='rgba(26,17,0,0.04)';}">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
            항목 추가
          </button>
        </div>
      </div>

      <!-- Todo 항목별 ▼ 드롭다운 -->
      <Teleport to="body">
        <div v-if="todoItemDropIdx >= 0 && mySites.length"
          :style="{
            position: 'fixed',
            top:   todoDropPos.top  + 'px',
            left:  todoDropPos.left + 'px',
            width: todoDropPos.width + 'px',
            background: '#fff',
            border: '2px solid #1A1100',
            borderRadius: '10px',
            boxShadow: '4px 4px 0 #1A1100',
            zIndex: 9999,
            display: 'flex',
            flexDirection: 'column',
            maxHeight: '264px',
            fontFamily: '\'Space Grotesk\',\'Noto Sans KR\',sans-serif',
          }">
          <div style="flex-shrink:0;padding:6px 14px;font-size:10px;font-weight:800;color:#9A8F7A;letter-spacing:0.06em;text-transform:uppercase;border-bottom:1.5px solid #F0EBE0;background:#FDFAF5;border-radius:8px 8px 0 0;">
            사이트 선택 <span style="color:#C5BAA8;font-weight:600;">({{ mySites.length }})</span>
          </div>
          <div style="overflow-y:auto;flex:1;border-radius:0 0 8px 8px;">
            <div v-for="(site, si) in mySites" :key="si"
              @mousedown.prevent="selectTodoItemSite(todoItemDropIdx, site)"
              :style="{
                padding: '9px 14px', fontSize: '12px', fontWeight: '600', color: '#1A1100',
                cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '8px',
                background: todoDropHover === si ? '#FFF0A0' : 'transparent', transition: 'background 0.07s',
              }"
              @mouseenter="todoDropHover = si"
              @mouseleave="todoDropHover = -1">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#9A8F7A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
              </svg>
              {{ site }}
            </div>
          </div>
        </div>
      </Teleport>

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
    <!-- 이전 보고서 미리보기 모달 -->
    <div v-if="showPrevModal"
      style="position:fixed;inset:0;background:rgba(26,17,0,0.5);display:flex;align-items:center;justify-content:center;z-index:200;backdrop-filter:blur(4px);padding:16px;"
      @click.self="showPrevModal=false;isFullscreen=false">
      <div class="card" :style="isFullscreen
        ? 'position:fixed;inset:16px;width:auto;max-width:none;max-height:none;display:flex;flex-direction:column;padding:0;overflow:hidden;z-index:201;border-radius:12px;'
        : 'width:92vw;max-width:1200px;max-height:90vh;display:flex;flex-direction:column;padding:0;overflow:hidden;'">
        <!-- 모달 헤더 -->
        <div style="padding:18px 22px;border-bottom:2px solid #1A1100;display:flex;justify-content:space-between;align-items:center;background:#F5EDDB;flex-shrink:0;">
          <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:15px;font-weight:800;">이전 보고서 불러오기</div>
          <div style="display:flex;gap:4px;align-items:center;">
            <!-- 전체화면 토글 -->
            <button type="button" @click="isFullscreen=!isFullscreen"
              v-tooltip="isFullscreen ? '작게 보기' : '전체 화면'"
              style="background:none;border:none;cursor:pointer;color:#9A8F7A;padding:4px;border-radius:6px;"
              @mouseenter="e=>e.currentTarget.style.color='#1A1100'"
              @mouseleave="e=>e.currentTarget.style.color='#9A8F7A'">
              <!-- 전체화면 아이콘 -->
              <svg v-if="!isFullscreen" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M8 3H5a2 2 0 0 0-2 2v3M21 8V5a2 2 0 0 0-2-2h-3M3 16v3a2 2 0 0 0 2 2h3M16 21h3a2 2 0 0 0 2-2v-3"/>
              </svg>
              <!-- 축소 아이콘 -->
              <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M8 3v3a2 2 0 0 1-2 2H3M21 8h-3a2 2 0 0 1-2-2V3M3 16h3a2 2 0 0 1 2 2v3M16 21v-3a2 2 0 0 1 2-2h3"/>
              </svg>
            </button>
            <!-- 닫기 -->
            <button type="button" @click="showPrevModal=false"
              style="background:none;border:none;cursor:pointer;color:#9A8F7A;padding:4px;border-radius:6px;"
              @mouseenter="e=>e.currentTarget.style.color='#DC2626'"
              @mouseleave="e=>e.currentTarget.style.color='#9A8F7A'">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
          </div>
        </div>

        <!-- 보고서 목록 + 미리보기 -->
        <div style="display:flex;flex:1;overflow:hidden;min-height:0;">
          <!-- 왼쪽: 보고서 목록 -->
          <div :style="isFullscreen ? 'width:240px;border-right:2px solid #1A1100;overflow-y:auto;flex-shrink:0;' : 'width:200px;border-right:2px solid #1A1100;overflow-y:auto;flex-shrink:0;'">
            <div v-for="r in prevReports" :key="r.id"
              @click="selectPrev(r)"
              style="padding:12px 14px;cursor:pointer;border-bottom:1.5px solid #1A1100;transition:background 0.1s;"
              :style="{ background: previewReport?.id===r.id ? '#FFF0A0' : 'transparent' }"
              @mouseenter="e=>{ if(previewReport?.id!==r.id) e.currentTarget.style.background='#FFF8EE'; }"
              @mouseleave="e=>{ if(previewReport?.id!==r.id) e.currentTarget.style.background='transparent'; }">
              <div :style="isFullscreen ? 'font-size:15px;font-weight:800;color:#1A1100;font-family:Space Grotesk,Noto Sans KR,sans-serif;' : 'font-size:13px;font-weight:800;color:#1A1100;font-family:Space Grotesk,Noto Sans KR,sans-serif;'">{{ r.label }}</div>
              <div :style="isFullscreen ? 'font-size:12px;color:#9A8F7A;margin-top:4px;' : 'font-size:11px;color:#9A8F7A;margin-top:3px;'">{{ fmtShort(r.curr_start) }} ~ {{ fmtShort(r.curr_end) }}</div>
            </div>
          </div>

          <!-- 오른쪽: 미리보기 (Excel 테이블 형식) -->
          <div style="flex:1;overflow-y:auto;display:flex;flex-direction:column;">
            <div v-if="!previewReport" style="flex:1;display:flex;align-items:center;justify-content:center;color:#9A8F7A;font-size:13px;">
              왼쪽에서 보고서를 선택하세요
            </div>
            <div v-else-if="previewLoading" style="flex:1;display:flex;align-items:center;justify-content:center;color:#9A8F7A;font-size:13px;">
              불러오는 중...
            </div>
            <div v-else-if="previewError" style="flex:1;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:8px;color:#B91C1C;font-size:13px;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              보고서를 불러오지 못했습니다. 다시 시도해 주세요.
            </div>
            <div v-else-if="!previewData" style="flex:1;display:flex;align-items:center;justify-content:center;color:#9A8F7A;font-size:13px;">
              데이터를 불러올 수 없습니다.
            </div>
            <table v-else :style="isFullscreen
              ? 'width:100%;border-collapse:collapse;font-size:13.5px;table-layout:fixed;flex:1;'
              : 'width:100%;border-collapse:collapse;font-size:12px;table-layout:fixed;flex:1;'">
              <colgroup>
                <col :style="isFullscreen ? 'width:72px;' : 'width:60px;'" />
                <col style="width:50%;" />
                <col style="width:50%;" />
              </colgroup>
              <thead>
                <tr style="background:#F5EDDB;border-bottom:1.5px solid #1A1100;">
                  <th style="padding:7px 6px;text-align:center;font-size:10px;font-weight:700;color:#9A8F7A;border-right:2px solid #1A1100;">구분</th>
                  <th style="padding:7px 10px;text-align:center;font-size:10px;font-weight:700;border-right:2px solid #1A1100;color:#1A1100;">전주 업무</th>
                  <th style="padding:7px 10px;text-align:center;font-size:10px;font-weight:700;color:#1A1100;">금주 업무</th>
                </tr>
              </thead>
              <tbody>
                <!-- 지원: 전주 | 금주 2열 -->
                <tr style="border-bottom:1.5px solid #1A1100;">
                  <td style="padding:8px 6px;text-align:center;font-size:10px;font-weight:700;background:#F5EDDB;border-right:2px solid #1A1100;vertical-align:top;white-space:nowrap;">지원</td>
                  <td style="padding:8px 10px;vertical-align:top;border-right:2px solid #1A1100;word-break:break-word;">
                    <template v-if="previewCurr['지원']?.length">
                      <div v-for="(item, i) in previewCurr['지원']" :key="i" style="margin-bottom:8px;">
                        <div style="display:flex;gap:5px;"><span style="color:#9A8F7A;flex-shrink:0;font-weight:700;">{{ i+1 }}.</span><span style="color:#1A1100;line-height:1.5;font-weight:700;">{{ item.title || item.content }}</span></div>
                        <div v-if="item.sub_items?.length" style="margin-left:14px;margin-top:3px;">
                          <div v-for="(sub, si) in item.sub_items" :key="si" style="display:flex;gap:5px;"><span style="color:#9A8F7A;flex-shrink:0;font-size:11px;">-</span><span style="color:#4A3F2A;font-size:11px;line-height:1.5;white-space:pre-wrap;" v-html="autoLink(sub)"></span></div>
                        </div>
                      </div>
                    </template>
                    <span v-else style="color:#D0C9BC;">-</span>
                  </td>
                  <td style="padding:8px 10px;vertical-align:top;word-break:break-word;">
                    <template v-if="previewNext['지원']?.length">
                      <div v-for="(item, i) in previewNext['지원']" :key="i" style="margin-bottom:8px;">
                        <div style="display:flex;gap:5px;"><span style="color:#9A8F7A;flex-shrink:0;font-weight:700;">{{ i+1 }}.</span><span style="color:#1A1100;line-height:1.5;font-weight:700;">{{ item.title || item.content }}</span></div>
                        <div v-if="item.sub_items?.length" style="margin-left:14px;margin-top:3px;">
                          <div v-for="(sub, si) in item.sub_items" :key="si" style="display:flex;gap:5px;"><span style="color:#9A8F7A;flex-shrink:0;font-size:11px;">-</span><span style="color:#4A3F2A;font-size:11px;line-height:1.5;white-space:pre-wrap;" v-html="autoLink(sub)"></span></div>
                        </div>
                      </div>
                    </template>
                    <span v-else style="color:#D0C9BC;">-</span>
                  </td>
                </tr>

                <!-- 내부작업 -->
                <tr style="border-bottom:1.5px solid #1A1100;">
                  <td style="padding:8px 6px;text-align:center;font-size:10px;font-weight:700;background:#F5EDDB;border-right:2px solid #1A1100;vertical-align:top;white-space:nowrap;">내부작업</td>
                  <td colspan="2" style="padding:8px 10px;vertical-align:top;word-break:break-word;">
                    <template v-if="previewCurr['내부작업']?.length">
                      <div v-for="(item, i) in previewCurr['내부작업']" :key="i" style="margin-bottom:8px;">
                        <div style="display:flex;gap:5px;"><span style="color:#9A8F7A;flex-shrink:0;font-weight:700;">{{ i+1 }}.</span><span style="color:#1A1100;line-height:1.5;font-weight:700;">{{ item.title || item.content }}</span></div>
                        <div v-if="item.sub_items?.length" style="margin-left:14px;margin-top:3px;">
                          <div v-for="(sub, si) in item.sub_items" :key="si" style="display:flex;gap:5px;"><span style="color:#9A8F7A;flex-shrink:0;font-size:11px;">-</span><span style="color:#4A3F2A;font-size:11px;line-height:1.5;white-space:pre-wrap;" v-html="autoLink(sub)"></span></div>
                        </div>
                      </div>
                    </template>
                    <span v-else style="color:#D0C9BC;">-</span>
                  </td>
                </tr>

                <!-- Todo -->
                <tr style="border-bottom:1.5px solid #1A1100;">
                  <td style="padding:8px 6px;text-align:center;font-size:10px;font-weight:700;background:#F5EDDB;border-right:2px solid #1A1100;vertical-align:top;">Todo</td>
                  <td colspan="2" style="padding:8px 10px;vertical-align:top;">
                    <template v-if="previewData?.todo_items?.length">
                      <div v-for="(t, i) in previewData.todo_items" :key="i" style="display:flex;gap:6px;margin-bottom:3px;align-items:flex-start;">
                        <span style="flex-shrink:0;" :style="{ color: t.done ? '#16A34A' : '#9A8F7A' }">{{ t.done ? '☑' : '☐' }}</span>
                        <span style="word-break:break-word;" :style="{ textDecoration: t.done ? 'line-through' : 'none', color: t.done ? '#9A8F7A' : '#1A1100' }">{{ t.content }}</span>
                      </div>
                    </template>
                    <span v-else style="color:#D0C9BC;">-</span>
                  </td>
                </tr>

                <!-- 공유 -->
                <tr style="border-bottom:1.5px solid #1A1100;">
                  <td style="padding:8px 6px;text-align:center;font-size:10px;font-weight:700;background:#F5EDDB;border-right:2px solid #1A1100;vertical-align:top;white-space:nowrap;">공유</td>
                  <td colspan="2" style="padding:8px 10px;vertical-align:top;word-break:break-word;">
                    <template v-if="previewCurr['공유']?.length">
                      <div v-for="(item, i) in previewCurr['공유']" :key="i" style="margin-bottom:8px;">
                        <div style="display:flex;gap:5px;"><span style="color:#9A8F7A;flex-shrink:0;font-weight:700;">{{ i+1 }}.</span><span style="color:#1A1100;line-height:1.5;font-weight:700;">{{ item.title || item.content }}</span></div>
                        <div v-if="item.sub_items?.length" style="margin-left:14px;margin-top:3px;">
                          <div v-for="(sub, si) in item.sub_items" :key="si" style="display:flex;gap:5px;"><span style="color:#9A8F7A;flex-shrink:0;font-size:11px;">-</span><span style="color:#4A3F2A;font-size:11px;line-height:1.5;white-space:pre-wrap;" v-html="autoLink(sub)"></span></div>
                        </div>
                      </div>
                    </template>
                    <span v-else style="color:#D0C9BC;">-</span>
                  </td>
                </tr>

                <!-- 기타 -->
                <tr style="border-bottom:1.5px solid #1A1100;">
                  <td style="padding:8px 6px;text-align:center;font-size:10px;font-weight:700;background:#F5EDDB;border-right:2px solid #1A1100;vertical-align:top;white-space:nowrap;">기타</td>
                  <td colspan="2" style="padding:8px 10px;vertical-align:top;word-break:break-word;">
                    <template v-if="previewCurr['기타']?.length">
                      <div v-for="(item, i) in previewCurr['기타']" :key="i" style="margin-bottom:8px;">
                        <div style="display:flex;gap:5px;"><span style="color:#9A8F7A;flex-shrink:0;font-weight:700;">{{ i+1 }}.</span><span style="color:#1A1100;line-height:1.5;font-weight:700;">{{ item.title || item.content }}</span></div>
                        <div v-if="item.sub_items?.length" style="margin-left:14px;margin-top:3px;">
                          <div v-for="(sub, si) in item.sub_items" :key="si" style="display:flex;gap:5px;"><span style="color:#9A8F7A;flex-shrink:0;font-size:11px;">-</span><span style="color:#4A3F2A;font-size:11px;line-height:1.5;white-space:pre-wrap;" v-html="autoLink(sub)"></span></div>
                        </div>
                      </div>
                    </template>
                    <span v-else style="color:#D0C9BC;">-</span>
                  </td>
                </tr>

                <!-- 특이사항 -->
                <tr v-if="previewData?.notes">
                  <td style="padding:8px 6px;text-align:center;font-size:10px;font-weight:700;background:#F5EDDB;border-right:2px solid #1A1100;vertical-align:top;">특이사항</td>
                  <td colspan="2" style="padding:8px 10px;white-space:pre-wrap;color:#1A1100;word-break:break-word;">{{ previewData.notes }}</td>
                </tr>
                <!-- 남은 공간 채우기 -->
                <tr style="height:100%;"><td colspan="3" style="border:none;"></td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- 모달 푸터 -->
        <div v-if="previewData" style="padding:14px 22px;border-top:2px solid #1A1100;display:flex;justify-content:flex-end;gap:8px;background:#F5EDDB;flex-shrink:0;">
          <button type="button" @click="showPrevModal=false" class="btn-secondary btn-sm">취소</button>
          <button type="button" @click="applyPrev" class="btn-primary btn-sm">이 내용으로 불러오기</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.sched-modal-fade-enter-active, .sched-modal-fade-leave-active { transition: all 0.2s ease; }
.sched-modal-fade-enter-from, .sched-modal-fade-leave-to { opacity: 0; transform: scale(0.97); }

/* 제출 오버레이 트랜지션 */
.submit-overlay-enter-active { transition: opacity 0.2s ease; }
.submit-overlay-leave-active  { transition: opacity 0.15s ease; }
.submit-overlay-enter-from, .submit-overlay-leave-to { opacity: 0; }

/* 스피너 회전 */
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.draft-toast-enter-active, .draft-toast-leave-active { transition: opacity 0.3s, transform 0.3s; }
.draft-toast-enter-from, .draft-toast-leave-to { opacity: 0; transform: translateY(-4px); }
.draft-banner-enter-active, .draft-banner-leave-active { transition: opacity 0.25s, transform 0.25s; }
.draft-banner-enter-from, .draft-banner-leave-to { opacity: 0; transform: translateY(-8px); }

/* ===========================
   보고서 작성 반응형
   =========================== */
@media (max-width: 768px) {
  /* 보고 기간 4열 → 2열 */
  .date-grid { grid-template-columns: repeat(2, 1fr) !important; }

  /* 지원 이번주/다음주 2열 → 1열 */
  .support-grid { grid-template-columns: 1fr !important; }
}
</style>

<script setup>
import { ref, reactive, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SupportSection from '@/Components/SupportSection.vue'
import { autoLink } from '@/utils/autoLink.js'

// 수정 후 돌아올 때 기존 데이터 높이 초기화
onMounted(() => {
  nextTick(() => {
    document.querySelectorAll('.auto-resize-ta').forEach(el => {
      el.style.height = 'auto'
      el.style.height = el.scrollHeight + 'px'
    })
  })
})

const props = defineProps({
  weekInfo:       { type: Object, default: () => ({}) },
  prevReports:    { type: Array,  default: () => [] },
  existingReport: { type: Object, default: null },
  mySchedules:    { type: Object, default: () => ({}) },
  mySites:        { type: Array,  default: () => [] },
})

// 중복 보고서 팝업 (제출 시점에만 표시)
const showDuplicateAlert = ref(false)

// ── 내 주간 일정 ──────────────────────────────────────
const showSchedule = ref(false)
const schedules    = ref({ ...props.mySchedules })

const fmtLocalDate = (d) =>
  `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`

const currDates = computed(() => {
  if (!form.curr_start) return []
  return Array.from({ length: 5 }, (_, i) => {
    const d = new Date(form.curr_start + 'T00:00:00')
    d.setDate(d.getDate() + i)
    return fmtLocalDate(d)
  })
})
const nextDates = computed(() => {
  if (!form.next_start) return []
  return Array.from({ length: 5 }, (_, i) => {
    const d = new Date(form.next_start + 'T00:00:00')
    d.setDate(d.getDate() + i)
    return fmtLocalDate(d)
  })
})
const fmtDay = (d) => {
  if (!d) return ''
  const dt   = new Date(d + 'T00:00:00')
  const days = ['일', '월', '화', '수', '목', '금', '토']
  const mm   = String(dt.getMonth() + 1).padStart(2, '0')
  const dd   = String(dt.getDate()).padStart(2, '0')
  return `${mm}/${dd}(${days[dt.getDay()]})`
}
const fmtDateOnly = (d) => {
  if (!d) return ''
  const dt = new Date(d + 'T00:00:00')
  return `${String(dt.getMonth() + 1).padStart(2, '0')}/${String(dt.getDate()).padStart(2, '0')}`
}
// ─────────────────────────────────────────────────────

// ── 일정 입력 모달 ────────────────────────────────────
const SCHED_QUICK_TAGS = [
  { label: '외근', icon: '🏢', bg: '#DBEAFE', color: '#1D4ED8', border: '#93C5FD' },
  { label: '출장', icon: '✈️', bg: '#EDE9FE', color: '#7C3AED', border: '#C4B5FD' },
  { label: '반차', icon: '🕐', bg: '#FEF9C3', color: '#854D0E', border: '#FDE68A' },
  { label: '휴가', icon: '🌴', bg: '#DCFCE7', color: '#166534', border: '#86EFAC' },
]
const SCHED_STATUS_LABELS = SCHED_QUICK_TAGS.map(t => t.label)
const SCHED_STATUS_MAP    = Object.fromEntries(SCHED_QUICK_TAGS.map(t => [t.label, t]))
const DAY_KR_LIST = ['일', '월', '화', '수', '목', '금', '토']

// 셀 파싱 — 신규 포맷: [시간]상태:사이트 + 구버전 호환
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
    // 구버전: 첫 줄 상태/사이트 → 종일 슬롯으로
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
const schedModalTime    = ref('종일')   // 현재 편집 중인 시간대
const schedModalContent = ref('')       // 내용 (단일)
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

// 상태 단일 선택 (현재 시간대)
const toggleSchedStatus = (label) => {
  const slot = schedModalAllSlots[schedModalTime.value]
  slot.status = slot.status === label ? '' : label
}
// 사이트 토글 (현재 시간대)
const toggleSchedSite = (site) => {
  const slot = schedModalAllSlots[schedModalTime.value]
  const idx  = slot.sites.indexOf(site)
  if (idx === -1) slot.sites.push(site)
  else            slot.sites.splice(idx, 1)
}
// ─────────────────────────────────────────────────────

// title 우선, 구버전 데이터는 content를 title로 마이그레이션
const splitByCat = (arr, cat) =>
  (arr || []).filter(i => i.category === cat && (i.title || i.content || '').trim())
             .map(i => ({ title: i.title || i.content || '', sub_items: i.sub_items || [] }))

const form = useForm({
  week:       props.weekInfo?.week       ?? '',
  curr_start: props.weekInfo?.curr_start ?? '',
  curr_end:   props.weekInfo?.curr_end   ?? '',
  next_start: props.weekInfo?.next_start ?? '',
  next_end:   props.weekInfo?.next_end   ?? '',
  jiWon_curr: [],   // 지원 이번 주
  jiWon_next: [],   // 지원 다음 주
  naebu:      [],   // 내부작업
  gongyu:     [],   // 공유
  gita:       [],   // 기타
  todo_items: [],
  notes:      '',
  requests:   '',
})

const fmtShort = (d) => d ? String(d).substring(5, 10).replace('-', '/') : '-'

// ── localStorage 자동 저장/복원 ──────────────────────────
const LS_KEY = 'weeklyrpt_create_draft'

const formFields = ['week','curr_start','curr_end','next_start','next_end',
  'jiWon_curr','jiWon_next','naebu','gongyu','gita','todo_items','notes','requests']

const saveToLocal = () => {
  if (props.existingReport) return   // 중복 보고서 있으면 저장 안함
  const snapshot = {}
  formFields.forEach(k => { snapshot[k] = form[k] })
  snapshot.__draftId = draftId.value
  try { localStorage.setItem(LS_KEY, JSON.stringify(snapshot)) } catch {}
}

const clearLocal = () => {
  try { localStorage.removeItem(LS_KEY) } catch {}
  hasDraft.value = false
}

// 임시저장 존재 여부 확인 (주차 만료 체크 포함)
const hasDraft = ref(false)
// 마운트 시 발견됐지만 아직 불러오기/삭제하지 않은 임시저장본이 있는지.
// true인 동안에는 자동저장이 localStorage를 덮어쓰지 않게 막아
// 사용자가 불러오기 전에 폼을 건드려도 저장본이 유실되지 않도록 한다.
const unloadedDraft = ref(false)

const checkLocalDraft = () => {
  if (props.existingReport) { hasDraft.value = false; unloadedDraft.value = false; return }
  try {
    const raw = localStorage.getItem(LS_KEY)
    if (!raw) { hasDraft.value = false; unloadedDraft.value = false; return }
    const saved = JSON.parse(raw)
    // 주차가 다르면 만료 처리
    if (saved.week && form.week && saved.week !== form.week) {
      clearLocal(); unloadedDraft.value = false; return
    }
    hasDraft.value = !!(saved.__draftId || saved.week)
    unloadedDraft.value = hasDraft.value
  } catch { hasDraft.value = false; unloadedDraft.value = false }
}

// 임시저장 불러오기 (수동)
const restoreFromLocal = () => {
  if (props.existingReport) return
  try {
    const raw = localStorage.getItem(LS_KEY)
    if (!raw) return
    const saved = JSON.parse(raw)
    if (saved.week && form.week && saved.week !== form.week) { clearLocal(); return }
    formFields.forEach(k => { if (saved[k] !== undefined) form[k] = saved[k] })
    if (saved.__draftId) draftId.value = saved.__draftId
    nextTick(() => {
      requestAnimationFrame(() => {
        document.querySelectorAll('.todo-textarea, .auto-resize-ta').forEach(el => {
          el.style.height = 'auto'; el.style.height = el.scrollHeight + 'px'
        })
      })
    })
  } catch { clearLocal() }
}

const loadDraft = () => {
  restoreFromLocal()
  hasDraft.value = false
  unloadedDraft.value = false   // 불러왔으니 이후 자동저장 재개
  showDraftToast('임시저장 내용을 불러왔습니다.', true)
}

const discardDraft = () => {
  clearLocal()
  draftId.value = null
  unloadedDraft.value = false   // 삭제했으니 이후 자동저장 재개
}

// ── Todo 항목별 ▼ 드롭다운 ────────────────────────────
const todoItemRefs    = ref({})    // 개별 항목 container ref
const todoItemDropIdx = ref(-1)    // 열려있는 항목 인덱스 (-1 = 닫힘)
const todoDropHover   = ref(-1)
const todoDropPos     = ref({ top: 0, left: 0, width: 220 })

const calcTodoDropPos = (el) => {
  if (!el) return
  const r = el.getBoundingClientRect()
  todoDropPos.value = { top: r.bottom + 4, left: r.left, width: Math.max(r.width, 200) }
}

const toggleTodoItemDrop = (idx) => {
  todoDropHover.value = -1
  if (todoItemDropIdx.value === idx) { todoItemDropIdx.value = -1; return }
  todoItemDropIdx.value = idx
  nextTick(() => calcTodoDropPos(todoItemRefs.value[idx]))
}

const selectTodoItemSite = (idx, site) => {
  form.todo_items[idx].content = site
  todoItemDropIdx.value = -1
}

// 스크롤 시 위치 재계산
const onTodoScroll = () => {
  if (todoItemDropIdx.value >= 0) calcTodoDropPos(todoItemRefs.value[todoItemDropIdx.value])
}

// 바깥 클릭 닫기
const onTodoDocClick = (e) => {
  const inItemRef = Object.values(todoItemRefs.value).some(el => el?.contains(e.target))
  if (!inItemRef) todoItemDropIdx.value = -1
}

onMounted(() => {
  document.addEventListener('mousedown', onTodoDocClick)
  window.addEventListener('scroll', onTodoScroll, true)
  checkLocalDraft()  // auto-restore 대신 배너만 표시
})
onBeforeUnmount(() => {
  document.removeEventListener('mousedown', onTodoDocClick)
  window.removeEventListener('scroll', onTodoScroll, true)
})

// 폼 변경될 때마다 localStorage에 자동 저장 (500ms 디바운스)
let localSaveTimer = null
watch(
  () => formFields.map(k => form[k]),
  () => {
    // 아직 불러오지 않은 임시저장본이 배너에 떠 있는 동안에는 자동저장으로
    // 덮어쓰지 않는다 (불러오기 전 폼 편집으로 저장본이 유실되는 것 방지)
    if (unloadedDraft.value) return
    clearTimeout(localSaveTimer)
    localSaveTimer = setTimeout(saveToLocal, 500)
  },
  { deep: true }
)

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
const submitting   = ref(false)
const submitStep   = ref(0)       // 0: 대기, 1: 일정 저장 중, 2: 보고서 저장/제출 중
const submitAction = ref('draft') // 'draft' | 'submit'

// ── 임시 저장 상태 ──
const draftId      = ref(null)    // 저장된 draft report id
const draftSaving  = ref(false)
const draftToast   = ref('')      // 토스트 메시지
const draftToastOk = ref(true)    // true=성공, false=실패
let draftToastTimer = null

const showDraftToast = (msg, ok = true) => {
  draftToast.value   = msg
  draftToastOk.value = ok
  clearTimeout(draftToastTimer)
  draftToastTimer = setTimeout(() => { draftToast.value = '' }, 3000)
}

// 공통 페이로드 빌더
const buildPayload = (action = 'draft') => ({
  week:       form.week,
  curr_start: form.curr_start,
  curr_end:   form.curr_end,
  next_start: form.next_start,
  next_end:   form.next_end,
  curr_work: [
    ...form.jiWon_curr.map(i => ({ title: i.title, content: i.title, category: '지원',    sub_items: i.sub_items })),
    ...form.naebu.map(i      => ({ title: i.title, content: i.title, category: '내부작업', sub_items: i.sub_items })),
    ...form.gongyu.map(i     => ({ title: i.title, content: i.title, category: '공유',    sub_items: i.sub_items })),
    ...form.gita.map(i       => ({ title: i.title, content: i.title, category: '기타',    sub_items: i.sub_items })),
  ],
  next_plan:  form.jiWon_next.map(i => ({ title: i.title, content: i.title, category: '지원', sub_items: i.sub_items })),
  todo_items: form.todo_items,
  notes:      form.notes,
  requests:   form.requests,
  action,
})

// 일정 저장 헬퍼
const saveSchedules = async () => {
  const allDates = [...currDates.value, ...nextDates.value]
  if (!allDates.length) return
  try {
    await Promise.all(
      allDates.map(date =>
        window.axios.post('/schedules/upsert', { date, content: schedules.value[date] || null })
      )
    )
  } catch (e) {
    console.warn('일정 저장 실패 (보고서 저장은 계속됩니다)', e)
  }
}

// ── 임시 저장 (AJAX, 페이지 이동 없음) ──
const saveDraft = async () => {
  if (props.existingReport) { showDuplicateAlert.value = true; return }
  if (draftSaving.value) return

  draftSaving.value = true
  await saveSchedules()

  try {
    const payload = buildPayload('draft')
    let res
    if (draftId.value) {
      try {
        res = await window.axios.patch(`/reports/${draftId.value}/draft`, payload)
      } catch (patchErr) {
        // 404: 서버에 해당 draft 없음 → draftId 초기화 후 새로 생성
        if (patchErr.response?.status === 404) {
          draftId.value = null
          res = await window.axios.post('/reports/draft', payload)
          draftId.value = res.data.id
        } else {
          throw patchErr
        }
      }
    } else {
      res = await window.axios.post('/reports/draft', payload)
      draftId.value = res.data.id
    }
    unloadedDraft.value = false   // 명시적 저장이 현재 폼 내용으로 갱신했으므로 가드 해제
    saveToLocal()   // draftId 포함해서 즉시 저장
    hasDraft.value = true
    showDraftToast('임시 저장되었습니다.', true)
  } catch (e) {
    const msg = e.response?.data?.message || '저장에 실패했습니다.'
    showDraftToast(msg, false)
    console.error('임시 저장 실패:', e)
  } finally {
    draftSaving.value = false
  }
}

// ── 최종 제출 ──
const submitFinal = async () => {
  if (props.existingReport) { showDuplicateAlert.value = true; return }
  if (submitting.value) return

  submitAction.value = 'submit'
  submitting.value   = true
  submitStep.value   = 1

  await saveSchedules()
  submitStep.value = 2

  // draft로 저장된 게 있으면 → update 후 submit 엔드포인트 사용
  if (draftId.value) {
    try {
      try {
        await window.axios.patch(`/reports/${draftId.value}/draft`, buildPayload('draft'))
      } catch (patchErr) {
        // 404: 서버에 해당 draft 없음 → draftId 초기화 후 새 draft로 생성
        if (patchErr.response?.status === 404) {
          const newRes = await window.axios.post('/reports/draft', buildPayload('draft'))
          draftId.value = newRes.data.id
        } else {
          throw patchErr
        }
      }
      form.post(`/reports/${draftId.value}/submit`, {
        onSuccess: () => { clearLocal() },
        onError:  () => { submitting.value = false; submitStep.value = 0 },
        onFinish: () => { submitting.value = false; submitStep.value = 0 },
      })
    } catch (e) {
      submitting.value = false; submitStep.value = 0
      console.error('제출 실패:', e)
    }
    return
  }

  // draft 없으면 → 기존 create+submit 흐름
  form.transform(data => buildPayload('submit')).post('/reports', {
    onSuccess: () => { clearLocal() },
    onError:  () => { submitting.value = false; submitStep.value = 0 },
    onFinish: () => { submitting.value = false; submitStep.value = 0 },
  })
}

// form @submit.prevent 호환용
const submit = submitFinal

// 지원 항목 복사/붙여넣기 클립보드 (이번 주 ↔ 다음 주 양방향)
const jiWonClipboard = ref(null)

const copyJiWon = (from) => {
  const src = from === 'curr' ? form.jiWon_curr : form.jiWon_next
  jiWonClipboard.value = JSON.parse(JSON.stringify(src))
}

const pasteJiWon = (to) => {
  if (!jiWonClipboard.value) return
  if (to === 'curr') form.jiWon_curr = JSON.parse(JSON.stringify(jiWonClipboard.value))
  else               form.jiWon_next = JSON.parse(JSON.stringify(jiWonClipboard.value))
}

// 이전 보고서 미리보기 모달
const showPrevModal  = ref(false)
const isFullscreen   = ref(false)
const previewReport  = ref(null)   // 선택된 보고서 메타
const previewData    = ref(null)   // 로드된 보고서 데이터
const previewLoading = ref(false)

const previewError   = ref(false)

const selectPrev = async (r) => {
  if (previewReport.value?.id === r.id) return
  previewReport.value  = r
  previewData.value    = null
  previewError.value   = false
  previewLoading.value = true
  try {
    const res = await window.axios.get(`/reports/${r.id}/load`)
    previewData.value = res.data
  } catch (e) {
    console.error('이전 보고서 로드 실패:', e)
    previewError.value = true
  } finally {
    previewLoading.value = false
  }
}

// 미리보기 테이블용 카테고리 grouping
const previewCurr = computed(() => {
  const map = {}
  for (const item of (previewData.value?.curr_work ?? [])) {
    const cat = item.category ?? '기타'
    if (!map[cat]) map[cat] = []
    map[cat].push(item)
  }
  return map
})
const previewNext = computed(() => {
  const map = {}
  for (const item of (previewData.value?.next_plan ?? [])) {
    const cat = item.category ?? '기타'
    if (!map[cat]) map[cat] = []
    map[cat].push(item)
  }
  return map
})

const applyPrev = async () => {
  if (!previewData.value) return
  const r = previewData.value
  // 이전 보고서의 '금주 업무(next_plan)' → 새 보고서 '전주 업무(jiWon_curr)'
  form.jiWon_curr = splitByCat(r.next_plan, '지원')
  // 다음 주 계획은 비워서 새로 작성하도록
  form.jiWon_next = []
  // 내부작업·공유·기타는 이전 curr_work에서 참고용으로 불러옴
  form.naebu      = splitByCat(r.curr_work, '내부작업')
  form.gongyu     = splitByCat(r.curr_work, '공유')
  form.gita       = splitByCat(r.curr_work, '기타')
  // 미완료 Todo만 이어받기
  if (r.todo_items) form.todo_items = r.todo_items
    .filter(t => !t.done && (t.content || '').trim())
    .map(t => ({ ...t, done: false }))
  showPrevModal.value = false
  previewReport.value = null
  previewData.value   = null

  // 데이터 채운 뒤 모든 textarea 높이 재계산 (불러오기 시 잘림 방지)
  const resizeAll = () => {
    document.querySelectorAll('.todo-textarea, .auto-resize-ta').forEach(el => {
      el.style.height = 'auto'
      el.style.height = el.scrollHeight + 'px'
    })
  }
  await nextTick()
  resizeAll()
  requestAnimationFrame(() => {
    resizeAll()
    setTimeout(resizeAll, 300)
  })
}
</script>
