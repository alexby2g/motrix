# Integración final MOTRIX

Este paquete está preparado para copiarse **encima de un clon actualizado** del repositorio `alexby2g/motrix`.

Importante:

- No contiene carpetas `.git` ni `node_modules`.
- Incluye `google-services.json` solo para prueba/build local; está ignorado por Git y no debe versionarse.
- Antes de copiar, actualizar la rama remota que se vaya a integrar.
- Después de copiar, revisar `git status` y ejecutar las pruebas antes del commit.
- GitHub Actions necesita el secret `MOTRIX_GOOGLE_SERVICES_JSON_B64` para compilar la APK.

Orden recomendado:

```bash
git checkout v5-suscripciones
git pull origin v5-suscripciones
git checkout -b integracion-final-motrix
# copiar el contenido de este paquete encima del clon
git status
```

Luego ejecutar la calidad, revisar diferencias y recién después hacer commit/push.
