<template>
  <q-page class="historial-page bg-grey-2">
    <div class="row justify-center">
      <div class="col-12 col-lg-9">
        <q-card class="historial-card shadow-2">
          <q-card-section class="bg-primary text-white">
            <div class="row items-center no-wrap">
              <q-btn
                flat
                round
                icon="arrow_back"
                color="white"
                class="q-mr-sm"
                @click="volver"
              />

              <q-avatar
                color="white"
                text-color="primary"
                icon="history"
                size="52px"
                class="q-mr-md"
              />

              <div class="col">
                <div class="text-h5 text-weight-bold">
                  Mis viajes
                </div>

                <div class="text-caption text-blue-1">
                  Historial personal de solicitudes
                </div>
              </div>

              <q-btn
                flat
                round
                icon="refresh"
                color="white"
                :loading="cargando"
                @click="cargarHistorial"
              >
                <q-tooltip>
                  Actualizar historial
                </q-tooltip>
              </q-btn>
            </div>
          </q-card-section>

          <q-card-section
            v-if="cargando"
            class="column flex-center q-pa-xl"
          >
            <q-spinner
              color="primary"
              size="52px"
            />

            <div class="text-grey-7 q-mt-md">
              Cargando tus viajes...
            </div>
          </q-card-section>

          <q-card-section
            v-else-if="viajes.length === 0"
            class="column flex-center q-pa-xl text-grey-6"
          >
            <q-icon
              name="history_toggle_off"
              size="70px"
              color="grey-5"
            />

            <div class="text-h6 text-weight-bold q-mt-md">
              Todavía no tienes viajes
            </div>

            <div class="text-body2 text-center q-mt-sm">
              Tus solicitudes aparecerán aquí después de pedir un mototaxi.
            </div>

            <q-btn
              color="positive"
              icon="add_location_alt"
              label="Solicitar mototaxi"
              class="q-mt-lg"
              unelevated
              @click="irASolicitar"
            />
          </q-card-section>

          <q-card-section
            v-else
            class="q-pa-md"
          >
            <div class="row items-center justify-between q-mb-md">
              <div>
                <div class="text-h6 text-weight-bold text-grey-9">
                  Historial de viajes
                </div>

                <div class="text-caption text-grey-6">
                  {{ viajes.length }} solicitudes registradas
                </div>
              </div>

              <q-btn
                color="positive"
                icon="add"
                label="Nueva solicitud"
                unelevated
                @click="irASolicitar"
              />
            </div>

            <div class="row q-col-gutter-md">
              <div
                v-for="viaje in viajes"
                :key="viaje.id"
                class="col-12 col-md-6"
              >
                <q-card
                  flat
                  bordered
                  class="viaje-card full-height"
                >
                  <q-card-section>
                    <div class="row items-start no-wrap">
                      <q-avatar
                        :color="getEstadoColor(viaje.estado)"
                        text-color="white"
                        icon="two_wheeler"
                        size="46px"
                        class="q-mr-md"
                      />

                      <div class="col">
                        <div class="row items-center justify-between">
                          <div class="text-subtitle1 text-weight-bold">
                            Solicitud #{{ viaje.id }}
                          </div>

                          <q-chip
                            :color="getEstadoColor(viaje.estado)"
                            text-color="white"
                            dense
                            class="text-weight-bold text-uppercase"
                          >
                            {{ viaje.estado }}
                          </q-chip>
                        </div>

                        <div class="text-caption text-grey-6">
                          {{ formatearFecha(viaje.fecha) }}
                        </div>
                      </div>
                    </div>

                    <q-separator class="q-my-md" />

                    <div class="route-item">
                      <q-icon
                        name="radio_button_checked"
                        color="positive"
                        size="20px"
                      />

                      <div>
                        <div class="text-caption text-grey-6">
                          Origen
                        </div>

                        <div class="text-body2 text-weight-medium">
                          {{ viaje.origen || 'Sin origen registrado' }}
                        </div>
                      </div>
                    </div>

                    <div class="route-line" />

                    <div class="route-item">
                      <q-icon
                        name="location_on"
                        color="negative"
                        size="22px"
                      />

                      <div>
                        <div class="text-caption text-grey-6">
                          Destino
                        </div>

                        <div class="text-body2 text-weight-medium">
                          {{ viaje.destino || 'Sin destino registrado' }}
                        </div>
                      </div>
                    </div>

                    <q-separator class="q-my-md" />

                    <div class="row q-col-gutter-sm">
                      <div class="col-6">
                        <div class="text-caption text-grey-6">
                          Distancia
                        </div>

                        <div class="text-subtitle2 text-weight-bold">
                          {{ formatearDistancia(viaje.distancia_km) }}
                        </div>
                      </div>

                      <div class="col-6 text-right">
                        <div class="text-caption text-grey-6">
                          Tarifa
                        </div>

                        <div class="text-h6 text-weight-bold text-positive">
                          {{ formatearPrecio(viaje.precio) }}
                        </div>
                      </div>
                    </div>

                    <div class="q-mt-md">
                      <div class="text-caption text-grey-6">
                        Mototaxista
                      </div>

                      <div
                        v-if="getConductor(viaje)"
                        class="text-body2 text-weight-bold text-primary"
                      >
                        <q-icon
                          name="motorcycle"
                          class="q-mr-xs"
                        />

                        {{ getConductor(viaje) }}
                      </div>

                      <div
                        v-else
                        class="text-body2 text-grey-6"
                      >
                        Sin conductor asignado
                      </div>
                    </div>

                    <div
                      v-if="tieneCalificacion(viaje)"
                      class="q-mt-md"
                    >
                      <q-card
                        flat
                        bordered
                        class="calificacion-viaje-card"
                      >
                        <q-card-section class="q-pa-sm">
                          <div class="row items-center no-wrap">
                            <q-avatar
                              color="amber-1"
                              text-color="amber-9"
                              icon="star"
                              size="42px"
                              class="q-mr-sm"
                            />

                            <div class="col">
                              <div class="text-caption text-grey-6">
                                Calificación otorgada
                              </div>

                              <div class="row items-center no-wrap">
                                <q-rating
                                  :model-value="obtenerCalificacion(viaje)"
                                  :max="5"
                                  size="22px"
                                  color="amber"
                                  icon="star_border"
                                  icon-selected="star"
                                  readonly
                                />

                                <span class="text-weight-bold text-amber-9 q-ml-sm">
                                  {{ obtenerCalificacion(viaje).toFixed(1) }}/5
                                </span>
                              </div>
                            </div>
                          </div>

                          <div
                            v-if="viaje.comentario_calificacion"
                            class="comentario-calificacion q-mt-sm"
                          >
                            “{{ viaje.comentario_calificacion }}”
                          </div>

                          <div
                            v-if="viaje.calificado_en"
                            class="text-caption text-grey-6 q-mt-xs"
                          >
                            Calificado el
                            {{ formatearFechaHora(viaje.calificado_en) }}
                          </div>
                        </q-card-section>
                      </q-card>
                    </div>

                    <div
                      v-else-if="String(viaje.estado).toLowerCase() === 'finalizado'"
                      class="q-mt-md"
                    >
                      <q-banner
                        dense
                        rounded
                        class="bg-grey-2 text-grey-7"
                      >
                        <template #avatar>
                          <q-icon
                            name="star_outline"
                            color="grey-6"
                          />
                        </template>

                        Este viaje todavía no fue calificado.
                      </q-banner>
                    </div>

                    <div
                      v-if="viaje.motivo_cancelacion"
                      class="q-mt-md"
                    >
                      <q-banner
                        dense
                        rounded
                        class="bg-red-1 text-negative"
                      >
                        <strong>Motivo:</strong>
                        {{ viaje.motivo_cancelacion }}
                      </q-banner>
                    </div>
                  </q-card-section>
                </q-card>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import {
  onMounted,
  ref
} from 'vue'

