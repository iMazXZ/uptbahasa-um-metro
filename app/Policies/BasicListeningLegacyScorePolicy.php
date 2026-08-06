<?php

namespace App\Policies;

use App\Models\BasicListeningLegacyScore;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BasicListeningLegacyScorePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_basic::listening::legacy::score');
    }

    public function view(User $user, BasicListeningLegacyScore $record): bool
    {
        return $user->can('view_basic::listening::legacy::score');
    }

    public function create(User $user): bool
    {
        return $user->can('create_basic::listening::legacy::score');
    }

    public function update(User $user, BasicListeningLegacyScore $record): bool
    {
        return $user->can('update_basic::listening::legacy::score');
    }

    public function delete(User $user, BasicListeningLegacyScore $record): bool
    {
        return $user->can('delete_basic::listening::legacy::score');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_basic::listening::legacy::score');
    }

    public function forceDelete(User $user, BasicListeningLegacyScore $record): bool
    {
        return $user->can('force_delete_basic::listening::legacy::score');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_basic::listening::legacy::score');
    }

    public function restore(User $user, BasicListeningLegacyScore $record): bool
    {
        return $user->can('restore_basic::listening::legacy::score');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_basic::listening::legacy::score');
    }

    public function replicate(User $user, BasicListeningLegacyScore $record): bool
    {
        return $user->can('replicate_basic::listening::legacy::score');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_basic::listening::legacy::score');
    }
}
