<template>
  <q-page class="reportes-page q-pa-md">
    <!-- ENCABEZADO -->
    <q-card class="reportes-header text-white q-mb-lg shadow-2">
      <q-card-section class="row items-center q-col-gutter-md">
        <div class="col">
          <div class="row items-center no-wrap">
            <q-avatar
              color="white"
              text-color="green-9"
              icon="analytics"
              size="54px"
              class="q-mr-md"
            />

            <div class="min-width-zero">
              <div class="text-h5 text-weight-bold">
                Panel de análisis MOTRIX
              </div>

              <div class="text-caption text-green-1">
                Operaciones, recaudación y reputación de mototaxistas
              </div>
            </div>
          </div>
        </div>

        <div class="col-auto">
          <q-btn
            flat
            round
            icon="sync"
            color="white"
            :loading="loading"
            @click="cargarDatosDashboard"
          >
            <q-tooltip>
              Actualizar reportes
            </q-tooltip>
          </q-btn>
        </div>
      </q-card-section>
    </q-card>

    <!-- INDICADORES GENERALES -->
    <div class="row q-col-gutter-md q-mb-lg">
      <div class="col-12 col-sm-6 col-lg-4">
        <q-card class="kpi-card border-left-positive full-height">
          <q-card-section class="row items-center no-wrap">
            <q-avatar
              color="green-1"
              text-color="positive"
              icon="monetization_on"
              size="58px"
              class="q-mr-md"
            />

            <div>
              <div class="kpi-label">
                Total recaudado
              </div>

              <div class="text-h5 text-weight-bold text-grey-9">
                {{ formatearMonto(kpis.total_recaudado) }}
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-lg-4">
        <q-card class="kpi-card border-left-primary full-height">
          <q-card-section class="row items-center no-wrap">
            <q-avatar
              color="blue-1"
              text-color="primary"
              icon="two_wheeler"
              size="58px"
              class="q-mr-md"
            />

            <div>
              <div class="kpi-label">
                Viajes finalizados
              </div>

              <div class="text-h5 text-weight-bold text-grey-9">
                {{ kpis.total_viajes }}
              </div>

              <div class="text-caption text-grey-6">
                servicios completados
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-lg-4">
        <q-card class="kpi-card border-left-warning full-height">
          <q-card-section class="row items-center no-wrap">
            <q-avatar
              color="amber-1"
              text-color="amber-9"
              icon="payments"
              size="58px"
              class="q-mr-md"
            />

            <div class="min-width-zero">
              <div class="kpi-label">
                Método preferido
              </div>

              <div class="text-h6 text-weight-bold text-grey-9 ellipsis">
                {{ kpis.metodo_preferido }}
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-lg-4">
        <q-card class="kpi-card border-left-rating full-height">
          <q-card-section class="row items-center no-wrap">
            <q-avatar
              color="amber-1"
              text-color="amber-9"
              icon="star"
              size="58px"
              class="q-mr-md"
            />

            <div>
              <div class="kpi-label">
                Promedio general
              </div>

              <div class="row items-center no-wrap">
                <div class="text-h5 text-weight-bold text-amber-9">
                  {{ numeroDosDecimales(kpis.promedio_general) }}/5
                </div>
              </div>

              <q-rating
                :model-value="estrellasVisuales(kpis.promedio_general)"
                :max="5"
                size="19px"
                color="amber"
                icon="star_border"
                icon-selected="star"
                readonly
              />
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-lg-4">
        <q-card class="kpi-card border-left-purple full-height">
          <q-card-section class="row items-center no-wrap">
            <q-avatar
              color="purple-1"
              text-color="purple-8"
              icon="reviews"
              size="58px"
              class="q-mr-md"
            />

            <div>
              <div class="kpi-label">
                Calificaciones recibidas
              </div>

              <div class="text-h5 text-weight-bold text-grey-9">
                {{ kpis.total_calificaciones }}
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-lg-4">
        <q-card class="kpi-card border-left-grey full-height">
          <q-card-section class="row items-center no-wrap">
            <q-avatar
              color="grey-3"
              text-color="grey-8"
              icon="star_outline"
              size="58px"
              class="q-mr-md"
            />

            <div>
              <div class="kpi-label">
                Viajes sin calificar
              </div>

              <div class="text-h5 text-weight-bold text-grey-9">
                {{ kpis.viajes_sin_calificar }}
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- GRÁFICA DE RECAUDACIÓN -->
    <q-card class="content-card q-mb-lg">
      <q-card-section>
        <div class="row items-center justify-between q-mb-md">
          <div>
            <div class="section-title">
              <q-icon
                name="bar_chart"
                color="green-9"
                size="26px"
                class="q-mr-sm"
              />

              Recaudación semanal
            </div>

            <div class="text-caption text-grey-6">
              Comparación entre efectivo y pagos digitales durante los últimos 7 días
            </div>
          </div>

          <q-badge
            color="green-1"
            text-color="green-9"
            class="text-weight-bold q-pa-sm"
          >
            Últimos 7 días
          </q-badge>
        </div>

        <div class="mixed-chart">
          <apexchart
            v-if="!loading"
            type="bar"
            height="350"
            :options="chartOptions"
            :series="chartSeries"
          />

          <div
            v-else
            class="row justify-center items-center"
            style="height: 350px"
          >
            <q-spinner-dots
              color="positive"
              size="48px"
            />
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- RANKING Y COMENTARIOS -->
    <div class="row q-col-gutter-md">
      <div class="col-12 col-lg-7">
        <q-card class="content-card full-height">
          <q-card-section>
            <div class="row items-center q-mb-md">
              <q-avatar
                color="amber-1"
                text-color="amber-9"
                icon="emoji_events"
                size="46px"
                class="q-mr-md"
              />

              <div class="col">
                <div class="section-title">
                  Reputación de mototaxistas
                </div>

                <div class="text-caption text-grey-6">
                  Promedio y cantidad de opiniones recibidas
                </div>
              </div>
            </div>

            <q-input
              v-model.trim="filtroRanking"
              outlined
              dense
              clearable
              label="Buscar mototaxista"
              class="q-mb-md"
            >
              <template #prepend>
                <q-icon name="search" />
              </template>
            </q-input>

            <div
              v-if="rankingFiltrado.length === 0"
              class="column items-center text-grey-6 q-pa-xl"
            >
              <q-icon
                name="person_search"
                size="54px"
                color="grey-5"
              />

              <div class="q-mt-sm">
                No se encontraron mototaxistas.
              </div>
            </div>

            <div
              v-else
              class="column q-gutter-sm"
            >
              <q-card
                v-for="(mototaxista, indice) in rankingFiltrado"
                :key="mototaxista.id"
                flat
                bordered
                class="ranking-card"
              >
                <q-card-section class="q-pa-md">
                  <div class="row items-start no-wrap">
                    <q-avatar
                      :color="indice < 3 ? 'amber-1' : 'green-1'"
                      :text-color="indice < 3 ? 'amber-9' : 'green-9'"
                      :icon="indice < 3 ? 'workspace_premium' : 'two_wheeler'"
                      size="50px"
                      class="q-mr-md"
                    />

                    <div class="col min-width-zero">
                      <div class="row items-start justify-between no-wrap">
                        <div class="col min-width-zero">
                          <div class="text-subtitle1 text-weight-bold text-grey-9 ellipsis">
                            {{ mototaxista.nombre }}
                          </div>

                          <div class="text-caption text-grey-6">
                            Mototaxista #{{ mototaxista.id }}
                          </div>
                        </div>

                        <q-badge
                          v-if="indice < 3 && mototaxista.total_calificaciones > 0"
                          color="amber-8"
                          text-color="white"
                          class="q-ml-sm q-pa-sm"
                        >
                          Puesto {{ indice + 1 }}
                        </q-badge>
                      </div>

                      <div class="row items-center q-mt-sm">
                        <q-rating
                          :model-value="estrellasVisuales(
                            mototaxista.promedio_calificacion
                          )"
                          :max="5"
                          size="23px"
                          color="amber"
                          icon="star_border"
                          icon-selected="star"
                          readonly
                        />

                        <span class="text-weight-bold text-amber-9 q-ml-sm">
                          {{
                            numeroDosDecimales(
                              mototaxista.promedio_calificacion
                            )
                          }}/5
                        </span>
                      </div>

                      <div class="text-caption text-grey-7 q-mt-xs">
                        {{
                          textoCalificaciones(
                            mototaxista.total_calificaciones
                          )
                        }}
                      </div>

                      <q-banner
                        v-if="mototaxista.ultimo_comentario"
                        dense
                        rounded
                        class="ultimo-comentario q-mt-sm"
                      >
                        <template #avatar>
                          <q-icon
                            name="format_quote"
                            color="green-8"
                          />
                        </template>

                        “{{ mototaxista.ultimo_comentario }}”
                      </q-banner>

                      <div
                        v-else
                        class="text-caption text-grey-6 q-mt-sm"
                      >
                        Todavía no tiene comentarios.
                      </div>
                    </div>
                  </div>
                </q-card-section>
              </q-card>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-lg-5">
        <q-card class="content-card full-height">
          <q-card-section>
            <div class="row items-center q-mb-md">
              <q-avatar
                color="green-1"
                text-color="green-9"
                icon="forum"
                size="46px"
                class="q-mr-md"
              />

              <div>
                <div class="section-title">
                  Comentarios recientes
                </div>

                <div class="text-caption text-grey-6">
                  Últimas opiniones registradas por los pasajeros
                </div>
              </div>
            </div>

            <div
              v-if="comentariosRecientes.length === 0"
              class="column items-center text-grey-6 q-pa-xl"
            >
              <q-icon
                name="chat_bubble_outline"
                size="54px"
                color="grey-5"
              />

              <div class="q-mt-sm text-center">
                Todavía no existen comentarios.
              </div>
            </div>

            <q-list
              v-else
              separator
              class="comentarios-lista"
            >
              <q-item
                v-for="comentario in comentariosRecientes"
                :key="comentario.solicitud_id"
                class="q-px-none"
              >
                <q-item-section avatar top>
                  <q-avatar
                    color="amber-1"
                    text-color="amber-9"
                    icon="star"
                  />
                </q-item-section>

                <q-item-section>
                  <q-item-label class="text-weight-bold text-grey-9">
                    {{
                      comentario.mototaxista_nombre
                      || `Mototaxista #${comentario.mototaxista_id}`
                    }}
                  </q-item-label>

                  <q-item-label caption>
                    Solicitud #{{ comentario.solicitud_id }}
                    ·
                    {{ formatearFechaHora(comentario.calificado_en) }}
                  </q-item-label>

                  <div class="row items-center q-mt-xs">
                    <q-rating
                      :model-value="estrellasVisuales(
                        comentario.calificacion
                      )"
                      :max="5"
                      size="18px"
                      color="amber"
                      icon="star_border"
                      icon-selected="star"
                      readonly
                    />

                    <span class="text-caption text-weight-bold text-amber-9 q-ml-xs">
                      {{ Number(comentario.calificacion) }}/5
                    </span>
                  </div>

                  <div class="comentario-texto q-mt-sm">
                    “{{ comentario.comentario_calificacion }}”
                  </div>
                </q-item-section>
              </q-item>
            </q-list>
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

