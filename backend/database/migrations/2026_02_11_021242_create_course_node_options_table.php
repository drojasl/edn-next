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
        Schema::create('course_node_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_node_id')->constrained()->onDelete('cascade');
            $table->string('label');
            $table->foreignId('next_node_id')->nullable()->constrained('course_nodes')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_node_options');
    }
};
