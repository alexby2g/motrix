<template>
  <q-page class="q-pa-md q-pa-lg-lg">
    <div class="page-shell">
      <div class="row items-center q-col-gutter-sm q-mb-lg">
        <div class="col">
          <div class="row items-center no-wrap">
            <q-avatar color="green-1" text-color="green-9" icon="account_tree" size="48px" class="q-mr-md" />
            <div>
              <div class="text-h5 text-weight-bold text-green-9">Federaciones</div>
              <div class="text-caption text-grey-7">Organización superior de los sindicatos registrados en MOTRIX.</div>
            </div>
          </div>
        </div>

        <div class="col-auto">
          <q-btn color="green-8" icon="add" label="Nueva federación" unelevated no-caps @click="abrirCrear" />
        </div>
      </div>

      <div class="row q-col-gutter-md q-mb-lg">
        <div class="col-12 col-sm-6">
          <q-card flat bordered class="stat-card">
            <q-card-section class="row items-center">
              <q-avatar color="green-1" text-color="green-9" icon="account_tree" size="44px" class="q-mr-md" />
              <div>
                <div class="text-caption text-grey-7">Federaciones registradas</div>
                <div class="text-h5 text-weight-bold text-green-9">{{ federaciones.length }}</div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-sm-6">
          <q-card flat bordered class="stat-card">
            <q-card-section class="row items-center">
              <q-avatar color="blue-1" text-color="blue-9" icon="groups" size="44px" class="q-mr-md" />
              <div>
                <div class="text-caption text-grey-7">Sindicatos afiliados</div>
                <div class="text-h5 text-weight-bold text-blue-9">{{ totalSindicatos }}</div>
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <q-linear-progress v-if="cargando" indeterminate color="green-8" class="q-mb-md" />

      <div v-if="!cargando && federaciones.length" class="row q-col-gutter-md">
        <div v-for="federacion in federaciones" :key="federacion.id" class="col-12 col-sm-6 col-md-4 col-lg-3">
          <q-card flat bordered class="federacion-card full-height">
            <q-card-section class="row items-start no-wrap">
              <q-avatar size="64px" color="green-1" text-color="green-8" class="q-mr-md">
                <img
                  v-if="federacion.logo && !logosFallidos.has(federacion.id)"
                  :src="archivoPublico(federacion.logo)"
                  alt="Logo de federación"
                  @error="marcarLogoFallido(federacion.id)"
                >
                <q-icon v-else name="account_tree" size="34px" />
              </q-avatar>

              <div class="col min-width-zero">
                <div class="text-subtitle1 text-weight-bold text-green-9 ellipsis-2-lines">
                  {{ federacion.nombre }}
                </div>

                <q-chip dense outline color="green-8" icon="groups" class="q-mt-sm">
                  {{ federacion.sindicatos_count || 0 }} sindicato{{ Number(federacion.sindicatos_count || 0) === 1 ? '' : 's' }}
                </q-chip>
              </div>

              <q-btn flat round dense icon="more_vert" color="grey-7">
                <q-menu>
                  <q-list style="min-width: 175px">
                    <q-item clickable v-close-popup @click="irSindicatos(federacion)">
                      <q-item-section avatar><q-icon name="groups" color="green-8" /></q-item-section>
                      <q-item-section>Ver sindicatos</q-item-section>
                    </q-item>
                    <q-item clickable v-close-popup @click="abrirEditar(federacion)">
                      <q-item-section avatar><q-icon name="edit" color="blue-8" /></q-item-section>
                      <q-item-section>Editar</q-item-section>
                    </q-item>
                    <q-item clickable v-close-popup @click="abrirLogo(federacion)">
                      <q-item-section avatar><q-icon name="image" color="purple-7" /></q-item-section>
                      <q-item-section>Cambiar logo</q-item-section>
                    </q-item>
                    <q-separator />
                    <q-item clickable v-close-popup @click="confirmarEliminar(federacion)">
                      <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                      <q-item-section class="text-negative">Eliminar</q-item-section>
                    </q-item>
                  </q-list>
                </q-menu>
              </q-btn>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <q-card v-else-if="!cargando" flat bordered class="empty-card">
        <q-card-section class="column items-center q-pa-xl text-grey-7">
          <q-icon name="account_tree" size="54px" color="green-4" />
          <div class="text-subtitle1 text-weight-bold q-mt-sm">Todavía no hay federaciones</div>
          <q-btn color="green-8" icon="add" label="Registrar primera federación" unelevated no-caps class="q-mt-md" @click="abrirCrear" />
        </q-card-section>
      </q-card>
    </div>

    <q-dialog v-model="dialogoForm">
      <q-card class="dialog-card">
        <q-card-section class="dialog-header row items-center">
          <q-avatar color="green-1" text-color="green-9" icon="account_tree" class="q-mr-md" />
          <div class="col">
            <div class="text-h6 text-weight-bold">{{ editando ? 'Editar federación' : 'Nueva federación' }}</div>
            <div class="text-caption text-grey-6">Información general de la federación.</div>
          </div>
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>

        <q-separator />

        <q-card-section>
          <q-input v-model.trim="form.nombre" outlined label="Nombre de la federación *" maxlength="150" autofocus :error="Boolean(errorNombre)" :error-message="errorNombre">
            <template #prepend><q-icon name="account_tree" color="green-8" /></template>
          </q-input>

          <q-file
            v-if="!editando"
            v-model="form.logo"
            outlined
            label="Logo (opcional)"
            accept=".jpg,.jpeg,.png,.webp"
            max-file-size="2097152"
            class="q-mt-md"
            @rejected="archivoRechazado"
          >
            <template #prepend><q-icon name="image" color="green-8" /></template>
          </q-file>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md bg-grey-1">
          <q-btn flat label="Cancelar" color="grey-7" no-caps v-close-popup />
          <q-btn color="green-8" :label="editando ? 'Guardar cambios' : 'Crear federación'" icon="save" unelevated no-caps :loading="guardando" @click="guardar" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="dialogoLogo">
      <q-card class="dialog-card">
        <q-card-section class="dialog-header row items-center">
          <q-icon name="image" color="green-8" size="30px" class="q-mr-md" />
          <div class="col">
            <div class="text-h6 text-weight-bold">Cambiar logo</div>
            <div class="text-caption text-grey-6">{{ federacionLogo?.nombre }}</div>
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
import { useRouter } from 'vue-router'
import { api } from 'src/boot/axios.js'

