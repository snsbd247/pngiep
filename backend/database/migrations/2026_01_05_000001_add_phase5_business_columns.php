<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 additions (additive only).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->string('inquiry_type', 100)->nullable()->after('phone');
            $table->foreignId('destination_id')->nullable()->after('inquiry_type')->constrained()->nullOnDelete();
            $table->text('reply')->nullable()->after('message');
            $table->foreignId('replied_by')->nullable()->after('replied_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->string('intake', 60)->nullable()->after('study_level');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->index(['counsellor_id', 'appointment_date']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->index(['email', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('destination_id');
            $table->dropConstrainedForeignId('replied_by');
            $table->dropColumn(['inquiry_type', 'reply']);
        });
        Schema::table('applications', fn (Blueprint $t) => $t->dropColumn('intake'));
        Schema::table('appointments', fn (Blueprint $t) => $t->dropIndex(['counsellor_id', 'appointment_date']));
        Schema::table('leads', fn (Blueprint $t) => $t->dropIndex(['email', 'status']));
    }
};
