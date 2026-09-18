<template>
  <div class="q-mt-md">
    <q-separator class="q-mb-md" />

    <q-banner rounded class="bg-red-1 text-red-9 q-mb-md">
      <template #avatar>
        <q-icon name="privacy_tip" color="red-7" size="26px" />
      </template>
      <div class="text-weight-bold">Control de la cuenta</div>
      <div class="text-caption text-grey-7 q-mt-xs">
        Puedes eliminar tus credenciales de acceso. Tu registro gremial,
        motocicleta, historial de servicios, pagos y suscripción se conservarán.
      </div>
    </q-banner>

    <q-btn
      outline
      color="negative"
      icon="delete_forever"
      label="Eliminar mi cuenta"
      class="full-width"
      no-caps
      @click="abrir"
    />

    <q-dialog v-model="dialogo" persistent>
      <q-card style="width:min(560px, calc(100vw - 28px)); border-radius:18px">
        <q-card-section class="row items-start no-wrap q-gutter-md">
          <q-avatar color="red-1" text-color="red-7" icon="warning" size="48px" />
          <div class="col">
            <div class="text-h6 text-weight-bold">Eliminar cuenta</div>
            <div class="text-body2 text-grey-6">Esta acción no se puede deshacer.</div>
          </div>
          <q-btn flat round dense icon="close" :disable="eliminando" @click="cerrar" />
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-banner rounded class="bg-red-1 text-red-8 q-mb-md">
            No podrás eliminar la cuenta mientras tengas un viaje activo.
            Se eliminarán tus credenciales y se cerrarán tus sesiones, pero
            se conservarán tu afiliación e historial administrativo.
          </q-banner>

          <q-input
            v-model="password"
            outlined
            :type="mostrarPassword ? 'text' : 'password'"
            label="Contraseña actual"
            class="q-mb-md"
          >
            <template #append>
              <q-icon
                :name="mostrarPassword ? 'visibility_off' : 'visibility'"
                class="cursor-pointer"
                @click="mostrarPassword = !mostrarPassword"
              />
            </template>
          </q-input>

          <q-input
            v-model.trim="confirmacion"
            outlined
            label="Escribe ELIMINAR"
            maxlength="8"
          />
          <div class="text-caption text-grey-6 q-mt-xs">
            Debe escribirse exactamente en mayúsculas.
          </div>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md q-pt-none">
          <q-btn flat color="grey-7" label="Cancelar" no-caps :disable="eliminando" @click="cerrar" />
          <q-btn
            color="negative"
            icon="delete_forever"
            label="Eliminar definitivamente"
            unelevated
            no-caps
            :loading="eliminando"
            :disable="!puedeEliminar"
            @click="eliminar"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import { api } from 'src/boot/axios.js'

const $q = useQuasar()
const router = useRouter()

const dialogo = ref(false)
const eliminando = ref(false)
const password = ref('')
const confirmacion = ref('')
const mostrarPassword = ref(false)

const puedeEliminar = computed(() =>
  Boolean(password.value)
  && confirmacion.value === 'ELIMINAR'
  && !eliminando.value
)

function abrir() {
  password.value = ''
  confirmacion.value = ''
  mostrarPassword.value = false
  dialogo.value = true
}

function cerrar() {
  if (eliminando.value) return
  dialogo.value = false
}

function limpiarSesion() {
  for (const key of ['motrix_token', 'motrix_user', 'mototaxista_id', 'pasajero_id']) {
    localStorage.removeItem(key)
    sessionStorage.removeItem(key)
  }
}

async function eliminar() {
  if (!puedeEliminar.value) return

  eliminando.value = true
  try {
    const respuesta = await api.delete('/conductor/cuenta', {
      data: {
        password: password.value,
        confirmacion: confirmacion.value
      }
    })

    limpiarSesion()
    dialogo.value = false

    $q.notify({
      type: 'positive',
      position: 'top',
      message: respuesta?.data?.message || 'Tu cuenta de conductor fue eliminada correctamente.'
    })

    await router.replace('/')
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      message:
        error?.response?.data?.message
        || error?.response?.data?.errors?.password?.[0]
        || 'No se pudo eliminar la cuenta de conductor.'
    })
  } finally {
    eliminando.value = false
  }
}
</script>
