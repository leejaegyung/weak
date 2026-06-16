<template>
  <AppLayout page-title="API 키 관리">

    <!-- 헤더 -->
    <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:12px;margin-bottom:24px;max-width:760px;margin-left:auto;margin-right:auto;">
      <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
          <div style="background:#7C3AED;border:2px solid #1A1100;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>
            </svg>
          </div>
          <h1 style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:26px;font-weight:700;letter-spacing:-0.03em;">API 키 관리</h1>
        </div>
        <p style="color:#9A8F7A;font-size:13px;margin-left:42px;">외부 서비스 연동에 사용되는 API 키를 관리합니다</p>
      </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:20px;max-width:760px;margin:0 auto;width:100%;">

      <!-- ── AI 분석 서비스 ── -->
      <div class="card" style="padding:0;overflow:hidden;">
        <!-- 섹션 헤더 -->
        <div style="padding:16px 22px;border-bottom:2px solid #1A1100;background:#F5EDDB;display:flex;align-items:center;gap:10px;">
          <div style="width:30px;height:30px;background:#EDE9FE;border:2px solid #1A1100;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.5 2A2.5 2.5 0 0 1 12 4.5v15a2.5 2.5 0 0 1-4.96-.46 2.5 2.5 0 0 1 .37-4.51A2.5 2.5 0 0 1 9.5 9.5"/><path d="M14.5 2A2.5 2.5 0 0 0 12 4.5v15a2.5 2.5 0 0 0 4.96-.46 2.5 2.5 0 0 0-.37-4.51A2.5 2.5 0 0 0 14.5 9.5"/></svg>
          </div>
          <div style="flex:1;">
            <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:14px;font-weight:800;">AI 분석 서비스</div>
            <div style="font-size:11px;color:#9A8F7A;">요구/이슈 등록 시 자동 검토에 사용할 AI 제공자를 선택합니다</div>
          </div>
          <!-- AI 전체 활성/비활성 토글 -->
          <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
            <span style="font-size:11px;font-weight:700;color:#4A3F2A;white-space:nowrap;">
              {{ aiEnabled ? activeProviderName : 'AI 꺼짐' }}
            </span>
            <button @click="toggleAi"
              :style="{
                width:'46px', height:'26px',
                borderRadius:'99px',
                border:'2px solid #1A1100',
                background: aiEnabled ? '#7C3AED' : '#D1D5DB',
                cursor:'pointer',
                position:'relative',
                transition:'background 0.2s',
                flexShrink:0,
                boxShadow: aiEnabled ? '2px 2px 0 #1A1100' : '2px 2px 0 #1A1100',
              }"
              v-tooltip="aiEnabled ? 'AI 분석 비활성화' : 'AI 분석 활성화'">
              <div :style="{
                position:'absolute',
                top:'3px',
                left: aiEnabled ? '22px' : '3px',
                width:'16px', height:'16px',
                borderRadius:'50%',
                background:'#fff',
                border:'1.5px solid rgba(0,0,0,0.15)',
                transition:'left 0.2s',
                boxShadow:'0 1px 3px rgba(0,0,0,0.2)',
              }"></div>
            </button>
          </div>
        </div>

        <!-- 비활성 안내 배너 -->
        <Transition name="fade">
          <div v-if="!aiEnabled" style="margin:0 22px 0;padding:10px 14px;background:#F3F4F6;border:2px solid #D1D5DB;border-radius:10px;font-size:12px;color:#6B7280;display:flex;align-items:center;gap:8px;margin-top:14px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            AI 분석이 비활성화되어 있습니다. 이슈 등록 시 AI 검토가 수행되지 않습니다. 토글을 켜면 즉시 활성화됩니다.
          </div>
        </Transition>

        <!-- 제공자 카드 목록 -->
        <div style="padding:16px 22px;display:flex;flex-direction:column;gap:12px;" :style="{ opacity: aiEnabled ? 1 : 0.45, pointerEvents: aiEnabled ? 'auto' : 'none', transition:'opacity 0.2s' }">
          <div v-for="svc in localAiServices" :key="svc.id"
            :style="{
              border: '2px solid',
              borderColor: activeProvider === svc.id ? '#7C3AED' : '#E8E0D0',
              borderRadius: '12px',
              overflow: 'hidden',
              transition: 'border-color 0.15s',
            }">

            <!-- 제공자 행 -->
            <div style="display:flex;align-items:center;gap:14px;padding:14px 16px;background:#fff;">
              <!-- 활성 라디오 -->
              <button @click="setProvider(svc.id)"
                :style="{
                  width:'20px', height:'20px', borderRadius:'50%',
                  border: '2px solid ' + (activeProvider === svc.id ? '#7C3AED' : '#D1D5DB'),
                  background: activeProvider === svc.id ? '#7C3AED' : '#fff',
                  flexShrink:0, cursor:'pointer',
                  display:'flex', alignItems:'center', justifyContent:'center',
                  transition:'all 0.15s',
                }">
                <div v-if="activeProvider === svc.id" style="width:8px;height:8px;border-radius:50%;background:#fff;"></div>
              </button>

              <!-- 아이콘 -->
              <div :style="{
                width:'36px', height:'36px', borderRadius:'9px', border:'2px solid #1A1100', flexShrink:0,
                background: svc.id === 'anthropic' ? '#EDE9FE' : '#EFF6FF',
                display:'flex', alignItems:'center', justifyContent:'center',
              }">
                <!-- Claude 아이콘 -->
                <svg v-if="svc.id === 'anthropic'" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M9.5 2A2.5 2.5 0 0 1 12 4.5v15a2.5 2.5 0 0 1-4.96-.46 2.5 2.5 0 0 1 .37-4.51A2.5 2.5 0 0 1 9.5 9.5"/>
                  <path d="M14.5 2A2.5 2.5 0 0 0 12 4.5v15a2.5 2.5 0 0 0 4.96-.46 2.5 2.5 0 0 0-.37-4.51A2.5 2.5 0 0 0 14.5 9.5"/>
                </svg>
                <!-- OpenAI 아이콘 -->
                <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 2a5 5 0 0 1 5 5v1h1a3 3 0 0 1 0 6h-1v1a5 5 0 0 1-10 0v-1H6a3 3 0 0 1 0-6h1V7a5 5 0 0 1 5-5z"/>
                </svg>
              </div>

              <!-- 이름/설명 -->
              <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:2px;">
                  <span style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:14px;font-weight:800;">{{ svc.name }}</span>
                  <span v-if="activeProvider === svc.id"
                    style="font-size:10px;font-weight:700;background:#EDE9FE;color:#7C3AED;border:1.5px solid #C4B5FD;border-radius:99px;padding:2px 8px;flex-shrink:0;">사용 중</span>
                  <span v-if="svc.is_set"
                    style="font-size:10px;font-weight:700;background:#DCFCE7;color:#15803D;border:1.5px solid #86EFAC;border-radius:99px;padding:2px 8px;flex-shrink:0;">✓ 설정됨</span>
                  <span v-else
                    style="font-size:10px;font-weight:700;background:#FEF3C7;color:#B45309;border:1.5px solid #FCD34D;border-radius:99px;padding:2px 8px;flex-shrink:0;">미설정</span>
                </div>
                <div style="font-size:12px;color:#9A8F7A;">{{ svc.description }}</div>
              </div>

              <!-- 키 발급 링크 -->
              <a :href="svc.docs_url" target="_blank" rel="noopener noreferrer"
                style="display:inline-flex;align-items:center;gap:4px;font-size:11px;color:#9A8F7A;font-weight:600;text-decoration:none;background:#F5EDDB;border:1.5px solid #D1C9B6;border-radius:7px;padding:4px 9px;white-space:nowrap;transition:all 0.1s;flex-shrink:0;"
                @mouseenter="e=>{e.currentTarget.style.color='#1A1100';e.currentTarget.style.background='#FDCB40';}"
                @mouseleave="e=>{e.currentTarget.style.color='#9A8F7A';e.currentTarget.style.background='#F5EDDB';}">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14L21 3"/></svg>
                키 발급
              </a>
            </div>

            <!-- 키 영역 (항상 표시) -->
            <div style="padding:0 16px 14px;background:#fff;border-top:1.5px solid #F5EDDB;">
              <div style="display:flex;align-items:center;gap:8px;margin-top:10px;">
                <div style="flex:1;background:#F5EDDB;border:2px solid #E8E0D0;border-radius:8px;padding:8px 12px;font-size:12px;font-family:'Space Grotesk',monospace;color:#4A3F2A;letter-spacing:0.05em;min-height:36px;display:flex;align-items:center;">
                  <span v-if="svc.is_set">{{ svc.key_preview }}</span>
                  <span v-else style="color:#C8BFA8;font-style:italic;font-family:'Noto Sans KR',sans-serif;font-size:11px;">API 키 미설정</span>
                </div>
                <!-- 테스트 버튼 -->
                <button v-if="svc.is_set && activeProvider === svc.id" @click="testAiKey(svc)"
                  :disabled="svc._testing"
                  style="background:#fff;border:2px solid #1A1100;border-radius:8px;padding:7px 12px;font-size:11px;font-weight:700;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:5px;transition:all 0.1s;white-space:nowrap;box-shadow:2px 2px 0 #1A1100;flex-shrink:0;"
                  :style="{ opacity: svc._testing ? 0.6 : 1 }"
                  @mouseenter="e=>{if(!svc._testing){e.currentTarget.style.transform='translate(-1px,-1px)';e.currentTarget.style.boxShadow='3px 3px 0 #1A1100';}}"
                  @mouseleave="e=>{e.currentTarget.style.transform='none';e.currentTarget.style.boxShadow='2px 2px 0 #1A1100';}">
                  <svg v-if="svc._testing" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation:spin 1s linear infinite;"><path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" opacity=".25"/><path d="M12 3a9 9 0 0 1 9 9"/></svg>
                  <svg v-else width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                  {{ svc._testing ? '테스트...' : '연결 테스트' }}
                </button>
                <!-- 변경 버튼 -->
                <button v-if="!svc._editing" @click="startEdit(svc)"
                  :style="{
                    background: svc.id === 'anthropic' ? '#EDE9FE' : '#EFF6FF',
                    color: svc.id === 'anthropic' ? '#7C3AED' : '#2563EB',
                    border: '2px solid ' + (svc.id === 'anthropic' ? '#C4B5FD' : '#93C5FD'),
                  }"
                  style="border-radius:8px;padding:7px 12px;font-size:11px;font-weight:700;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:5px;transition:all 0.1s;white-space:nowrap;flex-shrink:0;">
                  <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  {{ svc.is_set ? '변경' : '키 설정' }}
                </button>
              </div>

              <!-- 테스트 결과 -->
              <Transition name="fade">
                <div v-if="svc._testResult" :style="{
                  marginTop:'8px',
                  padding:'7px 12px',
                  borderRadius:'7px',
                  fontSize:'12px',
                  fontWeight:'700',
                  border:'1.5px solid',
                  background: svc._testResult.ok ? '#DCFCE7' : '#FEE2E2',
                  color:       svc._testResult.ok ? '#15803D'  : '#DC2626',
                  borderColor: svc._testResult.ok ? '#86EFAC'  : '#FCA5A5',
                }">{{ svc._testResult.ok ? '✓' : '✕' }} {{ svc._testResult.message }}</div>
              </Transition>

              <!-- 키 입력 폼 -->
              <Transition name="expand">
                <div v-if="svc._editing" style="margin-top:10px;background:#F5EDDB;border:2px solid #1A1100;border-radius:10px;padding:14px;">
                  <label style="display:block;font-size:11px;font-weight:700;color:#4A3F2A;margin-bottom:7px;">새 API 키 입력</label>
                  <div style="display:flex;gap:8px;">
                    <div style="flex:1;position:relative;">
                      <input v-model="svc._newKey" :type="svc._showKey ? 'text' : 'password'"
                        :placeholder="`${svc.name} API 키`" autocomplete="off"
                        style="width:100%;background:#fff;border:2px solid #1A1100;border-radius:8px;padding:9px 36px 9px 12px;font-size:12px;font-family:'Space Grotesk',monospace;outline:none;box-sizing:border-box;"
                        @focus="e=>e.target.style.borderColor='#7C3AED'"
                        @blur="e=>e.target.style.borderColor='#1A1100'" />
                      <button type="button" @click="svc._showKey = !svc._showKey"
                        style="position:absolute;right:9px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9A8F7A;display:flex;align-items:center;">
                        <svg v-if="!svc._showKey" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg v-else width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24M1 1l22 22"/></svg>
                      </button>
                    </div>
                    <button @click="saveAiKey(svc)" :disabled="!svc._newKey.trim() || svc._saving"
                      style="background:#1A1100;color:#fff;border:2px solid #1A1100;border-radius:8px;padding:9px 16px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:5px;white-space:nowrap;flex-shrink:0;"
                      :style="{ opacity: !svc._newKey.trim() || svc._saving ? 0.5 : 1 }">
                      <svg v-if="svc._saving" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation:spin 1s linear infinite;"><path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" opacity=".25"/><path d="M12 3a9 9 0 0 1 9 9"/></svg>
                      저장
                    </button>
                    <button @click="cancelEdit(svc)" class="btn-secondary" style="flex-shrink:0;font-size:12px;padding:8px 12px;">취소</button>
                  </div>
                  <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px;">
                    <p style="font-size:10px;color:#9A8F7A;">키는 DB에 저장됩니다. 서버 접근이 제한된 환경에서 사용하세요.</p>
                    <button v-if="svc.is_set" @click="clearAiKey(svc)"
                      style="background:none;border:none;font-size:11px;color:#DC2626;font-weight:700;cursor:pointer;font-family:inherit;">키 삭제</button>
                  </div>
                </div>
              </Transition>
            </div>
          </div>
        </div>
      </div>

      <!-- ── 추가 API 서비스 ── -->
      <div class="card" style="padding:0;overflow:hidden;">
        <!-- 섹션 헤더 -->
        <div style="padding:16px 22px;border-bottom:2px solid #1A1100;background:#F5EDDB;display:flex;align-items:center;gap:10px;">
          <div style="width:30px;height:30px;background:#FEF9C3;border:2px solid #1A1100;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#B45309" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 7h3a5 5 0 0 1 5 5 5 5 0 0 1-5 5h-3m-6 0H6a5 5 0 0 1-5-5 5 5 0 0 1 5-5h3M8 12h8"/></svg>
          </div>
          <div style="flex:1;">
            <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:14px;font-weight:800;">추가 API 서비스</div>
            <div style="font-size:11px;color:#9A8F7A;">외부 서비스 API 키를 등록하여 시스템에서 활용합니다</div>
          </div>
          <button @click="openAddCustom"
            style="background:#FDCB40;color:#1A1100;border:2px solid #1A1100;border-radius:9px;padding:7px 14px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:2px 2px 0 #1A1100;display:inline-flex;align-items:center;gap:5px;transition:all 0.1s;white-space:nowrap;"
            @mouseenter="e=>{e.currentTarget.style.transform='translate(-1px,-1px)';e.currentTarget.style.boxShadow='3px 3px 0 #1A1100';}"
            @mouseleave="e=>{e.currentTarget.style.transform='none';e.currentTarget.style.boxShadow='2px 2px 0 #1A1100';}">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
            추가
          </button>
        </div>

        <div style="padding:16px 22px;">

          <!-- 빈 목록 -->
          <div v-if="localCustomApis.length === 0 && !showAddForm"
            style="padding:32px 0;text-align:center;color:#9A8F7A;">
            <div style="font-size:28px;margin-bottom:10px;">🔌</div>
            <div style="font-size:13px;font-weight:600;margin-bottom:4px;">등록된 API 서비스가 없습니다</div>
            <div style="font-size:12px;">위의 추가 버튼을 눌러 새 API를 등록해 보세요</div>
          </div>

          <!-- 추가 폼 -->
          <Transition name="expand">
            <div v-if="showAddForm" style="background:#F5EDDB;border:2px solid #1A1100;border-radius:12px;padding:16px;margin-bottom:14px;">
              <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:13px;font-weight:800;margin-bottom:14px;">
                {{ editingCustomId ? 'API 서비스 수정' : '새 API 서비스 추가' }}
              </div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                <div>
                  <label style="display:block;font-size:11px;font-weight:700;color:#4A3F2A;margin-bottom:5px;">서비스 이름 <span style="color:#DC2626;">*</span></label>
                  <input v-model="customForm.name" type="text" placeholder="예: 슬랙 Webhook, GitHub Actions" maxlength="100"
                    style="width:100%;background:#fff;border:2px solid #1A1100;border-radius:8px;padding:8px 12px;font-size:12px;font-family:inherit;outline:none;box-sizing:border-box;"
                    @focus="e=>e.target.style.borderColor='#7C3AED'"
                    @blur="e=>e.target.style.borderColor='#1A1100'" />
                </div>
                <div>
                  <label style="display:block;font-size:11px;font-weight:700;color:#4A3F2A;margin-bottom:5px;">엔드포인트 URL <span style="color:#9A8F7A;">(선택)</span></label>
                  <input v-model="customForm.endpoint" type="url" placeholder="https://api.example.com/v1"
                    style="width:100%;background:#fff;border:2px solid #1A1100;border-radius:8px;padding:8px 12px;font-size:12px;font-family:inherit;outline:none;box-sizing:border-box;"
                    @focus="e=>e.target.style.borderColor='#7C3AED'"
                    @blur="e=>e.target.style.borderColor='#1A1100'" />
                </div>
              </div>
              <div style="margin-bottom:10px;">
                <label style="display:block;font-size:11px;font-weight:700;color:#4A3F2A;margin-bottom:5px;">설명 <span style="color:#9A8F7A;">(선택)</span></label>
                <input v-model="customForm.description" type="text" placeholder="이 API의 용도를 간단히 설명해 주세요" maxlength="300"
                  style="width:100%;background:#fff;border:2px solid #1A1100;border-radius:8px;padding:8px 12px;font-size:12px;font-family:inherit;outline:none;box-sizing:border-box;"
                  @focus="e=>e.target.style.borderColor='#7C3AED'"
                  @blur="e=>e.target.style.borderColor='#1A1100'" />
              </div>
              <div style="margin-bottom:14px;">
                <label style="display:block;font-size:11px;font-weight:700;color:#4A3F2A;margin-bottom:5px;">
                  API 키 <span v-if="!editingCustomId" style="color:#DC2626;">*</span>
                  <span v-else style="color:#9A8F7A;">(변경 시에만 입력)</span>
                </label>
                <div style="position:relative;">
                  <input v-model="customForm.api_key" :type="customForm.showKey ? 'text' : 'password'"
                    placeholder="API 키를 입력하세요" autocomplete="off"
                    style="width:100%;background:#fff;border:2px solid #1A1100;border-radius:8px;padding:8px 36px 8px 12px;font-size:12px;font-family:'Space Grotesk',monospace;outline:none;box-sizing:border-box;"
                    @focus="e=>e.target.style.borderColor='#7C3AED'"
                    @blur="e=>e.target.style.borderColor='#1A1100'" />
                  <button type="button" @click="customForm.showKey = !customForm.showKey"
                    style="position:absolute;right:9px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9A8F7A;display:flex;align-items:center;">
                    <svg v-if="!customForm.showKey" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg v-else width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24M1 1l22 22"/></svg>
                  </button>
                </div>
              </div>
              <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button @click="cancelCustom" class="btn-secondary" style="font-size:12px;padding:8px 14px;">취소</button>
                <button @click="saveCustom"
                  :disabled="!customForm.name.trim() || (!editingCustomId && !customForm.api_key.trim()) || customSaving"
                  style="background:#7C3AED;color:#fff;border:2px solid #1A1100;border-radius:9px;padding:8px 18px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:2px 2px 0 #1A1100;display:inline-flex;align-items:center;gap:6px;transition:all 0.1s;"
                  :style="{ opacity: (!customForm.name.trim() || (!editingCustomId && !customForm.api_key.trim()) || customSaving) ? 0.5 : 1 }">
                  <svg v-if="customSaving" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation:spin 1s linear infinite;"><path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" opacity=".25"/><path d="M12 3a9 9 0 0 1 9 9"/></svg>
                  {{ customSaving ? '저장 중...' : (editingCustomId ? '수정 저장' : '추가하기') }}
                </button>
              </div>
            </div>
          </Transition>

          <!-- 추가 API 목록 -->
          <div v-for="api in localCustomApis" :key="api.id"
            style="border:2px solid #E8E0D0;border-radius:12px;overflow:hidden;margin-bottom:10px;">
            <div style="display:flex;align-items:center;gap:12px;padding:12px 16px;background:#fff;">
              <!-- 아이콘 -->
              <div style="width:36px;height:36px;background:#FEF9C3;border:2px solid #1A1100;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#B45309" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 7h3a5 5 0 0 1 5 5 5 5 0 0 1-5 5h-3m-6 0H6a5 5 0 0 1-5-5 5 5 0 0 1 5-5h3M8 12h8"/></svg>
              </div>
              <!-- 정보 -->
              <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:7px;margin-bottom:2px;">
                  <span style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:13px;font-weight:800;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ api.name }}</span>
                  <span v-if="api.is_set"
                    style="font-size:10px;font-weight:700;background:#DCFCE7;color:#15803D;border:1.5px solid #86EFAC;border-radius:99px;padding:2px 7px;flex-shrink:0;">✓ 설정됨</span>
                </div>
                <div style="font-size:11px;color:#9A8F7A;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                  {{ api.description || api.endpoint || '설명 없음' }}
                </div>
              </div>
              <!-- 키 미리보기 -->
              <div style="background:#F5EDDB;border:1.5px solid #E8E0D0;border-radius:7px;padding:5px 10px;font-size:11px;font-family:'Space Grotesk',monospace;color:#4A3F2A;letter-spacing:0.04em;white-space:nowrap;flex-shrink:0;">
                {{ api.is_set ? api.key_preview : '미설정' }}
              </div>
              <!-- 액션 버튼 -->
              <div style="display:flex;gap:6px;flex-shrink:0;">
                <button @click="testCustomApi(api)"
                  :disabled="api._testing"
                  style="background:#fff;border:2px solid #1A1100;border-radius:7px;padding:5px 10px;font-size:11px;font-weight:700;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:4px;transition:all 0.1s;box-shadow:1px 1px 0 #1A1100;"
                  :style="{ opacity: api._testing ? 0.6 : 1 }"
                  @mouseenter="e=>{if(!api._testing){e.currentTarget.style.transform='translate(-1px,-1px)';e.currentTarget.style.boxShadow='2px 2px 0 #1A1100';}}"
                  @mouseleave="e=>{e.currentTarget.style.transform='none';e.currentTarget.style.boxShadow='1px 1px 0 #1A1100';}">
                  <svg v-if="api._testing" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation:spin 1s linear infinite;"><path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" opacity=".25"/><path d="M12 3a9 9 0 0 1 9 9"/></svg>
                  <svg v-else width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                  테스트
                </button>
                <button @click="openEditCustom(api)"
                  style="background:#EFF6FF;color:#2563EB;border:2px solid #93C5FD;border-radius:7px;padding:5px 10px;font-size:11px;font-weight:700;cursor:pointer;font-family:inherit;transition:all 0.1s;"
                  @mouseenter="e=>{e.currentTarget.style.background='#DBEAFE';}"
                  @mouseleave="e=>{e.currentTarget.style.background='#EFF6FF';}">수정</button>
                <button @click="deleteCustom(api)"
                  style="background:#FEE2E2;color:#DC2626;border:2px solid #FCA5A5;border-radius:7px;padding:5px 10px;font-size:11px;font-weight:700;cursor:pointer;font-family:inherit;transition:all 0.1s;"
                  @mouseenter="e=>{e.currentTarget.style.background='#DC2626';e.currentTarget.style.color='#fff';e.currentTarget.style.borderColor='#DC2626';}"
                  @mouseleave="e=>{e.currentTarget.style.background='#FEE2E2';e.currentTarget.style.color='#DC2626';e.currentTarget.style.borderColor='#FCA5A5';}">삭제</button>
              </div>
            </div>
            <!-- 테스트 결과 -->
            <Transition name="fade">
              <div v-if="api._testResult" :style="{
                padding:'8px 16px',
                fontSize:'12px',
                fontWeight:'700',
                background: api._testResult.ok ? '#F0FDF4' : '#FEF2F2',
                color:       api._testResult.ok ? '#15803D'  : '#DC2626',
                borderTop:  '1.5px solid ' + (api._testResult.ok ? '#86EFAC' : '#FCA5A5'),
              }">{{ api._testResult.ok ? '✓' : '✕' }} {{ api._testResult.message }}</div>
            </Transition>
          </div>

        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive, ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  aiProvider:  { type: String,  default: 'anthropic' },
  aiEnabled:   { type: Boolean, default: true },
  aiServices:  { type: Array,   default: () => [] },
  customApis:  { type: Array,   default: () => [] },
})

