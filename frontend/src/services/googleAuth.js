import { api } from '../boot/axios.js'

const SCRIPT_ID = 'motrix-google-identity-services'
const CLIENT_ID = String(
  import.meta.env.VITE_GOOGLE_CLIENT_ID || ''
).trim()

let scriptPromise = null
let inicializado = false
let clientIdInicializado = null
let callbackActivo = null

export function googleClientConfigurado() {
  return CLIENT_ID !== ''
}

export function esContenedorNativo() {
  if (typeof window === 'undefined') return false

  try {
    if (window.Capacitor?.isNativePlatform?.()) {
      return true
    }
  } catch {
    // Si Capacitor no existe, continuamos como navegador web.
  }

  return window.__MOTRIX_NATIVE_APP__ === true
}

function cargarScriptGoogle() {
  if (typeof window === 'undefined') {
    return Promise.reject(
      new Error('Google Identity Services requiere navegador.')
    )
  }

  if (window.google?.accounts?.id) {
    return Promise.resolve()
  }

  if (scriptPromise) return scriptPromise

  scriptPromise = new Promise((resolve, reject) => {
    const existente = document.getElementById(SCRIPT_ID)

    if (existente) {
      existente.addEventListener('load', resolve, { once: true })
      existente.addEventListener(
        'error',
        () => reject(new Error('No se pudo cargar Google Identity Services.')),
        { once: true }
      )
      return
    }

    const script = document.createElement('script')
    script.id = SCRIPT_ID
    script.src = 'https://accounts.google.com/gsi/client?hl=es'
    script.async = true
    script.defer = true
    script.onload = resolve
    script.onerror = () => reject(
      new Error('No se pudo cargar Google Identity Services.')
    )

    document.head.appendChild(script)
  })

  return scriptPromise
}

export async function renderizarBotonGoogle(elemento, callback) {
  if (!elemento || !googleClientConfigurado() || esContenedorNativo()) {
    return false
  }

  await cargarScriptGoogle()

  callbackActivo = callback

  if (!inicializado || clientIdInicializado !== CLIENT_ID) {
    window.google.accounts.id.initialize({
      client_id: CLIENT_ID,
      callback: respuesta => callbackActivo?.(respuesta),
      ux_mode: 'popup',
      auto_select: false
    })

    inicializado = true
    clientIdInicializado = CLIENT_ID
  }

  elemento.innerHTML = ''

  const ancho = Math.min(
    Math.max(elemento.clientWidth || 320, 240),
    400
  )

  window.google.accounts.id.renderButton(
    elemento,
    {
      type: 'standard',
      theme: 'outline',
      size: 'large',
      text: 'continue_with',
      shape: 'pill',
      logo_alignment: 'left',
      width: ancho,
      locale: 'es'
    }
  )

  return true
}

export async function autenticarConGoogle(credential) {
  return api.post('/auth/google', {
    credential,
    device_name: 'MOTRIX Google Web'
  })
}

export function guardarSesionMotrix(data) {
  localStorage.setItem('motrix_token', data.token)
  localStorage.setItem('motrix_user', JSON.stringify(data.user))

  if (data.user?.mototaxista_id) {
    localStorage.setItem(
      'mototaxista_id',
      String(data.user.mototaxista_id)
    )
  } else {
    localStorage.removeItem('mototaxista_id')
  }

  if (data.user?.pasajero_id) {
    localStorage.setItem(
      'pasajero_id',
      String(data.user.pasajero_id)
    )
  } else {
    localStorage.removeItem('pasajero_id')
  }
}

export function guardarGooglePendiente(registrationToken, profile) {
  sessionStorage.removeItem('motrix_google_credential')

  sessionStorage.setItem(
    'motrix_google_registration_token',
    registrationToken
  )

  sessionStorage.setItem(
    'motrix_google_profile',
    JSON.stringify(profile || {})
  )
}

export function obtenerGooglePendiente() {
  const registrationToken = sessionStorage.getItem(
    'motrix_google_registration_token'
  )

  let profile = null

  try {
    profile = JSON.parse(
      sessionStorage.getItem('motrix_google_profile') || 'null'
    )
  } catch {
    profile = null
  }

  return {
    registrationToken,
    profile
  }
}

export function limpiarGooglePendiente() {
  sessionStorage.removeItem('motrix_google_credential')
  sessionStorage.removeItem('motrix_google_registration_token')
  sessionStorage.removeItem('motrix_google_profile')
}
