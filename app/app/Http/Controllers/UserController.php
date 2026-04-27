<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private UserService $userService)
    {
    }

    public function index(): Response
    {
        $users = $this->userService->list();

        $pending = User::where('registration_status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($u) => [
                'id'         => $u->id,
                'name'       => $u->name,
                'username'   => $u->username,
                'email'      => $u->email,
                'position'   => $u->position,
                'created_at' => $u->created_at?->format('Y-m-d H:i'),
            ]);

        return Inertia::render('User/Index', [
            'users'   => $users,
            'pending' => $pending,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->userService->create($request->validated());

        return redirect()->route('admin.users.index')
            ->with('success', '사용자가 생성되었습니다.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->userService->update($user, $request->validated());

        return redirect()->route('admin.users.index')
            ->with('success', '사용자 정보가 수정되었습니다.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->userService->delete($user);

        return redirect()->route('admin.users.index')
            ->with('success', '사용자가 삭제되었습니다.');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $this->userService->toggleActive($user);

        return redirect()->route('admin.users.index')
            ->with('success', '사용자 상태가 변경되었습니다.');
    }

    public function toggleHidden(User $user): RedirectResponse
    {
        $user->update(['is_hidden' => !$user->is_hidden]);

        return redirect()->route('admin.users.index')
            ->with('success', $user->is_hidden ? '사용자가 숨김 처리되었습니다.' : '사용자가 표시 처리되었습니다.');
    }

    public function pendingIndex(): Response
    {
        $pending = User::where('registration_status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($u) => [
                'id'         => $u->id,
                'name'       => $u->name,
                'username'   => $u->username,
                'email'      => $u->email,
                'position'   => $u->position,
                'created_at' => $u->created_at?->format('Y-m-d H:i'),
            ]);

        return Inertia::render('User/Pending', ['pending' => $pending]);
    }

    public function approve(User $user): RedirectResponse
    {
        $user->update([
            'registration_status' => 'approved',
            'is_active'           => true,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', $user->name . ' 님의 가입을 승인했습니다.');
    }

    public function rejectRegistration(User $user): RedirectResponse
    {
        $user->update(['registration_status' => 'rejected']);

        return redirect()->route('admin.users.index')
            ->with('success', $user->name . ' 님의 가입을 거절했습니다.');
    }

    /** 관리자: 팀원 순서 저장 */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'order'   => ['required', 'array'],
            'order.*' => ['integer', 'exists:users,id'],
        ]);

        foreach ($request->order as $i => $userId) {
            User::where('id', $userId)->update(['sort_order' => $i]);
        }

        return response()->json(['ok' => true]);
    }
}
