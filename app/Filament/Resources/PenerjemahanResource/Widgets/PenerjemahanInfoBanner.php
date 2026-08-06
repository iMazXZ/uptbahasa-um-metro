<?php

namespace App\Filament\Resources\PenerjemahanResource\Widgets;

use App\Models\Penerjemahan;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class PenerjemahanInfoBanner extends Widget
{
    protected static string $view = 'filament.resources.penerjemahan-resource.widgets.penerjemahan-info-banner';
    protected int|string|array $columnSpan = 'full';
    public static function canView(): bool
    {
        return Auth::check() && Auth::user()->hasRole('pendaftar');
    }

    public function getLatestSubmissionProperty(): ?Penerjemahan
    {
        return Penerjemahan::where('user_id', Auth::id())
            ->latest('created_at')
            ->first();
    }

    public function getFinishedSubmissionProperty(): ?Penerjemahan
    {
        return Penerjemahan::where('user_id', Auth::id())
            ->where('status', 'Selesai')
            ->latest('completion_date')
            ->latest('created_at')
            ->first();
    }
}
