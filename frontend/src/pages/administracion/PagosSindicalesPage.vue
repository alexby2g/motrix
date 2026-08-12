<template>
  <q-page class="q-pa-md q-pa-lg-md pagos-sindicales-page">
    <div class="row items-center q-col-gutter-md q-mb-md">
      <div class="col">
        <div class="row items-center no-wrap">
          <q-avatar
            color="green-1"
            text-color="green-9"
            icon="account_balance_wallet"
            size="50px"
            class="q-mr-md"
          />

          <div>
            <div class="text-h5 text-weight-bold text-green-9">
              Pagos y aportes sindicales
            </div>

            <div class="text-caption text-grey-7">
              Afiliaciones, inscripciones, aportes y control de pendientes.
            </div>
          </div>
        </div>
      </div>

      <div class="col-auto">
        <q-btn
          color="green-8"
          icon="add_card"
          label="Registrar pago"
          unelevated
          @click="abrirFormulario()"
        />
      </div>
    </div>

    <q-banner
      v-if="esSecretario"
      rounded
      class="bg-green-1 text-green-10 q-mb-md"
    >
      <template #avatar>
        <q-icon
          name="verified_user"
          color="green-8"
        />
      </template>

      Estás administrando únicamente los pagos y aportes de
      <strong>{{ nombreSindicatoActual }}</strong>.
    </q-banner>

    <div class="row q-col-gutter-md q-mb-md">
      <div class="col-6 col-md-3">
        <q-card flat bordered class="stat-card">
          <q-card-section>
            <div class="text-caption text-grey-7">
              Total recaudado
            </div>
            <div class="text-h5 text-weight-bold text-green-9">
              {{ dinero(totalRecaudado) }}
            </div>
            <div class="text-caption text-grey-6">
              Solo registros pagados
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-6 col-md-3">
        <q-card flat bordered class="stat-card">
          <q-card-section>
            <div class="text-caption text-grey-7">
              Afiliación / inscripción
            </div>
            <div class="text-h5 text-weight-bold text-blue-8">
              {{ dinero(totalAfiliaciones) }}
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-6 col-md-3">
        <q-card flat bordered class="stat-card">
          <q-card-section>
            <div class="text-caption text-grey-7">
              Aportes
            </div>
            <div class="text-h5 text-weight-bold text-purple-7">
              {{ dinero(totalAportes) }}
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-6 col-md-3">
        <q-card flat bordered class="stat-card pending-card">
          <q-card-section>
            <div class="text-caption text-grey-7">
              Pendientes
            </div>
            <div class="text-h5 text-weight-bold text-orange-9">
              {{ totalPendientes }}
            </div>
            <div class="text-caption text-grey-6">
              pagos por cobrar
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <q-card
      flat
      bordered
      class="filter-card q-mb-md"
    >
      <q-card-section class="row q-col-gutter-md items-center">
        <div class="col-12 col-md">
          <q-input
            v-model="filtroTexto"
            dense
            outlined
            debounce="200"
            placeholder="Buscar mototaxista, CI, sindicato, tipo o período"
          >
            <template #prepend>
              <q-icon name="search" color="green-8" />
            </template>
          </q-input>
        </div>

        <div
          v-if="!esSecretario"
          class="col-12 col-sm-4 col-md-3"
        >
          <q-select
            v-model="filtroSindicato"
            :options="opcionesSindicato"
            dense
            outlined
            emit-value
            map-options
            label="Sindicato"
          />
        </div>

        <div class="col-12 col-sm-4 col-md-2">
          <q-select
            v-model="filtroTipo"
            :options="opcionesTipoFiltro"
            dense
            outlined
            emit-value
            map-options
            label="Tipo"
          />
        </div>

        <div class="col-12 col-sm-4 col-md-2">
          <q-select
            v-model="filtroEstado"
            :options="opcionesEstadoFiltro"
            dense
            outlined
            emit-value
            map-options
            label="Estado"
          />
        </div>
      </q-card-section>
    </q-card>

    <q-card flat bordered class="table-card">
      <q-card-section class="row items-center justify-between">
        <div>
          <div class="text-subtitle1 text-weight-bold text-grey-9">
            Historial de movimientos
          </div>
          <div class="text-caption text-grey-6">
            {{ pagosFiltrados.length }} registros visibles
          </div>
        </div>

        <q-btn
          flat
          round
          icon="refresh"
          color="green-8"
          :loading="loading"
          @click="cargarTodo"
        >
          <q-tooltip>Actualizar</q-tooltip>
        </q-btn>
      </q-card-section>

      <q-separator />

      <q-table
        flat
        :rows="pagosFiltrados"
        :columns="columnas"
        row-key="id"
        :loading="loading"
        :rows-per-page-options="[5, 10, 20, 50]"
        no-data-label="No hay pagos sindicales registrados."
      >
        <template #body-cell-mototaxista="props">
          <q-td :props="props">
            <div class="text-weight-bold">
              {{ nombreMototaxista(props.row.mototaxista) }}
            </div>
            <div class="text-caption text-grey-6">
              CI {{ props.row.mototaxista?.persona?.ci || '—' }}
              · Chaleco {{ props.row.mototaxista?.nro_chaleco || '—' }}
            </div>
          </q-td>
        </template>

        <template #body-cell-tipo_pago="props">
          <q-td :props="props">
            <q-badge
              :color="colorTipo(props.row.tipo_pago)"
              class="q-pa-xs"
            >
              {{ props.row.tipo_pago }}
            </q-badge>

            <div
              v-if="props.row.periodo"
              class="text-caption text-grey-6 q-mt-xs"
            >
              {{ formatearPeriodo(props.row.periodo) }}
            </div>
          </q-td>
        </template>

        <template #body-cell-monto="props">
          <q-td :props="props" class="text-weight-bold text-green-9">
            {{ dinero(props.row.monto) }}
          </q-td>
        </template>

        <template #body-cell-estado_pago="props">
          <q-td :props="props">
            <q-badge
              :color="colorEstado(props.row.estado_pago)"
              class="q-pa-xs"
            >
              {{ props.row.estado_pago }}
            </q-badge>
          </q-td>
        </template>

        <template #body-cell-registrado="props">
          <q-td :props="props">
            {{
              props.row.registrado_por?.name
              || props.row.registrado_por?.email
              || 'Sistema'
            }}
          </q-td>
        </template>

        <template #body-cell-acciones="props">
          <q-td :props="props" class="text-center">
            <q-btn
              flat
              round
              dense
              icon="more_vert"
              color="grey-7"
            >
              <q-menu>
                <q-list style="min-width: 190px">
                  <q-item
                    v-if="props.row.estado_pago !== 'Anulado'"
                    clickable
                    v-close-popup
                    @click="abrirFormulario(props.row)"
                  >
                    <q-item-section avatar>
                      <q-icon name="edit" color="green-8" />
                    </q-item-section>
                    <q-item-section>Editar</q-item-section>
                  </q-item>

                  <q-item
                    v-if="props.row.estado_pago !== 'Anulado'"
                    clickable
                    v-close-popup
                    @click="confirmarAnular(props.row)"
                  >
                    <q-item-section avatar>
                      <q-icon name="block" color="negative" />
                    </q-item-section>
                    <q-item-section class="text-negative">
                      Anular registro
                    </q-item-section>
                  </q-item>

                  <q-item
                    v-if="props.row.estado_pago === 'Anulado'"
                    disable
                  >
                    <q-item-section avatar>
                      <q-icon name="lock" color="grey-6" />
                    </q-item-section>
                    <q-item-section>
                      Registro cerrado
                    </q-item-section>
                  </q-item>
                </q-list>
              </q-menu>
            </q-btn>
          </q-td>
        </template>
      </q-table>
    </q-card>

    <q-dialog
      v-model="dialogFormulario"
      persistent
    >
      <q-card class="dialog-card">
        <q-card-section
          class="bg-green-8 text-white row items-center"
        >
          <q-icon
            name="account_balance_wallet"
            size="28px"
            class="q-mr-sm"
          />

          <div>
            <div class="text-h6 text-weight-bold">
              {{
                editando
                  ? 'Editar pago sindical'
                  : 'Registrar pago sindical'
              }}
            </div>
            <div class="text-caption text-green-1">
              Afiliación, inscripción o aporte del mototaxista.
            </div>
          </div>

          <q-space />

          <q-btn
            flat
            round
            dense
            icon="close"
            :disable="guardando"
            @click="cerrarFormulario"
          />
        </q-card-section>

        <q-form
          ref="formRef"
          class="dialog-form column no-wrap"
          @submit.prevent="guardar"
        >
          <q-card-section class="q-pa-lg dialog-scroll">
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <q-select
                  v-model="form.id_mototaxista"
                  :options="mototaxistas"
                  option-value="id"
                  :option-label="labelMototaxista"
                  emit-value
                  map-options
                  use-input
                  input-debounce="0"
                  outlined
                  label="Mototaxista *"
                  :disable="editando"
                  :rules="[requerido]"
                  @filter="filtrarMototaxistas"
                >
                  <template #prepend>
                    <q-icon
                      name="two_wheeler"
                      color="green-8"
                    />
                  </template>
                </q-select>
              </div>

              <div
                v-if="mototaxistaSeleccionado"
                class="col-12"
              >
                <q-banner
                  rounded
                  class="bg-green-1 text-green-10"
                >
                  <template #avatar>
                    <q-icon name="groups" color="green-8" />
                  </template>

                  Sindicato:
                  <strong>
                    {{
                      mototaxistaSeleccionado.sindicato?.nombre
                      || 'Sin sindicato'
                    }}
                  </strong>
                  · Chaleco
                  <strong>
                    {{ mototaxistaSeleccionado.nro_chaleco || '—' }}
                  </strong>
                </q-banner>
              </div>

              <div class="col-12 col-sm-6">
                <q-select
                  v-model="form.tipo_pago"
                  :options="tiposPago"
                  outlined
                  label="Tipo de pago *"
                  :rules="[requerido]"
                />
              </div>

              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="form.monto"
                  outlined
                  type="number"
                  step="0.01"
                  min="0.01"
                  label="Monto (Bs.) *"
                  :rules="[
                    requerido,
                    valor => Number(valor) > 0 || 'El monto debe ser mayor a 0'
                  ]"
                >
                  <template #prepend>
                    <q-icon
                      name="payments"
                      color="green-8"
                    />
                  </template>
                </q-input>
              </div>

              <div class="col-12 col-sm-6">
                <q-input
                  v-model="form.fecha"
                  outlined
                  type="date"
                  label="Fecha *"
                  :rules="[requerido]"
                />
              </div>

              <div
                v-if="form.tipo_pago === 'Aporte'"
                class="col-12 col-sm-6"
              >
                <q-input
                  v-model="form.periodo"
                  outlined
                  type="month"
                  label="Período del aporte"
                  hint="Ejemplo: agosto de 2026"
                />
              </div>

              <div
                :class="
                  form.tipo_pago === 'Aporte'
                    ? 'col-12 col-sm-6'
                    : 'col-12 col-sm-6'
                "
              >
                <q-select
                  v-model="form.forma_pago"
                  :options="formasPago"
                  outlined
                  label="Forma de pago *"
                  :rules="[requerido]"
                />
              </div>

              <div class="col-12 col-sm-6">
                <q-select
                  v-model="form.estado_pago"
                  :options="estadosFormulario"
                  outlined
                  label="Estado *"
                  :rules="[requerido]"
                />
              </div>

              <div class="col-12">
                <q-input
                  v-model.trim="form.observacion"
                  outlined
                  type="textarea"
                  autogrow
                  maxlength="255"
                  counter
                  label="Observación"
                  placeholder="Ej.: pago de afiliación inicial, aporte mensual..."
                />
              </div>
            </div>
          </q-card-section>

          <q-card-actions
            align="right"
            class="q-pa-md bg-grey-1"
          >
            <q-btn
              flat
              label="Cancelar"
              color="grey-7"
              :disable="guardando"
              @click="cerrarFormulario"
            />

            <q-btn
              type="submit"
              color="green-8"
              icon="save"
              :label="
                editando
                  ? 'Guardar cambios'
                  : 'Registrar pago'
              "
              unelevated
              :loading="guardando"
            />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
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
const guardando = ref(false)
const pagos = ref([])
const mototaxistasBase = ref([])
const mototaxistas = ref([])
const sindicatos = ref([])

