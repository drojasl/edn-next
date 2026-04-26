<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminCourseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $courses = $request->user()->courses()->with('nextCourse:id,title')->get();
        return response()->json([
            'success' => true,
            'data' => $courses
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'next_course_id' => 'nullable|exists:courses,id',
            'next_course_label' => 'nullable|string|max:255',
        ]);

        $validated['slug'] = Str::slug($validated['title']);
        // Ensure unique slug
        $count = Course::where('slug', 'LIKE', "{$validated['slug']}%")->count();
        if ($count > 0) {
            $validated['slug'] .= '-' . ($count + 1);
        }

        // Assign to current user (admin/entrepreneur)
        $validated['user_id'] = $request->user()->id;

        $course = Course::create($validated);

        return response()->json([
            'success' => true,
            'data' => $course
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $course = $request->user()->courses()->with('nodes')->findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $course
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $course = $request->user()->courses()->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'next_course_id' => 'nullable|exists:courses,id',
            'next_course_label' => 'nullable|string|max:255',
        ]);

        if (isset($validated['title'])) {
            // We keep the original slug to avoid breaking old URLs
        }

        $course->update($validated);

        return response()->json([
            'success' => true,
            'data' => $course
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $course = $request->user()->courses()->findOrFail($id);
        $course->delete();

        return response()->json([
            'success' => true,
            'message' => 'Course deleted successfully'
        ]);
    }

    /**
     * Update connections (next_course_id) for multiple courses.
     */
    public function updateConnections(Request $request)
    {
        $validated = $request->validate([
            'connections' => 'required|array',
            'connections.*.id' => 'required|exists:courses,id',
            'connections.*.next_course_id' => 'nullable|exists:courses,id',
            'connections.*.next_course_label' => 'nullable|string|max:255',
        ]);

        foreach ($validated['connections'] as $connection) {
            $request->user()->courses()->where('id', $connection['id'])->update([
                'next_course_id' => $connection['next_course_id'],
                'next_course_label' => $connection['next_course_label'] ?? null
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Connections updated successfully'
        ]);
    }

    public function updatePositions(Request $request)
    {
        $validated = $request->validate([
            'positions' => 'required|array',
            'positions.*.id' => 'required|exists:courses,id',
            'positions.*.pos_x' => 'required|numeric',
            'positions.*.pos_y' => 'required|numeric',
        ]);

        foreach ($validated['positions'] as $pos) {
            $request->user()->courses()->where('id', $pos['id'])->update([
                'pos_x' => $pos['pos_x'],
                'pos_y' => $pos['pos_y']
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Positions updated successfully'
        ]);
    }

    public function export(Request $request, string $id)
    {
        $course = $request->user()->courses()->with(['nodes.options'])->findOrFail($id);

        $exportData = [
            'version' => '1.0',
            'course' => [
                'title' => $course->title,
                'slug' => $course->slug,
                'description' => $course->description,
                'is_active' => $course->is_active,
                'pos_x' => $course->pos_x,
                'pos_y' => $course->pos_y,
            ],
            'nodes' => $course->nodes->map(function ($node) {
                return [
                    'original_id' => $node->id,
                    'type' => $node->type,
                    'title' => $node->title,
                    'slug' => $node->slug,
                    'position' => $node->position,
                    'content' => $node->content,
                    'video_url' => $node->video_url,
                    'playback_speed' => $node->playback_speed,
                    'meeting_link' => $node->meeting_link,
                    'show_description' => $node->show_description,
                    'pos_x' => $node->pos_x,
                    'pos_y' => $node->pos_y,
                    'is_start' => $node->is_start,
                    'is_end' => $node->is_end,
                    'options' => $node->options->map(function ($option) {
                        return [
                            'label' => $option->label,
                            'next_node_original_id' => $option->next_node_id,
                        ];
                    })->toArray()
                ];
            })->toArray()
        ];

        return response()->json([
            'success' => true,
            'data' => $exportData
        ]);
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file'
        ]);

        $content = file_get_contents($request->file('file')->getRealPath());
        $data = json_decode($content, true);

        if (!$data || !isset($data['version']) || !isset($data['course']) || !isset($data['nodes'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid course export file.'
            ], 400);
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $slug = $data['course']['slug'];
            $originalSlug = $slug;
            $count = 1;
            // Now we only check for uniqueness within the current user's courses
            while (\App\Models\Course::where('user_id', $request->user()->id)->where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $count;
                $count++;
            }
            
            $course = Course::create([
                'user_id' => $request->user()->id,
                'title' => $data['course']['title'],
                'slug' => $slug,
                'description' => $data['course']['description'],
                'is_active' => $data['course']['is_active'] ?? true,
                'pos_x' => $data['course']['pos_x'] ?? 0,
                'pos_y' => $data['course']['pos_y'] ?? 0,
                'next_course_id' => null,
                'next_course_label' => null,
            ]);

            $idMap = [];
            $nodesWithOptions = [];

            foreach ($data['nodes'] as $nodeData) {
                $node = $course->nodes()->create([
                    'type' => $nodeData['type'],
                    'title' => $nodeData['title'],
                    'slug' => $nodeData['slug'] . '-' . uniqid(),
                    'position' => $nodeData['position'] ?? 0,
                    'content' => $nodeData['content'] ?? null,
                    'video_url' => $nodeData['video_url'] ?? null,
                    'playback_speed' => $nodeData['playback_speed'] ?? 1.0,
                    'meeting_link' => $nodeData['meeting_link'] ?? null,
                    'show_description' => $nodeData['show_description'] ?? false,
                    'pos_x' => $nodeData['pos_x'] ?? 0,
                    'pos_y' => $nodeData['pos_y'] ?? 0,
                    'is_start' => $nodeData['is_start'] ?? false,
                    'is_end' => $nodeData['is_end'] ?? false,
                ]);

                if (isset($nodeData['original_id'])) {
                    $idMap[$nodeData['original_id']] = $node->id;
                }
                
                $nodeData['new_id'] = $node->id;
                $nodesWithOptions[] = $nodeData;
            }

            if (!empty($nodesWithOptions)) {
                foreach ($nodesWithOptions as $nodeData) {
                    if (!empty($nodeData['options'])) {
                        foreach ($nodeData['options'] as $opt) {
                            $nextNodeId = null;
                            if (!empty($opt['next_node_original_id']) && isset($idMap[$opt['next_node_original_id']])) {
                                $nextNodeId = $idMap[$opt['next_node_original_id']];
                            }

                            \App\Models\CourseNodeOption::create([
                                'course_node_id' => $nodeData['new_id'],
                                'label' => $opt['label'],
                                'next_node_id' => $nextNodeId,
                            ]);
                        }
                    }
                }
            }

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Course imported successfully.',
                'data' => $course
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Import error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error importing course.'
            ], 500);
        }
    }
}
