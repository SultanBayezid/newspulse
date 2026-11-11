<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Allow access when the user's role matches one of the given roles.
     * Super admins always pass.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user !== null, 401);

        $passes = $user->role->isAdmin()
            || in_array($user->role->value, $roles, true);

        abort_unless($passes, 403, 'You do not have permission to perform this action.');

        return $next($request);
    }
}