const dialogFormulario = ref(false)
const editando = ref(false)
const formRef = ref(null)

const filtroTexto = ref('')
const filtroSindicato = ref('todos')
const filtroTipo = ref('todos')
const filtroEstado = ref('todos')

const tiposPago = [
  'Afiliación',
  'Inscripción',
  'Aporte',
  'Otro'
]

const formasPago = [
  'Efectivo',
  'QR',
  'Transferencia',
  'Otro'
]

const estadosFormulario = [
  'Pagado',
  'Pendiente'
]

const formDefault = () => ({
  id: null,
  id_mototaxista: null,
  tipo_pago: 'Aporte',
  monto: null,
  fecha: fechaHoy(),
  periodo: periodoActual(),
  estado_pago: 'Pagado',
  forma_pago: 'Efectivo',
  observacion: ''
})

const form = ref(
  formDefault()
)

function leerUsuario() {
  try {
    return JSON.parse(
      localStorage.getItem(
        'motrix_user'
      ) || 'null'
    )
  } catch {
    return null
  }
}

const usuario = ref(
  leerUsuario()
)

const rol = computed(() =>
  String(
    usuario.value?.role || ''
  )
    .trim()
    .toLowerCase()
)

const esSecretario = computed(() =>
  rol.value === 'secretario'
)

