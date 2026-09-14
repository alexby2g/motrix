<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /*
         * Límites de seguridad de MOTRIX.
         * Se identifican por usuario cuando existe sesión y por IP
         * para las rutas públicas.
         */
        RateLimiter::for(
            'motrix-login',
            fn (Request $request) => Limit::perMinute(10)
                ->by((string) $request->ip())
        );

        RateLimiter::for(
            'motrix-registro-publico',
            fn (Request $request) => Limit::perHour(10)
                ->by((string) $request->ip())
        );

        RateLimiter::for(
            'motrix-gps',
            fn (Request $request) => Limit::perMinute(30)
                ->by((string) ($request->user()?->id ?? $request->ip()))
        );

        RateLimiter::for(
            'motrix-solicitud',
            fn (Request $request) => Limit::perMinute(10)
                ->by((string) ($request->user()?->id ?? $request->ip()))
        );

        RateLimiter::for(
            'motrix-eliminar-cuenta',
            fn (Request $request) => Limit::perHour(3)
                ->by((string) ($request->user()?->id ?? $request->ip()))
        );


        RateLimiter::for(
            'motrix-password-recovery',
            function (Request $request) {
                $login = mb_strtolower(trim((string) $request->input('login', '')));

                return [
                    Limit::perHour(30)->by((string) $request->ip()),
                    Limit::perHour(12)->by((string) $request->ip() . '|' . hash('sha256', $login)),
                ];
            }
        );
    }
}
