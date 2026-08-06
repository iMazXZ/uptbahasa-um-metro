<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('basic_listening_supervisors', function (Blueprint $table) {
            $table->id();
            $table->string('name');               // nama supervisor
            $table->string('position')->nullable(); // jabatan (opsional)
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('basic_listening_supervisors');
    }
};
