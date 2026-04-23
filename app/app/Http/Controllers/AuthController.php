<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService)
    {
    }

    public function showLogin(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $result = $this->authService->login($request->validated());

        if (!$result['success']) {
            return back()->withErrors([
                'username' => $result['message'],
            ])->onlyInput('username');
        }

        $request->session()->regenerate();

        return redirect()->intended('/reports');
    }

    public function showRegister(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function register(Request $request): RedirectResponse
    {
        $request->validate([
            'name'                  => ['required', 'string', 'max:50'],
            'username'              => ['required', 'string', 'max:30', 'unique:users,username'],
            'password'              => ['required', 'string', 'min:6', 'confirmed'],
            'position'              => ['nullable', 'string', 'max:30'],
            'email'                 => ['nullable', 'email', 'max:100'],
        ], [
            'name.required'         => '이름을 입력해주세요.',
            'username.required'     => '아이디를 입력해주세요.',
            'username.unique'       => '이미 사용 중인 아이디입니다.',
            'password.required'     => '비밀번호를 입력해주세요.',
            'password.min'          => '비밀번호는 최소 6자 이상이어야 합니다.',
            'password.confirmed'    => '비밀번호가 일치하지 않습니다.',
        ]);

        User::create([
            'name'                => $request->name,
            'username'            => $request->username,
            'password'            => Hash::make($request->password),
            'position'            => $request->position,
            'email'               => $request->email,
            'role'                => 'user',
            'is_active'           => false,
            'registration_status' => 'pending',
        ]);

        return redirect('/login')->with('success', '회원가입 신청이 완료되었습니다. 관리자 승인 후 로그인하세요.');
    }

    public function logout(): RedirectResponse
    {
        $this->authService->logout();

        return redirect('/login');
    }
}
