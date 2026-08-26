<template>
  <q-layout view="hHh lpR fFf">
    <q-page-container>
      <q-page class="register-page">
    <div class="register-shell">
      <section class="register-branding">
        <button
          type="button"
          class="back-button"
          @click="volver"
        >
          <q-icon name="arrow_back" />
          Volver
        </button>

        <div class="branding-content">
          <q-avatar
            size="72px"
            class="bg-white shadow-4 q-mb-lg"
          >
            <q-icon
              name="two_wheeler"
              color="green-8"
              size="42px"
            />
          </q-avatar>

          <div class="text-h3 text-weight-bold text-white">
            MOTRIX
          </div>

          <div class="text-h5 text-weight-medium text-green-1 q-mt-md">
            Crea tu cuenta de pasajero
          </div>

          <p class="branding-text">
            Regístrate una sola vez y después podrás solicitar viajes,
            consultar tu historial y calificar tus servicios.
          </p>

          <div class="benefit-list">
            <div>
              <q-icon name="place" />
              Solicita desde tu ubicación
            </div>
            <div>
              <q-icon name="payments" />
              Conoce la tarifa antes de confirmar
            </div>
            <div>
              <q-icon name="verified_user" />
              Viaja con conductores registrados
            </div>
          </div>
        </div>
      </section>

      <section class="register-form-panel">
        <q-card flat class="register-card">
          <q-card-section class="q-pa-none q-mb-lg">
            <div class="text-h5 text-weight-bold text-grey-9">
              Crear cuenta
            </div>
            <div class="text-body2 text-grey-6 q-mt-xs">
              Esta opción crea únicamente una cuenta de pasajero.
            </div>
          </q-card-section>

          <q-form
            greedy
            @submit.prevent="registrar"
          >
            <div class="form-grid">
              <q-input
                v-model.trim="form.nombre"
                outlined
                label="Nombre *"
                autocomplete="given-name"
                :rules="[reglaObligatoria('El nombre es obligatorio')]"
              >
                <template #prepend>
                  <q-icon name="person" color="green-8" />
                </template>
              </q-input>

              <q-input
                v-model.trim="form.apellidos"
                outlined
                label="Apellidos *"
                autocomplete="family-name"
                :rules="[reglaObligatoria('Los apellidos son obligatorios')]"
              >
                <template #prepend>
                  <q-icon name="badge" color="green-8" />
                </template>
              </q-input>

              <q-input
                v-model.trim="form.ci"
                outlined
                label="Cédula de identidad *"
                autocomplete="off"
                :rules="[reglaObligatoria('El CI es obligatorio')]"
              >
                <template #prepend>
                  <q-icon name="credit_card" color="green-8" />
                </template>
              </q-input>

              <q-input
                v-model.trim="form.telefono"
                outlined
                label="Teléfono *"
                type="tel"
                autocomplete="tel"
                :rules="[reglaObligatoria('El teléfono es obligatorio')]"
              >
                <template #prepend>
                  <q-icon name="phone" color="green-8" />
                </template>
              </q-input>
            </div>

            <q-input
              v-model.trim="form.direccion"
              outlined
              label="Dirección (opcional)"
              autocomplete="street-address"
              class="q-mt-xs"
            >
              <template #prepend>
                <q-icon name="home" color="green-8" />
              </template>
            </q-input>

            <q-input
              v-model.trim="form.email"
              outlined
              label="Correo electrónico *"
              type="email"
              autocomplete="email"
              class="q-mt-sm"
              :rules="reglasEmail"
            >
              <template #prepend>
                <q-icon name="mail" color="green-8" />
              </template>
            </q-input>

            <div class="form-grid q-mt-sm">
              <q-input
                v-model="form.password"
                outlined
                label="Contraseña *"
                autocomplete="new-password"
                :type="mostrarPassword ? 'text' : 'password'"
                :rules="reglasPassword"
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

              <q-input
                v-model="form.password_confirmation"
                outlined
                label="Confirmar contraseña *"
                autocomplete="new-password"
                :type="mostrarPassword ? 'text' : 'password'"
                :rules="reglasConfirmacion"
              >
                <template #prepend>
                  <q-icon name="lock_reset" color="green-8" />
                </template>
              </q-input>
            </div>

            <q-banner
              rounded
              class="bg-blue-1 text-blue-9 q-mt-md"
            >
              <template #avatar>
                <q-icon name="info" color="blue-7" />
              </template>
              Si eres mototaxista, no debes registrarte aquí como conductor.
              Tu cuenta será habilitada por MOTRIX después de validar tu
              afiliación y tu motocicleta.
            </q-banner>

            <q-btn
              type="submit"
              color="green-8"
              icon="person_add"
              label="Crear mi cuenta"
              unelevated
              rounded
              no-caps
              class="full-width register-button q-mt-lg"
              :loading="cargando"
            />

            <div class="login-link q-mt-lg">
              <span>¿Ya tienes una cuenta?</span>
              <q-btn
                flat
                dense
                color="green-8"
                label="Iniciar sesión"
                no-caps
                @click="irLogin"
              />
            </div>
          </q-form>
        </q-card>
      </section>
    </div>
      </q-page>
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'

import { api } from '../boot/axios.js'

const $q = useQuasar()
const router = useRouter()

