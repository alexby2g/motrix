<template>
  <q-page class="q-pa-md q-pa-lg-lg">
    <div class="page-shell">
      <div class="row items-center q-col-gutter-sm q-mb-lg">
        <div class="col">
          <div class="row items-center no-wrap">
            <q-avatar color="green-1" text-color="green-9" icon="manage_accounts" size="48px" class="q-mr-md" />
            <div>
              <div class="text-h5 text-weight-bold text-green-9">Usuarios administrativos</div>
              <div class="text-caption text-grey-7">Cuentas internas para administración general, servicios y secretaría.</div>
            </div>
          </div>
        </div>
        <div class="col-auto">
          <q-btn color="green-8" icon="person_add" label="Nuevo usuario" unelevated no-caps @click="abrirCrear" />
        </div>
      </div>

      <div class="row q-col-gutter-md q-mb-lg">
        <div v-for="resumen in resumenRoles" :key="resumen.role" class="col-12 col-sm-4">
          <q-card flat bordered class="stat-card cursor-pointer" :class="{ 'stat-card-active': filtroRol === resumen.role }" @click="alternarRol(resumen.role)">
            <q-card-section class="row items-center">
              <q-avatar :color="resumen.fondo" :text-color="resumen.texto" :icon="resumen.icono" size="44px" class="q-mr-md" />
              <div>
                <div class="text-caption text-grey-7">{{ resumen.etiqueta }}</div>
                <div class="text-h5 text-weight-bold" :class="`text-${resumen.texto}`">{{ totalRol(resumen.role) }}</div>
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <q-card flat bordered class="q-mb-md filters-card">
        <q-card-section class="row q-col-gutter-md items-center">
          <div class="col-12 col-md-7">
            <q-input v-model="busqueda" outlined dense clearable debounce="250" placeholder="Buscar por nombre, nickname o correo">
              <template #prepend><q-icon name="search" color="green-8" /></template>
            </q-input>
          </div>
          <div class="col-12 col-md-5 text-right">
            <q-chip v-if="filtroRol" removable color="green-1" text-color="green-9" icon="filter_alt" @remove="filtroRol = null">{{ etiquetaRol(filtroRol) }}</q-chip>
            <q-chip color="grey-2" text-color="grey-8">{{ usuariosFiltrados.length }} usuario{{ usuariosFiltrados.length === 1 ? '' : 's' }}</q-chip>
          </div>
        </q-card-section>
      </q-card>

      <q-card flat bordered class="lista-card">
        <q-linear-progress v-if="cargando" indeterminate color="green-8" />

        <q-list v-if="usuariosFiltrados.length" separator>
          <q-item v-for="usuario in usuariosFiltrados" :key="usuario.id" class="q-py-md">
            <q-item-section avatar>
              <q-avatar :color="configRol(usuario.role).fondo" :text-color="configRol(usuario.role).texto">
                {{ iniciales(usuario) }}
              </q-avatar>
            </q-item-section>

            <q-item-section>
              <q-item-label class="text-weight-bold">{{ nombreUsuario(usuario) }}</q-item-label>
              <q-item-label caption>
                <q-icon name="alternate_email" size="13px" /> {{ usuario.nickname || 'Sin nickname' }}
                <span class="q-mx-xs">·</span>
                <q-icon name="mail" size="13px" /> {{ usuario.email }}
              </q-item-label>
            </q-item-section>

            <q-item-section side class="gt-xs">
              <q-chip dense :color="configRol(usuario.role).chip" text-color="white" :icon="configRol(usuario.role).icono">{{ etiquetaRol(usuario.role) }}</q-chip>
            </q-item-section>

            <q-item-section side>
              <q-btn flat round dense icon="more_vert" color="grey-7">
                <q-menu>
                  <q-list style="min-width: 160px">
                    <q-item clickable v-close-popup @click="abrirEditar(usuario)">
                      <q-item-section avatar><q-icon name="edit" color="green-8" /></q-item-section>
                      <q-item-section>Editar</q-item-section>
                    </q-item>
                    <q-separator />
                    <q-item clickable v-close-popup @click="confirmarEliminar(usuario)">
                      <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                      <q-item-section class="text-negative">Eliminar</q-item-section>
                    </q-item>
                  </q-list>
                </q-menu>
              </q-btn>
            </q-item-section>
          </q-item>
        </q-list>

        <q-card-section v-else-if="!cargando" class="column items-center q-pa-xl text-grey-7">
          <q-icon name="manage_accounts" size="54px" color="green-4" />
          <div class="text-subtitle1 text-weight-bold q-mt-sm">No se encontraron usuarios administrativos</div>
        </q-card-section>
      </q-card>
    </div>

    <q-dialog v-model="dialogoForm">
      <q-card class="dialog-card">
        <q-card-section class="dialog-header row items-center">
          <q-avatar color="green-1" text-color="green-9" icon="manage_accounts" class="q-mr-md" />
          <div class="col">
            <div class="text-h6 text-weight-bold">{{ editando ? 'Editar usuario' : 'Nuevo usuario administrativo' }}</div>
            <div class="text-caption text-grey-6">Cuenta de acceso al sistema integrado.</div>
          </div>
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>

        <q-separator />

        <q-card-section class="q-gutter-md">
          <q-select
            v-model="form.persona_id"
            :options="opcionesPersonas"
            outlined
            emit-value
            map-options
            use-input
            input-debounce="0"
            label="Persona *"
            @filter="filtrarPersonas"
          >
            <template #prepend><q-icon name="person" color="green-8" /></template>
          </q-select>

          <div class="row q-col-gutter-md">
            <div class="col-12 col-sm-6">
              <q-input v-model.trim="form.nickname" outlined label="Nickname *" maxlength="50" autocomplete="off">
                <template #prepend><q-icon name="alternate_email" color="green-8" /></template>
              </q-input>
            </div>
            <div class="col-12 col-sm-6">
              <q-input v-model.trim="form.email" outlined label="Correo (opcional)" maxlength="150" type="email">
                <template #prepend><q-icon name="mail" color="green-8" /></template>
              </q-input>
            </div>
          </div>

          <q-input v-model="form.password" outlined :label="editando ? 'Nueva contraseña (dejar vacía para conservar)' : 'Contraseña *'" :type="mostrarPassword ? 'text' : 'password'" autocomplete="new-password">
            <template #prepend><q-icon name="lock" color="green-8" /></template>
            <template #append><q-icon :name="mostrarPassword ? 'visibility_off' : 'visibility'" class="cursor-pointer" @click="mostrarPassword = !mostrarPassword" /></template>
          </q-input>

          <q-select v-model="form.role" :options="opcionesRoles" outlined emit-value map-options label="Rol *">
            <template #prepend><q-icon name="admin_panel_settings" color="green-8" /></template>
          </q-select>

          <div
            v-if="form.role === 'secretario'"
            class="row q-col-gutter-md"
          >
            <div class="col-12 col-sm-6">
              <q-select
                v-model="form.federacion_id"
                :options="opcionesFederaciones"
                outlined
                clearable
                emit-value
                map-options
                label="Federación"
              />
            </div>

            <div class="col-12 col-sm-6">
              <q-select
                v-model="form.sindicato_id"
                :options="opcionesSindicatosFiltrados"
                outlined
                emit-value
                map-options
                label="Sindicato *"
                :rules="[
                  valor => !!valor || 'Selecciona el sindicato del secretario.'
                ]"
              />
            </div>
          </div>

          <q-banner
            v-else
            rounded
            dense
            class="bg-green-1 text-green-10"
          >
            <template #avatar>
              <q-icon
                name="verified_user"
                color="green-8"
              />
            </template>

            Este rol trabaja a nivel general del sistema y no queda
            vinculado a un sindicato específico.
          </q-banner>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md bg-grey-1">
          <q-btn flat label="Cancelar" color="grey-7" no-caps v-close-popup />
          <q-btn color="green-8" :label="editando ? 'Guardar cambios' : 'Crear usuario'" icon="save" unelevated no-caps :loading="guardando" @click="guardar" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useQuasar } from 'quasar'
