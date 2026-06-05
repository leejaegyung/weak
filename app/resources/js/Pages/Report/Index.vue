<template>
  <AppLayout page-title="보고서 목록">
    <!-- 헤더 -->
    <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
      <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
          <div style="background:#FD4401;border:2px solid #1A1100;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M16 13H8M16 17H8M10 9H8"/>
            </svg>
          </div>
          <h1 style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:26px;font-weight:700;letter-spacing:-0.03em;">보고서 목록</h1>
        </div>
        <p style="color:#9A8F7A;font-size:13px;margin-left:42px;">팀원들의 주간 보고서를 확인하세요</p>
      </div>
      <div class="index-header-actions" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <!-- 주차 네비게이션 -->
        <div style="display:flex;align-items:center;gap:6px;">
          <button @click="goWeek(prevWeek)"
            class="btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:4px;">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
            이전
          </button>
          <div style="background:#FFF0A0;border:2px solid #1A1100;border-radius:10px;padding:6px 14px;font-size:12px;font-weight:800;font-family:'Space Grotesk','Noto Sans KR',sans-serif;min-width:100px;text-align:center;box-shadow:2px 2px 0 #1A1100;">
            {{ weekLabel }}
            <div style="font-size:10px;font-weight:400;color:#9A8F7A;margin-top:1px;">{{ fmtShort(weekStart) }} ~ {{ fmtShort(weekEnd) }}</div>
          </div>
          <button v-if="!isCurrentWeek" @click="goWeek('')"
            style="background:#FD4401;color:#fff;border:2px solid #1A1100;border-radius:8px;padding:5px 12px;font-size:11px;font-weight:700;cursor:pointer;font-family:inherit;">
            이번 주
          </button>
          <button @click="goWeek(nextWeek)"
            class="btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:4px;">
            다음
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
          </button>
        </div>

        <!-- 미제출 알림 (관리자만) -->
        <button v-if="isAdmin" @click="showAlertModal=true"
          class="btn-secondary btn-sm"
          style="display:inline-flex;align-items:center;gap:5px;">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13M22 2L15 22l-4-9-9-4 20-7z"/></svg>
          미제출 알림
        </button>

        <!-- 팀장 메일 전송 (관리자만) -->
        <button v-if="isAdmin" @click="openMailModal"
          :disabled="!canSendMail"
          :title="!canSendMail ? '전원 제출 완료 후 전송 가능합니다' : '주간보고 메일 전송'"
          class="btn-secondary btn-sm"
          style="display:inline-flex;align-items:center;gap:5px;"
          :style="{ opacity: canSendMail ? 1 : 0.45, cursor: canSendMail ? 'pointer' : 'not-allowed' }">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
          </svg>
          메일 전송
        </button>

        <!-- Excel 다운로드 (관리자만) -->
        <a v-if="isAdmin" :href="`/export/weekly?curr_start=${weekStart}`"
          class="btn-secondary btn-sm"
          style="display:inline-flex;align-items:center;gap:5px;text-decoration:none;">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>
          </svg>
          Excel
        </a>

        <Link href="/reports/create" class="btn-primary">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
          보고서 작성
        </Link>
      </div>
    </div>

    <!-- 통계 카드 -->
    <div class="stats-grid" style="display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:24px;">
      <div v-for="stat in statsCards" :key="stat.label"
        class="card"
        :style="{ background: stat.bg, padding:'18px 20px', display:'flex', alignItems:'center', gap:'14px', cursor:'pointer', transition:'transform 0.1s,box-shadow 0.1s' }"
        @click="setFilter(stat.filter)"
        @mouseenter="e=>{e.currentTarget.style.transform='translate(-2px,-2px)';e.currentTarget.style.boxShadow='6px 6px 0 #1A1100';}"
        @mouseleave="e=>{e.currentTarget.style.transform='none';e.currentTarget.style.boxShadow='4px 4px 0 #1A1100';}">
        <div style="width:44px;height:44px;border-radius:10px;background:rgba(26,17,0,0.08);border:2px solid #1A1100;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <span style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:20px;font-weight:800;">{{ stat.val }}</span>
        </div>
        <span style="font-weight:600;font-size:13px;">{{ stat.label }}</span>
      </div>
    </div>

    <!-- 검색 + 필터 탭 -->
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:16px;">
      <div style="position:relative;flex:1;max-width:280px;">
        <span style="position:absolute;left:11px;top:50%;transform:translateY(-50%);pointer-events:none;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9A8F7A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/></svg>
        </span>
        <input v-model="searchInput" @input="onSearch"
          placeholder="이름 또는 기간 검색..."
          style="width:100%;background:#fff;border:2px solid #1A1100;border-radius:10px;padding:8px 12px 8px 32px;color:#1A1100;font-size:13px;font-family:inherit;outline:none;transition:border-color 0.12s;"
          @focus="e=>e.target.style.borderColor='#FD4401'"
          @blur="e=>e.target.style.borderColor='#1A1100'" />
      </div>
      <button v-for="f in filterBtns" :key="f.val" @click="setFilter(f.val)"
        :style="{
          background: activeFilter===f.val ? '#FD4401' : '#fff',
          color: activeFilter===f.val ? '#fff' : '#1A1100',
          border: '2px solid #1A1100',
          borderRadius: '10px',
          padding: '7px 16px',
          fontSize: '12px',
          fontWeight: '700',
          cursor: 'pointer',
          fontFamily: 'inherit',
          boxShadow: activeFilter===f.val ? '2px 2px 0 #1A1100' : 'none',
          transition: 'all 0.12s',
        }">{{ f.label }}</button>
    </div>

    <!-- 테이블 -->
    <div class="card table-card" style="overflow:hidden;padding:0;">
      <div class="table-scroll-wrap" style="overflow-x:auto;min-width:0;">
      <div style="min-width:580px;">
      <div style="display:grid;grid-template-columns:2fr 2fr 1fr 1fr 1fr 1.5fr 88px;padding:10px 20px;border-bottom:2px solid #1A1100;background:#F5EDDB;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#9A8F7A;font-family:'Space Grotesk','Noto Sans KR',sans-serif;">
        <span>작성자</span><span>보고 기간</span><span>제출일</span><span>업무</span><span>Todo</span><span>상태</span><span></span>
      </div>

      <div v-for="(r, i) in reports" :key="r.id ?? `ns-${r.user?.id}`"
        style="display:grid;grid-template-columns:2fr 2fr 1fr 1fr 1fr 1.5fr 88px;padding:14px 20px;align-items:center;transition:background 0.12s;"
        :style="{
          borderBottom: i < reports.length-1 ? '1.5px solid #F5EDDB' : 'none',
          cursor: r.status === 'not_submitted' ? 'default' : 'pointer',
          opacity: r.status === 'not_submitted' ? '0.75' : '1',
        }"
        @click="r.status !== 'not_submitted' && router.get(`/reports/${r.id}`)"
        @mouseenter="e=>{ if(r.status !== 'not_submitted') e.currentTarget.style.background='#FFF8EE' }"
        @mouseleave="e=>e.currentTarget.style.background='transparent'">

        <div style="display:flex;align-items:center;gap:10px;">
          <div :style="{ background: avatarColor(r.user?.id ?? r.user_id), width:'30px', height:'30px', borderRadius:'50%', border:'2px solid #1A1100', display:'flex', alignItems:'center', justifyContent:'center', color:'#fff', fontSize:'12px', fontWeight:'700', flexShrink:0, fontFamily:'\'Space Grotesk\',sans-serif' }">
            {{ r.user?.name?.charAt(0) ?? '?' }}
          </div>
          <div>
            <div style="font-size:13px;font-weight:700;">{{ r.user?.name ?? '-' }}</div>
            <div style="font-size:11px;color:#9A8F7A;">{{ r.week_label || r.week || '-' }}</div>
          </div>
        </div>
        <span style="font-size:12px;color:#4A3F2A;">
          {{ r.curr_start ? `${fmt(r.curr_start)} ~ ${fmt(r.curr_end)}` : '-' }}
        </span>
        <span style="font-size:12px;font-weight:600;">{{ r.submitted_at ? String(r.submitted_at).substring(0,10) : '-' }}</span>
        <!-- 업무: 전주 지원 항목 수 (curr_work 중 지원 카테고리), 미제출은 '-' -->
        <span style="font-family:'Space Grotesk',sans-serif;font-size:15px;font-weight:700;">
          {{ r.status === 'not_submitted' ? '-' : (r.curr_work?.filter(i => i.category === '지원').length ?? 0) }}
        </span>
        <!-- Todo: 미제출은 '-' -->
        <span style="font-family:'Space Grotesk',sans-serif;font-size:15px;font-weight:700;">
          {{ r.status === 'not_submitted' ? '-' : (r.todo_items?.length ?? 0) }}
        </span>
        <div style="display:flex;align-items:center;gap:6px;">
          <span :class="statusBadge(r.status)">{{ r.status_label }}</span>
        </div>
        <!-- 액션 버튼 (코멘트 + 삭제) -->
        <div @click.stop style="display:flex;justify-content:center;gap:4px;">
          <!-- 코멘트 버튼 (제출된 보고서 - 전체 사용자 접근) -->
          <button v-if="r.status !== 'not_submitted'"
            @click="openCommentModal(r)"
            style="background:none;border:none;cursor:pointer;padding:5px;border-radius:6px;transition:color 0.1s;display:flex;align-items:center;gap:3px;"
            :style="{ color: commentCountFor(r) > 0 ? '#7C3AED' : '#D0C9BC' }"
            @mouseenter="e=>e.currentTarget.style.color='#7C3AED'"
            @mouseleave="e=>{ e.currentTarget.style.color = commentCountFor(r) > 0 ? '#7C3AED' : '#D0C9BC' }"
            title="코멘트">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <span v-if="commentCountFor(r) > 0" style="font-size:10px;font-weight:700;font-family:'Space Grotesk',sans-serif;line-height:1;">{{ commentCountFor(r) }}</span>
          </button>
          <!-- 삭제 버튼 (미제출 행 제외, 관리자 또는 본인 보고서만) -->
          <button v-if="r.status !== 'not_submitted' && (isAdmin || r.user_id === currentUserId)"
            @click="confirmDelete(r)"
            style="background:none;border:none;cursor:pointer;color:#D0C9BC;padding:5px;border-radius:6px;transition:color 0.1s;display:flex;align-items:center;"
            @mouseenter="e=>e.currentTarget.style.color='#DC2626'"
            @mouseleave="e=>e.currentTarget.style.color='#D0C9BC'"
            title="삭제">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
            </svg>
          </button>
        </div>
      </div>

      <div v-if="reports.length === 0"
        style="padding:48px 20px;text-align:center;color:#9A8F7A;font-size:13px;">검색 결과가 없습니다</div>
      </div><!-- min-width -->
      </div><!-- table-scroll-wrap -->
    </div>

    <!-- 미제출 알림 채널 선택 모달 -->
    <div v-if="showAlertModal"
      style="position:fixed;inset:0;background:rgba(26,17,0,0.45);display:flex;align-items:center;justify-content:center;z-index:100;backdrop-filter:blur(3px);padding:16px;"
      @click.self="showAlertModal=false">
      <div class="card" style="width:420px;max-width:100%;max-height:90vh;overflow-y:auto;padding:28px;">
        <!-- 모달 헤더 -->
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
          <div style="width:44px;height:44px;background:#FFF0A0;border:2px solid #1A1100;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13M22 2L15 22l-4-9-9-4 20-7z"/></svg>
          </div>
          <div>
            <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:16px;font-weight:800;color:#1A1100;">미제출 알림 발송</div>
            <div style="font-size:12px;color:#9A8F7A;margin-top:2px;">{{ weekLabel }} · 발송 채널을 선택하세요</div>
          </div>
        </div>

        <!-- 채널 선택 -->
        <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:22px;">
          <label v-for="opt in alertChannelOptions" :key="opt.value"
            :style="{
              display:'flex', alignItems:'center', gap:'12px',
              border: alertChannel===opt.value ? '2px solid #FD4401' : '2px solid #E8E0D0',
              borderRadius: '12px', padding: '12px 16px', cursor: 'pointer',
              background: alertChannel===opt.value ? '#FFF8EE' : '#fff',
              transition: 'all 0.12s',
            }"
            @click="alertChannel=opt.value">
            <div :style="{
              width:'18px', height:'18px', borderRadius:'50%',
              border: alertChannel===opt.value ? '5px solid #FD4401' : '2px solid #C0B8AC',
              flexShrink: 0, transition:'all 0.12s',
            }"></div>
            <div>
              <div style="font-size:13px;font-weight:700;color:#1A1100;">{{ opt.label }}</div>
              <div style="font-size:11px;color:#9A8F7A;margin-top:2px;">{{ opt.desc }}</div>
            </div>
          </label>
        </div>

        <!-- 버튼 -->
        <div style="display:flex;gap:8px;justify-content:flex-end;">
          <button @click="showAlertModal=false" class="btn-secondary" :disabled="alertSending">취소</button>
          <button @click="sendNotSubmittedAlert"
            :disabled="alertSending"
            style="background:#FD4401;color:#fff;border:2px solid #FD4401;border-radius:10px;padding:8px 20px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:2px 2px 0 #B91C1C;transition:all 0.1s;display:inline-flex;align-items:center;gap:6px;">
            <svg v-if="alertSending" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation:spin 1s linear infinite;"><path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" opacity=".25"/><path d="M12 3a9 9 0 0 1 9 9"/></svg>
            {{ alertSending ? '발송 중...' : '알림 발송' }}
          </button>
        </div>
      </div>
    </div>

    <!-- 메일 전송 모달 -->
    <div v-if="showMailModal"
      style="position:fixed;inset:0;background:rgba(26,17,0,0.45);display:flex;align-items:center;justify-content:center;z-index:100;backdrop-filter:blur(3px);padding:16px;"
      @click.self="showMailModal=false">
      <div class="card" style="width:520px;max-width:100%;max-height:90vh;padding:0;overflow:hidden;display:flex;flex-direction:column;">
        <!-- 모달 헤더 -->
        <div style="padding:20px 24px;border-bottom:2px solid #1A1100;background:#F5EDDB;display:flex;align-items:center;gap:12px;flex-shrink:0;">
          <div style="width:44px;height:44px;background:#DCFCE7;border:2px solid #1A1100;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          </div>
          <div>
            <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:16px;font-weight:800;color:#1A1100;">주간보고 메일 전송</div>
            <div style="font-size:12px;color:#9A8F7A;margin-top:2px;">{{ weekLabel }} 보고서 링크를 메일로 전달합니다</div>
          </div>
        </div>

        <!-- 모달 바디 -->
        <div style="padding:22px 24px;display:flex;flex-direction:column;gap:14px;overflow-y:auto;flex:1;min-height:0;">
          <!-- 받는 사람 -->
          <div>
            <label style="font-size:11px;color:#9A8F7A;font-weight:700;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.04em;">받는 사람</label>
            <input v-model="mailForm.to" type="email" class="input-field" placeholder="teamlead@company.com" />
          </div>

          <!-- 참조 -->
          <div>
            <label style="font-size:11px;color:#9A8F7A;font-weight:700;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.04em;">참조 (CC)</label>
            <input v-model="mailForm.ccInput" type="text" class="input-field" placeholder="쉼표(,)로 구분하여 입력 예) a@b.com, c@d.com" />
          </div>

          <!-- 제목 -->
          <div>
            <label style="font-size:11px;color:#9A8F7A;font-weight:700;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.04em;">제목</label>
            <input v-model="mailForm.subject" type="text" class="input-field" placeholder="메일 제목" />
          </div>

          <!-- 본문 미리보기 -->
          <div style="background:#FDFAF5;border:1.5px solid #E8E0D0;border-radius:10px;padding:14px 16px;">
            <div style="font-size:11px;color:#9A8F7A;font-weight:700;margin-bottom:10px;text-transform:uppercase;letter-spacing:0.04em;">본문 미리보기</div>
            <p style="font-size:12px;color:#4A3F2A;line-height:1.7;margin-bottom:12px;">
              안녕하세요.<br>이번 주 팀원 주간업무보고를 전달드립니다.
            </p>
            <div style="display:flex;flex-direction:column;gap:6px;">
              <div v-for="r in submittedReports" :key="r.id"
                style="display:flex;align-items:center;justify-content:space-between;background:#fff;border:1.5px solid #E8E0D0;border-radius:8px;padding:8px 12px;">
                <div style="display:flex;align-items:center;gap:8px;">
                  <div :style="{ width:'24px', height:'24px', borderRadius:'50%', background: avatarColor(r.user?.id), border:'1.5px solid #1A1100', display:'flex', alignItems:'center', justifyContent:'center', fontSize:'10px', fontWeight:'700', color:'#fff', flexShrink:0 }">
                    {{ r.user?.name?.charAt(0) }}
                  </div>
                  <span style="font-size:12px;font-weight:700;color:#1A1100;">{{ r.user?.name }}</span>
                  <span style="font-size:11px;color:#9A8F7A;">{{ r.user?.position }}</span>
                </div>
                <span style="font-size:11px;color:#9A8F7A;">보고서 링크 포함</span>
              </div>
            </div>
            <p style="font-size:12px;color:#9A8F7A;margin-top:10px;line-height:1.6;">감사합니다.</p>
          </div>
        </div>

        <!-- 모달 푸터 -->
        <div style="padding:16px 24px;border-top:2px solid #1A1100;background:#F5EDDB;display:flex;gap:8px;justify-content:flex-end;flex-shrink:0;">
          <button @click="showMailModal=false" class="btn-secondary" :disabled="mailSending">취소</button>
          <button @click="sendWeeklyMail" :disabled="mailSending || !mailForm.to"
            style="background:#16A34A;color:#fff;border:2px solid #1A1100;border-radius:10px;padding:8px 20px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:2px 2px 0 #166534;transition:all 0.1s;display:inline-flex;align-items:center;gap:6px;"
            :style="{ opacity: (!mailSending && mailForm.to) ? 1 : 0.6 }">
            <svg v-if="mailSending" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation:spin 1s linear infinite;"><path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" opacity=".25"/><path d="M12 3a9 9 0 0 1 9 9"/></svg>
            <svg v-else width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13M22 2L15 22l-4-9-9-4 20-7z"/></svg>
            {{ mailSending ? '전송 중...' : '메일 전송' }}
          </button>
        </div>
      </div>
    </div>

    <!-- 삭제 확인 모달 -->
    <div v-if="deleteTarget"
      style="position:fixed;inset:0;background:rgba(26,17,0,0.45);display:flex;align-items:center;justify-content:center;z-index:100;backdrop-filter:blur(3px);padding:16px;"
      @click.self="deleteTarget=null">
      <div class="card" style="width:380px;max-width:100%;max-height:90vh;overflow-y:auto;padding:28px;text-align:center;">
        <div style="width:48px;height:48px;background:#FEE2E2;border:2px solid #DC2626;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
          </svg>
        </div>
        <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:16px;font-weight:800;color:#1A1100;margin-bottom:8px;">보고서를 삭제할까요?</div>
        <div style="font-size:13px;color:#9A8F7A;line-height:1.7;margin-bottom:22px;">
          <strong style="color:#1A1100;">{{ deleteTarget?.user?.name }}</strong>님의
          <strong style="color:#1A1100;">{{ deleteTarget?.week_label || deleteTarget?.week }}</strong> 보고서가<br>
          영구적으로 삭제됩니다. 되돌릴 수 없습니다.
        </div>
        <div style="display:flex;gap:8px;justify-content:center;">
          <button @click="deleteTarget=null" class="btn-secondary">취소</button>
          <button @click="doDelete"
            style="background:#DC2626;color:#fff;border:2px solid #DC2626;border-radius:10px;padding:8px 20px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:2px 2px 0 #991B1B;transition:all 0.1s;">
            삭제하기
          </button>
        </div>
      </div>
    </div>

    <!-- 코멘트 팝업 모달 (2단 레이아웃) -->
    <div v-if="commentModal.show"
      style="position:fixed;inset:0;background:rgba(26,17,0,0.5);display:flex;align-items:center;justify-content:center;z-index:100;backdrop-filter:blur(3px);padding:12px;"
      @click.self="closeCommentModal">
      <div class="card comment-modal-inner" style="width:min(95vw,1080px);height:min(90vh,720px);padding:0;overflow:hidden;display:flex;flex-direction:column;">

        <!-- 모달 헤더 -->
        <div style="padding:14px 20px;border-bottom:2px solid #1A1100;background:#F5EDDB;display:flex;align-items:center;gap:12px;flex-shrink:0;">
          <div style="width:36px;height:36px;background:#EDE9FE;border:2px solid #1A1100;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          </div>
          <div style="flex:1;min-width:0;">
            <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:15px;font-weight:800;">보고서 코멘트</div>
            <div style="font-size:11px;color:#9A8F7A;margin-top:1px;">{{ commentModal.reportUser }} · {{ commentModal.reportWeek }}</div>
          </div>
          <button @click="closeCommentModal" style="background:none;border:none;cursor:pointer;color:#9A8F7A;padding:6px;display:flex;align-items:center;border-radius:8px;"
            @mouseenter="e=>e.currentTarget.style.background='#E8E0D0'"
            @mouseleave="e=>e.currentTarget.style.background='transparent'">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
          </button>
        </div>

        <!-- 본문 2단 -->
        <div style="display:flex;flex:1;min-height:0;overflow:hidden;">

          <!-- 왼쪽: 보고서 내용 (42%) -->
          <div style="width:42%;flex-shrink:0;border-right:2px solid #E8E0D0;overflow-y:auto;padding:18px 20px;background:#FDFAF5;display:flex;flex-direction:column;gap:18px;">

            <!-- 전주 업무 -->
            <div>
              <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#9A8F7A;margin-bottom:8px;font-family:'Space Grotesk',sans-serif;">전주 업무</div>
              <div v-if="!commentModal.report?.curr_work?.length" style="font-size:12px;color:#C8BFA8;padding-left:4px;">없음</div>
              <div v-else>
                <div v-for="(items, gname) in currWorkGroups" :key="gname" style="margin-bottom:10px;">
                  <span style="font-size:10px;font-weight:700;color:#7C3AED;padding:2px 8px;background:#EDE9FE;border-radius:6px;display:inline-block;margin-bottom:5px;">{{ gname }}</span>
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
              <div v-if="!commentModal.report?.next_plan?.length" style="font-size:12px;color:#C8BFA8;padding-left:4px;">없음</div>
              <ul v-else style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:4px;">
                <li v-for="item in commentModal.report.next_plan" :key="item.title ?? item.content" style="font-size:12px;color:#4A3F2A;padding-left:12px;position:relative;line-height:1.6;">
                  <span style="position:absolute;left:0;top:0;color:#9A8F7A;">·</span>
                  {{ item.title || item.content }}
                </li>
              </ul>
            </div>

            <!-- Todo -->
            <div>
              <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#9A8F7A;margin-bottom:8px;font-family:'Space Grotesk',sans-serif;">Todo</div>
              <div v-if="!commentModal.report?.todo_items?.length" style="font-size:12px;color:#C8BFA8;padding-left:4px;">없음</div>
              <ul v-else style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:5px;">
                <li v-for="todo in commentModal.report.todo_items" :key="todo.content" style="display:flex;align-items:flex-start;gap:7px;font-size:12px;line-height:1.6;">
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
            <div v-if="commentModal.report?.notes">
              <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#9A8F7A;margin-bottom:8px;font-family:'Space Grotesk',sans-serif;">특이사항</div>
              <p style="font-size:12px;color:#4A3F2A;line-height:1.7;white-space:pre-wrap;margin:0;padding:10px 12px;background:#FFF8EE;border-radius:8px;">{{ commentModal.report.notes }}</p>
            </div>

            <!-- 요청사항 -->
            <div v-if="commentModal.report?.requests">
              <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#9A8F7A;margin-bottom:8px;font-family:'Space Grotesk',sans-serif;">요청사항</div>
              <p style="font-size:12px;color:#4A3F2A;line-height:1.7;white-space:pre-wrap;margin:0;padding:10px 12px;background:#FFF0A0;border-radius:8px;">{{ commentModal.report.requests }}</p>
            </div>
          </div>

          <!-- 오른쪽: 코멘트 영역 (58%) -->
          <div style="flex:1;display:flex;flex-direction:column;min-height:0;overflow:hidden;">

            <!-- 코멘트 목록 -->
            <div style="flex:1;overflow-y:auto;padding:18px 20px;">
              <div v-if="commentModal.loading" style="display:flex;align-items:center;justify-content:center;height:100%;color:#9A8F7A;font-size:13px;">불러오는 중...</div>
              <div v-else-if="commentModal.comments.length === 0" style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;gap:8px;color:#9A8F7A;">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.3;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <span style="font-size:13px;">아직 코멘트가 없습니다</span>
              </div>
              <div v-else style="display:flex;flex-direction:column;gap:10px;">
                <div v-for="c in commentModal.comments" :key="c.id"
                  style="background:#F9F7F4;border:1.5px solid #E8E0D0;border-radius:10px;padding:12px 14px;">
                  <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:8px;">
                    <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap;">
                      <span :style="sectionBadgeStyle(c.section)" style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;border:1.5px solid;white-space:nowrap;">{{ sectionLabel(c.section) }}</span>
                      <span style="font-size:11px;color:#9A8F7A;">{{ c.user_name }} · {{ c.created_at }}</span>
                    </div>
                    <div v-if="isAdmin" style="display:flex;gap:4px;flex-shrink:0;">
                      <button @click="startEditComment(c)"
                        style="background:none;border:none;cursor:pointer;color:#C8BFA8;padding:4px;border-radius:5px;display:flex;align-items:center;"
                        @mouseenter="e=>e.currentTarget.style.color='#7C3AED'"
                        @mouseleave="e=>e.currentTarget.style.color='#C8BFA8'" title="수정">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                      </button>
                      <button @click="deleteComment(c)"
                        style="background:none;border:none;cursor:pointer;color:#C8BFA8;padding:4px;border-radius:5px;display:flex;align-items:center;"
                        @mouseenter="e=>e.currentTarget.style.color='#DC2626'"
                        @mouseleave="e=>e.currentTarget.style.color='#C8BFA8'" title="삭제">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                      </button>
                    </div>
                  </div>
                  <div v-if="isAdmin && editingCommentId === c.id">
                    <textarea v-model="editingContent" rows="3"
                      style="width:100%;border:2px solid #7C3AED;border-radius:8px;padding:8px 10px;font-size:13px;font-family:inherit;outline:none;resize:vertical;box-sizing:border-box;"></textarea>
                    <div style="display:flex;gap:6px;margin-top:6px;justify-content:flex-end;">
                      <button @click="editingCommentId=null" style="background:#F5EDDB;color:#4A3F2A;border:1.5px solid #1A1100;border-radius:7px;padding:5px 14px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;">취소</button>
                      <button @click="saveEditComment(c)" style="background:#7C3AED;color:#fff;border:1.5px solid #1A1100;border-radius:7px;padding:5px 14px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;">저장</button>
                    </div>
                  </div>
                  <div v-else style="font-size:13px;color:#4A3F2A;line-height:1.7;white-space:pre-wrap;word-break:break-word;">{{ c.content }}</div>
                </div>
              </div>
            </div>

            <!-- 코멘트 입력 (관리자만) -->
            <div v-if="isAdmin" style="padding:14px 20px;border-top:2px solid #E8E0D0;flex-shrink:0;background:#fff;">
              <div style="display:flex;gap:8px;align-items:flex-end;">
                <textarea v-model="newCommentContent" rows="3" placeholder="코멘트를 입력하세요..."
                  style="flex:1;border:2px solid #1A1100;border-radius:10px;padding:9px 12px;font-size:13px;font-family:inherit;outline:none;resize:none;transition:border-color 0.12s;"
                  @focus="e=>e.target.style.borderColor='#7C3AED'"
                  @blur="e=>e.target.style.borderColor='#1A1100'"
                  @keydown.ctrl.enter="submitComment"
                  @keydown.meta.enter="submitComment"></textarea>
                <button @click="submitComment" :disabled="!newCommentContent.trim() || commentSubmitting"
                  style="background:#7C3AED;color:#fff;border:2px solid #1A1100;border-radius:10px;padding:10px 18px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;white-space:nowrap;flex-shrink:0;box-shadow:2px 2px 0 #1A1100;transition:all 0.1s;"
                  :style="{ opacity: !newCommentContent.trim() || commentSubmitting ? 0.5 : 1 }">
                  {{ commentSubmitting ? '등록 중...' : '등록' }}
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
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  reports:       { type: Array,   default: () => [] },
  counts:        { type: Object,  default: () => ({}) },
  filters:       { type: Object,  default: () => ({}) },
  weekStart:     { type: String,  default: '' },
  weekEnd:       { type: String,  default: '' },
  weekLabel:     { type: String,  default: '' },
  prevWeek:      { type: String,  default: '' },
  nextWeek:      { type: String,  default: '' },
  isCurrentWeek: { type: Boolean, default: true },
})

