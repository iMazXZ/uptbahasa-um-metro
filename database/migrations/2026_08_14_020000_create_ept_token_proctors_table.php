<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ept_token_proctors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ept_online_access_token_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->unique(['ept_online_access_token_id', 'user_id']);

            $table->foreign('ept_online_access_token_id')->references('id')->on('ept_online_access_tokens')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ept_token_proctors');
    }
};
