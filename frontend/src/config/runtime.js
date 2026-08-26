const quitarBarraFinal = (valor) => String(valor || '').trim().replace(/\/+$/, '')

function detectarAplicacionNativa() {
  if (typeof window === 'undefined') return false

  try {
    if (window.__MOTRIX_NATIVE_APP__ === true) return true
    if (window.Capacitor?.isNativePlatform?.()) return true
  } catch {
    // Continuamos con la detección por origen del WebView.
  }

  return (
    ['localhost', '127.0.0.1'].includes(window.location.hostname)
    && window.location.protocol === 'https:'
    && !window.location.port
  )
}

const esAplicacionNativa = detectarAplicacionNativa()

const esEntornoLocal = (() => {
  if (typeof window === 'undefined') return true
  if (esAplicacionNativa) return false

  return ['localhost', '127.0.0.1']
    .includes(window.location.hostname)
})()

const origenLocal = (() => {
  if (typeof window === 'undefined') return 'http://127.0.0.1:8000'
  return `${window.location.protocol}//${window.location.hostname}:8000`
})()

function requerirProduccion(nombre, valor) {
  const limpio = String(valor || '').trim()

  if (!esEntornoLocal && !limpio) {
    throw new Error(
      `MOTRIX: falta configurar ${nombre} para el entorno de producción.`
    )
  }

  return limpio
}

const apiConfigurada = import.meta.env.VITE_API_URL

export const API_URL = quitarBarraFinal(
  esEntornoLocal
    ? (apiConfigurada || `${origenLocal}/api`)
    : requerirProduccion('VITE_API_URL', apiConfigurada)
)

export const API_ORIGIN = (() => {
  try {
    return new URL(API_URL).origin
  } catch {
    return quitarBarraFinal(origenLocal)
  }
})()

export const BROADCAST_AUTH_URL = `${API_URL}/broadcasting/auth`

const apiUrl = (() => {
  try {
    return new URL(API_URL)
  } catch {
    return null
  }
})()

const claveReverb = esEntornoLocal
  ? (import.meta.env.VITE_REVERB_APP_KEY || 'motrix-local-key')
  : requerirProduccion(
      'VITE_REVERB_APP_KEY',
      import.meta.env.VITE_REVERB_APP_KEY
    )

const hostReverb = esEntornoLocal
  ? (import.meta.env.VITE_REVERB_HOST || apiUrl?.hostname || '127.0.0.1')
  : requerirProduccion(
      'VITE_REVERB_HOST',
      import.meta.env.VITE_REVERB_HOST
    )

const esquemaReverb = String(
  import.meta.env.VITE_REVERB_SCHEME
  || (esEntornoLocal ? (apiUrl?.protocol === 'https:' ? 'https' : 'http') : 'https')
).trim().toLowerCase()

export const REVERB_APP_KEY = String(claveReverb).trim()
export const REVERB_HOST = String(hostReverb).trim()
export const REVERB_SCHEME = esquemaReverb
export const REVERB_FORCE_TLS = REVERB_SCHEME === 'https'

const puertoConfigurado = Number(
  import.meta.env.VITE_REVERB_PORT
  || (REVERB_FORCE_TLS ? 443 : 8080)
)

export const REVERB_PORT = Number.isFinite(puertoConfigurado)
  ? puertoConfigurado
  : (REVERB_FORCE_TLS ? 443 : 8080)

export const ES_APLICACION_NATIVA = esAplicacionNativa

export const echoOptions = () => ({
  broadcaster: 'reverb',
  key: REVERB_APP_KEY,
  wsHost: REVERB_HOST,
  wsPort: REVERB_PORT,
  wssPort: REVERB_PORT,
  forceTLS: REVERB_FORCE_TLS,
  disableStats: true,
  enabledTransports: REVERB_FORCE_TLS ? ['wss'] : ['ws']
})
