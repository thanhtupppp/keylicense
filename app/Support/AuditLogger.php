<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\License;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    public static function log(string $event, ?Model $subject = null, array $meta = []): ?AuditLog
    {
        try {
            $request = Request::instance();
            $actor = Auth::user();
            $actorName = static::resolveActorName($meta, $actor);

            return AuditLog::create([
                'event_type' => $event,
                'subject_type' => static::resolveSubjectType($subject),
                'subject_id' => $subject?->getKey(),
                'actor_type' => static::resolveActorType($actor),
                'actor_id' => $actor?->getKey(),
                'actor_name' => $actorName,
                'ip_address' => $meta['ip'] ?? $request->ip(),
                'user_agent' => $meta['user_agent'] ?? $request->userAgent(),
                'payload' => Arr::except($meta, ['ip', 'user_agent', 'actor_name']),
                'result' => $meta['result'] ?? 'success',
                'severity' => $meta['severity'] ?? 'info',
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Audit log write failed', [
                'event_type' => $event,
                'subject_type' => static::resolveSubjectType($subject),
                'subject_id' => $subject?->getKey(),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public static function actorName(?string $actorName, array $meta = []): array
    {
        return array_merge($meta, ['actor_name' => $actorName]);
    }

    public static function login(array $meta = []): ?AuditLog
    {
        return static::log('login', null, $meta);
    }

    public static function logout(array $meta = []): ?AuditLog
    {
        return static::log('logout', null, $meta);
    }

    public static function adminLogin(array $meta = []): ?AuditLog
    {
        return static::log('ADMIN_LOGIN', null, $meta);
    }

    public static function adminLoginFailed(array $meta = []): ?AuditLog
    {
        return static::log('ADMIN_LOGIN_FAILED', null, $meta);
    }

    public static function adminLocked(array $meta = []): ?AuditLog
    {
        return static::log('ADMIN_LOCKED', null, $meta);
    }

    public static function adminLogout(array $meta = []): ?AuditLog
    {
        return static::log('ADMIN_LOGOUT', null, $meta);
    }

    public static function productCreated(Model $product, array $meta = []): ?AuditLog
    {
        return static::log('product.created', $product, $meta);
    }

    public static function productUpdated(Model $product, array $meta = []): ?AuditLog
    {
        return static::log('product.updated', $product, $meta);
    }

    public static function productDeleted(Model $product, array $meta = []): ?AuditLog
    {
        return static::log('product.deleted', $product, $meta);
    }

    public static function licenseCreated(Model $license, array $meta = []): ?AuditLog
    {
        return static::log('license.created', $license, $meta);
    }

    public static function licenseUpdated(Model $license, array $meta = []): ?AuditLog
    {
        return static::log('license.updated', $license, $meta);
    }

    public static function licenseRevoked(Model $license, array $meta = []): ?AuditLog
    {
        return static::log('license.revoked', $license, $meta);
    }

    public static function passwordChanged(?Model $user = null, array $meta = []): ?AuditLog
    {
        $user ??= Auth::user();

        return static::log('password_changed', $user, $meta);
    }

    private static function resolveSubjectType(?Model $subject): ?string
    {
        return match (true) {
            $subject instanceof Product => 'product',
            $subject instanceof License => 'license',
            $subject instanceof User => 'admin',
            $subject === null => null,
            default => class_basename($subject),
        };
    }

    private static function resolveActorType(?Model $actor): ?string
    {
        return match (true) {
            $actor instanceof User => 'admin',
            $actor === null => null,
            default => class_basename($actor),
        };
    }

    private static function resolveActorName(array $meta, ?Model $actor): ?string
    {
        if (! empty($meta['actor_name'])) {
            return $meta['actor_name'];
        }

        if ($actor) {
            return $actor->email ?? $actor->name ?? class_basename($actor);
        }

        return 'Hệ thống';
    }
}
