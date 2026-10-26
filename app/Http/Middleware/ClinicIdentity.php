<?php

namespace App\Http\Middleware;

use App\Models\Staff;
use App\Models\Student;
use Closure;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

class ClinicIdentity
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        try {
            $identityPayload = Crypt::decryptString((string) $request->cookie('clinic_identity'));
        } catch (DecryptException) {
            return $this->authenticationRequired();
        }

        $identity = json_decode($identityPayload, true);
        if (! is_array($identity)) {
            $identityPayload = CookieValuePrefix::validate(
                'clinic_identity',
                $identityPayload,
                [app('encrypter')->getKey()],
            ) ?? $identityPayload;

            try {
                $identity = json_decode(Crypt::decryptString($identityPayload), true);
            } catch (DecryptException) {
                return $this->authenticationRequired();
            }
        }

        if (! is_array($identity) || ! isset($identity['id'], $identity['role'])) {
            return $this->authenticationRequired();
        }

        $role = (string) $identity['role'];
        $user = $role === 'student'
            ? Student::query()->whereKey($identity['id'])->where('is_active', true)->first()
            : Staff::query()->whereKey($identity['id'])->where('is_active', true)->first();

        if (! $user || $user->role !== $role || ($roles !== [] && ! in_array($role, $roles, true))) {
            return response()->json(['error' => 'You are not authorized to access this resource'], 403);
        }

        $request->attributes->set('clinic_identity', [
            'id' => $user->id,
            'role' => $user->role,
            'campus_id' => $user->campus_id,
        ]);

        return $next($request);
    }

    private function authenticationRequired(): Response
    {
        return response()->json(['error' => 'Authentication required'], 401)
            ->withCookie(cookie()->forget('clinic_identity'))
            ->withCookie(cookie()->forget('clinic_role'));
    }
}
