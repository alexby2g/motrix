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
            Modo soporte · Mototaxista
          </div>
          <div class="text-caption">
            Vista administrativa de solo consulta. No estás operando como el conductor.
          </div>
        </div>

        <div class="col-auto">
          <q-btn
            outline
            color="white"
            icon="arrow_back"
            label="Volver a mototaxistas"
            @click="volver"
          />
        </div>
      </div>
    </q-banner>

    <q-inner-loading
      :showing="cargando"
      label="Cargando información del mototaxista..."
      color="green-8"
    />

    <template v-if="!cargando && mototaxista">
      <q-card
        flat
        bordered
        class="support-card q-mb-md"
      >
        <q-card-section class="row items-center q-col-gutter-lg">
          <div class="col-auto">
            <q-avatar
              size="82px"
              color="green-1"
              text-color="green-9"
              icon="two_wheeler"
            />
          </div>

          <div class="col">
            <div class="text-caption text-grey-6">
              Mototaxista #{{ mototaxista.id }}
            </div>
            <div class="text-h5 text-weight-bold text-grey-9">
              {{ nombreConductor }}
            </div>

            <div class="row q-gutter-sm q-mt-sm">
              <q-chip
                dense
                :color="normalizar(mototaxista.estado) === 'activo' ? 'green-1' : 'red-1'"
                :text-color="normalizar(mototaxista.estado) === 'activo' ? 'green-9' : 'red-9'"
                icon="verified"
              >
                {{ mototaxista.estado || 'Sin estado' }}
              </q-chip>

              <q-chip
                dense
                :color="mototaxista.disponible ? 'green-1' : 'grey-3'"
                :text-color="mototaxista.disponible ? 'green-9' : 'grey-8'"
                :icon="mototaxista.disponible ? 'wifi' : 'wifi_off'"
              >
                {{ mototaxista.disponible ? 'Disponible' : 'No disponible' }}
              </q-chip>

              <q-chip
                dense
                color="blue-1"
                text-color="blue-9"
                icon="groups"
              >
                {{ mototaxista.sindicato?.nombre || 'Sin sindicato' }}
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
                {{ viajesConductor.length }}
              </div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-6 col-md-3">
          <q-card flat bordered class="stat-card">
            <q-card-section>
              <div class="text-caption text-grey-6">Finalizados</div>
              <div class="text-h5 text-weight-bold text-positive">
                {{ viajesFinalizados }}
              </div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-6 col-md-3">
          <q-card flat bordered class="stat-card">
            <q-card-section>
              <div class="text-caption text-grey-6">Recaudación registrada</div>
              <div class="text-h5 text-weight-bold text-green-9">
                {{ dinero(totalRecaudado) }}
              </div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-6 col-md-3">
          <q-card flat bordered class="stat-card">
            <q-card-section>
              <div class="text-caption text-grey-6">Reputación</div>
              <div class="text-h5 text-weight-bold text-orange-8">
                {{ promedioCalificacion.toFixed(2) }}/5
              </div>
              <div class="text-caption text-grey-6">
                {{ totalCalificaciones }} calificaciones
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
                Perfil y afiliación
              </div>

              <q-separator class="q-my-md" />

              <div class="info-line">
                <span>Nombre</span>
                <strong>{{ nombreConductor }}</strong>
              </div>

              <div class="info-line">
                <span>CI</span>
                <strong>{{ mototaxista.persona?.ci || 'No registrado' }}</strong>
              </div>

              <div class="info-line">
                <span>Teléfono</span>
                <strong>{{ telefonoConductor }}</strong>
              </div>

              <div class="info-line">
                <span>Sindicato</span>
                <strong>{{ mototaxista.sindicato?.nombre || 'No registrado' }}</strong>
              </div>

              <div class="info-line">
                <span>N.º de chaleco</span>
                <strong>{{ mototaxista.nro_chaleco || 'No registrado' }}</strong>
              </div>

              <div class="info-line">
                <span>Código QR</span>
                <strong>{{ mototaxista.codigo_qr ? 'Generado' : 'Pendiente' }}</strong>
              </div>

              <div class="info-line">
                <span>Cuenta conductor</span>
                <strong>{{ cuentaConductor }}</strong>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-md-7">
          <q-card flat bordered class="support-card full-height">
            <q-card-section>
              <div class="text-subtitle1 text-weight-bold text-green-9">
                <q-icon name="gps_fixed" class="q-mr-xs" />
                Estado operativo
              </div>

              <q-separator class="q-my-md" />

              <div class="row q-col-gutter-md">
                <div class="col-12 col-sm-6">
                  <div class="operation-box">
                    <div class="text-caption text-grey-6">Última conexión</div>
                    <div class="text-weight-bold">
                      {{ formatearFechaHora(mototaxista.ultima_conexion) }}
                    </div>
                  </div>
                </div>

                <div class="col-12 col-sm-6">
                  <div class="operation-box">
                    <div class="text-caption text-grey-6">Última ubicación</div>
                    <div class="text-weight-bold">
                      {{ ubicacionTexto }}
                    </div>
                  </div>
                </div>
              </div>

              <q-separator class="q-my-md" />

              <template v-if="viajeActivo">
                <div class="row items-center">
                  <div class="col">
                    <div class="text-caption text-grey-6">
                      Viaje activo #{{ viajeActivo.id }}
                    </div>
                    <div class="text-subtitle1 text-weight-bold">
                      {{ viajeActivo.estado }}
                    </div>
                  </div>

                  <q-badge
                    :color="colorEstado(viajeActivo.estado)"
                    class="q-pa-sm"
                  >
                    {{ viajeActivo.estado }}
                  </q-badge>
                </div>

                <div class="route-line q-mt-md">
                  <q-icon name="person" color="blue-8" />
                  <div>
                    <div class="text-caption text-grey-6">Pasajero</div>
                    <div class="text-weight-medium">
                      {{ nombrePasajero(viajeActivo) }}
                    </div>
                  </div>
                </div>

                <div class="route-line q-mt-sm">
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
                  No se encontró un viaje operativo para este conductor.
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <q-card
        v-if="motocicletas.length"
        flat
        bordered
        class="support-card q-mb-md"
      >
        <q-card-section>
          <div class="text-h6 text-weight-bold text-grey-9">
            Motocicletas registradas
          </div>

          <div class="row q-col-gutter-md q-mt-xs">
            <div
              v-for="moto in motocicletas"
              :key="moto.id"
              class="col-12 col-sm-6 col-md-4"
            >
              <q-card flat bordered class="moto-card">
                <q-card-section>
                  <div class="text-weight-bold">
                    {{ moto.marca || 'Motocicleta' }}
                    {{ moto.modelo || '' }}
                  </div>
                  <div class="text-caption text-grey-6 q-mt-xs">
                    Placa: {{ moto.placa || 'No registrada' }}
                  </div>
                  <div class="text-caption text-grey-6">
                    Color: {{ moto.color || 'No registrado' }}
                  </div>
                </q-card-section>
              </q-card>
            </div>
          </div>
        </q-card-section>
      </q-card>

      <q-card flat bordered class="support-card">
        <q-card-section>
          <div class="row items-center justify-between q-mb-md">
            <div>
              <div class="text-h6 text-weight-bold text-grey-9">
                Historial del conductor
              </div>
              <div class="text-caption text-grey-6">
                Viajes relacionados con este mototaxista.
              </div>
            </div>

            <q-badge color="green-8" class="q-pa-sm">
              {{ viajesConductor.length }}
            </q-badge>
          </div>

          <q-table
            flat
            bordered
            :rows="viajesConductor"
            :columns="columnas"
            row-key="id"
            :rows-per-page-options="[5, 10, 20]"
            no-data-label="Este conductor todavía no tiene viajes registrados."
          >
            <template #body-cell-pasajero="props">
              <q-td :props="props">
                {{ nombrePasajero(props.row) }}
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
const mototaxista = ref(null)
const solicitudes = ref([])
const servicios = ref([])
const pagos = ref([])

