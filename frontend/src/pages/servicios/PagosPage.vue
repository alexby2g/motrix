<template>
  <q-page :class="$q.screen.lt.sm ? 'q-pa-sm bg-grey-2' : 'q-pa-lg bg-grey-2'">
    <!-- ENCABEZADO -->
    <div class="row items-center justify-between q-mb-lg q-col-gutter-sm">
      <div class="col-12 col-sm">
        <div :class="$q.screen.lt.sm ? 'text-h5' : 'text-h4'" class="text-weight-bold text-grey-9">
          Gestión de Pagos
        </div>
        <div class="text-subtitle2 text-grey-6">
          Historial de transacciones financieras y recaudación
        </div>
      </div>

      <div class="col-12 col-sm-auto">
        <q-btn
          color="positive"
          icon="account_balance_wallet"
          label="Registrar Pago"
          class="q-px-md text-bold full-width"
          unelevated
          @click="openDialogForm()"
        />
      </div>
    </div>

    <!-- INDICADORES -->
    <div class="row q-col-gutter-md q-mb-md">
      <div class="col-12 col-md-6">
        <q-card class="bg-dark text-white border-radius-md shadow-2 full-height">
          <q-card-section class="row items-center justify-between">
            <div>
              <div class="text-caption text-grey-4">Total recaudado</div>
              <div class="text-h4 text-bold text-amber">{{ formatearMonto(totalRecaudado) }}</div>
              <div class="text-caption text-grey-4">{{ pagosCompletados }} pagos completados</div>
            </div>
            <q-avatar color="amber" text-color="dark" icon="account_balance_wallet" size="58px" />
          </q-card-section>
        </q-card>
      </div>

      <div class="col-6 col-md-3">
        <q-card class="bg-green-1 border-radius-md shadow-1 full-height">
          <q-card-section class="text-center">
            <q-icon name="payments" color="positive" size="28px" />
            <div class="text-caption text-grey-7 q-mt-xs">Efectivo</div>
            <div class="text-h6 text-bold text-positive">{{ formatearMonto(totalEfectivo) }}</div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-6 col-md-3">
        <q-card class="bg-indigo-1 border-radius-md shadow-1 full-height">
          <q-card-section class="text-center">
            <q-icon name="qr_code_2" color="indigo-7" size="28px" />
            <div class="text-caption text-grey-7 q-mt-xs">Digital / QR</div>
            <div class="text-h6 text-bold text-indigo-7">{{ formatearMonto(totalDigital) }}</div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- TABLA / TARJETAS -->
    <q-card class="shadow-2 border-radius-md overflow-hidden">
      <q-card-section class="q-pa-none">
        <q-table
          class="motrix-table"
          :rows="pagos"
          :columns="columns"
          row-key="id"
          :filter="filter"
          :loading="loading"
          :grid="$q.screen.lt.xl"
          :hide-header="$q.screen.lt.xl"
          :rows-per-page-options="[6, 12, 24, 0]"
          rows-per-page-label="Registros por página"
          no-data-label="No se registran transacciones de pago"
          no-results-label="No se encontraron resultados"
          loading-label="Cargando pagos..."
          flat
          binary-state-sort
        >
          <template #top>
            <div class="row items-center full-width q-col-gutter-sm q-pa-sm">
              <div class="col-12 col-sm">
                <div class="text-subtitle1 text-weight-bold text-grey-8">
                  {{ pagos.length }} pagos registrados
                </div>
              </div>

              <div class="col-12 col-sm-auto">
                <q-input
                  v-model="filter"
                  outlined
                  dense
                  debounce="300"
                  placeholder="Buscar pago..."
                  class="bg-white search-input"
                  clearable
                >
                  <template #append><q-icon name="search" /></template>
                </q-input>
              </div>
            </div>
          </template>

          <template #body-cell-mototaxista="props">
            <q-td :props="props">
              <div class="row items-center no-wrap">
                <q-avatar color="blue-1" text-color="primary" icon="two_wheeler" size="34px" class="q-mr-sm" />
                <div class="text-weight-bold text-grey-9">{{ getConductorNombre(props.row) }}</div>
              </div>
            </q-td>
          </template>

          <template #body-cell-ruta="props">
            <q-td :props="props">
              <div class="route-table-cell">
                {{ getOrigen(props.row) }}
                <q-icon name="arrow_forward" color="grey-6" class="q-mx-xs" />
                {{ getDestino(props.row) }}
              </div>
            </q-td>
          </template>

          <template #body-cell-monto="props">
            <q-td :props="props" class="text-weight-bold text-positive text-right">
              {{ formatearMonto(props.row.monto) }}
            </q-td>
          </template>

          <template #body-cell-metodo="props">
            <q-td :props="props" class="text-center">
              <q-chip
                :color="getMetodoColor(props.row.metodo)"
                text-color="white"
                :icon="getMetodoIcono(props.row.metodo)"
                dense
                class="text-bold"
              >
                {{ props.row.metodo || 'No especificado' }}
              </q-chip>
            </q-td>
          </template>

          <template #body-cell-estado="props">
            <q-td :props="props" class="text-center">
              <q-chip
                :color="getEstadoColor(props.row.estado)"
                text-color="white"
                dense
                class="text-bold text-uppercase"
              >
                {{ props.row.estado || 'Sin estado' }}
              </q-chip>
            </q-td>
          </template>

          <template #body-cell-actions="props">
            <q-td :props="props" class="text-center">
              <q-btn flat round dense icon="more_vert" color="grey-8" aria-label="Acciones del pago">
                <q-menu auto-close anchor="bottom right" self="top right">
                  <q-list style="min-width: 210px">
                    <q-item clickable @click="openDetail(props.row)">
                      <q-item-section avatar><q-icon name="visibility" color="positive" /></q-item-section>
                      <q-item-section>Ver detalle</q-item-section>
                    </q-item>
                    <q-separator />
                    <q-item clickable @click="openDialogForm(props.row)">
                      <q-item-section avatar><q-icon name="edit" color="primary" /></q-item-section>
                      <q-item-section>Editar pago</q-item-section>
                    </q-item>
                    <q-item clickable @click="confirmDelete(props.row)">
                      <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                      <q-item-section class="text-negative">Eliminar pago</q-item-section>
                    </q-item>
                  </q-list>
                </q-menu>
              </q-btn>
            </q-td>
          </template>

          <!-- TARJETAS RESPONSIVAS -->
          <template #item="props">
            <div class="q-pa-sm col-12 col-md-6">
              <q-card class="payment-card border-radius-md shadow-1 full-height">
                <q-card-section class="q-pb-sm">
                  <div class="row items-start no-wrap">
                    <q-avatar
                      :color="getMetodoLightColor(props.row.metodo)"
                      :text-color="getMetodoTextColor(props.row.metodo)"
                      :icon="getMetodoIcono(props.row.metodo)"
                      size="44px"
                      class="q-mr-sm"
                    />

                    <div class="col min-width-zero">
                      <div class="text-caption text-grey-6">
                        Pago #{{ props.row.id }} · Servicio #{{ props.row.id_servicio || '—' }}
                      </div>
                      <div class="text-subtitle1 text-weight-bold text-grey-9 ellipsis">
                        {{ getConductorNombre(props.row) }}
                      </div>
                    </div>

                    <q-btn flat round dense icon="more_vert" color="grey-8">
                      <q-menu auto-close anchor="bottom right" self="top right">
                        <q-list style="min-width: 210px">
                          <q-item clickable @click="openDetail(props.row)">
                            <q-item-section avatar><q-icon name="visibility" color="positive" /></q-item-section>
                            <q-item-section>Ver detalle</q-item-section>
                          </q-item>
                          <q-separator />
                          <q-item clickable @click="openDialogForm(props.row)">
                            <q-item-section avatar><q-icon name="edit" color="primary" /></q-item-section>
                            <q-item-section>Editar pago</q-item-section>
                          </q-item>
                          <q-item clickable @click="confirmDelete(props.row)">
                            <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                            <q-item-section class="text-negative">Eliminar pago</q-item-section>
                          </q-item>
                        </q-list>
                      </q-menu>
                    </q-btn>
                  </div>
                </q-card-section>

                <q-separator />

                <q-card-section class="q-gutter-y-md">
                  <div class="route-line">
                    <q-icon name="radio_button_checked" color="positive" size="18px" />
                    <div class="min-width-zero">
                      <div class="text-caption text-grey-6">Origen</div>
                      <div class="text-body2 text-weight-medium text-wrap">{{ getOrigen(props.row) }}</div>
                    </div>
                  </div>

                  <div class="route-connector" />

                  <div class="route-line">
                    <q-icon name="location_on" color="negative" size="20px" />
                    <div class="min-width-zero">
                      <div class="text-caption text-grey-6">Destino</div>
                      <div class="text-body2 text-weight-medium text-wrap">{{ getDestino(props.row) }}</div>
                    </div>
                  </div>

                  <div class="row items-end justify-between q-pt-sm">
                    <div>
                      <div class="text-caption text-grey-6">Método de pago</div>
                      <q-chip
                        :color="getMetodoColor(props.row.metodo)"
                        text-color="white"
                        :icon="getMetodoIcono(props.row.metodo)"
                        dense
                        class="text-bold q-ma-none"
                      >
                        {{ props.row.metodo || 'No especificado' }}
                      </q-chip>
                    </div>

                    <div class="text-right">
                      <div class="text-caption text-grey-6">Monto recaudado</div>
                      <div class="text-h5 text-weight-bold text-positive">{{ formatearMonto(props.row.monto) }}</div>
                    </div>
                  </div>

                  <div class="row justify-end">
                    <q-chip
                      :color="getEstadoColor(props.row.estado)"
                      text-color="white"
                      dense
                      class="text-bold text-uppercase"
                    >
                      {{ props.row.estado || 'Sin estado' }}
                    </q-chip>
                  </div>
                </q-card-section>
              </q-card>
            </div>
          </template>
        </q-table>
      </q-card-section>
    </q-card>

    <!-- DETALLE -->
    <q-dialog v-model="detailDialogOpen">
      <q-card class="detail-dialog border-radius-md">
        <q-card-section class="bg-positive text-white row items-center no-wrap">
          <q-icon name="account_balance_wallet" size="sm" class="q-mr-sm" />
          <div class="text-h6 text-bold">Detalle del pago</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section v-if="selectedPayment" class="q-gutter-y-md">
          <div class="row items-center justify-between">
            <div>
              <div class="text-caption text-grey-6">Monto total</div>
              <div class="text-h4 text-weight-bold text-positive">{{ formatearMonto(selectedPayment.monto) }}</div>
            </div>
            <q-avatar
              :color="getMetodoLightColor(selectedPayment.metodo)"
              :text-color="getMetodoTextColor(selectedPayment.metodo)"
              :icon="getMetodoIcono(selectedPayment.metodo)"
              size="58px"
            />
          </div>

          <q-separator />

          <div class="row items-center no-wrap">
            <q-avatar color="blue-1" text-color="primary" icon="two_wheeler" size="42px" class="q-mr-sm" />
            <div>
              <div class="text-caption text-grey-6">Mototaxista</div>
              <div class="text-subtitle1 text-weight-bold">{{ getConductorNombre(selectedPayment) }}</div>
            </div>
          </div>

          <div class="route-line">
            <q-icon name="radio_button_checked" color="positive" size="18px" />
            <div><div class="text-caption text-grey-6">Origen</div><div class="text-body1 text-weight-medium">{{ getOrigen(selectedPayment) }}</div></div>
          </div>

          <div class="route-line">
            <q-icon name="location_on" color="negative" size="20px" />
            <div><div class="text-caption text-grey-6">Destino</div><div class="text-body1 text-weight-medium">{{ getDestino(selectedPayment) }}</div></div>
          </div>

          <div class="row items-center justify-between">
            <q-chip :color="getMetodoColor(selectedPayment.metodo)" text-color="white" :icon="getMetodoIcono(selectedPayment.metodo)">
              {{ selectedPayment.metodo || 'No especificado' }}
            </q-chip>
            <q-chip :color="getEstadoColor(selectedPayment.estado)" text-color="white" class="text-uppercase text-bold">
              {{ selectedPayment.estado || 'Sin estado' }}
            </q-chip>
          </div>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md bg-grey-1">
          <q-btn flat label="Cerrar" color="grey-7" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- CREAR / EDITAR -->
    <q-dialog v-model="dialogOpen" persistent>
      <q-card class="form-dialog border-radius-md">
        <q-card-section class="bg-positive text-white row items-center">
          <div class="text-h6 text-bold">
            {{ isEditing ? 'Modificar Registro de Pago' : 'Registrar Nuevo Pago' }}
          </div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-form @submit.prevent="savePago">
          <q-card-section class="q-gutter-md q-pt-lg">
            <q-input
              v-model.number="form.monto"
              outlined
              dense
              type="number"
              min="0.01"
              step="0.10"
              label="Monto Total (Bs.) *"
              prefix="Bs."
            />

            <q-select
              v-model="form.metodo"
              :options="metodoOptions"
              outlined
              dense
              label="Método de Pago *"
            />

            <q-select
              v-model="form.estado"
              :options="estadoOptions"
              outlined
              dense
              label="Estado de la Transacción *"
            />

            <q-select
              v-model="form.id_servicio"
              :options="serviciosOptions"
              outlined
              dense
              label="Vincular a Servicio *"
              emit-value
              map-options
              option-value="id"
              option-label="detalles"
            />
          </q-card-section>

          <q-card-actions align="right" class="q-pa-md bg-grey-1">
            <q-btn flat label="Cancelar" color="grey-7" v-close-popup />
            <q-btn
              type="submit"
              :label="isEditing ? 'Actualizar' : 'Registrar'"
              color="positive"
              class="text-bold"
              :loading="saving"
              unelevated
            />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useQuasar } from 'quasar'
