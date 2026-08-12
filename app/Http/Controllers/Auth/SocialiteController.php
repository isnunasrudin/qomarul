<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

/**
 * Login via Google (Socialite): email dari akun Google dicocokkan dengan
 * akun SIMQOH yang sudah ada. Jika cocok → login otomatis sebagai user itu.
 */
class SocialiteController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (! config('services.google.client_id')) {
            return redirect()->route('login')->withErrors([
                'login' => 'Login Google belum dikonfigurasi. Hubungi admin.',
            ]);
        }

        return redirect()->away(Socialite::driver('google')->redirect()->getTargetUrl());
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException $e) {
            return redirect()->route('login')->withErrors([
                'login' => 'Sesi login Google tidak valid. Silakan coba lagi.',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')->withErrors([
                'login' => 'Gagal terhubung ke Google. Silakan coba lagi.',
            ]);
        }

        return $this->loginWithGoogle($request, $googleUser);
    }

    protected function loginWithGoogle(Request $request, object $googleUser): RedirectResponse
    {
        $email = strtolower((string) $googleUser->getEmail());

        if ($email === '') {
            return redirect()->route('login')->withErrors([
                'login' => 'Akun Google Anda tidak memiliki email. Gunakan login biasa.',
            ]);
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            return redirect()->route('login')->withErrors([
                'login' => 'Email '.$email.' tidak terdaftar di SIMQOH. Gunakan login biasa atau hubungi admin.',
            ]);
        }

        if (! $user->is_active) {
            return redirect()->route('login')->withErrors([
                'login' => __('auth.inactive'),
            ]);
        }

        $request->session()->put('login.pre_2fa_id', $user->id);
        $request->session()->put('login.remember', false);
        $request->session()->put('login.via', 'google');

        // 2FA efektif hanya setelah setup selesai (secret tersimpan)
        if ($user->two_factor_active) {
            return redirect()->route('two-factor.verify');
        }

        return $this->completeLogin($request, $user);
    }

    protected function completeLogin(Request $request, User $user): RedirectResponse
    {
        Auth::login($user);
        $request->session()->regenerate();

        $user->update(['last_login_at' => now()]);

        if ($user->must_change_password) {
            return redirect()->route('password.change');
        }

        if ($user->role === UserRole::Employee) {
            return redirect()->route('portal.home');
        }

        return redirect()->route('dashboard');
    }
}