import apexchart from 'vue3-apexcharts'

import {
  api
} from '../../boot/axios.js'

const $q = useQuasar()

const loading = ref(false)
const filtroRanking = ref('')

const kpis = ref({
  total_viajes: 0,
  total_recaudado: 0,
  metodo_preferido: 'Ninguno',
  promedio_general: 0,
  total_calificaciones: 0,
  viajes_sin_calificar: 0
})

const rankingMototaxistas = ref([])
const comentariosRecientes = ref([])

const chartSeries = ref([
  {
    name: 'Efectivo (Bs.)',
    data: []
  },
  {
    name: 'Digital / QR (Bs.)',
    data: []
  }
])

const chartOptions = ref({
  chart: {
    id: 'reporte-ganancias-motrix',
    toolbar: {
      show: false
    },
    fontFamily:
      'Roboto, Helvetica, Arial, sans-serif'
  },

  colors: [
    '#2e7d32',
    '#3949ab'
  ],

  plotOptions: {
    bar: {
      horizontal: false,
      columnWidth: '55%',
      borderRadius: 6
    }
  },

  dataLabels: {
    enabled: false
  },

  stroke: {
    show: true,
    width: 2,
    colors: [
      'transparent'
    ]
  },

  xaxis: {
    categories: [],

    labels: {
      style: {
        colors: '#616161',
        fontWeight: 600
      }
    }
  },

  yaxis: {
    title: {
      text: 'Bolivianos (Bs.)',

      style: {
        color: '#616161',
        fontWeight: 600
      }
    }
  },

  fill: {
    opacity: 1
  },

  tooltip: {
    y: {
      formatter: (valor) =>
        formatearMonto(valor)
    }
  },

  legend: {
    position: 'top',
    horizontalAlign: 'left'
  },

  noData: {
    text: 'No existen datos para mostrar.'
  }
})

