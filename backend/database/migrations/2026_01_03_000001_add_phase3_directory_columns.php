<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 additions (additive only): section eyebrows for directory detail pages,
 * and a few directory attributes shown in the designs.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['destination_sections', 'university_sections', 'service_sections'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->string('eyebrow')->nullable()->after('type'));
        }

        Schema::table('destinations', function (Blueprint $table) {
            $table->string('capital')->nullable()->after('tagline');
            $table->string('language')->nullable()->after('capital');
            $table->string('post_study_work')->nullable()->after('living_cost_max');
            $table->string('video_url')->nullable()->after('quick_facts');
        });

        Schema::table('universities', function (Blueprint $table) {
            $table->string('campus_type', 30)->nullable()->after('city');
            $table->json('highlights')->nullable()->after('about');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->string('application_deadline')->nullable()->after('intakes');
            $table->json('highlights')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        foreach (['destination_sections', 'university_sections', 'service_sections'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn('eyebrow'));
        }
        Schema::table('destinations', fn (Blueprint $t) => $t->dropColumn(['capital', 'language', 'post_study_work', 'video_url']));
        Schema::table('universities', fn (Blueprint $t) => $t->dropColumn(['campus_type', 'highlights']));
        Schema::table('courses', fn (Blueprint $t) => $t->dropColumn(['application_deadline', 'highlights']));
    }
};