import {
  useQuasar
} from 'quasar'

import {
  useRouter
} from 'vue-router'

import {
  api
} from '../../boot/axios.js'

const $q = useQuasar()
const router = useRouter()

const cargando = ref(false)
const viajes = ref([])

function extraerMensajeError(error) {
  return (
    error?.response?.data?.mensaje
    || error?.response?.data?.message
    || 'No se pudo cargar el historial.'
  )
}

async function cargarHistorial() {
  cargando.value = true

  try {
    const respuesta = await api.get(
      '/pasajero/solicitudes'
    )

    viajes.value = Array.isArray(respuesta.data)
      ? respuesta.data
      : []
  } catch (error) {
    console.error(
      'Error cargando historial:',
      error
    )

    $q.notify({
      type: 'negative',
      message: extraerMensajeError(error)
    })
  } finally {
    cargando.value = false
  }
}

function getConductor(viaje) {
  return (
    viaje?.mototaxista?.persona?.nombre
    || viaje?.mototaxista?.persona?.nombre_completo
    || viaje?.mototaxista?.nombre
    || null
  )
}

function getEstadoColor(estado) {
  const valor = String(estado || '')
    .trim()
    .toLowerCase()

  if (
    valor === 'pendiente'
    || valor === 'buscando conductor'
  ) {
    return 'orange-8'
  }

  if (valor === 'aceptado') {
    return 'blue-7'
  }

  if (valor === 'llegó') {
    return 'positive'
  }

  if (valor === 'en curso') {
    return 'indigo-9'
  }

  if (valor === 'finalizado') {
    return 'positive'
  }

  if (valor === 'cancelado') {
    return 'negative'
  }

  if (valor === 'expirado') {
    return 'grey-7'
  }

  return 'grey-7'
}

