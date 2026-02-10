<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Register a new user.
     *
     * @param array $data
     * @return User
     */
    public function register(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'last_name' => $data['last_name'] ?? null,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'codigo_amway' => $data['codigo_amway'] ?? null,
            'is_active' => true,
        ]);
    }

    /**
     * Attempt to login a user.
     *
     * @param array $credentials
     * @return User|null
     */
    public function login(array $credentials): ?User
    {
        // Login logic to be implemented (Sanctum/Session)
        return null;
    }
}
