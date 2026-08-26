<template>
  <q-page class="driver-stats-page q-pa-md q-pa-lg-md">
    <div class="row justify-center">
      <div class="col-12 col-xl-10">
        <q-card class="stats-shell shadow-2">
          <q-card-section class="stats-header text-white">
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
                text-color="green-8"
                icon="account_balance_wallet"
                size="52px"
                class="q-mr-md"
              />

              <div class="col min-width-zero">
                <div class="text-h5 text-weight-bold">
                  Mis ganancias
                </div>

                <div class="text-caption text-green-1">
                  Recaudación, reputación e historial de viajes
                </div>
              </div>

              <q-btn
                flat
                round
                icon="refresh"
                color="white"
                :loading="cargando"
                @click="cargarGanancias"
              >
                <q-tooltip>
                  Actualizar
                </q-tooltip>
              </q-btn>
            </div>
          </q-card-section>

          <q-linear-progress
            v-if="cargando"
            indeterminate
            color="green-8"
          />

          <q-card-section class="q-pa-md q-pa-lg-md">
            <!-- RESUMEN -->
            <div class="row q-col-gutter-md">
              <div class="col-12 col-md-6">
                <q-card
                  flat
                  class="total-card full-height"
                >
                  <q-card-section class="row items-center no-wrap">
                    <q-avatar
                      color="white"
                      text-color="green-8"
                      icon="payments"
                      size="62px"
                      class="q-mr-md"
                    />

                    <div class="col">
                      <div class="text-caption text-green-1">
                        Total recaudado
                      </div>

                      <div class="text-h4 text-weight-bold text-white">
                        {{ formatearMonto(resumen.total_recaudado) }}
                      </div>

                      <div class="text-caption text-green-1 q-mt-xs">
                        {{ resumen.viajes_totales }}
                        viajes completados
                      </div>
                    </div>
                  </q-card-section>
                </q-card>
              </div>

              <div class="col-12 col-md-6">
                <q-card
                  flat
                  bordered
                  class="rating-card full-height"
                >
                  <q-card-section class="row items-center no-wrap">
                    <q-avatar
                      color="amber-1"
                      text-color="amber-9"
                      icon="workspace_premium"
                      size="62px"
                      class="q-mr-md"
                    />

                    <div class="col min-width-zero">
                      <div class="text-caption text-grey-6">
                        Mi reputación
                      </div>

                      <div class="row items-center no-wrap q-mt-xs">
                        <q-rating
                          :model-value="promedioEstrellas"
                          :max="5"
                          size="24px"
                          color="amber"
                          icon="star_border"
                          icon-selected="star"
                          readonly
                        />

                        <strong class="text-amber-9 q-ml-sm">
                          {{ resumen.promedio_calificacion.toFixed(2) }}/5
                        </strong>
                      </div>

                      <div class="text-caption text-grey-6 q-mt-xs">
                        {{ resumen.total_calificaciones }}
                        calificaciones recibidas
                      </div>
                    </div>
                  </q-card-section>
                </q-card>
              </div>

              <div class="col-6">
                <q-card
                  flat
                  bordered
                  class="money-card full-height"
                >
                  <q-card-section class="text-center">
                    <q-icon
                      name="payments"
                      color="green-8"
                      size="30px"
                    />

                    <div class="text-caption text-grey-6 q-mt-sm">
                      En efectivo
                    </div>

                    <div class="text-h6 text-weight-bold text-green-9">
                      {{ formatearMonto(resumen.ganancia_efectivo) }}
                    </div>
                  </q-card-section>
                </q-card>
              </div>

              <div class="col-6">
                <q-card
                  flat
                  bordered
                  class="money-card full-height"
                >
                  <q-card-section class="text-center">
                    <q-icon
                      name="qr_code_2"
                      color="green-8"
                      size="30px"
                    />

                    <div class="text-caption text-grey-6 q-mt-sm">
                      Digital / QR
                    </div>

                    <div class="text-h6 text-weight-bold text-green-9">
                      {{ formatearMonto(resumen.ganancia_qr) }}
                    </div>
                  </q-card-section>
                </q-card>
              </div>
            </div>

            <q-separator class="q-my-lg" />

            <!-- HISTORIAL -->
            <div class="row items-center q-mb-md">
              <div>
                <div class="text-h6 text-weight-bold text-grey-9">
                  Historial de viajes
                </div>

                <div class="text-caption text-grey-6">
                  Servicios finalizados y pagos registrados.
                </div>
              </div>

              <q-space />

              <q-badge
                color="green-8"
                class="q-pa-sm"
              >
                {{ historial.length }}
              </q-badge>
            </div>

            <div
              v-if="!cargando && historial.length === 0"
              class="empty-state"
            >
              <q-icon
                name="history_toggle_off"
                color="grey-5"
                size="58px"
              />

              <div class="text-subtitle1 text-weight-bold text-grey-7 q-mt-sm">
                Todavía no hay viajes completados
              </div>
            </div>

            <div
              v-else
              class="row q-col-gutter-md"
            >
              <div
                v-for="item in historial"
                :key="item.id"
                class="col-12 col-md-6"
              >
                <q-card
                  flat
                  bordered
                  class="trip-card full-height"
                >
                  <q-card-section>
                    <div class="row items-start no-wrap">
                      <q-avatar
                        color="green-1"
                        text-color="green-8"
                        :icon="iconoPago(item.metodo)"
                        size="46px"
                        class="q-mr-md"
                      />

                      <div class="col min-width-zero">
                        <div class="row items-start no-wrap">
                          <div class="col min-width-zero">
                            <div class="text-subtitle1 text-weight-bold text-grey-9 ellipsis">
                              {{ item.destino || 'Destino no registrado' }}
                            </div>

                            <div class="text-caption text-grey-6">
                              Viaje #{{ item.solicitud_id || item.id }}
                              · {{ formatearFecha(item.fecha) }}
                            </div>
                          </div>

                          <q-badge
                            color="green-8"
                            class="q-pa-sm q-ml-sm"
                          >
                            {{ formatearMonto(item.monto) }}
                          </q-badge>
                        </div>

                        <div class="trip-origin q-mt-md">
                          <q-icon
                            name="my_location"
                            color="green-8"
                          />

                          <span>
                            {{ item.origen || 'Origen no registrado' }}
                          </span>
                        </div>

                        <div class="trip-payment q-mt-sm">
                          <q-icon
                            :name="iconoPago(item.metodo)"
                            color="green-8"
                          />

                          <span>
                            {{ item.metodo || 'Método no especificado' }}
                          </span>
                        </div>
                      </div>
                    </div>

                    <q-separator class="q-my-md" />

                    <div
                      v-if="tieneCalificacion(item)"
                      class="rating-received"
                    >
                      <div class="row items-center no-wrap">
                        <q-rating
                          :model-value="calificacion(item)"
                          :max="5"
                          size="21px"
                          color="amber"
                          icon="star_border"
                          icon-selected="star"
                          readonly
                        />

                        <strong class="text-amber-9 q-ml-sm">
                          {{ calificacion(item).toFixed(1) }}/5
                        </strong>
                      </div>

                      <div
                        v-if="item.comentario_calificacion"
                        class="comment-box q-mt-sm"
                      >
                        “{{ item.comentario_calificacion }}”
                      </div>
                    </div>

                    <q-banner
                      v-else
                      rounded
                      dense
                      class="bg-grey-2 text-grey-7"
                    >
                      <template #avatar>
                        <q-icon name="star_outline" />
                      </template>

                      Este viaje todavía no recibió una calificación.
                    </q-banner>
                  </q-card-section>
                </q-card>
              </div>
            </div>

            <q-separator class="q-my-lg" />

            <!-- COMENTARIOS -->
            <div class="text-h6 text-weight-bold text-grey-9">
              Comentarios recientes
            </div>

            <div class="text-caption text-grey-6 q-mb-md">
              Opiniones recibidas de los pasajeros.
            </div>

            <q-banner
              v-if="comentarios.length === 0"
              rounded
              class="bg-grey-2 text-grey-7"
            >
              <template #avatar>
                <q-icon name="forum" />
              </template>

              Todavía no existen comentarios recientes.
            </q-banner>

            <div
              v-else
              class="column q-gutter-sm"
            >
              <q-card
                v-for="comentario in comentarios"
                :key="comentario.id"
                flat
                bordered
                class="comment-card"
              >
                <q-card-section>
                  <div class="row items-center">
                    <q-rating
                      :model-value="calificacion(comentario)"
                      :max="5"
                      size="20px"
                      color="amber"
                      icon="star_border"
                      icon-selected="star"
                      readonly
                    />

                    <q-space />

                    <span class="text-caption text-grey-6">
                      {{ formatearFechaHora(comentario.calificado_en) }}
                    </span>
                  </div>

                  <div class="text-body2 text-grey-8 q-mt-sm">
                    “{{ comentario.comentario_calificacion }}”
                  </div>
                </q-card-section>
              </q-card>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import {
  computed,
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
} from 'src/boot/axios.js'

