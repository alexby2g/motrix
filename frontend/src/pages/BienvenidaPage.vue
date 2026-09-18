<template>
  <q-layout view="hHh lpR fFf">
    <q-page-container>
      <q-page class="welcome-page">
    <div class="welcome-shell">
      <section class="welcome-hero">
        <div class="brand-row">
          <div class="brand-icon">
            <q-icon name="two_wheeler" />
          </div>

          <div>
            <div class="brand-name">MOTRIX</div>
            <div class="brand-subtitle">
              Movilidad · Gestión · Seguridad
            </div>
          </div>
        </div>

        <div class="hero-copy">
          <div class="eyebrow">TRINIDAD · BENI</div>

          <h1>
            Tu mototaxi,
            <span>cuando lo necesitas.</span>
          </h1>

          <p>
            Solicita un conductor disponible y cercano desde tu
            ubicación. Si ya tienes una cuenta, inicia sesión; si eres
            pasajero nuevo, crea tu cuenta en pocos pasos.
          </p>
        </div>

        <div class="hero-illustration" aria-hidden="true">
          <div class="map-line map-line-a" />
          <div class="map-line map-line-b" />
          <div class="origin-dot">
            <q-icon name="my_location" />
          </div>
          <div class="destination-dot">
            <q-icon name="place" />
          </div>
          <div class="moto-circle">
            <q-icon name="two_wheeler" />
          </div>
        </div>
      </section>

      <section class="welcome-actions">
        <div class="action-card">
          <div class="mobile-brand">
            <q-avatar
              size="64px"
              color="green-1"
              text-color="green-9"
              icon="two_wheeler"
            />

            <div>
              <div class="text-h5 text-weight-bold text-green-9">
                Bienvenido a MOTRIX
              </div>
              <div class="text-body2 text-grey-6">
                Elige cómo quieres continuar.
              </div>
            </div>
          </div>

          <div
            v-if="mostrarGoogleWeb"
            class="google-access q-mb-md"
          >
            <div
              ref="googleButton"
              class="google-button-host"
            />
          </div>

          <div
            v-if="mostrarGoogleWeb"
            class="google-divider"
          >
            <span />
            <small>O CONTINÚA CON</small>
            <span />
          </div>

          <q-btn
            color="green-8"
            icon="person_add"
            label="Crear cuenta de pasajero"
            unelevated
            no-caps
            rounded
            class="full-width main-action"
            @click="irRegistro"
          />

          <q-btn
            outline
            color="green-9"
            icon="login"
            label="Iniciar sesión"
            no-caps
            rounded
            class="full-width main-action q-mt-md"
            @click="irLogin"
          />

          <div class="divider-row">
            <span />
            <small>ACCESO SEGURO</small>
            <span />
          </div>

          <button
            type="button"
            class="driver-info-button"
            @click="mostrarInfoConductor = true"
          >
            <q-icon name="two_wheeler" />
            <span>
              <strong>¿Eres mototaxista?</strong>
              <small>Consulta cómo obtener tu cuenta de conductor.</small>
            </span>
            <q-icon name="chevron_right" />
          </button>

          <q-btn
            flat
            color="grey-7"
            icon="info_outline"
            label="Conocer MOTRIX"
            no-caps
            class="full-width q-mt-sm"
            @click="irPresentacion"
          />

          <div class="safety-note">
            <q-icon name="verified_user" />
            <span>
              Las cuentas de conductor solo se habilitan a mototaxistas
              previamente registrados y afiliados.
            </span>
          </div>
        </div>
      </section>
    </div>

    <q-dialog v-model="mostrarInfoConductor">
      <q-card class="driver-dialog">
        <q-card-section class="dialog-header row items-center no-wrap">
          <q-avatar
            color="green-1"
            text-color="green-9"
            icon="two_wheeler"
            size="52px"
          />

          <div class="q-ml-md col">
            <div class="text-h6 text-weight-bold">
              Acceso para mototaxistas
            </div>
            <div class="text-caption text-grey-7">
              La cuenta de conductor no se crea públicamente.
            </div>
          </div>

          <q-btn
            flat
            round
            dense
            icon="close"
            v-close-popup
          />
        </q-card-section>

        <q-separator />

        <q-card-section class="q-pa-lg">
          <div class="driver-step">
            <span>1</span>
            <p>Estar afiliado a un sindicato registrado en MOTRIX.</p>
          </div>

          <div class="driver-step">
            <span>2</span>
            <p>Registrar y verificar tus datos y motocicleta.</p>
          </div>

          <div class="driver-step">
            <span>3</span>
            <p>
              La administración habilita tu cuenta de conductor y recién
              entonces puedes iniciar sesión.
            </p>
          </div>

          <q-banner rounded class="bg-green-1 text-green-9 q-mt-md">
            <template #avatar>
              <q-icon name="shield" color="green-8" />
            </template>
            Este proceso evita que una persona no verificada se registre
            por su cuenta como conductor.
          </q-banner>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md bg-grey-1">
          <q-btn
            flat
            color="grey-7"
            label="Cerrar"
            no-caps
            v-close-popup
          />
          <q-btn
            color="green-8"
            icon="login"
            label="Ya tengo cuenta"
            no-caps
            unelevated
            @click="irLoginDesdeDialog"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
      </q-page>
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { nextTick, onMounted, ref } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import {
  autenticarConGoogle,
  esContenedorNativo,
  googleClientConfigurado,
  guardarGooglePendiente,
  guardarSesionMotrix,
  renderizarBotonGoogle
} from '../services/googleAuth.js'

