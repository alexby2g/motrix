// Resolucion conservadora de nombres de calles y barrios de Trinidad.
// Solo utiliza referencias cercanas cuando no existe una zona fiable en OSM.
const METROS_GRADO = 111320

function distanciaMetros(lat, lng, lugar) {
  if (lugar.lat == null || lugar.lng == null) return Infinity
  const la = Number(lugar.lat)
  const lo = Number(lugar.lng)
  if (!Number.isFinite(la) || !Number.isFinite(lo)) return Infinity
  const dy = (lat - la) * METROS_GRADO
  const dx = (lng - lo) * METROS_GRADO * Math.cos(lat * Math.PI / 180)
  return Math.hypot(dx, dy)
}

export function barrioLocalConfiable(lugares, lat, lng) {
  const barrios = lugares
    .filter(lugar => ['Barrio', 'Urbanización'].includes(lugar.tipo))
    .map(lugar => ({ nombre: String(lugar.nombre || '').trim(), metros: distanciaMetros(lat, lng, lugar) }))
    .filter(lugar => lugar.nombre && Number.isFinite(lugar.metros))
    .sort((a, b) => a.metros - b.metros)

  if (!barrios.length) return null

  const primero = barrios[0]
  const segundo = barrios[1]
  // Los puntos del catalogo NO son poligonos. No atribuir un barrio lejano
  // o donde dos referencias compiten por la misma ubicacion.
  if (primero.metros > 400) return null
  const ventaja = segundo ? segundo.metros - primero.metros : Infinity
  if (primero.metros > 100 && ventaja < 120) return null
  if (primero.metros > 300 && ventaja < 180) return null

  return primero.nombre
}

export function direccionOrigenTrinidad(datos, lugares, lat, lng) {
  const a = datos?.address || {}
  const categoria = String(datos?.category || datos?.class || '').toLowerCase()
  const nombre = String(datos?.name || '').trim()
  const calle = String(a.road || a.pedestrian || a.footway || a.path
    || (categoria === 'highway' ? nombre : '') || '').trim()
  const zonaOsm = String(a.neighbourhood || a.suburb || a.quarter
    || a.residential || '').trim()
  const zona = zonaOsm || barrioLocalConfiable(lugares, lat, lng) || ''
  const puntoInteres = String(a.amenity || a.building
    || (nombre && !['highway', 'place', 'boundary'].includes(categoria) && nombre !== calle
      ? nombre : '') || '').trim()

  if (puntoInteres) {
    return [puntoInteres, zona, 'Trinidad'].filter(Boolean).join(', ')
  }
  if (calle || zona) {
    return [calle, zona, 'Trinidad'].filter(Boolean).join(', ')
  }
  return ''
}
