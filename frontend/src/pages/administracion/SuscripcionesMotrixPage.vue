<template>
  <q-page class="q-pa-md bg-grey-1">
    <div class="row items-center q-col-gutter-md q-mb-lg">
      <div class="col-12 col-md">
        <div class="text-h5 text-weight-bold text-green-9">
          Suscripciones MOTRIX
        </div>
        <div class="text-body2 text-grey-7">
          Control comercial de planes, vigencia, cobranza y liquidación de conductores.
        </div>
      </div>

      <div class="col-12 col-md-auto row q-gutter-sm">
        <q-btn
          outline
          color="green-8"
          icon="assessment"
          label="Reportes"
          no-caps
          to="/reportes-suscripciones-motrix"
        />
        <q-btn
          outline
          color="green-8"
          icon="point_of_sale"
          label="Cobranza"
          no-caps
          to="/cobranza-motrix"
        />
        <q-btn
          color="green-8"
          icon="account_balance"
          label="Liquidaciones"
          no-caps
          unelevated
          to="/liquidaciones-motrix"
        />
      </div>
    </div>

    <q-card flat bordered class="q-mb-lg">
      <q-card-section>
        <div class="row q-col-gutter-md items-end">
          <div class="col-12 col-sm-6 col-md-3">
            <q-input
              v-model="periodo"
              type="month"
              outlined
              dense
              label="Periodo comercial"
              @update:model-value="actualizarPanel"
            >
              <template #prepend>
                <q-icon name="calendar_month" color="green-8" />
              </template>
            </q-input>
          </div>

          <div
            v-if="esAdminGeneral"
            class="col-12 col-sm-6 col-md-5"
          >
            <q-select
              v-model="sindicatoSeleccionado"
              :options="opcionesSindicato"
              emit-value
              map-options
              clearable
              outlined
              dense
              label="Sindicato (todos si está vacío)"
              :loading="cargandoSindicatos"
              @update:model-value="cambioSindicato"
            >
              <template #prepend>
                <q-icon name="business" color="green-8" />
              </template>
            </q-select>
          </div>

          <div
            v-else
            class="col-12 col-sm-6 col-md-5"
          >
            <q-field
              outlined
              dense
              label="Sindicato"
              stack-label
            >
              <template #control>
                <div class="self-center full-width no-outline">
                  {{ usuario?.sindicato_nombre || 'Mi sindicato' }}
                </div>
              </template>
              <template #prepend>
                <q-icon name="business" color="green-8" />
              </template>
            </q-field>
          </div>

          <div class="col-12 col-md">
            <q-btn
              outline
              color="green-8"
              icon="refresh"
              label="Actualizar"
              no-caps
              class="full-width"
              :loading="loadingPanel || loadingLista"
              @click="actualizarTodo"
            />
          </div>
        </div>
      </q-card-section>
    </q-card>

    <div class="row q-col-gutter-md q-mb-lg">
      <div
        v-for="tarjeta in tarjetasResumen"
        :key="tarjeta.etiqueta"
        class="col-12 col-sm-6 col-lg-3"
      >
        <q-card flat bordered class="stat-card">
          <q-card-section class="row items-center no-wrap">
            <q-avatar
              :color="tarjeta.fondo"
              :text-color="tarjeta.color"
              :icon="tarjeta.icono"
            />
            <div class="q-ml-md min-width-zero">
              <div class="text-caption text-grey-7">
                {{ tarjeta.etiqueta }}
              </div>
              <div class="text-h6 text-weight-bold ellipsis">
                {{ tarjeta.valor }}
              </div>
              <div class="text-caption text-grey-6 ellipsis">
                {{ tarjeta.detalle }}
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <q-card
      v-if="panel.planes?.length"
      flat
      bordered
      class="q-mb-lg"
    >
      <q-card-section>
        <div class="text-subtitle1 text-weight-bold">
          Planes comerciales activos
        </div>
        <div class="text-caption text-grey-7 q-mb-md">
          El precio se obtiene de la configuración del sistema y no está fijado en el frontend.
        </div>

        <div class="row q-col-gutter-md">
          <div
            v-for="plan in panel.planes"
            :key="plan.id"
            class="col-12 col-md-4"
          >
            <q-card flat bordered class="plan-card">
              <q-card-section>
                <div class="row items-start no-wrap">
                  <q-avatar
                    color="green-1"
                    text-color="green-9"
                    icon="workspace_premium"
                  />
                  <div class="q-ml-md">
                    <div class="text-subtitle1 text-weight-bold">
                      {{ plan.nombre }}
                    </div>
                    <div class="text-h5 text-green-9 text-weight-bold">
                      {{ dinero(plan.monto) }}
                    </div>
                    <div class="text-caption text-grey-7">
                      {{ plan.duracion_meses }} mes(es) ·
                      {{ plan.dias_gracia }} días de gracia ·
                      aviso {{ plan.aviso_dias_antes }} días antes
                    </div>
                  </div>
                </div>
              </q-card-section>
            </q-card>
          </div>
        </div>
      </q-card-section>
    </q-card>

    <q-card flat bordered class="q-mb-lg">
      <q-card-section>
        <div class="row q-col-gutter-md items-end">
          <div class="col-12 col-md-5">
            <q-input
              v-model="busqueda"
              outlined
              dense
              clearable
              label="Buscar conductor, CI, chaleco o sindicato"
              @keyup.enter="reiniciarLista"
            >
              <template #prepend>
                <q-icon name="search" color="green-8" />
              </template>
            </q-input>
          </div>

          <div class="col-12 col-sm-6 col-md-3">
            <q-select
              v-model="estadoFiltro"
              :options="estados"
              outlined
              dense
              label="Estado"
              @update:model-value="reiniciarLista"
            />
          </div>

          <div class="col-12 col-sm-6 col-md">
            <q-btn
              color="green-8"
              icon="search"
              label="Buscar"
              no-caps
              unelevated
              class="full-width"
              :loading="loadingLista"
              @click="reiniciarLista"
            />
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <q-table
        flat
        row-key="id"
        :rows="suscripciones"
        :columns="columnas"
        :loading="loadingLista"
        v-model:pagination="paginacion"
        binary-state-sort
        no-data-label="No se encontraron suscripciones con estos filtros."
        @request="solicitarPagina"
      >
        <template #body-cell-conductor="props">
          <q-td :props="props">
            <div class="text-weight-medium">
              {{ nombreConductor(props.row) }}
            </div>
            <div class="text-caption text-grey-7">
              CI {{ props.row.mototaxista?.persona?.ci || '—' }}
              · Chaleco {{ props.row.mototaxista?.nro_chaleco || '—' }}
            </div>
          </q-td>
        </template>

        <template #body-cell-plan="props">
          <q-td :props="props">
            <div>{{ props.row.plan?.nombre || 'Sin plan' }}</div>
            <div class="text-caption text-grey-7">
              {{ dinero(props.row.plan?.monto) }}
            </div>
          </q-td>
        </template>

        <template #body-cell-estado="props">
          <q-td :props="props">
            <q-chip
              dense
              :color="colorEstado(props.row.estado_calculado || props.row.estado)"
              text-color="white"
              :label="props.row.estado_calculado || props.row.estado"
            />
          </q-td>
        </template>

        <template #body-cell-vencimiento="props">
          <q-td :props="props">
            <div>{{ fecha(props.row.fecha_vencimiento) }}</div>
            <div
              :class="[
                'text-caption',
                Number(props.row.dias_restantes) < 0
                  ? 'text-negative'
                  : 'text-grey-7'
              ]"
            >
              {{ textoDias(props.row.dias_restantes) }}
            </div>
          </q-td>
        </template>

        <template #body-cell-sindicato="props">
          <q-td :props="props">
            {{ props.row.sindicato?.nombre || '—' }}
          </q-td>
        </template>

        <template #body-cell-acciones="props">
          <q-td :props="props">
            <q-btn
              flat
              round
              dense
              color="blue-8"
              icon="visibility"
              @click="abrirDetalle(props.row)"
            >
              <q-tooltip>Ver detalle e historial</q-tooltip>
            </q-btn>

            <q-btn
              flat
              round
              dense
              color="green-8"
              icon="event_repeat"
              @click="prepararRenovacion(props.row)"
            >
              <q-tooltip>Preparar renovación</q-tooltip>
            </q-btn>
          </q-td>
        </template>
      </q-table>
    </q-card>

    <q-card
      v-if="esAdminGeneral && panel.sindicatos?.length"
      flat
      bordered
    >
      <q-card-section>
        <div class="text-subtitle1 text-weight-bold">
          Comparativo por sindicato
        </div>
        <div class="text-caption text-grey-7">
          Recaudación y estado comercial del periodo {{ periodo }}.
        </div>
      </q-card-section>

      <q-separator />

      <q-table
        flat
        row-key="id_sindicato"
        :rows="panel.sindicatos || []"
        :columns="columnasSindicatos"
        :pagination="{ rowsPerPage: 10 }"
      >
        <template #body-cell-recaudado="props">
          <q-td :props="props">
            {{ dinero(props.row.cobranza?.recaudado_sindicato) }}
          </q-td>
        </template>

        <template #body-cell-pendiente="props">
          <q-td :props="props">
            {{ dinero(props.row.cobranza?.pendiente_cobro) }}
          </q-td>
        </template>

        <template #body-cell-liquidar="props">
          <q-td :props="props">
            {{ dinero(props.row.liquidaciones?.pendiente_liquidar) }}
          </q-td>
        </template>
      </q-table>
    </q-card>

    <q-dialog v-model="dialogDetalle">
      <q-card class="dialog-card">
        <q-card-section class="bg-green-8 text-white row items-center">
          <div>
            <div class="text-h6 text-weight-bold">
              Detalle de suscripción
            </div>
            <div class="text-caption text-green-1">
              {{ detalle ? nombreConductor(detalle) : '' }}
            </div>
          </div>
          <q-space />
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>

        <q-card-section v-if="detalle">
          <div class="row q-col-gutter-md q-mb-md">
            <div class="col-6 col-sm-3">
              <div class="text-caption text-grey-7">Plan</div>
              <div class="text-weight-bold">
                {{ detalle.plan?.nombre || '—' }}
              </div>
            </div>
            <div class="col-6 col-sm-3">
              <div class="text-caption text-grey-7">Cuota</div>
              <div class="text-weight-bold">
                {{ dinero(detalle.plan?.monto) }}
              </div>
            </div>
            <div class="col-6 col-sm-3">
              <div class="text-caption text-grey-7">Estado</div>
              <div class="text-weight-bold">
                {{ detalle.estado_calculado || detalle.estado }}
              </div>
            </div>
            <div class="col-6 col-sm-3">
              <div class="text-caption text-grey-7">Vencimiento</div>
              <div class="text-weight-bold">
                {{ fecha(detalle.fecha_vencimiento) }}
              </div>
            </div>
          </div>

          <q-table
            flat
            bordered
            dense
            row-key="id"
            :rows="detalle.pagos || []"
            :columns="columnasPagos"
            :pagination="{ rowsPerPage: 8 }"
            no-data-label="No hay pagos o cuotas generadas."
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
          </q-table>
        </q-card-section>

        <q-inner-loading :showing="cargandoDetalle">
          <q-spinner-dots size="42px" color="green-8" />
        </q-inner-loading>
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

