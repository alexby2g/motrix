<template>
  <q-page class="q-pa-md bg-grey-1">
    <div class="row items-center q-col-gutter-md q-mb-lg">
      <div class="col-12 col-md">
        <div class="text-h5 text-weight-bold text-green-9">
          Cobranza de suscripciones MOTRIX
        </div>
        <div class="text-body2 text-grey-7">
          Registro controlado de cuotas mensuales, renovaciones y pagos directos autorizados.
        </div>
      </div>

      <div class="col-12 col-md-auto">
        <q-btn
          outline
          color="green-8"
          icon="workspace_premium"
          label="Panel de suscripciones"
          no-caps
          to="/suscripciones-motrix"
        />
      </div>
    </div>

    <q-banner
      rounded
      class="bg-blue-1 text-blue-10 q-mb-lg"
    >
      <template #avatar>
        <q-icon name="info" color="blue-8" />
      </template>
      Los pagos sindicales internos permanecen separados. Esta pantalla registra
      únicamente la cuota comercial por uso de MOTRIX.
    </q-banner>

    <q-card flat bordered class="q-mb-lg">
      <q-card-section>
        <div class="row q-col-gutter-md items-end">
          <div class="col-12 col-sm-6 col-md-3">
            <q-input
              v-model="periodo"
              type="month"
              outlined
              dense
              label="Periodo"
              @update:model-value="reiniciar"
            />
          </div>

          <div class="col-12 col-sm-6 col-md-3">
            <q-select
              v-model="estado"
              :options="estados"
              outlined
              dense
              label="Estado"
              @update:model-value="reiniciar"
            />
          </div>

          <div
            v-if="esAdminGeneral"
            class="col-12 col-md-4"
          >
            <q-select
              v-model="sindicatoSeleccionado"
              :options="opcionesSindicato"
              emit-value
              map-options
              clearable
              outlined
              dense
              label="Sindicato"
              :loading="cargandoSindicatos"
              @update:model-value="reiniciar"
            />
          </div>

          <div class="col-12 col-md">
            <q-btn
              color="green-8"
              icon="refresh"
              label="Actualizar"
              no-caps
              unelevated
              class="full-width"
              :loading="loading"
              @click="cargarPagos"
            />
          </div>
        </div>
      </q-card-section>
    </q-card>

    <q-card flat bordered>
      <q-table
        flat
        row-key="id"
        :rows="pagos"
        :columns="columnas"
        :loading="loading"
        v-model:pagination="paginacion"
        no-data-label="No hay cuotas para los filtros seleccionados."
        @request="solicitarPagina"
      >
        <template #body-cell-conductor="props">
          <q-td :props="props">
            <div class="text-weight-medium">
              {{ nombreConductor(props.row) }}
            </div>
            <div class="text-caption text-grey-7">
              Chaleco {{ props.row.mototaxista?.nro_chaleco || '—' }}
              · {{ props.row.sindicato?.nombre || '—' }}
            </div>
          </q-td>
        </template>

        <template #body-cell-monto="props">
          <q-td :props="props">
            <div class="text-weight-bold">
              {{ dinero(props.row.monto_esperado) }}
            </div>
            <div
              v-if="numero(props.row.monto_pagado) > 0"
              class="text-caption text-green-8"
            >
              Pagado {{ dinero(props.row.monto_pagado) }}
            </div>
          </q-td>
        </template>

        <template #body-cell-estado="props">
          <q-td :props="props">
            <q-chip
              dense
              :color="colorEstado(props.row.estado)"
              text-color="white"
              :label="props.row.estado"
            />
          </q-td>
        </template>

        <template #body-cell-canal="props">
          <q-td :props="props">
            {{ etiquetaCanal(props.row.canal_cobro) }}
          </q-td>
        </template>

        <template #body-cell-fecha="props">
          <q-td :props="props">
            <div>{{ fecha(props.row.fecha_vencimiento) }}</div>
            <div
              v-if="props.row.fecha_pago"
              class="text-caption text-green-8"
            >
              Pagó {{ fechaHora(props.row.fecha_pago) }}
            </div>
          </q-td>
        </template>

        <template #body-cell-acciones="props">
          <q-td :props="props">
            <q-btn
              v-if="puedeCobrar(props.row)"
              color="green-8"
              icon="point_of_sale"
              label="Registrar pago"
              no-caps
              dense
              unelevated
              @click="abrirPago(props.row)"
            />

            <q-chip
              v-else
              dense
              color="grey-3"
              text-color="grey-8"
              :label="props.row.estado"
            />
          </q-td>
        </template>
      </q-table>
    </q-card>

    <q-dialog
      v-model="dialogPago"
      persistent
    >
      <q-card class="dialog-card">
        <q-form class="dialog-form" @submit.prevent="registrarPago">
          <q-card-section class="dialog-header bg-green-8 text-white row items-center">
            <div>
              <div class="text-h6 text-weight-bold">
                Registrar pago MOTRIX
              </div>
              <div class="text-caption text-green-1">
                {{ pagoSeleccionado ? nombreConductor(pagoSeleccionado) : '' }}
              </div>
            </div>
            <q-space />
            <q-btn
              flat
              round
              dense
              icon="close"
              :disable="guardando"
              v-close-popup
            />
          </q-card-section>

          <q-card-section class="dialog-body scroll">
            <q-banner
              rounded
              class="bg-green-1 text-green-10 q-mb-md"
            >
              Cuota del periodo {{ periodoMMYYYY(pagoSeleccionado?.periodo) }}:
              <strong>{{ dinero(pagoSeleccionado?.monto_esperado) }}</strong>
            </q-banner>

            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="form.monto_pagado"
                  type="number"
                  step="0.01"
                  outlined
                  label="Monto pagado *"
                  prefix="Bs."
                  :rules="[requeridoMonto]"
                />
              </div>

              <div class="col-12 col-sm-6">
                <q-select
                  v-model="form.forma_pago"
                  :options="formasPago"
                  outlined
                  label="Forma de pago *"
                  :rules="[requerido]"
                />
              </div>

              <div
                v-if="esAdminGeneral"
                class="col-12"
              >
                <q-select
                  v-model="form.canal_cobro"
                  :options="canalesAdmin"
                  emit-value
                  map-options
                  outlined
                  label="Canal de cobro *"
                  :rules="[requerido]"
                />
              </div>

              <div
                v-else
                class="col-12"
              >
                <q-field
                  outlined
                  label="Canal de cobro"
                  stack-label
                >
                  <template #control>
                    <div class="self-center full-width no-outline">
                      Cobrado por el sindicato
                    </div>
                  </template>
                </q-field>
              </div>

              <div class="col-12">
                <q-input
                  v-model="form.referencia_pago"
                  outlined
                  label="Referencia / Nro. de operación"
                  maxlength="150"
                />
              </div>

              <div class="col-12">
                <q-input
                  v-model="form.comprobante_url"
                  outlined
                  label="URL de comprobante (opcional)"
                  maxlength="2000"
                />
              </div>

              <div class="col-12">
                <q-input
                  v-model="form.observacion"
                  type="textarea"
                  outlined
                  autogrow
                  label="Observación"
                  maxlength="500"
                />
              </div>
            </div>
          </q-card-section>

          <q-separator />

          <q-card-actions align="right" class="dialog-actions q-pa-md">
            <q-btn
              flat
              label="Cancelar"
              no-caps
              :disable="guardando"
              v-close-popup
            />
            <q-btn
              color="green-8"
              icon="save"
              label="Registrar pago"
              no-caps
              unelevated
              type="submit"
              :loading="guardando"
            />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { fechaHoraDDMMYYYY, motrixDateV57, periodoActualBolivia, periodoMMYYYY } from 'src/utils/motrixDate.js'

