<template>
  <q-page class="driver-profile-page q-pa-md q-pa-lg-md">
    <div class="row justify-center">
      <div class="col-12 col-md-9 col-lg-8">
        <q-card class="profile-shell shadow-2">
          <q-card-section class="profile-header text-white">
            <div class="row items-center no-wrap">
              <q-btn
                flat
                round
                icon="arrow_back"
                color="white"
                class="q-mr-sm"
                @click="volver"
              />

              <q-avatar
                color="white"
                text-color="green-8"
                icon="two_wheeler"
                size="52px"
                class="q-mr-md"
              />

              <div class="col min-width-zero">
                <div class="text-h5 text-weight-bold">
                  Mi perfil
                </div>

                <div class="text-caption text-green-1">
                  Cuenta de mototaxista MOTRIX
                </div>
              </div>

              <q-btn
                flat
                round
                icon="refresh"
                color="white"
                :loading="cargando"
                @click="cargarPerfil"
              >
                <q-tooltip>
                  Actualizar información
                </q-tooltip>
              </q-btn>
            </div>
          </q-card-section>

          <q-linear-progress
            v-if="cargando"
            indeterminate
            color="green-8"
          />

          <q-card-section class="q-pa-md q-pa-lg-md">
            <div class="profile-identity">
              <q-avatar
                size="98px"
                color="green-1"
                text-color="green-9"
                class="profile-avatar"
                :class="{ 'cursor-pointer': Boolean(fotoPerfilUrl && !fotoPerfilError) }"
                @click="abrirVisorFoto"
              >
                <img
                  v-if="fotoPerfilUrl && !fotoPerfilError"
                  :src="fotoPerfilUrl"
                  :alt="`Foto de ${nombreCompleto}`"
                  class="profile-photo"
                  @error="fotoPerfilError = true"
                />

                <span v-else>
                  {{ iniciales }}
                </span>
              </q-avatar>

              <div class="text-h5 text-weight-bold text-grey-9 q-mt-md text-center">
                {{ nombreCompleto }}
              </div>

              <div class="row justify-center q-gutter-sm q-mt-sm">
                <q-chip
                  color="green-1"
                  text-color="green-9"
                  icon="two_wheeler"
                  class="text-weight-bold"
                >
                  Mototaxista
                </q-chip>

                <q-chip
                  :color="
                    perfil?.estado === 'Activo'
                      ? 'green-1'
                      : 'red-1'
                  "
                  :text-color="
                    perfil?.estado === 'Activo'
                      ? 'green-9'
                      : 'red-9'
                  "
                  :icon="
                    perfil?.estado === 'Activo'
                      ? 'verified'
                      : 'warning'
                  "
                  class="text-weight-bold"
                >
                  {{ perfil?.estado || 'Sin estado' }}
                </q-chip>

                <q-chip
                  :color="
                    perfil?.disponible
                      ? 'green-1'
                      : 'grey-2'
                  "
                  :text-color="
                    perfil?.disponible
                      ? 'green-9'
                      : 'grey-7'
                  "
                  :icon="
                    perfil?.disponible
                      ? 'wifi'
                      : 'wifi_off'
                  "
                >
                  {{
                    perfil?.disponible
                      ? 'En línea'
                      : 'Fuera de línea'
                  }}
                </q-chip>
              </div>
            </div>

            <q-separator class="q-my-lg" />

            <div class="section-title">
              Información del mototaxista
            </div>

            <div class="data-grid">
              <div class="data-card">
                <q-icon
                  name="badge"
                  color="green-8"
                />

                <div>
                  <span>N.º de chaleco</span>
                  <strong>
                    {{ valor(perfil?.nro_chaleco) }}
                  </strong>
                </div>
              </div>

              <div class="data-card">
                <q-icon
                  name="groups"
                  color="green-8"
                />

                <div>
                  <span>Sindicato</span>
                  <strong>
                    {{ valor(perfil?.sindicato?.nombre) }}
                  </strong>
                </div>
              </div>

              <div class="data-card">
                <q-icon
                  name="phone"
                  color="green-8"
                />

                <div>
                  <span>Teléfono</span>
                  <strong>
                    {{
                      valor(
                        perfil?.telefono
                        || perfil?.persona?.telefono
                      )
                    }}
                  </strong>
                </div>
              </div>

              <div class="data-card">
                <q-icon
                  name="fingerprint"
                  color="green-8"
                />

                <div>
                  <span>CI</span>
                  <strong>
                    {{ valor(perfil?.persona?.ci) }}
                  </strong>
                </div>
              </div>

              <div class="data-card">
                <q-icon
                  name="qr_code_2"
                  color="green-8"
                />

                <div>
                  <span>Código QR</span>
                  <strong>
                    {{
                      perfil?.codigo_qr
                        ? 'Generado'
                        : 'No generado'
                    }}
                  </strong>
                </div>
              </div>

              <div class="data-card">
                <q-icon
                  name="schedule"
                  color="green-8"
                />

                <div>
                  <span>Última conexión</span>
                  <strong>
                    {{ formatearFechaHora(perfil?.ultima_conexion) }}
                  </strong>
                </div>
              </div>
            </div>

            <q-banner
              rounded
              class="q-mt-md"
              :class="perfil?.habilitado_para_operar ? 'bg-green-1 text-green-10' : 'bg-orange-1 text-orange-10'"
            >
              <template #avatar>
                <q-icon
                  :name="perfil?.habilitado_para_operar ? 'verified_user' : 'gpp_maybe'"
                  :color="perfil?.habilitado_para_operar ? 'green-8' : 'orange-9'"
                />
              </template>

              <div class="row items-center q-col-gutter-sm">
                <div class="col-12 col-sm">
                  <div class="text-weight-bold">
                    Estado sindical: {{ perfil?.estado_sindical || 'No habilitado' }}
                  </div>
                  <div
                    v-if="!perfil?.habilitado_para_operar && perfil?.motivo_inhabilitacion"
                    class="text-caption q-mt-xs"
                  >
                    {{ perfil.motivo_inhabilitacion }}
                  </div>
                </div>

                <div class="col-auto">
                  <q-chip
                    dense
                    :color="perfil?.documentacion_en_regla ? 'green-2' : 'orange-2'"
                    :text-color="perfil?.documentacion_en_regla ? 'green-10' : 'orange-10'"
                    :icon="perfil?.documentacion_en_regla ? 'description' : 'pending_actions'"
                  >
                    Documentación {{ perfil?.documentacion_en_regla ? 'en regla' : 'pendiente' }}
                  </q-chip>
                  <q-chip
                    dense
                    :color="perfil?.aportes_al_dia ? 'green-2' : 'orange-2'"
                    :text-color="perfil?.aportes_al_dia ? 'green-10' : 'orange-10'"
                    :icon="perfil?.aportes_al_dia ? 'payments' : 'money_off'"
                  >
                    Aportes {{ perfil?.aportes_al_dia ? 'al día' : 'pendientes' }}
                  </q-chip>
                </div>
              </div>
            </q-banner>

            <q-separator class="q-my-lg" />

            <div class="section-title">
              QR de cobro
            </div>

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

            <q-separator class="q-my-lg" />

            <div class="section-title">
              Cuenta de acceso
            </div>

            <div class="data-grid">
              <div class="data-card">
                <q-icon
                  name="phone_android"
                  color="green-8"
                />

                <div>
                  <span>Celular / usuario</span>
                  <strong>
                    {{ valor(usuario?.telefono || usuario?.nickname || perfil?.telefono || perfil?.persona?.telefono) }}
                  </strong>
                </div>
              </div>

              <div class="data-card">
                <q-icon
                  name="email"
                  color="green-8"
                />

                <div>
                  <span>Correo (opcional)</span>
                  <strong>
                    {{ valor(usuario?.email) }}
                  </strong>
                </div>
              </div>

              <div class="data-card">
                <q-icon
                  name="vpn_key"
                  color="green-8"
                />

                <div>
                  <span>Código de conductor</span>
                  <strong>
                    {{
                      usuario?.mototaxista_id
                        ? `#${usuario.mototaxista_id}`
                        : valor(perfil?.id)
                    }}
                  </strong>
                </div>
              </div>

              <div class="data-card">
                <q-icon
                  name="manage_accounts"
                  color="green-8"
                />

                <div>
                  <span>Tipo de cuenta</span>
                  <strong>
                    Conductor MOTRIX
                  </strong>
                </div>
              </div>
            </div>

            <q-banner
              rounded
              class="profile-note q-mt-lg"
            >
              <template #avatar>
                <q-icon
                  name="info"
                  color="green-8"
                />
              </template>

              <div class="text-weight-bold text-green-9">
                Información de registro
              </div>

              <div class="text-caption text-grey-7">
                Los datos de afiliación, chaleco y QR institucional de
                verificación provienen del módulo administrativo. El QR de
                cobro puede ser administrado por el propio conductor desde
                esta pantalla.
              </div>
            </q-banner>

            <div class="row q-col-gutter-sm q-mt-md">
              <div class="col-12">
                <q-btn
                  outline
                  color="green-8"
                  icon="lock_reset"
                  label="Cambiar contraseña"
                  class="full-width"
                  no-caps
                  @click="router.push('/cuenta/cambiar-contrasena')"
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-btn
                  outline
                  color="green-8"
                  icon="account_balance_wallet"
                  label="Ver ganancias"
                  class="full-width"
                  no-caps
                  @click="irAGanancias"
                />
              </div>

              <div class="col-12 col-sm-6">
                <q-btn
                  color="green-8"
                  icon="two_wheeler"
                  label="Volver a operación"
                  class="full-width"
                  unelevated
                  no-caps
                  @click="volver"
                />
              </div>
            </div>

            <ConductorAccountDelete />
