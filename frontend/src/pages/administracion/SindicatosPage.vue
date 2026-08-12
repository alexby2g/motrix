<template>
  <q-page class="q-pa-md q-pa-lg-lg">
    <div class="page-shell">
      <div class="row items-center q-col-gutter-sm q-mb-lg">
        <div class="col">
          <div class="row items-center no-wrap">
            <q-avatar color="green-1" text-color="green-9" icon="groups" size="48px" class="q-mr-md" />
            <div>
              <div class="text-h5 text-weight-bold text-green-9">
                {{ esSecretario ? 'Mi sindicato' : 'Sindicatos' }}
              </div>
              <div class="text-caption text-grey-7">
                {{
                  esSecretario
                    ? 'Consulta y actualización de los datos de tu sindicato.'
                    : 'Registro de sindicatos y relación con sus federaciones.'
                }}
              </div>
            </div>
          </div>
        </div>
        <div class="col-auto">
          <q-btn v-if="!esSecretario" color="green-8" icon="add" label="Nuevo sindicato" unelevated no-caps @click="abrirCrear" />
        </div>
      </div>

      <q-card flat bordered class="filters-card q-mb-lg">
        <q-card-section class="row q-col-gutter-md items-center">
          <div class="col-12 col-md-5">
            <q-input v-model="busqueda" outlined dense clearable debounce="250" placeholder="Buscar sindicato o dirección">
              <template #prepend><q-icon name="search" color="green-8" /></template>
            </q-input>
          </div>
          <div class="col-12 col-md-4">
            <q-select v-model="filtroFederacion" :options="opcionesFederacionFiltro" outlined dense clearable emit-value map-options label="Federación" />
          </div>
          <div class="col-12 col-md-3 text-right">
            <q-chip color="green-1" text-color="green-9" icon="groups">{{ sindicatosFiltrados.length }} de {{ sindicatos.length }}</q-chip>
          </div>
        </q-card-section>
      </q-card>

      <q-linear-progress v-if="cargando" indeterminate color="green-8" class="q-mb-md" />

      <div v-if="!cargando && sindicatosFiltrados.length" class="row q-col-gutter-md">
        <div v-for="sindicato in sindicatosFiltrados" :key="sindicato.id" class="col-12 col-sm-6 col-lg-4">
          <q-card flat bordered class="sindicato-card full-height">
            <q-card-section class="row no-wrap items-start">
              <q-avatar size="62px" color="green-1" text-color="green-8" class="q-mr-md">
                <img
                  v-if="sindicato.logo && !logosFallidos.has(sindicato.id)"
                  :src="archivoPublico(sindicato.logo)"
                  alt="Logo de sindicato"
                  @error="marcarLogoFallido(sindicato.id)"
                >
                <q-icon v-else name="groups" size="32px" />
              </q-avatar>

              <div class="col min-width-zero">
                <div class="text-subtitle1 text-weight-bold text-green-9 ellipsis">{{ sindicato.nombre }}</div>
                <div class="text-caption text-grey-7 q-mt-xs">
                  <q-icon name="account_tree" size="14px" class="q-mr-xs" />
                  {{ sindicato.federacion?.nombre || 'Sin federación' }}
                </div>
                <div class="text-caption text-grey-7 q-mt-xs ellipsis">
                  <q-icon name="place" size="14px" class="q-mr-xs" />
                  {{ sindicato.direccion || 'Sin dirección' }}
                </div>
                <div class="text-caption text-grey-7 q-mt-xs">
                  <q-icon name="calendar_month" size="14px" class="q-mr-xs" />
                  {{ sindicato.fecha_creacion || 'Sin fecha registrada' }}
                </div>
              </div>
            </q-card-section>

            <q-separator />

            <q-card-section class="row items-center q-py-sm">
              <q-chip dense color="blue-1" text-color="blue-9" icon="two_wheeler">
                {{ sindicato.mototaxistas_count || 0 }} afiliado{{ Number(sindicato.mototaxistas_count || 0) === 1 ? '' : 's' }}
              </q-chip>
              <q-space />
              <q-btn flat round dense icon="edit" color="green-8" @click="abrirEditar(sindicato)"><q-tooltip>Editar</q-tooltip></q-btn>
              <q-btn flat round dense icon="image" color="purple-7" @click="abrirLogo(sindicato)"><q-tooltip>Cambiar logo</q-tooltip></q-btn>
              <q-btn v-if="!esSecretario" flat round dense icon="delete" color="negative" @click="confirmarEliminar(sindicato)"><q-tooltip>Eliminar</q-tooltip></q-btn>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <q-card v-else-if="!cargando" flat bordered>
        <q-card-section class="column items-center q-pa-xl text-grey-7">
          <q-icon name="search_off" size="52px" color="green-4" />
          <div class="text-subtitle1 text-weight-bold q-mt-sm">No se encontraron sindicatos</div>
        </q-card-section>
      </q-card>
    </div>

    <q-dialog v-model="dialogoForm">
      <q-card class="dialog-card">
        <q-card-section class="dialog-header row items-center">
          <q-avatar color="green-1" text-color="green-9" icon="groups" class="q-mr-md" />
          <div class="col">
            <div class="text-h6 text-weight-bold">{{ editando ? 'Editar sindicato' : 'Nuevo sindicato' }}</div>
            <div class="text-caption text-grey-6">Datos de identificación y afiliación.</div>
          </div>
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>

        <q-separator />

        <q-card-section class="q-gutter-md">
          <q-input v-model.trim="form.nombre" outlined label="Nombre *" maxlength="100" :readonly="esSecretario" />
          <q-select v-model="form.id_federacion" :options="opcionesFederaciones" outlined clearable emit-value map-options label="Federación" :readonly="esSecretario" />
          <q-input v-model="form.fecha_creacion" outlined label="Fecha de creación" type="date" />
          <q-input v-model.trim="form.direccion" outlined label="Dirección" maxlength="255" />
          <q-file
            v-if="!editando"
            v-model="form.logo"
            outlined
            label="Logo (opcional)"
            accept=".jpg,.jpeg,.png,.webp"
            max-file-size="2097152"
            @rejected="archivoRechazado"
          >
            <template #prepend><q-icon name="image" color="green-8" /></template>
          </q-file>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md bg-grey-1">
          <q-btn flat label="Cancelar" color="grey-7" no-caps v-close-popup />
          <q-btn color="green-8" :label="editando ? 'Guardar cambios' : 'Registrar sindicato'" icon="save" unelevated no-caps :loading="guardando" @click="guardar" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="dialogoLogo">
      <q-card class="dialog-card">
        <q-card-section class="dialog-header row items-center">
          <q-icon name="image" color="green-8" size="30px" class="q-mr-md" />
          <div class="col">
            <div class="text-h6 text-weight-bold">Cambiar logo</div>
            <div class="text-caption text-grey-6">{{ sindicatoLogo?.nombre }}</div>
          </div>
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-card-section>
          <q-file v-model="logoNuevo" outlined label="Seleccionar imagen *" accept=".jpg,.jpeg,.png,.webp" max-file-size="2097152" @rejected="archivoRechazado">
            <template #prepend><q-icon name="photo_camera" color="green-8" /></template>
          </q-file>
        </q-card-section>
        <q-card-actions align="right" class="q-pa-md bg-grey-1">
          <q-btn flat label="Cancelar" color="grey-7" no-caps v-close-popup />
          <q-btn color="green-8" label="Subir logo" icon="cloud_upload" unelevated no-caps :disable="!logoNuevo" :loading="subiendoLogo" @click="subirLogo" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useQuasar } from 'quasar'
