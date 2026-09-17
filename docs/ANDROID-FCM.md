# MOTRIX Android + Firebase Cloud Messaging

## Por qué puede cerrarse la APK al activar notificaciones

El plugin `@capacitor/push-notifications` usa Firebase Cloud Messaging en Android. El APK debe contener la configuración generada desde un `google-services.json` válido. Si el APK se compila sin esa configuración, el plugin puede estar presente pero Firebase no queda correctamente inicializado.

## Configuración requerida

1. En Firebase debe existir una app Android con package:

```text
bo.edu.josecastillo.motrix
```

2. Descargar `google-services.json` de esa app.
3. Para compilar localmente, copiarlo temporalmente a:

```text
frontend/src-capacitor/android/app/google-services.json
```

4. Validarlo:

```bash
cd frontend/src-capacitor
npm run firebase:check
```

El archivo está ignorado por Git y no debe subirse junto con credenciales privadas del backend.

## GitHub Actions

Convertir el archivo a Base64 y guardar el resultado como secret de GitHub:

```text
MOTRIX_GOOGLE_SERVICES_JSON_B64
```

El workflow de `main` reconstruye el archivo durante CI, valida el package y los campos requeridos, compila la APK y después comprueba que el artefacto contenga:

- `@capacitor/push-notifications`
- `google_app_id`
- `android.permission.POST_NOTIFICATIONS`

Si cualquiera falta, la publicación del APK se detiene.

## Actualizar una APK ya instalada

Cambiar Firebase, plugins Capacitor, permisos Android o cualquier archivo nativo obliga a generar e instalar una APK nueva. Una actualización conserva los datos solo cuando la firma del APK coincide con la versión instalada. Si se usaron APK debug firmadas con claves diferentes, Android puede exigir desinstalar la versión anterior antes de instalar la nueva.

Para distribución estable debe conservarse una única clave de firma Android y nunca almacenarla directamente en Git.

## Firebase del backend (envío real)

`google-services.json` configura la app Android para registrarse en FCM, pero el backend también necesita credenciales de servicio para **enviar** notificaciones. En Hostinger debe configurarse una de estas opciones:

- `MOTRIX_FIREBASE_CREDENTIALS` apuntando a un JSON de cuenta de servicio fuera del repositorio; o
- `MOTRIX_FIREBASE_PROJECT_ID`, `MOTRIX_FIREBASE_CLIENT_EMAIL` y `MOTRIX_FIREBASE_PRIVATE_KEY`.

Después de cambiar variables en producción, limpiar y regenerar la caché de configuración de Laravel. Nunca subir la clave privada ni el JSON de cuenta de servicio a Git.

## Preflight antes de generar APK

```bash
cd frontend/src-capacitor
npm ci
npm run firebase:check
```

Después, desde `frontend`, ejecutar lint/build o dejar que `MOTRIX Quality` lo haga en GitHub Actions.
