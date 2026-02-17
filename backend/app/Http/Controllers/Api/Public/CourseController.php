<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;
use Illuminate\Http\JsonResponse;

class CourseController extends Controller
{
    /**
     * Display the specified course for prospects.
     */
    public function show(string $entrepreneurSlug, string $courseSlug): JsonResponse
    {
        // 1. Resolve Entrepreneur
        $user = \App\Models\Entrepreneur::where('slug', $entrepreneurSlug)
            ->where('is_active', true)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Entrepreneur not found'], 404);
        }

        // 2. Resolve Course with its nodes and entrepreneur's profile
        $course = Course::where('user_id', $user->id)
            ->where('slug', $courseSlug)
            ->where('is_active', true)
            ->with(['nodes.options', 'user.styleSettings', 'user.socialLinks'])
            ->first();

        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        return response()->json([
            'course' => $course,
            'entrepreneur' => [
                'name' => $user->name,
                'last_name' => $user->last_name,
                'slug' => $user->slug,
                'style' => $user->styleSettings,
                'social' => $user->socialLinks->pluck('value', 'platform'),
            ]
        ]);
    }
}
