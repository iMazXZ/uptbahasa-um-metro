<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class EptRegistration extends Model
{
    use HasFactory;

    public const STUDENT_STATUS_REGULAR = 'regular';
    public const STUDENT_STATUS_MAGISTER = 'magister';
    public const STUDENT_STATUS_KONVERSI = 'konversi';
    public const STUDENT_STATUS_GENERAL = 'general';

    public const DEFAULT_MULTI_TEST_QUOTA = 3;
    public const EXTRA_MULTI_TEST_QUOTA = 4;
    public const GENERAL_TEST_QUOTA = 1;

    protected $fillable = [
        'user_id',
        'student_status',
        'test_quota',
        'bukti_pembayaran',
        'status',
        'approved_at',
        'rejected_at',
        'rejection_reason',
        'grup_1_id',
        'grup_2_id',
        'grup_3_id',
        'grup_4_id',
    ];

    protected $casts = [
        'test_quota' => 'integer',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $registration): void {
            if (filled($registration->bukti_pembayaran)) {
                Storage::disk('public')->delete($registration->bukti_pembayaran);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scheduleNotifications(): HasMany
    {
        return $this->hasMany(EptScheduleNotification::class, 'ept_registration_id');
    }

    public function eptOnlineAccessTokens(): HasMany
    {
        return $this->hasMany(\App\Models\EptOnlineAccessToken::class, 'ept_registration_id');
    }

    public function eptOnlineAttempts(): HasMany
    {
        return $this->hasMany(\App\Models\EptOnlineAttempt::class, 'ept_registration_id');
    }

    public static function studentStatusOptions(): array
    {
        return [
            self::STUDENT_STATUS_REGULAR => 'Regular',
            self::STUDENT_STATUS_MAGISTER => 'Magister',
            self::STUDENT_STATUS_KONVERSI => 'Konversi',
            self::STUDENT_STATUS_GENERAL => 'Umum',
        ];
    }

    public static function studentStatusLabel(?string $status): string
    {
        return static::studentStatusOptions()[$status] ?? 'Regular';
    }

    public static function defaultTestQuotaForStudentStatus(?string $status): int
    {
        return $status === self::STUDENT_STATUS_GENERAL
            ? self::GENERAL_TEST_QUOTA
            : self::DEFAULT_MULTI_TEST_QUOTA;
    }

    public static function testQuotaOptionsForStudentStatus(?string $status): array
    {
        if ($status === self::STUDENT_STATUS_GENERAL) {
            return [
                self::GENERAL_TEST_QUOTA => '1 kali tes',
            ];
        }

        return [
            self::DEFAULT_MULTI_TEST_QUOTA => '3 kali tes - default',
            self::EXTRA_MULTI_TEST_QUOTA => '4 kali tes - tagihan/pembayaran 200',
        ];
    }

    public static function normalizeTestQuota(?int $quota, ?string $status): int
    {
        if ($status === self::STUDENT_STATUS_GENERAL) {
            return self::GENERAL_TEST_QUOTA;
        }

        return in_array($quota, [self::DEFAULT_MULTI_TEST_QUOTA, self::EXTRA_MULTI_TEST_QUOTA], true)
            ? $quota
            : self::DEFAULT_MULTI_TEST_QUOTA;
    }

    public function grup1(): BelongsTo
    {
        return $this->belongsTo(EptGroup::class, 'grup_1_id');
    }

    public function grup2(): BelongsTo
    {
        return $this->belongsTo(EptGroup::class, 'grup_2_id');
    }

    public function grup3(): BelongsTo
    {
        return $this->belongsTo(EptGroup::class, 'grup_3_id');
    }

    public function grup4(): BelongsTo
    {
        return $this->belongsTo(EptGroup::class, 'grup_4_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'approved']);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'  => 'Menunggu Verifikasi',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default    => $this->status,
        };
    }

    public function getStudentStatusLabelAttribute(): string
    {
        return static::studentStatusLabel($this->student_status);
    }

    public function getTestQuotaLabelAttribute(): string
    {
        return $this->requiredGroupCount() . ' kali tes';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending'  => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            default    => 'gray',
        };
    }

    public function isGeneralParticipant(): bool
    {
        return $this->student_status === self::STUDENT_STATUS_GENERAL;
    }

    public function requiredGroupCount(): int
    {
        return static::normalizeTestQuota($this->test_quota, $this->student_status);
    }

    public function assignedGroups(): Collection
    {
        return collect(range(1, $this->requiredGroupCount()))
            ->map(fn (int $slot) => $this->{"grup{$slot}"});
    }

    public function testNumberForGroupId(int $groupId): ?int
    {
        foreach (range(1, $this->requiredGroupCount()) as $slot) {
            if ((int) $this->{"grup_{$slot}_id"} === $groupId) {
                return $slot;
            }
        }

        return null;
    }

    public function scheduleNotificationForGroupId(int $groupId): ?EptScheduleNotification
    {
        if ($this->relationLoaded('scheduleNotifications')) {
            return $this->scheduleNotifications
                ->firstWhere('ept_group_id', $groupId);
        }

        return $this->scheduleNotifications()
            ->where('ept_group_id', $groupId)
            ->first();
    }

    /**
     * Cek apakah ada jadwal (minimal 1 grup punya jadwal)
     */
    public function hasSchedule(): bool
    {
        return $this->assignedGroups()->contains(
            static fn ($group) => filled($group?->jadwal),
        );
    }

    /**
     * Cek apakah semua jadwal sudah lengkap
     */
    public function hasAllSchedules(): bool
    {
        $groups = $this->assignedGroups();

        return $groups->count() === $this->requiredGroupCount()
            && $groups->every(static fn ($group) => filled($group?->jadwal));
    }

    public function isCycleCompleted(?CarbonInterface $reference = null): bool
    {
        if ($this->status !== 'approved' || ! $this->hasAllSchedules()) {
            return false;
        }

        $reference ??= now();

        return $this->assignedGroups()->every(
            static fn ($group) => $group?->jadwal instanceof CarbonInterface
                ? $group->jadwal->lessThanOrEqualTo($reference)
                : false,
        );
    }

    public function blocksNewRegistration(?CarbonInterface $reference = null): bool
    {
        return match ($this->status) {
            'pending' => true,
            'approved' => ! $this->isCycleCompleted($reference),
            default => false,
        };
    }

    public static function hasDistinctGroupAssignments(array $groupIds): bool
    {
        $normalized = array_values(array_filter(
            array_map(
                static fn ($groupId) => filled($groupId) ? (int) $groupId : null,
                $groupIds,
            ),
            static fn ($groupId) => $groupId !== null,
        ));

        return count($normalized) === count(array_unique($normalized));
    }
}
