<?php

namespace App\Policies;

use App\Models\AdminUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdminActionPolicy
{
    use HandlesAuthorization;

    public function manageLicenses(AdminUser $user): bool
    {
        return $user->is_active && in_array($user->role, ['super_admin', 'admin'], true);
    }

    public function manageEntitlements(AdminUser $user): bool
    {
        return $user->is_active && in_array($user->role, ['super_admin', 'admin', 'support'], true);
    }

    public function manageFinance(AdminUser $user): bool
    {
        return $user->is_active && in_array($user->role, ['super_admin', 'admin', 'finance'], true);
    }
}
