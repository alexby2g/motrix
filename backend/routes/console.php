<?php

use App\Services\MotrixSubscriptionBillingService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('motrix:check-production', function () {
    $checks = [
        ['APP_ENV=production', app()->environment('production')],
        ['APP_DEBUG=false', config('app.debug') === false],
        ['APP_KEY configurada', filled(config('app.key'))],
        ['APP_URL usa HTTPS', str_starts_with((string) config('app.url'), 'https://')],
        ['Base de datos no es SQLite', config('database.default') !== 'sqlite'],
        ['Queue no usa sync', config('queue.default') !== 'sync'],
        ['Broadcast usa Reverb', config('broadcasting.default') === 'reverb'],
        ['Google Client ID configurado', filled(config('google.client_id'))],
        ['CORS sin wildcard', ! in_array('*', config('cors.allowed_origins', []), true)],
        ['Reverb sin wildcard', ! in_array('*', data_get(config('reverb'), 'apps.apps.0.allowed_origins', []), true)],
    ];

    $fallos = 0;

    foreach ($checks as [$nombre, $ok]) {
        if ($ok) {
            $this->info('OK   ' . $nombre);
        } else {
            $this->error('FAIL ' . $nombre);
            $fallos++;
        }
    }

    if ($fallos > 0) {
        $this->newLine();
        $this->error("MOTRIX no está listo para producción: {$fallos} comprobación(es) fallaron.");
        return 1;
    }

    $this->newLine();
    $this->info('MOTRIX superó las comprobaciones básicas de producción.');

    return 0;
})->purpose('Verifica configuraciones críticas antes de publicar MOTRIX');

Artisan::command('motrix:sync-subscriptions', function () {
    $resultado = app(
        MotrixSubscriptionBillingService::class
    )->sincronizarTodas();

    $this->info(
        'Suscripciones procesadas: '
        . $resultado['procesadas']
    );

    $this->info(
        'Alertas generadas: '
        . $resultado['alertas_creadas']
    );

    if ($resultado['errores'] > 0) {
        $this->warn(
            'Suscripciones con error: '
            . $resultado['errores']
        );
    }

    return $resultado['errores'] > 0
        ? 1
        : 0;
})->purpose(
    'Genera renovaciones, vencimientos y alertas de suscripción MOTRIX'
);

/*
|--------------------------------------------------------------------------
| Automatización V5
|--------------------------------------------------------------------------
|
| En producción el servidor debe ejecutar "php artisan schedule:run"
| cada minuto mediante cron. El trabajo interno se ejecuta una vez al día.
|
*/
Schedule::command(
    'motrix:sync-subscriptions'
)
    ->dailyAt('00:10')
    ->withoutOverlapping();
