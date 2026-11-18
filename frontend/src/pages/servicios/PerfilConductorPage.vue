<template>
  <q-page class="motrix-perfil-claro motrix-perfil-conductor">
    <div class="motrix-perfil-contenido">
      <header class="motrix-perfil-cabecera row items-center justify-between">
        <q-btn flat round icon="arrow_back" color="grey-9" aria-label="Volver" @click="volver" />
        <div class="text-h6 text-weight-bold">Mi perfil</div>
        <q-btn flat round icon="refresh" color="green-8" :loading="cargando" aria-label="Actualizar perfil" @click="cargarPerfil" />
      </header>

      <q-linear-progress v-if="cargando" indeterminate color="green-8" />

      <div class="motrix-perfil-identidad">
        <q-avatar size="94px" color="green-8" text-color="white" class="motrix-perfil-avatar"
          :class="{ 'cursor-pointer': fotoPerfilUrl && !fotoPerfilError }" @click="abrirVisorFoto">
          <img v-if="fotoPerfilUrl && !fotoPerfilError" :src="fotoPerfilUrl"
            :alt="`Foto de ${nombreCompleto}`" class="profile-photo" @error="fotoPerfilError = true">
          <span v-else>{{ iniciales }}</span>
        </q-avatar>
        <div class="text-h6 text-weight-bold q-mt-md text-center">{{ nombreCompleto }}</div>
        <div class="motrix-perfil-subtitulo">Conductor MOTRIX · Trinidad, Beni</div>
        <div class="motrix-conductor-estados">
          <q-chip dense icon="two_wheeler" color="green-1" text-color="green-9">
            Chaleco {{ valor(perfil?.nro_chaleco) }}
          </q-chip>
          <q-chip dense :icon="perfil?.disponible ? 'wifi' : 'wifi_off'"
            :color="perfil?.disponible ? 'green-1' : 'grey-2'"
            :text-color="perfil?.disponible ? 'green-9' : 'grey-8'">
            {{ perfil?.disponible ? 'En línea' : 'Fuera de línea' }}
          </q-chip>
        </div>
        <q-btn outline no-caps dense color="green-8" icon="photo_camera"
          :label="fotoPerfilUrl ? 'Cambiar foto' : 'Agregar foto'"
          class="motrix-perfil-foto-btn q-mt-sm" :loading="subiendoFotoPerfil" @click="abrirSelectorFotoPerfil" />
        <input ref="fotoPerfilInput" type="file" accept="image/jpeg,image/png,image/webp"
          class="profile-file-input" @change="seleccionarFotoPerfil">
      </div>

      <div class="motrix-perfil-seccion">Cuenta</div>
      <div class="motrix-perfil-lista">
        <q-expansion-item icon="manage_accounts" label="Mis datos personales" expand-icon="chevron_right">
          <div class="motrix-perfil-detalles">
            <div><strong>Nombre:</strong> {{ nombreCompleto }}</div>
            <div><strong>Teléfono:</strong> {{ valor(perfil?.telefono || perfil?.persona?.telefono || usuario?.telefono) }}</div>
            <div><strong>CI:</strong> {{ valor(perfil?.persona?.ci) }}</div>
            <div><strong>Usuario:</strong> {{ valor(usuario?.nickname || usuario?.telefono) }}</div>
            <div><strong>Correo:</strong> {{ valor(usuario?.email) }}</div>
            <div><strong>Código:</strong> {{ usuario?.mototaxista_id ? '#' + usuario.mototaxista_id : valor(perfil?.id) }}</div>
          </div>
        </q-expansion-item>
        <q-item clickable v-ripple @click="router.push('/cuenta/cambiar-contrasena')">
          <q-item-section avatar><q-icon name="lock_outline" /></q-item-section>
          <q-item-section>Cambiar contraseña</q-item-section>
          <q-item-section side><q-icon name="chevron_right" /></q-item-section>
        </q-item>
      </div>

      <div class="motrix-perfil-seccion">Mi actividad</div>
      <div class="motrix-perfil-lista">
        <q-expansion-item icon="two_wheeler" label="Información del mototaxista" expand-icon="chevron_right">
          <div class="motrix-perfil-detalles">
            <div><strong>Chaleco:</strong> {{ valor(perfil?.nro_chaleco) }}</div>
            <div><strong>Sindicato:</strong> {{ valor(perfil?.sindicato?.nombre) }}</div>
            <div><strong>Estado:</strong> {{ valor(perfil?.estado) }}</div>
            <div><strong>Última conexión:</strong> {{ formatearUltimaConexion(perfil?.ultima_conexion) }}</div>
            <div><strong>QR institucional:</strong> {{ perfil?.codigo_qr ? 'Generado' : 'No generado' }}</div>
          </div>
        </q-expansion-item>
        <q-expansion-item icon="verified_user" label="Habilitación y aportes" expand-icon="chevron_right">
          <div class="motrix-perfil-detalles">
            <q-banner rounded :class="perfil?.habilitado_para_operar ? 'bg-green-1 text-green-10' : 'bg-orange-1 text-orange-10'">
              <template #avatar>
                <q-icon :name="perfil?.habilitado_para_operar ? 'verified_user' : 'gpp_maybe'"
                  :color="perfil?.habilitado_para_operar ? 'green-8' : 'orange-9'" />
              </template>
              <strong>Estado sindical: {{ perfil?.estado_sindical || 'No habilitado' }}</strong>
              <div v-if="!perfil?.habilitado_para_operar && perfil?.motivo_inhabilitacion" class="text-caption">
                {{ perfil.motivo_inhabilitacion }}
              </div>
            </q-banner>
            <div><strong>Documentación:</strong> {{ perfil?.documentacion_en_regla ? 'En regla' : 'Pendiente' }}</div>
            <div><strong>Aportes:</strong> {{ perfil?.aportes_al_dia ? 'Al día' : 'Pendientes' }}</div>
          </div>
        </q-expansion-item>
        <q-item clickable v-ripple @click="irAGanancias">
          <q-item-section avatar><q-icon name="account_balance_wallet" /></q-item-section>
          <q-item-section>Ver ganancias</q-item-section>
          <q-item-section side><q-icon name="chevron_right" /></q-item-section>
        </q-item>
        <q-item clickable v-ripple @click="volver">
          <q-item-section avatar><q-icon name="two_wheeler" /></q-item-section>
          <q-item-section>Volver a operación</q-item-section>
          <q-item-section side><q-icon name="chevron_right" /></q-item-section>
        </q-item>
      </div>

      <div class="motrix-perfil-seccion">Cobros</div>
      <div class="motrix-perfil-lista">
        <q-expansion-item icon="qr_code_2" label="Mi QR de cobro"
          :caption="qrPagoUrl ? 'QR registrado · puedes actualizarlo' : 'Registra un QR para pagos digitales'"
          expand-icon="chevron_right">
          <div class="motrix-qr-formulario">
            <q-card flat bordered class="qr-payment-card">
              <q-card-section>
                <div class="row q-col-gutter-lg items-start">
                  <div class="col-12 col-md-5 text-center">
                    <div class="text-subtitle2 text-weight-bold text-green-9 q-mb-sm">
                      Imagen que verá el pasajero
                    </div>

                    <img
                      v-if="qrPagoUrl"
                      :src="qrPagoUrl"
                      alt="QR de cobro del mototaxista"
                      class="profile-qr-image"
                    >

                    <div
                      v-else
                      class="qr-empty-state"
                    >
                      <q-icon name="qr_code_2" size="52px" color="grey-5" />
                      <div class="text-weight-medium text-grey-7 q-mt-sm">
                        Aún no registraste un QR de cobro
                      </div>
                    </div>
                  </div>

                  <div class="col-12 col-md-7">
                    <q-input
                      v-model.trim="qrPagoMetodo"
                      outlined
                      dense
                      label="Banco o billetera"
                      placeholder="Ej.: QR bancario, Yape, Yasta"
                      class="q-mb-sm"
                    >
                      <template #prepend>
                        <q-icon name="account_balance_wallet" />
                      </template>
                    </q-input>

                    <q-input
                      v-model.trim="qrPagoTitular"
                      outlined
                      dense
                      label="Titular del QR"
                      :placeholder="nombreCompleto"
                      class="q-mb-sm"
                    >
                      <template #prepend>
                        <q-icon name="badge" />
                      </template>
                    </q-input>

                    <q-file
                      v-model="qrPagoArchivo"
                      outlined
                      dense
                      clearable
                      accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                      label="Seleccionar imagen QR"
                      class="q-mb-md"
                    >
                      <template #prepend>
                        <q-icon name="image" />
                      </template>
                    </q-file>

                    <div class="text-caption text-grey-7 q-mb-md">
                      Sube una imagen clara del QR. Se mostrará cuando el cobro
                      sea por QR o pago mixto. Máximo 4 MB.
                    </div>

                    <div class="row q-col-gutter-sm">
                      <div class="col-12 col-sm">
                        <q-btn
                          color="green-8"
                          icon="save"
                          label="Guardar QR de cobro"
                          class="full-width"
                          unelevated
                          no-caps
                          :loading="subiendoQrPago"
                          :disable="eliminandoQrPago"
                          @click="guardarQrPago"
                        />
                      </div>

                      <div
                        v-if="qrPagoUrl"
                        class="col-12 col-sm-auto"
                      >
                        <q-btn
                          outline
                          color="negative"
                          icon="delete"
                          label="Eliminar"
                          class="full-width"
                          no-caps
                          :loading="eliminandoQrPago"
                          :disable="subiendoQrPago"
                          @click="eliminarQrPago"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </q-card-section>
            </q-card>
          </div>
        </q-expansion-item>
      </div>

      <div class="motrix-perfil-seccion">Ayuda y seguridad</div>
      <div class="motrix-perfil-lista">
        <q-expansion-item icon="privacy_tip" label="Privacidad y eliminación de cuenta" expand-icon="chevron_right">
          <div class="motrix-eliminar-wrap"><ConductorAccountDelete /></div>
        </q-expansion-item>
      </div>

      <div class="motrix-perfil-lista motrix-perfil-sesion">
        <q-item clickable v-ripple :disable="cerrandoSesion" @click="cerrarSesion">
          <q-item-section avatar><q-icon name="logout" color="negative" /></q-item-section>
          <q-item-section class="text-negative text-weight-medium">Cerrar sesión</q-item-section>
          <q-item-section side>
            <q-spinner v-if="cerrandoSesion" color="negative" size="20px" />
            <q-icon v-else name="chevron_right" />
          </q-item-section>
        </q-item>
      </div>

      <div class="motrix-perfil-pie">MOTRIX · Movilidad en Trinidad</div>
    </div>
    <PhotoViewerDialog v-model="visorFoto" :src="fotoPerfilUrl" :title="nombreCompleto" />
  </q-page>
