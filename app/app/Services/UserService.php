<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\ReportComment;
use App\Models\Schedule;
use App\Models\User;
use App\Models\WeeklyReport;
use Illuminate\Database\QueryException;
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
     * 사용자 삭제. 작성한 주간보고와 요구/이슈는 남기고 작성자 정보만 스냅샷으로 보존한다.
     * (일정·코멘트·사이트·알림은 DB 제약에 따라 함께 삭제된다)
     */
    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            // 보존 대상을 명시적으로 분리한다.
            // FK의 ON DELETE SET NULL 에만 의존하면, 해당 마이그레이션이 적용되지 않은
            // DB에서는 CASCADE 가 동작해 데이터가 조용히 삭제된다. 삭제 확인 화면은
            // "남는다"고 안내하므로 그 약속이 깨진다. 아래 update 는 user_id 가
            // NOT NULL 이면 실패하고 트랜잭션이 되돌려지므로, 데이터가 사라지는 대신
            // 삭제 자체가 중단된다.
            $snapshot = [
                'author_name'     => $user->name,
                'author_position' => $user->position,
                'user_id'         => null,
            ];

            foreach ([
                '주간보고'  => WeeklyReport::class,
                '요구/이슈' => Issue::class,
            ] as $label => $model) {
                try {
                    $model::where('user_id', $user->id)->update($snapshot);
                } catch (QueryException $e) {
                    throw new \RuntimeException(
                        "{$label} 보존 설정이 적용되지 않아 삭제를 중단했습니다. "
                        . '서버에서 php artisan migrate 를 실행한 뒤 다시 시도해 주세요.',
                        0,
                        $e
                    );
                }
            }

            $user->delete();
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
