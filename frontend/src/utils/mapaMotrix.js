// Motor de mapas de MOTRIX. Mantiene las operaciones existentes de Leaflet
// y permite Google Maps JavaScript cuando hay una clave restringida configurada.
// No se guarda ninguna clave en el repositorio.
import Leaflet from 'leaflet'

const googleKey = String(import.meta.env.VITE_GOOGLE_MAPS_JS_KEY || '').trim()
let googleReady = false
let loadingPromise = null

export function proveedorMapa() {
  return googleReady ? 'Google Maps' : 'OpenStreetMap'
}

export function prepararGoogleMaps() {
  if (googleReady) return Promise.resolve(true)
  if (!googleKey || typeof window === 'undefined') return Promise.resolve(false)
  if (loadingPromise) return loadingPromise

  loadingPromise = new Promise((resolve) => {
    let terminado = false
    const finalizar = (ok) => {
      if (terminado) return
      terminado = true
      googleReady = Boolean(ok && window.google?.maps?.Map)
      resolve(googleReady)
    }
    const nombreCallback = '__motrixGoogleMapsReady'
    window[nombreCallback] = () => finalizar(true)
    const script = document.createElement('script')
    script.async = true
    script.defer = true
    script.src = 'https://maps.googleapis.com/maps/api/js?key='
      + encodeURIComponent(googleKey)
      + '&v=weekly&loading=async&callback=' + nombreCallback
    script.onerror = () => finalizar(false)
    document.head.appendChild(script)
    window.setTimeout(() => finalizar(false), 12000)
  })
  return loadingPromise
}

function punto(coords) {
  if (Array.isArray(coords)) return { lat: Number(coords[0]), lng: Number(coords[1]) }
  return coords
}

function iconoGoogle(opciones) {
  const html = String(opciones?.icon?.options?.html || '')
  const conductor = html.includes('pin-conductor')
  const destino = html.includes('pin-destino')
  const color = conductor ? '#137b45' : destino ? '#d43c4b' : '#159353'
  return {
    path: window.google.maps.SymbolPath.CIRCLE,
    fillColor: color,
    fillOpacity: 1,
    strokeColor: '#ffffff',
    strokeOpacity: 1,
    strokeWeight: 3,
    scale: conductor ? 15 : 12
  }
}

class MarcadorGoogle {
  constructor(coords, opciones = {}) {
    this.coords = punto(coords)
    this.opciones = opciones
    this.marcador = null
    this.mapa = null
    this.popup = null
    this.textoPopup = ''
  }

  addTo(mapa) {
    this.mapa = mapa
    this.marcador = new window.google.maps.Marker({
      map: mapa.instancia,
      position: this.coords,
      draggable: Boolean(this.opciones.draggable),
      icon: iconoGoogle(this.opciones),
      zIndex: this.opciones.draggable ? 20 : 10
    })
    return this
  }

  on(evento, callback) {
    if (this.marcador) {
      this.marcador.addListener(evento, callback)
    }
    return this
  }

  getLatLng() {
    const posicion = this.marcador?.getPosition()
    return posicion
      ? { lat: posicion.lat(), lng: posicion.lng() }
      : this.coords
  }

  setLatLng(coords) {
    this.coords = punto(coords)
    this.marcador?.setPosition(this.coords)
    return this
  }

  bindPopup(texto) {
    this.setPopupContent(texto)
    return this
  }

  setPopupContent(texto) {
    this.textoPopup = String(texto || '')
    if (!this.popup) this.popup = new window.google.maps.InfoWindow()
    const contenido = document.createElement('div')
    contenido.textContent = this.textoPopup
    this.popup.setContent(contenido)
    return this
  }

  openPopup() {
    if (this.popup && this.marcador && this.mapa) {
      this.popup.open({ map: this.mapa.instancia, anchor: this.marcador })
    }
    return this
  }

  remove() {
    this.popup?.close()
    this.marcador?.setMap(null)
    this.marcador = null
  }
}

class LineaGoogle {
  constructor(coords, opciones = {}) {
    this.coords = coords
    this.opciones = opciones
    this.linea = null
  }

  addTo(mapa) {
    this.linea = new window.google.maps.Polyline({
      map: mapa.instancia,
      path: this.coords.map(punto),
      strokeColor: this.opciones.color || '#137b45',
      strokeWeight: this.opciones.weight || 5,
      strokeOpacity: this.opciones.opacity ?? 0.9
    })
    return this
  }

  getBounds() {
    const bounds = new window.google.maps.LatLngBounds()
    this.coords.forEach(c => bounds.extend(punto(c)))
    return bounds
  }

  remove() {
    this.linea?.setMap(null)
    this.linea = null
  }
}

class MapaGoogle {
  constructor(contenedor, opciones = {}) {
    this.instancia = new window.google.maps.Map(contenedor, {
      center: { lat: -14.8308, lng: -64.9024 },
      zoom: 14,
      zoomControl: opciones.zoomControl !== false,
      mapTypeControl: false,
      fullscreenControl: false,
      streetViewControl: false,
      clickableIcons: false,
      gestureHandling: 'greedy'
    })
  }

  setView(coords, zoom) {
    this.instancia.setCenter(punto(coords))
    if (zoom !== undefined) this.instancia.setZoom(zoom)
    return this
  }

  on(evento, callback) {
    this.instancia.addListener(evento, (e) => {
      if (evento === 'click') {
        callback({ latlng: { lat: e.latLng.lat(), lng: e.latLng.lng() } })
      } else {
        callback(e)
      }
    })
    return this
  }

  fitBounds(bounds, opciones = {}) {
    const padding = Array.isArray(opciones.padding) ? opciones.padding[0] : 35
    this.instancia.fitBounds(bounds, padding)
    return this
  }

  removeLayer(layer) {
    layer?.remove()
    return this
  }

  invalidateSize() {
    window.google.maps.event.trigger(this.instancia, 'resize')
    return this
  }

  remove() {
    window.google.maps.event.clearInstanceListeners(this.instancia)
    this.instancia = null
  }
}

const L = {
  map(contenedor, opciones) {
    return googleReady
      ? new MapaGoogle(contenedor, opciones)
      : Leaflet.map(contenedor, opciones)
  },
  tileLayer(url, opciones) {
    return googleReady
      ? { addTo: () => null }
      : Leaflet.tileLayer(url, opciones)
  },
  marker(coords, opciones) {
    return googleReady
      ? new MarcadorGoogle(coords, opciones)
      : Leaflet.marker(coords, opciones)
  },
  divIcon(opciones) {
    return googleReady
      ? { options: opciones }
      : Leaflet.divIcon(opciones)
  },
  geoJSON(geometria, opciones = {}) {
    if (!googleReady) return Leaflet.geoJSON(geometria, opciones)
    const coords = geometria?.coordinates || []
    const puntos = geometria?.type === 'LineString'
      ? coords.map(c => [c[1], c[0]])
      : []
    return new LineaGoogle(puntos, opciones.style || {})
  },
  polyline(coords, opciones) {
    return googleReady
      ? new LineaGoogle(coords, opciones)
      : Leaflet.polyline(coords, opciones)
  },
  latLngBounds(coords) {
    if (!googleReady) return Leaflet.latLngBounds(coords)
    const bounds = new window.google.maps.LatLngBounds()
    coords.forEach(c => bounds.extend(punto(c)))
    return bounds
  }
}

export default L