const nombreSindicatoActual = computed(() => {
  return (
    usuario.value?.sindicato_nombre
    || sindicatos.value[0]?.nombre
    || 'tu sindicato'
  )
})

const mototaxistaSeleccionado = computed(() =>
  mototaxistasBase.value.find(
    m =>
      Number(m.id)
      === Number(
        form.value.id_mototaxista
      )
  ) || null
)

const opcionesSindicato = computed(() => [
  {
    label: 'Todos',
    value: 'todos'
  },
  ...sindicatos.value.map(
    s => ({
      label: s.nombre,
      value: Number(s.id)
    })
  )
])

const opcionesTipoFiltro = computed(() => [
  {
    label: 'Todos',
    value: 'todos'
  },
  ...tiposPago.map(
    tipo => ({
      label: tipo,
      value: tipo
    })
  )
])

const opcionesEstadoFiltro = computed(() => [
  {
    label: 'Todos',
    value: 'todos'
  },
  {
    label: 'Pagado',
    value: 'Pagado'
  },
  {
    label: 'Pendiente',
    value: 'Pendiente'
  },
  {
    label: 'Anulado',
    value: 'Anulado'
  }
])

const pagosFiltrados = computed(() => {
  let lista = [...pagos.value]

  const texto = normalizar(
    filtroTexto.value
  )

  if (texto) {
    lista = lista.filter(
      pago => {
        const contenido = [
          nombreMototaxista(
            pago.mototaxista
          ),
          pago.mototaxista?.persona?.ci,
          pago.mototaxista?.nro_chaleco,
          pago.sindicato?.nombre,
          pago.tipo_pago,
          pago.periodo,
          pago.estado_pago,
          pago.forma_pago
        ]
          .map(normalizar)
          .join(' ')

        return contenido.includes(texto)
      }
    )
  }

  if (
    filtroSindicato.value
    !== 'todos'
  ) {
    lista = lista.filter(
      pago =>
        Number(pago.id_sindicato)
        === Number(
          filtroSindicato.value
        )
    )
  }

  if (
    filtroTipo.value
    !== 'todos'
  ) {
    lista = lista.filter(
      pago =>
        pago.tipo_pago
        === filtroTipo.value
    )
  }

  if (
    filtroEstado.value
    !== 'todos'
  ) {
    lista = lista.filter(
      pago =>
        pago.estado_pago
        === filtroEstado.value
    )
  }

  return lista
})

