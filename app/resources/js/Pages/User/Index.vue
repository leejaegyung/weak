<template>
  <AppLayout page-title="사용자 관리">
    <!-- 헤더 -->
    <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
      <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
          <div style="background:#FD4401;border:2px solid #1A1100;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
          </div>
          <h1 style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:26px;font-weight:700;letter-spacing:-0.03em;">사용자 관리</h1>
        </div>
        <p style="color:#9A8F7A;font-size:13px;margin-left:42px;">
          <template v-if="activeTab === 'users'">총 {{ users.length }}명이 등록되어 있습니다</template>
          <template v-else>가입 승인 대기 중인 사용자 {{ pending.length }}명</template>
        </p>
      </div>
      <button v-if="activeTab === 'users'" @click="openCreate" class="btn-primary">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
        사용자 추가
      </button>
    </div>

    <!-- 탭 네비게이션 -->
    <div style="display:flex;gap:0;margin-bottom:20px;border:2px solid #1A1100;border-radius:12px;overflow:hidden;width:fit-content;box-shadow:3px 3px 0 #1A1100;">
      <!-- 사용자 관리 탭 -->
      <button @click="activeTab='users'"
        style="display:flex;align-items:center;gap:8px;padding:10px 20px;border:none;cursor:pointer;font-size:13px;font-weight:700;font-family:inherit;transition:all 0.12s;border-right:2px solid #1A1100;"
        :style="activeTab==='users'
          ? 'background:#FFF0F3;color:#1A1100;'
          : 'background:#F5EDDB;color:#9A8F7A;'">
        <div style="width:24px;height:24px;border-radius:6px;display:flex;align-items:center;justify-content:center;"
          :style="activeTab==='users' ? 'background:#FD440115;' : 'background:rgba(26,17,0,0.05);'">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" :stroke="activeTab==='users' ? '#FD4401' : '#9A8F7A'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
        </div>
        사용자 관리
      </button>

      <!-- 가입 승인 탭 -->
      <button @click="activeTab='pending'"
        style="display:flex;align-items:center;gap:8px;padding:10px 20px;border:none;cursor:pointer;font-size:13px;font-weight:700;font-family:inherit;transition:all 0.12s;position:relative;"
        :style="activeTab==='pending'
          ? 'background:#FFF0A0;color:#1A1100;'
          : 'background:#F5EDDB;color:#9A8F7A;'">
        <div style="width:24px;height:24px;border-radius:6px;display:flex;align-items:center;justify-content:center;"
          :style="activeTab==='pending' ? 'background:rgba(253,196,0,0.2);' : 'background:rgba(26,17,0,0.05);'">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" :stroke="activeTab==='pending' ? '#1A1100' : '#9A8F7A'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM19 8v6M22 11h-6"/>
          </svg>
        </div>
        가입 승인
        <span v-if="pending.length > 0"
          style="background:#DC2626;color:#fff;font-size:10px;font-weight:800;padding:1px 7px;border-radius:99px;font-family:'Space Grotesk',sans-serif;margin-left:2px;">
          {{ pending.length }}
        </span>
      </button>
    </div>

    <!-- ── 사용자 관리 탭 ── -->
    <template v-if="activeTab === 'users'">
      <!-- 유저 카드 그리드 -->
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;margin-bottom:24px;">
        <div v-for="(u, i) in users" :key="u.id"
          class="card"
          :style="{ padding:'20px', cursor:'pointer', transition:'transform 0.1s,box-shadow 0.1s', background: cardColors[i % cardColors.length] }"
          @click="openEdit(u)"
          @mouseenter="e=>{e.currentTarget.style.transform='translate(-2px,-2px)';e.currentTarget.style.boxShadow='6px 6px 0 #1A1100';}"
          @mouseleave="e=>{e.currentTarget.style.transform='none';e.currentTarget.style.boxShadow='4px 4px 0 #1A1100';}">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px;">
            <div :style="{ width:'44px', height:'44px', borderRadius:'50%', background: avatarColor(u), border:'2px solid #1A1100', display:'flex', alignItems:'center', justifyContent:'center', color:'#fff', fontSize:'18px', fontWeight:'700', flexShrink:0, fontFamily:'\'Space Grotesk\',sans-serif' }">
              {{ u.name.charAt(0) }}
            </div>
            <span :style="u.is_active ? 'background:#DCFCE7;color:#16A34A;border:1.5px solid #16A34A;' : 'background:#F3F4F6;color:#6B7280;border:1.5px solid #D1D5DB;'"
              style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:99px;">
              {{ u.is_active ? '활성' : '비활성' }}
            </span>
          </div>
          <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:16px;font-weight:700;margin-bottom:2px;">{{ u.name }}</div>
          <div style="font-size:12px;color:#4A3F2A;margin-bottom:12px;">@{{ u.username }}</div>
          <div style="display:flex;gap:5px;flex-wrap:wrap;">
            <span style="background:#FD4401;color:#fff;font-size:10px;font-weight:700;padding:2px 9px;border-radius:99px;border:1.5px solid #1A1100;font-family:'Space Grotesk',sans-serif;">{{ u.role === 'admin' ? '관리자' : '일반 사용자' }}</span>
            <span v-if="u.position" style="background:rgba(26,17,0,0.06);color:#4A3F2A;font-size:10px;font-weight:600;padding:2px 9px;border-radius:99px;border:1.5px solid rgba(26,17,0,0.15);">{{ u.position }}</span>
            <span v-if="u.last_login_at" style="margin-left:auto;font-size:10px;color:#9A8F7A;align-self:center;">{{ u.last_login_at?.substring(0,10) }}</span>
          </div>
        </div>
      </div>

      <!-- 전체 목록 테이블 -->
      <div class="card" style="overflow:hidden;padding:0;">
        <div style="padding:14px 20px;border-bottom:2px solid #1A1100;display:flex;justify-content:space-between;align-items:center;background:#F5EDDB;">
          <span style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:13px;font-weight:700;">전체 목록</span>
          <span style="font-size:12px;color:#9A8F7A;font-weight:600;">{{ users.length }}명</span>
        </div>
        <div class="user-table-scroll" style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
        <div style="min-width:600px;">
        <div style="display:grid;grid-template-columns:2fr 2fr 1fr 1.3fr 1.3fr 1fr 1fr 44px;padding:10px 20px;border-bottom:2px solid #1A1100;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#9A8F7A;font-family:'Space Grotesk','Noto Sans KR',sans-serif;background:#F5EDDB;">
          <span>이름</span><span>아이디</span><span>직급</span><span>권한</span><span>마지막 접속</span><span>숨김</span><span>상태</span><span></span>
        </div>
        <div v-for="(u, i) in users" :key="u.id"
          style="display:grid;grid-template-columns:2fr 2fr 1fr 1.3fr 1.3fr 1fr 1fr 44px;padding:12px 20px;align-items:center;transition:background 0.1s;cursor:pointer;"
          :style="{ borderBottom: i < users.length-1 ? '1.5px solid #F5EDDB' : 'none' }"
          @click="openEdit(u)"
          @mouseenter="e=>e.currentTarget.style.background='#FFF8EE'"
          @mouseleave="e=>e.currentTarget.style.background='transparent'">
          <!-- 이름 -->
          <div style="display:flex;align-items:center;gap:9px;min-width:0;">
            <div :style="{ width:'28px', height:'28px', borderRadius:'50%', background: avatarColor(u), border:'2px solid #1A1100', display:'flex', alignItems:'center', justifyContent:'center', color:'#fff', fontSize:'11px', fontWeight:'700', flexShrink:0, fontFamily:'\'Space Grotesk\',sans-serif' }">
              {{ u.name.charAt(0) }}
            </div>
            <span style="font-size:13px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ u.name }}</span>
          </div>
          <!-- 아이디 -->
          <span style="font-size:12px;color:#4A3F2A;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">@{{ u.username }}</span>
          <!-- 직급 -->
          <span style="font-size:12px;font-weight:600;">{{ u.position || '-' }}</span>
          <!-- 권한 -->
          <div>
            <span style="display:inline-flex;align-items:center;font-size:11px;font-weight:700;padding:3px 10px;border-radius:99px;white-space:nowrap;"
              :style="u.role==='admin' ? 'background:#FD440115;color:#FD4401;border:1.5px solid #FD4401;' : 'background:rgba(26,17,0,0.05);color:#4A3F2A;border:1.5px solid rgba(26,17,0,0.15);'">
              {{ u.role === 'admin' ? '관리자' : '일반 사용자' }}
            </span>
          </div>
          <!-- 마지막 접속 -->
          <span style="font-size:11px;color:#9A8F7A;white-space:nowrap;">{{ u.last_login_at ?? '-' }}</span>
          <!-- 숨김 토글 -->
          <div @click.stop>
            <button @click="toggleHidden(u)"
              :title="u.is_hidden ? '클릭하면 표시됩니다' : '클릭하면 숨겨집니다'"
              style="display:inline-flex;align-items:center;gap:4px;font-size:11px;padding:3px 10px;border-radius:99px;cursor:pointer;font-weight:700;transition:all 0.1s;white-space:nowrap;"
              :style="u.is_hidden
                ? 'background:#F3F4F6;color:#6B7280;border:1.5px solid #D1D5DB;'
                : 'background:#EFF6FF;color:#2563EB;border:1.5px solid #93C5FD;'">
              <svg v-if="u.is_hidden" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>
              </svg>
              <svg v-else width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
              {{ u.is_hidden ? '숨김' : '표시중' }}
            </button>
          </div>
          <!-- 상태 토글 -->
          <div @click.stop>
            <button @click="toggleActive(u)"
              style="display:inline-flex;align-items:center;gap:4px;font-size:11px;padding:3px 10px;border-radius:99px;cursor:pointer;font-weight:700;transition:all 0.1s;white-space:nowrap;"
              :style="u.is_active
                ? 'background:#DCFCE7;color:#16A34A;border:1.5px solid #16A34A;'
                : 'background:#F3F4F6;color:#6B7280;border:1.5px solid #D1D5DB;'">
              <span style="width:6px;height:6px;border-radius:50%;flex-shrink:0;"
                :style="u.is_active ? 'background:#16A34A;' : 'background:#9CA3AF;'"></span>
              {{ u.is_active ? '활성' : '비활성' }}
            </button>
          </div>
          <!-- 삭제 버튼 -->
          <div @click.stop style="display:flex;justify-content:center;">
            <button @click="confirmDelete(u)"
              title="사용자 삭제"
              style="background:none;border:none;cursor:pointer;color:#D0C9BC;padding:5px;border-radius:6px;transition:color 0.1s;display:flex;align-items:center;"
              @mouseenter="e=>e.currentTarget.style.color='#DC2626'"
              @mouseleave="e=>e.currentTarget.style.color='#D0C9BC'">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                <path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
              </svg>
            </button>
          </div>
        </div>
        <div v-if="users.length === 0"
          style="padding:48px 20px;text-align:center;color:#9A8F7A;font-size:13px;">등록된 사용자가 없습니다</div>
        </div><!-- min-width -->
        </div><!-- user-table-scroll -->
      </div>
    </template>

    <!-- ── 가입 승인 탭 ── -->
    <template v-else>
      <div class="card" style="overflow:hidden;padding:0;">
        <!-- 컬럼 헤더 -->
        <div style="display:grid;grid-template-columns:2fr 2fr 1fr 1.5fr 1.5fr;padding:10px 20px;border-bottom:2px solid #1A1100;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#9A8F7A;font-family:'Space Grotesk','Noto Sans KR',sans-serif;background:#F5EDDB;">
          <span>이름</span><span>아이디</span><span>직급</span><span>신청일시</span><span>처리</span>
        </div>

        <div v-for="(u, i) in pending" :key="u.id"
          style="display:grid;grid-template-columns:2fr 2fr 1fr 1.5fr 1.5fr;padding:14px 20px;align-items:center;transition:background 0.1s;"
          :style="{ borderBottom: i < pending.length-1 ? '1.5px solid #F5EDDB' : 'none' }"
          @mouseenter="e=>e.currentTarget.style.background='#FFF8EE'"
          @mouseleave="e=>e.currentTarget.style.background='transparent'">

          <div style="display:flex;align-items:center;gap:10px;">
            <div :style="{ width:'30px', height:'30px', borderRadius:'50%', background: pendingAvatarColor(u.id), border:'2px solid #1A1100', display:'flex', alignItems:'center', justifyContent:'center', color:'#fff', fontSize:'12px', fontWeight:'700', flexShrink:0, fontFamily:'\'Space Grotesk\',sans-serif' }">
              {{ u.name?.charAt(0) ?? '?' }}
            </div>
            <div>
              <div style="font-size:13px;font-weight:700;">{{ u.name }}</div>
              <div style="font-size:11px;color:#9A8F7A;">{{ u.email || '-' }}</div>
            </div>
          </div>

          <span style="font-size:12px;color:#4A3F2A;">@{{ u.username }}</span>
          <span style="font-size:12px;font-weight:600;">{{ u.position || '-' }}</span>
          <span style="font-size:12px;color:#9A8F7A;">{{ u.created_at }}</span>

          <div style="display:flex;gap:6px;">
            <button @click="approve(u.id)"
              style="display:flex;align-items:center;gap:4px;background:#DCFCE7;color:#16A34A;border:2px solid #16A34A;border-radius:8px;padding:5px 12px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:2px 2px 0 #16A34A;transition:transform 0.1s;"
              @mouseenter="e=>{e.currentTarget.style.transform='translate(-1px,-1px)';}"
              @mouseleave="e=>{e.currentTarget.style.transform='none';}">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
              승인
            </button>
            <button @click="rejectReg(u.id)"
              style="display:flex;align-items:center;gap:4px;background:#FEE2E2;color:#DC2626;border:2px solid #DC2626;border-radius:8px;padding:5px 12px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:2px 2px 0 #DC2626;transition:transform 0.1s;"
              @mouseenter="e=>{e.currentTarget.style.transform='translate(-1px,-1px)';}"
              @mouseleave="e=>{e.currentTarget.style.transform='none';}">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
              거절
            </button>
          </div>
        </div>

        <div v-if="pending.length === 0"
          style="padding:56px 20px;text-align:center;color:#9A8F7A;font-size:13px;">
          <div style="font-size:32px;margin-bottom:8px;">✓</div>
          대기 중인 가입 신청이 없습니다
        </div>
      </div>
    </template>

    <!-- 삭제 확인 모달 -->
    <div v-if="deleteTarget"
      style="position:fixed;inset:0;background:rgba(26,17,0,0.45);display:flex;align-items:center;justify-content:center;z-index:100;backdrop-filter:blur(3px);padding:16px;"
      @click.self="deleteTarget=null">
      <div class="card" style="width:400px;max-width:100%;max-height:90vh;overflow-y:auto;padding:28px;text-align:center;">
        <div style="width:52px;height:52px;background:#FEE2E2;border:2px solid #DC2626;border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
            <path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
          </svg>
        </div>
        <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:17px;font-weight:800;color:#1A1100;margin-bottom:10px;">사용자를 삭제할까요?</div>
        <div style="font-size:13px;color:#9A8F7A;line-height:1.8;margin-bottom:22px;">
          <strong style="color:#1A1100;">{{ deleteTarget?.name }}</strong> (@{{ deleteTarget?.username }}) 계정과<br>
          해당 사용자의 <strong style="color:#DC2626;">모든 보고서 및 일정</strong>이<br>
          영구적으로 삭제됩니다. 되돌릴 수 없습니다.
        </div>
        <div style="display:flex;gap:8px;justify-content:center;">
          <button @click="deleteTarget=null" class="btn-secondary">취소</button>
          <button @click="doDelete"
            style="background:#DC2626;color:#fff;border:2px solid #DC2626;border-radius:10px;padding:8px 22px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:2px 2px 0 #991B1B;transition:all 0.1s;">
            삭제하기
          </button>
        </div>
      </div>
    </div>

    <!-- 추가/수정 모달 -->
    <div v-if="showModal"
      style="position:fixed;inset:0;background:rgba(26,17,0,0.45);display:flex;align-items:center;justify-content:center;z-index:100;backdrop-filter:blur(3px);padding:16px;"
      @click.self="closeModal">
      <div class="card" style="width:420px;max-width:100%;max-height:90vh;overflow-y:auto;padding:28px;">
        <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:18px;font-weight:800;margin-bottom:20px;letter-spacing:-0.02em;">
          {{ editTarget ? '사용자 수정' : '사용자 추가' }}
        </div>
        <form @submit.prevent="submitUser" style="display:flex;flex-direction:column;gap:12px;">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div>
              <label style="font-size:11px;color:#9A8F7A;font-weight:700;display:block;margin-bottom:6px;font-family:'Space Grotesk','Noto Sans KR',sans-serif;">이름 *</label>
              <input v-model="userForm.name" type="text" class="input-field" />
              <p v-if="userForm.errors.name" style="margin-top:4px;font-size:11px;color:#FD4401;">{{ userForm.errors.name }}</p>
            </div>
            <div>
              <label style="font-size:11px;color:#9A8F7A;font-weight:700;display:block;margin-bottom:6px;font-family:'Space Grotesk','Noto Sans KR',sans-serif;">아이디 *</label>
              <input v-model="userForm.username" type="text" class="input-field" />
              <p v-if="userForm.errors.username" style="margin-top:4px;font-size:11px;color:#FD4401;">{{ userForm.errors.username }}</p>
            </div>
            <div>
              <label style="font-size:11px;color:#9A8F7A;font-weight:700;display:block;margin-bottom:6px;font-family:'Space Grotesk','Noto Sans KR',sans-serif;">비밀번호 {{ editTarget ? '(변경 시만)' : '*' }}</label>
              <input v-model="userForm.password" type="password" class="input-field" />
              <p v-if="userForm.errors.password" style="margin-top:4px;font-size:11px;color:#FD4401;">{{ userForm.errors.password }}</p>
            </div>
            <div>
              <label style="font-size:11px;color:#9A8F7A;font-weight:700;display:block;margin-bottom:6px;font-family:'Space Grotesk','Noto Sans KR',sans-serif;">직급</label>
              <input v-model="userForm.position" type="text" class="input-field" placeholder="예: 대리" />
            </div>
            <div>
              <label style="font-size:11px;color:#9A8F7A;font-weight:700;display:block;margin-bottom:6px;font-family:'Space Grotesk','Noto Sans KR',sans-serif;">이메일</label>
              <input v-model="userForm.email" type="email" class="input-field" />
            </div>
            <div>
              <label style="font-size:11px;color:#9A8F7A;font-weight:700;display:block;margin-bottom:6px;font-family:'Space Grotesk','Noto Sans KR',sans-serif;">권한 *</label>
              <select v-model="userForm.role" class="input-field">
                <option value="user">일반 사용자</option>
                <option value="admin">관리자</option>
              </select>
            </div>
          </div>
          <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:8px;">
            <button type="button" class="btn-secondary" @click="closeModal">취소</button>
            <button type="submit" :disabled="userForm.processing" class="btn-primary">
              {{ userForm.processing ? '처리 중...' : (editTarget ? '수정' : '추가하기') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  users:   { type: Array, default: () => [] },
  pending: { type: Array, default: () => [] },
})