const page          = usePage()
const isAdmin       = computed(() => page.props.auth?.user?.role === 'admin')
const currentUserId = computed(() => page.props.auth?.user?.id)

const activeFilter = ref(props.filters?.status ?? 'all')

// 삭제
const deleteTarget = ref(null)
const confirmDelete = (r) => { deleteTarget.value = r }
const doDelete = () => {
  if (!deleteTarget.value) return
  router.delete(`/reports/${deleteTarget.value.id}`, {
    onSuccess: () => { deleteTarget.value = null },
  })
}
const searchInput  = ref(props.filters?.search ?? '')

const fmtShort = (d) => d ? String(d).substring(5, 10).replace('-', '/') : '-'

const AVATAR_COLORS = ['#FD4401','#16a34a','#2563eb','#9333ea','#d97706','#0891b2']
const avatarColor = (id) => AVATAR_COLORS[(id ?? 0) % AVATAR_COLORS.length]

const statsCards = computed(() => [
  { label:'전체 보고서', val: props.counts.all          ?? 0, bg:'#FDCB40', filter:'all' },
  { label:'미제출',      val: props.counts.notSubmitted ?? 0, bg:'#FFF0A0', filter:'not_submitted' },
  { label:'작성중',      val: props.counts.draft        ?? 0, bg:'#F0FDF4', filter:'draft' },
  { label:'제출됨',      val: props.counts.submitted    ?? 0, bg:'#DBEAFE', filter:'submitted' },
  { label:'반려됨',      val: props.counts.rejected     ?? 0, bg:'#FEE2E2', filter:'rejected' },
])

