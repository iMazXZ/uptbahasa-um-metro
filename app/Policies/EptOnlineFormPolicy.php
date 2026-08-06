<?php

namespace App\Policies;

class EptOnlineFormPolicy extends EptOnlineShieldPolicy
{
    protected string $permissionName = 'ept::online::form';
}