import {
  computed,
  onMounted,
  ref
} from 'vue'
import {
  useQuasar
} from 'quasar'

import { api } from 'src/boot/axios.js'
const $q = useQuasar()

const loading = ref(false)
const guardando = ref(false)
const cargandoSindicatos = ref(false)

const pagos = ref([])
const sindicatos = ref([])
const sindicatoSeleccionado = ref(null)
const periodo = ref(periodoActual())
const estado = ref('Todos')

const dialogPago = ref(false)
const pagoSeleccionado = ref(null)
const form = ref(formVacio())

const paginacion = ref({
  page: 1,
  rowsPerPage: 20,
  rowsNumber: 0
})

const estados = [
  'Todos',
  'Pendiente',
  'Vencido',
  'Pagado',
  'Exonerado',
  'Anulado'
]

const formasPago = [
  'Efectivo',
  'QR',
  'Transferencia',
  'Deposito',
  'Otro'
]

const canalesAdmin = [
  {
    label: 'Cobrado por sindicato',
    value: 'sindicato'
  },
  {
    label: 'Pago directo a MOTRIX',
    value: 'motrix_directo'
  }
]

const columnas = [
  {
    name: 'conductor',
    label: 'Conductor / sindicato',
    field: 'id',
    align: 'left'
  },
  {
    name: 'periodo',
    label: 'Periodo',
    field: 'periodo',
    align: 'left'
  },
  {
    name: 'monto',
    label: 'Monto',
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
    name: 'canal',
    label: 'Canal',
    field: 'canal_cobro',
    align: 'left'
  },
  {
    name: 'fecha',
    label: 'Vencimiento / pago',
    field: 'fecha_vencimiento',
    format: value => motrixDateV57(value),
    align: 'left'
  },
  {
    name: 'acciones',
    label: '',
    field: 'id',
    align: 'right'
  }
]

