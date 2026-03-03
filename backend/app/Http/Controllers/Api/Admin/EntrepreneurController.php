<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Entrepreneur;
use App\Services\EntrepreneurService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EntrepreneurController extends Controller
{
    protected EntrepreneurService $entrepreneurService;

    public function __construct(EntrepreneurService $entrepreneurService)
    {
        $this->entrepreneurService = $entrepreneurService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Entrepreneur::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'codigo_amway' => 'required|string|max:20',
            'is_account_holder' => 'required|boolean',
            'slug' => 'required|string|max:255|unique:users',
        ]);

        $entrepreneur = $this->entrepreneurService->create($validated);

        return response()->json($entrepreneur, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Entrepreneur $entrepreneur)
    {
        return response()->json($entrepreneur);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Entrepreneur $entrepreneur)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($entrepreneur->id)],
            'password' => 'nullable|string|min:8',
            'codigo_amway' => 'required|string|max:20',
            'is_account_holder' => 'required|boolean',
            'slug' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($entrepreneur->id)],
            'is_active' => 'required|boolean',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $entrepreneur = $this->entrepreneurService->update(
            $entrepreneur,
            $validated,
            $request->file('profile_picture')
        );

        return response()->json($entrepreneur);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Entrepreneur $entrepreneur)
    {
        $this->entrepreneurService->delete($entrepreneur);
        return response()->json(['message' => 'Entrepreneur deleted successfully']);
    }

    /**
     * Validate if a slug is available.
     */
    public function validateSlug(Request $request)
    {
        $request->validate([
            'slug' => 'required|string',
            'exclude_id' => 'nullable|integer'
        ]);

        $query = Entrepreneur::where('slug', $request->slug);

        if ($request->exclude_id) {
            $query->where('id', '!=', $request->exclude_id);
        }

        $exists = $query->exists();

        return response()->json([
            'available' => !$exists
        ]);
    }
}
