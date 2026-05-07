<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $data['subject'] }}</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Apple SD Gothic Neo', 'Malgun Gothic', 'Noto Sans KR', Arial, sans-serif;
      background: #FFF8EE;
      color: #1A1100;
      -webkit-font-smoothing: antialiased;
    }
    .wrapper {
      max-width: 600px;
      margin: 32px auto;
      background: #ffffff;
      border: 2px solid #1A1100;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 4px 4px 0 #1A1100;
    }
    /* 헤더 */
    .header {
      background: #FD4401;
      padding: 24px 32px;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .header-icon {
      width: 40px;
      height: 40px;
      background: #FDCB40;
      border: 2px solid #1A1100;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .header-title {
      font-size: 18px;
      font-weight: 800;
      color: #ffffff;
      letter-spacing: -0.02em;
    }
    .header-sub {
      font-size: 12px;
      color: rgba(255,255,255,0.75);
      margin-top: 2px;
    }
    /* 바디 */
    .body {
      padding: 32px;
    }
    .greeting {
      font-size: 15px;
      color: #1A1100;
      line-height: 1.7;
      margin-bottom: 24px;
    }
    /* 기간 뱃지 */
    .week-badge {
      display: inline-block;
      background: #FFF0A0;
      border: 2px solid #1A1100;
      border-radius: 8px;
      padding: 6px 14px;
      font-size: 13px;
      font-weight: 800;
      color: #1A1100;
      margin-bottom: 24px;
    }
    /* 구분선 */
    .divider {
      border: none;
      border-top: 2px solid #1A1100;
      margin: 0 0 20px 0;
    }
    /* 보고서 링크 목록 */
    .report-list {
      display: flex;
      flex-direction: column;
      gap: 10px;
      margin-bottom: 28px;
    }
    .report-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: #FDFAF5;
      border: 1.5px solid #E8E0D0;
      border-radius: 10px;
      padding: 14px 18px;
    }
    .report-item-left {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .avatar {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      background: #FDCB40;
      border: 2px solid #1A1100;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 13px;
      font-weight: 800;
      color: #1A1100;
      flex-shrink: 0;
    }
    .report-name {
      font-size: 14px;
      font-weight: 700;
      color: #1A1100;
    }
    .report-position {
      font-size: 11px;
      color: #9A8F7A;
      margin-top: 2px;
    }
    .report-link {
      display: inline-block;
      background: #1A1100;
      color: #FDCB40;
      text-decoration: none;
      font-size: 12px;
      font-weight: 700;
      padding: 6px 14px;
      border-radius: 8px;
      white-space: nowrap;
    }
    /* 푸터 */
    .footer {
      background: #F5EDDB;
      border-top: 2px solid #1A1100;
      padding: 18px 32px;
      text-align: center;
      font-size: 11px;
      color: #9A8F7A;
      line-height: 1.6;
    }
    .footer strong {
      color: #4A3F2A;
    }
  </style>
</head>
<body>
  <div class="wrapper">

    <!-- 헤더 -->
    <div class="header">
      <div class="header-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1A1100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M16 13H8M16 17H8M10 9H8"/>
        </svg>
      </div>
      <div>
        <div class="header-title">주간업무보고</div>
        <div class="header-sub">SE팀 주간보고 시스템</div>
      </div>
    </div>

    <!-- 바디 -->
    <div class="body">
      <p class="greeting">
        안녕하세요.<br>
        이번 주 팀원 주간업무보고를 전달드립니다.
      </p>

      <div class="week-badge">
        📅 {{ \Carbon\Carbon::parse($data['week_start'])->format('Y.m.d') }}
        ({{ ['일','월','화','수','목','금','토'][\Carbon\Carbon::parse($data['week_start'])->dayOfWeek] }})
        &nbsp;~&nbsp;
        {{ \Carbon\Carbon::parse($data['week_end'])->format('m.d') }}
        ({{ ['일','월','화','수','목','금','토'][\Carbon\Carbon::parse($data['week_end'])->dayOfWeek] }})
      </div>

      <hr class="divider">

      <!-- 보고서 링크 목록 -->
      <div class="report-list">
        @foreach ($data['reportLinks'] as $report)
        <div class="report-item">
          <div class="report-item-left">
            <div class="avatar">{{ mb_substr($report['name'], 0, 1) }}</div>
            <div>
              <div class="report-name">{{ $report['name'] }}</div>
              <div class="report-position">{{ $report['position'] }}</div>
            </div>
          </div>
          <a href="{{ $report['url'] }}" class="report-link" target="_blank">보고서 보기 →</a>
        </div>
        @endforeach
      </div>

      <p style="font-size:13px;color:#9A8F7A;line-height:1.7;">
        감사합니다.
      </p>
    </div>

    <!-- 푸터 -->
    <div class="footer">
      <strong>주간업무보고 시스템</strong><br>
      이 메일은 시스템에서 자동 발송된 메일입니다.
    </div>
  </div>
</body>
</html>