const rankingFiltrado = computed(() => {
  const filtro = normalizarTexto(
    filtroRanking.value
  )

  if (!filtro) {
    return rankingMototaxistas.value
  }

  return rankingMototaxistas.value.filter(
    (mototaxista) => {
      return normalizarTexto(
        mototaxista.nombre
      ).includes(filtro)
    }
  )
})

function normalizarTexto(valor) {
  return String(valor || '')
    .trim()
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
}

function numeroDosDecimales(valor) {
  const numero = Number.parseFloat(valor)

  return Number.isFinite(numero)
    ? numero.toFixed(2)
    : '0.00'
}

function formatearMonto(valor) {
  const numero = Number.parseFloat(valor)

  return `Bs. ${
    Number.isFinite(numero)
      ? numero.toFixed(2)
      : '0.00'
  }`
}

function estrellasVisuales(valor) {
  const numero = Number.parseFloat(valor)

  if (!Number.isFinite(numero)) {
    return 0
  }

  return Math.round(
    Math.min(5, Math.max(0, numero))
  )
}

function textoCalificaciones(total) {
  const cantidad = Number(total) || 0

  if (cantidad === 0) {
    return 'Sin calificaciones'
  }

  if (cantidad === 1) {
    return '1 calificación recibida'
  }

  return `${cantidad} calificaciones recibidas`
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

  return new Intl.DateTimeFormat(
    'es-BO',
    {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    }
  ).format(valor)
}

