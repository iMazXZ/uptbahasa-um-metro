<?php

namespace App\Policies;

class EptOnlineResultPolicy extends EptOnlineShieldPolicy
{
    protected string $permissionName = 'ept::online::result';
}
