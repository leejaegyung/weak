{{--
  주간업무보고 메일 템플릿

  메일 클라이언트(Gmail·Outlook 등)는 flexbox·grid·인라인 SVG·<style> 태그를
  지원하지 않거나 제거한다. 따라서 레이아웃은 table, 스타일은 전부 인라인,
  아이콘은 이모지로 작성한다. (수정 시 이 규칙을 반드시 유지할 것)
--}}
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $data['subject'] }}</title>
</head>
<body style="margin:0;padding:0;background:#FFF8EE;font-family:'Apple SD Gothic Neo','Malgun Gothic','Noto Sans KR',Arial,sans-serif;color:#1A1100;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#FFF8EE;padding:32px 12px;">
  <tr>
    <td align="center">

      <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:100%;background:#ffffff;border:2px solid #1A1100;border-radius:16px;overflow:hidden;">

        <!-- 헤더 -->
        <tr>
          <td style="background:#FD4401;padding:22px 28px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td width="44" style="width:44px;padding-right:12px;vertical-align:middle;">
                  <div style="width:40px;height:40px;line-height:40px;background:#FDCB40;border:2px solid #1A1100;border-radius:10px;text-align:center;font-size:20px;">📋</div>
                </td>
                <td style="vertical-align:middle;">
                  <div style="font-size:18px;font-weight:800;color:#ffffff;letter-spacing:-0.02em;">주간업무보고</div>
                  <div style="font-size:12px;color:#FFE0D2;margin-top:3px;">SE팀 주간보고 시스템</div>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- 바디 -->
        <tr>
          <td style="padding:30px 28px;">

            <!-- 인사말 -->
            <div style="font-size:15px;color:#1A1100;line-height:1.7;margin-bottom:22px;">
              {!! nl2br(e($data['body_intro'] ?? "안녕하세요.\n이번 주 팀원 주간업무보고를 전달드립니다.")) !!}
            </div>

            @if (!empty($data['body_main']))
            <!-- 본문 내용 -->
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:22px;">
              <tr>
                <td style="background:#FFFBEF;border:1.5px solid #E8E0D0;border-radius:10px;padding:16px 18px;font-size:14px;color:#1A1100;line-height:1.7;">
                  {!! nl2br(e($data['body_main'])) !!}
                </td>
              </tr>
            </table>
            @endif

            <!-- 기간 뱃지 -->
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:24px;">
              <tr>
                <td style="background:#FFF0A0;border:2px solid #1A1100;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:800;color:#1A1100;white-space:nowrap;">
                  📅 {{ \Carbon\Carbon::parse($data['week_start'])->format('Y.m.d') }}({{ ['일','월','화','수','목','금','토'][\Carbon\Carbon::parse($data['week_start'])->dayOfWeek] }})
                  ~ {{ \Carbon\Carbon::parse($data['week_end'])->format('m.d') }}({{ ['일','월','화','수','목','금','토'][\Carbon\Carbon::parse($data['week_end'])->dayOfWeek] }})
                </td>
              </tr>
            </table>

            <!-- 구분선 -->
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:24px;">
              <tr><td style="border-top:2px solid #1A1100;font-size:0;line-height:0;">&nbsp;</td></tr>
            </table>

            <!-- 통합 바로가기 버튼 -->
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:20px;">
              <tr>
                <td align="center">
                  <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                      <td style="background:#FD4401;border:2px solid #1A1100;border-radius:12px;">
                        <a href="{{ $data['list_url'] }}" target="_blank"
                           style="display:block;padding:15px 34px;font-size:15px;font-weight:800;color:#ffffff;text-decoration:none;letter-spacing:-0.01em;">
                          주간업무보고 전체 보기 &rarr;
                        </a>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <!-- 포함된 보고서 목록 -->
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:24px;">
              <tr>
                <td style="background:#FDFAF5;border:1.5px solid #E8E0D0;border-radius:12px;padding:16px 18px;">
                  <div style="font-size:11px;font-weight:700;color:#9A8F7A;letter-spacing:0.06em;margin-bottom:10px;">
                    이번 주 제출 {{ count($data['reports']) }}명
                  </div>
                  <div style="font-size:0;line-height:0;">
                    @foreach ($data['reports'] as $report)
                    <span style="display:inline-block;background:#FFF0A0;border:1.5px solid #1A1100;border-radius:99px;padding:5px 13px;margin:0 6px 6px 0;font-size:13px;font-weight:700;color:#1A1100;line-height:1.4;">
                      {{ $report['name'] }}@if (!empty($report['position']))<span style="font-size:11px;font-weight:500;color:#4A3F2A;">&nbsp;{{ $report['position'] }}</span>@endif
                    </span>
                    @endforeach
                  </div>
                </td>
              </tr>
            </table>

            <!-- 맺음말 -->
            <div style="font-size:13px;color:#9A8F7A;line-height:1.7;">
              {!! nl2br(e($data['body_outro'] ?? '감사합니다.')) !!}
            </div>

          </td>
        </tr>

        <!-- 푸터 -->
        <tr>
          <td style="background:#F5EDDB;border-top:2px solid #1A1100;padding:18px 28px;text-align:center;font-size:11px;color:#9A8F7A;line-height:1.6;">
            <strong style="color:#4A3F2A;">주간업무보고 시스템</strong><br>
            이 메일은 시스템에서 자동 발송된 메일입니다.
          </td>
        </tr>

      </table>

    </td>
  </tr>
</table>

</body>
</html>
