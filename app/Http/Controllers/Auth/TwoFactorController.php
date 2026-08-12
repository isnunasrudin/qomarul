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

class TwoFactorController extends Controller
{
    public function setup(): Response
    {
        $user = $this->pendingUser();

        $secret = $user->two_factor_secret ?? app('pragmarx.google2fa')->generateSecretKey();

        $qrCode = app('pragmarx.google2fa')->getQRCodeInline(
            'SIMQOH Qomarul Hidayah',
            $user->email,
            $secret,
        );

        return Inertia::render('Auth/TwoFactorSetup', [
            'qrCode' => $qrCode,
            'secret' => $secret,
            'already_enabled' => (bool) $user->two_factor_secret,
        ]);
    }

    public function verify(): Response
    {
        $this->pendingUser();

        return Inertia::render('Auth/TwoFactorVerify');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $user = $this->pendingUser();

        $data = $request->validate([
            'secret' => ['required', 'string'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $google2fa = app('pragmarx.google2fa');

        if (! $google2fa->verifyKey($data['secret'], $data['code'])) {
            throw ValidationException::withMessages([
                'code' => __('auth.two_factor_code_invalid'),
            ]);
        }

        $user->update([
            'two_factor_secret' => $data['secret'],
        ]);

        return $this->finishLogin($request);
    }

    public function challenge(Request $request): RedirectResponse
    {
        $user = $this->pendingUser();

        $data = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $google2fa = app('pragmarx.google2fa');

        if (! $google2fa->verifyKey($user->two_factor_secret, $data['code'])) {
            throw ValidationException::withMessages([
                'code' => __('auth.two_factor_code_invalid'),
            ]);
        }

        return $this->finishLogin($request);
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->user()->update([
            'two_factor_secret' => null,
        ]);

        return back()->with('success', __('common.saved'));
    }

    protected function finishLogin(Request $request): RedirectResponse
    {
        $user = $this->pendingUser();

        $remember = (bool) $request->session()->pull('login.remember');
        $request->session()->forget('login.pre_2fa_id');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    protected function pendingUser(): User
    {
        $userId = session('login.pre_2fa_id');

        if (! $userId) {
            abort(403, __('common.unauthorized'));
        }

        $user = User::findOrFail($userId);

        if ($user->role !== UserRole::FoundationHead) {
            abort(403, __('common.forbidden'));
        }

        return $user;
    }
}
