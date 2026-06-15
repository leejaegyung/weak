<template>
  <AppLayout page-title="팀 일정판">
    <!-- 헤더 -->
    <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
      <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
          <div style="background:#FDCB40;border:2px solid #1A1100;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M8 2v3M16 2v3M3.5 9.5h17M3 6.5h18a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V7.5a1 1 0 0 1 1-1z"/>
            </svg>
          </div>
          <div>
            <h1 style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:26px;font-weight:700;letter-spacing:-0.03em;">팀 주간 일정판</h1>
            <p style="color:#9A8F7A;font-size:13px;">
              금주 {{ fmtRange(currDates[0], currDates[4]) }} &nbsp;·&nbsp; 차주 {{ fmtRange(nextDates[0], nextDates[4]) }}
            </p>
          </div>
        </div>
      </div>

      <!-- 주차 네비게이션 + 일정 추가 버튼 -->
      <div style="display:flex;gap:8px;align-items:center;">
        <Link :href="`/schedules?week=${prevWeek}`" class="btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:5px;">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
          이전 주
        </Link>
        <Link v-if="!isCurrentWeek" href="/schedules"
          style="background:#FD4401;color:#fff;border:2px solid #1A1100;border-radius:8px;padding:5px 14px;font-size:12px;font-weight:700;display:flex;align-items:center;text-decoration:none;">
          오늘
        </Link>
        <div v-else
          style="background:#FD4401;color:#fff;border:2px solid #1A1100;border-radius:8px;padding:5px 14px;font-size:12px;font-weight:700;">
          이번 주
        </div>
        <Link :href="`/schedules?week=${nextWeek}`" class="btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:5px;">
          다음 주
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
        </Link>

        <!-- 주간/월간 전환 버튼 -->
        <div style="display:flex;background:#F5EDDB;border:2px solid #1A1100;border-radius:10px;overflow:hidden;">
          <button type="button" @click="viewMode = 'week'"
            :style="{ background: viewMode === 'week' ? '#FDCB40' : 'transparent', color:'#1A1100', border:'none', padding:'6px 14px', fontSize:'12px', fontWeight:'700', cursor:'pointer', fontFamily:'inherit', display:'flex', alignItems:'center', gap:'5px' }">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9h18M3 15h18M9 3v18M15 3v18"/></svg>
            주간
          </button>
          <button type="button" @click="switchToMonth"
            :style="{ background: viewMode === 'month' ? '#FDCB40' : 'transparent', color:'#1A1100', border:'none', padding:'6px 14px', fontSize:'12px', fontWeight:'700', cursor:'pointer', fontFamily:'inherit', display:'flex', alignItems:'center', gap:'5px' }">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            월간
          </button>
        </div>

        <!-- 일정 추가 버튼 -->
        <button type="button" @click="openModal(null, '')"
          style="display:inline-flex;align-items:center;gap:6px;background:#FDCB40;color:#1A1100;border:2px solid #1A1100;border-radius:10px;padding:7px 14px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:2px 2px 0 #1A1100;transition:all 0.1s;"
          @mouseenter="e=>{e.currentTarget.style.transform='translate(-1px,-1px)';e.currentTarget.style.boxShadow='3px 3px 0 #1A1100';}"
          @mouseleave="e=>{e.currentTarget.style.transform='none';e.currentTarget.style.boxShadow='2px 2px 0 #1A1100';}">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
          일정 추가
        </button>
      </div>
    </div>

    <!-- 안내 -->
    <p style="font-size:12px;color:#9A8F7A;margin-bottom:12px;">
      본인 일정 셀을 클릭하거나 <strong style="color:#1A1100;">일정 추가</strong> 버튼으로 일정을 등록할 수 있습니다
      <span v-if="isAdmin" style="margin-left:8px;color:#FDCB40;background:#1A1100;border-radius:4px;padding:1px 7px;font-size:11px;font-weight:700;">
        ≡ 드래그하여 순서 변경 가능
      </span>
    </p>

    <!-- ── 월간 달력 뷰 ── -->
    <div v-if="viewMode === 'month'" class="card" style="padding:0;overflow:hidden;">
      <!-- 월간 헤더 -->
      <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;background:#F5EDDB;border-bottom:2px solid #1A1100;">
        <button type="button" @click="changeMonth(-1)"
          style="background:transparent;border:none;cursor:pointer;display:flex;align-items:center;gap:4px;font-size:12px;font-weight:700;color:#1A1100;font-family:inherit;padding:4px 8px;border-radius:6px;"
          @mouseenter="e=>e.currentTarget.style.background='rgba(253,203,64,0.4)'"
          @mouseleave="e=>e.currentTarget.style.background='transparent'">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 18l-6-6 6-6"/></svg>
          이전 달
        </button>
        <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:16px;font-weight:800;letter-spacing:-0.02em;">
          {{ monthlyYear }}년 {{ monthlyMonth }}월
          <span v-if="monthLoading" style="font-size:12px;color:#9A8F7A;font-weight:400;margin-left:8px;">로딩 중...</span>
        </div>
        <button type="button" @click="changeMonth(1)"
          style="background:transparent;border:none;cursor:pointer;display:flex;align-items:center;gap:4px;font-size:12px;font-weight:700;color:#1A1100;font-family:inherit;padding:4px 8px;border-radius:6px;"
          @mouseenter="e=>e.currentTarget.style.background='rgba(253,203,64,0.4)'"
          @mouseleave="e=>e.currentTarget.style.background='transparent'">
          다음 달
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg>
        </button>
      </div>

      <!-- 요일 헤더 -->
      <div style="display:grid;grid-template-columns:repeat(7,1fr);background:#F5EDDB;border-bottom:2px solid #1A1100;">
        <div v-for="d in ['일','월','화','수','목','금','토']" :key="d"
          :style="{ padding:'6px 0', textAlign:'center', fontSize:'12px', fontWeight:'700', color: d==='토' ? '#2563EB' : d==='일' ? '#DC2626' : '#1A1100' }">
          {{ d }}
        </div>
      </div>

      <!-- 달력 그리드 -->
      <div style="display:grid;grid-template-columns:repeat(7,1fr);">
        <!-- 빈 칸 (첫 주 시작 전) -->
        <div v-for="n in monthCalendarOffset" :key="'empty-'+n"
          style="min-height:100px;border-right:1px solid #E8E0D0;border-bottom:1px solid #E8E0D0;background:#FAFAF8;"></div>

        <!-- 날짜 셀 -->
        <div v-for="day in monthCalendarDays" :key="day.date"
          :style="{
            minHeight: '100px',
            borderRight: '1px solid #E8E0D0',
            borderBottom: '1px solid #E8E0D0',
            background: day.isToday ? '#FFFBEB' : '#fff',
            position: 'relative',
          }">
          <!-- 날짜 숫자 -->
          <div style="padding:5px 8px;display:flex;align-items:center;justify-content:space-between;">
            <span :style="{
              fontSize: '12px', fontWeight: '700',
              color: day.isSat ? '#2563EB' : day.isSun ? '#DC2626' : '#1A1100',
              background: day.isToday ? '#FDCB40' : 'transparent',
              borderRadius: '50%', width: '22px', height: '22px',
              display: 'flex', alignItems: 'center', justifyContent: 'center',
            }">{{ day.dayNum }}</span>
          </div>

          <!-- 팀원 일정 칩 (월간 — 이름 인라인 통합, 최대 MONTH_MAX_ITEMS개 표시 후 +N 더보기) -->
          <div style="padding:0 5px 5px;display:flex;flex-direction:column;gap:2px;align-items:flex-start;position:relative;z-index:5;">
            <template v-for="(entry, ei) in (monthDayEntriesMap[day.date] ?? []).slice(0, MONTH_MAX_ITEMS)" :key="ei">
              <!-- 슬롯 항목: (시간) 이름 아이콘 상태: 사이트, 내용 -->
              <div :style="{
                display:'flex', alignItems:'center', gap:'2px',
                padding:'1px 4px', borderRadius:'2px', fontSize:'10px', fontWeight:'700',
                background: entry.status && STATUS_STYLE_MAP[entry.status] ? STATUS_STYLE_MAP[entry.status].bg : '#F0FDFA',
                color: entry.status && STATUS_STYLE_MAP[entry.status] ? STATUS_STYLE_MAP[entry.status].color : '#0F766E',
                border: '1px solid ' + (entry.status && STATUS_STYLE_MAP[entry.status] ? STATUS_STYLE_MAP[entry.status].border : '#99F6E4'),
                width:'100%', overflow:'hidden',
              }">
                <span style="font-size:9px;opacity:0.6;white-space:nowrap;flex-shrink:0;">({{ entry.time }})</span>
                <span v-if="entry.status && STATUS_STYLE_MAP[entry.status]" style="font-size:9px;flex-shrink:0;">{{ STATUS_STYLE_MAP[entry.status].icon }}</span>
                <span v-else style="font-size:9px;flex-shrink:0;">📋</span>
                <span style="font-weight:800;white-space:nowrap;flex-shrink:0;overflow:hidden;text-overflow:ellipsis;max-width:30%;">{{ entry.userName }}</span>
                <span v-if="entry.status || entry.sites.length || entry.content" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;opacity:0.9;">
                  -{{ [entry.status, [...entry.sites, entry.content].filter(Boolean).join(', ')].filter(Boolean).join(': ') }}
                </span>
              </div>
            </template>
            <!-- +N 더보기 버튼 -->
            <button v-if="(monthDayEntriesMap[day.date] ?? []).length > MONTH_MAX_ITEMS"
              @click.stop="openDayModal(day.date)"
              style="position:relative;z-index:10;display:flex;align-items:center;justify-content:center;width:100%;padding:1px 4px;border-radius:4px;font-size:10px;font-weight:700;background:#1A1100;color:#FDCB40;border:1px solid #1A1100;cursor:pointer;font-family:inherit;line-height:1.5;transition:all 0.1s;"
              @mouseenter="e=>{e.currentTarget.style.background='#FDCB40';e.currentTarget.style.color='#1A1100';}"
              @mouseleave="e=>{e.currentTarget.style.background='#1A1100';e.currentTarget.style.color='#FDCB40';}">
              +{{ (monthDayEntriesMap[day.date] ?? []).length - MONTH_MAX_ITEMS }} 더보기
            </button>
          </div>

          <!-- 본인 셀 클릭 편집 -->
          <div v-if="currentUserId" @click="openModal(day.date, monthlySchedules[currentUserId]?.[day.date] ?? '')"
            style="position:absolute;inset:0;cursor:pointer;opacity:0;"
            @mouseenter="e=>e.currentTarget.style.opacity='1'"
            @mouseleave="e=>e.currentTarget.style.opacity='0'">
            <div style="position:absolute;bottom:4px;right:6px;font-size:10px;color:#C5BAA8;">✏ 편집</div>
          </div>
        </div>
      </div>
    </div>

    <!-- 팀 일정 그리드 (주간) -->
    <div v-if="viewMode === 'week'" class="card" style="padding:0;overflow:hidden;">
      <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
      <table style="border-collapse:collapse;min-width:1050px;width:100%;table-layout:fixed;">
        <!-- 주차 헤더 -->
        <thead>
          <tr style="background:#F5EDDB;border-bottom:2px solid #1A1100;">
            <th :style="nameColStyle">이름</th>
            <th colspan="5" style="padding:8px 14px;text-align:center;font-size:12px;font-weight:700;color:#1A1100;border-right:2px solid #1A1100;font-family:'Space Grotesk','Noto Sans KR',sans-serif;">
              금 주
            </th>
            <th colspan="5" style="padding:8px 14px;text-align:center;font-size:12px;font-weight:700;color:#1A1100;font-family:'Space Grotesk','Noto Sans KR',sans-serif;">
              차 주
            </th>
          </tr>
          <!-- 날짜 헤더 -->
          <tr style="background:#F5EDDB;border-bottom:2px solid #1A1100;">
            <th :style="nameColStyle" style="padding:8px 14px;"></th>
            <th v-for="(date, i) in [...currDates, ...nextDates]" :key="date"
              :style="{
                padding:'8px 6px',
                textAlign:'center',
                fontSize:'12px',
                fontWeight:'700',
                borderRight: i < 9 ? (i===4 ? '2px solid #1A1100' : '1.5px solid rgba(26,17,0,0.15)') : 'none',
                background: isToday(date) ? '#FFF0A0' : 'transparent',
                width: '90px',
              }">
              <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;">{{ DAY_KR[i % 5] }}</div>
              <div style="font-size:11px;color:#9A8F7A;margin-top:2px;">{{ date.substring(5).replace('-','/') }}</div>
            </th>
          </tr>
        </thead>

        <!-- 팀원 행 -->
        <tbody>
          <tr v-for="(user, ui) in orderedUsers" :key="user.id"
            :draggable="isAdmin"
            :style="{
              borderBottom: ui < orderedUsers.length-1 ? '2px solid #1A1100' : 'none',
              transition: 'opacity 0.15s',
              opacity: dragSrcIdx === ui ? '0.4' : '1',
              background: dragOverIdx === ui && dragSrcIdx !== ui ? '#FFF0A0' : 'transparent',
            }"
            @dragstart="onDragStart($event, ui)"
            @dragover.prevent="onDragOver(ui)"
            @dragleave="onDragLeave"
            @drop.prevent="onDrop(ui)"
            @dragend="onDragEnd">

            <!-- 이름 열 -->
            <td style="padding:10px 14px;border-right:2px solid #1A1100;background:#F5EDDB;vertical-align:middle;white-space:nowrap;">
              <div style="display:flex;align-items:center;gap:8px;">
                <div v-if="isAdmin"
                  style="cursor:grab;color:#C5BAA8;flex-shrink:0;padding:2px;display:flex;align-items:center;user-select:none;"
                  title="드래그하여 순서 변경">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                    <rect x="4" y="4" width="16" height="2" rx="1"/>
                    <rect x="4" y="11" width="16" height="2" rx="1"/>
                    <rect x="4" y="18" width="16" height="2" rx="1"/>
                  </svg>
                </div>
                <div
                  @click.stop="weekReportMap[user.id] && router.get(`/reports/${weekReportMap[user.id]}`)"
                  :style="{ display:'flex', alignItems:'center', gap:'6px', cursor: weekReportMap[user.id] ? 'pointer' : 'default' }"
                  :title="weekReportMap[user.id] ? '주간보고 보기' : '이번 주 보고서 없음'">
                  <div :style="{
                    width:'28px', height:'28px', borderRadius:'50%',
                    background: avatarImg(user.id) ? 'transparent' : avatarColor(user.id),
                    border: weekReportMap[user.id] ? '2.5px solid #FD4401' : '2px solid #1A1100',
                    boxShadow: weekReportMap[user.id] ? '0 0 0 1.5px #1A1100' : 'none',
                    display:'flex', alignItems:'center', justifyContent:'center', overflow:'hidden',
                    color:'#fff', fontSize:'11px', fontWeight:'700', flexShrink:0,
                    fontFamily:'\'Space Grotesk\',sans-serif',
                  }">
                    <img v-if="avatarImg(user.id)" :src="avatarImg(user.id)" style="width:100%;height:100%;object-fit:cover;" />
                    <template v-else>{{ user.name.charAt(0) }}</template>
                  </div>
                  <div>
                    <div style="font-size:12px;font-weight:700;">{{ user.name }}</div>
                    <div v-if="user.position" style="font-size:10px;color:#9A8F7A;">{{ user.position }}</div>
                  </div>
                </div>
              </div>
            </td>

            <!-- 날짜 셀들 (읽기 전용 + 본인 셀 클릭 → 모달) -->
            <td v-for="(date, di) in [...currDates, ...nextDates]" :key="date"
              @click="user.id === currentUserId ? openModal(date, localSchedules[user.id][date]) : null"
              :style="{
                borderRight: di < 9 ? (di===4 ? '2px solid #1A1100' : '1.5px solid rgba(26,17,0,0.1)') : 'none',
                background: isToday(date) ? '#FFF0A0' : 'transparent',
                padding:'6px',
                verticalAlign:'top',
                overflow:'hidden',
                cursor: user.id === currentUserId ? 'pointer' : 'default',
                transition:'background 0.1s',
                position:'relative',
              }"
              @mouseenter="e=>{ if(user.id === currentUserId) e.currentTarget.style.background = isToday(date) ? '#FFF0A0' : '#FFFBF0'; }"
              @mouseleave="e=>{ e.currentTarget.style.background = isToday(date) ? '#FFF0A0' : 'transparent'; }">

              <!-- 내용 표시 (시간대별 슬롯 칩 — 고정 높이, 최대 2개 표시) -->
              <div style="height:44px;padding:3px 4px;display:flex;flex-direction:column;gap:2px;align-items:flex-start;overflow:hidden;flex-shrink:0;">
                <template v-if="localSchedules[user.id]?.[date]">
                  <template v-for="slot in parsedCell(localSchedules[user.id][date]).slots.slice(0, 2)" :key="slot.time + slot.status">
                    <!-- 슬롯 1개 = 1 칩 (상태 + 사이트 한 줄 통합) -->
                    <div
                      :title="`(${slot.time}) ${[slot.status, [...slot.sites, slot.content ?? ''].filter(Boolean).join(', ')].filter(Boolean).join(': ')}`"
                      :style="{
                      display:'flex', alignItems:'center', gap:'3px',
                      padding:'2px 6px', borderRadius:'2px', fontSize:'10.5px', fontWeight:'800',
                      background: slot.status && STATUS_STYLE_MAP[slot.status] ? STATUS_STYLE_MAP[slot.status].bg : '#FFEDD5',
                      color: slot.status && STATUS_STYLE_MAP[slot.status] ? STATUS_STYLE_MAP[slot.status].color : '#C2410C',
                      border: '1.5px solid ' + (slot.status && STATUS_STYLE_MAP[slot.status] ? STATUS_STYLE_MAP[slot.status].border : '#FDBA74'),
                      width: '100%',
                      overflow: 'hidden',
                    }">
                      <span style="font-size:9px;opacity:0.65;font-weight:600;white-space:nowrap;">({{ slot.time }})</span>
                      <span v-if="slot.status && STATUS_STYLE_MAP[slot.status]" style="font-size:10px;flex-shrink:0;">{{ STATUS_STYLE_MAP[slot.status].icon }}</span>
                      <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        {{ [slot.status, [...slot.sites, slot.content ?? ''].filter(Boolean).join(', ')].filter(Boolean).join(': ') }}
                      </span>
                    </div>
                  </template>
                  <!-- 3개 이상 슬롯은 +N으로 축약 표시 -->
                  <div v-if="parsedCell(localSchedules[user.id][date]).slots.length > 2"
                    style="font-size:9px;color:#9A8F7A;font-weight:700;padding:0 2px;line-height:1;">
                    +{{ parsedCell(localSchedules[user.id][date]).slots.length - 2 }}
                  </div>
                </template>
              </div>

              <!-- 본인 셀: 내용 없으면 + 힌트, 있으면 연필 아이콘 -->
              <div v-if="user.id === currentUserId" style="text-align:right;height:16px;padding-right:2px;margin-top:2px;">
                <span v-if="!localSchedules[user.id]?.[date]"
                  style="font-size:10px;color:#C5BAA8;font-weight:600;">+ 추가</span>
                <svg v-else width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#C5BAA8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
              </div>
            </td>
          </tr>

          <tr v-if="orderedUsers.length === 0">
            <td colspan="11" style="padding:48px;text-align:center;color:#9A8F7A;font-size:13px;">등록된 팀원이 없습니다</td>
          </tr>
        </tbody>
      </table>
      </div><!-- /overflow-x:auto -->
    </div>

    <!-- 순서 저장 알림 -->
    <Transition name="toast">
      <div v-if="orderSaved"
        style="position:fixed;bottom:24px;right:24px;background:#1A1100;color:#FDCB40;border-radius:12px;padding:10px 20px;font-size:13px;font-weight:700;font-family:'Space Grotesk','Noto Sans KR',sans-serif;box-shadow:4px 4px 0 rgba(0,0,0,0.3);z-index:200;display:flex;align-items:center;gap:8px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
        팀원 순서가 저장되었습니다
      </div>
    </Transition>

    <!-- 저장 완료 토스트 -->
    <Transition name="toast">
      <div v-if="saveDone"
        style="position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#1A1100;color:#FDCB40;border-radius:12px;padding:10px 22px;font-size:13px;font-weight:700;font-family:'Space Grotesk','Noto Sans KR',sans-serif;box-shadow:4px 4px 0 rgba(0,0,0,0.3);z-index:200;display:flex;align-items:center;gap:8px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
        일정이 저장되었습니다
      </div>
    </Transition>

    <!-- 일정 추가/수정 모달 -->
    <Transition name="modal-fade">
      <div v-if="showModal"
        style="position:fixed;inset:0;background:rgba(26,17,0,0.5);display:flex;align-items:center;justify-content:center;z-index:300;backdrop-filter:blur(4px);padding:16px;"
        @click.self="closeModal">
        <div class="card" style="width:460px;max-width:100%;max-height:90vh;padding:0;overflow:hidden;display:flex;flex-direction:column;">
          <!-- 모달 헤더 -->
          <div style="padding:16px 20px;background:#F5EDDB;border-bottom:2px solid #1A1100;display:flex;justify-content:space-between;align-items:center;flex-shrink:0;">
            <div style="display:flex;align-items:center;gap:8px;">
              <div style="background:#FDCB40;border:2px solid #1A1100;border-radius:7px;width:28px;height:28px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M8 2v3M16 2v3M3.5 9.5h17M3 6.5h18a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V7.5a1 1 0 0 1 1-1z"/>
                </svg>
              </div>
              <span style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:15px;font-weight:800;">일정 추가</span>
              <span style="font-size:12px;color:#9A8F7A;">내 일정만 추가·수정됩니다</span>
            </div>
            <button type="button" @click="closeModal"
              style="background:none;border:none;cursor:pointer;color:#9A8F7A;padding:4px;border-radius:6px;"
              @mouseenter="e=>e.currentTarget.style.color='#DC2626'"
              @mouseleave="e=>e.currentTarget.style.color='#9A8F7A'">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
          </div>

          <!-- 모달 본문 -->
          <div style="padding:22px 24px;display:flex;flex-direction:column;gap:18px;overflow-y:auto;flex:1;min-height:0;">

            <!-- 날짜 선택 -->
            <div>
              <label style="font-size:12px;font-weight:700;color:#9A8F7A;display:block;margin-bottom:10px;letter-spacing:0.04em;text-transform:uppercase;">날짜 선택</label>
              <!-- 금주 -->
              <div style="margin-bottom:8px;">
                <div style="font-size:11px;color:#9A8F7A;font-weight:600;margin-bottom:6px;padding-left:2px;">
                  금주 ({{ fmtRange(currDates[0], currDates[4]) }})
                </div>
                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                  <button v-for="(date, i) in currDates" :key="date" type="button"
                    @click="modalDate = date"
                    :style="{
                      padding:'6px 10px', borderRadius:'8px', fontSize:'12px', fontWeight:'700',
                      border: modalDate === date ? '2px solid #1A1100' : '2px solid #E8E0D0',
                      background: modalDate === date ? (isToday(date) ? '#FDCB40' : '#1A1100') : (isToday(date) ? '#FFF0A0' : '#fff'),
                      color: modalDate === date ? (isToday(date) ? '#1A1100' : '#FDCB40') : '#4A3F2A',
                      cursor:'pointer', fontFamily:'inherit', transition:'all 0.12s',
                    }">
                    {{ DAY_KR[i] }} <span style="font-weight:400;font-size:11px;">{{ date.substring(5).replace('-','/') }}</span>
                  </button>
                </div>
              </div>
              <!-- 차주 -->
              <div>
                <div style="font-size:11px;color:#9A8F7A;font-weight:600;margin-bottom:6px;padding-left:2px;">
                  차주 ({{ fmtRange(nextDates[0], nextDates[4]) }})
                </div>
                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                  <button v-for="(date, i) in nextDates" :key="date" type="button"
                    @click="modalDate = date"
                    :style="{
                      padding:'6px 10px', borderRadius:'8px', fontSize:'12px', fontWeight:'700',
                      border: modalDate === date ? '2px solid #1A1100' : '2px solid #E8E0D0',
                      background: modalDate === date ? '#1A1100' : '#fff',
                      color: modalDate === date ? '#FDCB40' : '#4A3F2A',
                      cursor:'pointer', fontFamily:'inherit', transition:'all 0.12s',
                    }">
                    {{ DAY_KR[i] }} <span style="font-weight:400;font-size:11px;">{{ date.substring(5).replace('-','/') }}</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- 일정 내용 -->
            <div>
              <label style="font-size:12px;font-weight:700;color:#9A8F7A;display:block;margin-bottom:10px;letter-spacing:0.04em;text-transform:uppercase;">
                일정 내용
                <span v-if="modalDate" style="font-weight:600;color:#1A1100;text-transform:none;font-size:12px;margin-left:6px;">
                  — {{ modalDate.substring(5).replace('-','/') }} ({{ DAY_KR[allDates.indexOf(modalDate) % 5] }})
                </span>
              </label>

              <!-- ── 시간 선택 (라디오) ── -->
              <div style="background:#F5EDDB;border:1.5px solid #D0C9BC;border-radius:10px;padding:10px 12px;margin-bottom:10px;">
                <div style="display:flex;align-items:center;gap:5px;margin-bottom:8px;">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#9A8F7A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                  <span style="font-size:11px;color:#9A8F7A;font-weight:700;letter-spacing:0.03em;">시간</span>
                  <span style="font-size:10px;color:#C5BAA8;">종일/오전/오후별로 각각 등록됩니다</span>
                </div>
                <div style="display:flex;gap:6px;">
                  <button v-for="t in ['종일','오전','오후']" :key="t" type="button" @click="modalTime = t"
                    :style="{
                      padding:'5px 16px', borderRadius:'4px', fontSize:'12px', fontWeight:'700',
                      border: modalTime === t ? '2px solid #1A1100' : '2px solid #D0C9BC',
                      background: modalTime === t ? '#1A1100' : '#fff',
                      color: modalTime === t ? '#FDCB40' : '#6B5E4A',
                      cursor:'pointer', fontFamily:'inherit', transition:'all 0.12s', position:'relative',
                    }">
                    {{ t }}
                    <span v-if="modalAllSlots[t].status" :style="{
                      position:'absolute', top:'-5px', right:'-5px',
                      width:'8px', height:'8px', borderRadius:'50%',
                      background: STATUS_STYLE_MAP[modalAllSlots[t].status]?.border ?? '#9A8F7A',
                      border:'1.5px solid #fff',
                    }"></span>
                  </button>
                </div>
                <!-- 등록된 슬롯 미리보기 -->
                <div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:4px;">
                  <template v-for="t in ['종일','오전','오후']" :key="t">
                    <!-- 상태 있는 슬롯 -->
                    <div v-if="modalAllSlots[t].status && STATUS_STYLE_MAP[modalAllSlots[t].status]"
                      :style="{
                        display:'inline-flex', alignItems:'center', gap:'3px',
                        padding:'2px 7px', borderRadius:'4px', fontSize:'11px', fontWeight:'700',
                        background: STATUS_STYLE_MAP[modalAllSlots[t].status].bg,
                        color: STATUS_STYLE_MAP[modalAllSlots[t].status].color,
                        border: '1.5px solid ' + STATUS_STYLE_MAP[modalAllSlots[t].status].border,
                      }">
                      <span style="opacity:0.6;font-size:10px;">({{ t }})</span>
                      {{ STATUS_STYLE_MAP[modalAllSlots[t].status].icon }} {{ modalAllSlots[t].status }}<template v-if="modalAllSlots[t].content">: {{ modalAllSlots[t].content }}</template>
                    </div>
                    <!-- 내용만 있는 슬롯 (상태 없음) -->
                    <div v-else-if="modalAllSlots[t].content"
                      style="display:inline-flex;align-items:center;gap:3px;padding:2px 7px;border-radius:4px;font-size:11px;font-weight:700;background:#CFFAFE;color:#0E7490;border:1.5px solid #67E8F9;">
                      <span style="opacity:0.6;font-size:10px;">({{ t }})</span> ✏ {{ modalAllSlots[t].content }}
                    </div>
                  </template>
                </div>
              </div>

              <!-- ── 상태 선택 (라디오, 단일, 현재 시간대) ── -->
              <div style="background:#F8F7FF;border:1.5px solid #E0DCF5;border-radius:10px;padding:10px 12px;margin-bottom:10px;">
                <div style="display:flex;align-items:center;gap:5px;margin-bottom:8px;">
                  <span style="font-size:11px;color:#9A8F7A;font-weight:700;letter-spacing:0.03em;">상태</span>
                  <span style="font-size:10px;color:#B8B0C8;">{{ modalTime }} · 하나만 선택</span>
                </div>
                <div style="display:flex;gap:7px;flex-wrap:wrap;">
                  <button v-for="tag in QUICK_TAGS" :key="tag.label" type="button"
                    @click="selectStatus(tag.label)"
                    :style="{
                      display:'inline-flex', alignItems:'center', gap:'5px',
                      padding:'5px 13px', borderRadius:'4px', fontSize:'12px', fontWeight:'700',
                      border: modalAllSlots[modalTime].status === tag.label ? '2px solid #1A1100' : '2px solid #D0C9BC',
                      background: modalAllSlots[modalTime].status === tag.label ? tag.bg : '#fff',
                      color: modalAllSlots[modalTime].status === tag.label ? tag.color : '#9A8F7A',
                      cursor:'pointer', fontFamily:'inherit', transition:'all 0.12s',
                      boxShadow: modalAllSlots[modalTime].status === tag.label ? '2px 2px 0 #1A1100' : 'none',
                    }">
                    <span>{{ tag.icon }}</span>{{ tag.label }}
                  </button>
                </div>
              </div>

              <!-- ── 내 사이트 (현재 시간대) ── -->
              <div v-if="mySites.length"
                style="background:#FFFBF0;border:1.5px solid #E8E0D0;border-radius:10px;padding:10px 12px;margin-bottom:10px;">
                <div style="display:flex;align-items:center;gap:5px;margin-bottom:8px;">
                  <span style="font-size:11px;color:#9A8F7A;font-weight:700;letter-spacing:0.03em;">내 사이트</span>
                  <span style="font-size:10px;color:#C5BAA8;">{{ modalTime }}</span>
                </div>
                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                  <button v-for="site in mySites" :key="site" type="button"
                    @click="selectSite(site)"
                    :style="{
                      display:'inline-flex', alignItems:'center', gap:'4px',
                      padding:'4px 12px', borderRadius:'4px', fontSize:'12px', fontWeight:'700',
                      border: modalAllSlots[modalTime].sites.includes(site) ? '2px solid #1A1100' : '2px solid #D0C9BC',
                      background: modalAllSlots[modalTime].sites.includes(site) ? '#FDCB40' : '#fff',
                      color: modalAllSlots[modalTime].sites.includes(site) ? '#1A1100' : '#6B5E4A',
                      cursor:'pointer', fontFamily:'inherit', transition:'all 0.12s',
                      boxShadow: modalAllSlots[modalTime].sites.includes(site) ? '2px 2px 0 #1A1100' : 'none',
                    }">
                    {{ site }}
                  </button>
                </div>
              </div>

              <!-- ── 내용 (현재 시간대 슬롯에 인라인 포함) ── -->
              <div style="background:#F0F9FF;border:1.5px solid #BAE6FD;border-radius:10px;padding:10px 12px;">
                <div style="display:flex;align-items:center;gap:5px;margin-bottom:8px;">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#0369A1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                  </svg>
                  <span style="font-size:11px;color:#0369A1;font-weight:700;letter-spacing:0.03em;">내용</span>
                  <span style="font-size:10px;color:#7DD3FC;">{{ modalTime }} 슬롯에 포함되어 표시됩니다</span>
                </div>
                <!-- 미리보기 칩 — 슬롯 스타일로 통합 표시 -->
                <div v-if="modalAllSlots[modalTime].content.trim()" style="margin-bottom:8px;">
                  <div :style="{
                    display:'inline-flex', alignItems:'center', gap:'3px',
                    padding:'2px 8px', borderRadius:'4px', fontSize:'11px', fontWeight:'700',
                    background: modalAllSlots[modalTime].status && STATUS_STYLE_MAP[modalAllSlots[modalTime].status] ? STATUS_STYLE_MAP[modalAllSlots[modalTime].status].bg : '#CFFAFE',
                    color: modalAllSlots[modalTime].status && STATUS_STYLE_MAP[modalAllSlots[modalTime].status] ? STATUS_STYLE_MAP[modalAllSlots[modalTime].status].color : '#0E7490',
                    border: '1.5px solid ' + (modalAllSlots[modalTime].status && STATUS_STYLE_MAP[modalAllSlots[modalTime].status] ? STATUS_STYLE_MAP[modalAllSlots[modalTime].status].border : '#67E8F9'),
                  }">
                    <span style="opacity:0.6;font-size:10px;">({{ modalTime }})</span>
                    <template v-if="modalAllSlots[modalTime].status && STATUS_STYLE_MAP[modalAllSlots[modalTime].status]">
                      {{ STATUS_STYLE_MAP[modalAllSlots[modalTime].status].icon }} {{ modalAllSlots[modalTime].status }}:
                    </template>
                    <template v-else>✏</template>
                    {{ modalAllSlots[modalTime].content }}
                  </div>
                </div>
                <textarea
                  v-model="modalAllSlots[modalTime].content"
                  rows="2"
                  placeholder="내용을 입력하세요 — 현재 시간대 슬롯에 인라인으로 표시됩니다"
                  style="width:100%;background:#fff;border:1.5px solid #BAE6FD;border-radius:8px;padding:8px 11px;color:#1A1100;font-size:13px;font-family:inherit;outline:none;resize:none;line-height:1.65;transition:border-color 0.12s;"
                  @focus="e=>e.target.style.borderColor='#0369A1'"
                  @blur="e=>e.target.style.borderColor='#BAE6FD'"
                  @keydown.enter.prevent
                  @keydown.meta.enter.prevent="saveModal"
                  @keydown.ctrl.enter.prevent="saveModal"
                ></textarea>
              </div>
              <p style="font-size:11px;color:#9A8F7A;margin-top:6px;">모두 비워두면 해당 날짜 일정이 삭제됩니다 &nbsp;·&nbsp; Ctrl+Enter로 빠르게 저장</p>
            </div>
          </div>

          <!-- 모달 푸터 -->
          <div style="padding:14px 24px;background:#F5EDDB;border-top:2px solid #1A1100;display:flex;justify-content:space-between;align-items:center;flex-shrink:0;">
            <!-- 삭제 버튼 (기존 내용 있을 때) -->
            <button v-if="modalDate && localSchedules[currentUserId]?.[modalDate]"
              type="button" @click="deleteSchedule"
              style="display:inline-flex;align-items:center;gap:5px;background:#FEE2E2;color:#DC2626;border:2px solid #DC2626;border-radius:8px;padding:6px 12px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;transition:all 0.1s;"
              @mouseenter="e=>{e.currentTarget.style.background='#DC2626';e.currentTarget.style.color='#fff';}"
              @mouseleave="e=>{e.currentTarget.style.background='#FEE2E2';e.currentTarget.style.color='#DC2626';}">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                <path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
              </svg>
              삭제
            </button>
            <div v-else></div>

            <div style="display:flex;gap:8px;">
              <button type="button" @click="closeModal" class="btn-secondary btn-sm">취소</button>
              <button type="button" @click="saveModal"
                :disabled="!modalDate || modalSaving"
                style="display:inline-flex;align-items:center;gap:5px;background:#FDCB40;color:#1A1100;border:2px solid #1A1100;border-radius:8px;padding:7px 16px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:2px 2px 0 #1A1100;transition:all 0.1s;"
                :style="{ opacity: !modalDate || modalSaving ? 0.5 : 1, cursor: !modalDate || modalSaving ? 'not-allowed' : 'pointer' }"
                @mouseenter="e=>{ if(modalDate && !modalSaving){ e.currentTarget.style.transform='translate(-1px,-1px)'; e.currentTarget.style.boxShadow='3px 3px 0 #1A1100'; } }"
                @mouseleave="e=>{ e.currentTarget.style.transform='none'; e.currentTarget.style.boxShadow='2px 2px 0 #1A1100'; }">
                <svg v-if="modalSaving" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                <svg v-else width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                {{ modalSaving ? '저장 중...' : '저장' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- 일별 상세 모달 (카테고리 그룹형) -->
    <Transition name="modal-fade">
      <div v-if="showDayModal"
        style="position:fixed;inset:0;background:rgba(26,17,0,0.45);display:flex;align-items:center;justify-content:center;z-index:400;backdrop-filter:blur(3px);padding:16px;"
        @click.self="showDayModal=false">
        <div class="card" style="width:560px;max-width:100%;max-height:90vh;padding:0;overflow:hidden;display:flex;flex-direction:column;">

          <!-- 모달 헤더 -->
          <div style="padding:16px 20px;background:#F5EDDB;border-bottom:2px solid #1A1100;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="background:#FDCB40;border:2px solid #1A1100;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v3M16 2v3M3.5 9.5h17M3 6.5h18a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V7.5a1 1 0 0 1 1-1z"/></svg>
              </div>
              <div>
                <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:16px;font-weight:800;">{{ fmtDayModalDate(dayModalDate) }}</div>
                <div style="font-size:11px;color:#9A8F7A;">{{ (monthDayEntriesMap[dayModalDate] ?? []).length }}개 일정</div>
              </div>
            </div>
            <button @click="showDayModal=false"
              style="background:none;border:none;cursor:pointer;color:#9A8F7A;padding:4px;border-radius:6px;"
              @mouseenter="e=>e.currentTarget.style.color='#DC2626'"
              @mouseleave="e=>e.currentTarget.style.color='#9A8F7A'">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
          </div>

          <!-- 모달 본문: 카테고리별 섹션 -->
          <div style="padding:18px 20px;overflow-y:auto;flex:1;min-height:0;display:flex;flex-direction:column;gap:12px;">

            <!-- 카테고리 그룹 (외근/출장/반차/휴가) -->
            <template v-for="tag in QUICK_TAGS" :key="tag.label">
              <div v-if="dayModalGroups[tag.label]?.length"
                :style="{ background: tag.bg, border: '2px solid ' + tag.border, borderRadius: '14px', overflow: 'hidden' }">
                <!-- 카테고리 헤더 -->
                <div :style="{ padding: '10px 16px', background: tag.bg, borderBottom: '1.5px solid ' + tag.border, display: 'flex', alignItems: 'center', gap: '8px' }">
                  <span style="font-size:18px;">{{ tag.icon }}</span>
                  <span :style="{ fontFamily: '\'Space Grotesk\',\'Noto Sans KR\',sans-serif', fontSize: '14px', fontWeight: '800', color: tag.color }">{{ tag.label }}</span>
                  <span :style="{ marginLeft: 'auto', fontSize: '11px', fontWeight: '700', color: tag.color, opacity: 0.7 }">{{ dayModalGroups[tag.label].length }}명</span>
                </div>
                <!-- 사람 목록 -->
                <div style="padding:10px 16px;display:flex;flex-direction:column;gap:7px;">
                  <div v-for="(entry, ei) in dayModalGroups[tag.label]" :key="ei"
                    style="display:flex;align-items:center;gap:8px;">
                    <!-- 아바타 -->
                    <div :style="{ width:'26px', height:'26px', borderRadius:'50%', background: avatarImg(entry.userId) ? 'transparent' : avatarColor(entry.userId), border:'2px solid rgba(0,0,0,0.15)', display:'flex', alignItems:'center', justifyContent:'center', color:'#fff', fontSize:'10px', fontWeight:'700', flexShrink:0, overflow:'hidden' }">
                      <img v-if="avatarImg(entry.userId)" :src="avatarImg(entry.userId)" style="width:100%;height:100%;object-fit:cover;" />
                      <template v-else>{{ entry.userName.charAt(0) }}</template>
                    </div>
                    <!-- 이름 -->
                    <span style="font-size:13px;font-weight:700;color:#1A1100;white-space:nowrap;">{{ entry.userName }}</span>
                    <!-- 시간 (종일 아닌 경우) -->
                    <span v-if="entry.time && entry.time !== '종일'"
                      style="font-size:10px;font-weight:700;padding:1px 6px;border-radius:4px;background:rgba(0,0,0,0.07);color:#4A3F2A;white-space:nowrap;flex-shrink:0;">
                      {{ entry.time }}
                    </span>
                    <!-- 사이트 -->
                    <span v-if="entry.sites?.length"
                      :style="{ fontSize:'12px', color: tag.color, fontWeight:'600' }">
                      — {{ entry.sites.join(', ') }}
                    </span>
                    <!-- 기타 내용 -->
                    <span v-else-if="entry.content"
                      :style="{ fontSize:'12px', color: tag.color, fontWeight:'600' }">
                      — {{ entry.content }}
                    </span>
                  </div>
                </div>
              </div>
            </template>

            <!-- 기타 일정 (상태 미선택 항목 — 사이트/메모 포함) -->
            <div v-if="dayModalGroups['__etc']?.length"
              style="background:#F0FDFA;border:2px solid #99F6E4;border-radius:14px;overflow:hidden;">
              <div style="padding:10px 16px;border-bottom:1.5px solid #99F6E4;display:flex;align-items:center;gap:8px;">
                <span style="font-size:18px;">📋</span>
                <span style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:14px;font-weight:800;color:#0F766E;">기타 일정</span>
                <span style="margin-left:auto;font-size:11px;font-weight:700;color:#0F766E;opacity:0.7;">{{ dayModalGroups['__etc'].length }}명</span>
              </div>
              <div style="padding:10px 16px;display:flex;flex-direction:column;gap:7px;">
                <div v-for="(entry, ei) in dayModalGroups['__etc']" :key="ei" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                  <div :style="{ width:'26px', height:'26px', borderRadius:'50%', background: avatarImg(entry.userId) ? 'transparent' : avatarColor(entry.userId), border:'2px solid rgba(0,0,0,0.15)', display:'flex', alignItems:'center', justifyContent:'center', color:'#fff', fontSize:'10px', fontWeight:'700', flexShrink:0, overflow:'hidden' }">
                    <img v-if="avatarImg(entry.userId)" :src="avatarImg(entry.userId)" style="width:100%;height:100%;object-fit:cover;" />
                    <template v-else>{{ entry.userName.charAt(0) }}</template>
                  </div>
                  <span style="font-size:13px;font-weight:700;color:#1A1100;white-space:nowrap;">{{ entry.userName }}</span>
                  <span v-if="entry.time && entry.time !== '종일'" style="font-size:10px;font-weight:700;padding:1px 6px;border-radius:4px;background:rgba(0,0,0,0.07);color:#4A3F2A;white-space:nowrap;flex-shrink:0;">{{ entry.time }}</span>
                  <!-- 사이트 또는 내용 표시 -->
                  <span v-if="entry.sites?.length" style="font-size:12px;color:#0F766E;font-weight:600;">
                    — {{ entry.sites.join(', ') }}
                    <span v-if="entry.content" style="color:#9A8F7A;"> · {{ entry.content }}</span>
                  </span>
                  <span v-else-if="entry.content" style="font-size:12px;color:#0F766E;font-weight:600;">— {{ entry.content }}</span>
                </div>
              </div>
            </div>

            <!-- 일정 없음 -->
            <div v-if="!(monthDayEntriesMap[dayModalDate] ?? []).length"
              style="padding:32px;text-align:center;color:#9A8F7A;font-size:13px;">
              이 날 등록된 일정이 없습니다
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  users:          { type: Array,   default: () => [] },
  teamSchedules:  { type: Object,  default: () => ({}) },
  currDates:      { type: Array,   default: () => [] },
  nextDates:      { type: Array,   default: () => [] },
  weekStart:      { type: String,  default: '' },
  currentUserId:  { type: Number,  default: 0 },
  isAdmin:        { type: Boolean, default: false },
  prevWeek:       { type: String,  default: '' },
  nextWeek:       { type: String,  default: '' },
  isCurrentWeek:  { type: Boolean, default: true },
  weekReportMap:  { type: Object,  default: () => ({}) },
  mySites:        { type: Array,   default: () => [] },
})

