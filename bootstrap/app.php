<?php

use App\Http\Middleware\ClinicIdentity;
use App\Http\Middleware\ClinicSessionAuth;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'clinic.auth' => ClinicSessionAuth::class,
            'clinic.identity' => ClinicIdentity::class,
        ]);

        // These cookies are handled by the clinic auth middleware. The identity
        // cookie is already encrypted by AuthController and manually decrypted
        // by ClinicIdentity, so Laravel must not encrypt or decrypt it again.
        $middleware->encryptCookies(except: [
            'clinic_role',
            'clinic_identity',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
