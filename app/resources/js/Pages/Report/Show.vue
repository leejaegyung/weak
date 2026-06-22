<template>
  <AppLayout page-title="보고서 상세">

    <!-- 팀원 보고서 스위처 (관리자 또는 팀원 전체 조회 가능한 경우) -->
    <div v-if="teamUsers.length > 1"
      style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:16px;">
      <div v-for="u in teamUsers" :key="u.id"
        @click="u.report_id && router.get(`/reports/${u.report_id}`)"
        v-tooltip="u.report_id ? u.name + ' 보고서 보기' : u.name + ' — 미제출'"
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
        <div :style="{ width:'20px', height:'20px', borderRadius:'50%', background: u.avatar_image_url ? 'transparent' : avatarColor(u.id, u), border:'1.5px solid #1A1100', display:'flex', alignItems:'center', justifyContent:'center', color:'#fff', fontSize:'10px', fontWeight:'700', flexShrink:0, overflow:'hidden' }">
          <img v-if="u.avatar_image_url" :src="u.avatar_image_url" style="width:100%;height:100%;object-fit:cover;" />
          <template v-else>{{ u.name.charAt(0) }}</template>
        </div>
        {{ u.name }}
        <!-- 미제출 표시 -->
        <span v-if="!u.report_id" style="font-size:9px;color:#92400E;background:#FFF0A0;border:1px solid #D97706;border-radius:4px;padding:0 4px;">미제출</span>
      </div>
    </div>

    <!-- 상단 액션 바 -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:8px;">
      <div style="display:flex;gap:6px;align-items:center;">
        <Link href="/reports" class="btn-secondary" style="display:inline-flex;align-items:center;gap:5px;">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
          목록으로
        </Link>
        <!-- 이전 주 -->
        <Link v-if="prevReportId" :href="`/reports/${prevReportId}`"
          style="display:inline-flex;align-items:center;gap:4px;background:#fff;color:#4A3F2A;border:2px solid #D0C9BC;border-radius:10px;padding:6px 11px;font-size:12px;font-weight:700;text-decoration:none;transition:all 0.12s;"
          @mouseenter="e=>{e.currentTarget.style.borderColor='#1A1100';e.currentTarget.style.background='#F5EDDB';}"
          @mouseleave="e=>{e.currentTarget.style.borderColor='#D0C9BC';e.currentTarget.style.background='#fff';}">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
          이전
        </Link>
        <span v-else style="display:inline-flex;align-items:center;gap:4px;background:#FAFAF8;color:#C5BAA8;border:2px solid #E8E0D0;border-radius:10px;padding:6px 11px;font-size:12px;font-weight:700;cursor:default;">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
          이전
        </span>
        <!-- 금주 -->
        <Link v-if="currentReportId && currentReportId !== report.id" :href="`/reports/${currentReportId}`"
          style="display:inline-flex;align-items:center;gap:4px;background:#FDCB40;color:#1A1100;border:2px solid #1A1100;border-radius:10px;padding:6px 11px;font-size:12px;font-weight:700;text-decoration:none;box-shadow:2px 2px 0 #1A1100;transition:all 0.12s;"
          @mouseenter="e=>{e.currentTarget.style.transform='translate(-1px,-1px)';e.currentTarget.style.boxShadow='3px 3px 0 #1A1100';}"
          @mouseleave="e=>{e.currentTarget.style.transform='none';e.currentTarget.style.boxShadow='2px 2px 0 #1A1100';}">
          금주
        </Link>
        <span v-else-if="currentReportId === report.id"
          style="display:inline-flex;align-items:center;gap:4px;background:#FDCB40;color:#1A1100;border:2px solid #1A1100;border-radius:10px;padding:6px 11px;font-size:12px;font-weight:700;cursor:default;opacity:0.5;">
          금주
        </span>
        <!-- 다음 주 -->
        <Link v-if="nextReportId" :href="`/reports/${nextReportId}`"
          style="display:inline-flex;align-items:center;gap:4px;background:#fff;color:#4A3F2A;border:2px solid #D0C9BC;border-radius:10px;padding:6px 11px;font-size:12px;font-weight:700;text-decoration:none;transition:all 0.12s;"
          @mouseenter="e=>{e.currentTarget.style.borderColor='#1A1100';e.currentTarget.style.background='#F5EDDB';}"
          @mouseleave="e=>{e.currentTarget.style.borderColor='#D0C9BC';e.currentTarget.style.background='#fff';}">
          다음
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
        </Link>
        <span v-else style="display:inline-flex;align-items:center;gap:4px;background:#FAFAF8;color:#C5BAA8;border:2px solid #E8E0D0;border-radius:10px;padding:6px 11px;font-size:12px;font-weight:700;cursor:default;">
          다음
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
        </span>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <!-- 글자 크기 개인화 (기기별 저장) -->
        <div style="display:inline-flex;align-items:center;gap:2px;background:#fff;border:2px solid #1A1100;border-radius:10px;padding:2px;"
          v-tooltip="'보고서 글자 크기 조절 (이 브라우저에 저장됩니다)'">
          <button @click="changeFontScale(-FONT_SCALE_STEP)" :disabled="fontScale <= FONT_SCALE_MIN"
            :style="{
              display:'inline-flex', alignItems:'center', justifyContent:'center',
              width:'26px', height:'24px', border:'none', borderRadius:'7px', background:'transparent',
              cursor: fontScale <= FONT_SCALE_MIN ? 'not-allowed' : 'pointer',
              color: fontScale <= FONT_SCALE_MIN ? '#D0C9BC' : '#1A1100', fontFamily:'inherit',
            }"
            @mouseenter="e=>{ if(fontScale > FONT_SCALE_MIN) e.currentTarget.style.background='#F5EDDB' }"
            @mouseleave="e=>e.currentTarget.style.background='transparent'">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/></svg>
          </button>
          <button @click="resetFontScale" v-tooltip="'기본값(100%)으로'"
            style="min-width:42px;height:24px;border:none;background:transparent;cursor:pointer;font-family:'Space Grotesk',sans-serif;font-size:12px;font-weight:700;color:#9A8F7A;padding:0 4px;border-radius:7px;"
            @mouseenter="e=>{e.currentTarget.style.background='#F5EDDB';e.currentTarget.style.color='#1A1100';}"
            @mouseleave="e=>{e.currentTarget.style.background='transparent';e.currentTarget.style.color='#9A8F7A';}">
            {{ fontScalePercent }}%
          </button>
          <button @click="changeFontScale(FONT_SCALE_STEP)" :disabled="fontScale >= FONT_SCALE_MAX"
            :style="{
              display:'inline-flex', alignItems:'center', justifyContent:'center',
              width:'26px', height:'24px', border:'none', borderRadius:'7px', background:'transparent',
              cursor: fontScale >= FONT_SCALE_MAX ? 'not-allowed' : 'pointer',
              color: fontScale >= FONT_SCALE_MAX ? '#D0C9BC' : '#1A1100', fontFamily:'inherit',
            }"
            @mouseenter="e=>{ if(fontScale < FONT_SCALE_MAX) e.currentTarget.style.background='#F5EDDB' }"
            @mouseleave="e=>e.currentTarget.style.background='transparent'">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
          </button>
        </div>

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


