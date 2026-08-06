<?php

namespace App\Policies;

class EptOnlineQuestionPolicy extends EptOnlineShieldPolicy
{
    protected string $permissionName = 'ept::online::question';
}
