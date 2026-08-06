<?php

namespace App\Policies;

class EptOnlineSectionPolicy extends EptOnlineShieldPolicy
{
    protected string $permissionName = 'ept::online::section';
}
