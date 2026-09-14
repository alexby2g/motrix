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
                <div ref="mapaRef" class="shared-map" />
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
let intervalo = null

const viaje = computed(() => datos.value?.viaje || {})
const conductor = computed(() => datos.value?.conductor || null)
const nombreConductor = computed(() => conductor.value?.nombre || 'Mototaxista MOTRIX')
const promedioCalificacion = computed(() => Number(conductor.value?.promedio_calificacion || 0))
const totalCalificaciones = computed(() => Number(conductor.value?.total_calificaciones || 0))

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
  const oLat = numero(viaje.value.latitud_origen); const oLng = numero(viaje.value.longitud_origen)
  const dLat = numero(viaje.value.latitud_destino); const dLng = numero(viaje.value.longitud_destino)
  const cLat = numero(conductor.value?.latitud); const cLng = numero(conductor.value?.longitud)
  if (oLat !== null && oLng !== null) puntos.push({ lat: oLat, lng: oLng, label: 'Origen', color: '#2e7d32' })
  if (dLat !== null && dLng !== null) puntos.push({ lat: dLat, lng: dLng, label: 'Destino', color: '#c62828' })
  if (cLat !== null && cLng !== null) puntos.push({ lat: cLat, lng: cLng, label: nombreConductor.value, color: '#1565c0' })
  return puntos
}

async function dibujarMapa() {
  await nextTick()
  if (!mapaRef.value) return
  if (mapa) { mapa.remove(); mapa = null }
  const puntos = puntosMapa()
  mapa = L.map(mapaRef.value, { zoomControl: true })
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(mapa)
  const bounds = []
  puntos.forEach((p) => {
    L.circleMarker([p.lat, p.lng], { radius: 9, color: p.color, fillColor: p.color, fillOpacity: 0.9, weight: 3 })
      .addTo(mapa).bindPopup(p.label)
    bounds.push([p.lat, p.lng])
  })
  if (bounds.length > 1) mapa.fitBounds(bounds, { padding: [35, 35] })
  else if (bounds.length === 1) mapa.setView(bounds[0], 16)
  else mapa.setView([-14.8347, -64.9040], 13)
  setTimeout(() => mapa?.invalidateSize(), 80)
}

async function cargar() {
  if (cargando.value) return
  cargando.value = true
  try {
    const token = String(route.params.token || '').trim()
    const respuesta = await api.get(`/viaje-compartido/${encodeURIComponent(token)}`, { params: { _t: Date.now() } })
    datos.value = respuesta.data
    error.value = ''
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
  if (mapa) mapa.remove()
  mapa = null
})
</script>

<style scoped>
.shared-page { min-height: 100vh; background: #eef5f0; }
.shared-shell { max-width: 1120px; }
.shared-header { background: linear-gradient(135deg, #1b5e20, #2e7d32); }
.shared-map { min-height: 420px; border-radius: 14px; overflow: hidden; border: 1px solid #d8e4dc; }
@media (max-width: 599px) { .shared-map { min-height: 330px; } }
</style>
