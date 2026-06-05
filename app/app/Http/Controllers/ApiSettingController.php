<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreCustomApiRequest;
use App\Http\Requests\Admin\UpdateApiSettingRequest;
use App\Models\Setting;
use App\Services\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ApiSettingController extends Controller
{
    private static function maskKey(string $key): string
    {
        if (empty($key)) return '';
        if (strlen($key) <= 8) return str_repeat('•', strlen($key));
        return substr($key, 0, 8) . '•••••' . substr($key, -4);
    }

    public function index(): Response
    {
        $anthropicKey = Setting::get('api.anthropic_key', '');
        $openaiKey    = Setting::get('api.openai_key', '');
        $aiProvider   = Setting::get('api.ai_provider', 'anthropic');
        $customRaw    = Setting::get('api.custom_services', '[]');
        $customList   = json_decode($customRaw, true) ?? [];

        return Inertia::render('Admin/ApiSettings', [
            'aiProvider'   => $aiProvider,
            'aiServices'   => [
                [
                    'id'          => 'anthropic',
                    'name'        => 'Anthropic Claude',
                    'description' => '요구/이슈 등록 시 AI 자동 검토에 사용됩니다',
                    'is_set'      => !empty($anthropicKey),
                    'key_preview' => self::maskKey($anthropicKey),
                    'docs_url'    => 'https://console.anthropic.com/account/keys',
                ],
                [
                    'id'          => 'openai',
                    'name'        => 'OpenAI (GPT)',
                    'description' => 'GPT-4o 모델을 사용하여 이슈를 분석합니다',
                    'is_set'      => !empty($openaiKey),
                    'key_preview' => self::maskKey($openaiKey),
                    'docs_url'    => 'https://platform.openai.com/account/api-keys',
                ],
            ],
            'customApis' => array_map(fn($item) => [
                'id'          => $item['id'],
                'name'        => $item['name'],
                'description' => $item['description'] ?? '',
                'endpoint'    => $item['endpoint'] ?? '',
                'key_preview' => self::maskKey($item['api_key'] ?? ''),
                'is_set'      => !empty($item['api_key']),
            ], $customList),
        ]);
    }

    public function update(UpdateApiSettingRequest $request): RedirectResponse
    {
        $service = $request->input('service');
        $apiKey  = trim($request->input('api_key', ''));

        match ($service) {
            'anthropic'   => Setting::set('api.anthropic_key', $apiKey),
            'openai'      => Setting::set('api.openai_key', $apiKey),
            'ai_provider' => Setting::set('api.ai_provider', $apiKey),
        };

        $message = $service === 'ai_provider'
            ? 'AI 제공자가 변경되었습니다.'
            : (empty($apiKey) ? 'API 키가 삭제되었습니다.' : 'API 키가 저장되었습니다.');

        return back()->with('success', $message);
    }

    public function test(Request $request, AiService $ai): JsonResponse
    {
        if (!$request->user()?->isAdmin()) {
            return response()->json(['ok' => false, 'message' => '권한이 없습니다.'], 403);
        }

        $request->validate(['provider' => ['required', 'string', 'in:anthropic,openai']]);

        $result = $ai->testConnection();
        return response()->json($result);
    }

    // ── 추가 API CRUD ─────────────────────────────────────────

    public function storeCustom(StoreCustomApiRequest $request): RedirectResponse
    {
        $list = json_decode(Setting::get('api.custom_services', '[]'), true) ?? [];

        $list[] = [
            'id'          => (string) Str::uuid(),
            'name'        => $request->input('name'),
            'description' => $request->input('description', ''),
            'endpoint'    => $request->input('endpoint', ''),
            'api_key'     => $request->input('api_key'),
        ];

        Setting::set('api.custom_services', json_encode($list));
        return back()->with('success', 'API 서비스가 추가되었습니다.');
    }

    public function updateCustom(Request $request, string $id): RedirectResponse
    {
        if (!$request->user()?->isAdmin()) abort(403);

        $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:300'],
            'endpoint'    => ['nullable', 'url', 'max:500'],
            'api_key'     => ['nullable', 'string', 'max:500'],
        ]);

        $list = json_decode(Setting::get('api.custom_services', '[]'), true) ?? [];
        $idx  = array_search($id, array_column($list, 'id'));

        if ($idx === false) abort(404);

        $newKey = trim($request->input('api_key', ''));
        $list[$idx] = [
            'id'          => $id,
            'name'        => $request->input('name'),
            'description' => $request->input('description', ''),
            'endpoint'    => $request->input('endpoint', ''),
            'api_key'     => !empty($newKey) ? $newKey : $list[$idx]['api_key'],
        ];

        Setting::set('api.custom_services', json_encode(array_values($list)));
        return back()->with('success', 'API 서비스가 수정되었습니다.');
    }

    public function destroyCustom(Request $request, string $id): RedirectResponse
    {
        if (!$request->user()?->isAdmin()) abort(403);

        $list    = json_decode(Setting::get('api.custom_services', '[]'), true) ?? [];
        $filtered = array_filter($list, fn($item) => $item['id'] !== $id);

        Setting::set('api.custom_services', json_encode(array_values($filtered)));
        return back()->with('success', 'API 서비스가 삭제되었습니다.');
    }

    public function testCustom(Request $request, string $id): JsonResponse
    {
        if (!$request->user()?->isAdmin()) {
            return response()->json(['ok' => false, 'message' => '권한이 없습니다.'], 403);
        }

        $list = json_decode(Setting::get('api.custom_services', '[]'), true) ?? [];
        $idx  = array_search($id, array_column($list, 'id'));
        if ($idx === false) return response()->json(['ok' => false, 'message' => '서비스를 찾을 수 없습니다.'], 404);

        $item     = $list[$idx];
        $apiKey   = $item['api_key'] ?? '';
        $endpoint = $item['endpoint'] ?? '';

        if (empty($apiKey)) {
            return response()->json(['ok' => false, 'message' => 'API 키가 설정되지 않았습니다.']);
        }

        if (empty($endpoint)) {
            return response()->json(['ok' => true, 'message' => 'API 키가 저장되어 있습니다. (엔드포인트 미설정 — 연결 테스트 생략)']);
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept'        => 'application/json',
            ])->timeout(10)->get($endpoint);

            $status = $response->status();
            if ($status < 500) {
                return response()->json(['ok' => true, 'message' => "엔드포인트 응답 확인 (HTTP {$status})"]);
            }
            return response()->json(['ok' => false, 'message' => "서버 오류 응답 (HTTP {$status})"]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => '연결 실패: ' . $e->getMessage()]);
        }
    }
}