const mototaxistaId = computed(() =>
  Number(route.params.id || 0)
)

const nombreConductor = computed(() => {
  const persona = mototaxista.value?.persona

  return [
    persona?.nombre,
    persona?.apellidos
  ]
    .filter(Boolean)
    .join(' ')
    .trim()
    || `Mototaxista #${mototaxistaId.value}`
})

const telefonoConductor = computed(() =>
  mototaxista.value?.telefono
  || mototaxista.value?.persona?.telefono
  || 'No registrado'
)

const cuentaConductor = computed(() => {
  return (
    mototaxista.value?.usuario_conductor?.nickname
    || mototaxista.value?.usuario_conductor?.email
    || 'No creada'
  )
})

const motocicletas = computed(() => {
  return Array.isArray(
    mototaxista.value?.motocicletas
  )
    ? mototaxista.value.motocicletas
    : []
})

const serviciosConductor = computed(() => {
  return servicios.value.filter(
    servicio =>
      obtenerMototaxistaIdServicio(servicio)
      === mototaxistaId.value
  )
})

const idsServicios = computed(() => {
  return new Set(
    serviciosConductor.value.map(
      servicio => Number(servicio.id)
    )
  )
})

const idsSolicitudesServicios = computed(() => {
  return new Set(
    serviciosConductor.value
      .map(
        servicio => Number(
          servicio.id_solicitud
          ?? servicio.solicitud_id
          ?? servicio.solicitud?.id
          ?? 0
        )
      )
      .filter(Boolean)
  )
})

const viajesConductor = computed(() => {
  return solicitudes.value.filter(
    solicitud => {
      const idConductor = Number(
        solicitud?.mototaxista_id
        ?? solicitud?.id_mototaxista
        ?? solicitud?.mototaxista?.id
        ?? 0
      )

      return (
        idConductor === mototaxistaId.value
        || idsSolicitudesServicios.value.has(
          Number(solicitud?.id || 0)
        )
      )
    }
  )
})