// ── AI 서비스 ────────────────────────────────────
const activeProvider = ref(props.aiProvider)
const aiEnabled      = ref(props.aiEnabled)

const toggleAi = () => {
  aiEnabled.value = !aiEnabled.value
  router.post('/admin/settings/api', { service: 'ai_enabled', api_key: aiEnabled.value ? '1' : '0' }, {
    preserveState: true,
    preserveScroll: true,
  })
}

const localAiServices = reactive(
  props.aiServices.map(svc => ({
    ...svc,
    _editing:    false,
    _newKey:     '',
    _showKey:    false,
    _saving:     false,
    _testing:    false,
    _testResult: null,
  }))
)

const activeProviderName = computed(() => {
  const found = localAiServices.find(s => s.id === activeProvider.value)
  return found?.name ?? activeProvider.value
})

const setProvider = (id) => {
  if (activeProvider.value === id) return
  activeProvider.value = id
  router.post('/admin/settings/api', { service: 'ai_provider', api_key: id }, {
    preserveState: true,
    preserveScroll: true,
  })
}

const startEdit  = (svc) => { svc._editing = true; svc._newKey = ''; svc._showKey = false; svc._testResult = null }
const cancelEdit = (svc) => { svc._editing = false; svc._newKey = '' }

const saveAiKey = (svc) => {
  if (!svc._newKey.trim() || svc._saving) return
  svc._saving = true
  router.post('/admin/settings/api', { service: svc.id, api_key: svc._newKey.trim() }, {
    onSuccess: () => {
      svc.is_set      = true
      svc.key_preview = svc._newKey.slice(0, 8) + '•••••' + svc._newKey.slice(-4)
      svc._editing    = false
      svc._newKey     = ''
      svc._testResult = null
    },
    onFinish:  () => { svc._saving = false },
  })
}

