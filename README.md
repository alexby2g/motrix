# MOTRIX

Sistema independiente de gestión de mototaxis.

## Estructura

```text
motrix/
├── backend/      Laravel 12 / API / Sanctum / Reverb
├── frontend/     Quasar / Vue / SPA / PWA
├── docs/         documentación de despliegue
└── render.yaml   Blueprint para Render
```

## Base de datos

- Desarrollo local: MySQL en Laragon, base `motrix_integrado`.
- Producción: PostgreSQL en Neon.
- La estructura oficial se mantiene mediante migraciones Laravel.
- Los dumps SQL con datos personales, tokens o credenciales no forman parte del repositorio.

## Publicar este paquete en GitHub

En Windows, extrae el ZIP y ejecuta `SUBIR_A_GITHUB.bat`. El asistente conserva el historial remoto de `alexby2g/motrix`, crea un commit con el código limpio y lo publica en `main`.

## Despliegue objetivo

```text
Vercel (Quasar)
       |
       v
Render API (Laravel) ----> Neon PostgreSQL
       |
       v
Render Reverb (WebSockets)
```

Consulta [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) para los pasos de instalación y despliegue.
