<?php

namespace App\Support;

class AuditLinkResolver
{
    public static function routeName(?string $subjectType): ?string
    {
        return match ($subjectType) {
            'product' => 'admin.products.show',
            'license' => 'admin.licenses.show',
            'user' => 'admin.profile',
            default => null,
        };
    }

    public static function url(?string $subjectType, mixed $subjectId): ?string
    {
        if (! $subjectType || empty($subjectId)) {
            return null;
        }

        $route = static::routeName($subjectType);

        if (! $route) {
            return null;
        }

        return match ($subjectType) {
            'product', 'license' => route($route, $subjectId),
            'user' => route($route),
            default => null,
        };
    }

    public static function label(?string $subjectType, mixed $subjectId): ?string
    {
        if (! $subjectType || empty($subjectId)) {
            return null;
        }

        return match ($subjectType) {
            'product' => "Xem sản phẩm #{$subjectId}",
            'license' => "Xem license #{$subjectId}",
            'user' => "Xem tài khoản #{$subjectId}",
            default => null,
        };
    }
}
