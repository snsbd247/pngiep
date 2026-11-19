<?php

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Staff mailbox (cPanel) linked to an admin user, plus the webmail.access and
 * mailboxes.manage permissions for the default roles. Additive only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mailbox')->nullable()->unique()->after('email');
            $table->timestamp('mailbox_linked_at')->nullable()->after('mailbox');
            $table->boolean('mailbox_created')->default(false)->after('mailbox_linked_at');
        });

        (new RolePermissionSeeder)->run();
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['mailbox']);
            $table->dropColumn(['mailbox', 'mailbox_linked_at', 'mailbox_created']);
        });
    }
};
