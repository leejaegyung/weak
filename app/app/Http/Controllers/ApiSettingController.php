<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\UpdateApiSettingRequest;
use App\Models\Setting;
use App\Services\ClaudeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return Inertia::render('Admin/ApiSettings', [
            'apiServices' => [
                [
                    'id'          => 'anthropic',
                    'name'        => 'Anthropic Claude API',
                    'description' => '요구/이슈 등록 시 AI 자동 검토 기능에 사용됩니다. console.anthropic.com에서 API 키를 발급받을 수 있습니다.',
                    'is_set'      => !empty($anthropicKey),
                    'key_preview' => self::maskKey($anthropicKey),
                    'docs_url'    => 'https://console.anthropic.com/account/keys',
                    'badge_color' => 'purple',
                ],
            ],
        ]);
    }

    public function update(UpdateApiSettingRequest $request): RedirectResponse
    {
        $service = $request->input('service');
        $apiKey  = trim($request->input('api_key', ''));

        match ($service) {
            'anthropic' => Setting::set('api.anthropic_key', $apiKey),
        };

        $message = empty($apiKey) ? 'API 키가 삭제되었습니다.' : 'API 키가 저장되었습니다.';
        return back()->with('success', $message);
    }

    public function test(Request $request, ClaudeService $claude): JsonResponse
    {
        if (!$request->user()?->isAdmin()) {
            return response()->json(['ok' => false, 'message' => '권한이 없습니다.'], 403);
        }

        $request->validate(['service' => ['required', 'string', 'in:anthropic']]);

        if ($request->input('service') === 'anthropic') {
            $result = $claude->testConnection();
            return response()->json($result);
        }

        return response()->json(['ok' => false, 'message' => '알 수 없는 서비스입니다.']);
    }
}
