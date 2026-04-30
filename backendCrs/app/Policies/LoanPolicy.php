<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LoanPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-loan');
    }

    public function view(User $user, Loan $loan): bool
    {
        return $user->hasPermissionTo('view-loan');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-loan');
    }

    public function update(User $user, Loan $loan): bool
    {
        return $user->hasPermissionTo('edit-loan');
    }

    public function delete(User $user, Loan $loan): bool
    {
        return $user->hasPermissionTo('delete-loan');
    }

    public function approve(User $user, Loan $loan): bool
    {
        return $user->hasPermissionTo('approve-loan');
    }

    public function restore(User $user, Loan $loan): bool
    {
        return $user->hasPermissionTo('edit-loan');
    }

    public function forceDelete(User $user, Loan $loan): bool
    {
        return $user->hasRole('super-admin');
    }
}
