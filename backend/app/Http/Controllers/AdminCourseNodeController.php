<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseNode;
use App\Models\CourseNodeOption;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AdminCourseNodeController extends Controller
{
    /**
     * Display a listing of nodes for a specific course.
     */
    /**
     * Display a listing of nodes for a specific course.
     */
    public function index($courseId)
    {
        $course = $this->resolveCourse($courseId);

        // Include options to draw connections
        $nodes = $course->nodes()->with('options')->get();

        return response()->json([
            'success' => true,
            'data' => $nodes,
            'course' => $course
        ]);
    }

    /**
     * Store a newly created node.
     */
    public function store(Request $request, $courseId)
    {
        $course = $this->resolveCourse($courseId);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:' . implode(',', CourseNode::TYPES),
            'content' => 'nullable|array',
            'video_url' => 'nullable|string',
            'pos_x' => 'nullable|integer',
            'pos_y' => 'nullable|integer',
        ]);

        $validated['course_id'] = $course->id;
        $validated['slug'] = Str::slug($validated['title']) . '-' . uniqid(); // Ensure uniqueness

        $node = CourseNode::create($validated);

        return response()->json([
            'success' => true,
            'data' => $node
        ], 201);
    }

    /**
     * Update the specified node.
     */
    public function update(Request $request, $courseId, $nodeId)
    {
        // validation/auth check implicit in middleware usually, but here we ensure hierarchy
        $node = CourseNode::where('course_id', $courseId)->findOrFail($nodeId);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string',
            'content' => 'nullable|array',
            'video_url' => 'nullable|string',
            'is_start' => 'boolean',
            'is_end' => 'boolean',
        ]);

        if (isset($validated['title'])) {
            // Optional: update slug or keep original
        }

        $node->update($validated);

        return response()->json([
            'success' => true,
            'data' => $node
        ]);
    }

    /**
     * Remove the specified node.
     */
    public function destroy($courseId, $nodeId)
    {
        $node = CourseNode::where('course_id', $courseId)->findOrFail($nodeId);
        $node->delete();

        return response()->json([
            'success' => true,
            'message' => 'Node deleted successfully'
        ]);
    }

    /**
     * Update positions for multiple nodes.
     */
    public function updatePositions(Request $request, $courseId)
    {
        $course = $this->resolveCourse($courseId);

        $validated = $request->validate([
            'positions' => 'required|array',
            'positions.*.id' => 'required|exists:course_nodes,id',
            // Hardcoded limit matches frontend EDITOR_CONFIG.bounds
            'positions.*.pos_x' => 'required|numeric|min:0|max:5000',
            'positions.*.pos_y' => 'required|numeric|min:0|max:5000',
        ]);

        foreach ($validated['positions'] as $pos) {
            CourseNode::where('id', $pos['id'])
                ->where('course_id', $course->id)
                ->update([
                    'pos_x' => $pos['pos_x'],
                    'pos_y' => $pos['pos_y']
                ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Positions updated successfully'
        ]);
    }

    /**
     * Update connections between nodes.
     * Each connection is a record in course_node_options: source_node_id -> target_node_id
     */
    public function updateConnections(Request $request, $courseId)
    {
        $course = $this->resolveCourse($courseId);

        $validated = $request->validate([
            'connections' => 'required|array',
            'connections.*.source_node_id' => 'required|exists:course_nodes,id',
            'connections.*.target_node_id' => 'required|exists:course_nodes,id',
            'connections.*.label' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $course) {
            // Get all node IDs for this course
            $nodeIds = CourseNode::where('course_id', $course->id)->pluck('id');
            
            // Delete all existing connections for these nodes
            CourseNodeOption::whereIn('course_node_id', $nodeIds)->delete();
            
            // Create new connections - each edge is one record
            foreach ($validated['connections'] as $conn) {
                // Verify both nodes belong to this course
                $sourceNode = CourseNode::where('id', $conn['source_node_id'])
                    ->where('course_id', $course->id)
                    ->first();
                $targetNode = CourseNode::where('id', $conn['target_node_id'])
                    ->where('course_id', $course->id)
                    ->first();

                if ($sourceNode && $targetNode) {
                    CourseNodeOption::create([
                        'course_node_id' => $conn['source_node_id'],
                        'next_node_id' => $conn['target_node_id'],
                        'label' => $conn['label'] ?? 'Next'
                    ]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Node connections updated successfully'
        ]);
    }

    /**
     * Helper to resolve course by ID or Slug
     */
    private function resolveCourse($id)
    {
        return Course::where('id', is_numeric($id) ? $id : 0)
            ->orWhere('slug', $id)
            ->firstOrFail();
    }
}
