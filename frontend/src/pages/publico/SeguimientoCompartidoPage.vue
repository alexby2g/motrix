<template>
  <div class="shared-page q-pa-md q-pa-lg-md">
    <div class="shared-shell q-mx-auto">
      <q-card class="shadow-2 overflow-hidden">
        <q-card-section class="shared-header text-white">
          <div class="row items-center no-wrap">
            <q-avatar color="white" text-color="green-9" icon="share_location" size="52px" class="q-mr-md" />
            <div class="col">
              <div class="text-h6 text-weight-bold">Seguimiento compartido MOTRIX</div>
              <div class="text-caption text-green-1">Enlace temporal de seguridad del viaje</div>
            </div>
            <q-btn flat round icon="refresh" color="white" :loading="cargando" @click="cargar" />
          </div>
        </q-card-section>

        <q-linear-progress v-if="cargando" indeterminate color="green-8" />

        <q-card-section v-if="error" class="q-pa-lg">
          <q-banner rounded class="bg-red-1 text-red-10">
            <template #avatar><q-icon name="link_off" color="negative" /></template>
            {{ error }}
          </q-banner>
        </q-card-section>

        <template v-else-if="datos">
          <q-card-section class="q-pa-lg">
            <div class="row q-col-gutter-lg">
              <div class="col-12 col-md-7">
                <div class="shared-map-wrap">
                  <div ref="mapaRef" class="shared-map" />
                  <div class="shared-map-status">
                    <q-icon name="two_wheeler" color="positive" size="25px" />
                    <div class="col min-width-zero">
                      <strong>{{ tituloEstado }}</strong>
                      <div class="text-caption">{{ datos.seguimiento_activo ? 'Ubicación actualizada durante el viaje' : 'Seguimiento finalizado' }}</div>
                    </div>
                    <q-btn dense round flat icon="my_location" color="green-8"
                      aria-label="Centrar recorrido" @click="centrarMapa" />
                  </div>
                  <div class="shared-map-legend">
                    <span><i class="shared-dot shared-dot--blue" /> Conductor</span>
                    <span><i class="shared-dot shared-dot--green" /> Recogida</span>
                    <span><i class="shared-dot shared-dot--red" /> Destino</span>
                  </div>
                  <div v-if="datos.seguimiento_activo && !hayGps" class="shared-map-notice">
                    Esperando la próxima ubicación GPS del conductor.
                  </div>
                </div>
                <div class="shared-map-metrics">
                  <span><q-icon name="route" color="positive" /> {{ distanciaRutaTexto }}</span>
                  <span><q-icon name="schedule" color="orange-8" /> {{ tiempoRutaTexto }}</span>
                </div>
                <div class="text-caption text-grey-6 q-mt-sm">
                  La ubicación del conductor solo se comparte mientras el viaje está activo.
                </div>
              </div>

              <div class="col-12 col-md-5">
                <div class="row items-center no-wrap q-mb-md">
                  <q-avatar
                    size="72px"
                    color="green-1"
                    text-color="green-9"
                    class="cursor-pointer q-mr-md"
                    @click="abrirFoto"
                  >
                    <img v-if="fotoUrl" :src="fotoUrl" :alt="nombreConductor">
                    <q-icon v-else name="two_wheeler" size="36px" />
                  </q-avatar>
                  <div class="col min-width-zero">
                    <div class="text-caption text-grey-6">Mototaxista</div>
                    <div class="text-subtitle1 text-weight-bold ellipsis">{{ nombreConductor }}</div>
                    <div v-if="totalCalificaciones > 0" class="row items-center no-wrap q-gutter-xs q-mt-xs">
                      <q-rating :model-value="promedioCalificacion" readonly size="18px" color="amber-7" icon="star_border" icon-selected="star" />
                      <span class="text-caption text-weight-bold">{{ promedioCalificacion.toFixed(1) }}</span>
                      <span class="text-caption text-grey-6">({{ totalCalificaciones }})</span>
                    </div>
                    <div v-else class="text-caption text-grey-6">Sin calificaciones</div>
                  </div>
                </div>

                <q-list bordered separator class="rounded-borders">
                  <q-item>
                    <q-item-section avatar><q-icon name="route" color="green-8" /></q-item-section>
                    <q-item-section><q-item-label caption>Estado</q-item-label><q-item-label class="text-weight-bold">{{ viaje.estado }}</q-item-label></q-item-section>
                  </q-item>
                  <q-item>
                    <q-item-section avatar><q-icon name="radio_button_checked" color="positive" /></q-item-section>
                    <q-item-section><q-item-label caption>Origen</q-item-label><q-item-label>{{ viaje.origen || 'No registrado' }}</q-item-label></q-item-section>
                  </q-item>
                  <q-item>
                    <q-item-section avatar><q-icon name="location_on" color="negative" /></q-item-section>
                    <q-item-section><q-item-label caption>Destino</q-item-label><q-item-label>{{ viaje.destino || 'No registrado' }}</q-item-label></q-item-section>
                  </q-item>
                  <q-item v-if="conductor?.nro_chaleco">
                    <q-item-section avatar><q-icon name="badge" color="green-8" /></q-item-section>
                    <q-item-section><q-item-label caption>Chaleco</q-item-label><q-item-label>{{ conductor.nro_chaleco }}</q-item-label></q-item-section>
                  </q-item>
                  <q-item v-if="conductor?.motocicleta?.placa">
                    <q-item-section avatar><q-icon name="two_wheeler" color="green-8" /></q-item-section>
                    <q-item-section><q-item-label caption>Motocicleta</q-item-label><q-item-label>{{ conductor.motocicleta.placa }} · {{ conductor.motocicleta.modelo || 'Modelo no registrado' }} · {{ conductor.motocicleta.color || 'Color no registrado' }}</q-item-label></q-item-section>
                  </q-item>
                </q-list>

                <q-banner v-if="!datos.seguimiento_activo" rounded class="bg-grey-2 text-grey-8 q-mt-md">
                  <template #avatar><q-icon name="location_off" /></template>
                  El viaje ya no está activo. La ubicación en vivo dejó de compartirse.
                </q-banner>
              </div>
            </div>
          </q-card-section>
        </template>
      </q-card>
    </div>

    <PhotoViewerDialog v-model="visorFoto" :src="fotoUrl" :title="nombreConductor" />
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import L from 'leaflet'
import { obtenerRutaCallesMotrix } from 'src/utils/rutasViajeMotrix.js'
import 'leaflet/dist/leaflet.css'
import { api } from 'src/boot/axios.js'
import PhotoViewerDialog from 'src/components/PhotoViewerDialog.vue'

