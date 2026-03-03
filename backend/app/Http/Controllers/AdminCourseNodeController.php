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
            'data' => $this->formatNodes($nodes),
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
            'position' => 'nullable|integer',
            'pos_x' => 'nullable|integer',
            'pos_y' => 'nullable|integer',
        ]);

        $validated['course_id'] = $course->id;
        $slug = Str::slug($validated['title']);

        // Ensure unique slug within the course
        $count = CourseNode::where('course_id', $course->id)
            ->where('slug', 'LIKE', "{$slug}%")
            ->count();

        $validated['slug'] = $count > 0 ? "{$slug}-" . ($count + 1) : $slug;

        $node = CourseNode::create($validated);

        // Auto-validate start/end status
        $this->revalidateNodesStatus($course->id);
        $node->refresh();

        return response()->json([
            'success' => true,
            'data' => $this->formatNode($node)
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
            'position' => 'nullable|integer',
            'is_start' => 'boolean',
            'is_end' => 'boolean',
        ]);

        if (isset($validated['title'])) {
            // Optional: update slug or keep original
        }

        $node->update($validated);

        // Auto-validate start/end status
        $this->revalidateNodesStatus($courseId);
        $node->refresh();

        return response()->json([
            'success' => true,
            'data' => $this->formatNode($node)
        ]);
    }

    /**
     * Remove the specified node.
     */
    public function destroy($courseId, $nodeId)
    {
        $node = CourseNode::where('course_id', $courseId)->findOrFail($nodeId);
        $node->delete();

        // Auto-validate start/end status for remaining nodes
        $this->revalidateNodesStatus($courseId);

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

            // Auto-validate start/end status
            $this->revalidateNodesStatus($course->id);
        });

        // Return updated nodes for the course
        $nodes = CourseNode::where('course_id', $course->id)->with('options')->get();

        return response()->json([
            'success' => true,
            'message' => 'Node connections updated successfully',
            'data' => $this->formatNodes($nodes)
        ]);
    }

    /**
     * Revalidate is_start and is_end status for all nodes in a course.
     */
    private function revalidateNodesStatus($courseId)
    {
        $course = $this->resolveCourse($courseId);
        $nodes = CourseNode::where('course_id', $course->id)->get();
        $nodeIds = $nodes->pluck('id')->toArray();

        // Nodes that have incoming connections (targets)
        $nodesWithIncoming = CourseNodeOption::whereIn('next_node_id', $nodeIds)
            ->whereNotNull('next_node_id')
            ->distinct()
            ->pluck('next_node_id')
            ->toArray();

        // Nodes that have outgoing connections (sources)
        $nodesWithOutgoing = CourseNodeOption::whereIn('course_node_id', $nodeIds)
            ->whereNotNull('next_node_id')
            ->distinct()
            ->pluck('course_node_id')
            ->toArray();

        foreach ($nodes as $node) {
            $isStart = !in_array($node->id, $nodesWithIncoming);
            $isEnd = !in_array($node->id, $nodesWithOutgoing);

            // Only update if changed to avoid unnecessary queries
            if ($node->is_start !== $isStart || $node->is_end !== $isEnd) {
                $node->update([
                    'is_start' => $isStart,
                    'is_end' => $isEnd
                ]);
            }
        }
    }

    /**
     * Format nodes for frontend consistency.
     */
    private function formatNodes($nodes)
    {
        return $nodes->map(fn($node) => $this->formatNode($node));
    }

    /**
     * Format a single node for frontend consistency.
     */
    private function formatNode($node)
    {
        return [
            'id' => $node->id,
            'course_id' => $node->course_id,
            'type' => $node->type,
            'title' => $node->title,
            'slug' => $node->slug,
            'content' => $node->content,
            'video_url' => $node->video_url,
            'position' => (int) $node->position,
            'pos_x' => (int) $node->pos_x,
            'pos_y' => (int) $node->pos_y,
            'is_start' => (bool) $node->is_start,
            'is_end' => (bool) $node->is_end,
            'options' => $node->options
        ];
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
