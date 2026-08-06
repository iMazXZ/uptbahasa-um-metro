<?php

namespace App\Policies;

use App\Models\InteractiveClassScore;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InteractiveClassScorePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_interactive::class::score');
    }

    public function view(User $user, InteractiveClassScore $record): bool
    {
        return $user->can('view_interactive::class::score');
    }

    public function create(User $user): bool
    {
        return $user->can('create_interactive::class::score');
    }

    public function update(User $user, InteractiveClassScore $record): bool
    {
        return $user->can('update_interactive::class::score');
    }

    public function delete(User $user, InteractiveClassScore $record): bool
    {
        return $user->can('delete_interactive::class::score');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_interactive::class::score');
    }

    public function forceDelete(User $user, InteractiveClassScore $record): bool
    {
        return $user->can('force_delete_interactive::class::score');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_interactive::class::score');
    }

    public function restore(User $user, InteractiveClassScore $record): bool
    {
        return $user->can('restore_interactive::class::score');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_interactive::class::score');
    }

    public function replicate(User $user, InteractiveClassScore $record): bool
    {
        return $user->can('replicate_interactive::class::score');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_interactive::class::score');
    }
}
