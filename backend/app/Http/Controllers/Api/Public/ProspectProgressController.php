<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\AccessCode;
use App\Models\Prospect;
use App\Models\ProspectAccessLog;
use App\Models\ProspectNodeProgress;
use App\Models\ProspectCourseProgress;
use App\Models\CourseNode;
use App\Models\Course;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProspectProgressController extends Controller
{
    /**
     * Sincroniza el progreso del historial de localStorage a la base de datos.
     * Se dispara cuando el prospecto deja su correo.
     */
    public function sync(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:100',
            'name' => 'nullable|string|min:5|max:100',
            'phone' => 'nullable|string|min:5|max:25',
            'amway_code' => 'nullable|string|min:8|max:15',
            'city' => 'nullable|string|min:3|max:50',
            'country' => 'nullable|string|min:5|max:50',
            'accept_terms' => 'nullable|boolean',
            'code' => 'required|string',
            'progress' => 'nullable|array',
            'completed' => 'nullable|array',
            'session_id' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            $accessCode = AccessCode::where('code', $validated['code'])->firstOrFail();

            // Actualizar o crear prospecto con todos los datos disponibles
            $prospect = Prospect::updateOrCreate(
                ['email' => $validated['email']],
                array_filter([
                    'name' => $validated['name'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'amway_code' => $validated['amway_code'] ?? null,
                    'city' => $validated['city'] ?? null,
                    'country' => $validated['country'] ?? null,
                    'access_code_id' => $accessCode->id,
                ])
            );

            // Vincular prospecto con el empresario (access_log)
            $accessLog = ProspectAccessLog::firstOrCreate([
                'prospect_id' => $prospect->id,
                'access_code_id' => $accessCode->id,
            ], [
                'activated_at' => now(),
                'session_id' => $validated['session_id'] ?? null,
            ]);

            // Si vino con session_id, vincular todos los logs huérfanos de esa sesión a este prospecto
            if (!empty($validated['session_id'])) {
                ProspectAccessLog::where('session_id', $validated['session_id'])
                    ->whereNull('prospect_id')
                    ->update(['prospect_id' => $prospect->id]);

                ProspectNodeProgress::where('session_id', $validated['session_id'])
                    ->whereNull('prospect_id')
                    ->update(['prospect_id' => $prospect->id]);
            }

            // Sincronizar nodos vistos (solo si hay progreso)
            foreach (($validated['progress'] ?? []) as $courseSlug => $maxPos) {
                $course = Course::where('slug', $courseSlug)->first();
                if (!$course) continue;

                $nodes = CourseNode::where('course_id', $course->id)
                    ->where('position', '<=', $maxPos)
                    ->get();

                foreach ($nodes as $node) {
                    ProspectNodeProgress::firstOrCreate([
                        'prospect_id' => $prospect->id,
                        'course_node_id' => $node->id,
                    ], [
                        'viewed_at' => now(),
                    ]);
                }
            }

            // Sincronizar cursos completados
            if (isset($validated['completed'])) {
                foreach ($validated['completed'] as $courseSlug) {
                    $course = Course::where('slug', $courseSlug)->first();
                    if ($course) {
                        ProspectCourseProgress::firstOrCreate([
                            'prospect_id' => $prospect->id,
                            'course_id' => $course->id,
                        ], [
                            'started_at' => now(),
                            'completed_at' => now(),
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Progress synced successfully',
                'prospect_id' => $prospect->id
            ]);
        });
    }

    /**
     * Recupera el progreso de un prospecto basándose en su email y el código.
     */
    public function recover(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'code' => 'required|string',
        ]);

        $accessCode = AccessCode::where('code', $validated['code'])->first();

        if (!$accessCode) {
            return response()->json(['message' => 'Invalid access code'], 400);
        }

        $prospect = Prospect::where('email', $validated['email'])->first();

        if (!$prospect) {
            return response()->json(['message' => 'No records found for this email'], 404);
        }

        // Recuperar progreso granular filtrado por el empresario del código de acceso
        $nodeProgress = ProspectNodeProgress::where('prospect_id', $prospect->id)
            ->whereHas('node.course', function ($q) use ($accessCode) {
                $q->where('user_id', $accessCode->user_id);
            })
            ->with(['node' => function ($q) {
                $q->with('course');
            }])
            ->get();

        $progressMap = [];
        $history = [];

        foreach ($nodeProgress as $p) {
            if (!$p->node || !$p->node->course) continue;
            $courseSlug = $p->node->course->slug;

            if (!isset($progressMap[$courseSlug]) || $p->node->position > $progressMap[$courseSlug]) {
                $progressMap[$courseSlug] = (int)$p->node->position;
            }

            if (!in_array($courseSlug, $history)) {
                $history[] = $courseSlug;
            }
        }

        // Recuperar cursos completados filtrados por el empresario
        $completed = ProspectCourseProgress::where('prospect_id', $prospect->id)
            ->whereHas('course', function ($q) use ($accessCode) {
                $q->where('user_id', $accessCode->user_id);
            })
            ->with('course:id,slug')
            ->get()
            ->pluck('course.slug');
        return response()->json([
            'success' => true,
            'progress' => $progressMap,
            'completed' => $completed,
            'history' => $history
        ]);
    }

    /**
     * Track a node view (real-time).
     */
    public function trackNode(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'node_id' => 'required|exists:course_nodes,id',
            'session_id' => 'required|string',
            'email' => 'nullable|email', // Optional, if they already registered in this session
        ]);

        $accessCode = AccessCode::where('code', $validated['code'])->firstOrFail();
        
        $prospectId = null;
        if (!empty($validated['email'])) {
            $prospectId = Prospect::where('email', $validated['email'])->value('id');
        }

        // Record the progress
        $progress = ProspectNodeProgress::firstOrCreate([
            'prospect_id' => $prospectId,
            'course_node_id' => $validated['node_id'],
            'access_code_id' => $accessCode->id,
            'session_id' => $validated['session_id'],
        ], [
            'viewed_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }
}
