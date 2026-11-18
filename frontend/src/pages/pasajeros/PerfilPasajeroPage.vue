<template>
  <q-page class="motrix-perfil-claro">
    <div class="motrix-perfil-contenido">
      <div class="motrix-perfil-cabecera row items-center justify-between">
        <q-btn flat round icon="arrow_back" color="grey-9" aria-label="Volver" @click="volver" />
        <div class="text-h6 text-weight-bold">Mi perfil</div>
        <q-btn flat round icon="refresh" color="green-8" :loading="cargando" aria-label="Actualizar perfil" @click="cargarPerfil" />
      </div>
      <div class="motrix-perfil-identidad">
        <q-avatar size="92px" color="green-8" text-color="white" class="motrix-perfil-avatar" @click="abrirVisorFoto">
          <img v-if="fotoPerfilUrl && !fotoPerfilError" :src="fotoPerfilUrl" :alt="`Foto de ${nombreUsuario}`" @error="fotoPerfilError = true">
          <span v-else>{{ iniciales }}</span>
        </q-avatar>
        <div class="text-h6 text-weight-bold q-mt-md">{{ nombreUsuario }}</div>
        <div class="motrix-perfil-email">{{ usuario?.email || usuario?.telefono || 'Pasajero MOTRIX' }}</div>
        <q-btn outline no-caps dense color="green-8" icon="photo_camera" label="Cambiar foto"
          class="q-mt-md motrix-perfil-foto-btn" :loading="subiendoFotoPerfil" @click="abrirSelectorFotoPerfil" />
        <input ref="fotoPerfilInput" type="file" accept="image/jpeg,image/png,image/webp" class="profile-file-input" @change="seleccionarFotoPerfil">
      </div>

      <div class="motrix-perfil-seccion">Cuenta</div>
      <div class="motrix-perfil-lista">
        <q-expansion-item icon="manage_accounts" label="Mis datos personales" expand-icon="chevron_right">
          <div class="motrix-perfil-detalles">
            <div><b>Nombre:</b> {{ nombreUsuario }}</div>
            <div><b>Teléfono:</b> {{ usuario?.telefono || usuario?.nickname || 'No registrado' }}</div>
            <div><b>Correo:</b> {{ usuario?.email || 'No registrado' }}</div>
          </div>
        </q-expansion-item>
        <q-item clickable v-ripple @click="router.push('/cuenta/cambiar-contrasena')">
          <q-item-section avatar><q-icon name="lock_outline" /></q-item-section>
          <q-item-section>Cambiar contraseña</q-item-section>
          <q-item-section side><q-icon name="chevron_right" /></q-item-section>
        </q-item>
      </div>

      <div class="motrix-perfil-seccion">Mis viajes</div>
      <div class="motrix-perfil-lista">
        <q-item clickable v-ripple @click="irAHistorial">
          <q-item-section avatar><q-icon name="history" /></q-item-section>
          <q-item-section>Historial de viajes</q-item-section>
          <q-item-section side><q-icon name="chevron_right" /></q-item-section>
        </q-item>
        <q-item clickable v-ripple @click="irASolicitar">
          <q-item-section avatar><q-icon name="two_wheeler" /></q-item-section>
          <q-item-section>Solicitar mototaxi</q-item-section>
          <q-item-section side><q-icon name="chevron_right" /></q-item-section>
        </q-item>
      </div>

      <div class="motrix-perfil-seccion">Ayuda y seguridad</div>
      <div class="motrix-perfil-lista">
        <q-item clickable v-ripple @click="dialogAyuda = true">
          <q-item-section avatar><q-icon name="help_outline" /></q-item-section>
          <q-item-section>Ayuda</q-item-section>
          <q-item-section side><q-icon name="chevron_right" /></q-item-section>
        </q-item>
        <q-item clickable v-ripple @click="abrirEliminarCuenta">
          <q-item-section avatar><q-icon name="privacy_tip" /></q-item-section>
          <q-item-section>Privacidad y eliminación de cuenta</q-item-section>
          <q-item-section side><q-icon name="chevron_right" /></q-item-section>
        </q-item>
      </div>

      <div class="motrix-perfil-lista motrix-perfil-sesion">
        <q-item clickable v-ripple :disable="cerrandoSesion" @click="cerrarSesion">
          <q-item-section avatar><q-icon name="logout" color="negative" /></q-item-section>
          <q-item-section class="text-negative text-weight-medium">Cerrar sesión</q-item-section>
          <q-item-section side><q-spinner v-if="cerrandoSesion" color="negative" size="20px" /><q-icon v-else name="chevron_right" /></q-item-section>
        </q-item>
      </div>
      <div class="motrix-perfil-pie">MOTRIX · Movilidad en Trinidad</div>
    </div>

    <q-dialog v-model="dialogAyuda">
      <q-card style="width: min(94vw, 450px)">
        <q-card-section class="row items-center">
          <q-icon name="help_outline" color="green-8" size="28px" class="q-mr-sm" />
          <div class="text-h6">Ayuda para pasajeros</div>
        </q-card-section>
        <q-card-section>
          <p>Para solicitar un mototaxi, selecciona tu origen y destino y confirma la tarifa propuesta.</p>
          <p>Durante el viaje puedes comunicarte con tu conductor desde la pantalla de seguimiento. Si tienes una emergencia, utiliza la opción SOS disponible durante el servicio.</p>
          <p class="text-caption text-grey-7 q-mb-none">Los conductores son registrados por el equipo de MOTRIX. No necesitas crear una cuenta de conductor.</p>
        </q-card-section>
        <q-card-actions align="right"><q-btn flat color="green-8" label="Entendido" v-close-popup /></q-card-actions>
      </q-card>
    </q-dialog>
            <q-dialog v-model="dialogEliminarCuenta" persistent>
              <q-card class="delete-dialog">
                <q-card-section class="row items-center">
                  <q-avatar icon="warning" color="red-1" text-color="red-8" />
                  <div class="col q-ml-md">
                    <div class="text-h6 text-weight-bold">Eliminar cuenta</div>
                    <div class="text-caption text-grey-7">Esta acción no se puede deshacer.</div>
                  </div>
                </q-card-section>

                <q-card-section>
                  <q-banner rounded class="bg-red-1 text-red-9 q-mb-md">
                    No podrás eliminar la cuenta mientras tengas un viaje activo. Los viajes históricos
                    se conservarán únicamente de forma anonimizada para mantener la integridad del sistema.
                  </q-banner>

                  <q-input
                    v-model="passwordActual"
                    outlined
                    type="password"
                    label="Contraseña actual"
                    autocomplete="current-password"
                    class="q-mb-md"
                  />

                  <q-input
                    v-model.trim="textoConfirmacion"
                    outlined
                    label="Escribe ELIMINAR"
                    hint="Debe escribirse exactamente en mayúsculas."
                  />
                </q-card-section>

                <q-card-actions align="right" class="q-pa-md">
                  <q-btn
                    flat
                    label="Cancelar"
                    color="grey-7"
                    :disable="eliminandoCuenta"
                    @click="dialogEliminarCuenta = false"
                  />
                  <q-btn
                    color="red-7"
                    icon="delete_forever"
                    label="Eliminar definitivamente"
                    unelevated
                    :loading="eliminandoCuenta"
                    :disable="!puedeConfirmarEliminacion"
                    @click="eliminarCuenta"
                  />
                </q-card-actions>
              </q-card>
            </q-dialog>
    <PhotoViewerDialog v-model="visorFoto" :src="fotoPerfilUrl" :title="nombreUsuario" />
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
  useRoute,
  useRouter
} from 'vue-router'

