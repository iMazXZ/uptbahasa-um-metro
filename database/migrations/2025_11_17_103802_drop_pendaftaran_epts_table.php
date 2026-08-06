<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('pendaftaran_epts');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Dikosongkan saja. Kalau perlu, nanti bisa bikin migration baru untuk re-create tabelnya.
    }
};