const activeTab = ref('users')

const AVATAR_COLORS = ['#FD4401','#16a34a','#2563eb','#9333ea','#d97706','#0891b2','#dc2626','#65a30d']
const cardColors    = ['#FFF0A0','#EEF2FF','#F0FDF4','#FFF0F3','#F5F3FF','#FFF8EE']

const avatarColor        = (u) => AVATAR_COLORS[u.id % AVATAR_COLORS.length]
const pendingAvatarColor = (id) => AVATAR_COLORS[(id ?? 0) % AVATAR_COLORS.length]

const showModal  = ref(false)
const editTarget = ref(null)

const userForm = useForm({ name: '', username: '', email: '', password: '', position: '', role: 'user' })

const openCreate = () => {
  editTarget.value = null
  userForm.reset()
  userForm.role = 'user'
  showModal.value = true
}
const openEdit = (u) => {
  editTarget.value = u
  userForm.name     = u.name
  userForm.username = u.username
  userForm.email    = u.email    ?? ''
  userForm.password = ''
  userForm.position = u.position ?? ''
  userForm.role     = u.role
  showModal.value   = true
}
const closeModal = () => { showModal.value = false; editTarget.value = null }
const submitUser = () => {
  if (editTarget.value) {
    userForm.put(`/admin/users/${editTarget.value.id}`, { onSuccess: closeModal })
  } else {
    userForm.post('/admin/users', { onSuccess: closeModal })
  }
}
const toggleActive = (u) => router.post(`/admin/users/${u.id}/toggle-active`)
const toggleHidden = (u) => router.post(`/admin/users/${u.id}/toggle-hidden`)

// ── 사용자 삭제 ──
const deleteTarget = ref(null)
const confirmDelete = (u) => { deleteTarget.value = u }
const doDelete = () => {
  if (!deleteTarget.value) return
  router.delete(`/admin/users/${deleteTarget.value.id}`, {
    onSuccess: () => { deleteTarget.value = null },
  })
}

const approve   = (id) => router.post(`/admin/users/${id}/approve`)
const rejectReg = (id) => router.post(`/admin/users/${id}/reject-registration`)
</script>

<style scoped>
/* 사용자 테이블 스크롤 */
.user-table-scroll { -webkit-overflow-scrolling: touch; }
.user-table-scroll::-webkit-scrollbar { height: 4px; }
.user-table-scroll::-webkit-scrollbar-track { background: #F5EDDB; }
.user-table-scroll::-webkit-scrollbar-thumb { background: #C8BFA8; border-radius: 4px; }
</style>