const $q = useQuasar()
const router = useRouter()
const mostrarInfoConductor = ref(false)
const googleButton = ref(null)
const mostrarGoogleWeb = ref(
  googleClientConfigurado() && !esContenedorNativo()
)

onMounted(async () => {
  if (!mostrarGoogleWeb.value) return

  await nextTick()

  try {
    await renderizarBotonGoogle(
      googleButton.value,
      manejarGoogle
    )
  } catch (error) {
    console.error('No se pudo inicializar Google:', error)
    mostrarGoogleWeb.value = false
  }
})

async function manejarGoogle(respuestaGoogle) {
  const credential = respuestaGoogle?.credential
  if (!credential) return

  try {
    const respuesta = await autenticarConGoogle(credential)

    if (respuesta.data?.needs_profile) {
      guardarGooglePendiente(
        respuesta.data.registration_token,
        respuesta.data.profile
      )
      await router.push('/completar-google')
      return
    }

    guardarSesionMotrix(respuesta.data)

    $q.notify({
      type: 'positive',
      position: 'top',
      icon: 'check_circle',
      message: `Bienvenido, ${respuesta.data.user?.persona_nombre || respuesta.data.user?.name || 'Pasajero MOTRIX'}`
    })

    await router.replace('/pasajero')
  } catch (error) {
    const mensaje = error.response?.data?.message
      || Object.values(error.response?.data?.errors || {})
        .flat()
        .find(Boolean)
      || 'No se pudo continuar con Google.'

    $q.notify({
      type: 'negative',
      position: 'top',
      icon: 'error',
      message: mensaje
    })
  }
}

function irRegistro() {
  router.push('/registro-pasajero')
}

function irLogin() {
  router.push('/login')
}

function irPresentacion() {
  router.push('/presentacion')
}

function irLoginDesdeDialog() {
  mostrarInfoConductor.value = false
  router.push('/login')
}
</script>

<style scoped>
.welcome-page {
  min-height: 100vh;
  background: #eef7e9;
  color: #17351b;
}

.welcome-shell {
  min-height: 100vh;
  display: grid;
  grid-template-columns: minmax(0, 1.08fr) minmax(390px, 0.92fr);
}