import { api } from '../../boot/axios.js'

const $q = useQuasar()

const pagos = ref([])
const serviciosOptions = ref([])

const filter = ref('')
const loading = ref(false)
const saving = ref(false)

const dialogOpen = ref(false)
const detailDialogOpen = ref(false)
const isEditing = ref(false)
const selectedPayment = ref(null)

const metodoOptions = ['Efectivo', 'QR', 'Transferencia / QR']
const estadoOptions = ['Pendiente', 'Completado', 'Reembolsado']

const formDefault = {
  id: null,
  monto: 0,
  metodo: 'Efectivo',
  estado: 'Completado',
  id_servicio: null
}

const form = ref({ ...formDefault })

const normalizar = (valor) => String(valor || '').trim().toLowerCase()
const esCompletado = (pago) => normalizar(pago.estado) === 'completado'
const esEfectivo = (pago) => normalizar(pago.metodo) === 'efectivo'

const pagosCompletados = computed(() => pagos.value.filter(esCompletado).length)

const totalRecaudado = computed(() => {
  return pagos.value
    .filter(esCompletado)
    .reduce((total, pago) => total + (Number.parseFloat(pago.monto) || 0), 0)
})

const totalEfectivo = computed(() => {
  return pagos.value
    .filter((pago) => esCompletado(pago) && esEfectivo(pago))
    .reduce((total, pago) => total + (Number.parseFloat(pago.monto) || 0), 0)
})

