<template>
  <q-page class="support-page q-pa-md q-pa-lg-md">
    <q-banner
      rounded
      class="support-banner text-white q-mb-md"
    >
      <template #avatar>
        <q-icon
          name="admin_panel_settings"
          size="34px"
        />
      </template>

      <div class="row items-center justify-between q-col-gutter-md">
        <div class="col">
          <div class="text-h6 text-weight-bold">
            Modo soporte · Pasajero
          </div>
          <div class="text-caption">
            Vista administrativa de solo consulta. No estás iniciando sesión como el pasajero.
          </div>
        </div>

        <div class="col-auto">
          <q-btn
            outline
            color="white"
            icon="arrow_back"
            label="Volver a pasajeros"
            @click="volver"
          />
        </div>
      </div>
    </q-banner>

    <q-inner-loading
      :showing="cargando"
      label="Cargando información del pasajero..."
      color="green-8"
    />

    <template v-if="!cargando && pasajero">
      <q-card
        flat
        bordered
        class="support-card q-mb-md"
      >
        <q-card-section class="row items-center q-col-gutter-lg">
          <div class="col-auto">
            <q-avatar
              size="78px"
              color="green-1"
              text-color="green-9"
              icon="person"
            />
          </div>

          <div class="col">
            <div class="text-caption text-grey-6">
              Pasajero #{{ pasajero.id }}
            </div>
            <div class="text-h5 text-weight-bold text-grey-9">
              {{ nombrePasajero }}
            </div>
            <div class="row q-gutter-sm q-mt-sm">
              <q-chip
                dense
                color="green-1"
                text-color="green-9"
                icon="verified_user"
              >
                Registro MOTRIX
              </q-chip>

              <q-chip
                dense
                color="blue-1"
                text-color="blue-9"
                icon="email"
              >
                {{ pasajero.email || 'Sin correo registrado' }}
              </q-chip>
            </div>
          </div>
        </q-card-section>
      </q-card>

      <div class="row q-col-gutter-md q-mb-md">
        <div class="col-6 col-md-3">
          <q-card flat bordered class="stat-card">
            <q-card-section>
              <div class="text-caption text-grey-6">Viajes registrados</div>
              <div class="text-h5 text-weight-bold text-green-9">
                {{ viajes.length }}
              </div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-6 col-md-3">
          <q-card flat bordered class="stat-card">
            <q-card-section>
              <div class="text-caption text-grey-6">Finalizados</div>
              <div class="text-h5 text-weight-bold text-positive">
                {{ totalFinalizados }}
              </div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-6 col-md-3">
          <q-card flat bordered class="stat-card">
            <q-card-section>
              <div class="text-caption text-grey-6">Cancelados</div>
              <div class="text-h5 text-weight-bold text-negative">
                {{ totalCancelados }}
              </div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-6 col-md-3">
          <q-card flat bordered class="stat-card">
            <q-card-section>
              <div class="text-caption text-grey-6">Total en viajes finalizados</div>
              <div class="text-h5 text-weight-bold text-green-9">
                {{ dinero(totalGastado) }}
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <div class="row q-col-gutter-md q-mb-md">
        <div class="col-12 col-md-5">
          <q-card flat bordered class="support-card full-height">
            <q-card-section>
              <div class="text-subtitle1 text-weight-bold text-green-9">
                <q-icon name="badge" class="q-mr-xs" />
                Datos del pasajero
              </div>
              <q-separator class="q-my-md" />

              <div class="info-line">
                <span>Nombre</span>
                <strong>{{ nombrePasajero }}</strong>
              </div>

              <div class="info-line">
                <span>CI</span>
                <strong>{{ pasajero.persona?.ci || 'No registrado' }}</strong>
              </div>

              <div class="info-line">
                <span>Teléfono</span>
                <strong>{{ pasajero.persona?.telefono || 'No registrado' }}</strong>
              </div>

              <div class="info-line">
                <span>Correo</span>
                <strong>{{ pasajero.email || 'No registrado' }}</strong>
              </div>

              <div class="info-line">
                <span>ID de pasajero</span>
                <strong>#{{ pasajero.id }}</strong>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-md-7">
          <q-card
            flat
            bordered
            class="support-card full-height"
          >
            <q-card-section>
              <div class="row items-center">
                <div class="col">
                  <div class="text-subtitle1 text-weight-bold text-green-9">
                    <q-icon name="route" class="q-mr-xs" />
                    Estado operativo
                  </div>
                </div>

                <q-badge
                  v-if="viajeActivo"
                  :color="colorEstado(viajeActivo.estado)"
                  class="q-pa-sm"
                >
                  {{ viajeActivo.estado }}
                </q-badge>
              </div>

              <q-separator class="q-my-md" />

              <template v-if="viajeActivo">
                <div class="text-caption text-grey-6">
                  Viaje activo #{{ viajeActivo.id }}
                </div>

                <div class="route-line q-mt-md">
                  <q-icon name="radio_button_checked" color="positive" />
                  <div>
                    <div class="text-caption text-grey-6">Origen</div>
                    <div class="text-weight-medium">{{ viajeActivo.origen }}</div>
                  </div>
                </div>

                <div class="route-line q-mt-sm">
                  <q-icon name="location_on" color="negative" />
                  <div>
                    <div class="text-caption text-grey-6">Destino</div>
                    <div class="text-weight-medium">{{ viajeActivo.destino }}</div>
                  </div>
                </div>

                <div class="row q-col-gutter-md q-mt-sm">
                  <div class="col-6">
                    <div class="text-caption text-grey-6">Tarifa</div>
                    <div class="text-subtitle1 text-weight-bold">
                      {{ dinero(viajeActivo.precio) }}
                    </div>
                  </div>

                  <div class="col-6">
                    <div class="text-caption text-grey-6">Conductor</div>
                    <div class="text-subtitle1 text-weight-bold">
                      {{ nombreConductor(viajeActivo) }}
                    </div>
                  </div>
                </div>
              </template>

              <div
                v-else
                class="column items-center q-pa-lg text-grey-6"
              >
                <q-icon name="check_circle" color="positive" size="46px" />
                <div class="text-subtitle1 text-weight-bold q-mt-sm">
                  Sin viaje activo
                </div>
                <div class="text-caption">
                  El pasajero no tiene una solicitud en curso.
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <q-card flat bordered class="support-card">
        <q-card-section>
          <div class="row items-center justify-between q-mb-md">
            <div>
              <div class="text-h6 text-weight-bold text-grey-9">
                Historial de viajes
              </div>
              <div class="text-caption text-grey-6">
                Consulta administrativa del historial del pasajero.
              </div>
            </div>

            <q-badge color="green-8" class="q-pa-sm">
              {{ viajes.length }}
            </q-badge>
          </div>

          <q-table
            flat
            bordered
            :rows="viajes"
            :columns="columnas"
            row-key="id"
            :rows-per-page-options="[5, 10, 20]"
            no-data-label="Este pasajero todavía no tiene viajes registrados."
          >
            <template #body-cell-conductor="props">
              <q-td :props="props">
                {{ nombreConductor(props.row) }}
              </q-td>
            </template>

            <template #body-cell-precio="props">
              <q-td :props="props" class="text-weight-bold text-green-9">
                {{ dinero(props.row.precio) }}
              </q-td>
            </template>

            <template #body-cell-estado="props">
              <q-td :props="props">
                <q-badge :color="colorEstado(props.row.estado)">
                  {{ props.row.estado || 'Sin estado' }}
                </q-badge>
              </q-td>
            </template>

            <template #body-cell-calificacion="props">
              <q-td :props="props">
                <span v-if="props.row.calificacion">
                  {{ props.row.calificacion }}/5 ⭐
                </span>
                <span v-else class="text-grey-6">
                  Sin calificar
                </span>
              </q-td>
            </template>
          </q-table>
        </q-card-section>
      </q-card>
    </template>
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
  useRoute,
  useRouter
} from 'vue-router'

