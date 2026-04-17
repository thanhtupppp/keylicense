<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPortalCustomerController extends Controller
{
    public function auth(): View
    {
        return view('client.auth');
    }

    public function onboarding(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        return redirect()->route('client.portal')->with('status', sprintf('Onboarding demo đã sẵn sàng cho %s.', $data['email']));
    }

    public function verification(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'verification_code' => ['required', 'string', 'max:32'],
        ]);

        return redirect()->route('client.portal')->with('status', sprintf('Đã xác minh tài khoản %s.', $data['email']));
    }
}