import {
  api
} from 'src/boot/axios.js'
import PhotoViewerDialog from 'src/components/PhotoViewerDialog.vue'
import { ES_APLICACION_NATIVA } from 'src/config/runtime.js'

const $q = useQuasar()
const router = useRouter()
const route = useRoute()

const cargando = ref(false)
const dialogAyuda = ref(false)
const cerrandoSesion = ref(false)
const visorFoto = ref(false)
const fotoPerfilError = ref(false)
const fotoPerfilInput = ref(null)
const subiendoFotoPerfil = ref(false)
const dialogEliminarCuenta = ref(false)
const eliminandoCuenta = ref(false)
const passwordActual = ref('')
const textoConfirmacion = ref('')
const usuario = ref(
  leerUsuarioLocal()
)

function leerUsuarioLocal() {
  try {
    return JSON.parse(
      localStorage.getItem('motrix_user')
      || 'null'
    )
  } catch {
    return null
  }
}

const nombreUsuario = computed(() => {
  return (
    usuario.value?.persona_nombre
    || usuario.value?.pasajero?.persona?.nombre
    || usuario.value?.name
    || usuario.value?.email
    || 'Pasajero MOTRIX'
  )
})

const fotoPerfilUrl = computed(() => {
  if (fotoPerfilError.value) return ''
  const ruta = String(usuario.value?.foto_ruta || '').trim()
  if (!ruta) return ''
  if (/^https?:\/\//i.test(ruta)) return ruta

  const baseApi = String(api.defaults.baseURL || '')
    .replace(/\/api\/?$/i, '')
    .replace(/\/$/, '')
  let limpia = ruta.replace(/^\/+/, '').replace(/^public\//i, '')
  if (!limpia.startsWith('storage/')) limpia = `storage/${limpia}`
  return `${baseApi}/${limpia}`
})

function abrirVisorFoto() {
  if (fotoPerfilUrl.value) visorFoto.value = true
}

function abrirSelectorFotoPerfil() {
  if (subiendoFotoPerfil.value) return
  fotoPerfilInput.value?.click()
}

async function seleccionarFotoPerfil(event) {
  const input = event?.target
  const archivo = input?.files?.[0]

  if (!archivo) return

  const tiposPermitidos = [
    'image/jpeg',
    'image/png',
    'image/webp'
  ]

  if (!tiposPermitidos.includes(archivo.type)) {
    $q.notify({
      type: 'warning',
      position: 'top',
      message: 'Selecciona una imagen JPG, PNG o WEBP.'
    })
    input.value = ''
    return
  }

  if (archivo.size > 4 * 1024 * 1024) {
    $q.notify({
      type: 'warning',
      position: 'top',
      message: 'La foto no puede superar los 4 MB.'
    })
    input.value = ''
    return
  }

  subiendoFotoPerfil.value = true

  try {
    const formData = new FormData()
    formData.append('imagen', archivo)

    const respuesta = await api.post(
      '/perfil/foto',
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      }
    )

    fotoPerfilError.value = false

    $q.notify({
      type: 'positive',
      position: 'top',
      icon: 'check_circle',
      message:
        respuesta?.data?.message
        || 'Foto de perfil actualizada.'
    })

    await cargarPerfil()
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      message:
        error?.response?.data?.message
        || Object.values(
          error?.response?.data?.errors || {}
        ).flat().find(Boolean)
        || 'No se pudo actualizar la foto de perfil.'
    })
  } finally {
    subiendoFotoPerfil.value = false
    if (input) input.value = ''
  }
}

