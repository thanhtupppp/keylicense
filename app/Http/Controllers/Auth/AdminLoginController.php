<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\AuditLogger;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminLoginController extends Controller
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;

    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.admin-login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = $request->input('username');
        $password = $request->input('password');
        $ip = $request->ip();

        $lockoutKey = "admin_lockout:{$username}";
        if (Cache::has($lockoutKey)) {
            AuditLogger::adminLoginFailed([
                'username' => $username,
                'ip' => $ip,
                'reason' => 'Account locked',
                'severity' => 'warning',
                'result' => 'failure',
                'actor_name' => $username,
            ]);

            throw ValidationException::withMessages([
                'username' => ['Tài khoản đã bị khóa do đăng nhập thất bại quá nhiều lần. Vui lòng thử lại sau 15 phút.'],
            ]);
        }

        $user = User::where('email', $username)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            $attemptsKey = "admin_attempts:{$username}";
            $attempts = Cache::get($attemptsKey, 0) + 1;
            Cache::put($attemptsKey, $attempts, now()->addMinutes(self::LOCKOUT_MINUTES));

            if ($attempts >= self::MAX_ATTEMPTS) {
                Cache::put($lockoutKey, true, now()->addMinutes(self::LOCKOUT_MINUTES));
                Cache::forget($attemptsKey);

                AuditLogger::adminLocked([
                    'username' => $username,
                    'ip' => $ip,
                    'attempts' => $attempts,
                    'severity' => 'warning',
                    'result' => 'success',
                    'actor_name' => $username,
                ]);

                throw ValidationException::withMessages([
                    'username' => ['Tài khoản đã bị khóa do đăng nhập thất bại quá nhiều lần. Vui lòng thử lại sau 15 phút.'],
                ]);
            }

            AuditLogger::adminLoginFailed([
                'username' => $username,
                'ip' => $ip,
                'attempts' => $attempts,
                'severity' => 'warning',
                'result' => 'failure',
                'actor_name' => $username,
            ]);

            throw ValidationException::withMessages([
                'username' => ['Thông tin đăng nhập không chính xác.'],
            ]);
        }

        Cache::forget("admin_attempts:{$username}");

        Auth::login($user, $request->boolean('remember'));

        AuditLogger::adminLogin([
            'username' => $username,
            'ip' => $ip,
            'user_id' => $user->id,
            'actor_name' => $user->email,
        ]);

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            AuditLogger::adminLogout([
                'username' => $user->email,
                'ip' => $request->ip(),
                'user_id' => $user->id,
                'actor_name' => $user->email,
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
