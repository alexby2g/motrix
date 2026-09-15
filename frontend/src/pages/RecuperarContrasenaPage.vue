<template>
  <div class="recovery-page q-pa-md flex flex-center">
    <q-card class="recovery-card shadow-3">
      <q-card-section class="bg-green-9 text-white">
        <div class="row items-center no-wrap">
          <q-avatar color="white" text-color="green-9" icon="lock_reset" size="50px" class="q-mr-md" />
          <div>
            <div class="text-h6 text-weight-bold">Recuperar contraseña</div>
            <div class="text-caption text-green-1">Pasajeros y mototaxistas MOTRIX</div>
          </div>
        </div>
      </q-card-section>

      <q-linear-progress v-if="cargando" indeterminate color="green-8" />

      <q-card-section class="q-pa-lg">
        <q-stepper v-model="paso" flat animated color="green-8" header-nav>
          <q-step :name="1" title="Cuenta" icon="person_search" :done="paso > 1">
            <div class="text-body2 text-grey-7 q-mb-md">
              Ingresa el número de celular registrado en tu cuenta. Si la cuenta dispone de un medio de recuperación configurado, recibirás un código de 6 dígitos.
            </div>
            <q-input
              v-model.trim="login"
              outlined
              label="Celular registrado"
              inputmode="tel"
              autocomplete="tel"
              :disable="cargando"
              @keyup.enter="solicitarCodigo"
            >
              <template #prepend><q-icon name="phone_android" color="green-8" /></template>
            </q-input>
            <q-btn
              color="green-8"
              unelevated
              no-caps
              icon="send_to_mobile"
              label="Enviar código"
              class="full-width q-mt-md"
              :loading="cargando"
              :disable="!login"
              @click="solicitarCodigo"
            />
          </q-step>

          <q-step :name="2" title="Código" icon="pin" :done="paso > 2">
            <q-banner rounded class="bg-green-1 text-green-10 q-mb-md">
              {{ destino
                ? `Código enviado a ${destino}.`
                : 'Si la cuenta dispone de un medio de recuperación, revisa el contacto registrado.' }}
            </q-banner>
            <q-input
              v-model="codigo"
              outlined
              label="Código de 6 dígitos"
              inputmode="numeric"
              maxlength="6"
              mask="######"
              :disable="cargando"
              @keyup.enter="verificarCodigo"
            >
              <template #prepend><q-icon name="password" color="green-8" /></template>
            </q-input>
            <q-btn
              color="green-8"
              unelevated
              no-caps
              icon="verified"
              label="Verificar código"
              class="full-width q-mt-md"
              :loading="cargando"
              :disable="codigo.length !== 6"
              @click="verificarCodigo"
            />
            <q-btn flat no-caps color="green-8" label="Reenviar código" class="full-width q-mt-sm" :disable="cargando" @click="solicitarCodigo" />
          </q-step>

          <q-step :name="3" title="Nueva contraseña" icon="key">
            <q-input
              v-model="password"
              outlined
              label="Nueva contraseña"
              :type="mostrarPassword ? 'text' : 'password'"
              autocomplete="new-password"
              :disable="cargando"
              class="q-mb-md"
            >
              <template #prepend><q-icon name="lock" color="green-8" /></template>
              <template #append>
                <q-icon :name="mostrarPassword ? 'visibility_off' : 'visibility'" class="cursor-pointer" @click="mostrarPassword = !mostrarPassword" />
              </template>
            </q-input>
            <q-input
              v-model="passwordConfirmation"
              outlined
              label="Confirmar nueva contraseña"
              :type="mostrarPassword ? 'text' : 'password'"
              autocomplete="new-password"
              :disable="cargando"
              @keyup.enter="restablecer"
            />
            <div class="text-caption text-grey-6 q-mt-xs">Usa al menos 8 caracteres.</div>
            <q-btn
              color="green-8"
              unelevated
              no-caps
              icon="lock_open"
              label="Guardar nueva contraseña"
              class="full-width q-mt-md"
              :loading="cargando"
              :disable="password.length < 8 || password !== passwordConfirmation"
              @click="restablecer"
            />
          </q-step>
        </q-stepper>
      </q-card-section>

      <q-separator />
      <q-card-actions align="center" class="q-pa-md">
        <q-btn flat no-caps color="green-8" icon="arrow_back" label="Volver a iniciar sesión" to="/login" />
      </q-card-actions>
    </q-card>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import { api } from '../boot/axios.js'

const $q = useQuasar()
const router = useRouter()
const paso = ref(1)
const login = ref('')
const codigo = ref('')
const resetToken = ref('')
const destino = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const mostrarPassword = ref(false)
const cargando = ref(false)

function mensajeError(error, defecto) {
  return error?.response?.data?.message
    || Object.values(error?.response?.data?.errors || {}).flat().find(Boolean)
    || defecto
}

function normalizarCelular(valor) {
  const numero = String(valor || '').replace(/\D+/g, '')

  if (numero.startsWith('00591') && numero.length === 13) {
    return numero.slice(5)
  }

  if (numero.startsWith('591') && numero.length === 11) {
    return numero.slice(3)
  }

  return numero
}

function celularValido(valor) {
  const original = String(valor || '').trim()

  if (!original || !/^[+\d\s()-]+$/.test(original)) {
    return false
  }

  return /^[0-9]{7,15}$/.test(normalizarCelular(original))
}

async function solicitarCodigo() {
  if (cargando.value) return

  if (!celularValido(login.value)) {
    $q.notify({ type: 'warning', message: 'Ingresa el número de celular registrado en tu cuenta.' })
    return
  }

  login.value = normalizarCelular(login.value)
  cargando.value = true

  try {
    const { data } = await api.post('/auth/recuperacion/solicitar', { login: login.value })
    destino.value = data?.destino || ''
    codigo.value = ''
    resetToken.value = ''
    paso.value = 2
    $q.notify({ type: 'positive', message: data?.message || 'Revisa tu medio de recuperación.' })
  } catch (error) {
    $q.notify({ type: 'negative', message: mensajeError(error, 'No se pudo iniciar la recuperación.') })
  } finally {
    cargando.value = false
  }
}

async function verificarCodigo() {
  if (codigo.value.length !== 6 || cargando.value) return
  cargando.value = true
  try {
    const { data } = await api.post('/auth/recuperacion/verificar', {
      login: login.value,
      codigo: codigo.value
    })
    resetToken.value = data?.reset_token || ''
    paso.value = 3
    $q.notify({ type: 'positive', message: data?.message || 'Código verificado.' })
  } catch (error) {
    $q.notify({ type: 'negative', message: mensajeError(error, 'El código no es válido o venció.') })
  } finally {
    cargando.value = false
  }
}

async function restablecer() {
  if (!resetToken.value || password.value.length < 8 || password.value !== passwordConfirmation.value || cargando.value) return
  cargando.value = true
  try {
    const { data } = await api.post('/auth/recuperacion/restablecer', {
      login: login.value,
      reset_token: resetToken.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value
    })
    $q.notify({ type: 'positive', message: data?.message || 'Contraseña actualizada.' })
    await router.replace('/login')
  } catch (error) {
    $q.notify({ type: 'negative', message: mensajeError(error, 'No se pudo restablecer la contraseña.') })
  } finally {
    cargando.value = false
  }
}
</script>

<style scoped>
.recovery-page { background: linear-gradient(145deg, #edf7f0, #f8faf9); min-height: 100vh; }
.recovery-card { width: min(100%, 560px); border-radius: 18px; overflow: hidden; }
</style>
