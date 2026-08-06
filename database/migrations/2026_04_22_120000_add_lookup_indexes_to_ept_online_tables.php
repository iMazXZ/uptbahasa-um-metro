<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ept_online_access_tokens', function (Blueprint $table) {
            $table->index('token_hash', 'ept_online_access_tokens_token_hash_idx');
        });

        Schema::table('ept_online_attempts', function (Blueprint $table) {
            $table->index(
                ['access_token_id', 'user_id', 'status'],
                'ept_online_attempts_token_user_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('ept_online_attempts', function (Blueprint $table) {
            $table->dropIndex('ept_online_attempts_token_user_status_idx');
        });

        Schema::table('ept_online_access_tokens', function (Blueprint $table) {
            $table->dropIndex('ept_online_access_tokens_token_hash_idx');
        });
    }
};