</template>

<script setup>
import { fechaHoraUtcBolivia } from 'src/utils/motrixDate.js'

import ConductorAccountDelete from 'src/components/ConductorAccountDelete.vue'
import PhotoViewerDialog from 'src/components/PhotoViewerDialog.vue'
import {
  computed,
  onMounted,
  ref
} from 'vue'

import {
  useQuasar
} from 'quasar'

import {
  useRouter
} from 'vue-router'

import { api } from 'src/boot/axios.js'
import { desvincularPushMotrix } from 'src/services/pushNotifications.js'
import { ES_APLICACION_NATIVA } from 'src/config/runtime.js'
const $q = useQuasar()
const router = useRouter()

const cargando = ref(false)
const cerrandoSesion = ref(false)
const perfil = ref(null)
const fotoPerfilError = ref(false)
const visorFoto = ref(false)
const fotoPerfilInput = ref(null)
const subiendoFotoPerfil = ref(false)
const qrPagoArchivo = ref(null)
const qrPagoMetodo = ref('')
const qrPagoTitular = ref('')
const subiendoQrPago = ref(false)
const eliminandoQrPago = ref(false)
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

const nombreCompleto = computed(() => {
  const persona =
    perfil.value?.persona

  const nombre =
    String(
      persona?.nombre || ''
    ).trim()

  const apellidos =
    String(
      persona?.apellidos || ''
    ).trim()

  const unido =
    [nombre, apellidos]
      .filter(Boolean)
      .join(' ')
      .trim()

  return (
    unido
    || usuario.value?.persona_nombre
    || usuario.value?.name
    || usuario.value?.email
    || 'Mototaxista MOTRIX'
  )
})

