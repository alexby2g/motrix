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
    'allowed_origins' => $csv(
        'CORS_ALLOWED_ORIGINS',
        'http://localhost:9000,http://127.0.0.1:9000,capacitor://localhost'
    ),
    'allowed_origins_patterns' => $csv('CORS_ALLOWED_ORIGIN_PATTERNS'),
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
