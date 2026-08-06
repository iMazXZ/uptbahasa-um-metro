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

        $now = now();

        $settings = [
            [
                'key' => 'ept_all_prody',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'ept_registration',
                'label' => 'Semua Prodi Boleh Daftar EPT',
                'description' => 'Jika aktif, semua prodi bisa mendaftar EPT.',
            ],
            [
                'key' => 'ept_allowed_prody_ids',
                'value' => json_encode([]),
                'type' => 'json',
                'group' => 'ept_registration',
                'label' => 'Daftar Prodi yang Diizinkan',
                'description' => 'Isi prodi yang boleh mendaftar EPT.',
            ],
            [
                'key' => 'ept_allowed_prody_prefixes',
                'value' => json_encode(['S2']),
                'type' => 'json',
                'group' => 'ept_registration',
                'label' => 'Prefix Prodi yang Diizinkan',
                'description' => 'Contoh: S2, Profesi, Magister.',
            ],
            [
                'key' => 'ept_require_whatsapp',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'ept_registration',
                'label' => 'WhatsApp Wajib',
                'description' => 'Jika aktif, user wajib mengisi (dan verifikasi jika OTP aktif) nomor WhatsApp.',
            ],
            [
                'key' => 'ept_require_role_pendaftar',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'ept_registration',
                'label' => 'Role Pendaftar Saja',
                'description' => 'Jika aktif, hanya role pendaftar yang boleh mendaftar EPT.',
            ],
            [
                'key' => 'ept_require_biodata',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'ept_registration',
                'label' => 'Biodata Wajib',
                'description' => 'Jika aktif, biodata harus lengkap sebelum mendaftar EPT.',
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('site_settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge($setting, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        DB::table('site_settings')
            ->whereIn('key', [
                'ept_all_prody',
                'ept_allowed_prody_ids',
                'ept_allowed_prody_prefixes',
                'ept_require_whatsapp',
                'ept_require_role_pendaftar',
                'ept_require_biodata',
            ])
            ->delete();
    }
};
