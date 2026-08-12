<template>
  <q-page class="q-pa-md q-pa-lg-lg">
    <div class="page-shell">
      <div class="row items-center q-col-gutter-md q-mb-lg">
        <div class="col-12 col-md">
          <div class="row items-center no-wrap">
            <q-avatar color="green-1" text-color="green-9" icon="manage_accounts" size="50px" class="q-mr-md" />
            <div>
              <div class="text-h5 text-weight-bold text-green-9">Usuarios y accesos</div>
              <div class="text-caption text-grey-7">
                Administra IDs, roles y credenciales de administradores, conductores y pasajeros.
              </div>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-auto">
          <q-btn
            color="green-8"
            icon="person_add"
            label="Nuevo administrativo"
            unelevated
            no-caps
            class="full-width"
            @click="abrirCrear"
          />
        </div>
      </div>

      <div class="row q-col-gutter-sm q-mb-lg">
        <div
          v-for="resumen in resumenRoles"
          :key="resumen.role"
          class="col-6 col-sm-4 col-lg"
        >
          <q-card
            flat
            bordered
            class="stat-card cursor-pointer"
            :class="{ 'stat-card-active': filtroRol === resumen.role }"
            @click="alternarRol(resumen.role)"
          >
            <q-card-section class="row items-center no-wrap q-pa-md">
              <q-avatar
                :color="resumen.fondo"
                :text-color="resumen.texto"
                :icon="resumen.icono"
                size="40px"
                class="q-mr-sm"
              />
              <div class="ellipsis">
                <div class="text-caption text-grey-7 ellipsis">{{ resumen.etiqueta }}</div>
                <div class="text-h6 text-weight-bold" :class="`text-${resumen.texto}`">
                  {{ totalRol(resumen.role) }}
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <q-card flat bordered class="q-mb-md filters-card">
        <q-card-section class="row q-col-gutter-md items-center">
          <div class="col-12 col-md-8">
            <q-input
              v-model="busqueda"
              outlined
              dense
              clearable
              debounce="250"
              placeholder="Buscar por ID, nombre, usuario o correo"
            >
              <template #prepend>
                <q-icon name="search" color="green-8" />
              </template>
            </q-input>
          </div>
          <div class="col-12 col-md-4 row justify-end q-gutter-sm">
            <q-chip
              v-if="filtroRol"
              removable
              color="green-1"
              text-color="green-9"
              icon="filter_alt"
              @remove="filtroRol = null"
            >
              {{ etiquetaRol(filtroRol) }}
            </q-chip>
            <q-chip color="grey-2" text-color="grey-8">
              {{ usuariosFiltrados.length }} cuenta{{ usuariosFiltrados.length === 1 ? '' : 's' }}
            </q-chip>
          </div>
        </q-card-section>
      </q-card>

      <q-card flat bordered class="lista-card">
        <q-linear-progress v-if="cargando" indeterminate color="green-8" />

        <q-list v-if="usuariosFiltrados.length" separator>
          <q-item
            v-for="usuario in usuariosFiltrados"
            :key="usuario.id"
            class="usuario-item q-py-md"
          >
            <q-item-section avatar>
              <q-avatar
                :color="configRol(usuario.role).fondo"
                :text-color="configRol(usuario.role).texto"
              >
                {{ iniciales(usuario) }}
              </q-avatar>
            </q-item-section>

            <q-item-section>
              <div class="row items-center q-gutter-xs">
                <q-item-label class="text-weight-bold">
                  {{ nombreUsuario(usuario) }}
                </q-item-label>
                <q-badge color="grey-8" outline>
                  ID #{{ usuario.id }}
                </q-badge>
              </div>

              <q-item-label caption class="q-mt-xs">
                <q-icon name="alternate_email" size="13px" />
                {{ usuario.nickname || 'Sin usuario' }}
                <span class="q-mx-xs">·</span>
                <q-icon name="mail" size="13px" />
                {{ usuario.email || 'Sin correo' }}
              </q-item-label>

              <q-item-label caption class="text-grey-7 q-mt-xs">
                {{ descripcionVinculo(usuario) }}
              </q-item-label>
            </q-item-section>

            <q-item-section side class="gt-xs">
              <q-chip
                dense
                :color="configRol(usuario.role).chip"
                text-color="white"
                :icon="configRol(usuario.role).icono"
              >
                {{ etiquetaRol(usuario.role) }}
              </q-chip>
            </q-item-section>

            <q-item-section side>
              <q-btn flat round dense icon="more_vert" color="grey-7">
                <q-menu>
                  <q-list style="min-width: 190px">
                    <q-item clickable v-close-popup @click="abrirEditar(usuario)">
                      <q-item-section avatar>
                        <q-icon name="edit" color="green-8" />
                      </q-item-section>
                      <q-item-section>Editar acceso</q-item-section>
                    </q-item>

                    <q-item
                      v-if="esAdministrativo(usuario.role)"
                      clickable
                      v-close-popup
                      @click="confirmarEliminar(usuario)"
                    >
                      <q-item-section avatar>
                        <q-icon name="delete" color="negative" />
                      </q-item-section>
                      <q-item-section class="text-negative">Eliminar cuenta</q-item-section>
                    </q-item>
                  </q-list>
                </q-menu>
              </q-btn>
            </q-item-section>
          </q-item>
        </q-list>

        <q-card-section
          v-else-if="!cargando"
          class="column items-center q-pa-xl text-grey-7"
        >
          <q-icon name="manage_accounts" size="54px" color="green-4" />
          <div class="text-subtitle1 text-weight-bold q-mt-sm">No se encontraron cuentas</div>
        </q-card-section>
      </q-card>
    </div>

    <q-dialog v-model="dialogoForm" persistent>
      <q-card class="dialog-card">
        <q-card-section class="dialog-header row items-center">
          <q-avatar color="green-1" text-color="green-9" icon="manage_accounts" class="q-mr-md" />
          <div class="col">
            <div class="text-h6 text-weight-bold">
              {{ editando ? `Editar acceso · ID #${editando.id}` : 'Nuevo usuario administrativo' }}
            </div>
            <div class="text-caption text-grey-6">
              {{ esCuentaOperativa ? 'El vínculo operativo se conserva; solo se editan las credenciales.' : 'Cuenta administrativa de acceso a MOTRIX.' }}
            </div>
          </div>
          <q-btn flat round dense icon="close" @click="cerrarFormulario" />
        </q-card-section>

        <q-separator />

        <q-card-section class="q-gutter-md scroll" style="max-height: 68vh">
          <q-banner
            v-if="esCuentaOperativa"
            rounded
            class="bg-blue-1 text-blue-10"
          >
            <template #avatar>
              <q-icon name="link" color="blue-8" />
            </template>
            {{ descripcionVinculo(editando) }}. El rol y el vínculo no se cambian aquí para no romper sus viajes o historial.
          </q-banner>

          <q-select
            v-if="!esCuentaOperativa"
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
            <template #prepend>
              <q-icon name="person" color="green-8" />
            </template>
          </q-select>

          <q-input
            v-else
            :model-value="nombreUsuario(editando)"
            outlined
            readonly
            label="Persona vinculada"
          >
            <template #prepend>
              <q-icon name="person" color="blue-8" />
            </template>
          </q-input>

          <div class="row q-col-gutter-md">
            <div class="col-12 col-sm-6">
              <q-input
                v-model.trim="form.nickname"
                outlined
                label="Usuario / nickname *"
                maxlength="50"
                autocomplete="off"
              >
                <template #prepend>
                  <q-icon name="alternate_email" color="green-8" />
                </template>
              </q-input>
            </div>

            <div class="col-12 col-sm-6">
              <q-input
                v-model.trim="form.email"
                outlined
                :label="esCuentaOperativa ? 'Correo *' : 'Correo (opcional)'"
                maxlength="150"
                type="email"
              >
                <template #prepend>
                  <q-icon name="mail" color="green-8" />
                </template>
              </q-input>
            </div>
          </div>

          <q-input
            v-model="form.password"
            outlined
            :label="editando ? 'Nueva contraseña (vacío = conservar actual)' : 'Contraseña inicial *'"
            :type="mostrarPassword ? 'text' : 'password'"
            autocomplete="new-password"
            hint="Mínimo 6 caracteres"
          >
            <template #prepend>
              <q-icon name="lock" color="green-8" />
            </template>
            <template #append>
              <q-icon
                :name="mostrarPassword ? 'visibility_off' : 'visibility'"
                class="cursor-pointer"
                @click="mostrarPassword = !mostrarPassword"
              />
            </template>
          </q-input>

          <q-select
            v-if="!esCuentaOperativa"
            v-model="form.role"
            :options="opcionesRolesAdministrativos"
            outlined
            emit-value
            map-options
            label="Rol *"
          >
            <template #prepend>
              <q-icon name="admin_panel_settings" color="green-8" />
            </template>
          </q-select>

          <q-input
            v-else
            :model-value="etiquetaRol(editando?.role)"
            outlined
            readonly
            label="Rol"
          />

          <div v-if="!esCuentaOperativa && form.role === 'secretario'" class="row q-col-gutter-md">
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
              />
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md bg-grey-1">
          <q-btn flat label="Cancelar" color="grey-7" no-caps @click="cerrarFormulario" />
          <q-btn
            color="green-8"
            :label="editando ? 'Guardar cambios' : 'Crear usuario'"
            icon="save"
            unelevated
            no-caps
            :loading="guardando"
            @click="guardar"
          />
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

