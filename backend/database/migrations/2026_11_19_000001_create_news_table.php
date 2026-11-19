<?php

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * News module: its own table, the "news.manage" permission for the default roles,
 * and the /news list page (CMS page with template "listing" that holds the hero and
 * the sections around the list). Additive only, so it is safe on a live database.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_name')->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('featured_image')->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('source_name', 120)->nullable();
            $table->string('source_url', 500)->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->string('status', 20)->default('draft')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->unsignedInteger('views_count')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'published_at']);
        });

        // Adds news.manage and grants it to the default roles (existing grants are kept).
        (new RolePermissionSeeder)->run();

        if (! DB::table('pages')->where('slug', 'news')->exists()) {
            $now = now();
            $banner = DB::table('pages')->where('slug', 'blog')->value('banner_image');

            $pageId = DB::table('pages')->insertGetId([
                'title' => 'Latest *News*', 'slug' => 'news', 'template' => 'listing',
                'excerpt' => 'Announcements, events, university updates and visa news from PNG International Education Portal.',
                'banner_image' => $banner, 'status' => 'published', 'published_at' => $now,
                'created_at' => $now, 'updated_at' => $now,
            ]);

            $sections = [
                ['type' => 'listing', 'data' => []],
                ['type' => 'sidebar_card', 'title' => 'Never Miss an Update', 'subtitle' => 'Subscribe to get the latest news and study abroad updates directly to your inbox.',
                    'data' => ['kind' => 'newsletter', 'icon' => 'mail-check', 'button_text' => 'Subscribe']],
                ['type' => 'sidebar_card', 'title' => 'Need Expert Guidance?', 'subtitle' => 'Talk to our education counsellors and get personalised advice for your study abroad journey.',
                    'data' => ['kind' => 'link', 'button_text' => 'Book a Free Consultation', 'button_url' => '/book-appointment']],
            ];

            foreach ($sections as $i => $section) {
                DB::table('page_sections')->insert([
                    'page_id' => $pageId, 'key' => $section['type'].'-'.($i + 1), 'type' => $section['type'],
                    'title' => $section['title'] ?? null, 'subtitle' => $section['subtitle'] ?? null,
                    'data' => json_encode($section['data']), 'sort_order' => $i, 'is_active' => true,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
