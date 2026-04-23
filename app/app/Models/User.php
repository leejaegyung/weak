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
        'sort_order',
        'registration_status',
        'google_id',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active'      => 'boolean',
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

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