const rolesAdministrativos = ['admin_general', 'admin_servicios', 'secretario']

const form = reactive({
  persona_id: null,
  nickname: '',
  email: '',
  password: '',
  role: 'admin_general',
  federacion_id: null,
  sindicato_id: null
})

const resumenRoles = [
  { role: 'admin_general', etiqueta: 'Admin. general', icono: 'admin_panel_settings', fondo: 'green-1', texto: 'green-9' },
  { role: 'admin_servicios', etiqueta: 'Admin. servicios', icono: 'support_agent', fondo: 'blue-1', texto: 'blue-9' },
  { role: 'secretario', etiqueta: 'Secretarios', icono: 'edit_note', fondo: 'purple-1', texto: 'purple-9' },
  { role: 'conductor', etiqueta: 'Conductores', icono: 'two_wheeler', fondo: 'orange-1', texto: 'orange-9' },
  { role: 'pasajero', etiqueta: 'Pasajeros', icono: 'person_pin_circle', fondo: 'teal-1', texto: 'teal-9' }
]

const opcionesRolesAdministrativos = [
  { label: 'Administrador general', value: 'admin_general' },
  { label: 'Administrador de servicios', value: 'admin_servicios' },
  { label: 'Secretario de sindicato', value: 'secretario' }
]

const esCuentaOperativa = computed(() => Boolean(editando.value && ['conductor', 'pasajero'].includes(editando.value.role)))
const opcionesPersonas = computed(() => personasFiltradas.value.map(p => ({
  label: `${p.nombre || ''} ${p.apellidos || ''} · ID ${p.id}${p.ci ? ` · CI ${p.ci}` : ''}`.trim(),
  value: p.id
})))
const opcionesFederaciones = computed(() => federaciones.value.map(f => ({ label: f.nombre, value: f.id })))
const opcionesSindicatosFiltrados = computed(() => sindicatos.value
  .filter(s => !form.federacion_id || Number(s.id_federacion) === Number(form.federacion_id))
  .map(s => ({ label: s.nombre, value: s.id })))

