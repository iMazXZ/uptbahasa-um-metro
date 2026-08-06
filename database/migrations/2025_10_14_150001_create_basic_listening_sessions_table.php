<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('basic_listening_sessions', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('number')->comment('1-5, 6=UAS');
            $table->string('title');
            $table->text('summary')->nullable();
            $table->string('audio_url')->nullable(); // path storage atau URL
            $table->dateTime('opens_at')->nullable();
            $table->dateTime('closes_at')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('number');
        });
    }
    public function down(): void {
        Schema::dropIfExists('basic_listening_sessions');
    }
};