const DAY_KR = ['월', '화', '수', '목', '금']

const AVATAR_COLORS = ['#FD4401','#16a34a','#2563eb','#9333ea','#d97706','#0891b2','#dc2626','#65a30d']

const userAvatarMap = computed(() => {
  const map = {}
  const allUsers = [...orderedUsers.value, ...monthlyUsers.value]
  for (const u of allUsers) {
    if (!map[u.id]) map[u.id] = { color: u.avatar_color, image: u.avatar_image_url }
  }
  return map
})

const avatarColor = (id) => userAvatarMap.value[id]?.color || AVATAR_COLORS[(id ?? 0) % AVATAR_COLORS.length]
const avatarImg   = (id) => userAvatarMap.value[id]?.image || null

const today    = new Date().toISOString().slice(0, 10)
const isToday  = (d) => d === today
const allDates = computed(() => [...props.currDates, ...props.nextDates])

const nameColStyle = {
  width: props.isAdmin ? '130px' : '100px',
  padding: '10px 14px',
  textAlign: 'left',
  fontSize: '11px',
  fontWeight: '700',
  color: '#9A8F7A',
  textTransform: 'uppercase',
  letterSpacing: '0.06em',
  borderRight: '2px solid #1A1100',
  fontFamily: '\'Space Grotesk\',\'Noto Sans KR\',sans-serif',
  verticalAlign: 'middle',
}

