<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\AccessCode;
use App\Models\ProspectAccessLog;
use App\Models\ProspectNodeProgress;
use App\Models\Course;
use App\Models\CourseNode;
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

        $stats = $accessCodes->map(function($code) use ($user) {
            // Unique sessions that used this code
            $totalVisits = ProspectAccessLog::where('access_code_id', $code->id)
                ->distinct('session_id')
                ->count();

            // Total registered prospects for this code
            $totalProspects = ProspectAccessLog::where('access_code_id', $code->id)
                ->whereNotNull('prospect_id')
                ->distinct('prospect_id')
                ->count();

            // All nodes in logical order starting from the course sequence
            $courseSequence = collect([$code->course]);
            $currentCourse = $code->course;
            $visitedCourseIds = [$currentCourse->id];
            
            while ($currentCourse->next_course_id) {
                if (in_array($currentCourse->next_course_id, $visitedCourseIds)) break;
                
                $nextCourse = $user->courses()->find($currentCourse->next_course_id);
                if ($nextCourse) {
                    $courseSequence->push($nextCourse);
                    $visitedCourseIds[] = $nextCourse->id;
                    $currentCourse = $nextCourse;
                } else {
                    break;
                }
            }

            $allNodes = collect();
            foreach ($courseSequence as $course) {
                $nodes = CourseNode::where('course_id', $course->id)
                    ->orderBy('pos_x', 'asc')
                    ->orderBy('pos_y', 'asc')
                    ->get();
                $allNodes = $allNodes->concat($nodes);
            }

            // Get views for all nodes at once
            $viewCounts = ProspectNodeProgress::where('access_code_id', $code->id)
                ->whereIn('course_node_id', $allNodes->pluck('id'))
                ->select('course_node_id', DB::raw('COUNT(DISTINCT session_id) as unique_views'))
                ->groupBy('course_node_id')
                ->pluck('unique_views', 'course_node_id');

            $nodesStats = $allNodes->map(function($node) use ($totalVisits, $viewCounts) {
                $views = $viewCounts->get($node->id, 0);
                return [
                    'id' => $node->id,
                    'title' => $node->title,
                    'type' => $node->type,
                    'views' => $views,
                    'dropoff' => $totalVisits > 0 ? round((1 - ($views / $totalVisits)) * 100, 1) : 0
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