<!-- 수정 버튼: 본인이거나 관리자면 항상 표시 -->
        <Link v-if="isOwn || isAdmin" :href="`/reports/${report.id}/edit`"
          class="btn-secondary btn-sm"
          style="display:inline-flex;align-items:center;gap:5px;">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
          </svg>
          수정
        </Link>

        <!-- 코멘트 버튼 (전체 사용자 접근, 관리자만 작성) -->
        <button @click="openCommentPanel"
          style="display:inline-flex;align-items:center;gap:5px;background:#EDE9FE;color:#7C3AED;border:2px solid #1A1100;border-radius:10px;padding:6px 12px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:2px 2px 0 #1A1100;transition:all 0.1s;"
          @mouseenter="e=>{e.currentTarget.style.transform='translate(-1px,-1px)';e.currentTarget.style.boxShadow='3px 3px 0 #1A1100';}"
          @mouseleave="e=>{e.currentTarget.style.transform='none';e.currentTarget.style.boxShadow='2px 2px 0 #1A1100';}">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
          </svg>
          코멘트
          <span v-if="commentCount > 0"
            style="background:#7C3AED;color:#fff;font-size:10px;font-weight:800;padding:1px 6px;border-radius:99px;font-family:'Space Grotesk',sans-serif;">
            {{ commentCount }}
          </span>
        </button>

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
    <div class="report-scroll-wrap">
    <div class="card report-card" :style="{ overflow:'hidden', padding:0, zoom: fontScale }">

      <!-- 보고서 헤더 (데스크탑) -->
      <div class="report-header-desktop" style="display:flex;justify-content:space-between;align-items:stretch;border-bottom:2px solid #1A1100;background:#fff;">
        <div style="padding:16px 22px;display:flex;align-items:center;gap:12px;">
          <img src="/favicon.svg" alt="SE"
            style="width:36px;height:36px;border:2px solid #1A1100;border-radius:8px;flex-shrink:0;" />
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

      <!-- 보고서 헤더 (모바일) -->
      <div class="report-header-mobile" style="padding:14px 16px;border-bottom:2px solid #1A1100;background:#fff;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
          <img src="/favicon.svg" alt="SE"
            style="width:32px;height:32px;border:2px solid #1A1100;border-radius:8px;flex-shrink:0;" />
          <div>
            <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:15px;font-weight:800;">주간업무보고</div>
            <div style="font-size:11px;color:#9A8F7A;">{{ report.week_label || report.week }}</div>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;font-size:12px;">
          <div style="background:#F5EDDB;border-radius:8px;padding:7px 10px;">
            <div style="font-size:10px;color:#9A8F7A;font-weight:700;margin-bottom:2px;">이름</div>
            <div style="font-weight:700;">{{ report.user?.name ?? '-' }}</div>
          </div>
          <div style="background:#F5EDDB;border-radius:8px;padding:7px 10px;">
            <div style="font-size:10px;color:#9A8F7A;font-weight:700;margin-bottom:2px;">직급</div>
            <div style="font-weight:600;">{{ report.user?.position ?? '사원' }}</div>
          </div>
          <div style="background:#F5EDDB;border-radius:8px;padding:7px 10px;">
            <div style="font-size:10px;color:#9A8F7A;font-weight:700;margin-bottom:2px;">전주</div>
            <div style="font-weight:600;font-size:11px;">{{ fmtRange(report.curr_start, report.curr_end) }}</div>
          </div>
          <div style="background:#F5EDDB;border-radius:8px;padding:7px 10px;">
            <div style="font-size:10px;color:#9A8F7A;font-weight:700;margin-bottom:2px;">금주</div>
            <div style="font-weight:600;font-size:11px;">{{ fmtRange(report.next_start, report.next_end) }}</div>
          </div>
        </div>
      </div>

      <!-- 본문 테이블 -->
      <div class="report-table-scroll"><table class="report-body-table" style="width:100%;border-collapse:collapse;">
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
                        <span style="color:#1A1100;font-size:11px;line-height:1.5;white-space:pre-wrap;" v-html="autoLink(typeof sub === 'string' ? sub : sub.content)"></span>
                      </div>
                      <template v-if="typeof sub !== 'string' && sub.details && sub.details.length">
                        <div v-for="(detail, di) in sub.details" :key="di" style="display:flex;gap:6px;align-items:flex-start;margin-left:16px;">
                          <span style="color:#9A8F7A;flex-shrink:0;font-size:11px;">└</span>
                          <span style="color:#1A1100;font-size:11px;line-height:1.5;white-space:pre-wrap;" v-html="autoLink(detail)"></span>
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
                        <span style="color:#1A1100;font-size:11px;line-height:1.5;white-space:pre-wrap;" v-html="autoLink(typeof sub === 'string' ? sub : sub.content)"></span>
                      </div>
                      <template v-if="typeof sub !== 'string' && sub.details && sub.details.length">
                        <div v-for="(detail, di) in sub.details" :key="di" style="display:flex;gap:6px;align-items:flex-start;margin-left:16px;">
                          <span style="color:#9A8F7A;flex-shrink:0;font-size:11px;">└</span>
                          <span style="color:#1A1100;font-size:11px;line-height:1.5;white-space:pre-wrap;" v-html="autoLink(detail)"></span>
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
                        <span style="color:#1A1100;font-size:11px;line-height:1.5;white-space:pre-wrap;" v-html="autoLink(typeof sub === 'string' ? sub : sub.content)"></span>
                      </div>
                      <template v-if="typeof sub !== 'string' && sub.details && sub.details.length">
                        <div v-for="(detail, di) in sub.details" :key="di" style="display:flex;gap:6px;align-items:flex-start;margin-left:16px;">
                          <span style="color:#9A8F7A;flex-shrink:0;font-size:11px;">└</span>
                          <span style="color:#1A1100;font-size:11px;line-height:1.5;white-space:pre-wrap;" v-html="autoLink(detail)"></span>
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
                      <span style="font-size:12px;line-height:1.5;color:#1A1100;white-space:pre-wrap;word-break:break-word;">{{ typeof sub === 'string' ? sub : sub?.content }}</span>
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
                        <span style="color:#1A1100;font-size:11px;line-height:1.5;white-space:pre-wrap;" v-html="autoLink(typeof sub === 'string' ? sub : sub.content)"></span>
                      </div>
                      <template v-if="typeof sub !== 'string' && sub.details && sub.details.length">
                        <div v-for="(detail, di) in sub.details" :key="di" style="display:flex;gap:6px;align-items:flex-start;margin-left:16px;">
                          <span style="color:#9A8F7A;flex-shrink:0;font-size:11px;">└</span>
                          <span style="color:#1A1100;font-size:11px;line-height:1.5;white-space:pre-wrap;" v-html="autoLink(detail)"></span>
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
                        <span style="color:#1A1100;font-size:11px;line-height:1.5;white-space:pre-wrap;" v-html="autoLink(typeof sub === 'string' ? sub : sub.content)"></span>
                      </div>
                      <template v-if="typeof sub !== 'string' && sub.details && sub.details.length">
                        <div v-for="(detail, di) in sub.details" :key="di" style="display:flex;gap:6px;align-items:flex-start;margin-left:16px;">
                          <span style="color:#9A8F7A;flex-shrink:0;font-size:11px;">└</span>
                          <span style="color:#1A1100;font-size:11px;line-height:1.5;white-space:pre-wrap;" v-html="autoLink(detail)"></span>
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
      </table></div><!-- report-table-scroll -->
    </div><!-- report-card -->
    </div><!-- report-scroll-wrap -->

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

    <!-- 코멘트 패널 모달 (2단 레이아웃) -->
    <div v-if="showCommentPanel"
      style="position:fixed;inset:0;background:rgba(26,17,0,0.5);display:flex;align-items:center;justify-content:center;z-index:100;backdrop-filter:blur(3px);padding:12px;"
      @click.self="showCommentPanel=false">
      <div class="card comment-panel-inner" style="width:min(95vw,1080px);height:min(90vh,720px);padding:0;overflow:hidden;display:flex;flex-direction:column;">

        <!-- 헤더 -->
        <div style="padding:14px 20px;border-bottom:2px solid #1A1100;background:#F5EDDB;display:flex;align-items:center;gap:12px;flex-shrink:0;">
          <div style="width:36px;height:36px;background:#EDE9FE;border:2px solid #1A1100;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          </div>
          <div style="flex:1;min-width:0;">
            <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:15px;font-weight:800;">보고서 코멘트</div>
            <div style="font-size:11px;color:#9A8F7A;margin-top:1px;">{{ report.user?.name }} · {{ report.week_label || report.week }}</div>
          </div>
          <button @click="showCommentPanel=false" style="background:none;border:none;cursor:pointer;color:#9A8F7A;padding:6px;display:flex;align-items:center;border-radius:8px;"
            @mouseenter="e=>e.currentTarget.style.background='#E8E0D0'"
            @mouseleave="e=>e.currentTarget.style.background='transparent'">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
          </button>
        </div>

        <!-- 본문 2단 -->
        <div style="display:flex;flex:1;min-height:0;overflow:hidden;">

          <!-- 왼쪽: 보고서 내용 요약 (42%) -->
          <div style="width:42%;flex-shrink:0;border-right:2px solid #E8E0D0;overflow-y:auto;padding:18px 20px;background:#FDFAF5;display:flex;flex-direction:column;gap:18px;">

            <!-- 전주 업무 -->
            <div>
              <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#9A8F7A;margin-bottom:8px;font-family:'Space Grotesk',sans-serif;">전주 업무</div>
              <div v-if="!Object.keys(currByCategory).length" style="font-size:12px;color:#C8BFA8;padding-left:4px;">없음</div>
              <div v-else>
                <div v-for="(items, cat) in currByCategory" :key="cat" style="margin-bottom:10px;">
                  <span style="font-size:10px;font-weight:700;color:#7C3AED;padding:2px 8px;background:#EDE9FE;border-radius:6px;display:inline-block;margin-bottom:5px;">{{ cat }}</span>
                  <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:3px;">
                    <li v-for="item in items" :key="item.title ?? item.content" style="font-size:12px;color:#4A3F2A;padding-left:12px;position:relative;line-height:1.6;">
                      <span style="position:absolute;left:0;top:0;color:#9A8F7A;">·</span>
                      {{ item.title || item.content }}
                    </li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- 금주 계획 -->
            <div>
              <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#9A8F7A;margin-bottom:8px;font-family:'Space Grotesk',sans-serif;">금주 계획</div>
              <div v-if="!report.next_plan?.length" style="font-size:12px;color:#C8BFA8;padding-left:4px;">없음</div>
              <ul v-else style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:4px;">
                <li v-for="item in report.next_plan" :key="item.title ?? item.content" style="font-size:12px;color:#4A3F2A;padding-left:12px;position:relative;line-height:1.6;">
                  <span style="position:absolute;left:0;top:0;color:#9A8F7A;">·</span>
                  {{ item.title || item.content }}
                </li>
              </ul>
            </div>

            <!-- Todo -->
            <div>
              <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#9A8F7A;margin-bottom:8px;font-family:'Space Grotesk',sans-serif;">Todo</div>
              <div v-if="!report.todo_items?.length" style="font-size:12px;color:#C8BFA8;padding-left:4px;">없음</div>
              <ul v-else style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:5px;">
                <li v-for="todo in report.todo_items" :key="todo.content" style="display:flex;align-items:flex-start;gap:7px;font-size:12px;line-height:1.6;">
                  <span :style="{ color: todo.done ? '#16A34A' : '#9A8F7A', flexShrink:0, marginTop:'2px' }">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                      <template v-if="todo.done"><polyline points="20 6 9 17 4 12"/></template>
                      <template v-else><rect x="3" y="3" width="18" height="18" rx="3"/></template>
                    </svg>
                  </span>
                  <span :style="{ textDecoration: todo.done ? 'line-through' : 'none', color: todo.done ? '#9A8F7A' : '#4A3F2A' }">{{ todo.content }}</span>
                </li>
              </ul>
            </div>

            <!-- 특이사항 -->
            <div v-if="report.notes">
              <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#9A8F7A;margin-bottom:8px;font-family:'Space Grotesk',sans-serif;">특이사항</div>
              <p style="font-size:12px;color:#4A3F2A;line-height:1.7;white-space:pre-wrap;margin:0;padding:10px 12px;background:#FFF8EE;border-radius:8px;">{{ report.notes }}</p>
            </div>

            <!-- 요청사항 -->
            <div v-if="report.requests">
              <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#9A8F7A;margin-bottom:8px;font-family:'Space Grotesk',sans-serif;">요청사항</div>
              <p style="font-size:12px;color:#4A3F2A;line-height:1.7;white-space:pre-wrap;margin:0;padding:10px 12px;background:#FFF0A0;border-radius:8px;">{{ report.requests }}</p>
            </div>
          </div>

          <!-- 오른쪽: 코멘트 영역 (58%) -->
          <div style="flex:1;display:flex;flex-direction:column;min-height:0;overflow:hidden;">

            <!-- 코멘트 목록 -->
            <div style="flex:1;overflow-y:auto;padding:18px 20px;">
              <div v-if="commentLoading" style="display:flex;align-items:center;justify-content:center;height:100%;color:#9A8F7A;font-size:13px;">불러오는 중...</div>
              <div v-else-if="comments.length === 0" style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;gap:8px;color:#9A8F7A;">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.3;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <span style="font-size:13px;">아직 코멘트가 없습니다</span>
              </div>
              <div v-else style="display:flex;flex-direction:column;gap:10px;">
                <div v-for="c in comments" :key="c.id"
                  style="background:#F9F7F4;border:1.5px solid #E8E0D0;border-radius:10px;padding:12px 14px;">
                  <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:8px;">
                    <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap;">
                      <span :style="sectionBadgeStyle(c.section)" style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;border:1.5px solid;white-space:nowrap;">{{ sectionLabel(c.section) }}</span>
                      <span style="font-size:11px;color:#9A8F7A;">{{ c.user_name }} · {{ c.created_at }}</span>
                    </div>
                    <div v-if="isAdmin || c.user_id === page.props.auth?.user?.id" style="display:flex;gap:4px;flex-shrink:0;">
                      <button @click="startEdit(c)"
                        style="background:none;border:none;cursor:pointer;color:#C8BFA8;padding:4px;border-radius:5px;display:flex;align-items:center;"
                        @mouseenter="e=>e.currentTarget.style.color='#7C3AED'"
                        @mouseleave="e=>e.currentTarget.style.color='#C8BFA8'" v-tooltip="'수정'">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                      </button>
                      <button @click="removeComment(c)"
                        style="background:none;border:none;cursor:pointer;color:#C8BFA8;padding:4px;border-radius:5px;display:flex;align-items:center;"
                        @mouseenter="e=>e.currentTarget.style.color='#DC2626'"
                        @mouseleave="e=>e.currentTarget.style.color='#C8BFA8'" v-tooltip="'삭제'">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                      </button>
                    </div>
                  </div>
                  <div v-if="editingId === c.id">
                    <textarea v-model="editingText" rows="3"
                      style="width:100%;border:2px solid #7C3AED;border-radius:8px;padding:8px 10px;font-size:13px;font-family:inherit;outline:none;resize:vertical;box-sizing:border-box;"></textarea>
                    <div style="display:flex;gap:6px;margin-top:6px;justify-content:flex-end;">
                      <button @click="editingId=null" style="background:#F5EDDB;color:#4A3F2A;border:1.5px solid #1A1100;border-radius:7px;padding:5px 14px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;">취소</button>
                      <button @click="saveEdit(c)" style="background:#7C3AED;color:#fff;border:1.5px solid #1A1100;border-radius:7px;padding:5px 14px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;">저장</button>
                    </div>
                  </div>
                  <div v-else style="font-size:13px;color:#4A3F2A;line-height:1.7;white-space:pre-wrap;word-break:break-word;">{{ c.content }}</div>
                </div>
              </div>
            </div>

            <!-- 입력 영역 (관리자 또는 보고서 소유자) -->
            <div v-if="isAdmin || isOwn" style="padding:14px 20px;border-top:2px solid #E8E0D0;flex-shrink:0;background:#fff;">
              <div style="display:flex;gap:8px;align-items:flex-end;">
                <textarea v-model="newComment" rows="3" :placeholder="isAdmin ? '코멘트를 입력하세요...' : '답변을 입력하세요...'"
                  style="flex:1;border:2px solid #1A1100;border-radius:10px;padding:9px 12px;font-size:13px;font-family:inherit;outline:none;resize:none;transition:border-color 0.12s;"
                  @focus="e=>e.target.style.borderColor='#7C3AED'"
                  @blur="e=>e.target.style.borderColor='#1A1100'"
                  @keydown.ctrl.enter="addComment"
                  @keydown.meta.enter="addComment"></textarea>
                <button @click="addComment" :disabled="!newComment.trim() || addingComment"
                  style="background:#7C3AED;color:#fff;border:2px solid #1A1100;border-radius:10px;padding:10px 18px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;white-space:nowrap;flex-shrink:0;box-shadow:2px 2px 0 #1A1100;transition:all 0.1s;"
                  :style="{ opacity: !newComment.trim() || addingComment ? 0.5 : 1 }">
                  {{ addingComment ? '등록 중...' : '등록' }}
                </button>
              </div>
              <div style="font-size:10px;color:#C8BFA8;margin-top:4px;">Ctrl+Enter로 등록</div>
            </div>
          </div>
        </div>
      </div>
    </div>


  </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { autoLink } from '@/utils/autoLink.js'