import { api } from 'src/boot/axios.js'

const $q = useQuasar()
const cargando = ref(false)
const guardando = ref(false)
const usuarios = ref([])
const personas = ref([])
const personasFiltradas = ref([])
const federaciones = ref([])
const sindicatos = ref([])
const busqueda = ref('')
const filtroRol = ref(null)
const dialogoForm = ref(false)
const editando = ref(null)
const mostrarPassword = ref(false)

const form = reactive({ persona_id: null, nickname: '', email: '', password: '', role: 'admin_general', federacion_id: null, sindicato_id: null })

const resumenRoles = [
  {
    role: 'admin_general',
    etiqueta: 'Administradores generales',
    icono: 'admin_panel_settings',
    fondo: 'green-1',
    texto: 'green-9'
  },
  {
    role: 'admin_servicios',
    etiqueta: 'Administradores de servicios',
    icono: 'support_agent',
    fondo: 'blue-1',
    texto: 'blue-9'
  },
  {
    role: 'secretario',
    etiqueta: 'Secretarios',
    icono: 'edit_note',
    fondo: 'purple-1',
    texto: 'purple-9'
  }
]

const opcionesRoles = [
  {
    label: 'Administrador general',
    value: 'admin_general'
  },
  {
    label: 'Administrador de servicios',
    value: 'admin_servicios'
  },
  {
    label: 'Secretario de sindicato',
    value: 'secretario'
  }
]