function resolverUrlImagen(ruta) {
  const valor = String(ruta || '').trim()

  if (!valor) return ''

  if (/^https?:\/\//i.test(valor)) {
    return valor
  }

  const baseApi = String(
    api.defaults.baseURL || ''
  )
    .replace(/\/api\/?$/i, '')
    .replace(/\/$/, '')

  let limpia = valor
    .replace(/^\/+/, '')
    .replace(/^public\//i, '')

  if (limpia.startsWith('storage/')) {
    return `${baseApi}/${limpia}`
  }

  return `${baseApi}/storage/${limpia}`
}

const fotoPerfilUrl = computed(() => {
  const persona = perfil.value?.persona

  const imagenes = Array.isArray(persona?.imagenes)
    ? [...persona.imagenes]
    : []

  imagenes.sort(
    (a, b) => Number(b?.id || 0) - Number(a?.id || 0)
  )

  const ruta = (
    imagenes[0]?.ruta
    || persona?.foto
    || persona?.imagen
    || persona?.foto_url
    || persona?.imagen_url
    || ''
  )

  return resolverUrlImagen(ruta)
})

const qrPagoUrl = computed(() => {
  return resolverUrlImagen(
    perfil.value?.qr_pago_ruta
  )
})

function abrirVisorFoto() {
  if (fotoPerfilUrl.value && !fotoPerfilError.value) {
    visorFoto.value = true
  }
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
    String(nombreCompleto.value)
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
    const [
      respuestaPerfil,
      respuestaUsuario
    ] = await Promise.all([
      api.get(
        '/conductor/perfil',
        {
          params: {
            _t: Date.now()
          }
        }
      ),
      api.get('/auth/me')
    ])

    perfil.value =
      respuestaPerfil.data
      || null

    fotoPerfilError.value = false
    qrPagoArchivo.value = null
    qrPagoMetodo.value = String(
      perfil.value?.qr_pago_metodo
      || ''
    )
    qrPagoTitular.value = String(
      perfil.value?.qr_pago_titular
      || nombreCompleto.value
      || ''
    )

    const datosUsuario =
      respuestaUsuario?.data?.user

    if (datosUsuario) {
      usuario.value = datosUsuario

      localStorage.setItem(
        'motrix_user',
        JSON.stringify(datosUsuario)
      )
    }
  } catch (error) {
    console.error(
      'Error cargando perfil del conductor:',
      error
    )

    $q.notify({
      type: 'negative',
      position: 'top',
      message:
        error?.response?.data?.mensaje
        || error?.response?.data?.message
        || 'No se pudo cargar el perfil del conductor.'
    })
  } finally {
    cargando.value = false
  }
}

