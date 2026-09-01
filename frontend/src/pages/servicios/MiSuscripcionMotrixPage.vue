<template>
  <q-page class="q-pa-md bg-grey-1">
    <div class="row items-center q-col-gutter-md q-mb-lg">
      <div class="col">
        <div class="text-h5 text-weight-bold text-green-9">
          Mi suscripción MOTRIX
        </div>
        <div class="text-body2 text-grey-7">
          Consulta tu plan, vencimiento, cuotas, pagos y recordatorios.
        </div>
      </div>

      <div class="col-auto">
        <q-btn
          outline
          color="green-8"
          icon="refresh"
          label="Actualizar"
          no-caps
          :loading="loading"
          @click="cargarTodo"
        />
      </div>
    </div>

    <q-inner-loading
      :showing="loading && !cargado"
      class="fixed-center"
    >
      <q-spinner-dots
        size="48px"
        color="green-8"
      />
    </q-inner-loading>

    <q-banner
      v-if="cargado && !configurada"
      rounded
      class="bg-orange-1 text-orange-10"
    >
      <template #avatar>
        <q-icon
          name="info"
          color="orange-9"
        />
      </template>
      Tu sindicato todavía no configuró una suscripción MOTRIX para tu cuenta.
      Cuando sea asignada podrás consultar aquí el plan, la cuota y el vencimiento.
    </q-banner>

    <template v-if="configurada && suscripcion">
      <q-card
        flat
        bordered
        class="subscription-hero q-mb-lg"
      >
        <q-card-section>
          <div class="row q-col-gutter-lg items-center">
            <div class="col-12 col-md">
              <div class="row items-center no-wrap">
                <q-avatar
                  size="58px"
                  color="green-1"
                  text-color="green-9"
                  icon="workspace_premium"
                />
                <div class="q-ml-md">
                  <div class="text-caption text-grey-7">
                    Plan actual
                  </div>
                  <div class="text-h5 text-weight-bold text-green-9">
                    {{ suscripcion.plan?.nombre || 'MOTRIX Conductor' }}
                  </div>
                  <div class="text-body2 text-grey-7">
                    {{ suscripcion.sindicato?.nombre || 'Sindicato' }}
                  </div>
                </div>
              </div>
            </div>

            <div class="col-6 col-md-auto">
              <div class="metric">
                <div class="text-caption text-grey-7">
                  Cuota
                </div>
                <div class="text-h5 text-weight-bold">
                  {{ dinero(suscripcion.plan?.monto) }}
                </div>
              </div>
            </div>

            <div class="col-6 col-md-auto">
              <div class="metric">
                <div class="text-caption text-grey-7">
                  Estado
                </div>
                <q-chip
                  dense
                  :color="colorEstado(estadoActual)"
                  text-color="white"
                  :label="estadoActual"
                />
              </div>
            </div>
          </div>
        </q-card-section>
      </q-card>

      <div class="row q-col-gutter-md q-mb-lg">
        <div class="col-12 col-sm-6 col-lg-3">
          <q-card flat bordered class="info-card">
            <q-card-section>
              <div class="text-caption text-grey-7">
                Vencimiento
              </div>
              <div class="text-h6 text-weight-bold">
                {{ fecha(suscripcion.fecha_vencimiento) }}
              </div>
              <div
                :class="[
                  'text-caption',
                  numero(suscripcion.dias_restantes) < 0
                    ? 'text-negative'
                    : 'text-grey-7'
                ]"
              >
                {{ textoDias(suscripcion.dias_restantes) }}
              </div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
          <q-card flat bordered class="info-card">
            <q-card-section>
              <div class="text-caption text-grey-7">
                Periodo pendiente
              </div>
              <div class="text-h6 text-weight-bold">
                {{ cuotaPendiente?.periodo || 'Sin deuda' }}
              </div>
              <div class="text-caption text-grey-7">
                {{
                  cuotaPendiente
                    ? dinero(cuotaPendiente.monto_esperado)
                    : 'Al día'
                }}
              </div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
          <q-card flat bordered class="info-card">
            <q-card-section>
              <div class="text-caption text-grey-7">
                Último pago
              </div>
              <div class="text-h6 text-weight-bold">
                {{ ultimoPago?.periodo || '—' }}
              </div>
              <div class="text-caption text-grey-7">
                {{
                  ultimoPago
                    ? fechaHora(ultimoPago.fecha_pago)
                    : 'Todavía no registrado'
                }}
              </div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
          <q-card flat bordered class="info-card">
            <q-card-section>
              <div class="text-caption text-grey-7">
                Alertas pendientes
              </div>
              <div class="text-h6 text-weight-bold">
                {{ alertas.length }}
              </div>
              <div class="text-caption text-grey-7">
                Avisos de renovación MOTRIX
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <q-banner
        v-if="cuotaPendiente"
        rounded
        :class="bannerClase"
        class="q-mb-lg"
      >
        <template #avatar>
          <q-icon
            :name="bannerIcono"
            size="28px"
          />
        </template>

        <div class="text-weight-bold">
          {{ bannerTitulo }}
        </div>
        <div>
          Periodo {{ cuotaPendiente.periodo }} ·
          {{ dinero(cuotaPendiente.monto_esperado) }} ·
          vence {{ fecha(cuotaPendiente.fecha_vencimiento) }}.
        </div>
      </q-banner>

      <q-card
        v-if="alertas.length"
        flat
        bordered
        class="q-mb-lg"
      >
        <q-card-section>
          <div class="text-subtitle1 text-weight-bold">
            Recordatorios
          </div>
        </q-card-section>

        <q-separator />

        <q-list separator>
          <q-item
            v-for="alerta in alertas"
            :key="alerta.id"
          >
            <q-item-section avatar>
              <q-avatar
                color="orange-1"
                text-color="orange-9"
                icon="notifications_active"
              />
            </q-item-section>

            <q-item-section>
              <q-item-label class="text-weight-bold">
                {{ alerta.titulo }}
              </q-item-label>
              <q-item-label caption>
                {{ alerta.mensaje }}
              </q-item-label>
            </q-item-section>

            <q-item-section side>
              <q-btn
                flat
                round
                dense
                icon="done"
                color="green-8"
                @click="marcarLeida(alerta)"
              >
                <q-tooltip>Marcar como leída</q-tooltip>
              </q-btn>
            </q-item-section>
          </q-item>
        </q-list>
      </q-card>

      <q-card flat bordered>
        <q-card-section>
          <div class="text-subtitle1 text-weight-bold">
            Historial de cuotas y renovaciones
          </div>
          <div class="text-caption text-grey-7">
            Últimos movimientos registrados en tu suscripción MOTRIX.
          </div>
        </q-card-section>

        <q-separator />

        <q-table
          flat
          row-key="id"
          :rows="pagos"
          :columns="columnas"
          :pagination="{ rowsPerPage: 10 }"
          no-data-label="Todavía no tienes movimientos de suscripción."
        >
          <template #body-cell-monto="props">
            <q-td :props="props">
              {{ dinero(props.row.monto_esperado) }}
            </q-td>
          </template>

          <template #body-cell-estado="props">
            <q-td :props="props">
              <q-chip
                dense
                :color="colorPago(props.row.estado)"
                text-color="white"
                :label="props.row.estado"
              />
            </q-td>
          </template>

          <template #body-cell-fecha="props">
            <q-td :props="props">
              {{
                props.row.fecha_pago
                  ? fechaHora(props.row.fecha_pago)
                  : fecha(props.row.fecha_vencimiento)
              }}
            </q-td>
          </template>

          <template #body-cell-canal="props">
            <q-td :props="props">
              {{
                props.row.canal_cobro === 'motrix_directo'
                  ? 'MOTRIX directo'
                  : 'Sindicato'
              }}
            </q-td>
          </template>
        </q-table>
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
  api
} from 'src/boot/axios.js'