const route = useRoute()
const cargando = ref(false)
const error = ref('')
const datos = ref(null)
const mapaRef = ref(null)
const visorFoto = ref(false)
let mapa = null
const marcadores = new Map()
let lineaRuta = null
let claveRutaDibujada = ''
let ultimaRutaCalculadaEn = 0
let rutaEnCurso = false
let inicialCentrada = false
let numeroCarga = 0
let intervalo = null
const distanciaRutaKm = ref(null)
const tiempoRutaMin = ref(null)
const rutaDirecta = ref(false)
const calculandoRuta = ref(false)

const viaje = computed(() => datos.value?.viaje || {})
const conductor = computed(() => datos.value?.conductor || null)
const nombreConductor = computed(() => conductor.value?.nombre || 'Mototaxista MOTRIX')
const promedioCalificacion = computed(() => Number(conductor.value?.promedio_calificacion || 0))
const totalCalificaciones = computed(() => Number(conductor.value?.total_calificaciones || 0))
const estadoViaje = computed(() => String(viaje.value?.estado || '').trim().toLowerCase())
const tituloEstado = computed(() => {
  if (estadoViaje.value === 'aceptado') return 'Tu mototaxista va a recoger al pasajero'
  if (estadoViaje.value === 'llegó') return 'El mototaxista llegó al punto de recogida'
  if (estadoViaje.value === 'en curso') return 'Viaje en curso hacia el destino'
  return 'Seguimiento del viaje MOTRIX'
})
const hayGps = computed(() =>
  numero(conductor.value?.latitud) !== null && numero(conductor.value?.longitud) !== null
)
const distanciaRutaTexto = computed(() => {
  if (!datos.value?.seguimiento_activo) return 'Seguimiento cerrado'
  if (!hayGps.value) return 'Esperando GPS'
  if (distanciaRutaKm.value === null) return calculandoRuta.value ? 'Calculando ruta...' : 'Ruta pendiente'
  const distancia = Number(distanciaRutaKm.value).toFixed(2)
  return rutaDirecta.value ? distancia + ' km en línea recta' : distancia + ' km por calles'
})
const tiempoRutaTexto = computed(() => {
  if (!datos.value?.seguimiento_activo || !hayGps.value) return 'Sin estimación'
  if (rutaDirecta.value || tiempoRutaMin.value === null) return 'Tiempo no disponible'
  return tiempoRutaMin.value + ' min aprox.'
})

function apiOrigen() {
  try { return new URL(api.defaults.baseURL).origin } catch { return window.location.origin }
}

