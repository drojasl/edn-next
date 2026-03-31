<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('course_nodes', function (Blueprint $table) {
            $table->decimal('playback_speed', 3, 2)->default(1.0)->after('video_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_nodes', function (Blueprint $table) {
            $table->dropColumn('playback_speed');
        });
    }
};