function leerUsuario() {
  try {
    return JSON.parse(
      localStorage.getItem('motrix_user')
      || 'null'
    )
  } catch {
    return null
  }
}

const usuario = ref(leerUsuario())

const rol = computed(() =>
  String(usuario.value?.role || '')
    .trim()
    .toLowerCase()
)

const esAdminGeneral = computed(() =>
  rol.value === 'admin_general'
)

const opcionesSindicato = computed(() =>
  sindicatos.value.map(item => ({
    label: item.nombre,
    value: Number(item.id)
  }))
)

function periodoActual() {
  return periodoActualBolivia()
}

function formVacio() {
  return {
    monto_pagado: null,
    forma_pago: 'Efectivo',
    canal_cobro: 'sindicato',
    referencia_pago: '',
    comprobante_url: '',
    observacion: ''
  }
}

function parametros() {
  const params = {
    periodo: periodo.value,
    page: paginacion.value.page,
    per_page: paginacion.value.rowsPerPage
  }

  if (
    estado.value
    && estado.value !== 'Todos'
  ) {
    params.estado = estado.value
  }

  if (
    esAdminGeneral.value
    && sindicatoSeleccionado.value
  ) {
    params.id_sindicato =
      Number(sindicatoSeleccionado.value)
  }

  return params
}

async function cargarPagos() {
  loading.value = true

  try {
    const { data } = await api.get(
      '/pagos-suscripcion-motrix',
      {
        params: parametros()
      }
    )

    pagos.value =
      data?.data || []

    paginacion.value = {
      ...paginacion.value,
      page: Number(
        data?.meta?.current_page || 1
      ),
      rowsPerPage: Number(
        data?.meta?.per_page
        || paginacion.value.rowsPerPage
      ),
      rowsNumber: Number(
        data?.meta?.total || 0
      )
    }
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

async function cargarSindicatos() {
  if (!esAdminGeneral.value) return

  cargandoSindicatos.value = true

  try {
    const { data } =
      await api.get('/sindicatos')

    sindicatos.value =
      Array.isArray(data)
        ? data
        : data?.data || []
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      message: mensajeError(error)
    })
  } finally {
    cargandoSindicatos.value = false
  }
}

