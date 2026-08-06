<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        DB::table('site_settings')->updateOrInsert(
            ['key' => 'ept_registration_open'],
            [
                'value' => '1',
                'type' => 'boolean',
                'group' => 'ept_registration',
                'label' => 'Pendaftaran EPT Dibuka',
                'description' => 'Jika nonaktif, user baru tidak bisa membuat pendaftaran EPT. User yang sudah pernah daftar tetap bisa melihat status pendaftarannya.',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        DB::table('site_settings')
            ->where('key', 'ept_registration_open')
            ->delete();
    }
};
