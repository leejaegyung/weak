<?php

namespace App\Http\Controllers;

use App\Models\UserSite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /** 개인설정 페이지 */
    public function show(): Response
    {
        $user  = Auth::user();
        $sites = $user->sites->map(fn($s) => ['id' => $s->id, 'name' => $s->name])->values()->toArray();

        return Inertia::render('Profile/Index', [
            'profileUser' => [
                'id'               => $user->id,
                'name'             => $user->name,
                'username'         => $user->username,
                'position'         => $user->position ?? '',
                'role'             => $user->role,
                'kakao_connected'  => !empty($user->kakao_id),
                'avatar_color'     => $user->avatar_color,
                'avatar_image_url' => $user->avatar_image_url,
            ],
            'sites' => $sites,
        ]);
    }

    /** 카카오 연동 해제 */
    public function kakaoDisconnect(): RedirectResponse
    {
        Auth::user()->update([
            'kakao_id'            => null,
            'kakao_access_token'  => null,
            'kakao_refresh_token' => null,
        ]);
        return back()->with('success', '카카오 연동이 해제되었습니다.');
    }

    /** 기본 정보 수정 (이름, 직급, 비밀번호) */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $data = $request->validate([
            'name'                  => ['required', 'string', 'max:50'],
            'position'              => ['nullable', 'string', 'max:30'],
            'current_password'      => ['nullable', 'string'],
            'new_password'          => ['nullable', 'confirmed', Password::min(6)],
        ]);

        // 비밀번호 변경 요청 시 현재 비밀번호 확인
        if ($data['new_password'] ?? null) {
            if (!Hash::check($data['current_password'] ?? '', $user->password)) {
                return back()->withErrors(['current_password' => '현재 비밀번호가 올바르지 않습니다.']);
            }
            $user->password = Hash::make($data['new_password']);
        }

        $user->name     = $data['name'];
        $user->position = $data['position'] ?? $user->position;
        $user->save();

        return redirect()->route('profile')->with('success', '개인정보가 저장되었습니다.');
    }

    /** 아바타 설정 (색상 또는 사진) */
    public function updateAvatar(Request $request): JsonResponse
    {
        $user = Auth::user();
        $type = $request->input('type', 'color');

        if ($type === 'image') {
            $request->validate(['image' => ['required', 'image', 'max:2048']]);

            if ($user->avatar_image) {
                Storage::disk('public')->delete($user->avatar_image);
            }

            $path = $request->file('image')->store('avatars', 'public');
            $user->avatar_image = $path;
            $user->avatar_color = null;
            $user->save();

            return response()->json([
                'avatar_color'     => null,
                'avatar_image_url' => Storage::url($path),
            ]);
        }

        if ($type === 'remove') {
            if ($user->avatar_image) {
                Storage::disk('public')->delete($user->avatar_image);
                $user->avatar_image = null;
            }
            $user->save();

            return response()->json([
                'avatar_color'     => $user->avatar_color,
                'avatar_image_url' => null,
            ]);
        }

        // type === 'color'
        $request->validate(['color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/']]);
        $user->avatar_color = $request->input('color');
        $user->save();

        return response()->json([
            'avatar_color'     => $user->avatar_color,
            'avatar_image_url' => $user->avatar_image_url,
        ]);
    }

    /** 점검 사이트 추가 */
    public function storeSite(Request $request): JsonResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:100']]);

        $site = Auth::user()->sites()->create(['name' => trim($request->name)]);

        return response()->json(['id' => $site->id, 'name' => $site->name]);
    }

    /** 점검 사이트 삭제 */
    public function destroySite(UserSite $site): JsonResponse
    {
        if ($site->user_id !== Auth::id()) {
            abort(403);
        }

        $site->delete();

        return response()->json(['ok' => true]);
    }
}
