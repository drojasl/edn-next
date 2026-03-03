<?php

namespace App\Services;

use App\Models\Entrepreneur;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * Register a new entrepreneur.
     *
     * @param array $data
     * @return Entrepreneur
     */
    public function register(array $data): Entrepreneur
    {
        $slug = $data['slug'] ?? Str::slug($data['name'] . ' ' . ($data['last_name'] ?? ''));

        // Ensure slug is unique if not provided or even if provided
        if (Entrepreneur::where('slug', $slug)->exists()) {
            $slug = $slug . '-' . Str::random(5);
        }

        return Entrepreneur::create([
            'name' => $data['name'],
            'last_name' => $data['last_name'] ?? null,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'codigo_amway' => $data['codigo_amway'] ?? null,
            'is_account_holder' => $data['is_account_holder'] ?? true,
            'is_active' => true,
            'slug' => $slug,
        ]);
    }

    /**
     * Attempt to login a user.
     *
     * @param array $credentials
     * @return Entrepreneur|null
     */
    public function login(array $credentials): ?Entrepreneur
    {
        // Login logic is handled by Sanctum in LoginController, 
        // but we could add custom logic here if needed.
        return null;
    }
}
