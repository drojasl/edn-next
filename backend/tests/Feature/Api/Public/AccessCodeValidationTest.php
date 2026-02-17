<?php

namespace Tests\Feature\Api\Public;

use App\Models\AccessCode;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class AccessCodeValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_access_code_returns_slugs()
    {
        $user = \App\Models\Entrepreneur::create([
            'name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'codigo_amway' => '1234567',
            'slug' => 'entrepreneur-1',
            'is_active' => true
        ]);
        
        $course = Course::create([
            'user_id' => $user->id,
            'title' => 'Course 1',
            'slug' => 'course-1',
            'is_active' => true
        ]);
        
        $accessCode = AccessCode::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'code' => 'TESTCODE123',
            'is_active' => true
        ]);

        $response = $this->postJson('/api/v1/public/access-codes/validate', [
            'code' => 'TESTCODE123'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'entrepreneurSlug' => 'entrepreneur-1',
                'courseSlug' => 'course-1'
            ]);
    }

    public function test_invalid_access_code_returns_404()
    {
        $response = $this->postJson('/api/v1/public/access-codes/validate', [
            'code' => 'INVALIDCODE'
        ]);

        $response->assertStatus(404);
    }

    public function test_expired_access_code_returns_403()
    {
        $user = \App\Models\Entrepreneur::create([
            'name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'password' => Hash::make('password'),
            'codigo_amway' => '7654321',
            'slug' => 'entrepreneur-2',
            'is_active' => true
        ]);
        
        $course = Course::create([
            'user_id' => $user->id,
            'title' => 'Course 2',
            'slug' => 'course-2',
            'is_active' => true
        ]);
        
        $accessCode = AccessCode::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'code' => 'EXPIREDCODE',
            'is_active' => true,
            'expires_at' => Carbon::now()->subDay()
        ]);

        $response = $this->postJson('/api/v1/public/access-codes/validate', [
            'code' => 'EXPIREDCODE'
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'El código de acceso ha expirado.']);
    }
}
