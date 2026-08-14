<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ept_group_proctors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ept_group_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->unique(['ept_group_id', 'user_id']);

            $table->foreign('ept_group_id')->references('id')->on('ept_groups')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('ept_registrations', function (Blueprint $table) {
            $table->timestamp('proctor_verified_at')->nullable()->after('rejection_reason');
            $table->unsignedBigInteger('proctor_verified_by')->nullable()->after('proctor_verified_at');
        });

        Schema::table('ept_online_attempts', function (Blueprint $table) {
            $table->timestamp('paused_at')->nullable()->after('submitted_at');
            $table->timestamp('resumed_at')->nullable()->after('paused_at');
            $table->string('pause_reason', 255)->nullable()->after('resumed_at');
            $table->unsignedBigInteger('pause_controlled_by')->nullable()->after('pause_reason');
        });
    }

    public function down(): void
    {
        Schema::table('ept_online_attempts', function (Blueprint $table) {
            $table->dropColumn(['paused_at', 'resumed_at', 'pause_reason', 'pause_controlled_by']);
        });

        Schema::table('ept_registrations', function (Blueprint $table) {
            $table->dropColumn(['proctor_verified_at', 'proctor_verified_by']);
        });

        Schema::dropIfExists('ept_group_proctors');
    }
};