const totalDigital = computed(() => {
  return pagos.value
    .filter((pago) => esCompletado(pago) && !esEfectivo(pago))
    .reduce((total, pago) => total + (Number.parseFloat(pago.monto) || 0), 0)
})

const getConductorNombre = (pago) => {
  return pago?.servicio?.mototaxista?.persona?.nombre
    || pago?.servicio?.conductor?.persona?.nombre
    || pago?.mototaxista_nombre
    || 'Conductor no identificado'
}

const getOrigen = (pago) => pago?.servicio?.solicitud?.origen || 'Origen no registrado'
const getDestino = (pago) => pago?.servicio?.solicitud?.destino || 'Destino no registrado'

const formatearMonto = (monto) => {
  const numero = Number.parseFloat(monto)
  return `Bs. ${Number.isFinite(numero) ? numero.toFixed(2) : '0.00'}`
}

const getMetodoIcono = (metodo) => normalizar(metodo) === 'efectivo' ? 'payments' : 'qr_code_2'
const getMetodoColor = (metodo) => normalizar(metodo) === 'efectivo' ? 'positive' : 'indigo-7'
const getMetodoLightColor = (metodo) => normalizar(metodo) === 'efectivo' ? 'green-1' : 'indigo-1'
const getMetodoTextColor = (metodo) => normalizar(metodo) === 'efectivo' ? 'positive' : 'indigo-7'

