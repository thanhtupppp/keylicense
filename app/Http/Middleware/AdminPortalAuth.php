<?php

namespace App\Http\Middleware;

use App\Models\AdminToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminPortalAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $sessionToken = $request->session()->get('admin_api_token');
        $headerToken = $request->header('X-Admin-Session');

        $token = is_string($sessionToken) && $sessionToken !== '' ? $sessionToken : null;
        if ($token === null && is_string($headerToken) && $headerToken !== '') {
            $token = $headerToken;
        }

        if (! is_string($token) || $token === '') {
            return redirect()->route('admin.portal.login');
        }

        $tokenExists = AdminToken::query()
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('revoked_at')
            ->exists();

        if (! $tokenExists) {
            return redirect()->route('admin.portal.login');
        }

        if (! $request->session()->has('admin_api_token') && is_string($headerToken) && $headerToken !== '') {
            $request->session()->put('admin_api_token', $headerToken);
        }

        return $next($request);
    }
}