// ── 순서 관리 ──────────────────────────────────────────
const orderedUsers = ref([...props.users])
const dragSrcIdx   = ref(null)
const dragOverIdx  = ref(null)
const orderSaved   = ref(false)
let orderSavedTimer = null

const onDragStart = (e, idx) => {
  dragSrcIdx.value = idx
  e.dataTransfer.effectAllowed = 'move'
}
const onDragOver  = (idx) => { dragOverIdx.value = idx }
const onDragLeave = () => {}
const onDrop = async (targetIdx) => {
  const src = dragSrcIdx.value
  if (src === null || src === targetIdx) { dragSrcIdx.value = null; dragOverIdx.value = null; return }
  const arr = [...orderedUsers.value]
  const [moved] = arr.splice(src, 1)
  arr.splice(targetIdx, 0, moved)
  orderedUsers.value = arr
  dragSrcIdx.value   = null
  dragOverIdx.value  = null
  try {
    await window.axios.post('/admin/users/reorder', { order: arr.map(u => u.id) })
    clearTimeout(orderSavedTimer)
    orderSaved.value = true
    orderSavedTimer  = setTimeout(() => { orderSaved.value = false }, 2500)
  } catch (e) { console.error('순서 저장 실패', e) }
}
const onDragEnd = () => { dragSrcIdx.value = null; dragOverIdx.value = null }

