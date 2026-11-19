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
                $phone = preg_replace('/\\D+/', '', $login) ?? '';
                if (str_starts_with($phone, '00591') && strlen($phone) === 13) {
                    $phone = substr($phone, 5);
                } elseif (str_starts_with($phone, '591') && strlen($phone) === 11) {
                    $phone = substr($phone, 3);
                }
                $identifier = preg_match('/^[67][0-9]{7}$/', $phone)
                    ? $phone
                    : $login;

                $ruta = (string) $request->path();
                $solicitud = str_ends_with($ruta, '/solicitar');

                // IP: frena ataques automatizados. Teléfono: limita el
                // envío de SMS incluso desde distintas IPs.
                return [
                    Limit::perHour(30)->by('recuperacion-ip:' . (string) $request->ip()),
                    $solicitud
                        ? Limit::perHour(3)->by('recuperacion-sms:' . hash('sha256', $identifier))
                        : Limit::perHour(15)->by('recuperacion-codigo:' . (string) $request->ip() . '|' . hash('sha256', $identifier)),
                ];
            }
        );
    }
}