const props = defineProps({
  report:          { type: Object, required: true },
  teamUsers:       { type: Array,  default: () => [] },
  prevReportId:    { type: Number, default: null },
  nextReportId:    { type: Number, default: null },
  currentReportId: { type: Number, default: null },
})

const page    = usePage()
const isOwn   = computed(() => page.props.auth?.user?.id === props.report.user_id)
const isAdmin = computed(() => page.props.auth?.user?.role === 'admin')

// ── 보고서 본문 글자 크기 개인화 (기기별, localStorage 저장) ──────────
const FONT_SCALE_KEY = 'report_font_scale'
const FONT_SCALE_MIN = 0.9
const FONT_SCALE_MAX = 1.6
const FONT_SCALE_STEP = 0.1

const loadFontScale = () => {
  const v = parseFloat(localStorage.getItem(FONT_SCALE_KEY))
  if (Number.isNaN(v)) return 1
  return Math.min(FONT_SCALE_MAX, Math.max(FONT_SCALE_MIN, v))
}
const fontScale = ref(loadFontScale())

watch(fontScale, (v) => localStorage.setItem(FONT_SCALE_KEY, String(v)))

const changeFontScale = (delta) => {
  const next = Math.round((fontScale.value + delta) * 10) / 10
  fontScale.value = Math.min(FONT_SCALE_MAX, Math.max(FONT_SCALE_MIN, next))
}
const resetFontScale = () => { fontScale.value = 1 }
const fontScalePercent = computed(() => Math.round(fontScale.value * 100))