const $q = useQuasar()
const router = useRouter()

const cargando = ref(false)
const guardando = ref(false)
const subiendoLogo = ref(false)
const federaciones = ref([])
const logosFallidos = ref(new Set())

const dialogoForm = ref(false)
const dialogoLogo = ref(false)
const editando = ref(null)
const federacionLogo = ref(null)
const logoNuevo = ref(null)
const errorNombre = ref('')

const form = reactive({ nombre: '', logo: null })

const totalSindicatos = computed(() => federaciones.value.reduce((total, item) => total + Number(item.sindicatos_count || 0), 0))

function origenApi() {
  return String(api.defaults.baseURL || '').replace(/\/api\/?$/, '')
}

function archivoPublico(ruta) {
  return `${origenApi()}/storage/${ruta}`
}

function marcarLogoFallido(id) {
  const nuevo = new Set(logosFallidos.value)
  nuevo.add(id)
  logosFallidos.value = nuevo
}

function mensajeError(error, defecto) {
  const errors = error.response?.data?.errors
  if (errors) return Object.values(errors).flat().find(Boolean) || defecto
  return error.response?.data?.message || defecto
}

async function cargarFederaciones() {
  cargando.value = true
  try {
    const { data } = await api.get('/federaciones')
    federaciones.value = Array.isArray(data) ? data : []
  } catch (error) {
    $q.notify({ type: 'negative', position: 'top', message: mensajeError(error, 'No se pudieron cargar las federaciones.') })
  } finally {
    cargando.value = false
  }
}

