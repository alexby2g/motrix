<template>
  <q-page class="profile-page q-pa-md q-pa-lg-md">
    <div class="row justify-center">
      <div class="col-12 col-md-9 col-lg-7">
        <q-card class="profile-card shadow-2">
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
                icon="account_circle"
                size="52px"
                class="q-mr-md"
              />

              <div class="col min-width-zero">
                <div class="text-h5 text-weight-bold">
                  Mi perfil
                </div>

                <div class="text-caption text-green-1">
                  Cuenta de pasajero MOTRIX
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

          <q-card-section
            v-if="cargando && !usuario"
            class="column flex-center q-pa-xl"
          >
            <q-spinner
              color="green-8"
              size="48px"
            />

            <div class="text-grey-7 q-mt-md">
              Cargando tu perfil...
            </div>
          </q-card-section>

          <q-card-section
            v-else
            class="q-pa-md q-pa-lg-md"
          >
            <div class="profile-identity">
              <q-avatar
                size="92px"
                color="green-1"
                text-color="green-9"
                class="profile-avatar"
              >
                {{ iniciales }}
              </q-avatar>

              <div class="text-center">
                <div class="text-h5 text-weight-bold text-grey-9 q-mt-md">
                  {{ nombreUsuario }}
                </div>

                <q-chip
                  color="green-1"
                  text-color="green-9"
                  icon="person_pin_circle"
                  class="q-mt-sm text-weight-bold"
                >
                  Pasajero
                </q-chip>
              </div>
            </div>

            <q-separator class="q-my-lg" />

            <div class="text-subtitle1 text-weight-bold text-green-9 q-mb-md">
              Datos de la cuenta
            </div>

            <div class="profile-grid">
              <div class="profile-data">
                <q-icon
                  name="person"
                  color="green-8"
                />

                <div>
                  <span>Nombre</span>
                  <strong>
                    {{ nombreUsuario }}
                  </strong>
                </div>
              </div>

              <div class="profile-data">
                <q-icon
                  name="phone_android"
                  color="green-8"
                />

                <div>
                  <span>Celular / usuario</span>
                  <strong>
                    {{ usuario?.telefono || usuario?.nickname || 'No registrado' }}
                  </strong>
                </div>
              </div>

              <div class="profile-data">
                <q-icon
                  name="email"
                  color="green-8"
                />

                <div>
                  <span>Correo (opcional)</span>
                  <strong>
                    {{ usuario?.email || 'No registrado' }}
                  </strong>
                </div>
              </div>

              <div class="profile-data">
                <q-icon
                  name="badge"
                  color="green-8"
                />

                <div>
                  <span>Código de pasajero</span>
                  <strong>
                    {{
                      usuario?.pasajero_id
                        ? `#${usuario.pasajero_id}`
                        : 'No disponible'
                    }}
                  </strong>
                </div>
              </div>
            </div>

            <q-banner
              rounded
              class="account-note q-mt-lg"
            >
              <template #avatar>
                <q-icon
                  name="verified_user"
                  color="green-8"
                  size="30px"
                />
              </template>

              <div class="text-weight-bold text-green-9">
                Cuenta protegida
              </div>

              <div class="text-caption text-grey-7">
                Esta pantalla consulta tu sesión autenticada mediante
                MOTRIX. La edición de datos personales se habilitará
                cuando definamos qué campos puede modificar el pasajero.
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
                  icon="history"
                  label="Mis viajes"
                  class="full-width"
                  no-caps
                  @click="irAHistorial"
                />
              </div>

              <div class="col-12 col-sm-6">
                <q-btn
                  color="green-8"
                  icon="two_wheeler"
                  label="Solicitar mototaxi"
                  class="full-width"
                  unelevated
                  no-caps
                  @click="irASolicitar"
                />
              </div>
            </div>

            <q-separator class="q-my-xl" />

            <div class="danger-zone">
              <div class="text-subtitle1 text-weight-bold text-red-8">
                Privacidad y eliminación de cuenta
              </div>
              <div class="text-caption text-grey-7 q-mt-xs q-mb-md">
                Puedes eliminar tu acceso de pasajero y solicitar la eliminación o anonimización
                de tus datos personales. Esta acción es permanente.
              </div>
              <q-btn
                outline
                color="red-7"
                icon="delete_forever"
                label="Eliminar mi cuenta"
                no-caps
                @click="abrirEliminarCuenta"
              />
            </div>

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
  useRoute,
  useRouter
} from 'vue-router'

import {
  api
} from 'src/boot/axios.js'

const $q = useQuasar()
const router = useRouter()
const route = useRoute()

const cargando = ref(false)
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

    await router.replace('/inicio')
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
