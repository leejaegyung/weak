<template>
  <AppLayout page-title="보고서 상세">

    <!-- 팀원 보고서 스위처 (관리자 또는 팀원 전체 조회 가능한 경우) -->
    <div v-if="teamUsers.length > 1"
      style="display:flex;gap:6px;overflow-x:auto;padding-bottom:4px;margin-bottom:16px;scrollbar-width:none;">
      <div v-for="u in teamUsers" :key="u.id"
        @click="u.report_id && router.get(`/reports/${u.report_id}`)"
        :title="u.report_id ? u.name + ' 보고서 보기' : u.name + ' — 미제출'"
        :style="{
          display:'flex', alignItems:'center', gap:'6px',
          padding:'5px 12px', borderRadius:'10px', flexShrink:0,
          border:'2px solid',
          borderColor: u.id === report.user_id ? '#1A1100' : (u.report_id ? '#D0C9BC' : '#FDE68A'),
          background: u.id === report.user_id ? '#FDCB40' : (u.report_id ? '#fff' : '#FFF9E6'),
          cursor: u.report_id && u.id !== report.user_id ? 'pointer' : 'default',
          fontWeight: u.id === report.user_id ? '800' : '600',
          fontSize: '12px',
          boxShadow: u.id === report.user_id ? '2px 2px 0 #1A1100' : 'none',
          transition:'all 0.12s',
        }"
        @mouseenter="e=>{ if(u.report_id && u.id !== report.user_id) { e.currentTarget.style.background='#FFF8EE'; e.currentTarget.style.borderColor='#1A1100'; } }"
        @mouseleave="e=>{ if(u.report_id && u.id !== report.user_id) { e.currentTarget.style.background='#fff'; e.currentTarget.style.borderColor='#D0C9BC'; } }">
        <!-- 아바타 -->
        <div :style="{ width:'20px', height:'20px', borderRadius:'50%', background: avatarColor(u.id), border:'1.5px solid #1A1100', display:'flex', alignItems:'center', justifyContent:'center', color:'#fff', fontSize:'10px', fontWeight:'700', flexShrink:0 }">
          {{ u.name.charAt(0) }}
        </div>
        {{ u.name }}
        <!-- 미제출 표시 -->
        <span v-if="!u.report_id" style="font-size:9px;color:#92400E;background:#FFF0A0;border:1px solid #D97706;border-radius:4px;padding:0 4px;">미제출</span>
      </div>
    </div>

    <!-- 상단 액션 바 -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:8px;">
      <Link href="/reports" class="btn-secondary" style="display:inline-flex;align-items:center;gap:5px;">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
        목록으로
      </Link>
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <span :class="statusBadge(report.status)" style="font-size:13px;padding:5px 14px;">{{ report.status_label }}</span>

        <!-- 반려됨 안내 + 사유 -->
        <span v-if="report.status === 'rejected'"
          style="font-size:12px;color:#DC2626;font-weight:600;display:flex;flex-direction:column;gap:2px;">
          <span>⚠ 반려된 보고서입니다. 수정 후 재제출하세요.</span>
          <span v-if="report.reject_reason"
            style="font-size:11px;color:#7F1D1D;background:#FEE2E2;border-radius:6px;padding:2px 8px;font-weight:500;">
            사유: {{ report.reject_reason }}
          </span>
        </span>

        <!-- 삭제 버튼 (관리자 또는 본인) -->
        <button v-if="isOwn || isAdmin" @click="showDeleteModal=true"
          style="display:inline-flex;align-items:center;gap:5px;background:#FEE2E2;color:#DC2626;border:2px solid #DC2626;border-radius:10px;padding:6px 12px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:2px 2px 0 #DC2626;transition:all 0.1s;"
          @mouseenter="e=>{e.currentTarget.style.transform='translate(-1px,-1px)';e.currentTarget.style.boxShadow='3px 3px 0 #DC2626';}"
          @mouseleave="e=>{e.currentTarget.style.transform='none';e.currentTarget.style.boxShadow='2px 2px 0 #DC2626';}">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
          </svg>
          삭제
        </button>

        <!-- 관리자: submitted → 반려 -->
        <button v-if="isAdmin && report.status === 'submitted'" @click="showRejectModal=true"
          style="background:#FEE2E2;color:#DC2626;border:2px solid #DC2626;border-radius:10px;padding:6px 14px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:2px 2px 0 #DC2626;transition:all 0.1s;"
          @mouseenter="e=>{e.currentTarget.style.transform='translate(-1px,-1px)';e.currentTarget.style.boxShadow='3px 3px 0 #DC2626';}"
          @mouseleave="e=>{e.currentTarget.style.transform='none';e.currentTarget.style.boxShadow='2px 2px 0 #DC2626';}">
          ✕ 반려
        </button>

