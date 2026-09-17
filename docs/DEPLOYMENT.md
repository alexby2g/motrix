# Despliegue de MOTRIX

## Arquitectura de producción

- `backend/`: Laravel API + Sanctum en Hostinger.
- `frontend/`: Quasar/Vue en Hostinger.
- Base de datos: MySQL de producción.
- Dominio principal: `motrixbolivia.com`.
- Piloto/frontend: `piloto.motrixbolivia.com`.
- API: `backend.motrixbolivia.com`.
- Android: Capacitor, misma API de Hostinger.
- Push: Firebase Cloud Messaging (FCM HTTP v1).

## Desarrollo local

### Backend

1. Crear la base local `motrix_integrado`.
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

### Frontend

```bash
cd frontend
cp .env.example .env
npm ci
npm run dev
```

## Hostinger

El backend de producción debe usar `backend/.env.production.example` como referencia y mantener los secretos únicamente en el servidor. Después de cada despliegue del backend:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

Para el frontend web, usar `frontend/.env.production.example` y generar:

```bash
cd frontend
npm ci
npm run build
```

La SPA queda en `frontend/dist/spa`.

## Android / APK

La APK no depende de Vercel. El runtime nativo apunta directamente a:

```text
https://backend.motrixbolivia.com/api
```

Para FCM es obligatorio un `google-services.json` válido del package `bo.edu.josecastillo.motrix`. En GitHub Actions se carga mediante `MOTRIX_GOOGLE_SERVICES_JSON_B64` y se valida antes de compilar.

Cada build de `main` usa `github.run_number` como `versionCode`, evitando publicar repetidamente el mismo código de versión.

## Archivos que nunca se publican

- `.env`
- credenciales Firebase Admin
- keystores o contraseñas de firma Android
- dumps `.sql` con datos reales
- `vendor/`
- `node_modules/`
- fotos y archivos privados de usuarios
- sesiones, logs y cachés

## Configuraciones heredadas

`render.yaml` y `frontend/vercel.json` pertenecen al despliegue anterior. No deben considerarse la configuración oficial de producción actual.
