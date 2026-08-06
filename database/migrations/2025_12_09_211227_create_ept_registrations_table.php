<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ept_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('bukti_pembayaran');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            
            // Jadwal 3 grup (diisi admin setelah approve)
            $table->string('grup_1')->nullable();
            $table->dateTime('jadwal_1')->nullable();
            $table->string('grup_2')->nullable();
            $table->dateTime('jadwal_2')->nullable();
            $table->string('grup_3')->nullable();
            $table->dateTime('jadwal_3')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ept_registrations');
    }
};
