<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

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