// ── 로컬 일정 데이터 ───────────────────────────────────
const localSchedules = reactive({})
for (const user of props.users) {
  localSchedules[user.id] = {}
  for (const date of [...props.currDates, ...props.nextDates]) {
    localSchedules[user.id][date] = props.teamSchedules[user.id]?.[date] ?? ''
  }
}

const fmtRange = (start, end) => {
  if (!start || !end) return ''
  return start.substring(5).replace('-', '/') + ' – ' + end.substring(5).replace('-', '/')
}

// ── 주간/월간 뷰 전환 ──────────────────────────────────
const viewMode = ref('week')  // 'week' | 'month'

// 월간 달력 상태
const todayStr  = new Date().toISOString().slice(0, 10)
const todayDate = new Date()
const monthlyYear  = ref(todayDate.getFullYear())
const monthlyMonth = ref(todayDate.getMonth() + 1)  // 1~12
const monthlySchedules = reactive({})  // { userId: { date: content } }
const monthlyUsers     = ref([])
const monthLoading     = ref(false)

// 월간 달력 계산
const monthCalendarDays = computed(() => {
  const year  = monthlyYear.value
  const month = monthlyMonth.value
  const firstDay = new Date(year, month - 1, 1)
  const lastDay  = new Date(year, month, 0)
  const days = []
  for (let d = 1; d <= lastDay.getDate(); d++) {
    const date    = `${year}-${String(month).padStart(2,'0')}-${String(d).padStart(2,'0')}`
    const dayOfWk = new Date(year, month - 1, d).getDay()
    days.push({
      date,
      dayNum:  d,
      isSat:   dayOfWk === 6,
      isSun:   dayOfWk === 0,
      isToday: date === todayStr,
    })
  }
  return days
})