const $q = useQuasar()

const loading = ref(false)
const cargado = ref(false)
const configurada = ref(false)
const suscripcion = ref(null)
const pagos = ref([])
const alertas = ref([])

const columnas = [
  {
    name: 'periodo',
    label: 'Periodo',
    field: 'periodo',
    align: 'left'
  },
  {
    name: 'monto',
    label: 'Cuota',
    field: 'monto_esperado',
    align: 'right'
  },
  {
    name: 'estado',
    label: 'Estado',
    field: 'estado',
    align: 'center'
  },
  {
    name: 'fecha',
    label: 'Pago / vencimiento',
    field: 'fecha_pago',
    align: 'left'
  },
  {
    name: 'forma',
    label: 'Forma',
    field: 'forma_pago',
    align: 'left'
  },
  {
    name: 'canal',
    label: 'Canal',
    field: 'canal_cobro',
    align: 'left'
  }
]

const estadoActual = computed(() =>
  suscripcion.value?.estado_calculado
  || suscripcion.value?.estado
  || 'No configurada'
)

const cuotaPendiente = computed(() =>
  pagos.value.find(
    item => [
      'pendiente',
      'vencido'
    ].includes(
      String(item.estado || '')
        .trim()
        .toLowerCase()
    )
  ) || null
)

const ultimoPago = computed(() =>
  pagos.value.find(
    item =>
      String(item.estado || '')
        .trim()
        .toLowerCase()
      === 'pagado'
  ) || null
)