const clearAiKey = (svc) => {
  if (!confirm(`${svc.name} API 키를 삭제할까요?`)) return
  router.post('/admin/settings/api', { service: svc.id, api_key: '' }, {
    onSuccess: () => { svc.is_set = false; svc.key_preview = ''; svc._editing = false; svc._testResult = null },
  })
}

const testAiKey = async (svc) => {
  if (svc._testing) return
  svc._testing = true; svc._testResult = null
  try {
    const res = await window.axios.post('/admin/settings/api/test', { provider: svc.id })
    svc._testResult = res.data
  } catch (e) {
    svc._testResult = { ok: false, message: '요청 실패: ' + (e.response?.data?.message ?? e.message) }
  } finally {
    svc._testing = false
  }
}

// ── 추가 API ─────────────────────────────────────
const localCustomApis = reactive(
  props.customApis.map(api => ({ ...api, _testing: false, _testResult: null }))
)

const showAddForm     = ref(false)
const editingCustomId = ref(null)
const customSaving    = ref(false)
const customForm      = reactive({ name: '', description: '', endpoint: '', api_key: '', showKey: false })

const resetCustomForm = () => {
  customForm.name = ''; customForm.description = ''; customForm.endpoint = ''
  customForm.api_key = ''; customForm.showKey = false
  editingCustomId.value = null
}

