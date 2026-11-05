<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Counsellors & availability, appointments, leads (CRM), contact messages, applications & documents.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counsellors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('photo')->nullable();
            $table->string('designation')->nullable();
            $table->text('bio')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->unsignedTinyInteger('experience_years')->nullable();
            $table->json('languages')->nullable();
            $table->unsignedSmallInteger('slot_duration')->nullable(); // minutes; null = settings default
            $table->string('status', 20)->default('published')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('counsellor_service', function (Blueprint $table) {
            $table->foreignId('counsellor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->primary(['counsellor_id', 'service_id']);
        });

        Schema::create('counsellor_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counsellor_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 0 = Sunday … 6 = Saturday
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['counsellor_id', 'day_of_week']);
        });

        Schema::create('counsellor_time_offs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counsellor_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time')->nullable(); // null = whole day off
            $table->time('end_time')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();
            $table->index(['counsellor_id', 'date']);
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->index();
            $table->string('phone', 30)->nullable()->index();
            $table->string('source', 30)->index();
            $table->string('status', 20)->default('new')->index();
            $table->foreignId('destination_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('university_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->string('study_level', 30)->nullable();
            $table->text('message')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_contacted_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('created_at');
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('counsellor_id')->constrained()->restrictOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('destination_id')->nullable()->constrained()->nullOnDelete();
            $table->date('appointment_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('duration_minutes');
            $table->string('mode', 20)->default('in_person');
            $table->string('name');
            $table->string('email')->index();
            $table->string('phone', 30);
            $table->string('study_level', 30)->nullable();
            $table->text('message')->nullable();
            $table->string('status', 20)->default('pending')->index();
            // 1 while the appointment occupies its slot, NULL once cancelled / rescheduled away.
            // Combined with the unique index below this makes double booking impossible at DB level.
            $table->unsignedTinyInteger('slot_lock')->nullable();
            $table->foreignId('rescheduled_from_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->string('cancellation_reason')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('manage_token', 64)->nullable()->unique();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['counsellor_id', 'appointment_date', 'start_time', 'slot_lock'], 'appointments_slot_unique');
            $table->index(['appointment_date', 'status']);
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 30)->nullable();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->string('status', 20)->default('new')->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('destination_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('university_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->index();
            $table->string('phone', 30);
            $table->string('study_level', 30)->nullable();
            $table->text('message')->nullable();
            $table->string('status', 30)->default('new')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('created_at');
        });

        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('original_name');
            $table->string('path'); // stored on the private "documents" disk, never web-accessible
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'application_documents', 'applications', 'contact_messages', 'appointments', 'leads',
            'counsellor_time_offs', 'counsellor_availabilities', 'counsellor_service', 'counsellors',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