<!-- 수정 버튼: 본인이거나 관리자면 항상 표시 -->
        <Link v-if="isOwn || isAdmin" :href="`/reports/${report.id}/edit`"
          class="btn-secondary btn-sm"
          style="display:inline-flex;align-items:center;gap:5px;">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
          </svg>
          수정
        </Link>

        <!-- 본인: 초안/반려 → 제출 -->
        <template v-if="isOwn && (report.status === 'draft' || report.status === 'rejected')">
          <button @click="submit" class="btn-primary btn-sm">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
            {{ report.status === 'rejected' ? '재제출하기' : '제출하기' }}
          </button>
        </template>
      </div>
    </div>

    <!-- 보고서 본문 (Excel 스타일) -->
    <div class="card" style="overflow:hidden;padding:0;">

      <!-- 보고서 헤더 -->
      <div style="display:flex;justify-content:space-between;align-items:stretch;border-bottom:2px solid #1A1100;background:#fff;">
        <div style="padding:16px 22px;display:flex;align-items:center;gap:12px;">
          <div style="background:#FDCB40;border:2px solid #1A1100;border-radius:8px;width:36px;height:36px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M16 13H8M16 17H8M10 9H8"/>
            </svg>
          </div>
          <div>
            <h2 style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:18px;font-weight:800;letter-spacing:-0.02em;">주간업무보고</h2>
            <p style="font-size:11px;color:#9A8F7A;margin-top:2px;">{{ report.week_label || report.week }}</p>
          </div>
        </div>
        <!-- 오른쪽 정보 격자 -->
        <table style="border-collapse:collapse;border-left:2px solid #1A1100;font-size:12px;">
          <tr>
            <td style="padding:7px 14px;background:#F5EDDB;border-right:2px solid #1A1100;border-bottom:2px solid #1A1100;font-weight:700;color:#9A8F7A;white-space:nowrap;">전주</td>
            <td style="padding:7px 16px;border-right:2px solid #1A1100;border-bottom:2px solid #1A1100;white-space:nowrap;">{{ fmtRange(report.curr_start, report.curr_end) }}</td>
            <td style="padding:7px 14px;background:#F5EDDB;border-right:2px solid #1A1100;border-bottom:2px solid #1A1100;font-weight:700;color:#9A8F7A;">이름</td>
            <td style="padding:7px 16px;border-bottom:2px solid #1A1100;font-weight:700;">{{ report.user?.name ?? '-' }}</td>
          </tr>
          <tr>
            <td style="padding:7px 14px;background:#F5EDDB;border-right:2px solid #1A1100;font-weight:700;color:#9A8F7A;">금주</td>
            <td style="padding:7px 16px;border-right:2px solid #1A1100;">{{ fmtRange(report.next_start, report.next_end) }}</td>
            <td style="padding:7px 14px;background:#F5EDDB;border-right:2px solid #1A1100;font-weight:700;color:#9A8F7A;">직급</td>
            <td style="padding:7px 16px;">{{ report.user?.position ?? '사원' }}</td>
          </tr>
        </table>
      </div>

      <!-- 본문 테이블 -->
      <table style="width:100%;border-collapse:collapse;">
        <thead>
          <tr style="background:#F5EDDB;border-bottom:2px solid #1A1100;">
            <th style="width:72px;padding:10px 8px;text-align:center;font-size:11px;font-weight:700;color:#9A8F7A;border-right:2px solid #1A1100;font-family:'Space Grotesk','Noto Sans KR',sans-serif;letter-spacing:0.04em;">구분</th>
            <th style="width:50%;padding:10px 16px;text-align:center;font-size:12px;font-weight:700;color:#1A1100;border-right:2px solid #1A1100;font-family:'Space Grotesk','Noto Sans KR',sans-serif;">
              전주 업무
              <span style="font-weight:400;color:#9A8F7A;font-size:11px;margin-left:4px;">({{ fmtShort(report.curr_start) }} ~ {{ fmtShort(report.curr_end) }})</span>
            </th>
            <th style="padding:10px 16px;text-align:center;font-size:12px;font-weight:700;color:#1A1100;font-family:'Space Grotesk','Noto Sans KR',sans-serif;">
              금주 업무
              <span style="font-weight:400;color:#9A8F7A;font-size:11px;margin-left:4px;">({{ fmtShort(report.next_start) }} ~ {{ fmtShort(report.next_end) }})</span>
            </th>
          </tr>
        </thead>
        <tbody>
          <!-- 지원 행: 전주 / 금주 2열 -->
          <tr style="border-bottom:2px solid #1A1100;">
            <td style="padding:12px 8px;text-align:center;font-size:12px;font-weight:700;background:#F5EDDB;border-right:2px solid #1A1100;vertical-align:top;white-space:nowrap;">지원</td>
            <!-- 전주 -->
            <td style="padding:12px 16px;vertical-align:top;border-right:2px solid #1A1100;min-width:200px;">
              <div v-if="currByCategory['지원']?.length" style="display:flex;flex-direction:column;gap:10px;">
                <div v-for="(item, i) in currByCategory['지원']" :key="i">
                  <div style="display:flex;gap:7px;align-items:flex-start;">
                    <span style="font-size:11px;color:#9A8F7A;font-family:'Space Grotesk',sans-serif;font-weight:700;flex-shrink:0;margin-top:2px;min-width:14px;">{{ i+1 }}.</span>
                    <span style="font-size:13px;line-height:1.65;color:#1A1100;font-weight:700;">{{ item.title || item.content }}</span>
                  </div>
                  <div v-if="item.sub_items?.length" style="margin-left:0;margin-top:4px;display:flex;flex-direction:column;gap:3px;">
                    <template v-for="(sub, si) in item.sub_items" :key="si">
                      <div style="display:flex;gap:6px;align-items:flex-start;">
                        <span style="color:#9A8F7A;flex-shrink:0;font-size:11px;">-</span>
                        <span style="color:#4A3F2A;font-size:11px;line-height:1.5;white-space:pre-wrap;" v-html="autoLink(typeof sub === 'string' ? sub : sub.content)"></span>
                      </div>
                      <template v-if="typeof sub !== 'string' && sub.details && sub.details.length">
                        <div v-for="(detail, di) in sub.details" :key="di" style="display:flex;gap:6px;align-items:flex-start;margin-left:16px;">
                          <span style="color:#C5BAA8;flex-shrink:0;font-size:11px;">└</span>
                          <span style="color:#6B5E4A;font-size:11px;line-height:1.5;white-space:pre-wrap;" v-html="autoLink(detail)"></span>
                        </div>
                      </template>
                    </template>
                  </div>
                </div>
              </div>
              <span v-else style="color:#D0C9BC;font-size:13px;">-</span>
            </td>
            <!-- 금주 -->
            <td style="padding:12px 16px;vertical-align:top;min-width:200px;">
              <div v-if="nextByCategory['지원']?.length" style="display:flex;flex-direction:column;gap:10px;">
                <div v-for="(item, i) in nextByCategory['지원']" :key="i">
                  <div style="display:flex;gap:7px;align-items:flex-start;">
                    <span style="font-size:11px;color:#9A8F7A;font-family:'Space Grotesk',sans-serif;font-weight:700;flex-shrink:0;margin-top:2px;min-width:14px;">{{ i+1 }}.</span>
                    <span style="font-size:13px;line-height:1.65;color:#1A1100;font-weight:700;">{{ item.title || item.content }}</span>
                  </div>
                  <div v-if="item.sub_items?.length" style="margin-left:0;margin-top:4px;display:flex;flex-direction:column;gap:3px;">
                    <template v-for="(sub, si) in item.sub_items" :key="si">
                      <div style="display:flex;gap:6px;align-items:flex-start;">
                        <span style="color:#9A8F7A;flex-shrink:0;font-size:11px;">-</span>
                        <span style="color:#4A3F2A;font-size:11px;line-height:1.5;white-space:pre-wrap;" v-html="autoLink(typeof sub === 'string' ? sub : sub.content)"></span>
                      </div>
                      <template v-if="typeof sub !== 'string' && sub.details && sub.details.length">
                        <div v-for="(detail, di) in sub.details" :key="di" style="display:flex;gap:6px;align-items:flex-start;margin-left:16px;">
                          <span style="color:#C5BAA8;flex-shrink:0;font-size:11px;">└</span>
                          <span style="color:#6B5E4A;font-size:11px;line-height:1.5;white-space:pre-wrap;" v-html="autoLink(detail)"></span>
                        </div>
                      </template>
                    </template>
                  </div>
                </div>
              </div>
              <span v-else style="color:#D0C9BC;font-size:13px;">-</span>
            </td>
          </tr>

          <!-- 내부작업 행 -->
          <tr style="border-bottom:2px solid #1A1100;">
            <td style="padding:12px 8px;text-align:center;font-size:12px;font-weight:700;background:#F5EDDB;border-right:2px solid #1A1100;vertical-align:top;white-space:nowrap;">내부작업</td>
            <td colspan="2" style="padding:12px 16px;vertical-align:top;">
              <div v-if="currByCategory['내부작업']?.length" style="display:flex;flex-direction:column;gap:10px;">
                <div v-for="(item, i) in currByCategory['내부작업']" :key="i">
                  <div style="display:flex;gap:7px;align-items:flex-start;">
                    <span style="font-size:11px;color:#9A8F7A;font-family:'Space Grotesk',sans-serif;font-weight:700;flex-shrink:0;margin-top:2px;min-width:14px;">{{ i+1 }}.</span>
                    <span style="font-size:13px;line-height:1.65;color:#1A1100;font-weight:700;">{{ item.title || item.content }}</span>
                  </div>
                  <div v-if="item.sub_items?.length" style="margin-left:0;margin-top:4px;display:flex;flex-direction:column;gap:3px;">
                    <template v-for="(sub, si) in item.sub_items" :key="si">
                      <div style="display:flex;gap:6px;align-items:flex-start;">
                        <span style="color:#9A8F7A;flex-shrink:0;font-size:11px;">-</span>
                        <span style="color:#4A3F2A;font-size:11px;line-height:1.5;white-space:pre-wrap;" v-html="autoLink(typeof sub === 'string' ? sub : sub.content)"></span>
                      </div>
                      <template v-if="typeof sub !== 'string' && sub.details && sub.details.length">
                        <div v-for="(detail, di) in sub.details" :key="di" style="display:flex;gap:6px;align-items:flex-start;margin-left:16px;">
                          <span style="color:#C5BAA8;flex-shrink:0;font-size:11px;">└</span>
                          <span style="color:#6B5E4A;font-size:11px;line-height:1.5;white-space:pre-wrap;" v-html="autoLink(detail)"></span>
                        </div>
                      </template>
                    </template>
                  </div>
                </div>
              </div>
              <span v-else style="color:#D0C9BC;font-size:13px;">-</span>
            </td>
          </tr>

          <!-- Todo 행 -->
          <tr style="border-bottom:2px solid #1A1100;">
            <td style="padding:12px 8px;text-align:center;font-size:12px;font-weight:700;background:#F5EDDB;border-right:2px solid #1A1100;vertical-align:top;">Todo</td>
            <td colspan="2" style="padding:12px 16px;vertical-align:top;">
              <div v-if="report.todo_items?.length" style="display:flex;flex-direction:column;gap:8px;">
                <div v-for="(t, i) in report.todo_items" :key="i">
                  <div style="display:flex;gap:8px;align-items:flex-start;">
                    <span style="font-size:15px;flex-shrink:0;margin-top:1px;"
                      :style="{ color: t.done ? '#16A34A' : '#9A8F7A' }">{{ t.done ? '☑' : '☐' }}</span>
                    <span style="font-size:13px;line-height:1.5;white-space:pre-wrap;word-break:break-word;font-weight:700;"
                      :style="{ textDecoration: t.done ? 'line-through' : 'none', color: t.done ? '#9A8F7A' : '#1A1100' }">{{ t.content }}</span>
                  </div>
                  <div v-if="t.sub_items?.length" style="margin-left:24px;margin-top:3px;display:flex;flex-direction:column;gap:2px;">
                    <div v-for="(sub, si) in t.sub_items" :key="si" style="display:flex;gap:6px;align-items:flex-start;">
                      <span style="color:#9A8F7A;flex-shrink:0;font-size:12px;margin-top:1px;">-</span>
                      <span style="font-size:12px;line-height:1.5;color:#4A3F2A;white-space:pre-wrap;word-break:break-word;">{{ typeof sub === 'string' ? sub : sub?.content }}</span>
                    </div>
                  </div>
                </div>
              </div>
              <span v-else style="color:#D0C9BC;font-size:13px;">-</span>
            </td>
          </tr>

          <!-- 공유 행 -->
          <tr style="border-bottom:2px solid #1A1100;">
            <td style="padding:12px 8px;text-align:center;font-size:12px;font-weight:700;background:#F5EDDB;border-right:2px solid #1A1100;vertical-align:top;white-space:nowrap;">공유</td>
            <td colspan="2" style="padding:12px 16px;vertical-align:top;">
              <div v-if="currByCategory['공유']?.length" style="display:flex;flex-direction:column;gap:10px;">
                <div v-for="(item, i) in currByCategory['공유']" :key="i">
                  <div style="display:flex;gap:7px;align-items:flex-start;">
                    <span style="font-size:11px;color:#9A8F7A;font-family:'Space Grotesk',sans-serif;font-weight:700;flex-shrink:0;margin-top:2px;min-width:14px;">{{ i+1 }}.</span>
                    <span style="font-size:13px;line-height:1.65;color:#1A1100;font-weight:700;">{{ item.title || item.content }}</span>
                  </div>
                  <div v-if="item.sub_items?.length" style="margin-left:0;margin-top:4px;display:flex;flex-direction:column;gap:3px;">
                    <template v-for="(sub, si) in item.sub_items" :key="si">
                      <div style="display:flex;gap:6px;align-items:flex-start;">
                        <span style="color:#9A8F7A;flex-shrink:0;font-size:11px;">-</span>
                        <span style="color:#4A3F2A;font-size:11px;line-height:1.5;white-space:pre-wrap;" v-html="autoLink(typeof sub === 'string' ? sub : sub.content)"></span>
                      </div>
                      <template v-if="typeof sub !== 'string' && sub.details && sub.details.length">
                        <div v-for="(detail, di) in sub.details" :key="di" style="display:flex;gap:6px;align-items:flex-start;margin-left:16px;">
                          <span style="color:#C5BAA8;flex-shrink:0;font-size:11px;">└</span>
                          <span style="color:#6B5E4A;font-size:11px;line-height:1.5;white-space:pre-wrap;" v-html="autoLink(detail)"></span>
                        </div>
                      </template>
                    </template>
                  </div>
                </div>
              </div>
              <span v-else style="color:#D0C9BC;font-size:13px;">-</span>
            </td>
          </tr>

          <!-- 기타 행 -->
          <tr style="border-bottom:2px solid #1A1100;">
            <td style="padding:12px 8px;text-align:center;font-size:12px;font-weight:700;background:#F5EDDB;border-right:2px solid #1A1100;vertical-align:top;white-space:nowrap;">기타</td>
            <td colspan="2" style="padding:12px 16px;vertical-align:top;">
              <div v-if="currByCategory['기타']?.length" style="display:flex;flex-direction:column;gap:10px;">
                <div v-for="(item, i) in currByCategory['기타']" :key="i">
                  <div style="display:flex;gap:7px;align-items:flex-start;">
                    <span style="font-size:11px;color:#9A8F7A;font-family:'Space Grotesk',sans-serif;font-weight:700;flex-shrink:0;margin-top:2px;min-width:14px;">{{ i+1 }}.</span>
                    <span style="font-size:13px;line-height:1.65;color:#1A1100;font-weight:700;">{{ item.title || item.content }}</span>
                  </div>
                  <div v-if="item.sub_items?.length" style="margin-left:0;margin-top:4px;display:flex;flex-direction:column;gap:3px;">
                    <template v-for="(sub, si) in item.sub_items" :key="si">
                      <div style="display:flex;gap:6px;align-items:flex-start;">
                        <span style="color:#9A8F7A;flex-shrink:0;font-size:11px;">-</span>
                        <span style="color:#4A3F2A;font-size:11px;line-height:1.5;white-space:pre-wrap;" v-html="autoLink(typeof sub === 'string' ? sub : sub.content)"></span>
                      </div>
                      <template v-if="typeof sub !== 'string' && sub.details && sub.details.length">
                        <div v-for="(detail, di) in sub.details" :key="di" style="display:flex;gap:6px;align-items:flex-start;margin-left:16px;">
                          <span style="color:#C5BAA8;flex-shrink:0;font-size:11px;">└</span>
                          <span style="color:#6B5E4A;font-size:11px;line-height:1.5;white-space:pre-wrap;" v-html="autoLink(detail)"></span>
                        </div>
                      </template>
                    </template>
                  </div>
                </div>
              </div>
              <span v-else style="color:#D0C9BC;font-size:13px;">-</span>
            </td>
          </tr>

          <!-- 특이사항 행 -->
          <tr v-if="report.notes" style="border-bottom:2px solid #1A1100;">
            <td style="padding:12px 8px;text-align:center;font-size:12px;font-weight:700;background:#F5EDDB;border-right:2px solid #1A1100;vertical-align:top;white-space:nowrap;">특이사항</td>
            <td colspan="2" style="padding:12px 16px;font-size:13px;color:#1A1100;white-space:pre-wrap;line-height:1.65;">{{ report.notes }}</td>
          </tr>

          <!-- 요청사항 행 -->
          <tr v-if="report.requests">
            <td style="padding:12px 8px;text-align:center;font-size:12px;font-weight:700;background:#F5EDDB;border-right:2px solid #1A1100;vertical-align:top;white-space:nowrap;">요청사항</td>
            <td colspan="2" style="padding:12px 16px;font-size:13px;color:#1A1100;white-space:pre-wrap;line-height:1.65;">{{ report.requests }}</td>
          </tr>

          <!-- 모든 항목이 없을 때 -->
          <tr v-if="!allCategories.length && !report.todo_items?.length && !report.notes && !report.requests">
            <td colspan="3" style="padding:48px;text-align:center;color:#9A8F7A;font-size:13px;">작성된 내용이 없습니다</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- 제출일 / 검토일 정보 -->
    <div style="display:flex;gap:16px;margin-top:12px;padding:0 4px;">
      <span v-if="report.submitted_at" style="font-size:11px;color:#9A8F7A;">
        제출일: <strong style="color:#4A3F2A;">{{ String(report.submitted_at).substring(0,10) }}</strong>
      </span>
    </div>

    <!-- 삭제 확인 모달 -->
    <div v-if="showDeleteModal"
      style="position:fixed;inset:0;background:rgba(26,17,0,0.45);display:flex;align-items:center;justify-content:center;z-index:100;backdrop-filter:blur(3px);padding:16px;"
      @click.self="showDeleteModal=false">
      <div class="card" style="width:380px;max-width:100%;max-height:90vh;overflow-y:auto;padding:28px;text-align:center;">
        <div style="width:48px;height:48px;background:#FEE2E2;border:2px solid #DC2626;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
          </svg>
        </div>
        <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:16px;font-weight:800;color:#1A1100;margin-bottom:8px;">보고서를 삭제할까요?</div>
        <div style="font-size:13px;color:#9A8F7A;line-height:1.7;margin-bottom:22px;">
          <strong style="color:#1A1100;">{{ report.week }}</strong> 보고서가<br>
          영구적으로 삭제됩니다. 되돌릴 수 없습니다.
        </div>
        <div style="display:flex;gap:8px;justify-content:center;">
          <button @click="showDeleteModal=false" class="btn-secondary">취소</button>
          <button @click="doDelete"
            style="background:#DC2626;color:#fff;border:2px solid #DC2626;border-radius:10px;padding:8px 20px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:2px 2px 0 #991B1B;transition:all 0.1s;">
            삭제하기
          </button>
        </div>
      </div>
    </div>

    <!-- 반려 사유 모달 -->
    <div v-if="showRejectModal"
      style="position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:100;display:flex;align-items:center;justify-content:center;padding:16px;">
      <div style="background:#fff;border:2px solid #1A1100;border-radius:16px;box-shadow:6px 6px 0 #1A1100;padding:28px;max-width:420px;width:100%;max-height:90vh;overflow-y:auto;">
        <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:16px;font-weight:800;color:#DC2626;margin-bottom:6px;">보고서 반려</div>
        <div style="font-size:13px;color:#9A8F7A;margin-bottom:16px;">반려 사유를 입력하면 Webhook 및 카카오 알림으로 전달됩니다.</div>
        <textarea v-model="rejectReason" rows="3" class="input-field"
          placeholder="반려 사유 입력 (선택, 최대 500자)"
          style="width:100%;resize:vertical;font-family:inherit;margin-bottom:16px;"></textarea>
        <div style="display:flex;gap:8px;justify-content:flex-end;">
          <button @click="showRejectModal=false;rejectReason=''" class="btn-secondary">취소</button>
          <button @click="doReject"
            style="background:#DC2626;color:#fff;border:2px solid #DC2626;border-radius:10px;padding:8px 20px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;">
            반려하기
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { autoLink } from '@/utils/autoLink.js'