async function guardarQrPago() {
  if (subiendoQrPago.value || eliminandoQrPago.value) return

  if (!qrPagoArchivo.value && !qrPagoUrl.value) {
    $q.notify({
      type: 'warning',
      position: 'top',
      message: 'Selecciona una imagen QR antes de guardar.'
    })
    return
  }

  subiendoQrPago.value = true

  try {
    const formData = new FormData()

    if (qrPagoArchivo.value) {
      formData.append(
        'imagen',
        qrPagoArchivo.value
      )
    }

    formData.append(
      'metodo',
      qrPagoMetodo.value
      || 'QR / billetera móvil'
    )

    formData.append(
      'titular',
      qrPagoTitular.value
      || nombreCompleto.value
    )

    const respuesta = await api.post(
      '/conductor/qr-pago',
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      }
    )

    $q.notify({
      type: 'positive',
      position: 'top',
      message:
        respuesta?.data?.message
        || 'QR de cobro actualizado correctamente.'
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
        || 'No se pudo guardar el QR de cobro.'
    })
  } finally {
    subiendoQrPago.value = false
  }
}

function eliminarQrPago() {
  if (
    !qrPagoUrl.value
    || subiendoQrPago.value
    || eliminandoQrPago.value
  ) {
    return
  }

  $q.dialog({
    title: 'Eliminar QR de cobro',
    message:
      'El pasajero dejará de ver este QR en pagos digitales. ¿Deseas continuar?',
    cancel: true,
    persistent: true
  }).onOk(async () => {
    eliminandoQrPago.value = true

    try {
      const respuesta = await api.delete(
        '/conductor/qr-pago'
      )

      $q.notify({
        type: 'positive',
        position: 'top',
        message:
          respuesta?.data?.message
          || 'QR de cobro eliminado correctamente.'
      })

      qrPagoArchivo.value = null
      qrPagoMetodo.value = ''
      qrPagoTitular.value = ''

      await cargarPerfil()
    } catch (error) {
      $q.notify({
        type: 'negative',
        position: 'top',
        message:
          error?.response?.data?.message
          || 'No se pudo eliminar el QR de cobro.'
      })
    } finally {
      eliminandoQrPago.value = false
    }
  })
}

