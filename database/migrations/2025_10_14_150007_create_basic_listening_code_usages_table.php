<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('basic_listening_code_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connect_code_id')->constrained('basic_listening_connect_codes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('used_at');
            $table->string('ip', 45)->nullable();
            $table->string('ua', 255)->nullable();
            $table->timestamps();

            $table->index(['connect_code_id','user_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('basic_listening_code_usages');
    }
};
