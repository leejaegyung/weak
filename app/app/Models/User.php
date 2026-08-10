<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /** 로그인 후 시작 화면으로 지정할 수 있는 좌측 탭 (경로 => 표시명) */
    public const DEFAULT_PAGES = [
        '/schedules'      => '팀 일정판',
        '/reports'        => '보고서 목록',
        '/reports/create' => '보고서 작성',
        '/issues'         => '요구/이슈',
    ];

    /** 별도로 지정하지 않았을 때의 시작 화면 */
    public const DEFAULT_PAGE_FALLBACK = '/schedules';

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'position',
        'default_page',
        'role',
        'is_active',
        'is_hidden',
        'sort_order',
        'registration_status',
        'google_id',
        'kakao_id',
        'kakao_access_token',
        'kakao_refresh_token',
        'kakao_channel_uuid',
        'last_login_at',
        'avatar_color',
        'avatar_image',
    ];

    protected $appends = ['avatar_image_url'];

    protected $hidden = [
        'password',
        'remember_token',
        'kakao_access_token',
        'kakao_refresh_token',
        'kakao_channel_uuid',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active'      => 'boolean',
            'is_hidden'      => 'boolean',
            'last_login_at'  => 'datetime',
        ];
    }

    public function weeklyReports()
    {
        return $this->hasMany(WeeklyReport::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function sites()
    {
        return $this->hasMany(UserSite::class)->orderBy('created_at');
    }

    public function issues()
    {
        return $this->hasMany(Issue::class);
    }

    public function reportComments()
    {
        return $this->hasMany(ReportComment::class);
    }

    public function getAvatarImageUrlAttribute(): ?string
    {
        return $this->avatar_image
            ? \Illuminate\Support\Facades\Storage::url($this->avatar_image)
            : null;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * 로그인 후 이동할 경로.
     * 허용 목록에 없는 값(설정 안 함·삭제된 화면)은 기본값으로 되돌린다.
     */
    public function defaultPage(): string
    {
        return array_key_exists((string) $this->default_page, self::DEFAULT_PAGES)
            ? $this->default_page
            : self::DEFAULT_PAGE_FALLBACK;
    }
}
