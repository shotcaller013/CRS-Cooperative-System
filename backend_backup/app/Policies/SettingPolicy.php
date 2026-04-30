<?php
// app/Policies/SettingPolicy.php
namespace App\Policies;

use App\Models\CoopProfile;
use App\Models\User;

class SettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-setting');
    }

    public function create(User $user): bool
    {
        return $user->can('edit-setting');
    }

    public function update(User $user, CoopProfile $profile): bool
    {
        return $user->can('edit-setting');
    }
}
