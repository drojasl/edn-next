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
            $table->string('meeting_link')->nullable()->after('video_url');
            $table->boolean('show_description')->default(true)->after('meeting_link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_nodes', function (Blueprint $table) {
            $table->dropColumn(['meeting_link', 'show_description']);
        });
    }
};