// 달력 첫 번째 칸의 오프셋 (일요일 시작: 일=0 ... 토=6)
const monthCalendarOffset = computed(() => {
  const firstDay = new Date(monthlyYear.value, monthlyMonth.value - 1, 1).getDay()
  return firstDay  // 일=0 → 0칸, 월=1 → 1칸 ... 토=6 → 6칸
})

// 월간 데이터 로드
const loadMonthlyData = async () => {
  monthLoading.value = true
  try {
    const month = `${monthlyYear.value}-${String(monthlyMonth.value).padStart(2,'0')}`
    const res = await window.axios.get('/schedules/monthly', { params: { month } })
    monthlyUsers.value = res.data.users

    // monthlySchedules 초기화
    for (const key in monthlySchedules) delete monthlySchedules[key]
    for (const user of res.data.users) {
      monthlySchedules[user.id] = res.data.teamSchedules[user.id] ?? {}
    }
  } catch (e) { console.error('월간 일정 로드 실패', e) }
  finally { monthLoading.value = false }
}

// 월간 전환 시 데이터 로드
const switchToMonth = async () => {
  viewMode.value = 'month'
  await loadMonthlyData()
}

// 이전/다음 달 이동
const changeMonth = async (delta) => {
  let m = monthlyMonth.value + delta
  let y = monthlyYear.value
  if (m > 12) { m = 1; y++ }
  if (m < 1)  { m = 12; y-- }
  monthlyMonth.value = m
  monthlyYear.value  = y
  await loadMonthlyData()
}

