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

    <!-- 팀 일정 그리드 -->
    <div class="card" style="overflow:auto;padding:0;">
      <table style="width:100%;border-collapse:collapse;min-width:900px;">
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
                minWidth: '80px',
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
                  <div :style="{ width:'28px', height:'28px', borderRadius:'50%', background: avatarColor(user.id), border:'2px solid #1A1100', display:'flex', alignItems:'center', justifyContent:'center', color:'#fff', fontSize:'11px', fontWeight:'700', flexShrink:0, fontFamily:'\'Space Grotesk\',sans-serif' }">
                    {{ user.name.charAt(0) }}
                  </div>
                  <div>
                    <div :style="{ fontSize:'12px', fontWeight:'700', textDecoration: weekReportMap[user.id] ? 'underline' : 'none', textDecorationColor:'#9A8F7A', textUnderlineOffset:'2px' }">{{ user.name }}</div>
                    <div v-if="user.position" style="font-size:10px;color:#9A8F7A;">{{ user.position }}</div>
                  </div>
                  <span v-if="weekReportMap[user.id]"
                    style="font-size:9px;background:#DBEAFE;color:#1D6FE9;border:1px solid #1D6FE9;border-radius:4px;padding:1px 5px;font-weight:700;flex-shrink:0;">
                    보고서
                  </span>
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
                minWidth:'80px',
                cursor: user.id === currentUserId ? 'pointer' : 'default',
                transition:'background 0.1s',
                position:'relative',
              }"
              @mouseenter="e=>{ if(user.id === currentUserId) e.currentTarget.style.background = isToday(date) ? '#FFF0A0' : '#FFFBF0'; }"
              @mouseleave="e=>{ e.currentTarget.style.background = isToday(date) ? '#FFF0A0' : 'transparent'; }">

              <!-- 내용 표시 -->
              <div style="min-height:48px;font-size:11.5px;color:#1A1100;line-height:1.6;white-space:pre-wrap;word-break:break-word;padding:3px 4px;">
                {{ localSchedules[user.id]?.[date] || '' }}
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
        style="position:fixed;inset:0;background:rgba(26,17,0,0.5);display:flex;align-items:center;justify-content:center;z-index:300;backdrop-filter:blur(4px);"
        @click.self="closeModal">
        <div class="card" style="width:460px;max-width:95vw;padding:0;overflow:hidden;">
          <!-- 모달 헤더 -->
          <div style="padding:16px 20px;background:#F5EDDB;border-bottom:2px solid #1A1100;display:flex;justify-content:space-between;align-items:center;">
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
          <div style="padding:22px 24px;display:flex;flex-direction:column;gap:18px;">

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

            <!-- 내용 입력 -->
            <div>
              <label style="font-size:12px;font-weight:700;color:#9A8F7A;display:block;margin-bottom:10px;letter-spacing:0.04em;text-transform:uppercase;">
                일정 내용
                <span v-if="modalDate" style="font-weight:600;color:#1A1100;text-transform:none;font-size:12px;margin-left:6px;">
                  — {{ modalDate.substring(5).replace('-','/') }} ({{ DAY_KR[allDates.indexOf(modalDate) % 5] }})
                </span>
              </label>

              <!-- 빠른 선택 태그 -->
              <div style="display:flex;gap:7px;flex-wrap:wrap;margin-bottom:10px;">
                <button v-for="tag in QUICK_TAGS" :key="tag.label" type="button"
                  @click="toggleQuickTag(tag)"
                  :style="{
                    display:'inline-flex', alignItems:'center', gap:'5px',
                    padding:'5px 13px', borderRadius:'20px', fontSize:'12px', fontWeight:'700',
                    border: isTagActive(tag) ? '2px solid #1A1100' : '2px solid #D0C9BC',
                    background: isTagActive(tag) ? tag.bg : '#fff',
                    color: isTagActive(tag) ? tag.color : '#9A8F7A',
                    cursor:'pointer', fontFamily:'inherit', transition:'all 0.12s',
                    boxShadow: isTagActive(tag) ? '2px 2px 0 #1A1100' : 'none',
                  }"
                  @mouseenter="e=>{ if(!isTagActive(tag)){ e.currentTarget.style.borderColor='#9A8F7A'; e.currentTarget.style.color='#4A3F2A'; } }"
                  @mouseleave="e=>{ if(!isTagActive(tag)){ e.currentTarget.style.borderColor='#D0C9BC'; e.currentTarget.style.color='#9A8F7A'; } }">
                  <span>{{ tag.icon }}</span>
                  {{ tag.label }}
                </button>
              </div>

              <textarea
                v-model="modalContent"
                rows="3"
                class="input-field"
                placeholder="이 날의 일정을 직접 입력하거나 위 태그를 선택하세요&#10;(비워두면 해당 날짜 일정이 삭제됩니다)"
                style="resize:vertical;line-height:1.65;font-size:13px;"
                @keydown.meta.enter.prevent="saveModal"
                @keydown.ctrl.enter.prevent="saveModal"
              ></textarea>
              <p style="font-size:11px;color:#9A8F7A;margin-top:6px;">Ctrl+Enter 또는 ⌘+Enter로 빠르게 저장</p>
            </div>
          </div>

          <!-- 모달 푸터 -->
          <div style="padding:14px 24px;background:#F5EDDB;border-top:2px solid #1A1100;display:flex;justify-content:space-between;align-items:center;">
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
})

