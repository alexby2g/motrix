# Despliegue de MOTRIX

## Arquitectura

- `backend/`: Laravel API + Sanctum.
- `frontend/`: Quasar/Vue (SPA/PWA).
- Neon: PostgreSQL de producción.
- Render `motrix-api`: API Laravel.
- Render `motrix-reverb`: WebSockets Laravel Reverb.
- Vercel: frontend Quasar.

## Desarrollo local

### Backend

1. Crear en Laragon la base `motrix_integrado`.
2. Copiar `backend/.env.example` a `backend/.env`.
3. Ejecutar:

```bash
cd backend
composer install
php artisan key:generate
php artisan migrate
php artisan storage:link
php artisan serve
```

Para tiempo real, en otra terminal:

```bash
php artisan reverb:start --host=0.0.0.0 --port=8080
```

### Frontend

```bash
cd frontend
cp .env.example .env
npm ci
npm run dev
```

## Neon

Crear una base PostgreSQL y copiar la cadena de conexión SSL en `DB_URL` de Render. El backend ya acepta `DB_CONNECTION=pgsql` y `DB_SSLMODE=require`.

No importar el dump MySQL con datos privados. La estructura oficial queda definida por las migraciones Laravel.

## Render

El archivo `render.yaml` define dos servicios Docker:

- `motrix-api`
- `motrix-reverb`

Al crear el Blueprint se deben completar los secretos solicitados:

- `DB_URL`: cadena PostgreSQL de Neon.
- `CORS_ALLOWED_ORIGINS`: URL final del frontend en Vercel cuando esté disponible.
- `REVERB_APP_SECRET`: usar exactamente el mismo valor en `motrix-api` y `motrix-reverb`.

El API ejecuta `php artisan migrate --force` antes de cada despliegue.

### Archivos subidos por usuarios

Render no debe usarse como almacenamiento definitivo para fotos en el plan gratuito: el filesystem del servicio es efímero. Motrix puede desplegar y funcionar, pero antes de producción las imágenes de personas, motocicletas y sindicatos deben moverse a almacenamiento persistente u object storage.

## Vercel

Importar el mismo repositorio y establecer `frontend` como **Root Directory**. Configurar:

```text
VITE_API_URL=https://<motrix-api>.onrender.com/api
VITE_REVERB_APP_KEY=motrix-prod-key
VITE_REVERB_HOST=<motrix-reverb>.onrender.com
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

El proyecto usa Node 24.x y genera la SPA en `dist/spa`.

## APK

La SPA/PWA queda preparada para compartir la misma API. El paso Android se hará desde Quasar + Capacitor una vez validados Render, Neon y Vercel.

## Archivos que nunca se publican

- `.env`
- dumps `.sql` con datos reales
- `vendor/`
- `node_modules/`
- fotos cargadas por usuarios
- sesiones, logs y cachés