function abrirCrear() {
  editando.value = null
  form.nombre = ''
  form.logo = null
  errorNombre.value = ''
  dialogoForm.value = true
}

function abrirEditar(federacion) {
  editando.value = federacion
  form.nombre = federacion.nombre || ''
  form.logo = null
  errorNombre.value = ''
  dialogoForm.value = true
}

async function guardar() {
  errorNombre.value = ''
  if (form.nombre.trim().length < 3) {
    errorNombre.value = 'Escribe un nombre de al menos 3 caracteres.'
    return
  }

  guardando.value = true
  try {
    let id
    if (editando.value) {
      await api.put(`/federaciones/${editando.value.id}`, { nombre: form.nombre.trim() })
      id = editando.value.id
    } else {
      const { data } = await api.post('/federaciones', { nombre: form.nombre.trim() })
      id = data?.data?.id
    }

    if (!editando.value && form.logo && id) {
      await enviarLogo(id, form.logo)
    }

    $q.notify({ type: 'positive', position: 'top', icon: 'check_circle', message: editando.value ? 'Federación actualizada.' : 'Federación registrada.' })
    dialogoForm.value = false
    await cargarFederaciones()
  } catch (error) {
    const msg = mensajeError(error, 'No se pudo guardar la federación.')
    errorNombre.value = error.response?.status === 422 ? msg : ''
    if (!errorNombre.value) $q.notify({ type: 'negative', position: 'top', message: msg })
  } finally {
    guardando.value = false
  }
}

function abrirLogo(federacion) {
  federacionLogo.value = federacion
  logoNuevo.value = null
  dialogoLogo.value = true
}

async function enviarLogo(id, archivo) {
  const fd = new FormData()
  fd.append('logo', archivo)
  return api.post(`/federaciones/${id}/logo`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
}

async function subirLogo() {
  if (!federacionLogo.value || !logoNuevo.value) return
  subiendoLogo.value = true
  try {
    await enviarLogo(federacionLogo.value.id, logoNuevo.value)
    $q.notify({ type: 'positive', position: 'top', message: 'Logo actualizado correctamente.' })
    dialogoLogo.value = false
    await cargarFederaciones()
  } catch (error) {
    $q.notify({ type: 'negative', position: 'top', message: mensajeError(error, 'No se pudo subir el logo.') })
  } finally {
    subiendoLogo.value = false
  }
}

function archivoRechazado() {
  $q.notify({ type: 'negative', position: 'top', message: 'Usa una imagen JPG, PNG o WEBP de máximo 2 MB.' })
}

function confirmarEliminar(federacion) {
  $q.dialog({
    title: 'Eliminar federación',
    message: `¿Deseas eliminar “${federacion.nombre}”?`,
    persistent: true,
    ok: { label: 'Eliminar', color: 'negative' },
    cancel: { label: 'Cancelar', flat: true }
  }).onOk(async () => {
    try {
      await api.delete(`/federaciones/${federacion.id}`)
      $q.notify({ type: 'positive', position: 'top', message: 'Federación eliminada.' })
      await cargarFederaciones()
    } catch (error) {
      $q.notify({ type: 'negative', position: 'top', multiLine: true, message: mensajeError(error, 'No se pudo eliminar la federación.') })
    }
  })
}

function irSindicatos(federacion) {
  router.push({ path: '/sindicatos', query: { federacion: federacion.id } })
}

onMounted(cargarFederaciones)
</script>

<style scoped>
.page-shell { max-width: 1280px; margin: 0 auto; }
.stat-card, .federacion-card, .empty-card { border-radius: 14px; }
.stat-card { border-left: 4px solid #2e7d32; }
.federacion-card { transition: transform .18s ease, box-shadow .18s ease; }
.federacion-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(27,94,32,.12); }
.dialog-card { width: min(520px, 94vw); border-top: 4px solid #2e7d32; border-radius: 14px; }
.dialog-header { background: #f1f8e9; }
.min-width-zero { min-width: 0; }
.ellipsis-2-lines { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>
