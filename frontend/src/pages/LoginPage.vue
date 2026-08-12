<template>
  <q-layout view="hHh lpr fff">
    <q-page-container>
      <q-page class="login-page">
        <div class="login-wrapper">
          <!-- IDENTIDAD VISUAL MOTRIX -->
          <section class="login-branding">
            <q-icon
              name="two_wheeler"
              class="branding-icono-fondo"
            />

            <div class="branding-contenido">
              <q-avatar
                size="68px"
                class="bg-white q-mb-md shadow-3"
              >
                <q-icon
                  name="two_wheeler"
                  color="green-8"
                  size="39px"
                />
              </q-avatar>

              <div class="text-h4 text-weight-bold text-white">
                MOTRIX
              </div>

              <div class="text-subtitle2 text-green-2 q-mt-xs">
                Sistema Web y Aplicación Móvil para el Registro y
                Solicitud de Mototaxistas
              </div>

              <q-list class="q-mt-xl branding-list">
                <q-item class="q-px-none">
                  <q-item-section avatar top>
                    <q-icon
                      name="groups"
                      color="green-2"
                      size="22px"
                    />
                  </q-item-section>

                  <q-item-section>
                    <q-item-label class="text-white">
                      Registro y administración de sindicatos
                    </q-item-label>
                  </q-item-section>
                </q-item>

                <q-item class="q-px-none">
                  <q-item-section avatar top>
                    <q-icon
                      name="verified_user"
                      color="green-2"
                      size="22px"
                    />
                  </q-item-section>

                  <q-item-section>
                    <q-item-label class="text-white">
                      Verificación de mototaxistas mediante QR
                    </q-item-label>
                  </q-item-section>
                </q-item>

                <q-item class="q-px-none">
                  <q-item-section avatar top>
                    <q-icon
                      name="route"
                      color="green-2"
                      size="22px"
                    />
                  </q-item-section>

                  <q-item-section>
                    <q-item-label class="text-white">
                      Solicitud, seguimiento y seguridad del viaje
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </div>

            <div class="branding-footer text-green-3 text-caption">
              Instituto Tecnológico Superior José Castillo
              · Trinidad - Beni 2026
            </div>
          </section>

          <!-- FORMULARIO -->
          <section class="login-form-panel">
            <div class="login-form-inner">
              <div class="login-mobile-logo q-mb-lg">
                <q-avatar
                  size="58px"
                  color="green-1"
                  text-color="green-9"
                  icon="two_wheeler"
                />

                <div>
                  <div class="text-h5 text-weight-bold text-green-9">
                    MOTRIX
                  </div>

                  <div class="text-caption text-grey-6">
                    Plataforma integrada de mototaxis
                  </div>
                </div>
              </div>

              <div class="text-h5 text-weight-bold text-grey-9">
                Bienvenido de nuevo
              </div>

              <div class="text-body2 text-grey-6 q-mb-lg">
                Ingresa con tu correo electrónico o nickname.
              </div>

              <q-form
                greedy
                @submit.prevent="iniciarSesion"
              >
                <q-input
                  v-model.trim="form.login"
                  label="Correo o nickname"
                  outlined
                  autocomplete="username"
                  class="q-mb-md"
                  lazy-rules
                  :rules="reglasLogin"
                >
                  <template #prepend>
                    <q-icon
                      name="account_circle"
                      color="green-8"
                    />
                  </template>
                </q-input>

                <q-input
                  v-model="form.password"
                  label="Contraseña"
                  outlined
                  autocomplete="current-password"
                  :type="mostrarPassword ? 'text' : 'password'"
                  lazy-rules
                  :rules="reglasPassword"
                >
                  <template #prepend>
                    <q-icon
                      name="lock"
                      color="green-8"
                    />
                  </template>

                  <template #append>
                    <q-icon
                      :name="
                        mostrarPassword
                          ? 'visibility_off'
                          : 'visibility'
                      "
                      class="cursor-pointer"
                      color="grey-7"
                      @click="
                        mostrarPassword = !mostrarPassword
                      "
                    />
                  </template>
                </q-input>

                <q-btn
                  type="submit"
                  color="green-8"
                  label="Ingresar"
                  icon="login"
                  unelevated
                  rounded
                  no-caps
                  class="full-width q-mt-lg login-btn"
                  size="md"
                  :loading="cargando"
                />
              </q-form>

              <q-banner
                rounded
                class="bg-green-1 text-green-9 q-mt-lg"
              >
                <template #avatar>
                  <q-icon name="shield" color="green-8" />
                </template>

                El acceso se adapta automáticamente al perfil de
                administrador, conductor o pasajero.
              </q-banner>

              <div class="text-center text-caption text-grey-5 q-mt-lg">
                <q-icon
                  name="verified_user"
                  size="14px"
                  class="q-mr-xs"
                />
                Acceso restringido a usuarios autorizados
              </div>
            </div>
          </section>
        </div>
      </q-page>
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useQuasar } from 'quasar'
import { useRoute, useRouter } from 'vue-router'

import { api } from '../boot/axios.js'

const $q = useQuasar()
const route = useRoute()
const router = useRouter()

const cargando = ref(false)
const mostrarPassword = ref(false)

const form = reactive({
  login: '',
  password: ''
})

const reglasLogin = [
  valor =>
    Boolean(String(valor || '').trim())
    || 'El correo o nickname es obligatorio'
]

