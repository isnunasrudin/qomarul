<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role->value, $roles, true)) {
            abort(403, __('common.forbidden'));
        }

        return $next($request);
    }

    public static function access(?UserRole $role, string ...$allowed): bool
    {
        return $role !== null && in_array($role->value, $allowed, true);
    }
}
