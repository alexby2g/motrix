<template>
  <q-page class="passenger-home q-pa-md q-pa-lg-md">
    <div class="row justify-center">
      <div class="col-12 col-lg-9 col-xl-8">
        <q-card class="home-card shadow-2">
          <q-card-section class="passenger-hero text-white">
            <div class="row items-center no-wrap">
              <q-avatar
                color="white"
                text-color="green-8"
                icon="person_pin_circle"
                size="58px"
                class="q-mr-md"
              />

              <div class="col min-width-zero">
                <div class="text-h5 text-weight-bold">
                  Panel del Pasajero
                </div>

                <div class="text-caption text-green-1">
                  MOTRIX · Movilidad desde tu celular
                </div>
              </div>

              <q-btn
                flat
                round
                icon="account_circle"
                color="white"
                aria-label="Abrir mi perfil"
                @click="irAPerfil"
              >
                <q-tooltip>
                  Mi perfil
                </q-tooltip>
              </q-btn>
            </div>
          </q-card-section>

          <q-card-section class="q-pa-md q-pa-lg-md">
            <div class="welcome-row">
              <div>
                <div class="text-caption text-grey-6">
                  Bienvenido
                </div>

                <div class="text-h6 text-weight-bold text-grey-9">
                  {{ nombreUsuario }}
                </div>

                <div class="text-body2 text-grey-6 q-mt-xs">
                  ¿Qué deseas hacer?
                </div>
              </div>

              <q-chip
                color="green-1"
                text-color="green-9"
                icon="verified_user"
                class="text-weight-bold"
              >
                Cuenta protegida
              </q-chip>
            </div>

            <q-separator class="q-my-lg" />

            <div class="row q-col-gutter-md">
              <!-- SOLICITAR -->
              <div class="col-6 col-sm-6">
                <q-card
                  flat
                  bordered
                  class="action-card full-height action-primary"
                  clickable
                  @click="irASolicitar"
                >
                  <q-card-section class="text-center q-pa-md">
                    <q-avatar
                      color="green-1"
                      text-color="green-8"
                      icon="two_wheeler"
                      size="58px"
                    />

                    <div class="action-title">
                      Solicitar mototaxi
                    </div>

                    <div class="action-description">
                      Elige origen, destino y consulta tu tarifa.
                    </div>

                    <q-btn
                      color="green-8"
                      icon="add_location_alt"
                      label="Solicitar"
                      class="full-width q-mt-md text-weight-bold"
                      unelevated
                      no-caps
                      @click.stop="irASolicitar"
                    />
                  </q-card-section>
                </q-card>
              </div>

              <!-- QR -->
              <div class="col-6 col-sm-6">
                <q-card
                  flat
                  bordered
                  class="action-card full-height"
                  clickable
                  @click="irAEscanear"
                >
                  <q-card-section class="text-center q-pa-md">
                    <q-avatar
                      color="green-1"
                      text-color="green-9"
                      icon="qr_code_scanner"
                      size="58px"
                    />

                    <div class="action-title">
                      Escanear QR
                    </div>

                    <div class="action-description">
                      Verifica la identidad del mototaxista.
                    </div>

                    <q-btn
                      outline
                      color="green-8"
                      icon="photo_camera"
                      label="Abrir cámara"
                      class="full-width q-mt-md text-weight-bold"
                      no-caps
                      @click.stop="irAEscanear"
                    />
                  </q-card-section>
                </q-card>
              </div>

              <!-- HISTORIAL -->
              <div class="col-6 col-sm-6">
                <q-card
                  flat
                  bordered
                  class="action-card full-height"
                  clickable
                  @click="irAHistorial"
                >
                  <q-card-section class="text-center q-pa-md">
                    <q-avatar
                      color="green-1"
                      text-color="green-8"
                      icon="history"
                      size="58px"
                    />

                    <div class="action-title">
                      Mis viajes
                    </div>

                    <div class="action-description">
                      Consulta tus solicitudes y servicios anteriores.
                    </div>

                    <q-btn
                      outline
                      color="green-8"
                      icon="receipt_long"
                      label="Ver historial"
                      class="full-width q-mt-md text-weight-bold"
                      no-caps
                      @click.stop="irAHistorial"
                    />
                  </q-card-section>
                </q-card>
              </div>

              <!-- PERFIL -->
              <div class="col-6 col-sm-6">
                <q-card
                  flat
                  bordered
                  class="action-card full-height"
                  clickable
                  @click="irAPerfil"
                >
                  <q-card-section class="text-center q-pa-md">
                    <q-avatar
                      color="green-1"
                      text-color="green-9"
                      icon="account_circle"
                      size="58px"
                    />

                    <div class="action-title">
                      Mi perfil
                    </div>

                    <div class="action-description">
                      Revisa los datos vinculados a tu cuenta.
                    </div>

                    <q-btn
                      outline
                      color="green-8"
                      icon="person"
                      label="Ver perfil"
                      class="full-width q-mt-md text-weight-bold"
                      no-caps
                      @click.stop="irAPerfil"
                    />
                  </q-card-section>
                </q-card>
              </div>
            </div>

            <q-banner
              rounded
              class="security-banner q-mt-lg"
            >
              <template #avatar>
                <q-icon
                  name="shield"
                  color="green-8"
                  size="30px"
                />
              </template>

              <div class="text-weight-bold text-green-9">
                Seguridad MOTRIX
              </div>

              <div class="text-caption text-grey-7">
                Tus solicitudes e historial están vinculados únicamente
                con tu cuenta de pasajero.
              </div>
            </q-banner>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

