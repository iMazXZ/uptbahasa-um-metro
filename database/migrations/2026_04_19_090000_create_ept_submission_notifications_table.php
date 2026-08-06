<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ept_submission_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ept_submission_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('content_signature', 64)->nullable()->index();
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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ept_submission_notifications');
    }
};
