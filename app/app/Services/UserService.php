<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function list(): array
    {
        return User::orderBy('name')->get()->map(fn($u) => [
            'id'            => $u->id,
            'name'          => $u->name,
            'username'      => $u->username,
            'email'         => $u->email,
            'position'      => $u->position,
            'role'          => $u->role,
            'is_active'     => (bool) $u->is_active,
            'last_login_at' => $u->last_login_at?->format('Y-m-d H:i'),
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

    public function delete(User $user): void
    {
        $user->delete();
    }

    public function toggleActive(User $user): User
    {
        $user->update(['is_active' => !$user->is_active]);
        return $user->fresh();
    }
}