import { useRoute } from 'vue-router'
import { api } from 'src/boot/axios.js'

const $q = useQuasar()
const route = useRoute()

function leerUsuarioActual() {
  try {
    return JSON.parse(
      localStorage.getItem('motrix_user') || 'null'
    )
  } catch {
    return null
  }
}

const usuarioActual = leerUsuarioActual()
const esSecretario =
  String(usuarioActual?.role || '').toLowerCase()
  === 'secretario'

const cargando = ref(false)
const guardando = ref(false)
const subiendoLogo = ref(false)
const sindicatos = ref([])
const federaciones = ref([])
const busqueda = ref('')
const filtroFederacion = ref(null)
const logosFallidos = ref(new Set())

const dialogoForm = ref(false)
const dialogoLogo = ref(false)
const editando = ref(null)
const sindicatoLogo = ref(null)
const logoNuevo = ref(null)

const form = reactive({ nombre: '', id_federacion: null, fecha_creacion: '', direccion: '', logo: null })

const opcionesFederaciones = computed(() => federaciones.value.map(item => ({ label: item.nombre, value: item.id })))
const opcionesFederacionFiltro = computed(() => [{ label: 'Todas las federaciones', value: null }, ...opcionesFederaciones.value])

const sindicatosFiltrados = computed(() => {
  const texto = String(busqueda.value || '').trim().toLowerCase()
  return sindicatos.value.filter(item => {
    const coincideFederacion = !filtroFederacion.value || Number(item.id_federacion) === Number(filtroFederacion.value)
    const coincideTexto = !texto || String(item.nombre || '').toLowerCase().includes(texto) || String(item.direccion || '').toLowerCase().includes(texto)
    return coincideFederacion && coincideTexto
  })
})

function origenApi() { return String(api.defaults.baseURL || '').replace(/\/api\/?$/, '') }
function archivoPublico(ruta) { return `${origenApi()}/storage/${ruta}` }
function marcarLogoFallido(id) { const nuevo = new Set(logosFallidos.value); nuevo.add(id); logosFallidos.value = nuevo }
function mensajeError(error, defecto) { const errors = error.response?.data?.errors; if (errors) return Object.values(errors).flat().find(Boolean) || defecto; return error.response?.data?.message || defecto }

