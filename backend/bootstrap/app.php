<?php

use App\Http\Middleware\RoleMiddleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        /*
         * Toda ruta /api/* debe responder JSON incluso cuando la
         * petición provenga directamente del navegador y no incluya
         * Accept: application/json.
         *
         * Así una sesión inexistente o vencida devuelve 401 JSON
         * en lugar de intentar redirigir a una ruta web llamada login.
         */
        $exceptions->shouldRenderJsonWhen(
            fn (
                Request $request,
                \Throwable $exception
            ): bool =>
                $request->is('api/*')
                || $request->expectsJson()
        );


        /*
         * Mensaje uniforme para clientes web, PWA y Android.
         */
        $exceptions->render(
            function (
                AuthenticationException $exception,
                Request $request
            ) {
                if (! $request->is('api/*')) {
                    return null;
                }

                return response()->json([
                    'message' =>
                        'No autenticado.',
                ], 401);
            }
        );
    })->create();
