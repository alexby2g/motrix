<template>
  <q-layout view="hHh lpR fFf">
    <q-page-container>
      <q-page class="complete-page">
        <div class="complete-shell">
          <section class="complete-branding">
            <q-btn
              flat
              no-caps
              color="white"
              icon="arrow_back"
              label="Volver"
              class="back-btn"
              @click="volver"
            />

            <div class="branding-center">
              <q-avatar
                v-if="profile?.picture"
                size="92px"
                class="google-avatar shadow-4"
              >
                <img :src="profile.picture" alt="Foto de Google">
              </q-avatar>

              <q-avatar
                v-else
                size="92px"
                color="white"
                text-color="green-8"
                icon="person"
                class="shadow-4"
              />

              <div class="text-h4 text-weight-bold q-mt-lg">
                {{ profile?.name || 'Cuenta Google' }}
              </div>

              <div class="text-body1 text-green-2 q-mt-xs">
                {{ profile?.email }}
              </div>

              <p class="branding-copy">
                Google ya verificó tu identidad. Solo necesitamos unos
                datos locales para completar tu perfil de pasajero MOTRIX.
              </p>
            </div>
          </section>

          <section class="complete-form-side">
            <div class="complete-card">
              <div class="row items-center no-wrap q-mb-lg">
                <q-avatar
                  color="green-1"
                  text-color="green-9"
                  icon="verified_user"
                  size="56px"
                />

                <div class="q-ml-md">
                  <div class="text-h5 text-weight-bold text-grey-9">
                    Completa tu registro
                  </div>
                  <div class="text-body2 text-grey-6">
                    Estos datos se guardarán en tu perfil de pasajero.
                  </div>
                </div>
              </div>

              <q-form @submit.prevent="completarRegistro">
                <q-input
                  v-model.trim="form.ci"
                  outlined
                  label="Cédula de identidad (opcional)"
                  lazy-rules
                  class="q-mb-md"
                >
                  <template #prepend>
                    <q-icon name="badge" color="green-8" />
                  </template>
                </q-input>

                <q-input
                  v-model.trim="form.telefono"
                  outlined
                  label="Teléfono *"
                  lazy-rules
                  :rules="[requerido]"
                  class="q-mb-md"
                >
                  <template #prepend>
                    <q-icon name="phone" color="green-8" />
                  </template>
                </q-input>

                <q-input
                  v-model.trim="form.direccion"
                  outlined
                  label="Dirección (opcional)"
                  class="q-mb-lg"
                >
                  <template #prepend>
                    <q-icon name="home" color="green-8" />
                  </template>
                </q-input>

                <q-banner rounded class="bg-blue-1 text-blue-9 q-mb-lg">
                  <template #avatar>
                    <q-icon name="info" color="blue-7" />
                  </template>
                  Esta opción crea únicamente una cuenta de pasajero.
                  Las cuentas de conductor siguen requiriendo afiliación
                  y validación previa en MOTRIX.
                </q-banner>

                <q-btn
                  type="submit"
                  color="green-8"
                  icon="check_circle"
                  label="Completar y entrar"
                  no-caps
                  rounded
                  unelevated
                  class="full-width complete-btn"
                  :loading="cargando"
                />
              </q-form>
            </div>
          </section>
        </div>
      </q-page>
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import { api } from '../boot/axios.js'
import {
  guardarSesionMotrix,
  limpiarGooglePendiente,
  obtenerGooglePendiente
} from '../services/googleAuth.js'

const $q = useQuasar()
const router = useRouter()
const cargando = ref(false)
const profile = ref(null)
const registrationToken = ref(null)

const form = reactive({
  ci: '',
  telefono: '',
  direccion: ''
})

const requerido = valor => (
  Boolean(String(valor || '').trim()) || 'Este campo es obligatorio'
)

onMounted(() => {
  const pendiente = obtenerGooglePendiente()
  registrationToken.value = pendiente.registrationToken
  profile.value = pendiente.profile

  if (!registrationToken.value || !profile.value) {
    router.replace('/inicio')
  }
})

function primerMensaje(error) {
  const errores = error.response?.data?.errors
  if (errores) {
    return Object.values(errores)
      .flat()
      .find(Boolean)
  }

  return error.response?.data?.message
    || 'No se pudo completar el registro con Google.'
}

async function completarRegistro() {
  if (!registrationToken.value) return

  cargando.value = true

  try {
    const respuesta = await api.post('/auth/google/complete', {
      registration_token: registrationToken.value,
      ci: form.ci,
      telefono: form.telefono,
      direccion: form.direccion,
      device_name: 'MOTRIX Google Web'
    })

    guardarSesionMotrix(respuesta.data)
    limpiarGooglePendiente()

    $q.notify({
      type: 'positive',
      position: 'top',
      icon: 'check_circle',
      message: respuesta.data.message
        || 'Tu cuenta de pasajero fue creada correctamente.'
    })

    await router.replace('/pasajero')
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      icon: 'error',
      message: primerMensaje(error)
    })
  } finally {
    cargando.value = false
  }
}

function volver() {
  limpiarGooglePendiente()
  router.push('/inicio')
}
</script>

<style scoped>
.complete-page {
  min-height: 100vh;
  background: #eef7e9;
}

.complete-shell {
  min-height: 100vh;
  display: grid;
  grid-template-columns: minmax(360px, 0.85fr) minmax(500px, 1.15fr);
}

.complete-branding {
  position: relative;
  padding: 34px clamp(28px, 5vw, 72px);
  display: flex;
  flex-direction: column;
  background: linear-gradient(150deg, #0c3b12, #1b5e20 55%, #388e3c);
  color: white;
}

.back-btn {
  align-self: flex-start;
}

.branding-center {
  margin: auto 0;
  max-width: 480px;
}

.google-avatar {
  border: 5px solid rgba(255,255,255,.85);
}

.branding-copy {
  margin-top: 24px;
  color: #e8f5e9;
  font-size: 16px;
  line-height: 1.7;
}

.complete-form-side {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 36px;
}

.complete-card {
  width: min(560px, 100%);
  padding: 34px;
  background: white;
  border-radius: 22px;
  box-shadow: 0 24px 55px rgba(27, 94, 32, 0.14);
}

.complete-btn {
  min-height: 50px;
}

@media (max-width: 850px) {
  .complete-shell {
    grid-template-columns: 1fr;
  }

  .complete-branding {
    min-height: auto;
    padding: 24px;
  }

  .branding-center {
    margin: 30px 0 20px;
    text-align: center;
  }

  .complete-form-side {
    padding: 20px 16px 34px;
  }

  .complete-card {
    padding: 26px 22px;
  }
}
</style>
