<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ept_registrations', function (Blueprint $table) {
            $table->string('student_status', 20)
                ->nullable()
                ->after('user_id')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('ept_registrations', function (Blueprint $table) {
            $table->dropIndex(['student_status']);
            $table->dropColumn('student_status');
        });
    }
};