const usuariosFiltrados = computed(() => {
  const texto = String(busqueda.value || '').trim().toLowerCase()
  return usuarios.value.filter(u => {
    if (filtroRol.value && u.role !== filtroRol.value) return false
    if (!texto) return true
    return [u.id, nombreUsuario(u), u.nickname, u.email, etiquetaRol(u.role)]
      .some(valor => String(valor || '').toLowerCase().includes(texto))
  })
})

watch(() => form.role, nuevoRol => {
  if (nuevoRol !== 'secretario') {
    form.federacion_id = null
    form.sindicato_id = null
  }
})

watch(() => form.federacion_id, () => {
  if (form.sindicato_id && !sindicatos.value.some(s => Number(s.id) === Number(form.sindicato_id) && (!form.federacion_id || Number(s.id_federacion) === Number(form.federacion_id)))) {
    form.sindicato_id = null
  }
})

function esAdministrativo(role) {
  return rolesAdministrativos.includes(role)
}

function personaDeUsuario(u) {
  return u?.persona || u?.mototaxista?.persona || u?.pasajero?.persona || null
}

function nombreUsuario(u) {
  const persona = personaDeUsuario(u)
  return `${persona?.nombre || u?.name || ''} ${persona?.apellidos || ''}`.trim() || u?.nickname || 'Usuario'
}

