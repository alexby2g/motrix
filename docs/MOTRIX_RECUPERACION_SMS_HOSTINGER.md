# MOTRIX — recuperación segura por SMS en Hostinger

## Qué cambia
- El pasajero y el conductor ingresan **el celular registrado** (+591 o 8 dígitos bolivianos que comienzan por 6 o 7).
- Si el celular pertenece **a una sola cuenta**, MOTRIX envía un código de 6 dígitos al mismo número registrado (no a uno arbitrario enviado por el formulario).
- El código es válido 10 minutos y admite hasta cinco intentos erróneos. La verificación emite una autorización temporal de un solo uso para cambiar contraseña.
- Al terminar se revocan los tokens de inicio de sesión y se desactivan las notificaciones de los dispositivos de esa cuenta.
- No se revela si un número está registrado.
- **Sin un proveedor SMS configurado, la API devuelve 503 y no finge haber enviado un código.**

## Qué instalar (solamente backend)
En `domains/backend.motrixbolivia.com/public_html` reemplazar **únicamente** estos cuatro archivos, después de respaldarlos:
- `app/Http/Controllers/PasswordRecoveryController.php`
- `app/Http/Controllers/AuthController.php` (incluye fecha de ingreso para perfiles)
- `app/Providers/AppServiceProvider.php`
- `config/services.php`

No reemplazar `routes/api.php` (sus rutas de recuperación ya existen), `.env`, `vendor`, `bootstrap` ni la base de datos.

## Configurar el proveedor (credenciales PRIVADAS solo en .env)
Opción A: Twilio Programmable Messaging

```dotenv
MOTRIX_TWILIO_ACCOUNT_SID=AC_TU_ACCOUNT_SID
MOTRIX_TWILIO_AUTH_TOKEN=TU_AUTH_TOKEN_PRIVADO
# Configura UNO de estos dos:
MOTRIX_TWILIO_MESSAGING_SERVICE_SID=MG_TU_SERVICE_SID
# MOTRIX_TWILIO_FROM=+1NUMERO_EMISOR_SMS
```

- El servicio debe admitir **SMS hacia Bolivia (+591)**. Habilita en el proveedor los permisos geográficos internacionales y prueba la entrega en Entel, Tigo y Viva antes de ofrecerla a usuarios.
- Twilio puede aplicar cargos por SMS; consulta su panel.
- No publiques estos valores en GitHub, capturas ni la APK.

Opción B: proveedor propio con webhook autenticado (si ya dispones de uno):

```dotenv
MOTRIX_SMS_WEBHOOK_URL=https://TU_PROVEEDOR_SMS/endpoint-seguro
MOTRIX_SMS_WEBHOOK_TOKEN=TU_TOKEN_PRIVADO
```

Debe aceptar HTTPS POST JSON `{ "to": "+5917XXXXXXX", "message": "...", "purpose": "password_reset" }` y devolver HTTP 2xx al **aceptar realmente** el envío.

**Importante:** Firebase Cloud Messaging (FCM) es para notificaciones de la app, NO envía SMS al número de teléfono.

## Cache y activación
1. Respaldar archivos y base de datos antes de editar.
2. Subir los cuatro PHP a las rutas indicadas, no mover otras carpetas.
3. Guardar las variables en `.env` del backend de Hostinger, nunca en frontend.
4. Si Laravel usa `bootstrap/cache/config.php`, refrescar configuración mediante `php artisan config:clear` usando consola autorizada. Si no hay caché de configuración, los cambios de `.env` se leerán normalmente.
5. Probar con **una cuenta propia** y un teléfono al que tengas acceso.

## Prueba funcional
1. Salir de MOTRIX y abrir «¿Olvidaste tu contraseña?».
2. Introducir el número registrado en la cuenta, solicitar código.
3. Recibir SMS real, introducir seis dígitos, escribir clave nueva de mínimo 8 caracteres.
4. Confirmar que el SMS no se envía a otro teléfono ni a un email arbitrario.
5. Confirmar que la contraseña antigua ya no funciona y que las sesiones previas quedaron cerradas.
6. Probar código erróneo cinco veces, código vencido y reuso del token.
7. Si el SMS no llega, consultar el panel del proveedor; una respuesta 2xx solo confirma que el proveedor **aceptó** la solicitud, no que el operador la entregó.

## Nota de despliegue
La APK nueva contiene la interfaz pero **no instala PHP en Hostinger**. Primero activar y probar el backend/SMS; después instalar la APK. Para el sitio web, publicar el frontend también requiere un despliegue separado.

Documentación oficial: https://www.twilio.com/docs/messaging/api/message-resource y https://www.twilio.com/en-us/guidelines/bo/sms
