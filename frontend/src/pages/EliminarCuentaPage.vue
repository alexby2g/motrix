<template>
  <q-page class="delete-public-page q-pa-md">
    <div class="row justify-center">
      <div class="col-12 col-md-8 col-lg-6">
        <q-card flat bordered class="delete-public-card">
          <q-card-section class="q-pa-lg">
            <div class="row items-center q-gutter-md">
              <q-avatar color="red-1" text-color="red-8" icon="delete_forever" size="58px" />
              <div class="col">
                <div class="text-h5 text-weight-bold text-grey-9">
                  Eliminación de cuenta MOTRIX
                </div>
                <div class="text-grey-7 q-mt-xs">
                  Disponible para cuentas de pasajero creadas con correo o Google.
                </div>
              </div>
            </div>

            <q-separator class="q-my-lg" />

            <p class="text-grey-8">
              Para proteger tu identidad, debes iniciar sesión con la cuenta que deseas eliminar.
              Luego entra a <strong>Mi perfil</strong> y utiliza la opción <strong>Eliminar mi cuenta</strong>.
            </p>

            <q-banner rounded class="bg-grey-2 text-grey-8 q-my-md">
              Los datos personales se eliminan o anonimizan. El historial estrictamente necesario
              para mantener integridad de viajes, pagos y reportes puede conservarse sin identificarte.
            </q-banner>

            <div class="row q-col-gutter-sm q-mt-lg">
              <div class="col-12 col-sm-6">
                <q-btn
                  outline
                  color="green-8"
                  icon="home"
                  label="Volver a MOTRIX"
                  class="full-width"
                  no-caps
                  @click="router.push('/inicio')"
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-btn
                  color="red-7"
                  icon="login"
                  label="Iniciar sesión para eliminar"
                  class="full-width"
                  unelevated
                  no-caps
                  @click="irAEliminar"
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
import { useRouter } from 'vue-router'

const router = useRouter()

function irAEliminar() {
  router.push('/pasajero/perfil?eliminar=1')
}
</script>

<style scoped>
.delete-public-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  background: #f5f6f7;
}

.delete-public-card {
  border-radius: 18px;
  background: #ffffff;
}
</style>
