<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\AccessCode;
use App\Models\ProspectAccessLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PublicAccessController extends Controller
{
    /**
     * Validate an access code and return course/entrepreneur slugs.
     */
    public function validateCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'session_id' => 'required|string',
        ]);

        $code = $request->input('code');
        $sessionId = $request->input('session_id');

        // Search for the access code with related user (entrepreneur) and course
        $accessCode = AccessCode::where('code', $code)
            ->where('is_active', true)
            ->with(['user', 'course'])
            ->first();

        if (!$accessCode) {
            return response()->json([
                'message' => 'Código de acceso no válido o inactivo.'
            ], 404);
        }

        // Check expiration
        if ($accessCode->expires_at && Carbon::now()->gt($accessCode->expires_at)) {
            return response()->json([
                'message' => 'El código de acceso ha expirado.'
            ], 403);
        }

        // Verify entrepreneur and course exist and are active
        $user = $accessCode->user;
        $course = $accessCode->course;

        if (!$user || !$user->is_active) {
            return response()->json([
                'message' => 'El empresario asociado no está disponible.'
            ], 404);
        }

        if (!$course || !$course->is_active) {
            return response()->json([
                'message' => 'El curso asociado no está disponible.'
            ], 404);
        }

        // Log the access (visit)
        ProspectAccessLog::updateOrCreate([
            'session_id' => $sessionId,
            'access_code_id' => $accessCode->id,
            'prospect_id' => null, // Will be linked later if they register
        ], [
            'activated_at' => now(),
        ]);

        return response()->json([
            'entrepreneurSlug' => $user->slug,
            'courseSlug' => $course->slug,
            'code' => $accessCode->code,
        ]);
    }
}
