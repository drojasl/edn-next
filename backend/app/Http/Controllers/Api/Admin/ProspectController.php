<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prospect;
use Illuminate\Http\Request;

class ProspectController extends Controller
{
    /**
     * List prospects linked to the authenticated user's access codes.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get prospects associated with any of the user's access codes
        $prospects = Prospect::whereHas('accessCode', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json($prospects);
    }

    /**
     * Toggle the review status of a prospect.
     */
    public function toggleReview(Request $request, Prospect $prospect)
    {
        $user = $request->user();

        // Ensure the prospect belongs to the user
        if ($prospect->accessCode->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $prospect->is_reviewed = !$prospect->is_reviewed;
        $prospect->save();

        return response()->json($prospect);
    }

    public function destroy(Request $request, Prospect $prospect)
    {
        $user = $request->user();

        // Ensure the prospect belongs to the user
        if ($prospect->accessCode->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $prospect->delete();

        return response()->json(['message' => 'Prospect deleted successfully']);
    }
}
