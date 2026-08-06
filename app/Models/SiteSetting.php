<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BasicListeningGrade;
use App\Support\LegacyBasicListeningScores;
use Illuminate\Support\Facades\Cache;
use App\Models\User;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'label', 'description'];

    /**
     * Cache TTL in seconds (1 hour)
     */
    protected static int $cacheTtl = 3600;

    /**
     * Get a setting value by key
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("site_setting_{$key}", static::$cacheTtl, function () use ($key) {
            return static::where('key', $key)->first();
        });

        if (!$setting) {
            return $default;
        }

        return static::castValue($setting->value, $setting->type);
    }

    /**
     * Set a setting value by key
     */
    public static function set(string $key, mixed $value): bool
    {
        $setting = static::where('key', $key)->first();

        if (!$setting) {
            return false;
        }

        if (is_array($value)) {
            $value = json_encode($value);
        }

        // Convert boolean to string for storage
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }

        $setting->update(['value' => (string) $value]);

        // Clear cache
        Cache::forget("site_setting_{$key}");

        return true;
    }

    /**
     * Cast value to appropriate type
     */
    protected static function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => (bool) (int) $value,
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Get all settings by group
     */
    public static function getByGroup(string $group): array
    {
        return static::where('group', $group)->get()->toArray();
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        $keys = static::pluck('key')->toArray();
        foreach ($keys as $key) {
            Cache::forget("site_setting_{$key}");
        }
    }

    // ============================================================
    // HELPER METHODS - Shortcuts for commonly used settings
    // ============================================================

    /**
     * Check if maintenance mode is enabled
     */
    public static function isMaintenanceEnabled(): bool
    {
        return (bool) static::get('maintenance_mode', false);
    }

    /**
     * Check if registration is enabled
     */
    public static function isRegistrationEnabled(): bool
    {
        return (bool) static::get('registration_enabled', true);
    }

    /**
     * Check if OTP WhatsApp is enabled
     */
    public static function isOtpEnabled(): bool
    {
        return (bool) static::get('otp_enabled', false);
    }

    /**
     * Check if WhatsApp notifications are enabled
     */
    public static function isWaNotificationEnabled(): bool
    {
        return (bool) static::get('wa_notification_enabled', true);
    }

    /**
     * Check if Basic Listening quiz is enabled
     */
    public static function isBlQuizEnabled(): bool
    {
        return (bool) static::get('bl_quiz_enabled', true);
    }

    /**
     * Get BL period start date for filtering
     */
    public static function getBlPeriodStartDate(): ?string
    {
        return static::get('bl_period_start_date', null);
    }

    // ============================================================
    // EPT REGISTRATION SETTINGS
    // ============================================================

    public static function isEptAllProdyEnabled(): bool
    {
        return (bool) static::get('ept_all_prody', false);
    }

    public static function isEptRegistrationOpen(): bool
    {
        return (bool) static::get('ept_registration_open', true);
    }

    public static function getEptAllowedProdyIds(): array
    {
        $ids = static::get('ept_allowed_prody_ids', []);
        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_filter(array_map('intval', $ids)));
    }

    public static function getEptAllowedProdyPrefixes(): array
    {
        $prefixes = static::get('ept_allowed_prody_prefixes', []);
        if (is_string($prefixes)) {
            $prefixes = array_map('trim', explode(',', $prefixes));
        }
        if (! is_array($prefixes)) {
            return [];
        }

        $normalized = [];
        foreach ($prefixes as $prefix) {
            $prefix = trim((string) $prefix);
            if ($prefix === '') {
                continue;
            }
            $normalized[] = strtolower($prefix);
        }

        return array_values(array_unique($normalized));
    }

    public static function isEptRequireWhatsApp(): bool
    {
        return (bool) static::get('ept_require_whatsapp', false);
    }

    public static function isEptRequireRolePendaftar(): bool
    {
        return (bool) static::get('ept_require_role_pendaftar', false);
    }

    public static function isEptRequireBiodata(): bool
    {
        return (bool) static::get('ept_require_biodata', false);
    }

    public static function isEptBiodataComplete(User $user): bool
    {
        $hasBasicInfo = $user->prody_id && $user->srn && $user->year;
        if (! $hasBasicInfo) {
            return false;
        }

        $yearInt = (int) $user->year;
        $prodyName = $user->prody?->name ?? '';
        $isS2 = $prodyName !== '' && str_starts_with($prodyName, 'S2');
        $isPBI = $prodyName === 'Pendidikan Bahasa Inggris';
        $prodiIslam = ['Komunikasi dan Penyiaran Islam', 'Pendidikan Agama Islam', 'Pendidikan Islam Anak Usia Dini'];
        $isProdiIslam = $prodyName !== '' && in_array($prodyName, $prodiIslam, true);
        $needsNilai = $yearInt && $yearInt <= 2024 && ! $isS2;

        if (! $needsNilai) {
            return true;
        }

        if ($isPBI) {
            return is_numeric($user->interactive_class_1 ?? null)
                && is_numeric($user->interactive_class_6 ?? null);
        }

        if ($isProdiIslam) {
            return LegacyBasicListeningScores::effectiveScoreForUser($user) !== null
                && is_numeric($user->interactive_bahasa_arab_1 ?? null)
                && is_numeric($user->interactive_bahasa_arab_2 ?? null);
        }

        return LegacyBasicListeningScores::effectiveScoreForUser($user) !== null;
    }

    public static function checkEptEligibility(User $user): array
    {
        if (! static::isEptRegistrationOpen()) {
            return [false, 'Pendaftaran EPT sedang ditutup.'];
        }

        if (static::isEptRequireRolePendaftar() && ! $user->hasRole('pendaftar')) {
            return [false, 'Fitur ini hanya untuk role pendaftar.'];
        }

        if (static::isEptRequireWhatsApp() && ! static::hasValidWhatsapp($user)) {
            return [false, 'Nomor WhatsApp wajib diisi dan diverifikasi.'];
        }

        if (static::isEptRequireBiodata() && ! static::isEptBiodataComplete($user)) {
            return [false, 'Biodata belum lengkap.'];
        }

        if (static::isEptAllProdyEnabled()) {
            return [true, null];
        }

        if (! $user->prody_id || ! $user->prody?->name) {
            return [false, 'Prodi belum diisi.'];
        }

        $allowedIds = static::getEptAllowedProdyIds();
        if (! empty($allowedIds)) {
            return [in_array((int) $user->prody_id, $allowedIds, true), 'Prodi belum diizinkan.'];
        }

        $prefixes = static::getEptAllowedProdyPrefixes();
        if (! empty($prefixes)) {
            $name = strtolower($user->prody->name ?? '');
            foreach ($prefixes as $prefix) {
                if ($prefix !== '' && str_starts_with($name, $prefix)) {
                    return [true, null];
                }
            }
            return [false, 'Prodi belum diizinkan.'];
        }

        return [false, 'Akses EPT belum dibuka untuk prodi manapun.'];
    }

    public static function canUserRegisterEpt(User $user): bool
    {
        return static::checkEptEligibility($user)[0] ?? false;
    }

    public static function hasCompletedBasicListening(User $user): bool
    {
        $prodyName = $user->prody?->name ?? '';
        $isS2 = $prodyName !== '' && str_starts_with($prodyName, 'S2');
        if ($isS2) {
            return true;
        }

        $yearInt = (int) ($user->year ?? 0);
        if ($yearInt <= 2024) {
            return LegacyBasicListeningScores::effectiveScoreForUser($user) !== null;
        }

        if ($yearInt < 2025) {
            return false;
        }

        $grade = BasicListeningGrade::query()
            ->where('user_id', $user->id)
            ->where('user_year', $user->year)
            ->first();

        return $grade !== null && (
            (is_numeric($grade->attendance) && is_numeric($grade->final_test))
            || is_numeric($grade->final_numeric_cached)
        );
    }

    protected static function hasValidWhatsapp(User $user): bool
    {
        if (static::isOtpEnabled()) {
            return ! empty($user->whatsapp_verified_at);
        }

        return ! empty($user->whatsapp);
    }
}