function iniciales(u) {
  const partes = nombreUsuario(u).split(/\s+/).filter(Boolean)
  return ((partes[0]?.[0] || '') + (partes[1]?.[0] || '')).toUpperCase() || 'U'
}

function etiquetaRol(role) {
  return ({
    admin_general: 'Administrador general',
    admin_servicios: 'Administrador de servicios',
    secretario: 'Secretario',
    conductor: 'Conductor / mototaxista',
    pasajero: 'Pasajero / cliente'
  })[role] || role || 'Usuario'
}

function configRol(role) {
  return ({
    admin_general: { fondo: 'green-1', texto: 'green-9', chip: 'green-8', icono: 'admin_panel_settings' },
    admin_servicios: { fondo: 'blue-1', texto: 'blue-9', chip: 'blue-7', icono: 'support_agent' },
    secretario: { fondo: 'purple-1', texto: 'purple-9', chip: 'purple-6', icono: 'edit_note' },
    conductor: { fondo: 'orange-1', texto: 'orange-9', chip: 'orange-8', icono: 'two_wheeler' },
    pasajero: { fondo: 'teal-1', texto: 'teal-9', chip: 'teal-7', icono: 'person_pin_circle' }
  })[role] || { fondo: 'grey-2', texto: 'grey-8', chip: 'grey-7', icono: 'person' }
}

function descripcionVinculo(u) {
  if (!u) return ''
  const partes = [`Persona ID: ${u.persona_id ?? '—'}`]
  if (u.role === 'conductor') partes.push(`Mototaxista ID: ${u.mototaxista_id ?? '—'}`)
  if (u.role === 'pasajero') partes.push(`Pasajero ID: ${u.pasajero_id ?? '—'}`)
  if (u.sindicato_id) partes.push(`Sindicato ID: ${u.sindicato_id}`)
  if (u.federacion_id) partes.push(`Federación ID: ${u.federacion_id}`)
  return partes.join(' · ')
}

function totalRol(role) {
  return usuarios.value.filter(u => u.role === role).length
}

function alternarRol(role) {
  filtroRol.value = filtroRol.value === role ? null : role
}

function mensajeError(error, defecto) {
  const errors = error.response?.data?.errors
  if (errors) return Object.values(errors).flat().join(' | ')
  return error.response?.data?.message || error.response?.data?.mensaje || defecto
}

async function cargar() {
  cargando.value = true
  try {
    const [ru, rp, rf, rs] = await Promise.all([
      api.get('/usuarios'),
      api.get('/personas'),
      api.get('/federaciones'),
      api.get('/sindicatos')
    ])
    usuarios.value = Array.isArray(ru.data) ? ru.data : []
    personas.value = Array.isArray(rp.data) ? rp.data : []
    personasFiltradas.value = personas.value
    federaciones.value = Array.isArray(rf.data) ? rf.data : []
    sindicatos.value = Array.isArray(rs.data) ? rs.data : []
  } catch (error) {
    $q.notify({ type: 'negative', position: 'top', message: mensajeError(error, 'No se pudo cargar el módulo de usuarios.') })
  } finally {
    cargando.value = false
  }
}

function filtrarPersonas(valor, update) {
  update(() => {
    const texto = String(valor || '').trim().toLowerCase()
    personasFiltradas.value = !texto
      ? personas.value
      : personas.value.filter(p => `${p.id} ${p.nombre || ''} ${p.apellidos || ''} ${p.ci || ''}`.toLowerCase().includes(texto))
  })
}

function limpiarForm() {
  Object.assign(form, {
    persona_id: null,
    nickname: '',
    email: '',
    password: '',
    role: 'admin_general',
    federacion_id: null,
    sindicato_id: null
  })
  mostrarPassword.value = false
}

function abrirCrear() {
  editando.value = null
  limpiarForm()
  dialogoForm.value = true
}