const props = defineProps({
  report:    { type: Object, required: true },
  teamUsers: { type: Array,  default: () => [] },
})

const page    = usePage()
const isOwn   = computed(() => page.props.auth?.user?.id === props.report.user_id)
const isAdmin = computed(() => page.props.auth?.user?.role === 'admin')

const AVATAR_COLORS = ['#FD4401','#16a34a','#2563eb','#9333ea','#d97706','#0891b2','#dc2626','#65a30d']
const avatarColor = (id) => AVATAR_COLORS[(id ?? 0) % AVATAR_COLORS.length]

const fmt      = (d) => d ? String(d).substring(0, 10) : ''
const fmtShort = (d) => d ? String(d).substring(5, 10).replace('-', '/') : '-'
const fmtRange = (s, e) => `${fmtShort(s)} ~ ${fmtShort(e)}`

const statusBadge = (s) => ({ draft:'badge-draft', submitted:'badge-submitted', reviewed:'badge-reviewed', rejected:'badge-rejected' })[s] ?? 'badge-draft'

// 지원: 2열(전주/금주), 나머지: colspan=2 단일열
const TWO_COL_CAT  = '지원'
const SINGLE_CATS  = ['내부작업', '공유', '기타']

// curr_work, next_plan 를 카테고리별로 그룹화
const currByCategory = computed(() => {
  const map = {}
  for (const item of (props.report.curr_work ?? [])) {
    const cat = item.category ?? '기타'
    if (!map[cat]) map[cat] = []
    map[cat].push(item)
  }
  return map
})

const nextByCategory = computed(() => {
  const map = {}
  for (const item of (props.report.next_plan ?? [])) {
    const cat = item.category ?? '기타'
    if (!map[cat]) map[cat] = []
    map[cat].push(item)
  }
  return map
})

// 단일열에 표시할 카테고리 (데이터에 있는 비고정 포함)
const allSingleCats = computed(() => {
  const known = new Set([TWO_COL_CAT, ...SINGLE_CATS])
  const extra = [...new Set([
    ...Object.keys(currByCategory.value),
    ...Object.keys(nextByCategory.value),
  ])].filter(c => !known.has(c))
  return [...SINGLE_CATS, ...extra]
})

const submit = () => router.post(`/reports/${props.report.id}/submit`)

const showRejectModal = ref(false)
const rejectReason    = ref('')
const doReject = () => {
  router.post(`/reports/${props.report.id}/reject`, { reason: rejectReason.value }, {
    onSuccess: () => { showRejectModal.value = false; rejectReason.value = '' },
  })
}

const allCategories = computed(() => [
  ...Object.keys(currByCategory.value),
  ...Object.keys(nextByCategory.value),
])

const showDeleteModal = ref(false)
const doDelete = () => router.delete(`/reports/${props.report.id}`)
</script>