const viajeActivo = computed(() => {
  const estadosActivos = [
    'aceptado',
    'llegó',
    'en curso'
  ]

  return viajesConductor.value.find(
    viaje => estadosActivos.includes(
      normalizar(viaje?.estado)
    )
  ) || null
})

const viajesFinalizados = computed(() =>
  viajesConductor.value.filter(
    viaje =>
      normalizar(viaje?.estado)
      === 'finalizado'
  ).length
)

const pagosConductor = computed(() => {
  return pagos.value.filter(
    pago => {
      const servicioId = Number(
        pago?.id_servicio
        ?? pago?.servicio?.id
        ?? 0
      )

      const conductorPago = Number(
        pago?.servicio?.id_mototaxista
        ?? pago?.servicio?.mototaxista_id
        ?? pago?.servicio?.mototaxista?.id
        ?? 0
      )

      return (
        idsServicios.value.has(servicioId)
        || conductorPago === mototaxistaId.value
      )
    }
  )
})

const totalRecaudado = computed(() =>
  pagosConductor.value
    .filter(
      pago =>
        normalizar(pago?.estado)
        === 'completado'
    )
    .reduce(
      (total, pago) =>
        total + numero(pago?.monto),
      0
    )
)

const calificaciones = computed(() =>
  viajesConductor.value
    .map(
      viaje => numeroNullable(
        viaje?.calificacion
      )
    )
    .filter(
      valor => valor !== null
    )
)

const totalCalificaciones = computed(() =>
  calificaciones.value.length
)

const promedioCalificacion = computed(() => {
  if (!calificaciones.value.length) {
    return 0
  }

  const total = calificaciones.value.reduce(
    (suma, valor) => suma + valor,
    0
  )

  return total / calificaciones.value.length
})

const ubicacionTexto = computed(() => {
  const lat = numeroNullable(
    mototaxista.value?.latitud
  )
  const lng = numeroNullable(
    mototaxista.value?.longitud
  )

  if (lat === null || lng === null) {
    return 'No registrada'
  }

  return `${lat.toFixed(5)}, ${lng.toFixed(5)}`
})

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
    name: 'pasajero',
    label: 'Pasajero',
    field: 'pasajero',
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

function numeroNullable(valor) {
  if (
    valor === null
    || valor === undefined
    || valor === ''
  ) {
    return null
  }

  const n = Number.parseFloat(valor)
  return Number.isFinite(n) ? n : null
}

function dinero(valor) {
  return `Bs. ${numero(valor).toFixed(2)}`
}

function nombrePasajero(viaje) {
  return (
    viaje?.pasajero?.persona?.nombre
    || viaje?.pasajero?.nombre
    || (
      viaje?.id_pasajero
        ? `Pasajero #${viaje.id_pasajero}`
        : 'No registrado'
    )
  )
}

function obtenerMototaxistaIdServicio(servicio) {
  return Number(
    servicio?.id_mototaxista
    ?? servicio?.mototaxista_id
    ?? servicio?.mototaxista?.id
    ?? 0
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

function formatearFechaHora(valor) {
  if (!valor) {
    return 'No registrada'
  }

  const fecha = new Date(
    String(valor).replace(' ', 'T')
  )

  if (Number.isNaN(fecha.getTime())) {
    return String(valor)
  }

  return new Intl.DateTimeFormat(
    'es-BO',
    {
      dateStyle: 'medium',
      timeStyle: 'short'
    }
  ).format(fecha)
}

function volver() {
  router.push('/mototaxistas')
}

async function cargarListaSegura(url) {
  try {
    const respuesta = await api.get(url)

    return Array.isArray(respuesta.data)
      ? respuesta.data
      : []
  } catch (error) {
    console.warn(
      `No se pudo cargar ${url} para soporte:`,
      error
    )

    return []
  }
}

async function cargar() {
  if (!mototaxistaId.value) {
    router.replace('/mototaxistas')
    return
  }

  cargando.value = true

  try {
    const [
      respuestaMototaxista,
      listaSolicitudes,
      listaServicios,
      listaPagos
    ] = await Promise.all([
      api.get(
        `/mototaxistas/${mototaxistaId.value}`
      ),
      cargarListaSegura('/solicitudes'),
      cargarListaSegura('/servicios'),
      cargarListaSegura('/pagos')
    ])

    mototaxista.value =
      respuestaMototaxista.data || null

    solicitudes.value =
      listaSolicitudes

    servicios.value =
      listaServicios

    pagos.value =
      listaPagos
  } catch (error) {
    console.error(
      'Error cargando modo soporte conductor:',
      error
    )

    $q.notify({
      type: 'negative',
      position: 'top',
      message:
        error?.response?.data?.message
        || error?.response?.data?.mensaje
        || 'No se pudo cargar el perfil de soporte del mototaxista.'
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
.stat-card,
.moto-card {
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

.operation-box {
  min-height: 72px;
  padding: 14px;
  border-radius: 12px;
  background: #f7faf5;
  border: 1px solid #e0ebdc;
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
