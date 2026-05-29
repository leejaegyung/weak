<template>
  <div class="app-root" style="display:flex;flex-direction:column;height:100vh;background:#FFF8EE;overflow:hidden;">

    <!-- ── 오렌지 상단 헤더 ── -->
    <header style="height:56px;background:#FD4401;border-bottom:2px solid #1A1100;display:flex;align-items:center;justify-content:space-between;padding:0 20px;flex-shrink:0;z-index:30;position:relative;">
      <!-- 로고 -->
      <div style="display:flex;align-items:center;gap:12px;">
        <div style="display:flex;align-items:center;gap:8px;">
          <div style="width:30px;height:30px;border-radius:8px;background:#FDCB40;border:2px solid #1A1100;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M16 13H8M16 17H8M10 9H8"/>
            </svg>
          </div>
          <span style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:16px;font-weight:800;color:#fff;letter-spacing:-0.02em;">주간업무보고</span>
        </div>
        <span class="header-page-chip" style="background:rgba(255,255,255,0.2);border:1.5px solid rgba(255,255,255,0.4);color:#fff;font-size:11px;font-weight:700;padding:2px 9px;border-radius:99px;">
          {{ pageTitle || auth?.user?.name }}
        </span>
      </div>

      <!-- 우측: 알림 + 유저 + 로그아웃 -->
      <div style="display:flex;align-items:center;gap:10px;">
        <!-- 알림 벨 -->
        <div style="position:relative;" v-click-outside="closeNotifications">
          <button @click="toggleNotifications"
            style="background:rgba(255,255,255,0.2);border:1.5px solid rgba(255,255,255,0.4);border-radius:8px;padding:6px 8px;cursor:pointer;color:#fff;display:flex;align-items:center;position:relative;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <!-- 미읽음 뱃지 -->
            <span v-if="unreadCount > 0"
              style="position:absolute;top:-5px;right:-5px;background:#FDCB40;color:#1A1100;font-size:10px;font-weight:800;font-family:'Space Grotesk',sans-serif;border-radius:99px;min-width:16px;height:16px;display:flex;align-items:center;justify-content:center;padding:0 3px;border:1.5px solid #1A1100;">
              {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
          </button>

          <!-- 알림 드롭다운 패널 -->
          <Transition name="notif-drop">
            <div v-if="showNotifications"
              class="notif-panel"
              style="position:absolute;top:calc(100% + 8px);right:0;width:340px;background:#fff;border:2px solid #1A1100;border-radius:16px;box-shadow:4px 4px 0 #1A1100;z-index:200;overflow:hidden;">
              <!-- 패널 헤더 -->
              <div style="padding:12px 16px;border-bottom:2px solid #1A1100;background:#F5EDDB;display:flex;justify-content:space-between;align-items:center;">
                <span style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:13px;font-weight:800;">알림</span>
                <button v-if="notifications.some(n=>!n.is_read)" @click="markAllRead"
                  style="background:none;border:none;cursor:pointer;font-size:11px;color:#9A8F7A;font-weight:700;font-family:inherit;">
                  모두 읽음
                </button>
              </div>

              <!-- 알림 목록 -->
              <div style="max-height:360px;overflow-y:auto;">
                <div v-if="notifLoading" style="padding:32px;text-align:center;color:#9A8F7A;font-size:12px;">불러오는 중...</div>
                <div v-else-if="notifications.length === 0" style="padding:32px;text-align:center;color:#9A8F7A;font-size:12px;">알림이 없습니다</div>
                <div v-else>
                  <div v-for="n in notifications" :key="n.id"
                    @click="openNotification(n)"
                    :style="{
                      padding:'12px 16px',
                      borderBottom:'1px solid #F5EDDB',
                      cursor: n.link ? 'pointer' : 'default',
                      background: n.is_read ? 'transparent' : '#FFFBEB',
                      transition:'background 0.1s',
                    }"
                    @mouseenter="e=>{ if(!n.is_read || n.link) e.currentTarget.style.background='#FFF8EE' }"
                    @mouseleave="e=>e.currentTarget.style.background=n.is_read?'transparent':'#FFFBEB'">
                    <div style="display:flex;gap:10px;align-items:flex-start;">
                      <!-- 타입 아이콘 -->
                      <div :style="{
                        width:'28px',height:'28px',borderRadius:'8px',flexShrink:0,
                        background: n.type==='rejected' ? '#FEE2E2' : '#DBEAFE',
                        border:'1.5px solid #1A1100',
                        display:'flex',alignItems:'center',justifyContent:'center',
                      }">
                        <svg v-if="n.type==='rejected'" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                        <svg v-else width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                      </div>
                      <div style="flex:1;min-width:0;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2px;">
                          <span style="font-size:12px;font-weight:700;color:#1A1100;">{{ n.title }}</span>
                          <span v-if="!n.is_read" style="width:6px;height:6px;border-radius:50%;background:#FD4401;flex-shrink:0;margin-left:6px;"></span>
                        </div>
                        <div v-if="n.body" style="font-size:11px;color:#4A3F2A;line-height:1.5;margin-bottom:3px;">{{ n.body }}</div>
                        <div style="font-size:10px;color:#9A8F7A;">{{ n.created_at }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </Transition>
        </div>
        <Link href="/profile"
          class="header-user-info"
          style="display:flex;align-items:center;gap:8px;background:rgba(255,255,255,0.15);border:1.5px solid rgba(255,255,255,0.3);border-radius:10px;padding:5px 10px;text-decoration:none;transition:background 0.1s;"
          title="개인설정"
          @mouseenter="e=>e.currentTarget.style.background='rgba(255,255,255,0.25)'"
          @mouseleave="e=>e.currentTarget.style.background='rgba(255,255,255,0.15)'">
          <div style="width:24px;height:24px;border-radius:50%;background:#FDCB40;border:2px solid #1A1100;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#1A1100;flex-shrink:0;">
            {{ auth?.user?.name?.charAt(0) }}
          </div>
          <span style="font-size:13px;font-weight:700;color:#fff;">{{ auth?.user?.name }}</span>
          <span style="font-size:11px;color:rgba(255,255,255,0.7);">{{ auth?.user?.role === 'admin' ? '관리자' : '사원' }}</span>
        </Link>
        <button @click="logout"
          style="background:none;border:none;cursor:pointer;color:rgba(255,255,255,0.8);display:flex;align-items:center;padding:4px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.8)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>
          </svg>
        </button>
      </div>
    </header>

    <div class="app-body" style="display:flex;flex:1;overflow:hidden;">

      <!-- ── 사이드바 ── -->
      <aside class="app-sidebar" style="width:180px;background:#F5EDDB;border-right:2px solid #1A1100;display:flex;flex-direction:column;padding:20px 8px;flex-shrink:0;overflow-y:auto;">

        <div style="font-size:10px;color:#9A8F7A;font-weight:800;text-transform:uppercase;letter-spacing:0.1em;padding:0 8px;margin-bottom:10px;font-family:'Space Grotesk','Noto Sans KR',sans-serif;">
          메뉴
        </div>

        <nav style="display:flex;flex-direction:column;gap:4px;">
          <Link href="/schedules"
            class="nav-item"
            :style="isActive('/schedules') ? { background:'#FDCB40', borderColor:'#1A1100', boxShadow:'2px 2px 0 #1A1100', fontWeight:'700' } : {}">
            <div :style="navIconStyle(isActive('/schedules'))">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M8 2v3M16 2v3M3.5 9.5h17M3 6.5h18a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V7.5a1 1 0 0 1 1-1z"/>
              </svg>
            </div>
            팀 일정판
          </Link>

          <Link href="/reports"
            class="nav-item"
            :style="isActive('/reports') && !isActive('/reports/create') ? { background:'#DBEAFE', borderColor:'#1A1100', boxShadow:'2px 2px 0 #1A1100', fontWeight:'700' } : {}">
            <div :style="navIconStyle(isActive('/reports') && !isActive('/reports/create'))">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M16 13H8M16 17H8M10 9H8"/>
              </svg>
            </div>
            보고서 목록
          </Link>

          <Link href="/reports/create"
            class="nav-item"
            :style="isActive('/reports/create') ? { background:'#F0FDF4', borderColor:'#1A1100', boxShadow:'2px 2px 0 #1A1100', fontWeight:'700' } : {}">
            <div :style="navIconStyle(isActive('/reports/create'))">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
              </svg>
            </div>
            보고서 작성
          </Link>

          <Link href="/profile"
            class="nav-item"
            :style="isActive('/profile') ? { background:'#FFF0F0', borderColor:'#1A1100', boxShadow:'2px 2px 0 #1A1100', fontWeight:'700' } : {}">
            <div :style="navIconStyle(isActive('/profile'))">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/>
              </svg>
            </div>
            개인설정
          </Link>

          <!-- 알림 설정 — 아코디언 서브메뉴 -->
          <template v-if="auth?.user?.role === 'admin'">
            <!-- 부모 토글 버튼 -->
            <button type="button" @click="settingsOpen = !settingsOpen"
              class="nav-item"
              style="width:100%;text-align:left;background:none;border:none;cursor:pointer;"
              :style="isActive('/admin/settings') ? { background:'#F5F3FF', borderColor:'#1A1100', boxShadow:'2px 2px 0 #1A1100', fontWeight:'700' } : {}">
              <div :style="navIconStyle(isActive('/admin/settings'))">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
              </div>
              알림 설정
              <svg :style="{ marginLeft:'4px', transition:'transform 0.2s', transform: settingsOpen ? 'rotate(180deg)' : 'none', flexShrink:0 }"
                width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"/>
              </svg>
            </button>

            <!-- 서브메뉴 -->
            <Transition name="submenu">
              <div v-if="settingsOpen" style="display:flex;flex-direction:column;gap:2px;padding-left:14px;margin-top:2px;">
                <Link href="/admin/settings/webhook"
                  class="nav-item"
                  style="font-size:12px;"
                  :style="isActive('/admin/settings/webhook') ? { background:'#EDE9FE', borderColor:'#1A1100', boxShadow:'2px 2px 0 #1A1100', fontWeight:'700' } : {}">
                  <div :style="{ ...navIconStyle(isActive('/admin/settings/webhook')), width:'20px', height:'20px', borderRadius:'6px' }">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 7h3a5 5 0 0 1 5 5 5 5 0 0 1-5 5h-3m-6 0H6a5 5 0 0 1-5-5 5 5 0 0 1 5-5h3M8 12h8"/></svg>
                  </div>
                  Webhook
                </Link>
                <Link href="/admin/settings/kakao"
                  class="nav-item"
                  style="font-size:12px;"
                  :style="isActive('/admin/settings/kakao') ? { background:'#FEF9C3', borderColor:'#1A1100', boxShadow:'2px 2px 0 #1A1100', fontWeight:'700' } : {}">
                  <div :style="{ ...navIconStyle(isActive('/admin/settings/kakao')), width:'20px', height:'20px', borderRadius:'6px' }">
                    <svg width="11" height="11" viewBox="0 0 512 512" fill="#1A1100"><path d="M255.5 48C141.1 48 48 126.1 48 222.3c0 64.3 40.5 120.8 101.3 153.2l-21.7 80.6c-1.9 7.2 5.8 13.1 12.2 9.1L233.8 401c7.1.8 14.4 1.2 21.7 1.2 114.4 0 207.5-78.1 207.5-174.3S369.9 48 255.5 48z"/></svg>
                  </div>
                  카카오 연동
                </Link>
                <Link href="/admin/settings/smtp"
                  class="nav-item"
                  style="font-size:12px;"
                  :style="isActive('/admin/settings/smtp') ? { background:'#DCFCE7', borderColor:'#1A1100', boxShadow:'2px 2px 0 #1A1100', fontWeight:'700' } : {}">
                  <div :style="{ ...navIconStyle(isActive('/admin/settings/smtp')), width:'20px', height:'20px', borderRadius:'6px' }">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                  </div>
                  메일(SMTP) 설정
                </Link>
              </div>
            </Transition>
          </template>

          <Link v-if="auth?.user?.role === 'admin'"
            href="/admin/users"
            class="nav-item"
            :style="isActive('/admin/users')
              ? { background:'#FFF0F3', borderColor:'#1A1100', boxShadow:'2px 2px 0 #1A1100', fontWeight:'700' }
              : pendingCount > 0 ? { background:'#FEE2E2', borderColor:'#DC2626', fontWeight:'700' } : {}">
            <div :style="navIconStyle(isActive('/admin/users'))">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
              </svg>
            </div>
            사용자 관리
            <span v-if="pendingCount > 0"
              style="margin-left:4px;background:#DC2626;color:#fff;font-size:10px;font-weight:800;padding:1px 7px;border-radius:99px;font-family:'Space Grotesk',sans-serif;">
              {{ pendingCount }}
            </span>
          </Link>
        </nav>

        <!-- 이번 주 위젯 -->
        <div style="margin-top:auto;padding-top:20px;">
          <div style="background:#FDCB40;border:2px solid #1A1100;border-radius:16px;box-shadow:4px 4px 0 #1A1100;padding:16px;">
            <div style="font-size:10px;font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-weight:800;text-transform:uppercase;letter-spacing:0.08em;color:#4A3F2A;margin-bottom:8px;">이번 주</div>
            <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:18px;font-weight:800;margin-bottom:3px;letter-spacing:-0.02em;">{{ currentWeek?.label }}</div>
            <div style="font-size:12px;color:#4A3F2A;margin-bottom:12px;">{{ currentWeek?.dateRange }}</div>
            <div style="display:flex;align-items:center;gap:8px;">
              <div style="flex:1;height:6px;background:rgba(26,17,0,0.15);border-radius:99px;border:1.5px solid #1A1100;overflow:hidden;">
                <div :style="`width:${currentWeek?.progress ?? 0}%;height:100%;background:#1A1100;border-radius:99px;`"></div>
              </div>
              <span style="font-size:11px;font-weight:700;font-family:'Space Grotesk','Noto Sans KR',sans-serif;">{{ currentWeek?.dayOfWeek }}/5일</span>
            </div>
          </div>
        </div>
      </aside>

      <!-- ── 메인 콘텐츠 ── -->
      <main class="app-main" style="flex:1;overflow-y:auto;background:#FFF8EE;display:flex;flex-direction:column;">

        <!-- 플래시 메시지 -->
        <div v-if="flash.success && showFlash" class="flash-wrap" style="margin:16px 32px 0;">
          <div style="background:#DCFCE7;border:2px solid #16A34A;border-radius:12px;padding:12px 16px;font-size:13px;color:#15803D;display:flex;justify-content:space-between;align-items:center;">
            {{ flash.success }}
            <button @click="showFlash=false" style="background:none;border:none;cursor:pointer;color:#16A34A;font-weight:700;font-size:16px;line-height:1;">✕</button>
          </div>
        </div>
        <div v-if="flash.error && showFlash" class="flash-wrap" style="margin:16px 32px 0;">
          <div style="background:#FEE2E2;border:2px solid #DC2626;border-radius:12px;padding:12px 16px;font-size:13px;color:#DC2626;display:flex;justify-content:space-between;align-items:center;">
            {{ flash.error }}
            <button @click="showFlash=false" style="background:none;border:none;cursor:pointer;color:#DC2626;font-weight:700;font-size:16px;line-height:1;">✕</button>
          </div>
        </div>

        <div class="app-main-padding" style="padding:28px 32px;flex:1;">
          <slot />
        </div>
      </main>
    </div>

    <!-- ── 모바일 하단 내비게이션 ── -->
    <nav class="mobile-bottom-nav">
      <Link href="/schedules" :class="['mob-nav-btn', { 'mob-active': isActive('/schedules') }]">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" :stroke="isActive('/schedules') ? '#1A1100' : '#9A8F7A'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M8 2v3M16 2v3M3.5 9.5h17M3 6.5h18a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V7.5a1 1 0 0 1 1-1z"/>
        </svg>
        <span>일정</span>
      </Link>

      <Link href="/reports" :class="['mob-nav-btn', { 'mob-active': isActive('/reports') && !isActive('/reports/create') }]">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" :stroke="isActive('/reports') && !isActive('/reports/create') ? '#1A1100' : '#9A8F7A'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M16 13H8M16 17H8M10 9H8"/>
        </svg>
        <span>보고서</span>
      </Link>

      <!-- 작성 버튼 (강조) -->
      <Link href="/reports/create" class="mob-nav-write" :class="{ 'mob-active': isActive('/reports/create') }">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
        </svg>
      </Link>

      <Link href="/profile" :class="['mob-nav-btn', { 'mob-active': isActive('/profile') }]">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" :stroke="isActive('/profile') ? '#1A1100' : '#9A8F7A'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/>
        </svg>
        <span>내정보</span>
      </Link>

      <Link v-if="auth?.user?.role === 'admin'" href="/admin/users" :class="['mob-nav-btn', { 'mob-active': isActive('/admin/users') }]" style="position:relative;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" :stroke="isActive('/admin/users') ? '#1A1100' : '#9A8F7A'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
        <span>관리</span>
        <span v-if="pendingCount > 0" class="mob-nav-badge">{{ pendingCount > 9 ? '9+' : pendingCount }}</span>
      </Link>
    </nav>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'

// 알림 설정 서브메뉴 — /admin/settings 경로이면 자동 열림
const settingsOpen = ref(window.location.pathname.startsWith('/admin/settings'))

defineProps({ pageTitle: { type: String, default: '' } })

const page         = usePage()
const auth         = computed(() => page.props.auth)
const flash        = computed(() => page.props.flash ?? {})
const currentWeek  = computed(() => page.props.currentWeek)
const pendingCount = computed(() => page.props.pendingCount ?? 0)
const unreadCount  = computed(() => page.props.unreadCount  ?? 0)

const showFlash = ref(true)
watch(() => page.props.flash, () => { showFlash.value = true })

// ── 알림 드롭다운 ──
const showNotifications = ref(false)
const notifLoading      = ref(false)
const notifications     = ref([])

const toggleNotifications = async () => {
  showNotifications.value = !showNotifications.value
  if (showNotifications.value && notifications.value.length === 0) {
    await loadNotifications()
  }
}

const closeNotifications = () => { showNotifications.value = false }

const loadNotifications = async () => {
  notifLoading.value = true
  try {
    const res = await window.axios.get('/notifications')
    notifications.value = res.data
  } catch (e) { console.error(e) }
  finally { notifLoading.value = false }
}

const openNotification = async (n) => {
  if (!n.is_read) {
    await window.axios.post(`/notifications/${n.id}/read`)
    n.is_read = true
    // Inertia 공유 데이터 갱신 없이 로컬 카운트 반영
    router.reload({ only: ['unreadCount'] })
  }
  if (n.link) {
    showNotifications.value = false
    router.get(n.link)
  }
}

const markAllRead = async () => {
  await window.axios.post('/notifications/read-all')
  notifications.value.forEach(n => { n.is_read = true })
  router.reload({ only: ['unreadCount'] })
}

// v-click-outside 디렉티브 (전역 등록 없이 인라인 처리)
const vClickOutside = {
  mounted(el, binding) {
    el._clickOutside = (e) => {
      if (!el.contains(e.target)) binding.value()
    }
    document.addEventListener('mousedown', el._clickOutside)
  },
  unmounted(el) {
    document.removeEventListener('mousedown', el._clickOutside)
  },
}

const isActive = (path) => {
  const loc = window.location.pathname
  if (path === '/reports') return loc === '/reports' || (loc.startsWith('/reports') && !loc.includes('/create') && /\/reports\/\d+/.test(loc))
  return loc === path || loc.startsWith(path + '/')
}

const navIconStyle = (active) => ({
  width: '26px',
  height: '26px',
  borderRadius: '7px',
  background: active ? 'rgba(26,17,0,0.1)' : 'rgba(26,17,0,0.06)',
  display: 'flex',
  alignItems: 'center',
  justifyContent: 'center',
  flexShrink: '0',
})

const logout = () => router.post('/logout')
</script>

<style scoped>
.notif-drop-enter-active, .notif-drop-leave-active { transition: all 0.18s ease; }
.notif-drop-enter-from, .notif-drop-leave-to { opacity: 0; transform: translateY(-6px); }
.submenu-enter-active, .submenu-leave-active { transition: all 0.18s ease; overflow: hidden; }
.submenu-enter-from, .submenu-leave-to { opacity: 0; transform: translateY(-4px); }

/* ===========================
   모바일 하단 내비게이션
   =========================== */
.mobile-bottom-nav {
  display: none;
  position: fixed;
  bottom: 0; left: 0; right: 0;
  height: 62px;
  background: #FFF8EE;
  border-top: 2px solid #1A1100;
  z-index: 100;
  justify-content: space-around;
  align-items: center;
  padding: 0 4px;
  flex-shrink: 0;
}

.mob-nav-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 3px;
  padding: 6px 10px;
  border-radius: 10px;
  text-decoration: none;
  font-size: 10px;
  color: #9A8F7A;
  font-weight: 600;
  font-family: 'Space Grotesk', 'Noto Sans KR', sans-serif;
  transition: all 0.12s;
  border: 1.5px solid transparent;
  min-width: 50px;
  position: relative;
}
.mob-nav-btn.mob-active {
  background: #FDCB40;
  border-color: #1A1100;
  color: #1A1100;
  font-weight: 800;
  box-shadow: 2px 2px 0 #1A1100;
}

