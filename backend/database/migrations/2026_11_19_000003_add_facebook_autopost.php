<?php

use Database\Seeders\SettingSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Facebook Page auto-posting for blog posts and news: per-entry opt-in plus the result
 * (post id / error). Entries that already exist are NOT auto-posted (facebook_post = false);
 * they can still be posted by hand from the admin list. Adds the Settings › Facebook group.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['blogs', 'news'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->boolean('facebook_post')->default(true);
                $t->string('facebook_post_id', 100)->nullable();
                $t->timestamp('facebook_posted_at')->nullable();
                $t->string('facebook_error', 500)->nullable();
                $t->index(['facebook_post', 'facebook_post_id']);
            });
            DB::table($table)->update(['facebook_post' => false]);
        }

        (new SettingSeeder)->run();
    }

    public function down(): void
    {
        foreach (['blogs', 'news'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropIndex(['facebook_post', 'facebook_post_id']);
                $t->dropColumn(['facebook_post', 'facebook_post_id', 'facebook_posted_at', 'facebook_error']);
            });
        }
    }
};
