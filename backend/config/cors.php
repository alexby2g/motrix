<?php

$csv = static function (string $key, string $default = ''): array {
    return array_values(array_filter(array_map(
        static fn (string $value): string => trim($value),
        explode(',', (string) env($key, $default))
    )));
};

return [
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
    ],

    'allowed_methods' => ['*'],

    // La variable de Render sigue teniendo prioridad. La URL de producción
    // queda también como respaldo para evitar que una configuración de entorno
    // ausente deje bloqueado el frontend de Vercel por CORS.
    'allowed_origins' => $csv(
        'CORS_ALLOWED_ORIGINS',
        'https://motrix-nu.vercel.app,http://localhost:9000,http://127.0.0.1:9000,capacitor://localhost'
    ),

    // Permite previews HTTPS de Vercel sin abrir CORS a cualquier origen.
    'allowed_origins_patterns' => $csv(
        'CORS_ALLOWED_ORIGIN_PATTERNS',
        '^https://motrix(?:-[a-z0-9-]+)?\\.vercel\\.app$'
    ),

    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 86400,
    'supports_credentials' => false,
];
