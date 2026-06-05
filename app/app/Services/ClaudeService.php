<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeService
{
    private string $apiKey;
    private string $model = 'claude-opus-4-7';

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key', '');
    }

    public function analyzeIssue(string $title, string $content): array
    {
        if (empty($this->apiKey)) {
            return [
                'ok'       => false,
                'response' => 'AI 분석 기능을 사용하려면 관리자가 ANTHROPIC_API_KEY를 설정해야 합니다.',
                'status'   => 'pending',
            ];
        }

        $prompt = <<<EOT
다음은 사용자가 제출한 요구/이슈 항목입니다. 명확하게 작성되었는지 검토하고 간결하게 답변해 주세요.

제목: {$title}
내용: {$content}

판단 기준:
1. 이슈/요구 사항이 구체적이고 무엇을 요청하는지 명확한가
2. 담당자가 처리할 수 있을 정도로 충분한 맥락 정보가 있는가

명확하다면 → "접수되었습니다." 로 시작하는 짧은 확인 메시지 (1~2문장)
불명확하다면 → "명확하지 않습니다." 로 시작하며, 어떤 정보가 부족한지 구체적으로 설명 (2~3문장)

한국어로만 답변하세요.
EOT;

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model'      => $this->model,
                'max_tokens' => 512,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            if (!$response->successful()) {
                Log::error('Claude API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return [
                    'ok'       => false,
                    'response' => 'AI 분석 중 오류가 발생했습니다. 나중에 다시 시도해 주세요.',
                    'status'   => 'pending',
                ];
            }

            $text      = $response->json('content.0.text', '');
            $isUnclear = str_starts_with(trim($text), '명확하지 않습니다');

            return [
                'ok'       => true,
                'response' => $text,
                'status'   => $isUnclear ? 'unclear' : 'processing',
            ];
        } catch (\Throwable $e) {
            Log::error('Claude API exception', ['message' => $e->getMessage()]);
            return [
                'ok'       => false,
                'response' => 'AI 분석 중 오류가 발생했습니다.',
                'status'   => 'pending',
            ];
        }
    }
}
