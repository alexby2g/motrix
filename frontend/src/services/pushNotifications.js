import { api } from 'src/boot/axios.js'

let oyentesInstalados = false
let registroPendiente = null

function pluginNativo() {
  if (typeof window === 'undefined') return null
  if (!window.Capacitor?.isNativePlatform?.()) return null
  return window.Capacitor?.Plugins?.PushNotifications || null
}

export function inicializarPushMotrix(router) {
  if (registroPendiente) return registroPendiente

  const push = pluginNativo()
  if (!push) return Promise.resolve(false)

  registroPendiente = (async () => {
    const permiso = await push.checkPermissions()
    let recibir = permiso?.receive
    if (recibir === 'prompt' || recibir === 'prompt-with-rationale') {
      recibir = (await push.requestPermissions())?.receive
    }
    if (recibir !== 'granted') return false

    // Android 8+ controla sonido y vibración mediante un canal de avisos.
    // La configuración del celular (silencio/No molestar) sigue prevaleciendo.
    if (typeof push.createChannel === 'function') {
      await push.createChannel({
        id: 'motrix_alertas_fuerte_v2',
        name: 'Alertas de viajes MOTRIX',
        description: 'Solicitudes y novedades de viajes (sonido distintivo)',
        importance: 5,
        visibility: 1,
        vibration: true,
        sound: 'motrix_solicitud.wav'
      })
    }

    if (!oyentesInstalados) {
      await push.addListener('registration', async ({ value }) => {
        if (!value || !localStorage.getItem('motrix_token')) return
        try {
          await api.post('/push/devices', {
            token: value,
            platform: 'android',
            device_name: 'MOTRIX Android'
          })
        } catch (error) {
          console.warn('No se pudo registrar el dispositivo en MOTRIX:', error)
        }
      })
      await push.addListener('registrationError', (error) => {
        console.warn('Error de registro FCM MOTRIX:', error)
      })
      await push.addListener('pushNotificationActionPerformed', ({ notification }) => {
        const ruta = notification?.data?.route
        if (typeof ruta === 'string' && ruta.startsWith('/')) {
          router?.push?.(ruta).catch?.(() => {})
        }
      })
      oyentesInstalados = true
    }

    await push.register()
    return true
  })().catch((error) => {
    console.warn('No se pudieron activar las notificaciones MOTRIX:', error)
    return false
  }).finally(() => {
    registroPendiente = null
  })
  return registroPendiente
}
