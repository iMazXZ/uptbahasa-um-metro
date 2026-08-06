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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->dateTime('clock_in');
            $table->dateTime('clock_out')->nullable();
            $table->decimal('clock_in_lat', 10, 8);
            $table->decimal('clock_in_long', 11, 8);
            $table->string('clock_in_photo'); // wajib
            $table->decimal('clock_out_lat', 10, 8)->nullable();
            $table->decimal('clock_out_long', 11, 8)->nullable();
            $table->string('clock_out_photo')->nullable();
            $table->timestamps();

            // Index untuk query harian
            $table->index(['user_id', 'clock_in']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
