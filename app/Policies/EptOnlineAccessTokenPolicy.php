<?php

namespace App\Policies;

class EptOnlineAccessTokenPolicy extends EptOnlineShieldPolicy
{
    protected string $permissionName = 'ept::online::access::token';
}
