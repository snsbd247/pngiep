<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Profile pages for team members / directors at /team/{slug}: a detailed profile,
 * a message (speech) and key highlights. Existing members get a slug from their name.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->json('highlights')->nullable()->after('bio');
            $table->longText('profile')->nullable()->after('highlights');
            $table->string('message_title')->nullable()->after('profile');
            $table->longText('message')->nullable()->after('message_title');
        });

        $used = [];
        foreach (DB::table('team_members')->orderBy('id')->get(['id', 'name']) as $member) {
            $base = Str::slug($member->name) ?: 'member-'.$member->id;
            $slug = $base;
            for ($i = 2; in_array($slug, $used, true); $i++) {
                $slug = "{$base}-{$i}";
            }
            $used[] = $slug;
            DB::table('team_members')->where('id', $member->id)->update(['slug' => $slug]);
        }
    }

    public function down(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'highlights', 'profile', 'message_title', 'message']);
        });
    }
};
