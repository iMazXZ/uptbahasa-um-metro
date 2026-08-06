<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Storage;
use Filament\Models\Contracts\HasAvatar;
use Laravel\Sanctum\HasApiTokens;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Support\Facades\DB;
use App\Models\Penerjemahan;

class User extends Authenticatable implements HasAvatar, FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    /**
     * Kolom yang boleh diisi mass-assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'whatsapp',
        'whatsapp_verified_at',
        'whatsapp_otp',
        'whatsapp_otp_expires_at',
        'password',
        'srn',
        'prody_id',
        'year',
        'image',
        'nilaibasiclistening',
        'nomor_grup_bl',
        // Interactive Class (Pendidikan Bahasa Inggris)
        'interactive_class_1',
        'interactive_class_2',
        'interactive_class_3',
        'interactive_class_4',
        'interactive_class_5',
        'interactive_class_6',
        // Interactive Bahasa Arab (3 Prodi Islam)
        'interactive_bahasa_arab_1',
        'interactive_bahasa_arab_2',
    ];

    /**
     * Kolom yang disembunyikan saat serialisasi.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casting kolom.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'nomor_grup_bl' => 'integer',
        ];
    }

    /**
     * Relasi ke Prodi (FK: prody_id).
     */
    public function prody(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Prody::class, 'prody_id');
    }

    /**
     * Relasi ke notifikasi database (urut terbaru).
     */
    public function notifications()
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Avatar untuk Filament Admin.
     */
    public function getFilamentAvatarUrl(): ?string
    {
        if ($this->image) {
            return Storage::url($this->image);
        }

        return null; // Filament akan tampilkan inisial
    }

    /**
     * Contoh relasi ke pengajuan EPT (biarkan sesuai kebutuhanmu).
     */
    public function eptSubmissions(): HasMany
    {
        return $this->hasMany(\App\Models\EptSubmission::class);
    }

    public function eptOnlineTokens(): HasMany
    {
        return $this->hasMany(\App\Models\EptOnlineAccessToken::class, 'user_id');
    }

    public function eptOnlineAttempts(): HasMany
    {
        return $this->hasMany(\App\Models\EptOnlineAttempt::class, 'user_id');
    }

    /**
     * Relasi ke pengajuan penerjemahan milik user.
     */
    public function penerjemahans(): HasMany
    {
        return $this->hasMany(\App\Models\Penerjemahan::class, 'user_id');
    }

    /**
     * Relasi many-to-many: Tutor ↔ Prodi yang diampu.
     * Pivot: tutor_prody (user_id, prody_id).
     */
    public function tutorProdies(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Prody::class, 'tutor_prody')
            ->withTimestamps();
    }

    /**
     * Relasi ke attempts Basic Listening milik user (peserta).
     */
    public function basicListeningAttempts(): HasMany
    {
        return $this->hasMany(\App\Models\BasicListeningAttempt::class, 'user_id');
    }

    public function basicListeningGrade(): HasOne
    {
        return $this->hasOne(\App\Models\BasicListeningGrade::class);
    }

    public function basicListeningManualScores()
    {
        return $this->hasMany(\App\Models\BasicListeningManualScore::class);
    }

    public function getBlFinalNumericAttribute(): ?float
    {
        [$n, ] = \App\Support\BlSource::finalFor($this);
        return $n;
    }

    public function getBlFinalLetterAttribute(): ?string
    {
        [, $l] = \App\Support\BlSource::finalFor($this);
        return $l;
    }

    /**
     * Helper: ambil array ID prodi yang diampu tutor (memoized per-request).
     * Menggunakan pluck('id') dari tabel relasi (prodies).
     */
    public function assignedProdyIds(): array
    {
        static $cacheByUser = [];

        if (! isset($cacheByUser[$this->id])) {
            // Ambil dari pivot untuk menghindari ambiguitas kolom 'id'
            $ids = $this->tutorProdies()
                ->pluck('tutor_prody.prody_id')   // <- penting: kwalifikasi kolom
                ->unique()
                ->map(fn ($v) => (int) $v)
                ->values()
                ->all();

            $cacheByUser[$this->id] = $ids;
        }

        return $cacheByUser[$this->id];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->hasAnyRole([
                'Admin',
                'Staf Administrasi',
                'Kepala Lembaga',
                'pendaftar',
                'tutor',
                'Penerjemah',
            ]);
        }

        return false;
    }

    protected static function booted(): void
    {
        static::deleting(function (self $user) {
            // Hapus data yang tidak punya FK cascade.
            $user->penerjemahans()->delete();
            Penerjemahan::where('translator_id', $user->id)->update(['translator_id' => null]);

            $user->tokens()->delete();
            DatabaseNotification::where('notifiable_id', $user->id)
                ->where('notifiable_type', self::class)
                ->delete();

            DB::table('sessions')->where('user_id', $user->id)->delete();
        });
    }

}
