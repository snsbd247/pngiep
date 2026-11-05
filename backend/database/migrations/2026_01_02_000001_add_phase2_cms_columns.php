<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 additions to the CMS tables (additive only).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hero_slides', function (Blueprint $table) {
            $table->string('video_url')->nullable()->after('secondary_button_url');
            $table->string('video_label')->nullable()->after('video_url');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->string('eyebrow')->nullable()->after('template');
            $table->text('excerpt')->nullable()->after('subtitle');
        });

        Schema::table('testimonials', function (Blueprint $table) {
            $table->string('country_code', 2)->nullable()->after('country');
        });

        Schema::table('timeline_items', function (Blueprint $table) {
            $table->string('icon', 60)->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('hero_slides', fn (Blueprint $t) => $t->dropColumn(['video_url', 'video_label']));
        Schema::table('pages', fn (Blueprint $t) => $t->dropColumn(['eyebrow', 'excerpt']));
        Schema::table('testimonials', fn (Blueprint $t) => $t->dropColumn('country_code'));
        Schema::table('timeline_items', fn (Blueprint $t) => $t->dropColumn('icon'));
    }
};