const $q = useQuasar()
const router = useRouter()

const cargando = ref(false)

const resumen = ref({
  viajes_totales: 0,
  ganancia_efectivo: 0,
  ganancia_qr: 0,
  total_recaudado: 0,
  promedio_calificacion: 0,
  total_calificaciones: 0
})

const historial = ref([])
const comentarios = ref([])

const promedioEstrellas = computed(() => {
  return Math.min(
    5,
    Math.max(
      0,
      Number(resumen.value.promedio_calificacion) || 0
    )
  )
})

async function cargarGanancias() {
  if (cargando.value) return

  cargando.value = true

  try {
    const response =
      await api.get(
        '/conductor/ganancias',
        {
          params: {
            _t: Date.now()
          }
        }
      )

    resumen.value = {
      viajes_totales:
        Number(response.data?.viajes_totales) || 0,

      ganancia_efectivo:
        Number(response.data?.ganancia_efectivo) || 0,

      ganancia_qr:
        Number(response.data?.ganancia_qr) || 0,

      total_recaudado:
        Number(response.data?.total_recaudado) || 0,

      promedio_calificacion:
        Number(response.data?.promedio_calificacion) || 0,

      total_calificaciones:
        Number(response.data?.total_calificaciones) || 0
    }

    historial.value =
      Array.isArray(response.data?.detalles_pagos)
        ? response.data.detalles_pagos
        : []

    comentarios.value =
      Array.isArray(response.data?.comentarios_recientes)
        ? response.data.comentarios_recientes
        : []
  } catch (error) {
    console.error(
      'Error cargando ganancias:',
      error
    )

    $q.notify({
      type: 'negative',
      position: 'top',
      message:
        error?.response?.data?.mensaje
        || error?.response?.data?.message
        || 'No se pudo cargar la información de ganancias.'
    })
  } finally {
    cargando.value = false
  }
}