import {
  api
} from 'src/boot/axios.js'

const $q = useQuasar()
const route = useRoute()
const router = useRouter()

const cargando = ref(false)
const pasajero = ref(null)
const viajes = ref([])

const pasajeroId = computed(() =>
  Number(route.params.id || 0)
)

const nombrePasajero = computed(() => {
  return (
    pasajero.value?.persona?.nombre
    || pasajero.value?.nombre
    || `Pasajero #${pasajeroId.value}`
  )
})

const viajeActivo = computed(() => {
  const activos = [
    'pendiente',
    'buscando conductor',
    'aceptado',
    'llegó',
    'en curso'
  ]

  return viajes.value.find(
    viaje => activos.includes(
      normalizar(viaje?.estado)
    )
  ) || null
})

const totalFinalizados = computed(() =>
  viajes.value.filter(
    viaje => normalizar(viaje?.estado) === 'finalizado'
  ).length
)

const totalCancelados = computed(() =>
  viajes.value.filter(
    viaje => [
      'cancelado',
      'expirado'
    ].includes(
      normalizar(viaje?.estado)
    )
  ).length
)

const totalGastado = computed(() =>
  viajes.value
    .filter(
      viaje => normalizar(viaje?.estado) === 'finalizado'
    )
    .reduce(
      (total, viaje) =>
        total + numero(viaje?.precio),
      0
    )
)