const DAY_KR = ['월', '화', '수', '목', '금']

const AVATAR_COLORS = ['#FD4401','#16a34a','#2563eb','#9333ea','#d97706','#0891b2','#dc2626','#65a30d']
const avatarColor = (id) => AVATAR_COLORS[id % AVATAR_COLORS.length]

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

// ── 빠른 선택 태그 ────────────────────────────────────
const QUICK_TAGS = [
  { label: '외근', icon: '🏢', bg: '#DBEAFE', color: '#1D4ED8' },
  { label: '출장', icon: '✈️', bg: '#EDE9FE', color: '#7C3AED' },
  { label: '반차', icon: '🕐', bg: '#FEF9C3', color: '#854D0E' },
  { label: '휴가', icon: '🌴', bg: '#DCFCE7', color: '#166534' },
]

// 현재 내용에 태그가 활성화되어 있는지 확인
const isTagActive = (tag) => {
  const lines = modalContent.value.split('\n').map(l => l.trim())
  return lines.includes(tag.label)
}

// 태그 토글: 없으면 추가, 있으면 제거
const toggleQuickTag = (tag) => {
  const lines = modalContent.value.split('\n').map(l => l.trim()).filter(l => l)
  const idx = lines.indexOf(tag.label)
  if (idx !== -1) {
    // 이미 있으면 제거
    lines.splice(idx, 1)
  } else {
    // 없으면 맨 앞에 추가
    lines.unshift(tag.label)
  }
  modalContent.value = lines.join('\n')
}

// ── 모달 상태 ──────────────────────────────────────────
const showModal    = ref(false)
const modalDate    = ref(null)
const modalContent = ref('')
const modalSaving  = ref(false)
const saveDone     = ref(false)
let saveDoneTimer  = null

const openModal = (date, content) => {
  // 날짜 미지정 시 오늘 날짜(또는 금주 첫날) 기본 선택
  const defaultDate = allDates.value.includes(today) ? today : props.currDates[0]
  modalDate.value    = date ?? defaultDate
  modalContent.value = content ?? (date ? (localSchedules[props.currentUserId]?.[date] ?? '') : '')
  showModal.value    = true
}

const closeModal = () => {
  showModal.value    = false
  modalDate.value    = null
  modalContent.value = ''
}

const saveModal = async () => {
  if (!modalDate.value || modalSaving.value) return
  modalSaving.value = true
  try {
    await window.axios.post('/schedules/upsert', {
      date:    modalDate.value,
      content: modalContent.value,
      // user_id 미전송 → 서버에서 로그인 계정으로 처리
    })
    // 로컬 데이터 즉시 반영
    if (!localSchedules[props.currentUserId]) localSchedules[props.currentUserId] = {}
    localSchedules[props.currentUserId][modalDate.value] = modalContent.value

    // 저장 완료 토스트
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
</style>
