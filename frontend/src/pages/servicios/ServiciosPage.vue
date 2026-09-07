<template>
  <q-page
    :class="
      $q.screen.lt.sm
        ? 'q-pa-sm bg-grey-2'
        : 'q-pa-lg bg-grey-2'
    "
  >
    <div
      class="row items-center justify-between q-mb-lg q-col-gutter-sm"
    >
      <div class="col-12 col-sm">
        <div
          :class="
            $q.screen.lt.sm
              ? 'text-h5'
              : 'text-h4'
          "
          class="text-weight-bold text-grey-9"
        >
          Control de Servicios
        </div>
        <div class="text-subtitle2 text-grey-6">
          Monitoreo de carreras asignadas y estados de viaje
        </div>
      </div>

      <div class="col-12 col-sm-auto">
        <q-btn
          color="positive"
          icon="assignment_turned_in"
          label="Asignar Servicio"
          class="q-px-md text-bold full-width"
          unelevated
          @click="openDialogForm()"
        />
      </div>
    </div>

    <div class="row q-col-gutter-md q-mb-md">
      <div class="col-12 col-sm-4">
        <q-card class="kpi-card shadow-1 border-radius-md">
          <q-card-section class="row items-center no-wrap">
            <q-avatar
              color="blue-1"
              text-color="primary"
              icon="receipt_long"
              size="48px"
            />
            <div class="q-ml-md">
              <div class="text-caption text-grey-6">
                Total servicios
              </div>
              <div class="text-h5 text-weight-bold text-grey-9">
                {{ estadisticas.total }}
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-6 col-sm-4">
        <q-card class="kpi-card shadow-1 border-radius-md">
          <q-card-section class="row items-center no-wrap">
            <q-avatar
              color="orange-1"
              text-color="orange-9"
              icon="navigation"
              size="48px"
            />
            <div class="q-ml-md">
              <div class="text-caption text-grey-6">
                Activos
              </div>
              <div class="text-h5 text-weight-bold text-orange-9">
                {{ estadisticas.activos }}
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-6 col-sm-4">
        <q-card class="kpi-card shadow-1 border-radius-md">
          <q-card-section class="row items-center no-wrap">
            <q-avatar
              color="green-1"
              text-color="positive"
              icon="check_circle"
              size="48px"
            />
            <div class="q-ml-md">
              <div class="text-caption text-grey-6">
                Finalizados
              </div>
              <div class="text-h5 text-weight-bold text-positive">
                {{ estadisticas.finalizados }}
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <q-card class="shadow-2 border-radius-md overflow-hidden">
      <q-card-section class="q-pa-none">
        <q-table
          class="motrix-table"
          :rows="servicios"
          :columns="columns"
          row-key="id"
          :loading="loading"
          v-model:pagination="pagination"
          :grid="$q.screen.lt.xl"
          :hide-header="$q.screen.lt.xl"
          :rows-per-page-options="[6, 12, 24, 50]"
          rows-per-page-label="Registros por página"
          no-data-label="No hay servicios registrados"
          no-results-label="No se encontraron resultados"
          loading-label="Cargando servicios..."
          flat
          binary-state-sort
          @request="onRequestServicios"
          @row-click="(_, row) => openDetail(row)"
        >
          <template #top>
            <div
              class="row items-center full-width q-col-gutter-sm q-pa-sm"
            >
              <div class="col-12 col-sm">
                <div
                  class="text-subtitle1 text-weight-bold text-grey-8"
                >
                  {{ estadisticas.total }} servicios registrados
                </div>
              </div>

              <div class="col-12 col-sm-auto">
                <q-input
                  v-model="filter"
                  outlined
                  dense
                  debounce="400"
                  placeholder="Buscar servicio, ruta o conductor..."
                  class="bg-white search-input"
                  clearable
                  @update:model-value="buscarServicios"
                >
                  <template #append>
                    <q-icon name="search" />
                  </template>
                </q-input>
              </div>
            </div>
          </template>

          <template #body-cell-conductor="props">
            <q-td :props="props">
              <div class="row items-center no-wrap">
                <q-avatar
                  color="blue-1"
                  text-color="primary"
                  icon="two_wheeler"
                  size="34px"
                  class="q-mr-sm"
                />
                <div class="text-weight-bold text-grey-9">
                  {{ getConductorNombre(props.row) }}
                </div>
              </div>
            </q-td>
          </template>

          <template #body-cell-ruta="props">
            <q-td :props="props">
              <div class="route-table-cell">
                <span>{{ getOrigen(props.row) }}</span>
                <q-icon
                  name="arrow_forward"
                  color="grey-6"
                  class="q-mx-xs"
                />
                <span>{{ getDestino(props.row) }}</span>
              </div>
            </q-td>
          </template>

          <template #body-cell-hora_inicio="props">
            <q-td :props="props">
              {{ formatearHora(props.row.hora_inicio) }}
            </q-td>
          </template>

          <template #body-cell-hora_fin="props">
            <q-td :props="props">
              {{ formatearHora(props.row.hora_fin) }}
            </q-td>
          </template>

          <template #body-cell-estado="props">
            <q-td :props="props" class="text-center">
              <q-chip
                :color="getServicioColor(props.row.estado)"
                text-color="white"
                dense
                class="text-bold text-uppercase"
              >
                {{ props.row.estado || 'Sin estado' }}
              </q-chip>
            </q-td>
          </template>

          <template #body-cell-actions="props">
            <q-td :props="props" class="text-center" @click.stop>
              <q-btn
                flat
                round
                dense
                icon="more_vert"
                color="grey-8"
                aria-label="Acciones del servicio"
              >
                <q-menu auto-close anchor="bottom right" self="top right">
                  <q-list style="min-width: 210px">
                    <q-item clickable @click="openDetail(props.row)">
                      <q-item-section avatar>
                        <q-icon name="visibility" color="positive" />
                      </q-item-section>
                      <q-item-section>Ver detalle</q-item-section>
                    </q-item>
                    <q-separator />
                    <q-item clickable @click="openDialogForm(props.row)">
                      <q-item-section avatar>
                        <q-icon name="edit" color="primary" />
                      </q-item-section>
                      <q-item-section>Editar servicio</q-item-section>
                    </q-item>
                    <q-item clickable @click="confirmDelete(props.row)">
                      <q-item-section avatar>
                        <q-icon name="delete" color="negative" />
                      </q-item-section>
                      <q-item-section class="text-negative">
                        Eliminar servicio
                      </q-item-section>
                    </q-item>
                  </q-list>
                </q-menu>
              </q-btn>
            </q-td>
          </template>

          <template #item="props">
            <div class="q-pa-sm col-12 col-md-6">
              <q-card
                class="service-card border-radius-md shadow-1 full-height cursor-pointer"
                @click="openDetail(props.row)"
              >
                <q-card-section class="q-pb-sm">
                  <div class="row items-start no-wrap">
                    <q-avatar
                      color="blue-1"
                      text-color="primary"
                      icon="two_wheeler"
                      size="44px"
                      class="q-mr-sm"
                    />

                    <div class="col min-width-zero">
                      <div class="text-caption text-grey-6">
                        Servicio #{{ props.row.id }}
                      </div>
                      <div
                        class="text-subtitle1 text-weight-bold text-grey-9 ellipsis"
                      >
                        {{ getConductorNombre(props.row) }}
                      </div>
                    </div>

                    <q-btn
                      flat
                      round
                      dense
                      icon="more_vert"
                      color="grey-8"
                      @click.stop
                    >
                      <q-menu
                        auto-close
                        anchor="bottom right"
                        self="top right"
                      >
                        <q-list style="min-width: 210px">
                          <q-item clickable @click="openDetail(props.row)">
                            <q-item-section avatar>
                              <q-icon
                                name="visibility"
                                color="positive"
                              />
                            </q-item-section>
                            <q-item-section>Ver detalle</q-item-section>
                          </q-item>
                          <q-separator />
                          <q-item
                            clickable
                            @click="openDialogForm(props.row)"
                          >
                            <q-item-section avatar>
                              <q-icon name="edit" color="primary" />
                            </q-item-section>
                            <q-item-section>Editar servicio</q-item-section>
                          </q-item>
                          <q-item
                            clickable
                            @click="confirmDelete(props.row)"
                          >
                            <q-item-section avatar>
                              <q-icon name="delete" color="negative" />
                            </q-item-section>
                            <q-item-section class="text-negative">
                              Eliminar servicio
                            </q-item-section>
                          </q-item>
                        </q-list>
                      </q-menu>
                    </q-btn>
                  </div>
                </q-card-section>

                <q-separator />

                <q-card-section class="q-gutter-y-md">
                  <div class="route-line">
                    <q-icon
                      name="radio_button_checked"
                      color="positive"
                      size="18px"
                    />
                    <div class="min-width-zero">
                      <div class="text-caption text-grey-6">Origen</div>
                      <div
                        class="text-body2 text-weight-medium text-wrap"
                      >
                        {{ getOrigen(props.row) }}
                      </div>
                    </div>
                  </div>

                  <div class="route-connector" />

                  <div class="route-line">
                    <q-icon
                      name="location_on"
                      color="negative"
                      size="20px"
                    />
                    <div class="min-width-zero">
                      <div class="text-caption text-grey-6">Destino</div>
                      <div
                        class="text-body2 text-weight-medium text-wrap"
                      >
                        {{ getDestino(props.row) }}
                      </div>
                    </div>
                  </div>

                  <div class="row q-col-gutter-sm">
                    <div class="col-6">
                      <div class="info-box">
                        <q-icon
                          name="play_circle"
                          color="primary"
                          size="20px"
                        />
                        <div>
                          <div class="text-caption text-grey-6">Inicio</div>
                          <div class="text-weight-bold">
                            {{ formatearHora(props.row.hora_inicio) }}
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-6">
                      <div class="info-box">
                        <q-icon
                          name="stop_circle"
                          color="positive"
                          size="20px"
                        />
                        <div>
                          <div class="text-caption text-grey-6">Fin</div>
                          <div class="text-weight-bold">
                            {{ formatearHora(props.row.hora_fin) }}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="row items-center justify-between q-pt-xs">
                    <div class="text-caption text-grey-6">
                      Solicitud #{{ props.row.id_solicitud || '—' }}
                    </div>
                    <q-chip
                      :color="getServicioColor(props.row.estado)"
                      text-color="white"
                      dense
                      class="text-weight-bold text-uppercase"
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

    <q-dialog v-model="detailDialogOpen">
      <q-card class="detail-dialog border-radius-md">
        <q-card-section
          class="bg-positive text-white row items-center no-wrap"
        >
          <q-icon name="receipt_long" size="sm" class="q-mr-sm" />
          <div class="text-h6 text-bold">Detalle del servicio</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section
          v-if="selectedService"
          class="q-gutter-y-md"
        >
          <div class="row items-center no-wrap">
            <q-avatar
              color="blue-1"
              text-color="primary"
              icon="two_wheeler"
              size="48px"
              class="q-mr-md"
            />
            <div>
              <div class="text-caption text-grey-6">Mototaxista</div>
              <div class="text-subtitle1 text-weight-bold">
                {{ getConductorNombre(selectedService) }}
              </div>
            </div>
          </div>

          <q-separator />

          <div class="route-line">
            <q-icon
              name="radio_button_checked"
              color="positive"
              size="18px"
            />
            <div>
              <div class="text-caption text-grey-6">Origen</div>
              <div class="text-body1 text-weight-medium">
                {{ getOrigen(selectedService) }}
              </div>
            </div>
          </div>

          <div class="route-line">
            <q-icon
              name="location_on"
              color="negative"
              size="20px"
            />
            <div>
              <div class="text-caption text-grey-6">Destino</div>
              <div class="text-body1 text-weight-medium">
                {{ getDestino(selectedService) }}
              </div>
            </div>
          </div>

          <div class="row q-col-gutter-sm">
            <div class="col-6">
              <div class="info-box">
                <q-icon name="schedule" color="primary" />
                <div>
                  <div class="text-caption text-grey-6">Inicio</div>
                  <div class="text-bold">
                    {{ formatearHora(selectedService.hora_inicio) }}
                  </div>
                </div>
              </div>
            </div>
            <div class="col-6">
              <div class="info-box">
                <q-icon name="schedule" color="positive" />
                <div>
                  <div class="text-caption text-grey-6">Fin</div>
                  <div class="text-bold">
                    {{ formatearHora(selectedService.hora_fin) }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row items-center justify-between">
            <div class="text-grey-7">
              Servicio #{{ selectedService.id }}
            </div>
            <q-chip
              :color="getServicioColor(selectedService.estado)"
              text-color="white"
              class="text-bold text-uppercase"
            >
              {{ selectedService.estado || 'Sin estado' }}
            </q-chip>
          </div>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md bg-grey-1">
          <q-btn flat label="Cerrar" color="grey-7" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="dialogOpen" persistent>
      <q-card class="form-dialog border-radius-md">
        <q-card-section class="bg-positive text-white row items-center">
          <div class="text-h6 text-bold">
            {{
              isEditing
                ? 'Editar Estado del Servicio'
                : 'Vincular Nuevo Servicio'
            }}
          </div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-form @submit.prevent="saveServicio">
          <q-card-section class="q-gutter-md q-pt-lg">
            <div class="row q-col-gutter-sm">
              <div class="col-6">
                <q-input
                  v-model="form.hora_inicio"
                  outlined
                  dense
                  type="time"
                  label="Hora Inicio *"
                  stack-label
                />
              </div>
              <div class="col-6">
                <q-input
                  v-model="form.hora_fin"
                  outlined
                  dense
                  type="time"
                  label="Hora Fin"
                  stack-label
                  hint="Opcional mientras el servicio esté activo."
                />
              </div>
            </div>

            <q-select
              v-model="form.estado"
              :options="estadoOptions"
              outlined
              dense
              label="Estado del Servicio *"
            />

            <q-select
              v-model="form.id_solicitud"
              :options="solicitudesOptions"
              :option-label="labelSolicitud"
              option-value="id"
              emit-value
              map-options
              use-input
              fill-input
              hide-selected
              clearable
              input-debounce="300"
              outlined
              dense
              label="Seleccionar Solicitud (Ruta) *"
              hint="Escribe ID, origen, destino o pasajero."
              :loading="buscandoSolicitudes"
              @filter="filtrarSolicitudes"
            >
              <template #option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section avatar>
                    <q-avatar
                      color="green-1"
                      text-color="green-9"
                      icon="route"
                    />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>
                      Solicitud #{{ scope.opt.id }}
                    </q-item-label>
                    <q-item-label caption>
                      {{ scope.opt.origen || 'Sin origen' }}
                      →
                      {{ scope.opt.destino || 'Sin destino' }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </template>
              <template #no-option>
                <q-item>
                  <q-item-section class="text-grey-7">
                    Escribe al menos 2 caracteres para buscar una solicitud disponible.
                  </q-item-section>
                </q-item>
              </template>
            </q-select>

            <q-select
              v-model="form.id_mototaxista"
              :options="mototaxistasOptions"
              :option-label="labelMototaxista"
              option-value="id"
              emit-value
              map-options
              use-input
              fill-input
              hide-selected
              clearable
              input-debounce="300"
              outlined
              dense
              label="Asignar Conductor *"
              hint="Escribe nombre, CI, chaleco o teléfono."
              :loading="buscandoMototaxistas"
              @filter="filtrarMototaxistas"
            >
              <template #option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section avatar>
                    <q-avatar
                      color="blue-1"
                      text-color="blue-9"
                      icon="two_wheeler"
                    />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>
                      {{ nombreMototaxista(scope.opt) }}
                    </q-item-label>
                    <q-item-label caption>
                      {{
                        scope.opt.nro_chaleco
                          ? `Chaleco ${scope.opt.nro_chaleco}`
                          : 'Sin chaleco'
                      }}
                      <span v-if="scope.opt.sindicato?.nombre">
                        · {{ scope.opt.sindicato.nombre }}
                      </span>
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </template>
              <template #no-option>
                <q-item>
                  <q-item-section class="text-grey-7">
                    Escribe al menos 2 caracteres para buscar un conductor activo.
                  </q-item-section>
                </q-item>
              </template>
            </q-select>
          </q-card-section>

          <q-card-actions align="right" class="q-pa-md bg-grey-1">
            <q-btn flat label="Cancelar" color="grey-7" v-close-popup />
            <q-btn
              type="submit"
              :label="isEditing ? 'Guardar' : 'Asignar'"
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
import { onMounted, ref } from 'vue'
import { useQuasar } from 'quasar'
import { api } from '../../boot/axios.js'

const $q = useQuasar()

const servicios = ref([])
const solicitudesOptions = ref([])
const mototaxistasOptions = ref([])

const filter = ref('')
const loading = ref(false)
const saving = ref(false)
const buscandoSolicitudes = ref(false)
const buscandoMototaxistas = ref(false)

let secuenciaSolicitudes = 0
let secuenciaMototaxistas = 0

const pagination = ref({
  page: 1,
  rowsPerPage: 12,
  rowsNumber: 0,
  sortBy: 'id',
  descending: true
})

const estadisticas = ref({
  total: 0,
  activos: 0,
  finalizados: 0
})

const dialogOpen = ref(false)
const detailDialogOpen = ref(false)
const isEditing = ref(false)
const selectedService = ref(null)

const estadoOptions = [
  'Activo',
  'En Curso',
  'Finalizado',
  'Cancelado'
]

const formDefault = {
  id: null,
  hora_inicio: '',
  hora_fin: '',
  estado: 'Activo',
  id_solicitud: null,
  id_mototaxista: null
}

const form = ref({ ...formDefault })

const normalizarEstado = (estado) =>
  String(estado || '').trim().toLowerCase()

const nombreCompletoPersona = (persona) =>
  [persona?.nombre, persona?.apellidos]
    .filter(Boolean)
    .join(' ')
    .trim()

const getConductorNombre = (servicio) => {
  const persona =
    servicio?.mototaxista?.persona
    || servicio?.conductor?.persona

  return nombreCompletoPersona(persona)
    || servicio?.mototaxista_nombre
    || 'Sin conductor'
}

const getOrigen = (servicio) =>
  servicio?.solicitud?.origen
  || 'Origen no registrado'

const getDestino = (servicio) =>
  servicio?.solicitud?.destino
  || 'Destino no registrado'

const formatearHora = (hora) => {
  if (!hora) return '--:--'
  return String(hora).slice(0, 5)
}

const getServicioColor = (estado) => {
  const valor = normalizarEstado(estado)

  if (['activo', 'iniciado'].includes(valor)) return 'cyan-8'
  if (valor === 'en curso') return 'primary'
  if (['finalizado', 'terminado'].includes(valor)) return 'positive'
  if (valor === 'cancelado') return 'negative'

  return 'grey-7'
}

const columns = [
  {
    name: 'id',
    label: 'ID',
    align: 'left',
    field: (row) => row.id,
    sortable: true
  },
  {
    name: 'conductor',
    label: 'Mototaxista',
    align: 'left',
    field: (row) => getConductorNombre(row)
  },
  {
    name: 'ruta',
    label: 'Ruta',
    align: 'left',
    field: (row) => `${getOrigen(row)} ${getDestino(row)}`
  },
  {
    name: 'hora_inicio',
    label: 'Inicio',
    align: 'left',
    field: 'hora_inicio'
  },
  {
    name: 'hora_fin',
    label: 'Fin',
    align: 'left',
    field: 'hora_fin'
  },
  {
    name: 'estado',
    label: 'Estado',
    align: 'center',
    field: 'estado'
  },
  {
    name: 'actions',
    label: '',
    align: 'center',
    field: 'actions'
  }
]

const extraerMensajeError = (error, fallback) => {
  const errores = error?.response?.data?.errors

  if (errores && typeof errores === 'object') {
    const primerGrupo = Object.values(errores)[0]

    if (Array.isArray(primerGrupo) && primerGrupo[0]) {
      return primerGrupo[0]
    }
  }

  return error?.response?.data?.mensaje
    || error?.response?.data?.message
    || fallback
}

const cargarServicios = async ({ page, rowsPerPage } = {}) => {
  const pagina = page ?? pagination.value.page
  const porPagina = rowsPerPage ?? pagination.value.rowsPerPage

  loading.value = true

  try {
    const response = await api.get('/servicios', {
      params: {
        paginated: 1,
        page: pagina,
        per_page: porPagina,
        q: String(filter.value || '').trim() || undefined
      }
    })

    const data = response?.data?.data
    const meta = response?.data?.meta || {}

    servicios.value = Array.isArray(data) ? data : []

    pagination.value = {
      ...pagination.value,
      page: Number(meta.current_page || pagina),
      rowsPerPage: Number(meta.per_page || porPagina),
      rowsNumber: Number(meta.total || 0)
    }

    estadisticas.value = {
      total: Number(meta?.stats?.total || 0),
      activos: Number(meta?.stats?.activos || 0),
      finalizados: Number(meta?.stats?.finalizados || 0)
    }
  } catch (error) {
    console.error('Error cargando servicios:', error)
    $q.notify({
      type: 'negative',
      message: 'No se pudo cargar la información de servicios.'
    })
  } finally {
    loading.value = false
  }
}

const onRequestServicios = (props) => {
  cargarServicios({
    page: props.pagination.page,
    rowsPerPage: props.pagination.rowsPerPage
  })
}

const buscarServicios = () => {
  pagination.value.page = 1
  cargarServicios({
    page: 1,
    rowsPerPage: pagination.value.rowsPerPage
  })
}

const labelSolicitud = (solicitud) => {
  if (!solicitud) return ''

  return `#${solicitud.id} - ${solicitud.origen || 'Sin origen'} → ${solicitud.destino || 'Sin destino'}`
}

const nombreMototaxista = (mototaxista) =>
  nombreCompletoPersona(mototaxista?.persona)
  || `Mototaxista #${mototaxista?.id || '—'}`

const labelMototaxista = (mototaxista) => {
  if (!mototaxista) return ''

  const chaleco = mototaxista.nro_chaleco
    ? ` · Chaleco ${mototaxista.nro_chaleco}`
    : ''

  return `${nombreMototaxista(mototaxista)}${chaleco}`
}

const filtrarSolicitudes = async (valor, update) => {
  const texto = String(valor || '').trim()
  const actual = isEditing.value
    ? Number(form.value.id_solicitud || 0)
    : 0

  if (texto.length < 2) {
    secuenciaSolicitudes += 1
    buscandoSolicitudes.value = false

    update(() => {
      solicitudesOptions.value = actual && selectedService.value?.solicitud
        ? [selectedService.value.solicitud]
        : []
    })
    return
  }

  const secuencia = ++secuenciaSolicitudes
  buscandoSolicitudes.value = true

  try {
    const response = await api.get(
      '/solicitudes/opciones-servicio',
      {
        params: {
          q: texto,
          include_id: actual || undefined
        }
      }
    )

    if (secuencia !== secuenciaSolicitudes) return

    update(() => {
      solicitudesOptions.value = Array.isArray(response.data)
        ? response.data
        : []
    })
  } catch (error) {
    console.error('Error buscando solicitudes:', error)

    if (secuencia !== secuenciaSolicitudes) return

    update(() => {
      solicitudesOptions.value = []
    })
  } finally {
    if (secuencia === secuenciaSolicitudes) {
      buscandoSolicitudes.value = false
    }
  }
}

const filtrarMototaxistas = async (valor, update) => {
  const texto = String(valor || '').trim()
  const actual = isEditing.value
    ? Number(form.value.id_mototaxista || 0)
    : 0

  if (texto.length < 2) {
    secuenciaMototaxistas += 1
    buscandoMototaxistas.value = false

    update(() => {
      mototaxistasOptions.value = actual && selectedService.value?.mototaxista
        ? [selectedService.value.mototaxista]
        : []
    })
    return
  }

  const secuencia = ++secuenciaMototaxistas
  buscandoMototaxistas.value = true

  try {
    const response = await api.get(
      '/mototaxistas/opciones-servicio',
      {
        params: {
          q: texto,
          include_id: actual || undefined
        }
      }
    )

    if (secuencia !== secuenciaMototaxistas) return

    update(() => {
      mototaxistasOptions.value = Array.isArray(response.data)
        ? response.data
        : []
    })
  } catch (error) {
    console.error('Error buscando mototaxistas:', error)

    if (secuencia !== secuenciaMototaxistas) return

    update(() => {
      mototaxistasOptions.value = []
    })
  } finally {
    if (secuencia === secuenciaMototaxistas) {
      buscandoMototaxistas.value = false
    }
  }
}

const openDetail = (row) => {
  selectedService.value = row
  detailDialogOpen.value = true
}

const openDialogForm = (row = null) => {
  secuenciaSolicitudes += 1
  secuenciaMototaxistas += 1
  buscandoSolicitudes.value = false
  buscandoMototaxistas.value = false

  if (row) {
    isEditing.value = true
    selectedService.value = row

    form.value = {
      id: row.id,
      hora_inicio: formatearHora(row.hora_inicio),
      hora_fin: row.hora_fin
        ? formatearHora(row.hora_fin)
        : '',
      estado: row.estado || 'Activo',
      id_solicitud:
        row.id_solicitud
        ?? row.solicitud?.id
        ?? null,
      id_mototaxista:
        row.id_mototaxista
        ?? row.mototaxista?.id
        ?? null
    }

    solicitudesOptions.value = row.solicitud
      ? [row.solicitud]
      : []

    mototaxistasOptions.value = row.mototaxista
      ? [row.mototaxista]
      : []
  } else {
    isEditing.value = false
    selectedService.value = null
    form.value = { ...formDefault }
    solicitudesOptions.value = []
    mototaxistasOptions.value = []
  }

  dialogOpen.value = true
}

const saveServicio = async () => {
  if (
    !form.value.hora_inicio
    || !form.value.estado
    || !form.value.id_solicitud
    || !form.value.id_mototaxista
  ) {
    $q.notify({
      type: 'negative',
      message: 'Rellena los campos obligatorios (*)'
    })
    return
  }

  const payload = {
    hora_inicio: form.value.hora_inicio,
    hora_fin: form.value.hora_fin || null,
    estado: form.value.estado,
    id_solicitud: form.value.id_solicitud,
    id_mototaxista: form.value.id_mototaxista
  }

  saving.value = true

  try {
    if (isEditing.value) {
      await api.put(
        `/servicios/${form.value.id}`,
        payload
      )
    } else {
      await api.post('/servicios', payload)
    }

    dialogOpen.value = false
    await cargarServicios()

    $q.notify({
      type: 'positive',
      message: isEditing.value
        ? 'Servicio actualizado correctamente.'
        : 'Servicio registrado correctamente.'
    })
  } catch (error) {
    console.error('Error guardando servicio:', error)
    $q.notify({
      type: 'negative',
      message: extraerMensajeError(
        error,
        'No se pudo guardar el servicio.'
      )
    })
  } finally {
    saving.value = false
  }
}

const confirmDelete = (row) => {
  $q.dialog({
    title: 'Eliminar servicio',
    message: `¿Deseas eliminar el servicio #${row.id}?`,
    cancel: {
      label: 'Cancelar',
      flat: true
    },
    ok: {
      label: 'Eliminar',
      color: 'negative'
    },
    persistent: true
  }).onOk(async () => {
    try {
      await api.delete(`/servicios/${row.id}`)

      if (
        servicios.value.length === 1
        && pagination.value.page > 1
      ) {
        pagination.value.page -= 1
      }

      await cargarServicios()

      $q.notify({
        type: 'positive',
        message: 'Servicio eliminado correctamente.'
      })
    } catch (error) {
      console.error('Error eliminando servicio:', error)
      $q.notify({
        type: 'negative',
        message: extraerMensajeError(
          error,
          'No se pudo eliminar el servicio.'
        )
      })
    }
  })
}

onMounted(() => {
  cargarServicios()
})
</script>

<style scoped>
.border-radius-md {
  border-radius: 12px;
}

.overflow-hidden {
  overflow: hidden;
}

.min-width-zero {
  min-width: 0;
}

.text-wrap {
  white-space: normal;
  overflow-wrap: anywhere;
  word-break: break-word;
}

.search-input {
  width: 320px;
}

.kpi-card {
  min-height: 82px;
}

.service-card {
  border-left: 4px solid var(--q-primary);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.service-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 14px rgba(0, 0, 0, 0.12);
}

.route-line {
  display: grid;
  grid-template-columns: 22px minmax(0, 1fr);
  gap: 8px;
  align-items: start;
}

.route-connector {
  width: 2px;
  height: 14px;
  margin-left: 8px;
  background: repeating-linear-gradient(
    to bottom,
    #bdbdbd 0,
    #bdbdbd 4px,
    transparent 4px,
    transparent 8px
  );
}

.info-box {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px;
  min-height: 58px;
  background: #f6f7f9;
  border-radius: 10px;
}

.route-table-cell {
  max-width: 420px;
  white-space: normal;
  line-height: 1.35;
}

.form-dialog {
  width: 560px;
  max-width: 95vw;
}

.detail-dialog {
  width: 620px;
  max-width: 95vw;
}

@media (max-width: 599px) {
  .search-input {
    width: 100%;
  }

  .form-dialog,
  .detail-dialog {
    width: 100%;
    max-width: 100vw;
  }
}
</style>