const AVATAR_COLORS = ['#FD4401','#16a34a','#2563eb','#9333ea','#d97706','#0891b2','#dc2626','#65a30d']
const avatarColor = (id, user) => {
  if (user?.avatar_color) return user.avatar_color
  const u = props.teamUsers?.find(tu => tu.id === id)
  return u?.avatar_color || AVATAR_COLORS[(id ?? 0) % AVATAR_COLORS.length]
}

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


const allCategories = computed(() => [
  ...Object.keys(currByCategory.value),
  ...Object.keys(nextByCategory.value),
])

const showDeleteModal = ref(false)
const doDelete = () => router.delete(`/reports/${props.report.id}`)

// ── 코멘트 패널 ──────────────────────────────────
const showCommentPanel = ref(false)
const commentLoading   = ref(false)
const comments         = ref([])
const commentCount     = ref(0)
const newComment       = ref('')
const newSection       = ref('general')
const addingComment    = ref(false)
const editingId        = ref(null)
const editingText      = ref('')

const openCommentPanel = async () => {
  showCommentPanel.value = true
  if (comments.value.length === 0) await loadComments()
}

const loadComments = async () => {
  commentLoading.value = true
  try {
    const res = await window.axios.get(`/reports/${props.report.id}/comments`)
    comments.value    = res.data
    commentCount.value = res.data.length
  } catch { /* ignore */ }
  finally { commentLoading.value = false }
}

