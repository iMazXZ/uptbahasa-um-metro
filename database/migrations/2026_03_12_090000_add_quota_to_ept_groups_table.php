<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ept_groups', function (Blueprint $table) {
            $table->unsignedSmallInteger('quota')
                ->default(20)
                ->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ept_groups', function (Blueprint $table) {
            $table->dropColumn('quota');
        });
    }
};

