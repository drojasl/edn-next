<?php

namespace App\Services;

use App\Mail\WelcomeEntrepreneurMail;
use App\Models\Entrepreneur;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * Register a new entrepreneur and send welcome email.
     *
     * @param array $data
     * @return Entrepreneur
     */
    public function register(array $data, ?string $locale = null): Entrepreneur
    {
        $slug = $data['slug'] ?? Str::slug($data['name'] . ' ' . ($data['last_name'] ?? ''));

        // Ensure slug is unique if not provided or even if provided
        if (Entrepreneur::where('slug', $slug)->exists()) {
            $slug = $slug . '-' . Str::random(5);
        }

        $entrepreneur = Entrepreneur::create([
            'name'              => $data['name'],
            'last_name'         => $data['last_name'] ?? null,
            'email'             => $data['email'],
            'password'          => Hash::make($data['password']),
            'codigo_amway'      => $data['codigo_amway'] ?? null,
            'is_account_holder' => $data['is_account_holder'] ?? true,
            'is_active'         => true,
            'slug'              => $slug,
        ]);

        // Send welcome email (wrapped to avoid blocking registration on mail failure)
        try {
            Mail::to($entrepreneur->email)
                ->locale($locale ?? config('app.locale'))
                ->send(new WelcomeEntrepreneurMail($entrepreneur));
        } catch (\Exception $e) {
            \Log::warning('Welcome email failed for: ' . $entrepreneur->email . ' — ' . $e->getMessage());
        }

        return $entrepreneur;
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