const addComment = async () => {
  if (!newComment.value.trim() || addingComment.value) return
  addingComment.value = true
  try {
    const res = await window.axios.post(`/reports/${props.report.id}/comments`, {
      content: newComment.value.trim(),
    })
    comments.value.push(res.data)
    commentCount.value++
    newComment.value = ''
  } catch (e) {
    console.error('코멘트 등록 실패:', e.response?.data ?? e.message)
    alert('코멘트 등록에 실패했습니다. (' + (e.response?.status ?? '네트워크 오류') + ')')
  } finally { addingComment.value = false }
}

const startEdit = (c) => { editingId.value = c.id; editingText.value = c.content }
const saveEdit  = async (c) => {
  try {
    await window.axios.put(`/report-comments/${c.id}`, { content: editingText.value.trim() })
    c.content = editingText.value.trim()
    editingId.value = null
  } catch { /* ignore */ }
}
const removeComment = async (c) => {
  try {
    await window.axios.delete(`/report-comments/${c.id}`)
    const idx = comments.value.findIndex(x => x.id === c.id)
    if (idx !== -1) { comments.value.splice(idx, 1); commentCount.value-- }
  } catch { /* ignore */ }
}

const sectionLabel = (s) => ({
  general:    '전체',
  curr_work:  '전주 업무',
  next_plan:  '금주 업무',
  todo_items: 'Todo',
  notes:      '특이사항',
  requests:   '요청사항',
})[s] ?? s