const fotoUrl = computed(() => {
  const ruta = String(conductor.value?.foto_ruta || '').trim()
  if (!ruta) return ''
  if (/^https?:\/\//i.test(ruta)) return ruta
  let limpia = ruta.replace(/^\/+/, '').replace(/^public\//i, '')
  if (!limpia.startsWith('storage/')) limpia = `storage/${limpia}`
  return `${apiOrigen()}/${limpia}`
})

function numero(valor) {
  const n = Number.parseFloat(valor)
  return Number.isFinite(n) ? n : null
}

function puntosMapa() {
  const puntos = []
  const oLat = numero(viaje.value.latitud_origen)
  const oLng = numero(viaje.value.longitud_origen)
  const dLat = numero(viaje.value.latitud_destino)
  const dLng = numero(viaje.value.longitud_destino)
  const cLat = numero(conductor.value?.latitud)
  const cLng = numero(conductor.value?.longitud)
  if (oLat !== null && oLng !== null) puntos.push({ id: 'recogida', lat: oLat, lng: oLng, label: 'Punto de recogida', color: '#22b35b' })
  if (dLat !== null && dLng !== null) puntos.push({ id: 'destino', lat: dLat, lng: dLng, label: 'Destino', color: '#ca364b' })
  if (cLat !== null && cLng !== null) puntos.push({ id: 'conductor', lat: cLat, lng: cLng, label: nombreConductor.value, color: '#1976d2' })
  return puntos
}

function centrarMapa() {
  if (!mapa) return
  const conductorPunto = puntosMapa().find(p => p.id === 'conductor')
  const objetivo = puntosMapa().find(p => p.id === (estadoViaje.value === 'en curso' ? 'destino' : 'recogida'))
  const puntos = [conductorPunto, objetivo].filter(Boolean).map(p => [p.lat, p.lng])
  if (puntos.length >= 2) mapa.fitBounds(puntos, { padding: [45, 45], maxZoom: 16 })
  else if (puntos.length === 1) mapa.setView(puntos[0], 16)
  else mapa.setView([-14.8308, -64.9024], 13)
}

async function actualizarRuta() {
  if (!mapa || !datos.value?.seguimiento_activo || rutaEnCurso) return
  const posiciones = puntosMapa()
  const conductorPunto = posiciones.find(p => p.id === 'conductor')
  const objetivo = posiciones.find(p => p.id === (estadoViaje.value === 'en curso' ? 'destino' : 'recogida'))
  if (!conductorPunto || !objetivo) return

  const origen = [conductorPunto.lat, conductorPunto.lng]
  const destino = [objetivo.lat, objetivo.lng]
  const clave = [estadoViaje.value, ...origen.map(v => v.toFixed(4)), ...destino.map(v => v.toFixed(5))].join('|')
  if (clave === claveRutaDibujada && lineaRuta
    && (!rutaDirecta.value || Date.now() - ultimaRutaCalculadaEn < 30000)) return

  rutaEnCurso = true
  calculandoRuta.value = true
  const generacion = numeroCarga
  try {
    const resultado = await obtenerRutaCallesMotrix(origen, destino)
    if (!mapa || !datos.value?.seguimiento_activo || generacion !== numeroCarga
      || (estadoViaje.value === 'en curso' ? 'destino' : 'recogida') !== objetivo.id) return

    if (lineaRuta) mapa.removeLayer(lineaRuta)
    if (resultado.disponible) {
      lineaRuta = L.geoJSON(resultado.geometria, {
        style: { color: estadoViaje.value === 'en curso' ? '#303f9f' : '#1976d2', weight: 6, opacity: 0.88 }
      }).addTo(mapa)
      distanciaRutaKm.value = resultado.distanciaKm
      tiempoRutaMin.value = resultado.duracionMin
      rutaDirecta.value = false
    } else {
      lineaRuta = L.polyline([origen, destino], {
        color: '#1976d2', weight: 5, opacity: 0.7, dashArray: '9 9'
      }).addTo(mapa)
      // Nunca confundir una línea de orientación con ruta de conducción.
      const distanciaMetros = mapa.distance(origen, destino)
      distanciaRutaKm.value = Number((distanciaMetros / 1000).toFixed(2))
      tiempoRutaMin.value = null
      rutaDirecta.value = true
    }
    claveRutaDibujada = clave
    ultimaRutaCalculadaEn = Date.now()
    // Sin fitBounds automático: quien recibe el enlace puede explorar.
  } catch (err) {
    console.warn('No se pudo mostrar la ruta del enlace:', err)
  } finally {
    rutaEnCurso = false
    calculandoRuta.value = false
  }
}

async function dibujarMapa() {
  await nextTick()
  if (!mapaRef.value) return
  if (!mapa) {
    mapa = L.map(mapaRef.value, { zoomControl: false })
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19, attribution: '&copy; OpenStreetMap'
    }).addTo(mapa)
    L.control.zoom({ position: 'bottomright' }).addTo(mapa)
  }

  const puntos = puntosMapa()
  const presentes = new Set(puntos.map(p => p.id))
  for (const [id, marcador] of marcadores) {
    if (!presentes.has(id)) {
      mapa.removeLayer(marcador)
      marcadores.delete(id)
    }
  }
  puntos.forEach((p) => {
    const posicion = [p.lat, p.lng]
    const actual = marcadores.get(p.id)
    if (actual) {
      actual.setLatLng(posicion)
    } else {
      const marcador = L.circleMarker(posicion, {
        radius: p.id === 'conductor' ? 12 : 10,
        color: '#fff', weight: 3, fillColor: p.color, fillOpacity: 1
      }).addTo(mapa)
      // Texto no fiable como nodo DOM, nunca inyectado como HTML.
      marcador.bindPopup(document.createTextNode(p.label))
      marcadores.set(p.id, marcador)
    }
  })

  if (!inicialCentrada) {
    inicialCentrada = true
    centrarMapa()
    window.setTimeout(() => mapa?.invalidateSize(), 120)
  }
  // La ruta se consulta en segundo plano, sin impedir actualizaciones GPS.
  void actualizarRuta()
}