function formatearFechaGrafica(fecha) {
  const valor = new Date(
    `${fecha}T00:00:00`
  )

  if (Number.isNaN(valor.getTime())) {
    return String(fecha)
  }

  return new Intl.DateTimeFormat(
    'es-BO',
    {
      day: '2-digit',
      month: 'short'
    }
  ).format(valor)
}

function extraerMensajeError(error) {
  return (
    error?.response?.data?.mensaje
    || error?.response?.data?.error
    || error?.response?.data?.message
    || 'No se pudieron cargar los reportes.'
  )
}

async function cargarDatosDashboard() {
  if (loading.value) {
    return
  }

  loading.value = true

  try {
    const respuesta = await api.get(
      '/reportes/dashboard',
      {
        params: {
          _t: Date.now()
        }
      }
    )

    const datos = respuesta.data || {}

    kpis.value = {
      total_viajes:
        Number(datos.kpis?.total_viajes) || 0,

      total_recaudado:
        Number(datos.kpis?.total_recaudado) || 0,

      metodo_preferido:
        datos.kpis?.metodo_preferido
        || 'Ninguno',

      promedio_general:
        Number(datos.kpis?.promedio_general) || 0,

      total_calificaciones:
        Number(datos.kpis?.total_calificaciones) || 0,

      viajes_sin_calificar:
        Number(datos.kpis?.viajes_sin_calificar) || 0
    }

    rankingMototaxistas.value = Array.isArray(
      datos.ranking_mototaxistas
    )
      ? datos.ranking_mototaxistas
      : []

    comentariosRecientes.value = Array.isArray(
      datos.comentarios_recientes
    )
      ? datos.comentarios_recientes
      : []

    const registros = Array.isArray(
      datos.semanal
    )
      ? datos.semanal
      : []

    chartOptions.value = {
      ...chartOptions.value,

      xaxis: {
        ...chartOptions.value.xaxis,

        categories: registros.map(
          (item) =>
            formatearFechaGrafica(
              item.fecha
            )
        )
      }
    }

    chartSeries.value = [
      {
        name: 'Efectivo (Bs.)',

        data: registros.map(
          (item) =>
            Number.parseFloat(
              item.efectivo
            ) || 0
        )
      },
      {
        name: 'Digital / QR (Bs.)',

        data: registros.map(
          (item) =>
            Number.parseFloat(
              item.digital
            ) || 0
        )
      }
    ]
  } catch (error) {
    console.error(
      'Error cargando reportes:',
      error
    )

    $q.notify({
      type: 'negative',
      message: extraerMensajeError(error),
      position: 'top'
    })
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  cargarDatosDashboard()
})
</script>