// 월간 달력에서 일정 저장 후 데이터 갱신 (saveModal 이후 호출)
const refreshMonthlySchedule = (date, content) => {
  const userId = props.currentUserId
  if (!monthlySchedules[userId]) monthlySchedules[userId] = {}
  monthlySchedules[userId][date] = content
}

// ── 월간 달력 — 날짜별 통합 항목 맵 ──────────────────────
const MONTH_MAX_ITEMS = 2

const monthDayEntriesMap = computed(() => {
  const map = {}
  for (const user of monthlyUsers.value) {
    const userSchedules = monthlySchedules[user.id] ?? {}
    for (const [date, content] of Object.entries(userSchedules)) {
      if (!content?.trim()) continue
      if (!map[date]) map[date] = []
      // parsedCell은 QUICK_TAGS/STATUS_STYLE_MAP 정의 후에 호출되지만
      // computed 평가 시점엔 이미 정의되어 있으므로 안전
      const parsed = parsedCell(content)
      for (const slot of parsed.slots) {
        map[date].push({ type: 'slot', userName: user.name, userId: user.id, time: slot.time, status: slot.status, sites: slot.sites, content: slot.content ?? '' })
      }
      // 구버전 별도 content는 종일 슬롯으로 통합해서 표시
      if (parsed.content) {
        map[date].push({ type: 'slot', userName: user.name, userId: user.id, time: '종일', status: '', sites: [], content: parsed.content })
      }
    }
  }
  return map
})

