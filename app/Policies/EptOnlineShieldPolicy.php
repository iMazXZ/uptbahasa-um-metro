<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

abstract class EptOnlineShieldPolicy
{
    use HandlesAuthorization;

    protected string $permissionName;

    protected function can(User $user, string $action): bool
    {
        return $user->can($action . '_' . $this->permissionName);
    }

    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view_any');
    }

    public function view(User $user, Model $record): bool
    {
        return $this->can($user, 'view');
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'create');
    }

    public function update(User $user, Model $record): bool
    {
        return $this->can($user, 'update');
    }

    public function delete(User $user, Model $record): bool
    {
        return $this->can($user, 'delete');
    }

    public function deleteAny(User $user): bool
    {
        return $this->can($user, 'delete_any');
    }

    public function forceDelete(User $user, Model $record): bool
    {
        return $this->can($user, 'force_delete');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->can($user, 'force_delete_any');
    }

    public function restore(User $user, Model $record): bool
    {
        return $this->can($user, 'restore');
    }

    public function restoreAny(User $user): bool
    {
        return $this->can($user, 'restore_any');
    }

    public function replicate(User $user, Model $record): bool
    {
        return $this->can($user, 'replicate');
    }

    public function reorder(User $user): bool
    {
        return $this->can($user, 'reorder');
    }
}
