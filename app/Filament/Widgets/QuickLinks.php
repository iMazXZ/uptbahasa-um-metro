<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class QuickLinks extends Widget
{
    use HasWidgetShield;
    
    protected static string $view = 'filament.widgets.quick-links';
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return true;
    }
}
