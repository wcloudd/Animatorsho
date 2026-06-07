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
        Schema::table('course_packages', function (Blueprint $table) {
            $table->json('spotplayer_course_ids')->nullable()->after('display_order');
            $table->string('spotplayer_access_limit')->nullable()->after('spotplayer_course_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_packages', function (Blueprint $table) {
            $table->dropColumn([
                'spotplayer_course_ids',
                'spotplayer_access_limit',
            ]);
        });
    }
};
