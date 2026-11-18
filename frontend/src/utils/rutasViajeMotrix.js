// MOTRIX: rutas para las pantallas de viaje y el enlace publico.
// Los endpoints son servicios comunitarios con limites de uso:
// minimizar peticiones y nunca bloquear el seguimiento GPS si no responden.
import axios from 'axios'

const SERVICIOS = [
  'https://router.project-osrm.org/route/v1/driving',
  'https://routing.openstreetmap.de/routed-car/route/v1/driving'
]
const cacheRutas = new Map()
const VIDA_CACHE_MS = 30000
const TIEMPO_POR_SERVICIO_MS = 5500

function posicionSegura(valor) {
  const lat = Number(Array.isArray(valor) ? valor[0] : valor?.lat)
  const lng = Number(Array.isArray(valor) ? valor[1] : valor?.lng)
  if (!Number.isFinite(lat) || !Number.isFinite(lng)
    || Math.abs(lat) > 90 || Math.abs(lng) > 180 || (lat === 0 && lng === 0)) {
    return null
  }
  return [lat, lng]
}

export async function obtenerRutaCallesMotrix(origen, destino) {
  const a = posicionSegura(origen)
  const b = posicionSegura(destino)
  if (!a || !b) return { disponible: false, motivo: 'Sin coordenadas validas' }

  // Redondeo de ~11 m: reduce peticiones sin ocultar movimientos reales del GPS.
  const clave = [...a, ...b].map(x => x.toFixed(4)).join('|')
  const ahora = Date.now()
  const anterior = cacheRutas.get(clave)
  if (anterior && ahora - anterior.inicio < VIDA_CACHE_MS) return anterior.promesa

  const coordenadas = `${a[1]},${a[0]};${b[1]},${b[0]}`
  const promesa = (async () => {
    for (const base of SERVICIOS) {
      try {
        const respuesta = await axios.get(`${base}/${coordenadas}`, {
          params: { overview: 'full', geometries: 'geojson', steps: false },
          timeout: TIEMPO_POR_SERVICIO_MS
        })
        const ruta = respuesta.data?.routes?.[0]
        const linea = ruta?.geometry
        if (respuesta.data?.code !== 'Ok'
          || linea?.type !== 'LineString'
          || !Array.isArray(linea.coordinates)
          || linea.coordinates.length < 2) continue
        const distancia = Number(ruta.distance)
        const duracion = Number(ruta.duration)
        if (!Number.isFinite(distancia) || distancia < 0
          || !Number.isFinite(duracion) || duracion < 0) continue
        return {
          disponible: true,
          geometria: linea,
          distanciaKm: Number((distancia / 1000).toFixed(2)),
          duracionMin: Math.max(1, Math.round(duracion / 60))
        }
      } catch (error) {
        // Pasar al siguiente proveedor; no interrumpir marcadores ni chat.
        console.warn('Servicio de rutas MOTRIX no disponible:', base, error?.message)
      }
    }
    return { disponible: false, motivo: 'No se pudo calcular la ruta por calles' }
  })()
  cacheRutas.set(clave, { inicio: ahora, promesa })
  if (cacheRutas.size > 60) {
    for (const [key, value] of cacheRutas) {
      if (ahora - value.inicio > VIDA_CACHE_MS) cacheRutas.delete(key)
    }
  }
  return promesa
}
