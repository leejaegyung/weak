<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\ReportComment;
use App\Models\Schedule;
use App\Models\User;
use App\Models\WeeklyReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function list(): array
    {
        return User::withCount(['weeklyReports', 'schedules', 'reportComments', 'issues'])
            ->orderBy('sort_order')->orderBy('name')->get()->map(fn($u) => [
            'id'                => $u->id,
            'name'              => $u->name,
            'username'          => $u->username,
            'email'             => $u->email,
            'position'          => $u->position,
            'role'              => $u->role,
            'is_active'         => (bool) $u->is_active,
            'is_hidden'         => (bool) $u->is_hidden,
            'last_login_at'     => $u->last_login_at?->format('Y-m-d H:i'),
            'avatar_color'      => $u->avatar_color,
            'avatar_image_url'  => $u->avatar_image_url,
            // 삭제 확인 안내용 보유 데이터 건수
            'report_count'      => $u->weekly_reports_count,
            'schedule_count'    => $u->schedules_count,
            'comment_count'     => $u->report_comments_count,
            'issue_count'       => $u->issues_count,
        ])->toArray();
    }

    public function create(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'] ?? ($data['username'] . '@company.local'),
            'password' => Hash::make($data['password']),
            'position' => $data['position'] ?? null,
            'role' => $data['role'] ?? 'user',
            'is_active' => true,
        ]);
    }

    public function update(User $user, array $data): User
    {
        $updateData = [
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'] ?? $user->email,
            'position' => $data['position'] ?? null,
            'role' => $data['role'] ?? $user->role,
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);
        return $user->fresh();
    }

    /**
     * 사용자 삭제. 작성한 주간보고는 남기고 작성자 정보만 스냅샷으로 보존한다.
     * (일정·이슈·코멘트·사이트·알림은 DB 제약에 따라 함께 삭제된다)
     */
    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            WeeklyReport::where('user_id', $user->id)->update([
                'author_name'     => $user->name,
                'author_position' => $user->position,
            ]);

            $user->delete();   // weekly_reports.user_id 는 NULL 로 남는다
        });
    }

    /** 삭제 시 함께 사라지는 데이터 건수 (삭제 확인 안내용) */
    public function deletionImpact(User $user): array
    {
        return [
            'reports'   => WeeklyReport::where('user_id', $user->id)->count(),
            'schedules' => Schedule::where('user_id', $user->id)->count(),
            'comments'  => ReportComment::where('user_id', $user->id)->count(),
            'issues'    => Issue::where('user_id', $user->id)->count(),
        ];
    }

    public function toggleActive(User $user): User
    {
        $user->update(['is_active' => !$user->is_active]);
        return $user->fresh();
    }
}