function obtenerCalificacion(viaje) {
  const numero = Number.parseFloat(
    viaje?.calificacion
  )

  return Number.isFinite(numero)
    ? Math.min(5, Math.max(1, numero))
    : 0
}

function tieneCalificacion(viaje) {
  return obtenerCalificacion(viaje) > 0
}

function formatearFechaHora(fecha) {
  if (!fecha) {
    return 'Fecha no registrada'
  }

  const valor = new Date(
    String(fecha).replace(' ', 'T')
  )

  if (Number.isNaN(valor.getTime())) {
    return String(fecha)
  }

  return new Intl.DateTimeFormat('es-BO', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(valor)
}

function formatearPrecio(precio) {
  const monto = Number.parseFloat(precio)

  return `Bs. ${
    Number.isFinite(monto)
      ? monto.toFixed(2)
      : '0.00'
  }`
}

function formatearDistancia(distancia) {
  const numero = Number.parseFloat(distancia)

  return Number.isFinite(numero)
    ? `${numero.toFixed(2)} km`
    : 'No registrada'
}

function formatearFecha(fecha) {
  if (!fecha) {
    return 'Fecha no registrada'
  }

  const valor = String(fecha).includes('T')
    ? new Date(fecha)
    : new Date(`${fecha}T00:00:00`)

  if (Number.isNaN(valor.getTime())) {
    return String(fecha)
  }

  return new Intl.DateTimeFormat('es-BO', {
    day: '2-digit',
    month: 'long',
    year: 'numeric'
  }).format(valor)
}

function volver() {
  router.push('/pasajero')
}

function irASolicitar() {
  router.push('/pasajero/solicitar')
}

onMounted(() => {
  cargarHistorial()
})
</script>

<style scoped>
.historial-page {
  min-height: 100%;
  padding: 16px;
}

.historial-card {
  overflow: hidden;
  border-radius: 16px;
}

.viaje-card {
  border-radius: 14px;
  border-left: 5px solid var(--q-primary);
}

.calificacion-viaje-card {
  border-left: 4px solid #ffb300;
  border-radius: 10px;
  background: #fffdf5;
}

.comentario-calificacion {
  padding: 8px 10px;
  color: #5d4037;
  font-size: 13px;
  font-style: italic;
  line-height: 1.45;
  border-radius: 8px;
  background: #fff8e1;
}

.route-item {
  display: grid;
  grid-template-columns: 26px minmax(0, 1fr);
  gap: 8px;
  align-items: start;
}

.route-line {
  width: 2px;
  height: 24px;
  margin: 2px 0 2px 9px;
  background: #bdbdbd;
}

@media (min-width: 600px) {
  .historial-page {
    padding: 24px;
  }
}

@media (max-width: 599px) {
  .historial-page {
    padding: 8px;
  }

  .historial-card {
    border-radius: 10px;
  }
}
</style>