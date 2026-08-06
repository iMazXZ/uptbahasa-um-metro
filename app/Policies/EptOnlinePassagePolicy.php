<?php

namespace App\Policies;

class EptOnlinePassagePolicy extends EptOnlineShieldPolicy
{
    protected string $permissionName = 'ept::online::passage';
}
