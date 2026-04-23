<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 관리자 계정
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => '관리자',
                'username' => 'admin',
                'email' => 'admin@company.local',
                'password' => Hash::make('admin1234'),
                'role' => 'admin',
                'is_active' => true,
                'position' => '시스템관리자',
            ]
        );

        $this->command->info('관리자 계정 생성 완료: admin / admin1234');
    }
}