function abrirPago(pago) {
  pagoSeleccionado.value = pago
  form.value = {
    ...formVacio(),
    monto_pagado:
      numero(pago.monto_esperado),
    canal_cobro:
      esAdminGeneral.value
        ? String(
          pago.canal_cobro
          || 'sindicato'
        )
        : 'sindicato'
  }
  dialogPago.value = true
}

async function registrarPago() {
  if (!pagoSeleccionado.value) return

  guardando.value = true

  try {
    const payload = {
      monto_pagado:
        numero(form.value.monto_pagado),
      forma_pago:
        form.value.forma_pago,
      canal_cobro:
        esAdminGeneral.value
          ? form.value.canal_cobro
          : 'sindicato',
      referencia_pago:
        form.value.referencia_pago || null,
      comprobante_url:
        form.value.comprobante_url || null,
      observacion:
        form.value.observacion || null
    }

    const respuesta = await api.post(
      `/pagos-suscripcion-motrix/${pagoSeleccionado.value.id}/registrar`,
      payload
    )

    dialogPago.value = false

    $q.notify({
      type: 'positive',
      position: 'top',
      icon: 'check_circle',
      message:
        respuesta.data?.mensaje
        || 'Pago registrado correctamente.'
    })

    await cargarPagos()
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      message: mensajeError(error)
    })
  } finally {
    guardando.value = false
  }
}

async function reiniciar() {
  paginacion.value.page = 1
  await cargarPagos()
}

async function solicitarPagina(props) {
  paginacion.value = {
    ...paginacion.value,
    page: props.pagination.page,
    rowsPerPage: props.pagination.rowsPerPage
  }

  await cargarPagos()
}

function puedeCobrar(row) {
  return [
    'pendiente',
    'vencido'
  ].includes(
    String(row?.estado || '')
      .trim()
      .toLowerCase()
  )
}

function nombreConductor(row) {
  const persona =
    row?.mototaxista?.persona

  return [
    persona?.nombre,
    persona?.apellidos
  ]
    .filter(Boolean)
    .join(' ')
    .trim()
    || 'Mototaxista'
}

function etiquetaCanal(canal) {
  return (
    String(canal || '')
      .trim()
      .toLowerCase()
    === 'motrix_directo'
  )
    ? 'MOTRIX directo'
    : 'Sindicato'
}

function colorEstado(valor) {
  const estadoPago =
    String(valor || '')
      .trim()
      .toLowerCase()

  if (estadoPago === 'pagado') return 'green-8'
  if (estadoPago === 'pendiente') return 'orange-8'
  if (estadoPago === 'vencido') return 'negative'
  if (estadoPago === 'exonerado') return 'blue-8'
  if (estadoPago === 'anulado') return 'grey-8'

  return 'blue-grey-7'
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
  return fechaHoraDDMMYYYY(valor)
}

function requerido(valor) {
  return Boolean(valor)
    || 'Campo obligatorio.'
}

function requeridoMonto(valor) {
  return numero(valor) > 0
    || 'Ingresa un monto válido.'
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
    || 'No se pudo completar la operación.'
  )
}

onMounted(async () => {
  await cargarSindicatos()
  await cargarPagos()
})
</script>

<style scoped>
.dialog-card {
  width: min(720px, 94vw);
  max-width: 720px;
  max-height: calc(100vh - 32px);
  overflow: hidden;
}

.dialog-form {
  display: flex;
  flex-direction: column;
  max-height: calc(100vh - 32px);
  min-height: 0;
}

.dialog-header,
.dialog-actions {
  flex: 0 0 auto;
}

.dialog-body {
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
  overscroll-behavior: contain;
}

@media (max-width: 599px) {
  .dialog-card,
  .dialog-form {
    max-height: calc(100vh - 16px);
  }
}
</style>