async function cargar() {
  cargando.value = true
  try {
    const [respSind, respFed] = await Promise.all([api.get('/sindicatos'), api.get('/federaciones')])
    sindicatos.value = Array.isArray(respSind.data) ? respSind.data : []
    federaciones.value = Array.isArray(respFed.data) ? respFed.data : []
  } catch (error) {
    $q.notify({ type: 'negative', position: 'top', message: mensajeError(error, 'No se pudieron cargar los sindicatos.') })
  } finally {
    cargando.value = false
  }
}

function limpiarForm() {
  form.nombre = ''
  form.id_federacion = null
  form.fecha_creacion = ''
  form.direccion = ''
  form.logo = null
}

function abrirCrear() {
  if (esSecretario) {
    return
  }

  editando.value = null
  limpiarForm()
  dialogoForm.value = true
}
function abrirEditar(item) {
  editando.value = item
  form.nombre = item.nombre || ''
  form.id_federacion = item.id_federacion || null
  form.fecha_creacion = item.fecha_creacion || ''
  form.direccion = item.direccion || ''
  form.logo = null
  dialogoForm.value = true
}

async function guardar() {
  if (form.nombre.trim().length < 3) {
    $q.notify({ type: 'warning', position: 'top', message: 'Escribe el nombre del sindicato.' })
    return
  }
  guardando.value = true
  try {
    const payload = {
      nombre: esSecretario && editando.value
        ? editando.value.nombre
        : form.nombre.trim(),
      id_federacion: esSecretario && editando.value
        ? (editando.value.id_federacion || null)
        : (form.id_federacion || null),
      fecha_creacion: form.fecha_creacion || null,
      direccion: form.direccion.trim() || null
    }
    let id
    if (editando.value) {
      await api.put(`/sindicatos/${editando.value.id}`, payload)
      id = editando.value.id
    } else {
      const { data } = await api.post('/sindicatos', payload)
      id = data?.data?.id
    }
    if (!editando.value && form.logo && id) await enviarLogo(id, form.logo)
    $q.notify({ type: 'positive', position: 'top', message: editando.value ? 'Sindicato actualizado.' : 'Sindicato registrado.' })
    dialogoForm.value = false
    await cargar()
  } catch (error) {
    $q.notify({ type: 'negative', position: 'top', multiLine: true, message: mensajeError(error, 'No se pudo guardar el sindicato.') })
  } finally {
    guardando.value = false
  }
}

function abrirLogo(item) { sindicatoLogo.value = item; logoNuevo.value = null; dialogoLogo.value = true }
async function enviarLogo(id, archivo) { const fd = new FormData(); fd.append('logo', archivo); return api.post(`/sindicatos/${id}/logo`, fd, { headers: { 'Content-Type': 'multipart/form-data' } }) }
async function subirLogo() {
  if (!sindicatoLogo.value || !logoNuevo.value) return
  subiendoLogo.value = true
  try {
    await enviarLogo(sindicatoLogo.value.id, logoNuevo.value)
    $q.notify({ type: 'positive', position: 'top', message: 'Logo actualizado correctamente.' })
    dialogoLogo.value = false
    await cargar()
  } catch (error) {
    $q.notify({ type: 'negative', position: 'top', message: mensajeError(error, 'No se pudo subir el logo.') })
  } finally { subiendoLogo.value = false }
}

function archivoRechazado() { $q.notify({ type: 'negative', position: 'top', message: 'Usa una imagen JPG, PNG o WEBP de máximo 2 MB.' }) }

function confirmarEliminar(item) {
  if (esSecretario) {
    return
  }

  $q.dialog({ title: 'Eliminar sindicato', message: `¿Deseas eliminar “${item.nombre}”?`, cancel: { label: 'Cancelar', flat: true }, ok: { label: 'Eliminar', color: 'negative' }, persistent: true }).onOk(async () => {
    try {
      await api.delete(`/sindicatos/${item.id}`)
      $q.notify({ type: 'positive', position: 'top', message: 'Sindicato eliminado.' })
      await cargar()
    } catch (error) {
      $q.notify({ type: 'negative', position: 'top', multiLine: true, message: mensajeError(error, 'No se pudo eliminar el sindicato.') })
    }
  })
}

onMounted(async () => {
  const idFederacion = Number(route.query.federacion || 0)
  if (idFederacion) filtroFederacion.value = idFederacion
  await cargar()
})
</script>

<style scoped>
.page-shell { max-width: 1280px; margin: 0 auto; }
.filters-card, .sindicato-card { border-radius: 14px; }
.sindicato-card { border-left: 4px solid #2e7d32; transition: transform .18s ease, box-shadow .18s ease; }
.sindicato-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(27,94,32,.12); }
.dialog-card { width: min(580px, 95vw); border-top: 4px solid #2e7d32; border-radius: 14px; }
.dialog-header { background: #f1f8e9; }
.min-width-zero { min-width: 0; }
</style>