const filterBtns = [
  { val:'all',           label:'전체' },
  { val:'not_submitted', label:'미제출' },
  { val:'draft',         label:'작성중' },
  { val:'submitted',     label:'제출됨' },
  { val:'rejected',      label:'반려됨' },
]

const fmt = (d) => d ? String(d).substring(0, 10) : '-'

const statusBadge = (s) => ({
  draft:         'badge-draft',
  submitted:     'badge-submitted',
  reviewed:      'badge-reviewed',
  rejected:      'badge-rejected',
  not_submitted: 'badge-not-submitted',
})[s] ?? 'badge-draft'

// ── 미제출 알림 채널 선택 모달 ──
const showAlertModal = ref(false)
const alertSending   = ref(false)
const alertChannel   = ref('both')

const alertChannelOptions = [
  { value: 'both',    label: '전체 전송',       desc: 'Webhook 팀 알림 + 카카오톡 개인 메시지 동시 발송' },
  { value: 'webhook', label: 'Webhook 전송만',  desc: '팀 채널(슬랙/Teams/구글챗 등)에만 발송' },
  { value: 'kakao',   label: '카카오톡 전송만', desc: '카카오 연동된 팀원에게 개인 메시지로만 발송' },
]

const sendNotSubmittedAlert = async () => {
  if (!props.weekStart) return
  alertSending.value = true
  try {
    const res = await window.axios.post('/admin/settings/notify-not-submitted', {
      week_start: props.weekStart,
      channels:   alertChannel.value,
    })
    showAlertModal.value = false
    alert(res.data.message)
  } catch {
    alert('알림 발송 실패. 설정을 확인해 주세요.')
  } finally {
    alertSending.value = false
  }
}

