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
    public function index()
    {
        $courses = Course::with('nextCourse:id,title')->get();
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
    public function show(string $id)
    {
        $course = Course::with('nodes')->findOrFail($id);
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
        $course = Course::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'next_course_id' => 'nullable|exists:courses,id',
        ]);

        if (isset($validated['title'])) {
            $validated['slug'] = Str::slug($validated['title']);
            // Ensure unique slug logic if title changed (omitted for brevity, assume simple update for now)
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
    public function destroy(string $id)
    {
        $course = Course::findOrFail($id);
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
        ]);

        foreach ($validated['connections'] as $connection) {
            Course::where('id', $connection['id'])->update(['next_course_id' => $connection['next_course_id']]);
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
            Course::where('id', $pos['id'])->update([
                'pos_x' => $pos['pos_x'],
                'pos_y' => $pos['pos_y']
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Positions updated successfully'
        ]);
    }
}