async function cargar() {
  if (cargando.value) return
  cargando.value = true
  try {
    const token = String(route.params.token || '').trim()
    const respuesta = await api.get(`/viaje-compartido/${encodeURIComponent(token)}`, { params: { _t: Date.now() } })
    datos.value = respuesta.data
    error.value = ''
    if (!datos.value?.seguimiento_activo && lineaRuta && mapa) {
      mapa.removeLayer(lineaRuta)
      lineaRuta = null
      claveRutaDibujada = ''
    }
    await dibujarMapa()
  } catch (err) {
    const estado = Number(err?.response?.status || 0)
    const mensaje = err?.response?.data?.message || 'No fue posible consultar este enlace de seguimiento.'

    if ([404, 410].includes(estado)) {
      datos.value = null
      error.value = mensaje
      if (intervalo) {
        window.clearInterval(intervalo)
        intervalo = null
      }
    } else if (!datos.value) {
      error.value = mensaje
    }
  } finally {
    cargando.value = false
  }
}

function abrirFoto() { if (fotoUrl.value) visorFoto.value = true }

onMounted(async () => {
  await cargar()
  intervalo = window.setInterval(cargar, 5000)
})

onBeforeUnmount(() => {
  if (intervalo) window.clearInterval(intervalo)
  numeroCarga += 1
  if (mapa) mapa.remove()
  mapa = null
  marcadores.clear()
  lineaRuta = null
})
</script>

<style scoped>
.shared-page { min-height: 100vh; background: #eef5f0; }
.shared-shell { max-width: 1120px; }
.shared-header { background: linear-gradient(135deg, #1b5e20, #2e7d32); }
.shared-map-wrap { position: relative; overflow: hidden; border-radius: 18px; border: 1px solid #d8e4dc; background: #edf3ef; }
.shared-map { height: min(64vh, 640px); min-height: 450px; }
.shared-map-status {
  position: absolute; top: 12px; left: 12px; right: 12px; z-index: 500;
  display: flex; align-items: center; gap: 10px;
  padding: 10px 12px; background: rgba(255,255,255,.97);
  color: #173d2a; border-radius: 16px; box-shadow: 0 5px 24px #1d3c281f;
}
.shared-map-status strong { font-size: 14px; }
.shared-map-status .text-caption { color: #527862; }
.shared-map-legend {
  position: absolute; top: 85px; left: 12px; z-index: 500;
  display: flex; gap: 10px; flex-wrap: wrap; border-radius: 20px;
  padding: 8px 11px; background: rgba(255,255,255,.97);
  box-shadow: 0 3px 18px #1d3c281b; font-size: 12px; color: #3a5045;
}
.shared-map-legend span { display:flex; align-items:center; gap:5px; }
.shared-dot { width: 10px; height:10px; border-radius:50%; display:inline-block; }
.shared-dot--blue { background:#1976d2; }
.shared-dot--green { background:#22b35b; }
.shared-dot--red { background:#ca364b; }
.shared-map-notice {
  position:absolute; z-index:500; left:12px; right:12px; top:135px;
  background:#fff8e8; color:#81591c; border-radius:12px; padding:8px 12px; font-size:12px;
}
.shared-map-metrics { display:flex; flex-wrap:wrap; gap:8px; padding-top:10px; }
.shared-map-metrics span {
  border:1px solid #e0ebe4; border-radius:20px; padding:8px 12px;
  font-size:12px; font-weight:650; color:#34483b; background:white;
}
.shared-map-metrics .q-icon { margin-right:4px; }
@media (max-width: 599px) {
  .shared-page { padding: 9px !important; }
  .shared-header { padding: 12px !important; }
  .shared-map { height: 60dvh; min-height: 380px; }
  .shared-map-status { left:9px; right:9px; top:9px; }
  .shared-map-legend { left:9px; top:85px; }
  .shared-map-metrics span { font-size:11px; }
}
</style>
