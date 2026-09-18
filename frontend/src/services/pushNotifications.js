import { api } from 'src/boot/axios.js'

const TOKEN_KEY = 'motrix_push_token'
let listenersRegistrados = false

function pluginPush() {
  return window?.Capacitor?.Plugins?.PushNotifications || null
}

function plataformaNativa() {
  try {
    return Boolean(window?.Capacitor?.isNativePlatform?.())
  } catch {
    return false
  }
}

function tokenGuardado() {
  try {
    return String(localStorage.getItem(TOKEN_KEY) || '').trim()
  } catch {
    return ''
  }
}

function guardarToken(token) {
  try {
    localStorage.setItem(TOKEN_KEY, token)
  } catch {
    // El registro remoto sigue funcionando aunque el almacenamiento local falle.
  }
}

async function registrarTokenBackend(token) {
  const value = String(token || '').trim()
  if (!value) return false

  await api.post('/push/devices', {
    token: value,
    platform: 'android',
    device_name: 'MOTRIX Android'
  })

  guardarToken(value)
  return true
}

export async function inicializarPushMotrix(router) {
  if (!plataformaNativa()) return false

  const push = pluginPush()
  if (!push) {
    console.info('PushNotifications todavía no está disponible en el contenedor nativo.')
    return false
  }

  try {
    const permisos = await push.checkPermissions()
    let receive = permisos?.receive

    if (receive === 'prompt' || receive === 'prompt-with-rationale') {
      receive = (await push.requestPermissions())?.receive
    }

    if (receive !== 'granted') {
      return false
    }

    /*
     * Si ya existe un token nativo conocido, se vuelve a asociar a la cuenta
     * autenticada actual. Esto es importante cuando dos usuarios usan el mismo
     * teléfono en momentos distintos o después de cerrar e iniciar sesión.
     */
    const previo = tokenGuardado()
    if (previo) {
      try {
        await registrarTokenBackend(previo)
      } catch (error) {
        console.warn('No se pudo reactivar el token push MOTRIX:', error)
      }
    }

    if (!listenersRegistrados) {
      await push.addListener('registration', async (token) => {
        const value = String(token?.value || '').trim()
        if (!value) return

        try {
          await registrarTokenBackend(value)
        } catch (error) {
          console.warn('No se pudo registrar el token push MOTRIX:', error)
        }
      })

      await push.addListener('registrationError', (error) => {
        console.warn('Firebase no pudo registrar notificaciones push:', error)
      })

      await push.addListener('pushNotificationActionPerformed', (evento) => {
        const ruta = evento?.notification?.data?.route
        if (typeof ruta === 'string' && ruta.startsWith('/')) {
          router?.push?.(ruta).catch?.(() => {})
        }
      })

      listenersRegistrados = true
    }

    await push.register()
    return true
  } catch (error) {
    console.warn('No se pudieron inicializar las notificaciones push MOTRIX:', error)
    return false
  }
}

export async function desregistrarPushMotrix() {
  const token = tokenGuardado()
  if (!token) return false

  try {
    await api.delete('/push/devices', {
      data: { token }
    })
    return true
  } catch (error) {
    console.warn('No se pudo desactivar el token push al cerrar sesión:', error)
    return false
  }
}