</q-card-section>
        </q-card>
      </div>
    </div>

    <PhotoViewerDialog
      v-model="visorFoto"
      :src="fotoPerfilUrl"
      :title="nombreCompleto"
    />
  </q-page>
</template>

<script setup>
import { fechaHoraDDMMYYYY } from 'src/utils/motrixDate.js'

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
const $q = useQuasar()
const router = useRouter()

const cargando = ref(false)
const perfil = ref(null)
const fotoPerfilError = ref(false)
const visorFoto = ref(false)
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

function formatearFechaHora(valorFecha) {
  return fechaHoraDDMMYYYY(valorFecha)
}

function volver() {
  router.push('/conductor')
}

function irAGanancias() {
  router.push('/conductor/ganancias')
}

onMounted(() => {
  cargarPerfil()
})
</script>

<style scoped>
.driver-profile-page {
  min-height: 100%;
  background: transparent;
}

.profile-shell {
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

.profile-avatar {
  font-size: 31px;
  font-weight: 900;
  border: 4px solid #ffffff;
  box-shadow:
    0 8px 24px rgba(46, 125, 50, 0.18);
}

.profile-photo {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.section-title {
  margin-bottom: 12px;
  color: #1b5e20;
  font-size: 16px;
  font-weight: 800;
}

.data-grid {
  display: grid;
  grid-template-columns:
    repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.data-card {
  min-height: 78px;
  padding: 14px;
  display: flex;
  align-items: center;
  gap: 13px;
  background: #fafcf9;
  border: 1px solid #d7e4d3;
  border-radius: 13px;
}

.data-card > .q-icon {
  flex: 0 0 auto;
  font-size: 25px;
}

.data-card > div {
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.data-card span {
  color: #788678;
  font-size: 11px;
}

.data-card strong {
  margin-top: 2px;
  color: #263a28;
  overflow-wrap: anywhere;
}

.profile-note {
  color: #365239;
  background: #eef7ec;
  border: 1px solid #d5e5d2;
}

.min-width-zero {
  min-width: 0;
}

@media (max-width: 599px) {
  .driver-profile-page {
    padding: 9px 9px 22px;
  }

  .profile-shell {
    border-radius: 14px;
  }

  .data-grid {
    grid-template-columns: 1fr;
  }
}

.qr-payment-card {
  border-radius: 16px;
  overflow: hidden;
}

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
  min-height: 210px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 24px;
  border: 1px dashed #cfd8d2;
  border-radius: 14px;
  background: #fafcfb;
}

</style>
