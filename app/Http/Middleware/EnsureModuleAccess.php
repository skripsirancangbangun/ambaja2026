<?php

namespace App\Http\Middleware;

use App\Support\RolePermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        abort_unless(
            RolePermissions::canAccessRoute($user->role, $routeName),
            403,
            'Role akun Anda tidak memiliki akses ke modul ini.'
        );

        return $next($request);
    }
}
