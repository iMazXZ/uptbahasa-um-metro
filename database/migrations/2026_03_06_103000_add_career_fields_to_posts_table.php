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
        Schema::table('posts', function (Blueprint $table) {
            $table->boolean('career_is_open')
                ->default(true)
                ->after('cover_path');

            $table->dateTime('career_deadline')
                ->nullable()
                ->after('career_is_open');

            $table->string('career_apply_url', 500)
                ->nullable()
                ->after('career_deadline');

            $table->index(
                ['type', 'career_is_open', 'career_deadline'],
                'posts_career_status_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_career_status_idx');
            $table->dropColumn([
                'career_is_open',
                'career_deadline',
                'career_apply_url',
            ]);
        });
    }
};

