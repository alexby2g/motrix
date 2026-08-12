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
              >
                {{ iniciales }}
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

            <q-separator class="q-my-lg" />

            <div class="section-title">
              Cuenta de acceso
            </div>

            <div class="data-grid">
              <div class="data-card">
                <q-icon
                  name="email"
                  color="green-8"
                />

                <div>
                  <span>Correo</span>
                  <strong>
                    {{ valor(usuario?.email) }}
                  </strong>
                </div>
              </div>

              <div class="data-card">
                <q-icon
                  name="alternate_email"
                  color="green-8"
                />

                <div>
                  <span>Nickname</span>
                  <strong>
                    {{ valor(usuario?.nickname) }}
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
                Los datos de afiliación, chaleco, QR y registro del
                mototaxista provienen del módulo administrativo.
                Desde esta pantalla el conductor solamente consulta
                su propia información.
              </div>
            </q-banner>

            <div class="row q-col-gutter-sm q-mt-md">
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
          </q-card-section>
        </q-card>
      </div>
    </div>
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
  useRouter
} from 'vue-router'

import {
  api
} from 'src/boot/axios.js'

const $q = useQuasar()
const router = useRouter()

const cargando = ref(false)
const perfil = ref(null)
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
  if (!valorFecha) {
    return 'No registrada'
  }

  const fecha =
    new Date(valorFecha)

  if (Number.isNaN(fecha.getTime())) {
    return String(valorFecha)
  }

  return new Intl.DateTimeFormat(
    'es-BO',
    {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    }
  ).format(fecha)
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
</style>
