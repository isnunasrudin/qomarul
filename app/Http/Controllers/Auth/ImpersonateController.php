<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Impersonasi (masuk sebagai) untuk operator:
 * - operator yayasan → semua pengguna
 * - operator satker → GTK pada satker sendiri.
 */
class ImpersonateController extends Controller
{
    public function start(Request $request, User $user): RedirectResponse
    {
        $this->authorize('impersonate', $user);

        $request->session()->put('impersonator_id', $request->user()->id);

        Auth::login($user);
        $request->session()->regenerate();

        if ($user->role->value === 'employee') {
            return redirect()->route('portal.home');
        }

        return redirect()->route('dashboard');
    }

    public function stop(Request $request): RedirectResponse
    {
        $impersonatorId = (int) $request->session()->pull('impersonator_id');

        if (! $impersonatorId) {
            return redirect()->route('dashboard');
        }

        $impersonator = User::findOrFail($impersonatorId);

        Auth::login($impersonator);
        $request->session()->forget('impersonator_id');
        $request->session()->regenerate();

        return redirect()->route('admin.users.index');
    }
}
