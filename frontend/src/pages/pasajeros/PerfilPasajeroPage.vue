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
                  name="email"
                  color="green-8"
                />

                <div>
                  <span>Correo</span>
                  <strong>
                    {{ usuario?.email || 'No disponible' }}
                  </strong>
                </div>
              </div>

              <div class="profile-data">
                <q-icon
                  name="alternate_email"
                  color="green-8"
                />

                <div>
                  <span>Nickname</span>
                  <strong>
                    {{ usuario?.nickname || 'No registrado' }}
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
