// Puente entre el flujo de viajes existente (API tipo Leaflet) y Google Maps
// nativo de Capacitor 8. La clave Android solo vive en AndroidManifest.xml
// durante la compilacion; nunca se incorpora al bundle JavaScript.
import { registerPlugin } from '@capacitor/core'

let GoogleMapPlugin = null
let secuenciaMapa = 0
let mapasActivos = 0
const eventosGoogleMaps = registerPlugin('CapacitorGoogleMaps')
let estadoCargaNativa = 'No iniciado'

export function estadoCargaGoogleMapsNativo() {
  return estadoCargaNativa
}

export async function prepararMapaNativo() {
  if (typeof window === 'undefined' || !window.__MOTRIX_NATIVE_APP__) return false
  if (GoogleMapPlugin) return true
  try {
    const { GoogleMap } = await import('@capacitor/google-maps')
    GoogleMapPlugin = GoogleMap
    return true
  } catch (error) {
    console.warn('Google Maps nativo no esta disponible:', error)
    return false
  }
}

function coordenada(valor) {
  if (Array.isArray(valor)) return { lat: Number(valor[0]), lng: Number(valor[1]) }
  return { lat: Number(valor?.lat), lng: Number(valor?.lng) }
}

function limites(puntos) {
  const validos = puntos.map(coordenada).filter((p) => Number.isFinite(p.lat) && Number.isFinite(p.lng))
  if (!validos.length) return null
  return {
    southwest: {
      lat: Math.min(...validos.map((p) => p.lat)),
      lng: Math.min(...validos.map((p) => p.lng))
    },
    northeast: {
      lat: Math.max(...validos.map((p) => p.lat)),
      lng: Math.max(...validos.map((p) => p.lng))
    }
  }
}

function tarea(promise) {
  Promise.resolve(promise).catch((error) => console.warn('Google Maps:', error))
}

function tipoMarcador(icono) {
  const html = String(icono?.options?.html || '')
  if (/destino|destination/i.test(html)) return { r: 214, g: 54, b: 71, a: 255 }
  if (/conductor|driver|moto/i.test(html)) return { r: 25, g: 118, b: 210, a: 255 }
  if (/origen|pickup|pasajero/i.test(html)) return { r: 33, g: 186, b: 69, a: 255 }
  return { r: 3, g: 147, b: 80, a: 255 }
}

class MarcadorNativo {
  constructor(valor, opciones = {}) {
    this.coords = coordenada(valor)
    this.opciones = opciones
    this.mapa = null
    this.id = null
    this.texto = ''
    this.eventos = new Map()
    this.cola = Promise.resolve()
    this.eliminado = false
    this.isNative = true
  }

  addTo(mapa) {
    this.mapa = mapa
    mapa.marcadores.add(this)
    this.actualizar()
    return this
  }

  actualizar() {
    this.cola = this.cola.then(async () => {
      if (!this.mapa || this.mapa.eliminado || this.eliminado) return
      if (this.id) {
        this.mapa.idsMarcadores.delete(this.id)
        await this.mapa.instancia.removeMarker(this.id)
        this.id = null
      }
      if (!this.mapa || this.mapa.eliminado || this.eliminado) return
      const id = await this.mapa.instancia.addMarker({
        coordinate: this.coords,
        draggable: Boolean(this.opciones.draggable),
        title: this.texto || 'MOTRIX',
        tintColor: tipoMarcador(this.opciones.icon)
      })
      if (this.eliminado || this.mapa?.eliminado) {
        await this.mapa?.instancia?.removeMarker(id)
        return
      }
      this.id = id
      this.mapa.idsMarcadores.set(id, this)
    }).catch((error) => console.warn('Marcador Google Maps:', error))
  }

  on(nombre, callback) {
    this.eventos.set(nombre, callback)
    return this
  }

  getLatLng() {
    return { ...this.coords }
  }

  setLatLng(valor) {
    this.coords = coordenada(valor)
    this.actualizar()
    return this
  }

  bindPopup(texto) {
    return this.setPopupContent(texto)
  }

  setPopupContent(texto) {
    this.texto = String(texto || '')
    // El SDK muestra el titulo al pulsar el marcador.
    return this
  }

  openPopup() {
    return this
  }

  remove() {
    this.eliminado = true
    this.mapa?.marcadores.delete(this)
    tarea(this.cola.then(async () => {
      if (this.id && this.mapa?.instancia && !this.mapa.eliminado) {
        this.mapa.idsMarcadores.delete(this.id)
        await this.mapa.instancia.removeMarker(this.id)
      }
      this.id = null
    }))
  }
}

class LineaNativa {
  constructor(coords, opciones = {}) {
    this.coords = coords.map(coordenada)
    this.opciones = opciones
    this.mapa = null
    this.ids = []
    this.eliminado = false
    this.cola = Promise.resolve()
  }

  addTo(mapa) {
    this.mapa = mapa
    mapa.lineas.add(this)
    this.cola = mapa.instancia.addPolylines([{
      path: this.coords,
      strokeColor: this.opciones.color || '#158454',
      strokeWeight: this.opciones.weight || 5,
      strokeOpacity: this.opciones.opacity ?? 0.9
    }]).then(async (ids) => {
      this.ids = ids
      if (this.eliminado && !mapa.eliminado) await mapa.instancia.removePolylines(ids)
    }).catch((error) => console.warn('Ruta Google Maps:', error))
    return this
  }