function leerUsuario() {
  try {
    return JSON.parse(
      localStorage.getItem('motrix_user')
      || 'null'
    )
  } catch {
    return null
  }
}

const usuario = leerUsuario()

const nombreUsuario = computed(() => {
  return (
    usuario?.persona_nombre
    || usuario?.pasajero?.persona?.nombre
    || usuario?.name
    || usuario?.email
    || 'Pasajero MOTRIX'
  )
})

function irASolicitar() {
  router.push('/pasajero/solicitar')
}

function irAEscanear() {
  router.push('/pasajero/escanear')
}

function irAHistorial() {
  router.push('/pasajero/historial')
}

function irAPerfil() {
  router.push('/pasajero/perfil')
}
</script>

<style scoped>
.passenger-home {
  min-height: 100%;
  background: transparent;
}

.home-card {
  overflow: hidden;
  border-radius: 18px;
}

.passenger-hero {
  background:
    linear-gradient(
      135deg,
      #1b5e20,
      #2e7d32
    );
}

.welcome-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.action-card {
  border-color: #d7e4d3;
  border-radius: 16px;
  cursor: pointer;
  transition:
    transform 0.16s ease,
    box-shadow 0.16s ease,
    border-color 0.16s ease;
}

.action-card:hover {
  transform: translateY(-2px);
  border-color: #81c784;
  box-shadow:
    0 9px 24px rgba(46, 125, 50, 0.12);
}

.action-primary {
  border-top: 4px solid #2e7d32;
}

.action-title {
  margin-top: 14px;
  color: #17351b;
  font-size: 16px;
  font-weight: 800;
}

.action-description {
  min-height: 38px;
  margin-top: 6px;
  color: #788078;
  font-size: 12px;
  line-height: 1.45;
}

.security-banner {
  color: #234c27;
  background: #edf7eb;
  border: 1px solid #d2e6cf;
}

.min-width-zero {
  min-width: 0;
}

@media (max-width: 599px) {
  .passenger-home {
    padding: 10px 10px 22px;
  }

  .home-card {
    border-radius: 14px;
  }

  .passenger-hero {
    margin: 0;
  }

  .welcome-row {
    align-items: flex-start;
    flex-direction: column;
  }

  .action-card {
    border-radius: 14px;
  }

  .action-card :deep(.q-card__section) {
    padding: 13px 9px;
  }

  .action-title {
    min-height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
  }

  .action-description {
    display: none;
  }

  .action-card :deep(.q-btn) {
    min-height: 40px;
    font-size: 11px;
  }
}
</style>
