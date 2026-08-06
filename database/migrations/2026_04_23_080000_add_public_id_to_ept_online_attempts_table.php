<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ept_online_attempts', function (Blueprint $table) {
            $table->string('public_id', 26)->nullable()->after('id');
            $table->unique('public_id');
        });

        DB::table('ept_online_attempts')
            ->select(['id'])
            ->orderBy('id')
            ->get()
            ->each(function (object $attempt): void {
                DB::table('ept_online_attempts')
                    ->where('id', $attempt->id)
                    ->update([
                        'public_id' => (string) Str::ulid(),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('ept_online_attempts', function (Blueprint $table) {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }
};
