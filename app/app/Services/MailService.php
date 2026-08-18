<?php

namespace App\Services;

use App\Mail\WeeklyReportMail;
use App\Models\Setting;
use App\Models\WeeklyMailLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MailService
{
    /** DB 설정값을 런타임 SMTP 설정으로 적용 */
    public function configureSmtp(): void
    {
        $host       = Setting::get('smtp_host', '');
        $port       = (int) (Setting::get('smtp_port', '587') ?: 587);
        $encryption = Setting::get('smtp_encryption', 'tls');
        $username   = Setting::get('smtp_username', '');
        $password   = Setting::get('smtp_password', '');
        $fromAddr   = Setting::get('smtp_from_address', '');
        $fromName   = Setting::get('smtp_from_name', '주간업무보고 시스템');

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host',       $host);
        Config::set('mail.mailers.smtp.port',       $port);
        Config::set('mail.mailers.smtp.encryption', ($encryption === 'none' || $encryption === '') ? null : $encryption);
        Config::set('mail.mailers.smtp.username',   $username);
        Config::set('mail.mailers.smtp.password',   $password);
        Config::set('mail.from.address',            $fromAddr ?: 'noreply@example.com');
        Config::set('mail.from.name',               $fromName);

        // OpenSSL 3.x + cafe24 SMTP 호환: TLS 1.3 미지원 서버를 위해 TLS 1.2 강제
        Config::set('mail.mailers.smtp.stream', [
            'ssl' => [
                'crypto_method'     => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
                'allow_self_signed' => true,
                'verify_peer'       => false,
                'verify_peer_name'  => false,
            ],
        ]);

        // 설정 변경 후 transport 재생성 강제
        Mail::purge('smtp');

        // 비밀번호는 로그에 남기지 않음 (전체 mail 설정 dump는 유출 위험)
        Log::info('SMTP configured from settings', [
            'host'       => $host,
            'port'       => $port,
            'encryption' => $encryption,
            'username'   => $username,
            'from'       => $fromAddr,
        ]);
    }

    /**
     * 주간보고 메일 발송 후 전송 이력을 남긴다.
     *
     * @param array $data {to, cc[], subject, week, week_start, week_end, reports[]}
     */
    public function sendWeeklyReport(array $data): WeeklyMailLog
    {
        $this->configureSmtp();

        $mailer = Mail::to($data['to']);
        if (!empty($data['cc'])) {
            $mailer->cc(array_filter($data['cc']));
        }
        $mailer->send(new WeeklyReportMail($data));

        return $this->recordSent($data);
    }

    /** 전송 성공 건을 이력 테이블에 기록 */
    private function recordSent(array $data): WeeklyMailLog
    {
        $sender = Auth::user();

        return WeeklyMailLog::create([
            'week'         => $data['week'],
            'week_start'   => $data['week_start'],
            'sent_by'      => $sender?->id,
            'sender_name'  => $sender?->name,
            'to_email'     => $data['to'],
            'cc_emails'    => array_values(array_filter($data['cc'] ?? [])),
            'subject'      => $data['subject'],
            'report_count' => count($data['reports'] ?? []),
            'sent_at'      => now(),
        ]);
    }

    /**
     * 해당 주차의 마지막 메일 전송 이력. 전송된 적이 없으면 null.
     * 보고서 목록 화면에서 "이번 주차 메일 전송 여부"를 표시하는 데 사용한다.
     */
    public function weekLog(string $week): ?array
    {
        if (!Schema::hasTable('weekly_mail_logs')) {
            return null;
        }

        $log = WeeklyMailLog::with('sender')
            ->where('week', $week)
            ->orderByDesc('sent_at')
            ->first();

        if (!$log) {
            return null;
        }

        return [
            'sent_at'       => $log->sent_at->format('Y-m-d H:i'),
            'sent_at_short' => $log->sent_at->format('m/d H:i'),
            'sender_name'   => $log->sender_label,
            'to_email'      => $log->to_email,
            'cc_emails'     => $log->cc_emails ?? [],
            'subject'       => $log->subject,
            'report_count'  => $log->report_count,
            'send_count'    => WeeklyMailLog::where('week', $week)->count(),
        ];
    }

    /** SMTP 연결 테스트 메일 발송 */
    public function sendTest(string $toEmail): array
    {
        try {
            Log::info('sendTest start', ['to' => $toEmail]);

            $this->configureSmtp();

            $sent = Mail::raw(
                "✅ 주간업무보고 시스템 메일 연결 테스트입니다.\n이 메일이 수신되었다면 SMTP 설정이 정상입니다.",
                function ($message) use ($toEmail) {
                    $message->to($toEmail)
                            ->subject('[테스트] 주간업무보고 시스템 메일 설정');
                }
            );

            $ctx = ['to' => $toEmail];
            if ($sent instanceof \Symfony\Component\Mailer\SentMessage) {
                $ctx['message_id'] = $sent->getMessageId();
            }
            Log::info('sendTest smtp transport accepted (no exception)', $ctx);

            return ['ok' => true, 'message' => "{$toEmail} 으로 테스트 메일을 전송했습니다."];
        } catch (\Throwable $e) {
            Log::error('sendTest failed', [
                'to'       => $toEmail,
                'message'  => $e->getMessage(),
                'exception'=> $e,
            ]);

            return ['ok' => false, 'message' => '전송 실패: ' . $e->getMessage()];
        }
    }

    /** SMTP 설정이 최소한 구성되어 있는지 확인 */
    public function isConfigured(): bool
    {
        return !empty(Setting::get('smtp_host')) && !empty(Setting::get('smtp_from_address'));
    }
}