<style scoped>
.reportes-page {
  min-height: 100%;
  background: transparent;
}

.reportes-header {
  overflow: hidden;
  border-radius: 16px;
  background:
    linear-gradient(
      135deg,
      #145a22 0%,
      #23752f 55%,
      #2e7d32 100%
    );
}

.content-card {
  overflow: hidden;
  border-radius: 14px;
  background: #ffffff;
  box-shadow:
    0 3px 12px rgba(0, 0, 0, 0.08);
}

.kpi-card {
  overflow: hidden;
  border-radius: 14px;
  background: #ffffff;
  box-shadow:
    0 3px 10px rgba(0, 0, 0, 0.08);
}

.kpi-label {
  color: #757575;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.section-title {
  display: flex;
  align-items: center;
  color: #263238;
  font-size: 18px;
  font-weight: 700;
}

.border-left-positive {
  border-left: 5px solid #21ba45;
}

.border-left-primary {
  border-left: 5px solid #1976d2;
}

.border-left-warning {
  border-left: 5px solid #f2c037;
}

.border-left-rating {
  border-left: 5px solid #ffb300;
}

.border-left-purple {
  border-left: 5px solid #8e24aa;
}

.border-left-grey {
  border-left: 5px solid #78909c;
}

.ranking-card {
  overflow: hidden;
  border-left: 4px solid #23752f;
  border-radius: 12px;
  background: #ffffff;
}

.ultimo-comentario {
  color: #1b5e20;
  background: #edf7ed;
}

.comentarios-lista {
  max-height: 660px;
  overflow-y: auto;
}

.comentario-texto {
  padding: 9px 11px;
  color: #4e342e;
  font-size: 13px;
  font-style: italic;
  line-height: 1.5;
  border-radius: 9px;
  background: #fff8e1;
}

.min-width-zero {
  min-width: 0;
}

@media (max-width: 599px) {
  .reportes-page {
    padding: 8px;
  }

  .reportes-header,
  .content-card,
  .kpi-card {
    border-radius: 10px;
  }
}
</style>
