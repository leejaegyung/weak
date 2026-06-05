<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    private string $provider;
    private string $apiKey;

    private const CLAUDE_MODEL = 'claude-opus-4-7';
    private const OPENAI_MODEL = 'gpt-4o';

    public function __construct()
    {
        $this->provider = Setting::get('api.ai_provider', 'anthropic');

        if ($this->provider === 'openai') {
            $dbKey = Setting::get('api.openai_key', '');
            $this->apiKey = !empty($dbKey) ? $dbKey : config('services.openai.api_key', '');
        } else {
            $dbKey = Setting::get('api.anthropic_key', '');
            $this->apiKey = !empty($dbKey) ? $dbKey : config('services.anthropic.api_key', '');
        }
    }

    public function analyzeIssue(string $title, string $content): array
    {
        if (empty($this->apiKey)) {
            return [
                'ok'       => false,
                'response' => 'AI 분석 기능을 사용하려면 관리자가 API 키를 설정해야 합니다.',
            ];
        }

        return $this->provider === 'openai'
            ? $this->analyzeWithOpenAi($title, $content)
            : $this->analyzeWithClaude($title, $content);
    }

    public function testConnection(): array
    {
        if (empty($this->apiKey)) {
            return ['ok' => false, 'message' => 'API 키가 설정되지 않았습니다.'];
        }

        return $this->provider === 'openai'
            ? $this->testOpenAi()
            : $this->testClaude();
    }

    private function buildPrompt(string $title, string $content): string
    {
        return <<<EOT
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
    }

    private function analyzeWithClaude(string $title, string $content): array
    {
        try {
            $response = Http::withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model'      => self::CLAUDE_MODEL,
                'max_tokens' => 512,
                'messages'   => [['role' => 'user', 'content' => $this->buildPrompt($title, $content)]],
            ]);

            if (!$response->successful()) {
                Log::error('Claude API error', ['status' => $response->status(), 'body' => $response->body()]);
                return ['ok' => false, 'response' => 'AI 분석 중 오류가 발생했습니다.'];
            }

            $text = $response->json('content.0.text', '');
            return ['ok' => true, 'response' => $text];
        } catch (\Throwable $e) {
            Log::error('Claude API exception', ['message' => $e->getMessage()]);
            return ['ok' => false, 'response' => 'AI 분석 중 오류가 발생했습니다.'];
        }
    }

    private function analyzeWithOpenAi(string $title, string $content): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model'      => self::OPENAI_MODEL,
                'max_tokens' => 512,
                'messages'   => [['role' => 'user', 'content' => $this->buildPrompt($title, $content)]],
            ]);

            if (!$response->successful()) {
                Log::error('OpenAI API error', ['status' => $response->status(), 'body' => $response->body()]);
                return ['ok' => false, 'response' => 'AI 분석 중 오류가 발생했습니다.'];
            }

            $text = $response->json('choices.0.message.content', '');
            return ['ok' => true, 'response' => $text];
        } catch (\Throwable $e) {
            Log::error('OpenAI API exception', ['message' => $e->getMessage()]);
            return ['ok' => false, 'response' => 'AI 분석 중 오류가 발생했습니다.'];
        }
    }

    private function testClaude(): array
    {
        try {
            $response = Http::withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(15)->post('https://api.anthropic.com/v1/messages', [
                'model'      => self::CLAUDE_MODEL,
                'max_tokens' => 16,
                'messages'   => [['role' => 'user', 'content' => '안녕']],
            ]);

            if ($response->successful()) return ['ok' => true, 'message' => 'Claude API 키가 정상적으로 작동합니다.'];
            $error = $response->json('error.message', '알 수 없는 오류');
            return ['ok' => false, 'message' => "Claude API 오류: {$error}"];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => '연결 실패: ' . $e->getMessage()];
        }
    }

    private function testOpenAi(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(15)->post('https://api.openai.com/v1/chat/completions', [
                'model'      => self::OPENAI_MODEL,
                'max_tokens' => 16,
                'messages'   => [['role' => 'user', 'content' => '안녕']],
            ]);

            if ($response->successful()) return ['ok' => true, 'message' => 'OpenAI API 키가 정상적으로 작동합니다.'];
            $error = $response->json('error.message', '알 수 없는 오류');
            return ['ok' => false, 'message' => "OpenAI API 오류: {$error}"];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => '연결 실패: ' . $e->getMessage()];
        }
    }
}