const getEstadoColor = (estado) => {
  const valor = normalizar(estado)
  if (valor === 'completado') return 'positive'
  if (valor === 'pendiente') return 'orange-8'
  if (valor === 'reembolsado') return 'negative'
  return 'grey-7'
}

const columns = [
  { name: 'id', label: 'Pago', align: 'left', field: (row) => row.id, sortable: true },
  { name: 'servicio_id', label: 'Servicio', align: 'left', field: (row) => row.id_servicio ? `#${row.id_servicio}` : '—', sortable: true },
  { name: 'mototaxista', label: 'Mototaxista', align: 'left', field: (row) => getConductorNombre(row), sortable: true },
  { name: 'ruta', label: 'Ruta', align: 'left', field: (row) => `${getOrigen(row)} ${getDestino(row)}` },
  { name: 'monto', label: 'Monto', align: 'right', field: 'monto', sortable: true },
  { name: 'metodo', label: 'Método', align: 'center', field: 'metodo' },
  { name: 'estado', label: 'Estado', align: 'center', field: 'estado', sortable: true },
  { name: 'actions', label: '', align: 'center', field: 'actions' }
]

const cargarDatos = async () => {
  loading.value = true

  try {
    const [resPagos, resServicios] = await Promise.all([
      api.get('/pagos'),
      api.get('/servicios')
    ])

    pagos.value = Array.isArray(resPagos?.data) ? resPagos.data : []

    const servicios = Array.isArray(resServicios?.data) ? resServicios.data : []
    serviciosOptions.value = servicios.map((servicio) => ({
      id: servicio.id,
      detalles: `Servicio #${servicio.id} - ${servicio?.mototaxista?.persona?.nombre || `Mototaxista ${servicio.id_mototaxista || 'sin asignar'}`}`
    }))
  } catch (error) {
    console.error('Error cargando pagos:', error)
    $q.notify({ type: 'negative', message: 'No se pudo cargar la información de pagos.' })
  } finally {
    loading.value = false
  }
}

