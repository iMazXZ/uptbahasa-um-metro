<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->foreignId('ept_group_id')
                ->nullable()
                ->after('related_post_id')
                ->constrained('ept_groups')
                ->nullOnDelete();

            $table->unique('ept_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropUnique(['ept_group_id']);
            $table->dropForeign(['ept_group_id']);
            $table->dropColumn('ept_group_id');
        });
    }
};
