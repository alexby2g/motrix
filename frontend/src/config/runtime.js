const quitarBarraFinal = (valor) => String(valor || '').trim().replace(/\/+$/, '')

function detectarAplicacionNativa() {
  if (typeof window === 'undefined') return false

  try {
    if (window.__MOTRIX_NATIVE_APP__ === true) {
      return true
    }

    if (window.Capacitor?.isNativePlatform?.()) {
      return true
    }
  } catch {
    // Continuamos con la detección por origen del WebView.
  }

  const hostLocal = ['localhost', '127.0.0.1']
    .includes(window.location.hostname)

  return (
    hostLocal
    && window.location.protocol === 'https:'
    && !window.location.port
  )
}

const esAplicacionNativa = detectarAplicacionNativa()

const esEntornoLocal = (() => {
  if (typeof window === 'undefined') {
    return true
  }

  if (esAplicacionNativa) {
    return false
  }

  return ['localhost', '127.0.0.1']
    .includes(window.location.hostname)
})()

const origenLocal = (() => {
  if (typeof window === 'undefined') {
    return 'http://127.0.0.1:8000'
  }

  return `${window.location.protocol}//${window.location.hostname}:8000`
})()

const apiProduccion = 'https://motrix-api-h9aq.onrender.com/api'

const reverbProduccion = {
  key: 'motrix-prod-key',
  host: 'motrix-reverb.onrender.com',
  scheme: 'https',
  port: 443
}

export const API_URL = quitarBarraFinal(
  import.meta.env.VITE_API_URL
    || (esEntornoLocal ? `${origenLocal}/api` : apiProduccion)
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

export const REVERB_APP_KEY = String(
  esEntornoLocal
    ? (import.meta.env.VITE_REVERB_APP_KEY || 'motrix-local-key')
    : reverbProduccion.key
).trim()

export const REVERB_HOST = String(
  esEntornoLocal
    ? (import.meta.env.VITE_REVERB_HOST || apiUrl?.hostname || '127.0.0.1')
    : reverbProduccion.host
).trim()

export const REVERB_SCHEME = String(
  esEntornoLocal
    ? (
        import.meta.env.VITE_REVERB_SCHEME
        || (apiUrl?.protocol === 'https:' ? 'https' : 'http')
      )
    : reverbProduccion.scheme
).trim().toLowerCase()

export const REVERB_FORCE_TLS = REVERB_SCHEME === 'https'

const puertoPorDefecto = esEntornoLocal
  ? (REVERB_FORCE_TLS ? 443 : 8080)
  : reverbProduccion.port

const puertoConfigurado = Number(
  esEntornoLocal
    ? (import.meta.env.VITE_REVERB_PORT || puertoPorDefecto)
    : reverbProduccion.port
)

export const REVERB_PORT = Number.isFinite(puertoConfigurado)
  ? puertoConfigurado
  : puertoPorDefecto

export const ES_APLICACION_NATIVA = esAplicacionNativa

export const echoOptions = () => ({
  broadcaster: 'reverb',
  key: REVERB_APP_KEY,
  wsHost: REVERB_HOST,
  wsPort: REVERB_PORT,
  wssPort: REVERB_PORT,
  forceTLS: REVERB_FORCE_TLS,
  disableStats: true,
  enabledTransports: ['ws', 'wss']
})