const pagosPagados = computed(() =>
  pagos.value.filter(
    pago =>
      pago.estado_pago === 'Pagado'
  )
)

const totalRecaudado = computed(() =>
  pagosPagados.value.reduce(
    (total, pago) =>
      total + numero(pago.monto),
    0
  )
)

const totalAfiliaciones = computed(() =>
  pagosPagados.value
    .filter(
      pago => [
        'Afiliación',
        'Inscripción'
      ].includes(
        pago.tipo_pago
      )
    )
    .reduce(
      (total, pago) =>
        total + numero(pago.monto),
      0
    )
)

const totalAportes = computed(() =>
  pagosPagados.value
    .filter(
      pago =>
        pago.tipo_pago
        === 'Aporte'
    )
    .reduce(
      (total, pago) =>
        total + numero(pago.monto),
      0
    )
)

const totalPendientes = computed(() =>
  pagos.value.filter(
    pago =>
      pago.estado_pago
      === 'Pendiente'
  ).length
)

const columnas = [
  {
    name: 'fecha',
    label: 'Fecha',
    field: 'fecha',
    align: 'left',
    sortable: true
  },
  {
    name: 'mototaxista',
    label: 'Mototaxista',
    field: 'mototaxista',
    align: 'left'
  },
  {
    name: 'sindicato',
    label: 'Sindicato',
    field: row =>
      row.sindicato?.nombre || '—',
    align: 'left'
  },
  {
    name: 'tipo_pago',
    label: 'Tipo',
    field: 'tipo_pago',
    align: 'left'
  },
  {
    name: 'monto',
    label: 'Monto',
    field: 'monto',
    align: 'left',
    sortable: true
  },
  {
    name: 'forma_pago',
    label: 'Forma',
    field: 'forma_pago',
    align: 'left'
  },
  {
    name: 'estado_pago',
    label: 'Estado',
    field: 'estado_pago',
    align: 'left'
  },
  {
    name: 'registrado',
    label: 'Registrado por',
    field: 'registrado_por',
    align: 'left'
  },
  {
    name: 'acciones',
    label: '',
    field: 'acciones',
    align: 'center'
  }
]

