<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MemberPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-member');
    }

    public function view(User $user, Member $member): bool
    {
        return $user->hasPermissionTo('view-member');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-member');
    }

    public function update(User $user, Member $member): bool
    {
        return $user->hasPermissionTo('edit-member');
    }

    public function delete(User $user, Member $member): bool
    {
        return $user->hasPermissionTo('delete-member');
    }

    public function restore(User $user, Member $member): bool
    {
        return $user->hasPermissionTo('edit-member');
    }

    public function forceDelete(User $user, Member $member): bool
    {
        return $user->hasRole('super-admin');
    }
}
