<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ept_schedule_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ept_registration_id')->constrained('ept_registrations')->cascadeOnDelete();
            $table->foreignId('ept_group_id')->constrained('ept_groups')->cascadeOnDelete();
            $table->unsignedTinyInteger('test_number');
            $table->string('content_signature', 64)->nullable();
            $table->timestamp('last_requested_at')->nullable();

            $table->string('dashboard_status', 20)->nullable();
            $table->timestamp('dashboard_sent_at')->nullable();
            $table->timestamp('dashboard_failed_at')->nullable();
            $table->text('dashboard_error')->nullable();

            $table->string('mail_status', 20)->nullable();
            $table->timestamp('mail_queued_at')->nullable();
            $table->timestamp('mail_sent_at')->nullable();
            $table->timestamp('mail_failed_at')->nullable();
            $table->text('mail_error')->nullable();

            $table->string('whatsapp_status', 20)->nullable();
            $table->timestamp('whatsapp_queued_at')->nullable();
            $table->timestamp('whatsapp_sent_at')->nullable();
            $table->timestamp('whatsapp_failed_at')->nullable();
            $table->text('whatsapp_error')->nullable();

            $table->timestamps();

            $table->unique(
                ['ept_registration_id', 'ept_group_id', 'test_number'],
                'ept_schedule_notifications_unique'
            );
            $table->index(['ept_group_id', 'dashboard_status']);
            $table->index(['ept_group_id', 'mail_status']);
            $table->index(['ept_group_id', 'whatsapp_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ept_schedule_notifications');
    }
};