  getBounds() {
    return limites(this.coords)
  }

  remove() {
    this.eliminado = true
    this.mapa?.lineas.delete(this)
    tarea(this.cola.then(async () => {
      if (this.ids.length && this.mapa?.instancia && !this.mapa.eliminado) {
        await this.mapa.instancia.removePolylines(this.ids)
      }
      this.ids = []
    }))
  }
}

class MapaNativo {
  constructor(instancia, elemento) {
    this.instancia = instancia
    this.elemento = elemento
    this.marcadores = new Set()
    this.idsMarcadores = new Map()
    this.lineas = new Set()
    this.eventos = new Map()
    this.eliminado = false
    this.isNative = true
    this.handleCarga = null
    this.temporizadorCarga = null

    tarea(instancia.setOnMapClickListener((evento) => {
      this.eventos.get('click')?.({
        latlng: { lat: evento.latitude, lng: evento.longitude }
      })
    }))
    tarea(instancia.setOnMarkerDragEndListener((evento) => {
      const marcador = this.idsMarcadores.get(evento.markerId)
      if (!marcador) return
      marcador.coords = { lat: evento.latitude, lng: evento.longitude }
      marcador.eventos.get('dragend')?.()
    }))
  }

  setView(valor, zoom) {
    tarea(this.instancia.setCamera({
      coordinate: coordenada(valor),
      ...(Number.isFinite(zoom) ? { zoom } : {}),
      animate: false
    }))
    return this
  }

  on(nombre, callback) {
    this.eventos.set(nombre, callback)
    return this
  }

  fitBounds(bounds, opciones = {}) {
    if (!bounds) return this
    const padding = Array.isArray(opciones.padding) ? opciones.padding[0] : 40
    // Google Maps nativo usa esquinas sudoeste/noreste.
    tarea(this.instancia.fitBounds(bounds, padding))
    return this
  }

  removeLayer(capa) {
    capa?.remove()
    return this
  }

  invalidateSize() {
    // El componente nativo actualiza su posicion con su elemento HTML.
    return this
  }

  remove() {
    if (this.eliminado) return
    this.eliminado = true
    this.marcadores.forEach((marcador) => { marcador.eliminado = true })
    this.lineas.forEach((linea) => { linea.eliminado = true })
    this.idsMarcadores.clear()
    if (this.temporizadorCarga) window.clearTimeout(this.temporizadorCarga)
    tarea(this.handleCarga?.remove())
    tarea(this.instancia.destroy())
    this.elemento.remove()
    mapasActivos = Math.max(0, mapasActivos - 1)
    if (!mapasActivos) document.body.classList.remove('motrix-google-native-map')
  }
}

export async function crearMapaNativo(contenedor) {
  if (!GoogleMapPlugin) throw new Error('Google Maps nativo no esta inicializado')
  const elemento = document.createElement('capacitor-google-map')
  elemento.className = 'motrix-google-map-element'
  contenedor.appendChild(elemento)
  document.body.classList.add('motrix-google-native-map')
  const id = 'motrix-native-' + (++secuenciaMapa)
  let handleCarga = null
  let temporizadorCarga = null
  estadoCargaNativa = 'Iniciando vista de Google Maps'

  try {
    // El SDK informa cuando realmente se cargan las calles, no solo
    // cuando se crea el mapa. El evento se instrumenta en Android CI.
    handleCarga = await eventosGoogleMaps.addListener('onMapLoaded', (evento) => {
      if (evento.mapId !== id) return
      estadoCargaNativa = 'Calles cargadas por Google Maps'
      if (temporizadorCarga) window.clearTimeout(temporizadorCarga)
    })
    const instancia = await GoogleMapPlugin.create({
      id,
      element: elemento,
      apiKey: 'android-native-manifest',
      config: {
        center: { lat: -14.8308, lng: -64.9024 },
        zoom: 14,
        androidLiteMode: false
      },
      forceCreate: true
    })
    mapasActivos += 1
    const mapa = new MapaNativo(instancia, elemento)
    mapa.handleCarga = handleCarga
    if (estadoCargaNativa !== 'Calles cargadas por Google Maps') {
      estadoCargaNativa = 'Vista creada; esperando calles de Google Maps'
    }
    temporizadorCarga = window.setTimeout(() => {
      if (estadoCargaNativa !== 'Calles cargadas por Google Maps') {
        estadoCargaNativa = 'Las calles no se cargaron en 12 segundos'
      }
    }, 12000)
    mapa.temporizadorCarga = temporizadorCarga
    return mapa
  } catch (error) {
    estadoCargaNativa = 'Error al iniciar Google Maps: ' + String(error?.message || error)
    if (temporizadorCarga) window.clearTimeout(temporizadorCarga)
    tarea(handleCarga?.remove())
    elemento.remove()
    if (!mapasActivos) document.body.classList.remove('motrix-google-native-map')
    throw error
  }
}

export function marcadorNativo(coords, opciones) {
  return new MarcadorNativo(coords, opciones)
}

export function lineaNativa(coords, opciones) {
  return new LineaNativa(coords, opciones)
}

export function limitesNativos(coords) {
  return limites(coords)
}
