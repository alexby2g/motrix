<template>
  <q-page class="q-pa-md bg-grey-1">
    <div class="row items-center q-col-gutter-md q-mb-lg">
      <div class="col-12 col-md">
        <div class="text-h5 text-weight-bold text-green-9">
          Reportes de Suscripciones MOTRIX
        </div>
        <div class="text-body2 text-grey-7">
          Exporta información comercial y de liquidaciones en PDF o Excel.
        </div>
      </div>

      <div class="col-12 col-md-auto">
        <q-btn
          outline
          color="green-8"
          icon="workspace_premium"
          label="Volver a suscripciones"
          no-caps
          to="/suscripciones-motrix"
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
              label="Periodo"
            >
              <template #prepend>
                <q-icon name="calendar_month" color="green-8" />
              </template>
            </q-input>
          </div>

          <div v-if="esAdminGeneral" class="col-12 col-sm-6 col-md-5">
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
            >
              <template #prepend>
                <q-icon name="business" color="green-8" />
              </template>
            </q-select>
          </div>

          <div class="col-12 col-md">
            <q-btn
              color="green-8"
              icon="refresh"
              label="Actualizar catálogo"
              no-caps
              unelevated
              class="full-width"
              :loading="loading"
              @click="cargarInicial"
            />
          </div>
        </div>
      </q-card-section>
    </q-card>

    <div class="row q-col-gutter-md">
      <div
        v-for="reporte in catalogo"
        :key="reporte.tipo"
        class="col-12 col-md-6 col-xl-4"
      >
        <q-card flat bordered class="report-card full-height">
          <q-card-section>
            <div class="row items-start no-wrap">
              <q-avatar
                color="green-1"
                text-color="green-9"
                :icon="iconoReporte(reporte.tipo)"
              />
              <div class="q-ml-md col">
                <div class="text-subtitle1 text-weight-bold">
                  {{ reporte.titulo }}
                </div>
                <div class="text-body2 text-grey-7 q-mt-xs">
                  {{ reporte.descripcion }}
                </div>
              </div>
            </div>
          </q-card-section>

          <q-card-section
            v-if="reporte.requiere_mototaxista"
            class="q-pt-none"
          >
            <q-select
              v-model="mototaxistaSeleccionado"
              :options="opcionesMototaxista"
              emit-value
              map-options
              use-input
              clearable
              outlined
              dense
              label="Conductor"
              :loading="cargandoMototaxistas"
              @filter="filtrarMototaxistas"
            >
              <template #prepend>
                <q-icon name="two_wheeler" color="green-8" />
              </template>
            </q-select>
          </q-card-section>

          <q-separator />

          <q-card-actions align="right" class="q-pa-md">
            <q-btn
              flat
              color="grey-8"
              icon="visibility"
              label="Vista previa"
              no-caps
              :disable="reporte.requiere_mototaxista && !mototaxistaSeleccionado"
              @click="previsualizar(reporte)"
            />
            <q-btn
              outline
              color="red-8"
              icon="picture_as_pdf"
              label="PDF"
              no-caps
              :disable="reporte.requiere_mototaxista && !mototaxistaSeleccionado"
              @click="descargar(reporte, 'pdf')"
            />
            <q-btn
              color="green-8"
              icon="table_view"
              label="Excel"
              no-caps
              unelevated
              :disable="reporte.requiere_mototaxista && !mototaxistaSeleccionado"
              @click="descargar(reporte, 'excel')"
            />
          </q-card-actions>
        </q-card>
      </div>
    </div>

    <q-dialog v-model="dialogoPreview" maximized>
      <q-card>
        <q-toolbar class="bg-green-9 text-white">
          <q-toolbar-title>
            {{ preview.titulo || 'Vista previa' }}
          </q-toolbar-title>
          <q-btn flat round dense icon="close" v-close-popup />
        </q-toolbar>

        <q-card-section>
          <div class="text-body2 text-grey-7 q-mb-md">
            {{ preview.subtitulo }}
          </div>

          <q-table
            flat
            bordered
            row-key="__row"
            :rows="filasPreview"
            :columns="columnasPreview"
            :pagination="{ rowsPerPage: 20 }"
          />

          <q-banner
            v-if="Object.keys(preview.totales || {}).length"
            rounded
            class="bg-green-1 text-green-10 q-mt-md"
          >
            <strong>Totales:</strong>
            <span
              v-for="(valor, clave) in preview.totales"
              :key="clave"
              class="q-ml-md"
            >
              {{ etiquetaTotal(clave) }}: {{ valor }}
            </span>
          </q-banner>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import {
  computed,
  onMounted,
  ref,
  watch
} from 'vue'
import {
  useQuasar
} from 'quasar'
import {
  api
} from '../../boot/axios.js'

const $q = useQuasar()
const loading = ref(false)
const cargandoSindicatos = ref(false)
const cargandoMototaxistas = ref(false)
const catalogo = ref([])
const sindicatos = ref([])
const mototaxistas = ref([])
const periodo = ref(new Date().toISOString().slice(0, 7))
const sindicatoSeleccionado = ref(null)
const mototaxistaSeleccionado = ref(null)
const dialogoPreview = ref(false)
const preview = ref({
  titulo: '',
  subtitulo: '',
  columnas: {},
  filas: [],
  totales: {}
})

function leerUsuario() {
  try {
    return JSON.parse(localStorage.getItem('motrix_user') || 'null')
  } catch {
    return null
  }
}

