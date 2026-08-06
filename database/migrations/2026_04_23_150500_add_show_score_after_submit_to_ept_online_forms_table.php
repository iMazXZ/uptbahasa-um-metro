<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ept_online_forms', function (Blueprint $table) {
            $table->boolean('show_score_after_submit')
                ->default(true)
                ->after('listening_audio_path');
        });
    }

    public function down(): void
    {
        Schema::table('ept_online_forms', function (Blueprint $table) {
            $table->dropColumn('show_score_after_submit');
        });
    }
};