// ── 메일 전송 ──────────────────────────────────────
// 전원 제출 완료 여부 (미제출=0 이고 제출된 보고서가 1개 이상)
const canSendMail = computed(() =>
  isAdmin.value &&
  (props.counts.notSubmitted ?? 1) === 0 &&
  (props.counts.submitted ?? 0) > 0
)

// 제출된 보고서 목록 (메일 미리보기용)
const submittedReports = computed(() =>
  props.reports.filter(r => r.status !== 'not_submitted' && r.user)
)

const showMailModal = ref(false)
const mailSending   = ref(false)
const mailForm      = ref({ to: '', ccInput: '', subject: '' })

const openMailModal = async () => {
  // SMTP 기본값 불러오기
  try {
    const res = await window.axios.get('/admin/settings/mail-defaults')
    const d   = res.data
    mailForm.value = {
      to:       d.mail_to ?? '',
      ccInput:  (d.mail_cc ?? []).join(', '),
      subject:  `[${props.weekLabel}] SE팀 주간업무보고`,
    }
  } catch {
    mailForm.value = {
      to:      '',
      ccInput: '',
      subject: `[${props.weekLabel}] SE팀 주간업무보고`,
    }
  }
  showMailModal.value = true
}

const sendWeeklyMail = async () => {
  if (!mailForm.value.to) return
  mailSending.value = true
  try {
    const ccArr = mailForm.value.ccInput
      ? mailForm.value.ccInput.split(',').map(s => s.trim()).filter(Boolean)
      : []
    const res = await window.axios.post('/admin/settings/send-weekly-mail', {
      week_start: props.weekStart,
      to:         mailForm.value.to,
      cc:         ccArr,
      subject:    mailForm.value.subject,
    })
    showMailModal.value = false
    alert('✅ ' + res.data.message)
  } catch (e) {
    alert('❌ ' + (e.response?.data?.message ?? '메일 전송 실패. SMTP 설정을 확인해 주세요.'))
  } finally {
    mailSending.value = false
  }
}
// ── 코멘트 모달 ──────────────────────────────────
const commentModal = ref({
  show: false, loading: false,
  reportId: null, reportUser: '', reportWeek: '',
  report: null,
  comments: [],
})
const newCommentSection  = ref('general')
const newCommentContent  = ref('')
const commentSubmitting  = ref(false)
const editingCommentId   = ref(null)
const editingContent     = ref('')