const columnas = [
  {
    name: 'id',
    label: 'Viaje',
    field: 'id',
    align: 'left'
  },
  {
    name: 'fecha',
    label: 'Fecha',
    field: 'fecha',
    align: 'left'
  },
  {
    name: 'origen',
    label: 'Origen',
    field: 'origen',
    align: 'left'
  },
  {
    name: 'destino',
    label: 'Destino',
    field: 'destino',
    align: 'left'
  },
  {
    name: 'conductor',
    label: 'Conductor',
    field: 'conductor',
    align: 'left'
  },
  {
    name: 'precio',
    label: 'Tarifa',
    field: 'precio',
    align: 'left'
  },
  {
    name: 'estado',
    label: 'Estado',
    field: 'estado',
    align: 'left'
  },
  {
    name: 'calificacion',
    label: 'Calificación',
    field: 'calificacion',
    align: 'left'
  }
]

function normalizar(valor) {
  return String(valor || '')
    .trim()
    .toLocaleLowerCase('es')
}

function numero(valor) {
  const n = Number.parseFloat(valor)
  return Number.isFinite(n) ? n : 0
}

function dinero(valor) {
  return `Bs. ${numero(valor).toFixed(2)}`
}

function nombreConductor(viaje) {
  return (
    viaje?.mototaxista?.persona?.nombre
    || viaje?.mototaxista?.nombre
    || (
      viaje?.mototaxista_id
        ? `Mototaxista #${viaje.mototaxista_id}`
        : 'Sin conductor'
    )
  )
}

function colorEstado(estado) {
  switch (normalizar(estado)) {
    case 'finalizado':
      return 'positive'
    case 'aceptado':
    case 'llegó':
    case 'en curso':
      return 'blue-8'
    case 'pendiente':
    case 'buscando conductor':
      return 'orange-8'
    case 'cancelado':
    case 'expirado':
      return 'negative'
    default:
      return 'grey-7'
  }
}

function volver() {
  router.push('/pasajeros')
}

async function cargar() {
  if (!pasajeroId.value) {
    router.replace('/pasajeros')
    return
  }

  cargando.value = true

  try {
    const [
      respuestaPasajero,
      respuestaViajes
    ] = await Promise.all([
      api.get(
        `/pasajeros/${pasajeroId.value}`
      ),
      api.get(
        '/solicitudes',
        {
          params: {
            id_pasajero: pasajeroId.value
          }
        }
      )
    ])

    pasajero.value =
      respuestaPasajero.data || null

    viajes.value =
      Array.isArray(
        respuestaViajes.data
      )
        ? respuestaViajes.data
        : []
  } catch (error) {
    console.error(
      'Error cargando modo soporte pasajero:',
      error
    )

    $q.notify({
      type: 'negative',
      position: 'top',
      message:
        error?.response?.data?.message
        || error?.response?.data?.mensaje
        || 'No se pudo cargar el perfil de soporte del pasajero.'
    })
  } finally {
    cargando.value = false
  }
}

onMounted(
  cargar
)
</script>

<style scoped>
.support-page {
  min-height: 100%;
  background: #f1f8e9;
}

.support-banner {
  background: #1b5e20;
  border-left: 5px solid #c62828;
}

.support-card,
.stat-card {
  border-color: #d6e6d1;
  border-radius: 14px;
}

.stat-card {
  border-left: 4px solid #2e7d32;
}

.info-line {
  display: flex;
  justify-content: space-between;
  gap: 18px;
  padding: 9px 0;
  border-bottom: 1px solid #edf2ea;
}

.info-line span {
  color: #757575;
}

.info-line strong {
  text-align: right;
}

.route-line {
  display: flex;
  gap: 12px;
  align-items: flex-start;
}

@media (max-width: 600px) {
  .info-line {
    display: block;
  }

  .info-line strong {
    display: block;
    text-align: left;
    margin-top: 2px;
  }
}
</style>
