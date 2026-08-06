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
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, json
            $table->string('group')->default('general'); // general, whatsapp, basic_listening
            $table->string('label')->nullable(); // Label untuk display di admin
            $table->text('description')->nullable(); // Deskripsi setting
            $table->timestamps();
        });

        // Insert default settings
        $this->seedDefaultSettings();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }

    /**
     * Seed default settings
     */
    private function seedDefaultSettings(): void
    {
        $settings = [
            // General Settings
            [
                'key' => 'maintenance_mode',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'general',
                'label' => 'Maintenance Mode',
                'description' => 'Jika aktif, tampilkan halaman maintenance untuk semua user (kecuali admin)',
            ],
            [
                'key' => 'registration_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'general',
                'label' => 'Registrasi Terbuka',
                'description' => 'Jika nonaktif, user baru tidak bisa mendaftar',
            ],

            // WhatsApp Settings
            [
                'key' => 'otp_enabled',
                'value' => '0', // Default OFF karena WA masih restricted
                'type' => 'boolean',
                'group' => 'whatsapp',
                'label' => 'OTP WhatsApp',
                'description' => 'Jika aktif, user harus verifikasi nomor via kode OTP yang dikirim ke WhatsApp',
            ],
            [
                'key' => 'wa_notification_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'whatsapp',
                'label' => 'Notifikasi WhatsApp',
                'description' => 'Jika aktif, sistem mengirim notifikasi status (EPT, Penerjemahan) via WhatsApp',
            ],

            // Basic Listening Settings
            [
                'key' => 'bl_quiz_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'basic_listening',
                'label' => 'Fitur Quiz Aktif',
                'description' => 'Jika nonaktif, user tidak bisa mengakses quiz Basic Listening',
            ],
        ];

        foreach ($settings as $setting) {
            \DB::table('site_settings')->insert(array_merge($setting, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
};