.welcome-hero {
  position: relative;
  overflow: hidden;
  min-height: 100vh;
  padding: 48px clamp(32px, 6vw, 86px);
  display: flex;
  flex-direction: column;
  background: linear-gradient(145deg, #0c3b12 0%, #1b5e20 52%, #388e3c 100%);
  color: white;
}

.brand-row {
  position: relative;
  z-index: 2;
  display: flex;
  align-items: center;
  gap: 14px;
}

.brand-icon {
  width: 54px;
  height: 54px;
  display: grid;
  place-items: center;
  border-radius: 18px;
  background: white;
  color: #2e7d32;
  font-size: 31px;
  box-shadow: 0 12px 28px rgba(0, 0, 0, 0.18);
}

.brand-name {
  font-size: 28px;
  font-weight: 800;
  line-height: 1;
  letter-spacing: 0.02em;
}

.brand-subtitle {
  margin-top: 6px;
  color: #c8e6c9;
  font-size: 12px;
  font-weight: 600;
}

.hero-copy {
  position: relative;
  z-index: 2;
  width: min(620px, 100%);
  margin: auto 0;
  padding: 60px 0;
}

.eyebrow {
  margin-bottom: 16px;
  color: #c8e6c9;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.18em;
}

.hero-copy h1 {
  margin: 0;
  font-size: clamp(42px, 5vw, 72px);
  line-height: 1.02;
  font-weight: 800;
  letter-spacing: -0.04em;
}

.hero-copy h1 span {
  display: block;
  color: #dcedc8;
}

.hero-copy p {
  width: min(550px, 100%);
  margin: 24px 0 0;
  color: #e8f5e9;
  font-size: 17px;
  line-height: 1.7;
}

.hero-illustration {
  position: absolute;
  right: -55px;
  bottom: -70px;
  width: 430px;
  height: 360px;
  opacity: 0.27;
}

.map-line {
  position: absolute;
  height: 14px;
  background: rgba(255, 255, 255, 0.32);
  border-radius: 20px;
  transform-origin: left center;
}

.map-line-a {
  width: 360px;
  left: 15px;
  top: 180px;
  transform: rotate(-24deg);
}

.map-line-b {
  width: 320px;
  left: 70px;
  top: 80px;
  transform: rotate(35deg);
}

.origin-dot,
.destination-dot,
.moto-circle {
  position: absolute;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: white;
  color: #2e7d32;
}

.origin-dot {
  width: 48px;
  height: 48px;
  left: 35px;
  top: 188px;
}

.destination-dot {
  width: 52px;
  height: 52px;
  right: 45px;
  top: 60px;
  color: #c62828;
}

.moto-circle {
  width: 110px;
  height: 110px;
  left: 165px;
  top: 105px;
  font-size: 64px;
}

.welcome-actions {
  min-height: 100vh;
  padding: 40px clamp(24px, 5vw, 72px);
  display: flex;
  align-items: center;
  justify-content: center;
  background:
    radial-gradient(circle at 90% 10%, rgba(76, 175, 80, 0.16), transparent 26%),
    #f5faef;
}

.action-card {
  width: 100%;
  max-width: 470px;
  padding: 36px;
  border: 1px solid rgba(46, 125, 50, 0.13);
  border-radius: 26px;
  background: rgba(255, 255, 255, 0.95);
  box-shadow: 0 28px 70px rgba(27, 94, 32, 0.14);
}

.mobile-brand {
  margin-bottom: 28px;
  display: flex;
  align-items: center;
  gap: 14px;
}

.main-action {
  min-height: 54px;
  font-weight: 700;
}

.divider-row {
  margin: 26px 0 18px;
  display: flex;
  align-items: center;
  gap: 12px;
  color: #9e9e9e;
}

.divider-row span {
  flex: 1;
  height: 1px;
  background: #e5e5e5;
}

.divider-row small {
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.1em;
}

.driver-info-button {
  width: 100%;
  padding: 15px 14px;
  display: flex;
  align-items: center;
  gap: 12px;
  border: 1px solid #c8e6c9;
  border-radius: 15px;
  background: #f3faef;
  color: #1b5e20;
  cursor: pointer;
  text-align: left;
}

.driver-info-button > .q-icon:first-child {
  font-size: 26px;
}

.driver-info-button span {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.driver-info-button strong {
  font-size: 14px;
}

.driver-info-button small {
  margin-top: 3px;
  color: #6a7e6b;
  font-size: 11px;
}

.safety-note {
  margin-top: 20px;
  padding: 14px;
  display: flex;
  gap: 10px;
  border-radius: 13px;
  background: #f6f7f6;
  color: #738075;
  font-size: 12px;
  line-height: 1.5;
}

.safety-note .q-icon {
  color: #2e7d32;
  font-size: 20px;
}

.driver-dialog {
  width: 560px;
  max-width: calc(100vw - 24px);
  border-radius: 20px;
  overflow: hidden;
}

.dialog-header {
  padding: 20px 22px;
}

.driver-step {
  display: flex;
  align-items: flex-start;
  gap: 13px;
  margin-bottom: 14px;
}

.driver-step span {
  flex: 0 0 30px;
  width: 30px;
  height: 30px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: #2e7d32;
  color: white;
  font-weight: 800;
}

.driver-step p {
  margin: 4px 0 0;
  color: #405342;
  line-height: 1.5;
}

@media (max-width: 900px) {
  .welcome-shell {
    display: block;
  }

  .welcome-hero {
    min-height: 42vh;
    padding: 28px 24px 48px;
  }

  .hero-copy {
    padding: 52px 0 18px;
  }

  .hero-copy h1 {
    font-size: clamp(38px, 10vw, 54px);
  }

  .hero-copy p {
    font-size: 15px;
  }

  .hero-illustration {
    width: 300px;
    height: 240px;
    right: -100px;
    bottom: -70px;
  }

  .welcome-actions {
    min-height: 58vh;
    padding: 22px 16px 34px;
  }

  .action-card {
    max-width: 540px;
    padding: 26px 22px;
    border-radius: 22px;
  }
}

@media (max-width: 520px) {
  .brand-subtitle {
    display: none;
  }

  .welcome-hero {
    min-height: 35vh;
  }

  .hero-copy {
    padding-top: 42px;
  }

  .hero-copy p {
    margin-top: 16px;
  }

  .welcome-actions {
    align-items: flex-start;
  }

  .action-card {
    margin-top: -10px;
  }
}

.google-access {
  width: 100%;
}

.google-button-host {
  width: 100%;
  min-height: 44px;
  display: flex;
  justify-content: center;
}

.google-divider {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 8px 0 16px;
  color: #8a8a8a;
}

.google-divider span {
  flex: 1;
  height: 1px;
  background: #e0e0e0;
}

.google-divider small {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: .08em;
}
</style>