const iniciales = computed(() => {
  const partes =
    String(nombreUsuario.value)
      .trim()
      .split(/\s+/)
      .filter(Boolean)

  const primera =
    partes[0]?.charAt(0)
    || 'M'

  const ultima =
    partes.length > 1
      ? partes[
          partes.length - 1
        ].charAt(0)
      : ''

  return (
    primera + ultima
  ).toUpperCase()
})

async function cargarPerfil() {
  if (cargando.value) return

  cargando.value = true

  try {
    const response =
      await api.get('/auth/me')

    const datos =
      response?.data?.user

    if (datos) {
      usuario.value = datos

      localStorage.setItem(
        'motrix_user',
        JSON.stringify(datos)
      )
    }
  } catch (error) {
    console.error(
      'No se pudo cargar el perfil:',
      error
    )

    $q.notify({
      type: 'negative',
      position: 'top',
      message:
        error?.response?.data?.message
        || 'No se pudo actualizar el perfil.'
    })
  } finally {
    cargando.value = false
  }
}

const puedeConfirmarEliminacion = computed(() => {
  return (
    textoConfirmacion.value === 'ELIMINAR'
    && String(passwordActual.value || '').length > 0
  )
})

function abrirEliminarCuenta() {
  passwordActual.value = ''
  textoConfirmacion.value = ''
  dialogEliminarCuenta.value = true
}

function limpiarSesionLocal() {
  localStorage.removeItem('motrix_token')
  localStorage.removeItem('motrix_user')
  localStorage.removeItem('mototaxista_id')
  localStorage.removeItem('pasajero_id')
}

async function eliminarCuenta() {
  if (!puedeConfirmarEliminacion.value || eliminandoCuenta.value) return

  eliminandoCuenta.value = true

  try {
    const response = await api.delete('/pasajero/cuenta-segura', {
      data: {
        confirmacion: textoConfirmacion.value,
        password_actual: passwordActual.value
      }
    })

    limpiarSesionLocal()
    dialogEliminarCuenta.value = false

    $q.notify({
      type: 'positive',
      position: 'top',
      icon: 'check_circle',
      message: response?.data?.message || 'Tu cuenta fue eliminada correctamente.'
    })

    await router.replace('/presentacion')
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      icon: 'error',
      message:
        error?.response?.data?.message
        || Object.values(error?.response?.data?.errors || {}).flat().find(Boolean)
        || 'No se pudo eliminar la cuenta.'
    })
  } finally {
    eliminandoCuenta.value = false
  }
}

