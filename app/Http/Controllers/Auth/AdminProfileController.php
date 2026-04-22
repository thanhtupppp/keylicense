<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();

        $initials = $this->initialsFor($user->name ?? $user->email ?? 'A');

        return view('admin.profile', [
            'user' => $user,
            'initials' => $initials,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['required', 'current_password'],
            'password' => ['nullable', 'string', Password::min(12)->mixedCase()->letters()->numbers()->symbols(), 'confirmed'],
        ], [
            'email.unique' => 'Email này đã được sử dụng bởi tài khoản khác.',
            'password.min' => 'Mật khẩu mới phải có ít nhất 12 ký tự.',
            'password.mixed' => 'Mật khẩu mới nên có cả chữ hoa và chữ thường.',
            'password.letters' => 'Mật khẩu mới phải chứa chữ cái.',
            'password.numbers' => 'Mật khẩu mới phải chứa số.',
            'password.symbols' => 'Mật khẩu mới phải chứa ký tự đặc biệt.',
            'current_password.current_password' => 'Mật khẩu hiện tại không đúng.',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        Auth::setUser($user);
        $request->session()->put('login_web_' . Auth::id(), true);

        return back()->with('success', 'Cập nhật hồ sơ quản trị thành công.');
    }

    private function initialsFor(string $value): string
    {
        $parts = preg_split('/\s+/', trim($value)) ?: [];
        $initials = collect($parts)
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : 'A';
    }
}