const opcionesPersonas = computed(() => personasFiltradas.value.map(p => ({ label: `${p.nombre || ''} ${p.apellidos || ''} · CI ${p.ci || '—'}`.trim(), value: p.id })))
const opcionesFederaciones = computed(() => federaciones.value.map(f => ({ label: f.nombre, value: f.id })))
const opcionesSindicatosFiltrados = computed(() => sindicatos.value.filter(s => !form.federacion_id || Number(s.id_federacion) === Number(form.federacion_id)).map(s => ({ label: s.nombre, value: s.id })))

const usuariosFiltrados = computed(() => {
  const texto = String(busqueda.value || '').trim().toLowerCase()
  return usuarios.value.filter(u => {
    if (filtroRol.value && u.role !== filtroRol.value) return false
    if (!texto) return true
    return [nombreUsuario(u), u.nickname, u.email].some(valor => String(valor || '').toLowerCase().includes(texto))
  })
})

watch(() => form.federacion_id, () => {
  if (form.sindicato_id && !sindicatos.value.some(s => Number(s.id) === Number(form.sindicato_id) && (!form.federacion_id || Number(s.id_federacion) === Number(form.federacion_id)))) {
    form.sindicato_id = null
  }
})

watch(
  () => form.role,
  (nuevoRol) => {
    if (nuevoRol !== 'secretario') {
      form.federacion_id = null
      form.sindicato_id = null
    }
  }
)

function nombreUsuario(u) { return `${u.persona?.nombre || u.name || ''} ${u.persona?.apellidos || ''}`.trim() || u.nickname || 'Usuario' }
function iniciales(u) { const n = nombreUsuario(u).split(/\s+/).filter(Boolean); return ((n[0]?.[0] || '') + (n[1]?.[0] || '')).toUpperCase() || 'U' }
function etiquetaRol(role) {
  return ({
    admin_general: 'Administrador general',
    admin_servicios: 'Administrador de servicios',
    secretario: 'Secretario'
  })[role] || role || 'Usuario'
}

function configRol(role) {
  return ({
    admin_general: {
      fondo: 'green-1',
      texto: 'green-9',
      chip: 'green-8',
      icono: 'admin_panel_settings'
    },
    admin_servicios: {
      fondo: 'blue-1',
      texto: 'blue-9',
      chip: 'blue-7',
      icono: 'support_agent'
    },
    secretario: {
      fondo: 'purple-1',
      texto: 'purple-9',
      chip: 'purple-6',
      icono: 'edit_note'
    }
  })[role] || {
    fondo: 'grey-2',
    texto: 'grey-8',
    chip: 'grey-7',
    icono: 'person'
  }
}
function totalRol(role) { return usuarios.value.filter(u => u.role === role).length }
function alternarRol(role) { filtroRol.value = filtroRol.value === role ? null : role }
function mensajeError(error, defecto) { const errors = error.response?.data?.errors; if (errors) return Object.values(errors).flat().join(' | '); return error.response?.data?.message || defecto }

