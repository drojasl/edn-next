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
        Schema::table('prospect_access_logs', function (Blueprint $table) {
            $table->foreignId('prospect_id')->nullable()->change();
            $table->string('session_id')->nullable()->after('access_code_id');
        });

        Schema::table('prospect_node_progress', function (Blueprint $table) {
            $table->foreignId('prospect_id')->nullable()->change();
            $table->foreignId('access_code_id')->nullable()->constrained()->onDelete('cascade')->after('prospect_id');
            $table->string('session_id')->nullable()->after('access_code_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prospect_access_logs', function (Blueprint $table) {
            $table->foreignId('prospect_id')->nullable(false)->change();
            $table->dropColumn('session_id');
        });

        Schema::table('prospect_node_progress', function (Blueprint $table) {
            $table->foreignId('prospect_id')->nullable(false)->change();
            $table->dropConstrainedForeignId('access_code_id');
            $table->dropColumn('session_id');
        });
    }
};