const openDetail = (row) => {
  selectedPayment.value = row
  detailDialogOpen.value = true
}

const openDialogForm = (row = null) => {
  if (row) {
    isEditing.value = true
    form.value = {
      id: row.id,
      monto: Number.parseFloat(row.monto) || 0,
      metodo: row.metodo || 'Efectivo',
      estado: row.estado || 'Completado',
      id_servicio: row.id_servicio ?? row.servicio?.id ?? null
    }
  } else {
    isEditing.value = false
    form.value = { ...formDefault }
  }

  dialogOpen.value = true
}

const savePago = async () => {
  if (!form.value.monto || !form.value.metodo || !form.value.estado || !form.value.id_servicio) {
    $q.notify({ type: 'negative', message: 'Los campos marcados con (*) son obligatorios.' })
    return
  }

  const payload = {
    monto: Number.parseFloat(form.value.monto),
    metodo: form.value.metodo,
    estado: form.value.estado,
    id_servicio: form.value.id_servicio
  }

  saving.value = true

  try {
    if (isEditing.value) {
      await api.put(`/pagos/${form.value.id}`, payload)
    } else {
      await api.post('/pagos', payload)
    }

    dialogOpen.value = false
    await cargarDatos()

    $q.notify({
      type: 'positive',
      message: isEditing.value ? 'Pago actualizado correctamente.' : 'Pago registrado correctamente.'
    })
  } catch (error) {
    console.error('Error guardando pago:', error)
    $q.notify({
      type: 'negative',
      message: error?.response?.data?.message || 'No se pudo guardar el pago.'
    })
  } finally {
    saving.value = false
  }
}

const confirmDelete = (row) => {
  $q.dialog({
    title: 'Eliminar pago',
    message: `¿Deseas eliminar el pago #${row.id}? Esta acción afectará la contabilidad.`,
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Eliminar', color: 'negative' },
    persistent: true
  }).onOk(async () => {
    try {
      await api.delete(`/pagos/${row.id}`)
      await cargarDatos()
      $q.notify({ type: 'positive', message: 'Pago eliminado correctamente.' })
    } catch (error) {
      console.error('Error eliminando pago:', error)
      $q.notify({ type: 'negative', message: 'No se pudo eliminar el pago.' })
    }
  })
}

onMounted(cargarDatos)
</script>

<style scoped>
.border-radius-md { border-radius: 12px; }
.overflow-hidden { overflow: hidden; }
.min-width-zero { min-width: 0; }
.text-wrap { white-space: normal; overflow-wrap: anywhere; word-break: break-word; }
.search-input { width: 280px; }
.payment-card { border-left: 4px solid var(--q-positive); transition: transform 0.2s ease, box-shadow 0.2s ease; }
.payment-card:hover { transform: translateY(-2px); box-shadow: 0 5px 14px rgba(0, 0, 0, 0.12); }
.route-line { display: grid; grid-template-columns: 22px minmax(0, 1fr); gap: 8px; align-items: start; }
.route-connector { width: 2px; height: 14px; margin-left: 8px; background: repeating-linear-gradient(to bottom, #bdbdbd 0, #bdbdbd 4px, transparent 4px, transparent 8px); }
.route-table-cell { max-width: 420px; white-space: normal; line-height: 1.35; }
.form-dialog { width: 480px; max-width: 95vw; }
.detail-dialog { width: 620px; max-width: 95vw; }

@media (max-width: 599px) {
  .search-input { width: 100%; }
  .form-dialog, .detail-dialog { width: 100%; max-width: 100vw; }
}
</style>
