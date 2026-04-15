<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AdminToken;
use App\Models\AdminTokenAuditLog;
use App\Services\Admin\AdminLoginService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AdminPortalAuthController extends Controller
{
    private const OBS_EVENT_CODE_AUTH_TOKEN_GUARD_FAIL = 'AUTH_TOKEN_GUARD_FAIL';
    public function showLogin(Request $request): View|RedirectResponse
    {
        if ($request->session()->has('admin_api_token')) {
            return redirect()->route('admin.portal.dashboard');
        }

        return view('admin.login');
    }

    public function login(Request $request, AdminLoginService $adminLoginService): RedirectResponse
    {
        $payload = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $remember = (bool) ($payload['remember'] ?? false);
        $deviceKey = hash('sha256', (string) $request->userAgent().'|'.(string) $request->ip());
        $result = $adminLoginService->login(
            email: $payload['email'],
            password: $payload['password'],
            remember: $remember,
            deviceKey: $deviceKey,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        if (isset($result['error'])) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => $result['error']['message']]);
        }

        $request->session()->regenerate();
        $request->session()->put('admin_api_token', $result['token']);
        $request->session()->put('admin_profile', $result['admin']);
        $request->session()->put('admin_token_id', $result['token_id']);
        $request->session()->put('admin_session_timeout', $result['expires_in']);
        $request->session()->put('admin_last_activity', now()->timestamp);
        $request->session()->put('admin_session_expires_at', $result['expires_at']);
        $request->session()->put('admin_last_rotated_at', now()->timestamp);

        if ($remember) {
            $request->session()->put('admin_remember', true);
        } else {
            $request->session()->forget('admin_remember');
        }

        if (($result['kicked_count'] ?? 0) > 0) {
            $request->session()->flash(
                'admin_warning',
                'Đăng nhập thành công. '.($result['kicked_count']).' phiên cũ đã bị đăng xuất do vượt giới hạn thiết bị.'
            );
        }

        return redirect()->route('admin.portal.dashboard');
    }

    public function dashboard(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('admin_api_token')) {
            return redirect()->route('admin.portal.login');
        }

        return view('admin.dashboard', [
            'admin' => $request->session()->get('admin_profile', []),
            'token' => $request->session()->get('admin_api_token'),
            'session_expires_at' => $request->session()->get('admin_session_expires_at'),
            'session_timeout' => $request->session()->get('admin_session_timeout', 7200),
        ]);
    }

    public function sessions(Request $request): View|RedirectResponse
    {
        $admin = $request->session()->get('admin_profile', []);
        $adminId = data_get($admin, 'id');

        if (! is_string($adminId) || $adminId === '') {
            return redirect()->route('admin.portal.login');
        }

        $tokens = AdminToken::query()
            ->where('admin_user_id', $adminId)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('last_activity_at')
            ->get();

        $logs = AdminTokenAuditLog::query()
            ->where('admin_user_id', $adminId)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        return view('admin.sessions', [
            'admin' => $admin,
            'tokens' => $tokens,
            'logs' => $logs,
            'current_token_id' => $request->session()->get('admin_token_id'),
        ]);
    }

    public function revokeSession(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'token_id' => ['required', 'uuid'],
        ]);

        $admin = $request->session()->get('admin_profile', []);
        $adminId = data_get($admin, 'id');

        if (! is_string($adminId) || $adminId === '') {
            return redirect()->route('admin.portal.login');
        }

        $token = AdminToken::query()
            ->where('id', $payload['token_id'])
            ->where('admin_user_id', $adminId)
            ->whereNull('revoked_at')
            ->first();

        if ($token) {
            if (! $this->assertModelOrLog($token, 'manual_single_revoke', ['admin_id' => $adminId])) {
                return redirect()->route('admin.portal.sessions')
                    ->withErrors(['email' => 'Không thể thu hồi phiên do dữ liệu không hợp lệ.']);
            }

            AdminToken::query()->whereKey($token->id)->update(['revoked_at' => now()]);

            AdminTokenAuditLog::query()->create([
                'admin_user_id' => $adminId,
                'admin_token_id' => $token->id,
                'event' => 'revoke',
                'actor_type' => 'admin',
                'actor_admin_user_id' => $adminId,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => ['source' => 'manual_single_revoke'],
                'created_at' => now(),
            ]);
        }

        if ($request->session()->get('admin_token_id') === $payload['token_id']) {
            $request->session()->flush();

            return redirect()->route('admin.portal.login')
                ->withErrors(['email' => 'Bạn đã thu hồi phiên hiện tại. Vui lòng đăng nhập lại.']);
        }

        return redirect()->route('admin.portal.sessions')->with('status', 'Đã thu hồi phiên đăng nhập.');
    }

    public function revokeAllExceptCurrent(Request $request): RedirectResponse
    {
        $admin = $request->session()->get('admin_profile', []);
        $adminId = data_get($admin, 'id');
        $currentTokenId = $request->session()->get('admin_token_id');

        if (! is_string($adminId) || $adminId === '' || ! is_string($currentTokenId) || $currentTokenId === '') {
            return redirect()->route('admin.portal.login');
        }

        $tokens = AdminToken::query()
            ->where('admin_user_id', $adminId)
            ->whereNull('revoked_at')
            ->where('id', '!=', $currentTokenId)
            ->get();

        $count = 0;

        foreach ($tokens as $token) {
            if (! $this->assertModelOrLog($token, 'manual_bulk_revoke', ['admin_id' => $adminId])) {
                continue;
            }

            AdminToken::query()->whereKey($token->id)->update(['revoked_at' => now()]);
            $count++;

            AdminTokenAuditLog::query()->create([
                'admin_user_id' => $adminId,
                'admin_token_id' => $token->id,
                'event' => 'revoke_all_except_current',
                'actor_type' => 'admin',
                'actor_admin_user_id' => $adminId,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => ['source' => 'manual_bulk_revoke'],
                'created_at' => now(),
            ]);
        }

        return redirect()->route('admin.portal.sessions')
            ->with('status', "Đã thu hồi {$count} phiên khác.");
    }

    public function logout(Request $request, AdminLoginService $adminLoginService): RedirectResponse
    {
        $currentToken = $request->session()->get('admin_api_token');
        $adminId = data_get($request->session()->get('admin_profile', []), 'id');

        if (\is_string($currentToken) && $currentToken !== '') {
            $adminLoginService->revokeByPlainToken(
                plainToken: $currentToken,
                actorAdminUserId: is_string($adminId) ? $adminId : null,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                event: 'revoke'
            );
        }

        $request->session()->forget([
            'admin_api_token',
            'admin_profile',
            'admin_token_id',
            'admin_session_timeout',
            'admin_last_activity',
            'admin_session_expires_at',
            'admin_remember',
        ]);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.portal.login');
    }

    /**
     * @param  mixed  $candidate
     */
    private function assertModelOrLog(mixed $candidate, string $context, array $meta = []): bool
    {
        if ($candidate instanceof Model) {
            return true;
        }

        Log::warning('Admin portal hardening guard failed: expected Eloquent model.', [
            'event_code' => self::OBS_EVENT_CODE_AUTH_TOKEN_GUARD_FAIL,
            'context' => $context,
            'actual_type' => get_debug_type($candidate),
            'meta' => $meta,
        ]);

        return false;
    }
}
