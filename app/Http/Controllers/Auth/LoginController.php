<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::validate([$field => $credentials['login'], 'password' => $credentials['password']])) {
            throw ValidationException::withMessages([
                'login' => __('auth.failed'),
            ]);
        }

        $user = User::where($field, $credentials['login'])->first();

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'login' => __('auth.inactive'),
            ]);
        }

        $request->session()->put('login.pre_2fa_id', $user->id);
        $request->session()->put('login.remember', $request->boolean('remember'));

        if ($user->role === UserRole::FoundationHead) {
            if (! $user->two_factor_secret) {
                return redirect()->route('two-factor.setup');
            }

            return redirect()->route('two-factor.verify');
        }

        return $this->completeLogin($request, $user);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    protected function completeLogin(Request $request, User $user): RedirectResponse
    {
        Auth::login($user, (bool) $request->session()->pull('login.remember'));
        $request->session()->regenerate();

        if ($user->must_change_password) {
            return redirect()->route('password.change');
        }

        return redirect()->intended(route('dashboard'));
    }
}
