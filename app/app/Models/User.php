<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'position',
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
}
