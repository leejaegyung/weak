<template>
  <AppLayout page-title="보고서 작성">

    <!-- 이번 주 보고서 중복 경고 팝업 -->
    <div v-if="showDuplicateAlert"
      style="position:fixed;inset:0;background:rgba(26,17,0,0.5);display:flex;align-items:center;justify-content:center;z-index:300;backdrop-filter:blur(4px);">
      <div class="card" style="width:400px;padding:28px;text-align:center;">
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
          title="이전 보고서 불러오기"
          style="background:#FFF0A0;border:2px solid #1A1100;border-radius:10px;padding:6px 12px;font-size:12px;font-family:inherit;cursor:pointer;color:#1A1100;font-weight:700;display:inline-flex;align-items:center;gap:6px;box-shadow:2px 2px 0 #1A1100;transition:all 0.1s;"
          @mouseenter="e=>{e.currentTarget.style.transform='translate(-1px,-1px)';e.currentTarget.style.boxShadow='3px 3px 0 #1A1100';}"
          @mouseleave="e=>{e.currentTarget.style.transform='none';e.currentTarget.style.boxShadow='2px 2px 0 #1A1100';}">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 3M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
          이전 보고서
        </button>
        <Link href="/reports" class="btn-secondary">취소</Link>
        <button type="button" @click="submit" :disabled="form.processing" class="btn-primary">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
          {{ form.processing ? '제출 중...' : '제출하기' }}
        </button>
      </div>
    </div>

    <form @submit.prevent="submit" style="display:flex;flex-direction:column;gap:18px;">
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
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
          <div style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:14px;font-weight:700;">Todo 목록</div>
          <button type="button" @click="addTodo" class="btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:5px;">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>추가
          </button>
        </div>
        <div v-if="form.todo_items.length === 0" style="text-align:center;padding:16px 0;color:#9A8F7A;font-size:13px;">항목을 추가하세요</div>
        <div style="display:flex;flex-direction:column;gap:8px;">
          <div v-for="(t, idx) in form.todo_items" :key="idx" style="display:flex;gap:8px;align-items:center;">
            <input type="checkbox" v-model="t.done" style="accent-color:#FD4401;width:16px;height:16px;cursor:pointer;flex-shrink:0;" />
            <input v-model="t.content" type="text" class="input-field" :style="{ textDecoration: t.done ? 'line-through' : 'none', flex:1 }" placeholder="할 일을 입력하세요" />
            <button type="button" @click="removeTodo(idx)"
              style="background:none;border:none;cursor:pointer;color:#9A8F7A;padding:4px;border-radius:6px;flex-shrink:0;transition:color 0.1s;"
              @mouseenter="e=>e.currentTarget.style.color='#DC2626'"
              @mouseleave="e=>e.currentTarget.style.color='#9A8F7A'">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
          </div>
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
          <textarea v-model="form.notes" rows="4" class="input-field" style="resize:vertical;line-height:1.65;" placeholder="이번 주 특이사항을 입력해주세요"></textarea>
        </div>
        <div class="card">
          <label style="font-family:'Space Grotesk','Noto Sans KR',sans-serif;font-size:14px;font-weight:700;display:block;margin-bottom:12px;">요청사항</label>
          <textarea v-model="form.requests" rows="4" class="input-field" style="resize:vertical;line-height:1.65;" placeholder="관리자에게 전달할 요청사항"></textarea>
        </div>
      </div>
    </form>
    <!-- 이전 보고서 미리보기 모달 -->
    <div v-if="showPrevModal"
      style="position:fixed;inset:0;background:rgba(26,17,0,0.5);display:flex;align-items:center;justify-content:center;z-index:200;backdrop-filter:blur(4px);"
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
              :title="isFullscreen ? '작게 보기' : '전체 화면'"
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
                          <div v-for="(sub, si) in item.sub_items" :key="si" style="display:flex;gap:5px;"><span style="color:#9A8F7A;flex-shrink:0;font-size:11px;">-</span><span style="color:#4A3F2A;font-size:11px;line-height:1.5;">{{ sub }}</span></div>
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
                          <div v-for="(sub, si) in item.sub_items" :key="si" style="display:flex;gap:5px;"><span style="color:#9A8F7A;flex-shrink:0;font-size:11px;">-</span><span style="color:#4A3F2A;font-size:11px;line-height:1.5;">{{ sub }}</span></div>
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
                          <div v-for="(sub, si) in item.sub_items" :key="si" style="display:flex;gap:5px;"><span style="color:#9A8F7A;flex-shrink:0;font-size:11px;">-</span><span style="color:#4A3F2A;font-size:11px;line-height:1.5;">{{ sub }}</span></div>
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
                          <div v-for="(sub, si) in item.sub_items" :key="si" style="display:flex;gap:5px;"><span style="color:#9A8F7A;flex-shrink:0;font-size:11px;">-</span><span style="color:#4A3F2A;font-size:11px;line-height:1.5;">{{ sub }}</span></div>
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
                          <div v-for="(sub, si) in item.sub_items" :key="si" style="display:flex;gap:5px;"><span style="color:#9A8F7A;flex-shrink:0;font-size:11px;">-</span><span style="color:#4A3F2A;font-size:11px;line-height:1.5;">{{ sub }}</span></div>
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

<script setup>
import { ref, computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SupportSection from '@/Components/SupportSection.vue'

const props = defineProps({
  weekInfo:       { type: Object, default: () => ({}) },
  prevReports:    { type: Array,  default: () => [] },
  existingReport: { type: Object, default: null },
})

// 중복 보고서 팝업 (제출 시점에만 표시)
const showDuplicateAlert = ref(false)

// title 우선, 구버전 데이터는 content를 title로 마이그레이션
const splitByCat = (arr, cat) =>
  (arr || []).filter(i => i.category === cat)
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

const addTodo    = () => form.todo_items.push({ content: '', done: false })
const removeTodo = (idx) => form.todo_items.splice(idx, 1)

const submit = () => {
  if (props.existingReport) {
    showDuplicateAlert.value = true
    return
  }
  form.transform(data => ({
    week:       data.week,
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
    next_plan: data.jiWon_next.map(i => ({ title: i.title, content: i.title, category: '지원', sub_items: i.sub_items })),
    todo_items: data.todo_items,
    notes:      data.notes,
    requests:   data.requests,
  })).post('/reports')
}

// 이전 보고서 미리보기 모달
const showPrevModal  = ref(false)
const isFullscreen   = ref(false)
const previewReport  = ref(null)   // 선택된 보고서 메타
const previewData    = ref(null)   // 로드된 보고서 데이터
const previewLoading = ref(false)

const selectPrev = async (r) => {
  if (previewReport.value?.id === r.id) return
  previewReport.value = r
  previewData.value   = null
  previewLoading.value = true
  try {
    const res = await window.axios.get(`/reports/${r.id}/load`)
    previewData.value = res.data
  } catch (e) { console.error(e) }
  previewLoading.value = false
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

const applyPrev = () => {
  if (!previewData.value) return
  const r = previewData.value
  form.jiWon_curr = splitByCat(r.curr_work, '지원')
  form.jiWon_next = splitByCat(r.next_plan, '지원')
  form.naebu      = splitByCat(r.curr_work, '내부작업')
  form.gongyu     = splitByCat(r.curr_work, '공유')
  form.gita       = splitByCat(r.curr_work, '기타')
  if (r.todo_items) form.todo_items = r.todo_items
  if (r.notes)      form.notes      = r.notes
  if (r.requests)   form.requests   = r.requests
  showPrevModal.value = false
  previewReport.value = null
  previewData.value   = null
}
</script>