// 로컬 코멘트 카운트 캐시 (서버 데이터 보완)
const localCommentCounts = ref({})
const commentCountFor = (r) => {
  if (!r.id) return 0
  return localCommentCounts.value[r.id] !== undefined
    ? localCommentCounts.value[r.id]
    : (r.comment_count ?? 0)
}

// 전주 업무 카테고리별 그룹화
const currWorkGroups = computed(() => {
  const items = commentModal.value.report?.curr_work ?? []
  return items.reduce((groups, item) => {
    const cat = item.category || '기타'
    if (!groups[cat]) groups[cat] = []
    groups[cat].push(item)
    return groups
  }, {})
})

const openCommentModal = async (r) => {
  commentModal.value = {
    show: true, loading: true,
    reportId: r.id,
    reportUser: r.user?.name ?? '',
    reportWeek: r.week_label || r.week || '',
    report: r,
    comments: [],
  }
  newCommentContent.value = ''
  newCommentSection.value = 'general'
  editingCommentId.value  = null
  try {
    const res = await window.axios.get(`/reports/${r.id}/comments`)
    commentModal.value.comments = res.data
    localCommentCounts.value[r.id] = res.data.length
  } catch { /* ignore */ }
  finally { commentModal.value.loading = false }
}

const closeCommentModal = () => {
  commentModal.value.show = false
  editingCommentId.value  = null
}