/* 작성 버튼 - 원형 강조 */
.mob-nav-write {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px; height: 48px;
  background: #FD4401;
  border: 2px solid #1A1100;
  border-radius: 14px;
  box-shadow: 3px 3px 0 #1A1100;
  text-decoration: none;
  flex-shrink: 0;
  transition: all 0.12s;
}
.mob-nav-write:active { transform: translate(1px, 1px); box-shadow: 2px 2px 0 #1A1100; }

.mob-nav-badge {
  position: absolute;
  top: 2px; right: 4px;
  background: #DC2626;
  color: white;
  font-size: 8px;
  font-weight: 800;
  font-family: 'Space Grotesk', sans-serif;
  border-radius: 99px;
  min-width: 14px; height: 14px;
  display: flex; align-items: center; justify-content: center;
  padding: 0 3px;
  border: 1px solid #fff;
}

/* ===========================
   반응형 미디어 쿼리
   =========================== */
@media (max-width: 768px) {
  /* 뷰포트 높이 고정 해제 */
  .app-root {
    height: auto !important;
    min-height: 100dvh;
    overflow: visible !important;
  }
  .app-body {
    overflow: visible !important;
    flex: 1;
  }

  /* 사이드바 숨김 */
  .app-sidebar { display: none !important; }

  /* 하단 네비 표시 */
  .mobile-bottom-nav { display: flex !important; }

  /* 메인 콘텐츠 패딩 조정 (하단 네비 공간 확보) */
  .app-main-padding { padding: 16px 16px 80px 16px !important; }
  .flash-wrap { margin: 12px 16px 0 !important; }

  /* 헤더 페이지 칩 숨김 */
  .header-page-chip { display: none !important; }

  /* 유저 정보 버튼 숨김 (내정보는 하단 네비에서) */
  .header-user-info { display: none !important; }

  /* 알림 드롭다운: 화면 너비에 맞춤 */
  .notif-panel {
    width: min(340px, calc(100vw - 20px)) !important;
    right: 0 !important;
    left: auto !important;
  }
}

/* 매우 좁은 화면 (320px) */
@media (max-width: 380px) {
  .mob-nav-btn { padding: 5px 7px; min-width: 42px; font-size: 9px; }
  .mob-nav-write { width: 44px; height: 44px; }
}
</style>