// ── 일별 상세 모달 ─────────────────────────────────────
const showDayModal  = ref(false)
const dayModalDate  = ref('')
const openDayModal  = (date) => { dayModalDate.value = date; showDayModal.value = true }

// 카테고리별 그룹핑 (모달용)
// 상태(외근/출장/반차/휴가) 선택 시 해당 그룹, 미선택 시 모두 기타 일정으로 분류
const dayModalGroups = computed(() => {
  const entries = monthDayEntriesMap.value[dayModalDate.value] ?? []
  const groups  = {}
  for (const entry of entries) {
    if (entry.status && STATUS_STYLE_MAP[entry.status]) {
      // 상태 있는 경우 → 해당 카테고리 그룹
      if (!groups[entry.status]) groups[entry.status] = []
      groups[entry.status].push(entry)
    } else {
      // 상태 없는 경우 → 사이트/내용 무관하게 모두 기타 일정
      if (!groups['__etc']) groups['__etc'] = []
      groups['__etc'].push(entry)
    }
  }
  return groups
})

const fmtDayModalDate = (date) => {
  if (!date) return ''
  const d    = new Date(date + 'T00:00:00')
  const days = ['일', '월', '화', '수', '목', '금', '토']
  const mm   = String(d.getMonth() + 1).padStart(2, '0')
  const dd   = String(d.getDate()).padStart(2, '0')
  return `${mm}/${dd} (${days[d.getDay()]})`
}

// ── 빠른 선택 태그 & 색상 맵 ────────────────────────────
const QUICK_TAGS = [
  { label: '외근', icon: '🏢', bg: '#DBEAFE', color: '#1D4ED8', border: '#93C5FD' },
  { label: '출장', icon: '✈️', bg: '#EDE9FE', color: '#7C3AED', border: '#C4B5FD' },
  { label: '반차', icon: '🕐', bg: '#FEF9C3', color: '#854D0E', border: '#FDE68A' },
  { label: '휴가', icon: '🌴', bg: '#DCFCE7', color: '#166534', border: '#86EFAC' },
]
const STATUS_STYLE_MAP = Object.fromEntries(QUICK_TAGS.map(t => [t.label, t]))
const STATUS_LABELS    = QUICK_TAGS.map(t => t.label)
const TIME_ORDER       = ['종일', '오전', '오후']