function valor(dato) {
  if (
    dato === null
    || dato === undefined
    || String(dato).trim() === ''
  ) {
    return 'No registrado'
  }

  return String(dato)
}


function formatearUltimaConexion(valorFecha) {
  return fechaHoraUtcBolivia(valorFecha, 'Sin registro')
}

function volver() {
  router.push('/conductor')
}

function irAGanancias() {
  router.push('/conductor/ganancias')
}

async function cerrarSesion() {
  if (cerrandoSesion.value) return
  cerrandoSesion.value = true
  try {
    await desvincularPushMotrix()
    await api.post('/auth/logout')
  } catch (error) {
    if (error?.response?.status !== 401) {
      console.warn('No se pudo cerrar la sesión en el servidor:', error)
    }
  } finally {
    for (const campo of ['motrix_token', 'motrix_user', 'mototaxista_id', 'pasajero_id']) {
      localStorage.removeItem(campo)
    }
    cerrandoSesion.value = false
    await router.replace(ES_APLICACION_NATIVA ? '/login' : '/presentacion')
  }
}

onMounted(() => {
  cargarPerfil()
})
</script>
<style scoped>
.motrix-perfil-claro {
  min-height: 100%;
  background: #f6f8f7 !important;
  color: #192e22;
}
.motrix-perfil-contenido {
  width: min(100%, 700px);
  margin: 0 auto;
  padding: 12px 17px 36px;
}
.motrix-perfil-cabecera { min-height: 54px; }
.motrix-perfil-identidad {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 17px 0 24px;
}
.motrix-perfil-avatar {
  font-size: 32px;
  font-weight: 800;
  box-shadow: 0 0 0 5px #fff, 0 7px 22px #16452b20;
  cursor: pointer;
}
.profile-photo { width: 100%; height: 100%; object-fit: cover; }
.profile-file-input { display: none; }
.motrix-perfil-subtitulo { font-size: 13px; color: #778a80; margin-top: 3px; }
.motrix-conductor-estados { display: flex; flex-wrap: wrap; justify-content: center; gap: 3px; margin-top: 12px; }
.motrix-perfil-foto-btn { border-radius: 24px !important; }
.motrix-perfil-seccion {
  font-weight: 750;
  margin: 17px 0 10px;
  color: #25382b;
}
.motrix-perfil-lista {
  overflow: hidden;
  border: 1px solid #e0e7e2;
  border-radius: 16px;
  background: #fff;
  box-shadow: 0 2px 9px #153a2a0a;
}
.motrix-perfil-lista :deep(.q-item) { min-height: 57px; color: #26392c; }
.motrix-perfil-lista :deep(.q-item__section--avatar) { min-width: 42px; color: #38764e; }
.motrix-perfil-lista :deep(.q-item__section--side) { color: #98a59b; }
.motrix-perfil-lista :deep(.q-item:not(:last-child)) { border-bottom: 1px solid #edf1ed; }
.motrix-perfil-detalles { padding: 8px 16px 17px 56px; color: #55655b; line-height: 1.9; font-size: 13px; }
.motrix-perfil-detalles > div { overflow-wrap: anywhere; }
.motrix-perfil-sesion { margin-top: 21px; }
.motrix-perfil-pie { padding-top: 23px; text-align: center; color: #91a097; font-size: 12px; }
.motrix-qr-formulario { padding: 14px 12px 16px; }
.motrix-eliminar-wrap { padding: 0 16px 16px; }
.qr-payment-card { border-radius: 16px; overflow: hidden; }
.profile-qr-image {
  display: block;
  width: min(100%, 320px);
  max-height: 360px;
  object-fit: contain;
  margin: 0 auto;
  padding: 10px;
  border-radius: 14px;
  border: 1px solid #dfe7e1;
  background: #fff;
}
.qr-empty-state {
  min-height: 170px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 18px;
  border: 1px dashed #cfd8d2;
  border-radius: 14px;
  background: #fafcfb;
}
@media (max-width: 599px) {
  .motrix-perfil-contenido { padding: 8px 13px 32px; }
  .motrix-perfil-detalles { padding-left: 16px; }
}
</style>
<style>
/* El SDK de Google Maps queda detrás del WebView: perfil siempre opaco. */
body.motrix-google-native-map .q-page.motrix-perfil-conductor,
body.motrix-conductor-google-native .q-page.motrix-perfil-conductor {
  background: #f6f8f7 !important;
}
</style>
