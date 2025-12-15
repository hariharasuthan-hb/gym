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
        Schema::table('cms_contents', function (Blueprint $table) {
            $table->boolean('video_is_background')->default(false)->after('video_path');
            $table->string('background_video')->nullable()->after('video_is_background');
            $table->string('title_color', 7)->nullable()->after('background_video');
            $table->string('description_color', 7)->nullable()->after('title_color');
            $table->string('content_color', 7)->nullable()->after('description_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cms_contents', function (Blueprint $table) {
            $table->dropColumn(['video_is_background', 'background_video', 'title_color', 'description_color', 'content_color']);
        });
    }
};
