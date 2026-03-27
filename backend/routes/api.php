<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\LoginController;

use App\Http\Controllers\Api\Public\CourseController;

// Empresarios authentication
Route::post('/auth/login', [LoginController::class, 'login']);
Route::post('/auth/validate-slug', [\App\Http\Controllers\Api\Admin\EntrepreneurController::class, 'validateSlug']);

// Public Course API (for Prospects)
Route::prefix('v1/public')->group(function () {
    Route::post('/access-codes/validate', [App\Http\Controllers\Api\Public\PublicAccessController::class, 'validateCode']);
    Route::get('/courses/{entrepreneurSlug}/{courseSlug}', [CourseController::class, 'show']);

    // Prospect Progress
    Route::post('/prospect/sync', [App\Http\Controllers\Api\Public\ProspectProgressController::class, 'sync']);
    Route::post('/prospect/recover', [App\Http\Controllers\Api\Public\ProspectProgressController::class, 'recover']);
});

// Legacy routes (if needed)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user()->load('socialLinks');
    });

    // Admin API
    Route::prefix('v1/admin')->group(function () {
        // Courses
        Route::apiResource('courses', \App\Http\Controllers\AdminCourseController::class);
        Route::post('courses/update-connections', [\App\Http\Controllers\AdminCourseController::class, 'updateConnections']);
        Route::post('courses/update-positions', [\App\Http\Controllers\AdminCourseController::class, 'updatePositions']);

        // Course Nodes
        Route::get('courses/{course}/nodes', [\App\Http\Controllers\AdminCourseNodeController::class, 'index']);
        Route::post('courses/{course}/nodes', [\App\Http\Controllers\AdminCourseNodeController::class, 'store']);
        Route::put('courses/{course}/nodes/{node}', [\App\Http\Controllers\AdminCourseNodeController::class, 'update']);
        Route::delete('courses/{course}/nodes/{node}', [\App\Http\Controllers\AdminCourseNodeController::class, 'destroy']);
        Route::post('courses/{course}/nodes/update-positions', [\App\Http\Controllers\AdminCourseNodeController::class, 'updatePositions']);
        Route::post('courses/{course}/nodes/update-connections', [\App\Http\Controllers\AdminCourseNodeController::class, 'updateConnections']);

        // Entrepreneurs
        Route::apiResource('entrepreneurs', \App\Http\Controllers\Api\Admin\EntrepreneurController::class);
        Route::post('entrepreneurs/validate-slug', [\App\Http\Controllers\Api\Admin\EntrepreneurController::class, 'validateSlug']);

        // Access Codes
        Route::post('access-codes/validate-code', [\App\Http\Controllers\Api\Admin\AccessCodeController::class, 'validateCode']);
        Route::apiResource('access-codes', \App\Http\Controllers\Api\Admin\AccessCodeController::class)->except(['show', 'update']);

        // Prospects
        Route::get('prospects', [\App\Http\Controllers\Api\Admin\ProspectController::class, 'index']);
        Route::patch('prospects/{prospect}/toggle-review', [\App\Http\Controllers\Api\Admin\ProspectController::class, 'toggleReview']);
    });
});
