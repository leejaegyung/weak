<?php

namespace App\Services;

use App\Mail\WeeklyReportMail;
use App\Models\Setting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

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

        // OpenSSL 3.x + 구형 SMTP 서버(legacy renegotiation) 호환성 옵션
        Config::set('mail.mailers.smtp.stream', [
            'ssl' => [
                'allow_self_signed' => true,
                'verify_peer'       => false,
                'verify_peer_name'  => false,
            ],
        ]);

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
     * 주간보고 메일 발송
     *
     * @param array $data {to, cc[], subject, week_start, week_end, reportLinks[]}
     */
    public function sendWeeklyReport(array $data): void
    {
        $this->configureSmtp();

        $mailer = Mail::to($data['to']);
        if (!empty($data['cc'])) {
            $mailer->cc(array_filter($data['cc']));
        }
        $mailer->send(new WeeklyReportMail($data));
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
