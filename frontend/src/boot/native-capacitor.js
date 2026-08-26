import { Geolocation } from '@capacitor/geolocation'
import { Haptics } from '@capacitor/haptics'
import { CapacitorBarcodeScanner } from '@capacitor/barcode-scanner'

let contadorWatch = 0
const watches = new Map()

function opcionesGeolocalizacion(options = {}) {
  return {
    enableHighAccuracy: options.enableHighAccuracy !== false,
    timeout: Number(options.timeout || 15000),
    maximumAge: Number(options.maximumAge || 0),
    minimumUpdateInterval: Number(options.minimumUpdateInterval || 5000),
    interval: Number(options.interval || 5000)
  }
}

async function asegurarPermisoUbicacion() {
  const estado = await Geolocation.checkPermissions()

  if (estado.location === 'granted') {
    return true
  }

  const solicitado = await Geolocation.requestPermissions({
    permissions: ['location']
  })

  return solicitado.location === 'granted'
}

function instalarGeolocalizacionNativa() {
  const geolocation = {
    getCurrentPosition(success, error, options = {}) {
      asegurarPermisoUbicacion()
        .then((permitido) => {
          if (!permitido) {
            const fallo = new Error('Permiso de ubicación denegado.')
            fallo.name = 'NotAllowedError'
            throw fallo
          }

          return Geolocation.getCurrentPosition(
            opcionesGeolocalizacion(options)
          )
        })
        .then((position) => {
          success?.(position)
        })
        .catch((err) => {
          error?.(err)
        })
    },

    watchPosition(success, error, options = {}) {
      const localId = ++contadorWatch

      const registro = {
        nativeId: null,
        cancelado: false
      }

      watches.set(localId, registro)

      asegurarPermisoUbicacion()
        .then((permitido) => {
          if (!permitido) {
            const fallo = new Error('Permiso de ubicación denegado.')
            fallo.name = 'NotAllowedError'
            throw fallo
          }

          return Geolocation.watchPosition(
            opcionesGeolocalizacion(options),
            (position, err) => {
              if (registro.cancelado) return

              if (err) {
                error?.(err)
                return
              }

              if (position) {
                success?.(position)
              }
            }
          )
        })
        .then((nativeId) => {
          registro.nativeId = nativeId

          if (registro.cancelado && nativeId) {
            Geolocation.clearWatch({ id: nativeId }).catch(() => {})
          }
        })
        .catch((err) => {
          if (!registro.cancelado) {
            error?.(err)
          }
        })

      return localId
    },

    clearWatch(localId) {
      const registro = watches.get(Number(localId))
      if (!registro) return

      registro.cancelado = true
      watches.delete(Number(localId))

      if (registro.nativeId) {
        Geolocation.clearWatch({ id: registro.nativeId }).catch(() => {})
      }
    }
  }

  try {
    Object.defineProperty(navigator, 'geolocation', {
      configurable: true,
      enumerable: true,
      value: geolocation
    })
  } catch (error) {
    console.warn('No se pudo reemplazar navigator.geolocation:', error)
  }
}

function instalarHapticos() {
  const vibrar = (pattern = 120) => {
    const valores = Array.isArray(pattern)
      ? pattern.filter((valor) => Number(valor) > 0)
      : [pattern]

    const duracion = Math.min(
      1000,
      Math.max(80, Number(valores[0] || 120))
    )

    Haptics.vibrate({ duration: duracion }).catch(() => {})
    return true
  }

  try {
    Object.defineProperty(navigator, 'vibrate', {
      configurable: true,
      enumerable: true,
      value: vibrar
    })
  } catch {
    window.__MOTRIX_HAPTICS__ = vibrar
  }
}

function instalarEscanerQrNativo() {
  window.__MOTRIX_NATIVE_SCAN_QR__ = async () => {
    const resultado = await CapacitorBarcodeScanner.scanBarcode({
      hint: 0,
      scanInstructions: 'Apunta la cámara al código QR del mototaxista',
      scanButton: false,
      cameraDirection: 1,
      scanOrientation: 3,
      cancelButtonAccessibilityLabel: 'Cancelar escaneo',
      torchButtonOnAccessibilityLabel: 'Apagar linterna',
      torchButtonOffAccessibilityLabel: 'Encender linterna',
      android: {
        scanningLibrary: 'mlkit'
      }
    })

    return String(resultado?.ScanResult || '').trim()
  }
}

export default () => {
  instalarGeolocalizacionNativa()
  instalarHapticos()
  instalarEscanerQrNativo()

  window.__MOTRIX_NATIVE_APP__ = true
}
