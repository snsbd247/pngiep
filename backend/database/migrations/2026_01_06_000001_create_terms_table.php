<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-managed lists (taxonomies): page / destination / story / partner / FAQ categories,
 * degree types and intakes. Pages, destinations and success stories reference a term by id;
 * partners, FAQs and courses keep the term's name in their existing text columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->string('taxonomy', 40)->index();
            $table->string('name', 120);
            $table->string('slug', 140);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['taxonomy', 'slug']);
        });

        Schema::table('pages', fn (Blueprint $t) => $t->foreignId('page_category_id')->nullable()->after('template')->constrained('terms')->nullOnDelete());
        Schema::table('destinations', fn (Blueprint $t) => $t->foreignId('destination_category_id')->nullable()->after('country_code')->constrained('terms')->nullOnDelete());
        Schema::table('success_stories', fn (Blueprint $t) => $t->foreignId('story_category_id')->nullable()->after('university_id')->constrained('terms')->nullOnDelete());
    }

    public function down(): void
    {
        Schema::table('success_stories', fn (Blueprint $t) => $t->dropConstrainedForeignId('story_category_id'));
        Schema::table('destinations', fn (Blueprint $t) => $t->dropConstrainedForeignId('destination_category_id'));
        Schema::table('pages', fn (Blueprint $t) => $t->dropConstrainedForeignId('page_category_id'));
        Schema::dropIfExists('terms');
    }
};
