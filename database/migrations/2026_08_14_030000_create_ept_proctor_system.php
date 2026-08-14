<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Kolom verifikasi pengawas pada registrasi
        Schema::table('ept_registrations', function (Blueprint $table) {
            $table->timestamp('proctor_verified_at')->nullable()->after('rejection_reason');
            $table->unsignedBigInteger('proctor_verified_by')->nullable()->after('proctor_verified_at');
        });

        // Kolom kontrol pause / disqualify pada attempt
        Schema::table('ept_online_attempts', function (Blueprint $table) {
            $table->timestamp('paused_at')->nullable()->after('submitted_at');
            $table->timestamp('resumed_at')->nullable()->after('paused_at');
            $table->string('pause_reason', 255)->nullable()->after('resumed_at');
            $table->unsignedBigInteger('pause_controlled_by')->nullable()->after('pause_reason');
        });

        // Tambah nilai enum disqualified
        DB::statement("ALTER TABLE ept_online_attempts MODIFY COLUMN status ENUM('draft','in_progress','submitted','expired','cancelled','disqualified') NOT NULL DEFAULT 'draft'");

        // Pivot pengawas per token
        Schema::create('ept_token_proctors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ept_online_access_token_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->unique(['ept_online_access_token_id', 'user_id']);

            $table->foreign('ept_online_access_token_id')->references('id')->on('ept_online_access_tokens')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        // Role pengawas
        Role::firstOrCreate([
            'name' => 'Pengawas EPT',
            'guard_name' => 'web',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ept_token_proctors');

        Schema::table('ept_online_attempts', function (Blueprint $table) {
            $table->dropColumn(['paused_at', 'resumed_at', 'pause_reason', 'pause_controlled_by']);
        });

        DB::statement("ALTER TABLE ept_online_attempts MODIFY COLUMN status ENUM('draft','in_progress','submitted','expired','cancelled') NOT NULL DEFAULT 'draft'");

        Schema::table('ept_registrations', function (Blueprint $table) {
            $table->dropColumn(['proctor_verified_at', 'proctor_verified_by']);
        });

        Role::where('name', 'Pengawas EPT')->where('guard_name', 'web')->delete();
    }
};
