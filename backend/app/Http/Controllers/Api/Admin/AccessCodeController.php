<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessCode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AccessCodeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $codes = AccessCode::with(['course:id,title', 'user:id,name,last_name'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'data' => $codes
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'code' => 'required|string|max:50|unique:access_codes,code',
            'expiration_days' => 'nullable|in:2,7,15',
        ]);

        $expiresAt = null;
        if (!empty($validated['expiration_days'])) {
            $expiresAt = now()->addDays((int) $validated['expiration_days']);
        }

        $code = AccessCode::create([
            'user_id' => $request->user()->id,
            'course_id' => $validated['course_id'],
            'code' => strtoupper($validated['code']),
            'expires_at' => $expiresAt,
            'is_active' => true,
        ]);

        return response()->json([
            'data' => $code->load(['course:id,title', 'user:id,name,last_name'])
        ], 201);
    }

    /**
     * Validate code uniqueness
     */
    public function validateCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $exists = AccessCode::where('code', strtoupper($request->code))->exists();

        return response()->json([
            'available' => !$exists
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, AccessCode $accessCode)
    {
        if ($accessCode->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $accessCode->delete();

        return response()->json(['message' => 'Código eliminado correctamente.']);
    }
}
