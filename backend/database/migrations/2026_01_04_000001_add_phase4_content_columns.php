<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 additions (additive only): story outcome & public submissions,
 * category icons for tabs, video durations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('success_stories', function (Blueprint $table) {
            $table->string('outcome', 60)->nullable()->after('year');
            // Set when a visitor submits their story from the website (saved as a draft for review).
            $table->string('submitter_email')->nullable()->after('youtube_id');
            $table->index(['status', 'destination_id']);
        });

        Schema::table('gallery_categories', fn (Blueprint $t) => $t->string('icon', 60)->nullable()->after('type'));
        Schema::table('blog_categories', fn (Blueprint $t) => $t->string('icon', 60)->nullable()->after('type'));
        Schema::table('gallery_videos', fn (Blueprint $t) => $t->string('duration', 10)->nullable()->after('thumbnail'));

        Schema::table('blogs', function (Blueprint $table) {
            $table->index(['status', 'published_at']);
            $table->index('views_count');
        });
    }

    public function down(): void
    {
        Schema::table('success_stories', function (Blueprint $table) {
            $table->dropIndex(['status', 'destination_id']);
            $table->dropColumn(['outcome', 'submitter_email']);
        });
        Schema::table('gallery_categories', fn (Blueprint $t) => $t->dropColumn('icon'));
        Schema::table('blog_categories', fn (Blueprint $t) => $t->dropColumn('icon'));
        Schema::table('gallery_videos', fn (Blueprint $t) => $t->dropColumn('duration'));
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropIndex(['status', 'published_at']);
            $table->dropIndex(['views_count']);
        });
    }
};
