<?php

namespace App\Filament\Resources;

use BezhanSalleh\FilamentShield\Resources\RoleResource as ShieldRoleResource;

class RoleResource extends ShieldRoleResource
{
    public static function getNavigationGroup(): ?string
    {
        return 'Sistem';
    }
    
    public static function getNavigationSort(): ?int
    {
        return 2; // Setelah Pengaturan Situs
    }
}
