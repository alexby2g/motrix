<template>
  <q-page class="password-page q-pa-md q-pa-lg-md">
    <div class="row justify-center">
      <div class="col-12 col-sm-9 col-md-7 col-lg-5">
        <q-card class="password-card shadow-2">
          <q-card-section class="password-header text-white">
            <div class="row items-center no-wrap">
              <q-btn flat round icon="arrow_back" color="white" class="q-mr-sm" @click="volver" />
              <q-avatar color="white" text-color="green-8" icon="lock_reset" size="52px" class="q-mr-md" />
              <div>
                <div class="text-h5 text-weight-bold">Cambiar contraseña</div>
                <div class="text-caption text-green-1">Protege tu cuenta MOTRIX</div>
              </div>
            </div>
          </q-card-section>

          <q-card-section class="q-pa-lg">
            <q-banner rounded class="bg-green-1 text-green-10 q-mb-lg">
              <template #avatar>
                <q-icon name="verified_user" color="green-8" />
              </template>
              Usa al menos 8 caracteres. Al cambiarla se cerrarán las demás sesiones de tu cuenta.
            </q-banner>

            <q-form greedy @submit.prevent="guardar">
              <q-input
                v-model="form.password_actual"
                outlined
                :type="mostrarActual ? 'text' : 'password'"
                label="Contraseña actual *"
                autocomplete="current-password"
                :rules="[requerido]"
                class="q-mb-sm"
              >
                <template #prepend><q-icon name="lock" color="green-8" /></template>
                <template #append>
                  <q-btn flat round dense :icon="mostrarActual ? 'visibility_off' : 'visibility'" @click="mostrarActual = !mostrarActual" />
                </template>
              </q-input>

              <q-input
                v-model="form.password"
                outlined
                :type="mostrarNueva ? 'text' : 'password'"
                label="Nueva contraseña *"
                autocomplete="new-password"
                :rules="reglasNueva"
                class="q-mb-sm"
              >
                <template #prepend><q-icon name="key" color="green-8" /></template>
                <template #append>
                  <q-btn flat round dense :icon="mostrarNueva ? 'visibility_off' : 'visibility'" @click="mostrarNueva = !mostrarNueva" />
                </template>
              </q-input>

              <q-input
                v-model="form.password_confirmation"
                outlined
                :type="mostrarNueva ? 'text' : 'password'"
                label="Confirmar nueva contraseña *"
                autocomplete="new-password"
                :rules="reglasConfirmacion"
              >
                <template #prepend><q-icon name="task_alt" color="green-8" /></template>
              </q-input>

              <q-btn
                type="submit"
                color="green-8"
                icon="save"
                label="Actualizar contraseña"
                class="full-width q-mt-lg"
                size="lg"
                unelevated
                no-caps
                :loading="guardando"
              />
            </q-form>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import { api } from 'src/boot/axios.js'

const $q = useQuasar()
const router = useRouter()
const guardando = ref(false)
const mostrarActual = ref(false)
const mostrarNueva = ref(false)

const form = reactive({
  password_actual: '',
  password: '',
  password_confirmation: ''
})

const requerido = valor => Boolean(String(valor || '').trim()) || 'Campo obligatorio'
const reglasNueva = [
  requerido,
  valor => String(valor || '').length >= 8 || 'Mínimo 8 caracteres',
  valor => valor !== form.password_actual || 'Debe ser diferente a la contraseña actual'
]
const reglasConfirmacion = [
  requerido,
  valor => valor === form.password || 'Las contraseñas no coinciden'
]

function rolActual() {
  try {
    return String(JSON.parse(localStorage.getItem('motrix_user') || 'null')?.role || '').toLowerCase()
  } catch {
    return ''
  }
}

function volver() {
  router.push(rolActual() === 'conductor' ? '/conductor/perfil' : '/pasajero/perfil')
}

function mensajeError(error) {
  return Object.values(error?.response?.data?.errors || {}).flat().find(Boolean)
    || error?.response?.data?.message
    || error?.response?.data?.mensaje
    || 'No se pudo cambiar la contraseña.'
}

async function guardar() {
  if (guardando.value) return
  guardando.value = true
  try {
    const { data } = await api.post('/auth/cambiar-password', { ...form })
    form.password_actual = ''
    form.password = ''
    form.password_confirmation = ''
    $q.notify({
      type: 'positive',
      position: 'top',
      icon: 'check_circle',
      message: data?.message || 'Contraseña actualizada correctamente.'
    })
    volver()
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      multiLine: true,
      message: mensajeError(error)
    })
  } finally {
    guardando.value = false
  }
}
</script>

<style scoped>
.password-page { min-height: 100%; background: transparent; }
.password-card { overflow: hidden; border-radius: 20px; }
.password-header { background: linear-gradient(135deg, #1b5e20, #2e7d32); }
</style>
