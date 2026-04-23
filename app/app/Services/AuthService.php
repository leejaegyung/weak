<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    public function login(array $credentials): array
    {
        $user = User::where('username', $credentials['username'])->first();

        if (!$user) {
            return ['success' => false, 'message' => '아이디 또는 비밀번호가 올바르지 않습니다.'];
        }

        if ($user->registration_status === 'pending') {
            return ['success' => false, 'message' => '관리자 승인 대기 중입니다. 승인 후 로그인하세요.'];
        }

        if ($user->registration_status === 'rejected') {
            return ['success' => false, 'message' => '가입이 거절되었습니다. 관리자에게 문의하세요.'];
        }

        if (!$user->is_active) {
            return ['success' => false, 'message' => '비활성화된 계정입니다. 관리자에게 문의하세요.'];
        }

        $result = Auth::attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
        ], $credentials['remember'] ?? false);

        if ($result) {
            $user->update(['last_login_at' => now()]);
            return ['success' => true, 'message' => ''];
        }

        return ['success' => false, 'message' => '아이디 또는 비밀번호가 올바르지 않습니다.'];
    }

    public function logout(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
    }
}