async function cerrarSesion() {
  if (cerrandoSesion.value) return
  cerrandoSesion.value = true
  try {
    await api.post('/auth/logout')
  } catch (error) {
    if (error?.response?.status !== 401) {
      console.warn('No se pudo cerrar la sesión en el servidor:', error)
    }
  } finally {
    limpiarSesionLocal()
    cerrandoSesion.value = false
    await router.replace(ES_APLICACION_NATIVA ? '/login' : '/presentacion')
  }
}

function volver() {
  router.push('/pasajero')
}

function irAHistorial() {
  router.push('/pasajero/historial')
}

function irASolicitar() {
  router.push('/pasajero/solicitar')
}

onMounted(() => {
  cargarPerfil()

  if (String(route.query.eliminar || '') === '1') {
    abrirEliminarCuenta()
  }
})
</script>

<style scoped>
.profile-page {
  min-height: 100%;
  background: transparent;
}

.profile-card {
  overflow: hidden;
  border-radius: 18px;
}

.profile-header {
  background:
    linear-gradient(
      135deg,
      #1b5e20,
      #2e7d32
    );
}

.profile-identity {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.profile-file-input {
  display: none;
}

.profile-avatar {
  font-size: 30px;
  font-weight: 800;
  border: 4px solid #ffffff;
  box-shadow:
    0 8px 22px rgba(46, 125, 50, 0.16);
}

.profile-grid {
  display: grid;
  grid-template-columns:
    repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.profile-data {
  min-height: 78px;
  padding: 14px;
  display: flex;
  align-items: center;
  gap: 13px;
  background: #fafcf9;
  border: 1px solid #d8e5d5;
  border-radius: 13px;
}

.profile-data > .q-icon {
  flex: 0 0 auto;
  font-size: 25px;
}

.profile-data > div {
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.profile-data span {
  color: #7a8879;
  font-size: 11px;
}

.profile-data strong {
  margin-top: 2px;
  color: #273b29;
  overflow-wrap: anywhere;
}

.account-note {
  color: #365239;
  background: #eef7ec;
  border: 1px solid #d5e5d2;
}

.min-width-zero {
  min-width: 0;
}

.danger-zone {
  padding: 16px;
  border: 1px solid #f0caca;
  border-radius: 14px;
  background: #fffafa;
}

.delete-dialog {
  width: 100%;
  max-width: 520px;
  border-radius: 16px;
}

@media (max-width: 599px) {
  .profile-page {
    padding: 9px 9px 20px;
  }

  .profile-card {
    border-radius: 14px;
  }

  .profile-grid {
    grid-template-columns: 1fr;
  }
}
</style>

<style>
.motrix-perfil-claro { min-height: 100%; background: #f6f8f7 !important; color: #192e22; }
.motrix-perfil-contenido { width: min(100%, 700px); margin: auto; padding: 12px 17px 36px; }
.motrix-perfil-cabecera { min-height: 54px; }
.motrix-perfil-identidad { display: flex; align-items: center; flex-direction: column; text-align: center; padding: 17px 0 24px; }
.motrix-perfil-avatar { cursor: pointer; font-size: 34px; box-shadow: 0 0 0 5px #fff, 0 7px 22px #16452b20; }
.motrix-perfil-email { font-size: 13px; color: #7b8981; overflow-wrap: anywhere; }
.motrix-perfil-foto-btn { border-radius: 24px !important; }
.motrix-perfil-seccion { font-weight: 750; margin: 17px 0 10px; color: #25382b; }
.motrix-perfil-lista { overflow: hidden; border: 1px solid #e0e7e2; border-radius: 16px; background: #fff; box-shadow: 0 2px 9px #153a2a0a; }
.motrix-perfil-lista .q-item, .motrix-perfil-lista .q-expansion-item__container > .q-item { min-height: 57px; color: #26392c; }
.motrix-perfil-lista .q-item:not(:last-child), .motrix-perfil-lista .q-expansion-item:not(:last-child) { border-bottom: 1px solid #edf1ed; }
.motrix-perfil-lista .q-item__section--avatar { min-width: 42px; color: #38764e; }
.motrix-perfil-lista .q-item__section--side { color: #98a59b; }
.motrix-perfil-detalles { padding: 8px 16px 17px 56px; color: #55655b; line-height: 1.9; font-size: 13px; }
.motrix-perfil-sesion { margin-top: 21px; }
.motrix-perfil-pie { padding-top: 23px; text-align: center; color: #91a097; font-size: 12px; }
</style>