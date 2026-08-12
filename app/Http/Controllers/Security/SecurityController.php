<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Keamanan akun mandiri: status & pengelolaan 2FA, ganti kata sandi.
 */
class SecurityController extends Controller
{
    /** Selama impersonasi, pengaturan keamanan milik akun yang diwakili tidak boleh diubah. */
    protected function guardAgainstImpersonation(Request $request): void
    {
        abort_if($request->session()->has('impersonator_id'), 403, 'Pengaturan keamanan tidak dapat diubah saat masuk sebagai pengguna lain.');
    }

    public function index(): Response
    {
        $this->guardAgainstImpersonation(request());

        $user = request()->user();

        return Inertia::render('Security', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'two_factor_enabled' => (bool) $user->two_factor_enabled,
                'two_factor_active' => (bool) $user->two_factor_active,
                'two_factor_setup' => (bool) $user->two_factor_secret,
            ],
        ]);
    }

    public function enableTwoFactor(Request $request): RedirectResponse
    {
        $this->guardAgainstImpersonation($request);

        $user = $request->user();

        $user->update(['two_factor_enabled' => true]);

        return redirect()->route('two-factor.setup');
    }

    public function disableTwoFactor(Request $request): RedirectResponse
    {
        $this->guardAgainstImpersonation($request);

        $user = $request->user();

        if (! $user->two_factor_secret) {
            $user->update(['two_factor_enabled' => false]);

            return back()->with('success', __('common.saved'));
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $google2fa = app('pragmarx.google2fa');

        if (! $google2fa->verifyKey($user->two_factor_secret, $data['code'])) {
            throw ValidationException::withMessages([
                'code' => __('auth.two_factor_code_invalid'),
            ]);
        }

        $user->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
        ]);

        return back()->with('success', __('common.saved'));
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $this->guardAgainstImpersonation($request);

        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Kata sandi saat ini salah.',
            ]);
        }

        $user->update([
            'password' => $data['password'],
            'must_change_password' => false,
        ]);

        return back()->with('success', __('common.updated'));
    }
}
