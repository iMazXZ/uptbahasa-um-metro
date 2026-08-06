<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;

abstract class BaseResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        return static::$shouldRegisterNavigation && static::canViewAny();
    }
}
