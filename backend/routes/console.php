<?php

use App\Services\MotrixSubscriptionAutomationService;
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

Artisan::command(
    'motrix:sync-subscriptions {--origen=cli}',
    function () {
        $resultado = app(
            MotrixSubscriptionAutomationService::class
        )->ejecutar(
            (string) $this->option('origen')
        );

        if ($resultado['ocupada'] ?? false) {
            $this->warn($resultado['mensaje']);
            return 0;
        }

        $this->info(
            'Estado: '
            . ($resultado['estado'] ?? 'Desconocido')
        );
        $this->info(
            'Suscripciones procesadas: '
            . ($resultado['procesadas'] ?? 0)
        );
        $this->info(
            'Alertas nuevas: '
            . ($resultado['alertas_creadas'] ?? 0)
        );
        $this->info(
            'Alertas publicadas: '
            . ($resultado['alertas_enviadas'] ?? 0)
        );

        if (($resultado['alertas_sin_usuario'] ?? 0) > 0) {
            $this->warn(
                'Alertas pendientes sin usuario conductor: '
                . $resultado['alertas_sin_usuario']
            );
        }

        if (($resultado['errores'] ?? 0) > 0) {
            $this->warn(
                'Errores detectados: '
                . $resultado['errores']
            );
        }

        return ($resultado['errores'] ?? 0) > 0
            ? 1
            : 0;
    }
)->purpose(
    'Sincroniza vencimientos, cuotas y alertas de suscripción MOTRIX'
);

Artisan::command(
    'motrix:publish-subscription-alerts',
    function () {
        $resultado = app(
            MotrixSubscriptionAutomationService::class
        )->publicarAlertasPendientes();

        if ($resultado['ocupada'] ?? false) {
            $this->warn(
                'Ya existe una publicación de alertas MOTRIX en ejecución.'
            );
            return 0;
        }

        $this->info(
            'Alertas publicadas: '
            . $resultado['alertas_enviadas']
        );

        if ($resultado['alertas_sin_usuario'] > 0) {
            $this->warn(
                'Alertas sin usuario conductor: '
                . $resultado['alertas_sin_usuario']
            );
        }

        if ($resultado['errores'] > 0) {
            $this->warn(
                'Errores de publicación: '
                . $resultado['errores']
            );
        }

        return $resultado['errores'] > 0
            ? 1
            : 0;
    }
)->purpose(
    'Reintenta publicar alertas MOTRIX pendientes mediante Reverb'
);

Artisan::command(
    'motrix:automation-status',
    function () {
        $estado = app(
            MotrixSubscriptionAutomationService::class
        )->estado();

        if (! ($estado['configurada'] ?? false)) {
            $this->error($estado['mensaje']);
            return 1;
        }

        $ultima = $estado['ultima_ejecucion'];
        $alertas = $estado['alertas'];

        $this->info('Automatización de Suscripciones MOTRIX');
        $this->line(
            'Última ejecución: '
            . ($ultima?->iniciada_en?->format('Y-m-d H:i:s') ?? 'Sin ejecuciones')
        );
        $this->line(
            'Estado: '
            . ($ultima?->estado ?? 'Sin ejecuciones')
        );
        $this->line(
            'Alertas pendientes de publicar: '
            . $alertas['pendientes_publicar']
        );
        $this->line(
            'Alertas pendientes sin usuario: '
            . $alertas['pendientes_sin_usuario']
        );
        $this->line(
            'Alertas no leídas: '
            . $alertas['no_leidas']
        );

        return 0;
    }
)->purpose(
    'Muestra el estado operativo de la automatización de suscripciones MOTRIX'
);

/*
|--------------------------------------------------------------------------
| Automatización MOTRIX V5.6
|--------------------------------------------------------------------------
|
| En producción el servidor debe ejecutar "php artisan schedule:run"
| cada minuto mediante cron. La sincronización comercial se ejecuta una vez
| al día y las alertas pendientes se reintentan cada diez minutos.
|
*/
Schedule::command(
    'motrix:sync-subscriptions --origen=scheduler'
)
    ->dailyAt('00:10')
    ->timezone('America/La_Paz')
    ->withoutOverlapping(30);

Schedule::command(
    'motrix:publish-subscription-alerts'
)
    ->everyTenMinutes()
    ->withoutOverlapping(5);