const requerido = valor =>
  Boolean(
    String(
      valor ?? ''
    ).trim()
  )
  || 'Campo obligatorio'

function normalizar(valor) {
  return String(
    valor || ''
  )
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

function fechaHoy() {
  const fecha = new Date()
  const offset = fecha.getTimezoneOffset()
  const local = new Date(
    fecha.getTime()
    - offset * 60 * 1000
  )

  return local
    .toISOString()
    .slice(0, 10)
}

function periodoActual() {
  return fechaHoy().slice(0, 7)
}

function nombreMototaxista(m) {
  if (!m) {
    return 'Mototaxista'
  }

  return [
    m.persona?.nombre,
    m.persona?.apellidos
  ]
    .filter(Boolean)
    .join(' ')
    .trim()
    || `Mototaxista #${m.id}`
}

function labelMototaxista(m) {
  if (!m) return ''

  return (
    nombreMototaxista(m)
    + (
      m.persona?.ci
        ? ` · CI ${m.persona.ci}`
        : ''
    )
    + (
      m.sindicato?.nombre
        ? ` · ${m.sindicato.nombre}`
        : ''
    )
  )
}

function colorTipo(tipo) {
  switch (tipo) {
    case 'Afiliación':
      return 'blue-8'
    case 'Inscripción':
      return 'indigo-7'
    case 'Aporte':
      return 'purple-7'
    default:
      return 'grey-7'
  }
}

function colorEstado(estado) {
  switch (estado) {
    case 'Pagado':
      return 'positive'
    case 'Pendiente':
      return 'orange-8'
    case 'Anulado':
      return 'negative'
    default:
      return 'grey-7'
  }
}

function formatearPeriodo(periodo) {
  if (
    !/^\d{4}-\d{2}$/.test(
      String(periodo || '')
    )
  ) {
    return periodo || ''
  }

  const [anio, mes] =
    periodo.split('-')

  const fecha = new Date(
    Number(anio),
    Number(mes) - 1,
    1
  )

  const texto = new Intl.DateTimeFormat(
    'es-BO',
    {
      month: 'long',
      year: 'numeric'
    }
  ).format(fecha)

  return texto.charAt(0).toUpperCase()
    + texto.slice(1)
}

function mensajeError(error) {
  const data =
    error?.response?.data

  if (data?.errors) {
    const mensaje =
      Object.values(
        data.errors
      )
        .flat()
        .find(Boolean)

    if (mensaje) {
      return mensaje
    }
  }

  return (
    data?.mensaje
    || data?.message
    || 'No se pudo completar la operación.'
  )
}

async function cargarTodo() {
  loading.value = true

  try {
    const [
      resPagos,
      resMototaxistas,
      resSindicatos
    ] = await Promise.all([
      api.get('/pagos-sindicales'),
      api.get('/mototaxistas'),
      api.get('/sindicatos')
    ])

    pagos.value =
      Array.isArray(resPagos.data)
        ? resPagos.data
        : []

    mototaxistasBase.value =
      Array.isArray(
        resMototaxistas.data
      )
        ? resMototaxistas.data
        : []

    mototaxistas.value =
      [...mototaxistasBase.value]

    sindicatos.value =
      Array.isArray(
        resSindicatos.data
      )
        ? resSindicatos.data
        : []
  } catch (error) {
    console.error(
      'Error cargando pagos sindicales:',
      error
    )

    $q.notify({
      type: 'negative',
      position: 'top',
      multiLine: true,
      message: mensajeError(error)
    })
  } finally {
    loading.value = false
  }
}

function filtrarMototaxistas(
  valor,
  update
) {
  update(() => {
    const texto =
      normalizar(valor)

    if (!texto) {
      mototaxistas.value =
        [...mototaxistasBase.value]

      return
    }

    mototaxistas.value =
      mototaxistasBase.value.filter(
        m =>
          normalizar(
            labelMototaxista(m)
          ).includes(texto)
      )
  })
}

function abrirFormulario(
  pago = null
) {
  if (pago) {
    editando.value = true

    form.value = {
      id: pago.id,
      id_mototaxista:
        pago.id_mototaxista,
      tipo_pago:
        pago.tipo_pago,
      monto:
        numero(pago.monto),
      fecha:
        String(
          pago.fecha || ''
        ).slice(0, 10),
      periodo:
        pago.periodo || '',
      estado_pago:
        pago.estado_pago,
      forma_pago:
        pago.forma_pago,
      observacion:
        pago.observacion || ''
    }
  } else {
    editando.value = false
    form.value =
      formDefault()
  }

  mototaxistas.value =
    [...mototaxistasBase.value]

  dialogFormulario.value = true
}

function cerrarFormulario() {
  if (guardando.value) {
    return
  }

  dialogFormulario.value = false
  editando.value = false
  form.value =
    formDefault()
}

async function guardar() {
  const valido =
    await formRef.value?.validate()

  if (valido === false) {
    return
  }

  guardando.value = true
  const eraEdicion = editando.value

  try {
    const payload = {
      id_mototaxista:
        form.value.id_mototaxista,
      tipo_pago:
        form.value.tipo_pago,
      monto:
        Number(form.value.monto),
      fecha:
        form.value.fecha,
      periodo:
        form.value.tipo_pago
        === 'Aporte'
          ? form.value.periodo || null
          : null,
      estado_pago:
        form.value.estado_pago,
      forma_pago:
        form.value.forma_pago,
      observacion:
        form.value.observacion
        || null
    }

    if (editando.value) {
      await api.put(
        `/pagos-sindicales/${form.value.id}`,
        payload
      )
    } else {
      await api.post(
        '/pagos-sindicales',
        payload
      )
    }

    guardando.value = false
    cerrarFormulario()

    $q.notify({
      type: 'positive',
      position: 'top',
      icon: 'check_circle',
      message:
        eraEdicion
          ? 'Pago sindical actualizado.'
          : 'Pago sindical registrado.'
    })

    await cargarTodo()
  } catch (error) {
    console.error(
      'Error guardando pago sindical:',
      error
    )

    $q.notify({
      type: 'negative',
      position: 'top',
      multiLine: true,
      message: mensajeError(error)
    })
  } finally {
    guardando.value = false
  }
}

function confirmarAnular(pago) {
  $q.dialog({
    title: 'Anular pago sindical',
    message:
      `¿Deseas anular el registro de ${nombreMototaxista(pago.mototaxista)} por ${dinero(pago.monto)}?`,
    prompt: {
      model: '',
      type: 'text',
      label: 'Motivo (opcional)'
    },
    cancel: true,
    persistent: true
  }).onOk(
    async motivo => {
      try {
        await api.post(
          `/pagos-sindicales/${pago.id}/anular`,
          {
            motivo:
              motivo || null
          }
        )

        $q.notify({
          type: 'positive',
          position: 'top',
          message:
            'Pago sindical anulado.'
        })

        await cargarTodo()
      } catch (error) {
        $q.notify({
          type: 'negative',
          position: 'top',
          multiLine: true,
          message:
            mensajeError(error)
        })
      }
    }
  )
}

onMounted(
  cargarTodo
)
</script>

<style scoped>
.pagos-sindicales-page {
  min-height: 100%;
  background: transparent;
}

.stat-card,
.filter-card,
.table-card {
  border-color: #d8e7d5;
  border-radius: 14px;
}

.stat-card {
  border-left: 4px solid #2e7d32;
}

.pending-card {
  border-left-color: #ef6c00;
}

.dialog-card {
  width: 680px;
  max-width: 94vw;
  max-height: 90vh;
  border-radius: 14px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.dialog-form {
  flex: 1 1 auto;
  min-height: 0;
  overflow: hidden;
}

.dialog-scroll {
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
  overscroll-behavior: contain;
}

@media (max-width: 600px) {
  .dialog-card {
    width: 96vw;
    max-height: 92vh;
  }
}
</style>
