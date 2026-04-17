<?php

namespace App\Services\Access;

use App\Models\AdminUser;
use App\Models\Entitlement;

class ProductScopeService
{
    public function canAccessEntitlement(AdminUser $admin, Entitlement $entitlement): bool
    {
        if (\in_array($admin->role, ['super_admin', 'admin'], true)) {
            return true;
        }

        $productId = $entitlement->plan?->product_id;

        if (! $productId) {
            return false;
        }

        return $admin->managedProducts()->whereKey($productId)->exists();
    }
}
