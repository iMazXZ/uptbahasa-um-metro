<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EptSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nilai_tes_1', 'tanggal_tes_1', 'foto_path_1',
        'nilai_tes_2', 'tanggal_tes_2', 'foto_path_2',
        'nilai_tes_3', 'tanggal_tes_3', 'foto_path_3',
        'status',
        'catatan_admin',
        'verification_code', 'verification_url', 'surat_nomor',
        'approved_at', 'approved_by', 'rejected_at', 'rejected_by',
        'surat_nomor_updated_by', 'surat_nomor_updated_at', 'surat_nomor_history',
    ];

    protected $casts = [
        'tanggal_tes_1' => 'date',
        'tanggal_tes_2' => 'date',
        'tanggal_tes_3' => 'date',
        'approved_at'   => 'datetime',
        'rejected_at'   => 'datetime',
        'surat_nomor_updated_at' => 'datetime',
        'surat_nomor_history' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function suratNomorUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'surat_nomor_updated_by');
    }

    public function notificationTracking(): HasOne
    {
        return $this->hasOne(EptSubmissionNotification::class, 'ept_submission_id');
    }

    /**
     * Tambah entry ke history
     */
    public function addSuratNomorHistory(string $oldNumber, string $newNumber, string $reason = ''): void
    {
        $history = $this->surat_nomor_history ?? [];
        
        $history[] = [
            'old_number' => $oldNumber,
            'new_number' => $newNumber,
            'changed_by' => auth()->id(),
            'changed_by_name' => auth()->user()?->name ?? 'System',
            'reason' => $reason,
            'changed_at' => now()->toIso8601String(),
        ];

        $this->update([
            'surat_nomor_history' => $history,
            'surat_nomor_updated_by' => auth()->id(),
            'surat_nomor_updated_at' => now(),
        ]);
    }

    /**
     * Get formatted history untuk display
     */
    public function getFormattedHistory(): string
    {
        if (!$this->surat_nomor_history || empty($this->surat_nomor_history)) {
            return '<em class="text-slate-500">Belum ada riwayat perubahan</em>';
        }

        $html = '<div class="space-y-2">';
        foreach (array_reverse($this->surat_nomor_history) as $entry) {
            $changedAt = \Carbon\Carbon::parse($entry['changed_at'] ?? now())->format('d M Y H:i');
            $html .= '<div class="text-sm border-l-4 border-blue-400 pl-3 py-2">';
            $html .= '<p class="font-semibold text-slate-900">' . $entry['old_number'] . ' → ' . $entry['new_number'] . '</p>';
            $html .= '<p class="text-xs text-slate-600">Oleh: ' . ($entry['changed_by_name'] ?? 'Unknown') . '</p>';
            if (!empty($entry['reason'])) {
                $html .= '<p class="text-xs text-slate-600">Alasan: ' . $entry['reason'] . '</p>';
            }
            $html .= '<p class="text-xs text-slate-500">' . $changedAt . '</p>';
            $html .= '</div>';
        }
        $html .= '</div>';

        return $html;
    }
}