const cargando = ref(false)
const mostrarPassword = ref(false)

const form = reactive({
  nombre: '',
  apellidos: '',
  ci: '',
  telefono: '',
  direccion: '',
  email: '',
  password: '',
  password_confirmation: ''
})

function reglaObligatoria(mensaje) {
  return valor =>
    Boolean(String(valor || '').trim())
    || mensaje
}

const reglasEmail = [
  reglaObligatoria('El correo electrónico es obligatorio'),
  valor =>
    /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(valor || ''))
    || 'Ingresa un correo electrónico válido'
]

const reglasPassword = [
  reglaObligatoria('La contraseña es obligatoria'),
  valor =>
    String(valor || '').length >= 8
    || 'La contraseña debe tener al menos 8 caracteres'
]

const reglasConfirmacion = [
  reglaObligatoria('Debes confirmar la contraseña'),
  valor =>
    valor === form.password
    || 'Las contraseñas no coinciden'
]

function guardarSesion(token, user) {
  localStorage.setItem('motrix_token', token)
  localStorage.setItem('motrix_user', JSON.stringify(user))

  localStorage.removeItem('mototaxista_id')

  if (user?.pasajero_id) {
    localStorage.setItem(
      'pasajero_id',
      String(user.pasajero_id)
    )
  } else {
    localStorage.removeItem('pasajero_id')
  }
}

async function registrar() {
  cargando.value = true

  try {
    const respuesta = await api.post(
      '/auth/registro-pasajero',
      {
        ...form,
        device_name: 'MOTRIX Pasajero'
      }
    )

    const { token, user } = respuesta.data

    guardarSesion(token, user)

    $q.notify({
      type: 'positive',
      position: 'top',
      icon: 'check_circle',
      message: 'Tu cuenta de pasajero fue creada correctamente.'
    })

    await router.replace('/pasajero')
  } catch (error) {
    console.error('Error al crear cuenta de pasajero:', error)

    const errores = error.response?.data?.errors
    const primerError = errores
      ? Object.values(errores).flat().find(Boolean)
      : null

    $q.notify({
      type: 'negative',
      position: 'top',
      icon: 'error',
      timeout: 4500,
      message:
        primerError
        || error.response?.data?.message
        || 'No se pudo crear la cuenta. Revisa los datos e inténtalo nuevamente.'
    })
  } finally {
    cargando.value = false
  }
}

function volver() {
  router.push('/inicio')
}

function irLogin() {
  router.push('/login')
}
</script>

<style scoped>
.register-page {
  min-height: 100vh;
  background: #f1f8e9;
}

.register-shell {
  min-height: 100vh;
  display: grid;
  grid-template-columns: minmax(330px, 0.72fr) minmax(0, 1.28fr);
}

.register-branding {
  position: relative;
  overflow: hidden;
  min-height: 100vh;
  padding: 38px clamp(28px, 5vw, 70px);
  display: flex;
  flex-direction: column;
  background: linear-gradient(155deg, #0d4714 0%, #1b5e20 55%, #388e3c 100%);
}

.register-branding::after {
  content: '';
  position: absolute;
  width: 420px;
  height: 420px;
  right: -170px;
  bottom: -130px;
  border: 70px solid rgba(255, 255, 255, 0.07);
  border-radius: 50%;
}

.back-button {
  position: relative;
  z-index: 2;
  width: fit-content;
  padding: 8px 0;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #e8f5e9;
  background: transparent;
  border: 0;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}

.branding-content {
  position: relative;
  z-index: 2;
  margin: auto 0;
}

.branding-text {
  max-width: 440px;
  margin: 18px 0 28px;
  color: #dcedc8;
  font-size: 15px;
  line-height: 1.65;
}

.benefit-list {
  display: grid;
  gap: 13px;
}

.benefit-list > div {
  display: flex;
  align-items: center;
  gap: 11px;
  color: #f1f8e9;
  font-size: 13px;
  font-weight: 600;
}

.benefit-list .q-icon {
  width: 34px;
  height: 34px;
  display: grid;
  place-items: center;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.13);
  font-size: 19px;
}

.register-form-panel {
  min-height: 100vh;
  padding: 38px clamp(22px, 5vw, 76px);
  display: flex;
  align-items: center;
  justify-content: center;
}

.register-card {
  width: 100%;
  max-width: 760px;
  padding: 34px;
  border: 1px solid rgba(46, 125, 50, 0.12);
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.96);
  box-shadow: 0 24px 60px rgba(27, 94, 32, 0.12);
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.register-button {
  min-height: 52px;
  font-weight: 700;
}

.login-link {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  color: #777;
  font-size: 13px;
}

@media (max-width: 900px) {
  .register-shell {
    display: block;
  }

  .register-branding {
    min-height: auto;
    padding: 24px 22px 30px;
  }

  .branding-content {
    margin-top: 34px;
  }

  .benefit-list {
    display: none;
  }

  .register-form-panel {
    min-height: auto;
    padding: 22px 16px 36px;
  }

  .register-card {
    max-width: 680px;
    padding: 26px 22px;
  }
}

@media (max-width: 620px) {
  .form-grid {
    grid-template-columns: 1fr;
    gap: 4px;
  }

  .register-card {
    padding: 24px 18px;
    border-radius: 20px;
  }
}
</style>