// ── 셀 내용 파싱 — 신규 포맷: [시간]상태:사이트1,사이트2\n내용 ─────────
// 구버전(상태:사이트\n상세) 호환 지원
const parsedCell = (text) => {
  if (!text?.trim()) return { slots: [], content: '' }
  const lines = text.trim().split('\n')
  const slots = []
  const contentLines = []

  // 신규 포맷 여부 확인 ([...] 패턴)
  const hasNewFmt = lines.some(l => /^\[[^\]]+\]/.test(l.trim()))

  if (hasNewFmt) {
    for (const line of lines) {
      const trimmed = line.trim()
      if (!trimmed) continue
      const m = trimmed.match(/^\[([^\]]+)\](.*)$/)
      if (m) {
        const time = m[1]
        const rest = m[2]
        // | 뒤는 슬롯 내용(content)
        const pipeIdx = rest.indexOf('|')
        let mainPart = rest, slotContent = ''
        if (pipeIdx >= 0) {
          mainPart    = rest.substring(0, pipeIdx)
          slotContent = rest.substring(pipeIdx + 1).trim()
        }
        const ci   = mainPart.indexOf(':')
        let status = '', sites = []
        if (ci >= 0) {
          status = mainPart.substring(0, ci).trim()
          sites  = mainPart.substring(ci + 1).split(',').map(s => s.trim()).filter(Boolean)
        } else {
          status = mainPart.trim()
        }
        slots.push({ time, status, sites, content: slotContent })
      } else {
        contentLines.push(trimmed)
      }
    }
  } else {
    // 구버전 파싱 → 종일 슬롯으로 변환
    const header = lines[0]?.trim() ?? ''
    const ci = header.indexOf(':')
    let legacyStatus = '', legacySites = [], detailStart = 1

    if (ci === 0) {
      legacySites = header.substring(1).split(',').map(s => s.trim()).filter(Boolean)
    } else if (ci > 0) {
      const before = header.substring(0, ci).split(',').map(s => s.trim()).filter(Boolean)
      if (before.every(s => STATUS_LABELS.includes(s))) {
        legacyStatus = before[0] ?? ''
        legacySites  = header.substring(ci + 1).split(',').map(s => s.trim()).filter(Boolean)
      } else { detailStart = 0 }
    } else {
      const parts = header.split(',').map(s => s.trim()).filter(Boolean)
      if (parts.length && parts.every(s => STATUS_LABELS.includes(s))) {
        legacyStatus = parts[0] ?? ''
      } else if (STATUS_LABELS.includes(header)) {
        legacyStatus = header
      } else {
        detailStart = 0
      }
    }

    if (legacyStatus || legacySites.length) {
      slots.push({ time: '종일', status: legacyStatus, sites: legacySites, content: '' })
    }
    for (let i = detailStart; i < lines.length; i++) {
      const t = lines[i].trim()
      if (t) contentLines.push(t)
    }
  }

  return { slots, content: contentLines.join(' ').trim() }
}

// ── 저장 내용 빌드 — 신규 포맷 ───────────────────────────────────────────
const buildContent = () => {
  const lines = []
  for (const time of TIME_ORDER) {
    const slot = modalAllSlots[time]
    const c    = slot.content.replace(/\n/g, ' ').trim()
    if (slot.status || slot.sites.length || c) {
      const sitePart = slot.sites.join(',')
      let line = `[${time}]${slot.status}`
      if (sitePart) line += ':' + sitePart
      if (c)        line += '|' + c
      lines.push(line)
    }
  }
  return lines.join('\n')
}

// ── 모달 상태 ──────────────────────────────────────────
const showModal    = ref(false)
const modalDate    = ref(null)
const modalTime    = ref('종일')   // 현재 편집 중인 시간대
const modalAllSlots = reactive({
  '종일': { status: '', sites: [], content: '' },
  '오전': { status: '', sites: [], content: '' },
  '오후': { status: '', sites: [], content: '' },
})
const modalSaving  = ref(false)
const saveDone     = ref(false)
let saveDoneTimer  = null

// 상태 단일 선택 토글 (현재 시간대)
const selectStatus = (label) => {
  const slot = modalAllSlots[modalTime.value]
  slot.status = slot.status === label ? '' : label
}
// 사이트 토글 (현재 시간대)
const selectSite = (site) => {
  const slot = modalAllSlots[modalTime.value]
  const idx  = slot.sites.indexOf(site)
  if (idx === -1) slot.sites.push(site)
  else            slot.sites.splice(idx, 1)
}

const resetSlots = () => {
  for (const t of TIME_ORDER) {
    modalAllSlots[t].status  = ''
    modalAllSlots[t].sites   = []
    modalAllSlots[t].content = ''
  }
}

const openModal = (date, content) => {
  const defaultDate = allDates.value.includes(today) ? today : props.currDates[0]
  modalDate.value = date ?? defaultDate

  const raw    = content ?? (date ? (localSchedules[props.currentUserId]?.[date] ?? '') : '')
  const parsed = parsedCell(raw)

  resetSlots()
  for (const slot of parsed.slots) {
    if (modalAllSlots[slot.time]) {
      modalAllSlots[slot.time].status  = slot.status
      modalAllSlots[slot.time].sites   = [...slot.sites]
      modalAllSlots[slot.time].content = slot.content ?? ''
    }
  }
  // 구버전 별도 내용 → 종일 슬롯에 병합 (하위 호환)
  if (parsed.content && !modalAllSlots['종일'].content) {
    modalAllSlots['종일'].content = parsed.content
  }
  modalTime.value    = '종일'
  showModal.value    = true
}

const closeModal = () => {
  showModal.value = false
  modalDate.value = null
  modalTime.value = '종일'
  resetSlots()
}

const saveModal = async () => {
  if (!modalDate.value || modalSaving.value) return
  modalSaving.value = true
  const content = buildContent()
  try {
    await window.axios.post('/schedules/upsert', {
      date:    modalDate.value,
      content: content || null,
    })
    if (!localSchedules[props.currentUserId]) localSchedules[props.currentUserId] = {}
    localSchedules[props.currentUserId][modalDate.value] = content
    // 월간 달력도 즉시 반영
    refreshMonthlySchedule(modalDate.value, content)

    clearTimeout(saveDoneTimer)
    saveDone.value = true
    saveDoneTimer  = setTimeout(() => { saveDone.value = false }, 2200)
    closeModal()
  } catch (e) {
    console.error('일정 저장 실패', e)
  } finally {
    modalSaving.value = false
  }
}

const deleteSchedule = async () => {
  if (!modalDate.value) return
  modalSaving.value = true
  try {
    await window.axios.post('/schedules/upsert', {
      date:    modalDate.value,
      content: null,
    })
    if (localSchedules[props.currentUserId]) {
      localSchedules[props.currentUserId][modalDate.value] = ''
    }
    clearTimeout(saveDoneTimer)
    saveDone.value = true
    saveDoneTimer  = setTimeout(() => { saveDone.value = false }, 2200)
    closeModal()
  } catch (e) {
    console.error('일정 삭제 실패', e)
  } finally {
    modalSaving.value = false
  }
}
</script>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.25s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(12px); }
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.2s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
.team-collapse-enter-active, .team-collapse-leave-active {
  transition: opacity 0.2s ease, max-height 0.25s ease;
  max-height: 600px; overflow: hidden;
}
.team-collapse-enter-from, .team-collapse-leave-to { opacity: 0; max-height: 0; }
</style>