const loadingPanel = ref(false)
const loadingLista = ref(false)
const cargandoSindicatos = ref(false)
const cargandoDetalle = ref(false)

const periodo = ref(periodoActual())
const sindicatoSeleccionado = ref(null)
const sindicatos = ref([])
const panel = ref(panelVacio())

const busqueda = ref('')
const estadoFiltro = ref('Todos')
const suscripciones = ref([])
const detalle = ref(null)
const dialogDetalle = ref(false)

const paginacion = ref({
  page: 1,
  rowsPerPage: 12,
  rowsNumber: 0
})

const estados = [
  'Todos',
  'Activa',
  'Por vencer',
  'Periodo de gracia',
  'Vencida',
  'Suspendida'
]

const columnas = [
  {
    name: 'conductor',
    label: 'Conductor',
    field: 'id',
    align: 'left'
  },
  {
    name: 'sindicato',
    label: 'Sindicato',
    field: row => row.sindicato?.nombre,
    align: 'left'
  },
  {
    name: 'plan',
    label: 'Plan / cuota',
    field: row => row.plan?.nombre,
    align: 'left'
  },
  {
    name: 'estado',
    label: 'Estado',
    field: 'estado_calculado',
    align: 'center'
  },
  {
    name: 'vencimiento',
    label: 'Vencimiento',
    field: 'fecha_vencimiento',
    align: 'left'
  },
  {
    name: 'acciones',
    label: '',
    field: 'id',
    align: 'right'
  }
]

