<?php

namespace App\Filament\Widgets;

use App\Models\Pengumuman;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class PengumumanWidget extends Widget
{
    use HasWidgetShield;

    protected static string $view = 'filament.widgets.pengumuman-widget';
    
    protected int | string | array $columnSpan = 'full';

    public Collection $pengumumans;

    public function mount(): void
    {
        $this->pengumumans = Pengumuman::where('is_visible', true)
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->limit(5)
            ->get();
    }
}