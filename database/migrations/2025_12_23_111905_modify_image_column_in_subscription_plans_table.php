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
        Schema::table('subscription_plans', function (Blueprint $table) {
            // Check if image column exists
            if (!Schema::hasColumn('subscription_plans', 'image')) {
                // Add the column if it doesn't exist
                $table->text('image')->nullable()->after('description');
            } else {
                // Modify existing column to TEXT type
                $table->text('image')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            // Revert back to string type if column exists
            if (Schema::hasColumn('subscription_plans', 'image')) {
                $table->string('image')->nullable()->change();
            }
        });
    }
};
