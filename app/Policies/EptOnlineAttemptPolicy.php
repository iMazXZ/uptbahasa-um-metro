<?php

namespace App\Policies;

class EptOnlineAttemptPolicy extends EptOnlineShieldPolicy
{
    protected string $permissionName = 'ept::online::attempt';
}
