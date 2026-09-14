<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Hash, Password};
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Inertia\Inertia;

class PasswordResetController extends Controller
{
    public function request() { return Inertia::render('Auth/ForgotPassword'); }

    public function email(Request $request)
    {
        $request->validate(['email' => ['required', 'email:rfc']]);
        Password::sendResetLink($request->only('email'));
        return back()->with('success', 'If an account matches that email, a secure password-reset link has been sent.');
    }

    public function reset(Request $request, string $token) { return Inertia::render('Auth/ResetPassword', ['token' => $token, 'email' => $request->query('email', '')]); }

    public function update(Request $request)
    {
        $data = $request->validate(['token' => ['required'], 'email' => ['required', 'email:rfc'], 'password' => ['required', 'confirmed', PasswordRule::min(12)->mixedCase()->numbers()->symbols()]]);
        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });
        if ($status !== Password::PASSWORD_RESET) return back()->withErrors(['email' => __($status)]);
        return redirect()->route('login')->with('success', 'Your password has been reset. Sign in with the new password.');
    }
}
