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
                'key' => 'front_head_script',
                'value' => '',
                'type' => 'string',
                'group' => 'front_site',
                'label' => 'Script Head (Front Site)',
                'description' => 'Script yang disisipkan di <head> untuk front site.',
            ],
            [
                'key' => 'front_body_script',
                'value' => '',
                'type' => 'string',
                'group' => 'front_site',
                'label' => 'Script Body (Front Site)',
                'description' => 'Script yang disisipkan tepat setelah <body> untuk front site.',
            ],
            [
                'key' => 'front_footer_script',
                'value' => '',
                'type' => 'string',
                'group' => 'front_site',
                'label' => 'Script Footer (Front Site)',
                'description' => 'Script yang disisipkan sebelum </body> untuk front site.',
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
                'front_head_script',
                'front_body_script',
                'front_footer_script',
            ])
            ->delete();
    }
};
