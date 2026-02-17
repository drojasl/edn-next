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
        Schema::table('user_social_links', function (Blueprint $table) {
            // Remove old columns
            $table->dropColumn(['whatsapp', 'instagram', 'facebook', 'linkedin', 'email_contact', 'website']);

            // Add new EAV columns
            $table->string('platform')->after('user_id');
            $table->string('value')->after('platform');

            // Scalability index
            $table->unique(['user_id', 'platform']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_social_links', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'platform']);
            $table->dropColumn(['platform', 'value']);

            $table->string('whatsapp')->nullable();
            $table->string('instagram')->nullable();
            $table->string('facebook')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('email_contact')->nullable();
            $table->string('website')->nullable();
        });
    }
};