const reglasPassword = [
  valor =>
    Boolean(valor)
    || 'La contraseña es obligatoria'
]

function rutaInicialPorRol(role) {
  let rol = String(role || '')
    .trim()
    .toLowerCase()

  if (rol === 'admin') {
    rol = 'admin_general'
  }

  if (rol === 'cajero') {
    rol = 'admin_servicios'
  }

  if (rol === 'admin_general') {
    return '/monitoreo'
  }

  if (rol === 'admin_servicios') {
    return '/solicitudes'
  }

  if (rol === 'secretario') {
    return '/mototaxistas'
  }

  if (rol === 'conductor') {
    return '/conductor'
  }

  if (rol === 'pasajero') {
    return '/pasajero'
  }

  return '/'
}

async function iniciarSesion() {
  cargando.value = true

  try {
    const respuesta = await api.post(
      '/auth/login',
      {
        login: form.login,
        password: form.password,
        device_name: 'MOTRIX Web'
      }
    )

    const { token, user } = respuesta.data

    localStorage.setItem(
      'motrix_token',
      token
    )

    localStorage.setItem(
      'motrix_user',
      JSON.stringify(user)
    )

    if (user.mototaxista_id) {
      localStorage.setItem(
        'mototaxista_id',
        String(user.mototaxista_id)
      )
    } else {
      localStorage.removeItem(
        'mototaxista_id'
      )
    }

    if (user.pasajero_id) {
      localStorage.setItem(
        'pasajero_id',
        String(user.pasajero_id)
      )
    } else {
      localStorage.removeItem(
        'pasajero_id'
      )
    }

    $q.notify({
      type: 'positive',
      position: 'top',
      icon: 'check_circle',
      message:
        `Bienvenido, ${
          user.persona_nombre
          || user.name
          || user.nickname
          || 'Usuario MOTRIX'
        }`
    })

    const destinoSolicitado =
      typeof route.query.redirect === 'string'
        ? route.query.redirect
        : null

    await router.replace(
      destinoSolicitado
      || rutaInicialPorRol(user.role)
    )
  } catch (error) {
    console.error(
      'Error de inicio de sesión:',
      error
    )

    const erroresValidacion =
      error.response?.data?.errors

    const primerError =
      erroresValidacion
        ? Object.values(erroresValidacion)
          .flat()
          .find(Boolean)
        : null

    $q.notify({
      type: 'negative',
      position: 'top',
      icon: 'error',
      message:
        primerError
        || error.response?.data?.message
        || 'No se pudo iniciar sesión. Verifica tus datos.'
    })
  } finally {
    cargando.value = false
  }
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  background: #f1f8e9;
}

.login-wrapper {
  display: flex;
  width: 100%;
  min-height: 100vh;
}

.login-branding {
  position: relative;
  overflow: hidden;
  flex: 1 1 42%;
  max-width: 500px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 48px 44px;
  background:
    linear-gradient(
      150deg,
      #1b5e20 0%,
      #2e7d32 55%,
      #388e3c 100%
    );
}

.login-branding::after {
  content: '';
  position: absolute;
  right: 0;
  bottom: 0;
  left: 0;
  height: 5px;
  background:
    linear-gradient(
      90deg,
      #c62828,
      rgba(198, 40, 40, 0.15) 70%,
      transparent
    );
}

.branding-icono-fondo {
  position: absolute;
  right: -60px;
  bottom: -60px;
  color: white;
  font-size: 320px;
  opacity: 0.08;
  transform: rotate(-15deg);
}

.branding-contenido {
  position: relative;
  z-index: 1;
}

.branding-list :deep(.q-item) {
  min-height: 48px;
}

.branding-footer {
  position: relative;
  z-index: 1;
  margin-top: 40px;
  line-height: 1.5;
}

.login-form-panel {
  flex: 1 1 58%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 32px;
  background:
    radial-gradient(
      circle at 85% 15%,
      rgba(102, 187, 106, 0.14),
      transparent 28%
    ),
    #f1f8e9;
}

.login-form-inner {
  width: 100%;
  max-width: 390px;
  padding: 34px 32px;
  background: rgba(255, 255, 255, 0.94);
  border: 1px solid rgba(46, 125, 50, 0.13);
  border-radius: 20px;
  box-shadow:
    0 22px 55px rgba(27, 94, 32, 0.14);
  backdrop-filter: blur(10px);
}

.login-mobile-logo {
  display: none;
  align-items: center;
  gap: 12px;
}

.login-btn {
  min-height: 48px;
  transition:
    transform 0.15s ease,
    box-shadow 0.15s ease;
}

.login-btn:hover {
  transform: translateY(-1px);
  box-shadow:
    0 10px 22px rgba(27, 94, 32, 0.22);
}

@media (max-width: 850px) {
  .login-wrapper {
    flex-direction: column;
  }

  .login-branding {
    max-width: 100%;
    flex: 0 0 auto;
    padding: 30px 24px;
    align-items: center;
    text-align: center;
  }

  .branding-list,
  .branding-footer,
  .branding-icono-fondo {
    display: none;
  }

  .login-form-panel {
    flex: 1 1 auto;
    padding: 28px 18px 40px;
  }

  .login-form-inner {
    padding: 28px 23px;
  }

  .login-mobile-logo {
    display: flex;
  }
}
</style>
