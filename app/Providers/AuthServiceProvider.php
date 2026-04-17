<?php

namespace App\Providers;

use App\Models\AdminUser;
use App\Policies\AdminActionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        AdminUser::class => AdminActionPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('admin-access', static function (?AdminUser $user): bool {
            return $user instanceof AdminUser && $user->is_active;
        });

        Gate::define('admin-super-admin', static function (?AdminUser $user): bool {
            return $user instanceof AdminUser && $user->is_active && $user->role === 'super_admin';
        });

        Gate::define('admin-support', static function (?AdminUser $user): bool {
            return $user instanceof AdminUser && $user->is_active && in_array($user->role, ['super_admin', 'admin', 'support'], true);
        });

        Gate::define('admin-finance', static function (?AdminUser $user): bool {
            return $user instanceof AdminUser && $user->is_active && in_array($user->role, ['super_admin', 'admin', 'finance'], true);
        });

        Gate::define('admin-license-manage', static fn (AdminUser $user): bool => app(AdminActionPolicy::class)->manageLicenses($user));
        Gate::define('admin-entitlement-manage', static fn (AdminUser $user): bool => app(AdminActionPolicy::class)->manageEntitlements($user));
        Gate::define('admin-finance-manage', static fn (AdminUser $user): bool => app(AdminActionPolicy::class)->manageFinance($user));
    }
}