const bannerClase = computed(() => {
  const estado =
    String(cuotaPendiente.value?.estado || '')
      .trim()
      .toLowerCase()

  return estado === 'vencido'
    ? 'bg-red-1 text-red-10'
    : 'bg-orange-1 text-orange-10'
})

const bannerIcono = computed(() =>
  String(cuotaPendiente.value?.estado || '')
    .trim()
    .toLowerCase()
  === 'vencido'
    ? 'error'
    : 'schedule'
)

const bannerTitulo = computed(() =>
  String(cuotaPendiente.value?.estado || '')
    .trim()
    .toLowerCase()
  === 'vencido'
    ? 'Tienes una cuota vencida'
    : 'Tienes una renovación pendiente'
)

async function cargarTodo() {
  loading.value = true

  try {
    const [
      respuestaSuscripcion,
      respuestaPagos,
      respuestaAlertas
    ] = await Promise.all([
      api.get(
        '/conductor/suscripcion-motrix'
      ),
      api.get(
        '/conductor/suscripcion-motrix/pagos'
      ),
      api.get(
        '/conductor/suscripcion-motrix/alertas'
      )
    ])

    configurada.value =
      Boolean(
        respuestaSuscripcion.data?.configurada
      )

    suscripcion.value =
      respuestaSuscripcion.data?.data
      || null

    pagos.value =
      respuestaPagos.data?.data
      || suscripcion.value?.pagos
      || []

    alertas.value =
      respuestaAlertas.data?.data
      || []

    cargado.value = true
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      message: mensajeError(error)
    })
  } finally {
    loading.value = false
  }
}

async function marcarLeida(alerta) {
  try {
    await api.post(
      `/conductor/suscripcion-motrix/alertas/${alerta.id}/leida`
    )

    alertas.value =
      alertas.value.filter(
        item => item.id !== alerta.id
      )

    $q.notify({
      type: 'positive',
      position: 'top',
      message: 'Recordatorio marcado como leído.'
    })
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      message: mensajeError(error)
    })
  }
}

function numero(valor) {
  const n = Number(valor)
  return Number.isFinite(n) ? n : 0
}

function dinero(valor) {
  return new Intl.NumberFormat(
    'es-BO',
    {
      style: 'currency',
      currency: 'BOB',
      minimumFractionDigits: 2
    }
  ).format(
    numero(valor)
  )
}

function fecha(valor) {
  if (!valor) return '—'

  const texto =
    String(valor).slice(0, 10)

  const [anio, mes, dia] =
    texto.split('-')

  if (!anio || !mes || !dia) {
    return String(valor)
  }

  return `${dia}/${mes}/${anio}`
}

function fechaHora(valor) {
  if (!valor) return '—'

  const fechaValor = new Date(valor)

  if (Number.isNaN(fechaValor.getTime())) {
    return String(valor)
  }

  return new Intl.DateTimeFormat(
    'es-BO',
    {
      dateStyle: 'short',
      timeStyle: 'short'
    }
  ).format(fechaValor)
}

function textoDias(valor) {
  const dias = numero(valor)

  if (dias < 0) {
    return `${Math.abs(dias)} día(s) desde el vencimiento`
  }

  if (dias === 0) {
    return 'Vence hoy'
  }

  return `${dias} día(s) restantes`
}

function colorEstado(valor) {
  const estado =
    String(valor || '')
      .trim()
      .toLowerCase()

  if (estado === 'activa') return 'green-8'
  if (estado === 'por vencer') return 'orange-8'
  if (estado === 'periodo de gracia') return 'amber-9'
  if (estado === 'vencida') return 'negative'
  if (estado === 'suspendida') return 'grey-8'

  return 'blue-grey-7'
}

function colorPago(valor) {
  const estado =
    String(valor || '')
      .trim()
      .toLowerCase()

  if (estado === 'pagado') return 'green-8'
  if (estado === 'pendiente') return 'orange-8'
  if (estado === 'vencido') return 'negative'
  if (estado === 'exonerado') return 'blue-8'

  return 'grey-7'
}

function mensajeError(error) {
  const errores =
    error.response?.data?.errors

  const primero =
    errores
      ? Object.values(errores)
        .flat()
        .find(Boolean)
      : null

  return (
    primero
    || error.response?.data?.message
    || error.response?.data?.mensaje
    || 'No se pudo cargar tu suscripción MOTRIX.'
  )
}

onMounted(
  cargarTodo
)
</script>

<style scoped>
.subscription-hero,
.info-card {
  border-radius: 14px;
}

.info-card {
  height: 100%;
}

.metric {
  min-width: 130px;
}
</style>