const usuario = ref(leerUsuario())
const rol = computed(() =>
  String(usuario.value?.role || '').trim().toLowerCase()
)
const esAdminGeneral = computed(() => rol.value === 'admin_general')

const opcionesSindicato = computed(() =>
  sindicatos.value.map(item => ({
    label: item.nombre,
    value: Number(item.id)
  }))
)

const opcionesMototaxista = computed(() =>
  mototaxistas.value.map(item => ({
    label: nombreMototaxista(item),
    value: Number(item.id_mototaxista)
  }))
)

const columnasPreview = computed(() =>
  Object.entries(preview.value.columnas || {}).map(([clave, etiqueta]) => ({
    name: clave,
    label: etiqueta,
    field: clave,
    align: 'left',
    sortable: true
  }))
)

const filasPreview = computed(() =>
  (preview.value.filas || []).map((fila, index) => ({
    ...fila,
    __row: index
  }))
)

function paramsReporte(reporte) {
  const params = {
    periodo: periodo.value
  }

  if (esAdminGeneral.value && sindicatoSeleccionado.value) {
    params.id_sindicato = sindicatoSeleccionado.value
  }

  if (reporte.requiere_mototaxista && mototaxistaSeleccionado.value) {
    params.id_mototaxista = mototaxistaSeleccionado.value
  }

  return params
}

async function cargarInicial() {
  if (loading.value) return
  loading.value = true
  try {
    await Promise.all([
      cargarCatalogo(),
      esAdminGeneral.value ? cargarSindicatos() : Promise.resolve(),
      cargarMototaxistas()
    ])
  } finally {
    loading.value = false
  }
}

async function cargarCatalogo() {
  const respuesta = await api.get('/reportes-suscripcion-motrix/catalogo')
  catalogo.value = Array.isArray(respuesta.data?.data)
    ? respuesta.data.data
    : []
}

async function cargarSindicatos() {
  cargandoSindicatos.value = true
  try {
    const respuesta = await api.get('/sindicatos')
    const data = respuesta.data?.data ?? respuesta.data
    sindicatos.value = Array.isArray(data) ? data : []
  } catch {
    sindicatos.value = []
  } finally {
    cargandoSindicatos.value = false
  }
}

async function cargarMototaxistas(q = '') {
  cargandoMototaxistas.value = true
  try {
    const respuesta = await api.get('/suscripciones-motrix', {
      params: {
        q: q || undefined,
        id_sindicato:
          esAdminGeneral.value && sindicatoSeleccionado.value
            ? sindicatoSeleccionado.value
            : undefined,
        per_page: 100
      }
    })
    mototaxistas.value = Array.isArray(respuesta.data?.data)
      ? respuesta.data.data
      : []
  } finally {
    cargandoMototaxistas.value = false
  }
}

function filtrarMototaxistas(valor, actualizar) {
  actualizar(() => {
    cargarMototaxistas(valor)
  })
}

async function previsualizar(reporte) {
  try {
    const respuesta = await api.get('/reportes-suscripcion-motrix/preview', {
      params: {
        tipo: reporte.tipo,
        ...paramsReporte(reporte)
      }
    })
    preview.value = respuesta.data?.data || preview.value
    dialogoPreview.value = true
  } catch (error) {
    notificarError(error)
  }
}

async function descargar(reporte, formato) {
  try {
    const respuesta = await api.get(
      `/reportes-suscripcion-motrix/${reporte.tipo}/${formato}`,
      {
        params: paramsReporte(reporte),
        responseType: 'blob'
      }
    )

    const extension = formato === 'pdf' ? 'pdf' : 'xlsx'
    descargarBlob(
      respuesta.data,
      `MOTRIX_${reporte.tipo}_${periodo.value}.${extension}`
    )
  } catch (error) {
    notificarError(error)
  }
}

function descargarBlob(blob, nombre) {
  const url = URL.createObjectURL(blob)
  const enlace = document.createElement('a')
  enlace.href = url
  enlace.download = nombre
  document.body.appendChild(enlace)
  enlace.click()
  enlace.remove()
  URL.revokeObjectURL(url)
}

function nombreMototaxista(item) {
  const persona = item?.mototaxista?.persona || {}
  const nombre = `${persona.nombre || ''} ${persona.apellidos || ''}`.trim()
  const chaleco = item?.mototaxista?.nro_chaleco
  return `${nombre || `Mototaxista #${item.id_mototaxista}`}${chaleco ? ` · Chaleco ${chaleco}` : ''}`
}

function iconoReporte(tipo) {
  return {
    suscripciones: 'workspace_premium',
    pendientes: 'warning_amber',
    recaudacion: 'payments',
    liquidaciones: 'account_balance',
    historial: 'history',
    consolidado: 'analytics'
  }[tipo] || 'description'
}

function etiquetaTotal(clave) {
  return String(clave || '')
    .replaceAll('_', ' ')
    .replace(/^./, letra => letra.toUpperCase())
}

function notificarError(error) {
  console.error('Error en reportes MOTRIX:', error)
  $q.notify({
    type: 'negative',
    message:
      error?.response?.data?.message
      || error?.response?.data?.mensaje
      || 'No se pudo generar el reporte.',
    position: 'top'
  })
}

watch(sindicatoSeleccionado, () => {
  mototaxistaSeleccionado.value = null
  cargarMototaxistas()
})

onMounted(() => {
  cargarInicial()
})
</script>

<style scoped>
.report-card {
  border-radius: 14px;
  background: #fff;
}
</style>