const openAddCustom = () => { resetCustomForm(); showAddForm.value = true }

const openEditCustom = (api) => {
  resetCustomForm()
  customForm.name        = api.name
  customForm.description = api.description
  customForm.endpoint    = api.endpoint
  editingCustomId.value  = api.id
  showAddForm.value      = true
}

const cancelCustom = () => { showAddForm.value = false; resetCustomForm() }

const saveCustom = () => {
  if (!customForm.name.trim()) return
  if (!editingCustomId.value && !customForm.api_key.trim()) return
  customSaving.value = true

  const payload = {
    name:        customForm.name,
    description: customForm.description,
    endpoint:    customForm.endpoint,
    api_key:     customForm.api_key,
  }

  if (editingCustomId.value) {
    router.put(`/admin/settings/api/custom/${editingCustomId.value}`, payload, {
      onSuccess: () => { showAddForm.value = false; resetCustomForm() },
      onFinish:  () => { customSaving.value = false },
    })
  } else {
    router.post('/admin/settings/api/custom', payload, {
      onSuccess: () => { showAddForm.value = false; resetCustomForm() },
      onFinish:  () => { customSaving.value = false },
    })
  }
}

const deleteCustom = (api) => {
  if (!confirm(`"${api.name}" API를 삭제할까요?`)) return
  router.delete(`/admin/settings/api/custom/${api.id}`, {
    onSuccess: () => {
      const idx = localCustomApis.findIndex(a => a.id === api.id)
      if (idx !== -1) localCustomApis.splice(idx, 1)
    },
  })
}

const testCustomApi = async (api) => {
  if (api._testing) return
  api._testing = true; api._testResult = null
  try {
    const res = await window.axios.post(`/admin/settings/api/custom/${api.id}/test`)
    api._testResult = res.data
  } catch (e) {
    api._testResult = { ok: false, message: '요청 실패: ' + (e.response?.data?.message ?? e.message) }
  } finally {
    api._testing = false
  }
}
</script>

<style scoped>
.expand-enter-active, .expand-leave-active { transition: all 0.2s ease; overflow: hidden; }
.expand-enter-from, .expand-leave-to { opacity: 0; max-height: 0; padding-top: 0; padding-bottom: 0; }
.expand-enter-to, .expand-leave-from { max-height: 500px; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
