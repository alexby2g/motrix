const quitarBarraFinal = (valor) =>
  String(valor || '').trim().replace(/\/+$/, '')

/*
 * Configuración pública de la APK MOTRIX.
 *
 * Estos valores NO son secretos.
 * La aplicación Android debe apuntar siempre al backend
 * productivo de MOTRIX y nunca al localhost del equipo
 * donde se genera la APK.
 */
const NATIVE_API_URL =
  'https://backend.motrixbolivia.com/api'

const NATIVE_REVERB_APP_KEY =
  'motrix-pilot-key'

const NATIVE_REVERB_HOST =
  'backend.motrixbolivia.com'

const NATIVE_REVERB_PORT = 443

const NATIVE_REVERB_SCHEME =
  'https'

function detectarAplicacionNativa() {
  if (typeof window === 'undefined') {
    return false
  }

  try {
    if (
      window.__MOTRIX_NATIVE_APP__ === true
    ) {
      return true
    }

    if (
      window.Capacitor?.isNativePlatform?.()
    ) {
      return true
    }
  } catch {
    // Continuamos con la detección por origen del WebView.
  }

  /*
   * Capacitor Android utiliza normalmente:
   *
   * https://localhost
   *
   * como origen interno del WebView.
   */
  return (
    ['localhost', '127.0.0.1']
      .includes(window.location.hostname)
    && window.location.protocol === 'https:'
    && !window.location.port
  )
}

const esAplicacionNativa =
  detectarAplicacionNativa()

const esEntornoLocal = (() => {
  if (typeof window === 'undefined') {
    return true
  }

  /*
   * Aunque Capacitor utilice localhost internamente,
   * una APK nunca debe considerarse entorno local.
   */
  if (esAplicacionNativa) {
    return false
  }

  return [
    'localhost',
    '127.0.0.1'
  ].includes(window.location.hostname)
})()

const origenLocal = (() => {
  if (typeof window === 'undefined') {
    return 'http://127.0.0.1:8000'
  }

  return (
    `${window.location.protocol}//`
    + `${window.location.hostname}:8000`
  )
})()

function requerirProduccion(
  nombre,
  valor
) {
  const limpio =
    String(valor || '').trim()

  if (
    !esEntornoLocal
    && !limpio
  ) {
    throw new Error(
      `MOTRIX: falta configurar ${nombre} para el entorno de producción.`
    )
  }

  return limpio
}

/*
 * API
 *
 * APK:
 * siempre Hostinger producción.
 *
 * Web local:
 * localhost.
 *
 * Web producción:
 * variable VITE_API_URL.
 */
const apiConfigurada =
  esAplicacionNativa
    ? NATIVE_API_URL
    : import.meta.env.VITE_API_URL

export const API_URL =
  quitarBarraFinal(
    esAplicacionNativa
      ? NATIVE_API_URL
      : (
          esEntornoLocal
            ? (
                apiConfigurada
                || `${origenLocal}/api`
              )
            : requerirProduccion(
                'VITE_API_URL',
                apiConfigurada
              )
        )
  )

export const API_ORIGIN = (() => {
  try {
    return new URL(API_URL).origin
  } catch {
    return quitarBarraFinal(
      origenLocal
    )
  }
})()

export const BROADCAST_AUTH_URL =
  `${API_URL}/broadcasting/auth`

const apiUrl = (() => {
  try {
    return new URL(API_URL)
  } catch {
    return null
  }
})()

/*
 * REVERB
 *
 * El piloto en Hostinger utiliza polling como respaldo,
 * pero dejamos la configuración pública correcta para
 * evitar que una APK intente usar Reverb local.
 */
const claveReverb =
  esAplicacionNativa
    ? NATIVE_REVERB_APP_KEY
    : (
        esEntornoLocal
          ? (
              import.meta.env
                .VITE_REVERB_APP_KEY
              || 'motrix-local-key'
            )
          : requerirProduccion(
              'VITE_REVERB_APP_KEY',
              import.meta.env
                .VITE_REVERB_APP_KEY
            )
      )

const hostReverb =
  esAplicacionNativa
    ? NATIVE_REVERB_HOST
    : (
        esEntornoLocal
          ? (
              import.meta.env
                .VITE_REVERB_HOST
              || apiUrl?.hostname
              || '127.0.0.1'
            )
          : requerirProduccion(
              'VITE_REVERB_HOST',
              import.meta.env
                .VITE_REVERB_HOST
            )
      )

const esquemaReverb =
  esAplicacionNativa
    ? NATIVE_REVERB_SCHEME
    : String(
        import.meta.env
          .VITE_REVERB_SCHEME
        || (
          esEntornoLocal
            ? (
                apiUrl?.protocol
                  === 'https:'
                  ? 'https'
                  : 'http'
              )
            : 'https'
        )
      )
      .trim()
      .toLowerCase()

export const REVERB_APP_KEY =
  String(claveReverb).trim()

export const REVERB_HOST =
  String(hostReverb).trim()

export const REVERB_SCHEME =
  esquemaReverb

export const REVERB_FORCE_TLS =
  REVERB_SCHEME === 'https'

const puertoConfigurado =
  esAplicacionNativa
    ? NATIVE_REVERB_PORT
    : Number(
        import.meta.env
          .VITE_REVERB_PORT
        || (
          REVERB_FORCE_TLS
            ? 443
            : 8080
        )
      )

export const REVERB_PORT =
  Number.isFinite(
    Number(puertoConfigurado)
  )
    ? Number(puertoConfigurado)
    : (
        REVERB_FORCE_TLS
          ? 443
          : 8080
      )

export const ES_APLICACION_NATIVA =
  esAplicacionNativa

export const echoOptions = () => ({
  broadcaster: 'reverb',
  key: REVERB_APP_KEY,
  wsHost: REVERB_HOST,
  wsPort: REVERB_PORT,
  wssPort: REVERB_PORT,
  forceTLS: REVERB_FORCE_TLS,
  disableStats: true,
  enabledTransports:
    REVERB_FORCE_TLS
      ? ['ws', 'wss']
      : ['ws']
})