function abrirEditar(usuario) {
  editando.value = usuario
  Object.assign(form, {
    persona_id: usuario.persona_id || personaDeUsuario(usuario)?.id || null,
    nickname: usuario.nickname || '',
    email: usuario.email || '',
    password: '',
    role: usuario.role,
    federacion_id: usuario.federacion_id || null,
    sindicato_id: usuario.sindicato_id || null
  })
  dialogoForm.value = true
}

function cerrarFormulario() {
  dialogoForm.value = false
  editando.value = null
  limpiarForm()
}

async function guardar() {
  if (!form.nickname || form.nickname.length < 4) {
    $q.notify({ type: 'warning', message: 'El usuario debe tener al menos 4 caracteres.' })
    return
  }

  if (!editando.value && (!form.password || form.password.length < 6)) {
    $q.notify({ type: 'warning', message: 'La contraseña inicial debe tener al menos 6 caracteres.' })
    return
  }

  if (editando.value && form.password && form.password.length < 6) {
    $q.notify({ type: 'warning', message: 'La nueva contraseña debe tener al menos 6 caracteres.' })
    return
  }

  if (esCuentaOperativa.value && !form.email) {
    $q.notify({ type: 'warning', message: 'La cuenta operativa debe conservar un correo válido.' })
    return
  }

  if (!esCuentaOperativa.value && !form.persona_id) {
    $q.notify({ type: 'warning', message: 'Selecciona la persona vinculada.' })
    return
  }

  if (!esCuentaOperativa.value && form.role === 'secretario' && !form.sindicato_id) {
    $q.notify({ type: 'warning', message: 'Selecciona el sindicato del secretario.' })
    return
  }

  guardando.value = true
  try {
    let payload

    if (esCuentaOperativa.value) {
      payload = {
        nickname: form.nickname,
        email: form.email
      }
      if (form.password) payload.password = form.password
    } else {
      payload = {
        persona_id: form.persona_id,
        nickname: form.nickname,
        email: form.email || null,
        role: form.role,
        federacion_id: form.role === 'secretario' ? form.federacion_id : null,
        sindicato_id: form.role === 'secretario' ? form.sindicato_id : null
      }
      if (form.password) payload.password = form.password
    }

    if (editando.value) {
      await api.put(`/usuarios/${editando.value.id}`, payload)
    } else {
      await api.post('/usuarios', payload)
    }

    $q.notify({
      type: 'positive',
      position: 'top',
      message: editando.value ? 'Cuenta actualizada correctamente.' : 'Usuario creado correctamente.'
    })

    cerrarFormulario()
    await cargar()
  } catch (error) {
    $q.notify({ type: 'negative', position: 'top', message: mensajeError(error, 'No se pudo guardar la cuenta.') })
  } finally {
    guardando.value = false
  }
}

function confirmarEliminar(usuario) {
  $q.dialog({
    title: 'Eliminar cuenta',
    message: `¿Eliminar la cuenta administrativa de ${nombreUsuario(usuario)}?`,
    cancel: true,
    persistent: true,
    ok: { label: 'Eliminar', color: 'negative', noCaps: true },
    cancel: { label: 'Cancelar', flat: true, noCaps: true }
  }).onOk(async () => {
    try {
      await api.delete(`/usuarios/${usuario.id}`)
      $q.notify({ type: 'positive', message: 'Cuenta eliminada.' })
      await cargar()
    } catch (error) {
      $q.notify({ type: 'negative', message: mensajeError(error, 'No se pudo eliminar la cuenta.') })
    }
  })
}

onMounted(cargar)
</script>

<style scoped>
.page-shell {
  max-width: 1180px;
  margin: 0 auto;
}

.stat-card,
.filters-card,
.lista-card,
.dialog-card {
  border-radius: 16px;
}

.stat-card {
  min-height: 82px;
  transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease;
}

.stat-card:hover {
  transform: translateY(-1px);
  border-color: #66bb6a;
}

.stat-card-active {
  border-color: #2e7d32;
  box-shadow: 0 4px 14px rgba(46, 125, 50, .12);
}

.usuario-item {
  min-height: 84px;
}

.dialog-card {
  width: min(720px, 94vw);
  max-width: 720px;
}

.dialog-header {
  background: linear-gradient(135deg, #f4fff5, #ffffff);
}

@media (max-width: 599px) {
  .usuario-item {
    padding-left: 10px;
    padding-right: 6px;
  }
}
</style>