async function cargar() {
  cargando.value = true
  try {
    const [ru, rp, rf, rs] = await Promise.all([api.get('/usuarios'), api.get('/personas'), api.get('/federaciones'), api.get('/sindicatos')])
    usuarios.value = Array.isArray(ru.data) ? ru.data : []
    personas.value = Array.isArray(rp.data) ? rp.data : []
    personasFiltradas.value = personas.value
    federaciones.value = Array.isArray(rf.data) ? rf.data : []
    sindicatos.value = Array.isArray(rs.data) ? rs.data : []
  } catch (error) {
    $q.notify({ type: 'negative', position: 'top', message: mensajeError(error, 'No se pudo cargar el módulo de usuarios.') })
  } finally { cargando.value = false }
}

function filtrarPersonas(valor, update) {
  update(() => {
    const texto = String(valor || '').trim().toLowerCase()
    personasFiltradas.value = !texto ? personas.value : personas.value.filter(p => `${p.nombre || ''} ${p.apellidos || ''} ${p.ci || ''}`.toLowerCase().includes(texto))
  })
}

function limpiarForm() {
  Object.assign(form, { persona_id: null, nickname: '', email: '', password: '', role: 'admin_general', federacion_id: null, sindicato_id: null })
  mostrarPassword.value = false
}
function abrirCrear() { editando.value = null; limpiarForm(); dialogoForm.value = true }
function abrirEditar(u) {
  editando.value = u
  Object.assign(form, { persona_id: u.persona_id || null, nickname: u.nickname || '', email: u.email?.endsWith('@motrix.local') ? '' : (u.email || ''), password: '', role: u.role || 'admin_general', federacion_id: u.federacion_id || null, sindicato_id: u.sindicato_id || null })
  mostrarPassword.value = false
  dialogoForm.value = true
}

async function guardar() {
  if (!form.persona_id || form.nickname.trim().length < 4 || (!editando.value && form.password.length < 6)) {
    $q.notify({ type: 'warning', position: 'top', message: 'Completa persona, nickname y contraseña correctamente.' })
    return
  }

  if (form.role === 'secretario' && !form.sindicato_id) {
    $q.notify({
      type: 'warning',
      position: 'top',
      message: 'Selecciona el sindicato que administrará el secretario.'
    })
    return
  }

  guardando.value = true
  try {
    const esSecretario = form.role === 'secretario'

    const payload = {
      persona_id: form.persona_id,
      nickname: form.nickname.trim(),
      email: form.email.trim() || null,
      role: form.role,
      federacion_id: esSecretario
        ? (form.federacion_id || null)
        : null,
      sindicato_id: esSecretario
        ? (form.sindicato_id || null)
        : null
    }
    if (form.password) payload.password = form.password
    if (editando.value) await api.put(`/usuarios/${editando.value.id}`, payload)
    else await api.post('/usuarios', payload)
    $q.notify({ type: 'positive', position: 'top', message: editando.value ? 'Usuario actualizado.' : 'Usuario creado.' })
    dialogoForm.value = false
    await cargar()
  } catch (error) {
    $q.notify({ type: 'negative', position: 'top', multiLine: true, timeout: 7000, message: mensajeError(error, 'No se pudo guardar el usuario.') })
  } finally { guardando.value = false }
}

function confirmarEliminar(u) {
  $q.dialog({ title: 'Eliminar usuario', message: `¿Deseas eliminar la cuenta “${u.nickname || u.email}”?`, cancel: { label: 'Cancelar', flat: true }, ok: { label: 'Eliminar', color: 'negative' }, persistent: true }).onOk(async () => {
    try {
      await api.delete(`/usuarios/${u.id}`)
      $q.notify({ type: 'positive', position: 'top', message: 'Usuario eliminado.' })
      await cargar()
    } catch (error) {
      $q.notify({ type: 'negative', position: 'top', multiLine: true, message: mensajeError(error, 'No se pudo eliminar el usuario.') })
    }
  })
}

onMounted(cargar)
</script>

<style scoped>
.page-shell { max-width: 1280px; margin: 0 auto; }
.stat-card, .filters-card, .lista-card { border-radius: 14px; }
.stat-card { border-left: 4px solid #2e7d32; transition: transform .18s ease, box-shadow .18s ease; }
.stat-card:hover, .stat-card-active { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(27,94,32,.12); }
.stat-card-active { background: #f1f8e9; }
.dialog-card { width: min(680px, 96vw); border-top: 4px solid #2e7d32; border-radius: 14px; }
.dialog-header { background: #f1f8e9; }
</style>
