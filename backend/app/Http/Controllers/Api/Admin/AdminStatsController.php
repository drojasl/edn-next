<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\AccessCode;
use App\Models\ProspectAccessLog;
use App\Models\ProspectNodeProgress;
use Illuminate\Support\Facades\DB;

class AdminStatsController extends Controller
{
    public function funnel(Request $request)
    {
        $user = $request->user();
        
        // Get all access codes of the current entrepreneur
        $accessCodes = AccessCode::where('user_id', $user->id)
            ->with(['course'])
            ->get();

        $stats = $accessCodes->map(function($code) {
            // Unique sessions that used this code
            $totalVisits = ProspectAccessLog::where('access_code_id', $code->id)
                ->distinct('session_id')
                ->count();

            // Total registered prospects for this code
            $totalProspects = ProspectAccessLog::where('access_code_id', $code->id)
                ->whereNotNull('prospect_id')
                ->distinct('prospect_id')
                ->count();

            // All unique nodes that have been viewed using this code, across any course
            $nodeViews = ProspectNodeProgress::where('access_code_id', $code->id)
                ->select('course_node_id', DB::raw('COUNT(DISTINCT session_id) as unique_views'), DB::raw('MIN(viewed_at) as first_seen'))
                ->with('node') // Ensure relations exist
                ->groupBy('course_node_id')
                ->orderBy('first_seen', 'asc') // This orders them by the logical sequence they were explored
                ->get();

            $nodesStats = $nodeViews->map(function($view) use ($totalVisits) {
                return [
                    'id' => $view->course_node_id,
                    'title' => $view->node ? $view->node->title : 'Nodo Desconocido',
                    'type' => $view->node ? $view->node->type : 'unknown',
                    'views' => $view->unique_views,
                    'dropoff' => $totalVisits > 0 ? round((1 - ($view->unique_views / $totalVisits)) * 100, 1) : 0
                ];
            });

            return [
                'id' => $code->id,
                'code' => $code->code,
                'course' => [
                    'id' => $code->course->id,
                    'title' => $code->course->title,
                    'slug' => $code->course->slug,
                ],
                'visits' => $totalVisits,
                'registered' => $totalProspects,
                'funnel' => $nodesStats
            ];
        });

        return response()->json($stats);
    }
}
