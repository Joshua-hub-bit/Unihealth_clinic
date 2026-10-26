<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies the clinic session cookie set by the JS auth layer.
 *
 * The JS login flow stores the session in sessionStorage AND writes a
 * short-lived cookie `clinic_role` so the server can gate access.
 * If the cookie is absent the user is redirected to /login.
 *
 * Accepted role values are passed as middleware parameters:
 *   e.g.  clinic.auth:admin
 *         clinic.auth:nurse,nurse_head
 *         clinic.auth:physician,dentist
 */
class ClinicSessionAuth
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $request->cookie('clinic_role');

        // The role cookie is only a routing hint; the API identity cookie is
        // the authenticated session and must still be present.
        if (! $role || ! $request->cookie('clinic_identity')) {
            return redirect('/login')
                ->withCookie(cookie()->forget('clinic_role'))
                ->withCookie(cookie()->forget('clinic_identity'));
        }

        // Role restriction check
        if (! empty($roles) && ! in_array($role, $roles, true)) {
            return redirect('/login');
        }

        return $next($request);
    }
}