function volver() {
  router.push('/conductor')
}

function formatearMonto(valor) {
  return `Bs. ${Number(valor || 0).toFixed(2)}`
}

function iconoPago(metodo) {
  return metodo === 'Efectivo'
    ? 'payments'
    : 'qr_code_2'
}

function calificacion(item) {
  const numero =
    Number.parseFloat(
      item?.calificacion
    )

  if (!Number.isFinite(numero)) {
    return 0
  }

  return Math.min(
    5,
    Math.max(0, numero)
  )
}

function tieneCalificacion(item) {
  return calificacion(item) > 0
}

function formatearFecha(valor) {
  if (!valor) return 'Sin fecha'

  const fecha =
    new Date(`${valor}T12:00:00`)

  if (Number.isNaN(fecha.getTime())) {
    return String(valor)
  }

  return new Intl.DateTimeFormat(
    'es-BO',
    {
      day: '2-digit',
      month: 'short',
      year: 'numeric'
    }
  ).format(fecha)
}

function formatearFechaHora(valor) {
  if (!valor) return 'Sin fecha'

  const fecha = new Date(valor)

  if (Number.isNaN(fecha.getTime())) {
    return String(valor)
  }

  return new Intl.DateTimeFormat(
    'es-BO',
    {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    }
  ).format(fecha)
}

onMounted(() => {
  cargarGanancias()
})
</script>

<style scoped>
.driver-stats-page {
  min-height: 100%;
  background: transparent;
}

.stats-shell {
  overflow: hidden;
  border-radius: 18px;
}

.stats-header {
  background:
    linear-gradient(
      135deg,
      #1b5e20,
      #2e7d32
    );
}

.total-card {
  color: white;
  background:
    linear-gradient(
      135deg,
      #124c1b,
      #2e7d32
    );
  border-radius: 16px;
}

.rating-card,
.money-card,
.trip-card,
.comment-card {
  border-color: #d6e4d2;
  border-radius: 15px;
}

.trip-card {
  border-top: 4px solid #2e7d32;
}

.trip-origin,
.trip-payment {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  color: #657467;
  font-size: 12px;
}

.rating-received {
  padding: 11px;
  background: #fffaf0;
  border: 1px solid #f0e0b4;
  border-radius: 11px;
}

.comment-box {
  padding: 10px 12px;
  color: #58635a;
  background: white;
  border-radius: 9px;
  font-size: 12px;
  font-style: italic;
}

.empty-state {
  min-height: 210px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.min-width-zero {
  min-width: 0;
}

@media (max-width: 599px) {
  .driver-stats-page {
    padding: 9px 9px 22px;
  }

  .stats-shell {
    border-radius: 14px;
  }
}
</style>
