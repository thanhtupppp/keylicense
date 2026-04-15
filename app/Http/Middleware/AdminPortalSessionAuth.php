<?php

namespace App\Http\Middleware;

use App\Models\AdminToken;
use App\Models\AdminTokenAuditLog;
use App\Models\AdminUser;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AdminPortalSessionAuth
{
    private const OBS_EVENT_CODE_AUTH_TOKEN_GUARD_FAIL = 'AUTH_TOKEN_GUARD_FAIL';
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->session()->get('admin_api_token');

        if (! is_string($token) || $token === '') {
            return redirect()->route('admin.portal.login');
        }

        $hash = hash('sha256', $token);

        $adminToken = AdminToken::query()
            ->where('token_hash', $hash)
            ->whereNull('revoked_at')
            ->first();

        if (! $adminToken || now()->greaterThan($adminToken->expires_at)) {
            if ($adminToken && now()->greaterThan($adminToken->expires_at)) {
                if ($this->assertModelOrLog($adminToken, 'expired_session_revoke')) {
                    AdminToken::query()->whereKey($adminToken->id)->update(['revoked_at' => now()]);

                    AdminTokenAuditLog::query()->create([
                        'admin_user_id' => $adminToken->admin_user_id,
                        'admin_token_id' => $adminToken->id,
                        'event' => 'expired',
                        'actor_type' => 'system',
                        'actor_admin_user_id' => null,
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'metadata' => [],
                        'created_at' => now(),
                    ]);
                }
            }

            $request->session()->flush();

            return redirect()->route('admin.portal.login')
                ->withErrors(['email' => 'Phiên đăng nhập đã hết hạn hoặc đã bị đăng xuất trên thiết bị khác.']);
        }

        $admin = AdminUser::query()
            ->whereKey($adminToken->admin_user_id)
            ->where('is_active', true)
            ->first();

        if (! $admin) {
            if ($this->assertModelOrLog($adminToken, 'admin_not_found_revoke')) {
                AdminToken::query()->whereKey($adminToken->id)->update(['revoked_at' => now()]);
            }

            $request->session()->flush();

            return redirect()->route('admin.portal.login')
                ->withErrors(['email' => 'Phiên đăng nhập không hợp lệ, vui lòng đăng nhập lại.']);
        }

        AdminToken::query()->whereKey($adminToken->id)->update([
            'last_activity_at' => now(),
            'last_ip' => $request->ip(),
            'last_user_agent' => $request->userAgent(),
            'expires_at' => now()->addSeconds((int) $request->session()->get('admin_session_timeout', 7200)),
        ]);

        $adminToken = AdminToken::query()->whereKey($adminToken->id)->first();

        if (! $adminToken) {
            $request->session()->flush();

            return redirect()->route('admin.portal.login')
                ->withErrors(['email' => 'Phiên đăng nhập không hợp lệ, vui lòng đăng nhập lại.']);
        }

        // Sliding rotation by activity window.
        $rotateAfter = config('admin_portal.rotate_after_seconds', 900);
        $lastRotatedAt = (int) $request->session()->get('admin_last_rotated_at', now()->timestamp);

        if ((now()->timestamp - $lastRotatedAt) >= $rotateAfter) {
            $newPlain = Str::random(64);

            if (! $this->assertModelOrLog($adminToken, 'sliding_rotate')) {
                return $next($request);
            }

            AdminToken::query()->whereKey($adminToken->id)->update(['token_hash' => hash('sha256', $newPlain)]);

            AdminTokenAuditLog::query()->create([
                'admin_user_id' => $adminToken->admin_user_id,
                'admin_token_id' => $adminToken->id,
                'event' => 'rotate',
                'actor_type' => 'system',
                'actor_admin_user_id' => null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => ['rotate_after_seconds' => $rotateAfter],
                'created_at' => now(),
            ]);

            $admin->forceFill(['api_token' => hash('sha256', $newPlain)])->save();

            $request->session()->put('admin_api_token', $newPlain);
            $request->session()->put('admin_last_rotated_at', now()->timestamp);
        }

        $request->session()->put('admin_last_activity', now()->timestamp);
        $request->session()->put('admin_session_expires_at', $adminToken->expires_at->toISOString());

        return $next($request);
    }

    /**
     * @param  mixed  $candidate
     */
    private function assertModelOrLog(mixed $candidate, string $context, array $meta = []): bool
    {
        if ($candidate instanceof Model) {
            return true;
        }

        Log::warning('Admin portal middleware hardening guard failed: expected Eloquent model.', [
            'event_code' => self::OBS_EVENT_CODE_AUTH_TOKEN_GUARD_FAIL,
            'context' => $context,
            'actual_type' => get_debug_type($candidate),
            'meta' => $meta,
        ]);

        return false;
    }
}
