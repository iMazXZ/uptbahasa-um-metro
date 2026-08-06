<?php

namespace App\Filament\Resources\PenerjemahanResource\Pages;

use App\Filament\Resources\PenerjemahanResource;
use App\Models\SiteSetting;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;

class ListPenerjemahans extends ListRecords
{
    protected static string $resource = PenerjemahanResource::class;

    /** === Helpers selaras dengan SubmitEptScore === */

    private function userHasCompleteBiodata(): bool
    {
        $u = Auth::user();
        if (! $u) return false;

        $hasBasicInfo = !empty($u->prody) && !empty($u->srn) && !empty($u->year);
        if (! $hasBasicInfo) return false;

        $year = (int) $u->year;
        if ($year <= 2024) {
            // angkatan lama: wajib nilai BL manual
            return is_numeric($u->nilaibasiclistening);
        }

        // 2025+ biodata dasar saja sudah cukup (cek BL di helper lain)
        return true;
    }

    private function userHasCompletedBasicListening(): bool
    {
        $u = Auth::user();
        return $u ? SiteSetting::hasCompletedBasicListening($u) : false;
    }

    private function basicListeningRequirementMessage(): string
    {
        $u = Auth::user();
        $year = (int) ($u?->year ?? 0);

        if ($year <= 2024) {
            return '⚠️ Nilai Basic Listening arsip/manual Anda belum tercatat. Jika sudah pernah lulus, silakan konfirmasi ke kantor Lembaga Bahasa.';
        }

        return '⚠️ Anda belum mengikuti Basic Listening. Setelah nilai Attendance dan Final Test terisi, tombol “Permintaan Baru” akan muncul.';
    }

    protected function getHeaderActions(): array
    {
        $user = Auth::user();

        $baseActions = [
            Action::make('dashboard')
                ->label('Kembali ke Dasbor')
                ->url(route('filament.admin.pages.2'))
                ->color('gray')
                ->icon('heroicon-o-arrow-left'),
        ];

        $conditional = [];

        // Syarat tampil:
        // - BUKAN penerjemah
        // - Biodata dasar lengkap (lihat aturan tahun)
        // - Jika 2025+, sudah mengikuti Basic Listening
        $isTranslator = $user?->hasRole('Penerjemah');

        if (! $isTranslator && $this->userHasCompleteBiodata() && $this->userHasCompletedBasicListening()) {
            $conditional[] = Actions\CreateAction::make()->label('Permintaan Baru');
        }

        return array_merge($baseActions, $conditional);
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        // Filter hanya data yang ditugaskan ke penerjemah ini
        if (auth()->user()->hasRole('Penerjemah')) {
            return $query->where('translator_id', auth()->id());
        }

        return $query;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\PenerjemahanResource\Widgets\PenerjemahanInfoBanner::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    public function getSubheading(): ?string
    {
        $user = Auth::user();

        if ($user->hasRole('pendaftar')) {
            // Pesan selaras: jelaskan kedua aturan
            if (! $this->userHasCompleteBiodata()) {
                return '⚠️ Lengkapi Prodi, NPM, dan Tahun Angkatan. Untuk angkatan 2024 ke bawah wajib mengisi nilai Basic Listening.';
            }

            if (! $this->userHasCompletedBasicListening()) {
                return $this->basicListeningRequirementMessage();
            }
        }

        return '';
    }
}
