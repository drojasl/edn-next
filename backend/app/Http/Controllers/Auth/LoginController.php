<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Entrepreneur;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /**
     * Handle user login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Find user by amway_code and is_account_holder
        $user = Entrepreneur::byCredentials(
            $validated['amway_code'],
            $validated['is_account_holder']
        )->first();

        // Check if user exists and password is correct
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Generate Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'codigo_amway' => $user->codigo_amway,
                'is_account_holder' => $user->is_account_holder,
                'name' => $user->name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'slug' => $user->slug,
            ]
        ]);

    }
}