const submitComment = async () => {
  if (!newCommentContent.value.trim() || commentSubmitting.value) return
  commentSubmitting.value = true
  try {
    const res = await window.axios.post(`/reports/${commentModal.value.reportId}/comments`, {
      content: newCommentContent.value.trim(),
    })
    commentModal.value.comments.push(res.data)
    newCommentContent.value = ''
    localCommentCounts.value[commentModal.value.reportId] = commentModal.value.comments.length
  } catch (e) {
    console.error('코멘트 등록 실패:', e.response?.data ?? e.message)
    alert('코멘트 등록에 실패했습니다. (' + (e.response?.status ?? '네트워크 오류') + ')')
  } finally { commentSubmitting.value = false }
}

const startEditComment = (c) => {
  editingCommentId.value = c.id
  editingContent.value   = c.content
}

const saveEditComment = async (c) => {
  try {
    await window.axios.put(`/report-comments/${c.id}`, { content: editingContent.value.trim() })
    c.content = editingContent.value.trim()
    editingCommentId.value = null
  } catch { /* ignore */ }
}

const deleteComment = async (c) => {
  try {
    await window.axios.delete(`/report-comments/${c.id}`)
    const idx = commentModal.value.comments.findIndex(x => x.id === c.id)
    if (idx !== -1) commentModal.value.comments.splice(idx, 1)
    localCommentCounts.value[commentModal.value.reportId] = commentModal.value.comments.length
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
// ─────────────────────────────────────────────────

let timer = null
const onSearch  = () => { clearTimeout(timer); timer = setTimeout(applyFilter, 400) }
const setFilter = (f) => { activeFilter.value = f; applyFilter() }
const goWeek    = (week) => {
  router.get('/reports', {
    week:   week || '',
    status: activeFilter.value === 'all' ? '' : activeFilter.value,
    search: searchInput.value,
  }, { preserveState: false, replace: true })
}
const applyFilter = () => {
  router.get('/reports', {
    week:   props.weekStart,
    status: activeFilter.value === 'all' ? '' : activeFilter.value,
    search: searchInput.value,
  }, { preserveState: true, replace: true })
}
</script>

<style scoped>
@keyframes spin { to { transform: rotate(360deg); } }

/* ===========================
   보고서 목록 반응형
   =========================== */

/* 통계 그리드: 모바일에서 2열 */
@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr) !important;
  }
  /* 헤더 상단 버튼 영역 */
  .index-header-actions {
    width: 100% !important;
    justify-content: flex-start !important;
  }
}

/* 코멘트 모달: 모바일에서 단일 열 */
@media (max-width: 700px) {
  .comment-modal-inner {
    height: 95vh !important;
  }
  .comment-modal-inner > div:last-child {
    flex-direction: column !important;
  }
  .comment-modal-inner > div:last-child > div:first-child {
    width: 100% !important;
    border-right: none !important;
    border-bottom: 2px solid #E8E0D0;
    max-height: 220px;
  }
}

/* 테이블 스크롤 영역: 스크롤바 스타일 */
.table-scroll-wrap {
  -webkit-overflow-scrolling: touch;
}
.table-scroll-wrap::-webkit-scrollbar {
  height: 4px;
}
.table-scroll-wrap::-webkit-scrollbar-track {
  background: #F5EDDB;
}
.table-scroll-wrap::-webkit-scrollbar-thumb {
  background: #C8BFA8;
  border-radius: 4px;
}
</style>
