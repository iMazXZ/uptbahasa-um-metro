<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ept_registrations', function (Blueprint $table) {
            $table->unsignedTinyInteger('test_quota')
                ->nullable()
                ->after('student_status')
                ->index();

            $table->foreignId('grup_4_id')
                ->nullable()
                ->after('grup_3_id')
                ->constrained('ept_groups')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ept_registrations', function (Blueprint $table) {
            $table->dropForeign(['grup_4_id']);
            $table->dropIndex(['test_quota']);
            $table->dropColumn(['test_quota', 'grup_4_id']);
        });
    }
};
