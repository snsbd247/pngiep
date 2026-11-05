<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Destinations, universities, courses, services and their flexible detail-page sections.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destinations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('country_code', 2)->nullable();
            $table->string('flag_image')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('hero_image')->nullable();
            $table->string('tagline')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('overview')->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->decimal('tuition_fee_min', 12, 2)->nullable();
            $table->decimal('tuition_fee_max', 12, 2)->nullable();
            $table->decimal('living_cost_min', 12, 2)->nullable();
            $table->decimal('living_cost_max', 12, 2)->nullable();
            $table->json('intakes')->nullable();
            $table->json('quick_facts')->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->string('status', 20)->default('published')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('destination_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destination_id')->constrained()->cascadeOnDelete();
            $table->string('key', 100);
            $table->string('type', 50)->default('content');
            $table->string('title')->nullable();
            $table->text('subtitle')->nullable();
            $table->longText('content')->nullable();
            $table->string('image')->nullable();
            $table->json('data')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['destination_id', 'is_active', 'sort_order']);
        });

        Schema::create('universities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destination_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('type', 20)->default('public')->index();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->string('website')->nullable();
            $table->unsignedSmallInteger('established_year')->nullable();
            $table->unsignedInteger('world_ranking')->nullable()->index();
            $table->unsignedInteger('national_ranking')->nullable();
            $table->unsignedInteger('student_count')->nullable();
            $table->unsignedInteger('international_student_count')->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->decimal('tuition_fee_min', 12, 2)->nullable()->index();
            $table->decimal('tuition_fee_max', 12, 2)->nullable();
            $table->decimal('application_fee', 10, 2)->nullable();
            $table->json('intakes')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('about')->nullable();
            $table->json('gallery')->nullable();
            $table->string('video_url')->nullable();
            $table->string('youtube_id', 20)->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->string('status', 20)->default('published')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('university_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained()->cascadeOnDelete();
            $table->string('key', 100);
            $table->string('type', 50)->default('content');
            $table->string('title')->nullable();
            $table->text('subtitle')->nullable();
            $table->longText('content')->nullable();
            $table->string('image')->nullable();
            $table->json('data')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['university_id', 'is_active', 'sort_order']);
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained()->restrictOnDelete();
            // Denormalised from the university for fast country filtering; kept in sync by the model.
            $table->foreignId('destination_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('subject')->nullable()->index();
            $table->string('degree_type', 50)->nullable()->index();
            $table->string('study_level', 30)->index();
            $table->string('duration')->nullable();
            $table->unsignedSmallInteger('duration_months')->nullable()->index();
            $table->string('currency_code', 3)->nullable();
            $table->decimal('tuition_fee', 12, 2)->nullable()->index();
            $table->string('fee_period', 20)->default('year');
            $table->json('intakes')->nullable();
            $table->decimal('ielts_requirement', 3, 1)->nullable();
            $table->longText('entry_requirements')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->string('status', 20)->default('published')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['destination_id', 'study_level', 'status']);
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('icon', 50)->nullable();
            $table->string('image')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->json('features')->nullable();
            $table->string('cta_text')->nullable();
            $table->string('cta_url')->nullable();
            $table->boolean('is_bookable')->default(true);
            $table->boolean('is_featured')->default(false)->index();
            $table->string('status', 20)->default('published')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('service_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('key', 100);
            $table->string('type', 50)->default('content');
            $table->string('title')->nullable();
            $table->text('subtitle')->nullable();
            $table->longText('content')->nullable();
            $table->string('image')->nullable();
            $table->json('data')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['service_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        foreach (['service_sections', 'services', 'courses', 'university_sections', 'universities', 'destination_sections', 'destinations'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