const columnasSindicatos = [
  {
    name: 'nombre',
    label: 'Sindicato',
    field: 'nombre',
    align: 'left'
  },
  {
    name: 'suscritos',
    label: 'Suscritos',
    field: row => row.suscripciones?.total || 0,
    align: 'center'
  },
  {
    name: 'pagadas',
    label: 'Pagadas',
    field: row => row.cobranza?.pagadas || 0,
    align: 'center'
  },
  {
    name: 'vencidas',
    label: 'Vencidas',
    field: row => row.suscripciones?.vencidas || 0,
    align: 'center'
  },
  {
    name: 'recaudado',
    label: 'Recaudado',
    field: row => row.cobranza?.recaudado_sindicato || 0,
    align: 'right'
  },
  {
    name: 'pendiente',
    label: 'Pendiente cobro',
    field: row => row.cobranza?.pendiente_cobro || 0,
    align: 'right'
  },
  {
    name: 'liquidar',
    label: 'Pendiente liquidar',
    field: row => row.liquidaciones?.pendiente_liquidar || 0,
    align: 'right'
  }
]

const columnasPagos = [
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
    name: 'fecha_pago',
    label: 'Pago',
    field: row => fecha(row.fecha_pago),
    align: 'left'
  },
  {
    name: 'forma_pago',
    label: 'Medio',
    field: 'forma_pago',
    align: 'left'
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

const tarjetasResumen = computed(() => {
  const resumen = panel.value.resumen || panelVacio().resumen

  return [
    {
      etiqueta: 'Suscripciones',
      valor: resumen.suscripciones?.total || 0,
      detalle:
        `${resumen.suscripciones?.activas || 0} activas · `
        + `${resumen.suscripciones?.por_vencer || 0} por vencer`,
      icono: 'workspace_premium',
      fondo: 'green-1',
      color: 'green-9'
    },
    {
      etiqueta: 'Pagadas del periodo',
      valor: resumen.cobranza?.pagadas || 0,
      detalle:
        `${resumen.cobranza?.pendientes || 0} pendientes · `
        + `${resumen.cobranza?.vencidas || 0} vencidas`,
      icono: 'task_alt',
      fondo: 'blue-1',
      color: 'blue-9'
    },
    {
      etiqueta: 'Recaudado sindicato',
      valor: dinero(resumen.cobranza?.recaudado_sindicato),
      detalle:
        `Directo MOTRIX: ${dinero(resumen.cobranza?.motrix_directo)}`,
      icono: 'payments',
      fondo: 'teal-1',
      color: 'teal-9'
    },
    {
      etiqueta: 'Pendiente de cobro',
      valor: dinero(resumen.cobranza?.pendiente_cobro),
      detalle:
        `${resumen.suscripciones?.vencidas || 0} suscripciones vencidas`,
      icono: 'pending_actions',
      fondo: 'orange-1',
      color: 'orange-9'
    },
    {
      etiqueta: 'Liquidado validado',
      valor: dinero(resumen.liquidaciones?.liquidado_validado),
      detalle:
        `En revisión: ${dinero(resumen.liquidaciones?.pendiente_validacion)}`,
      icono: 'verified',
      fondo: 'purple-1',
      color: 'purple-9'
    },
    {
      etiqueta: 'Pendiente de liquidar',
      valor: dinero(resumen.liquidaciones?.pendiente_liquidar),
      detalle:
        `Disponible: ${dinero(resumen.liquidaciones?.disponible_transferir)}`,
      icono: 'account_balance',
      fondo: 'red-1',
      color: 'red-9'
    },
    {
      etiqueta: 'Periodo de gracia',
      valor: resumen.suscripciones?.gracia || 0,
      detalle: 'Acceso temporal con advertencia',
      icono: 'schedule',
      fondo: 'amber-1',
      color: 'amber-10'
    },
    {
      etiqueta: 'Suspendidas',
      valor: resumen.suscripciones?.suspendidas || 0,
      detalle: 'Suspensiones administrativas',
      icono: 'block',
      fondo: 'grey-3',
      color: 'grey-9'
    }
  ]
})

function periodoActual() {
  const hoy = new Date()
  const mes = String(
    hoy.getMonth() + 1
  ).padStart(2, '0')

  return `${hoy.getFullYear()}-${mes}`
}

function panelVacio() {
  return {
    periodo: periodoActual(),
    id_sindicato: null,
    resumen: {
      suscripciones: {
        total: 0,
        activas: 0,
        por_vencer: 0,
        gracia: 0,
        vencidas: 0,
        suspendidas: 0
      },
      cobranza: {
        pagadas: 0,
        pendientes: 0,
        vencidas: 0,
        recaudado_total: '0.00',
        recaudado_sindicato: '0.00',
        motrix_directo: '0.00',
        pendiente_cobro: '0.00'
      },
      liquidaciones: {
        liquidado_validado: '0.00',
        pendiente_validacion: '0.00',
        pendiente_liquidar: '0.00',
        disponible_transferir: '0.00'
      }
    },
    sindicatos: [],
    planes: []
  }
}

function parametrosPanel() {
  const params = {
    periodo: periodo.value
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

function parametrosLista() {
  const params = {
    page: paginacion.value.page,
    per_page: paginacion.value.rowsPerPage
  }

  if (busqueda.value?.trim()) {
    params.q = busqueda.value.trim()
  }

  if (
    estadoFiltro.value
    && estadoFiltro.value !== 'Todos'
  ) {
    params.estado = estadoFiltro.value
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

async function cargarPanel() {
  loadingPanel.value = true

  try {
    const { data } = await api.get(
      '/suscripciones-motrix/panel',
      {
        params: parametrosPanel()
      }
    )

    panel.value =
      data?.data || panelVacio()
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      message: mensajeError(error)
    })
  } finally {
    loadingPanel.value = false
  }
}

async function cargarSuscripciones() {
  loadingLista.value = true

  try {
    const { data } = await api.get(
      '/suscripciones-motrix',
      {
        params: parametrosLista()
      }
    )

    suscripciones.value =
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
    loadingLista.value = false
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

async function abrirDetalle(row) {
  dialogDetalle.value = true
  cargandoDetalle.value = true
  detalle.value = row

  try {
    const { data } = await api.get(
      `/suscripciones-motrix/${row.id}`
    )

    detalle.value =
      data?.data || row
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      message: mensajeError(error)
    })
  } finally {
    cargandoDetalle.value = false
  }
}

async function prepararRenovacion(row) {
  try {
    const respuesta = await api.post(
      `/suscripciones-motrix/${row.id}/generar-renovacion`
    )

    $q.notify({
      type: 'positive',
      position: 'top',
      icon: 'check_circle',
      message:
        respuesta.data?.mensaje
        || 'Renovación preparada correctamente.'
    })

    await actualizarTodo()
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      message: mensajeError(error)
    })
  }
}

async function actualizarPanel() {
  paginacion.value.page = 1
  await Promise.all([
    cargarPanel(),
    cargarSuscripciones()
  ])
}

async function actualizarTodo() {
  await Promise.all([
    cargarPanel(),
    cargarSuscripciones()
  ])
}

async function cambioSindicato() {
  paginacion.value.page = 1
  await actualizarTodo()
}

async function reiniciarLista() {
  paginacion.value.page = 1
  await cargarSuscripciones()
}

async function solicitarPagina(props) {
  paginacion.value = {
    ...paginacion.value,
    page: props.pagination.page,
    rowsPerPage: props.pagination.rowsPerPage
  }

  await cargarSuscripciones()
}

function nombreConductor(row) {
  const persona =
    row?.mototaxista?.persona

  const nombre = [
    persona?.nombre,
    persona?.apellidos
  ]
    .filter(Boolean)
    .join(' ')
    .trim()

  return nombre || 'Mototaxista'
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

  const soloFecha =
    String(valor).slice(0, 10)

  const [anio, mes, dia] =
    soloFecha.split('-')

  if (!anio || !mes || !dia) {
    return String(valor)
  }

  return `${dia}/${mes}/${anio}`
}

function textoDias(valor) {
  const dias = Number(valor || 0)

  if (dias < 0) {
    return `${Math.abs(dias)} día(s) vencida`
  }

  if (dias === 0) {
    return 'Vence hoy'
  }

  return `${dias} día(s) restantes`
}

function colorEstado(estado) {
  const valor =
    String(estado || '')
      .trim()
      .toLowerCase()

  if (valor === 'activa') return 'green-8'
  if (valor === 'por vencer') return 'orange-8'
  if (valor === 'periodo de gracia') return 'amber-9'
  if (valor === 'vencida') return 'negative'
  if (valor === 'suspendida') return 'grey-8'

  return 'blue-grey-7'
}

function colorPago(estado) {
  const valor =
    String(estado || '')
      .trim()
      .toLowerCase()

  if (valor === 'pagado') return 'green-8'
  if (valor === 'pendiente') return 'orange-8'
  if (valor === 'vencido') return 'negative'
  if (valor === 'exonerado') return 'blue-8'

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
    || 'No se pudo completar la operación.'
  )
}

onMounted(async () => {
  await cargarSindicatos()
  await actualizarTodo()
})
</script>

<style scoped>
.stat-card,
.plan-card {
  height: 100%;
  border-radius: 14px;
}

.dialog-card {
  width: min(920px, 94vw);
  max-width: 920px;
}

.min-width-zero {
  min-width: 0;
}
</style>