const sectionBadgeStyle = (s) => ({
  general:    { background:'#F3F4F6', color:'#374151', borderColor:'#D1D5DB' },
  curr_work:  { background:'#EFF6FF', color:'#1D4ED8', borderColor:'#BFDBFE' },
  next_plan:  { background:'#F0FDF4', color:'#15803D', borderColor:'#BBF7D0' },
  todo_items: { background:'#FFF7ED', color:'#C2410C', borderColor:'#FED7AA' },
  notes:      { background:'#FDF4FF', color:'#7E22CE', borderColor:'#E9D5FF' },
  requests:   { background:'#FFFBEB', color:'#92400E', borderColor:'#FDE68A' },
})[s] ?? { background:'#F3F4F6', color:'#374151', borderColor:'#D1D5DB' }
</script>

<style scoped>
/* ===========================
   보고서 상세 반응형
   =========================== */

/* 데스크탑: 헤더 desktop 표시, mobile 숨김 */
.report-header-mobile { display: none; }
.report-header-desktop { display: flex; }

/* 코멘트 패널: 모바일에서 단일 열 */
@media (max-width: 700px) {
  .comment-panel-inner {
    height: 95vh !important;
  }
  .comment-panel-inner > div:last-child {
    flex-direction: column !important;
  }
  .comment-panel-inner > div:last-child > div:first-child {
    width: 100% !important;
    border-right: none !important;
    border-bottom: 2px solid #E8E0D0;
    max-height: 220px;
  }
}

/* 테이블 스크롤 감싸개 */
.report-table-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
.report-table-scroll::-webkit-scrollbar { height: 4px; }
.report-table-scroll::-webkit-scrollbar-track { background: #F5EDDB; }
.report-table-scroll::-webkit-scrollbar-thumb { background: #C8BFA8; border-radius: 4px; }

.report-body-table { min-width: 480px; }

@media (max-width: 768px) {
  /* 헤더 전환 */
  .report-header-desktop { display: none !important; }
  .report-header-mobile { display: block !important; }

  /* 보고서 카드 자체 스크롤 제거 (테이블만 스크롤) */
  .report-scroll-wrap { overflow: visible; }

  /* 본문 테이블: 구분 컬럼 좁게, 폰트 작게 */
  .report-body-table th:first-child,
  .report-body-table td:first-child {
    width: 48px !important;
    padding: 10px 4px !important;
    font-size: 10px !important;
  }
  .report-body-table th,
  .report-body-table td {
    padding: 10px 10px !important;
    font-size: 12px !important;
  }

  /* 지원 행 전주/금주 열: 모바일에서 쌓기 */
  .report-body-table .support-row-curr,
  .report-body-table .support-row-next {
    display: block;
    width: 100% !important;
  }
}
</style>
