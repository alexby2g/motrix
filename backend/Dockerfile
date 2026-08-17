FROM php:8.4-cli-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libonig-dev \
    && docker-php-ext-install \
        bcmath \
        pcntl \
        mbstring \
        pdo_mysql \
        pdo_pgsql \
        sockets \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

# Render descarga las dependencias desde GitHub durante el build. Reintentamos
# dentro de la misma capa para tolerar errores HTTP 5xx transitorios sin dejar
# fuera de línea una corrección urgente del backend.
RUN set -eux; \
    composer config -g process-timeout 900; \
    intento=1; \
    hasta=5; \
    while [ "$intento" -le "$hasta" ]; do \
        if COMPOSER_MAX_PARALLEL_HTTP=4 composer install \
            --no-dev \
            --no-interaction \
            --no-progress \
            --prefer-dist \
            --optimize-autoloader; then \
            break; \
        fi; \
        if [ "$intento" -eq "$hasta" ]; then \
            echo "Composer falló después de ${hasta} intentos." >&2; \
            exit 1; \
        fi; \
        echo "Composer falló en el intento ${intento}/${hasta}; reintentando..."; \
        intento=$((intento + 1)); \
        sleep 8; \
    done; \
    mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache; \
    chown -R www-data:www-data storage bootstrap/cache

USER www-data

EXPOSE 10000

CMD ["sh", "-c", "php artisan config:cache && php artisan migrate --force && php artisan view:cache && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
