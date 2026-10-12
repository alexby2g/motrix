<template>
  <q-layout view="hHh lpr fff">
    <q-page-container>
      <q-page class="login-page">
        <div class="login-shell">
          <section class="login-visual" aria-label="Identidad MOTRIX">
            <div class="login-visual__shade" />
            <div class="login-visual__top">
              <div class="visual-brand-final">
              <img src="/motrix-logo-oficial.png" alt="Logo oficial MOTRIX" />
              <div>
                <strong>MOTRIX</strong>
                <small>MOVILIDAD · GESTIÓN · SEGURIDAD</small>
              </div>
            </div>
              <span class="visual-city"><q-icon name="location_on" /> Trinidad · Beni</span>
            </div>

            <div class="login-visual__content">
              <span class="visual-eyebrow">MOVILIDAD · GESTIÓN · SEGURIDAD</span>
              <h1>Todo MOTRIX,<br><strong>en un solo acceso.</strong></h1>
              <p>
                Entra según tu perfil para administrar, conducir o solicitar un viaje.
                Tu cuenta abre automáticamente las funciones que te corresponden.
              </p>

              <div class="visual-benefits">
                <div><q-icon name="groups" /><span>Gestión sindical y federativa</span></div>
                <div><q-icon name="verified_user" /><span>Mototaxistas verificados</span></div>
                <div><q-icon name="route" /><span>Solicitud y seguimiento del viaje</span></div>
              </div>
            </div>

            <div class="login-visual__bottom">
              <span><q-icon name="shield" /> Acceso oficial MOTRIX</span>
              <span>motrixbolivia.com</span>
            </div>
          </section>

          <section class="login-panel">
            <div class="login-panel__inner">
              <div class="panel-topbar">
                <q-btn
                  v-if="!esAppNativa"
                  flat dense no-caps icon="arrow_back"
                  label="Volver a MOTRIX"
                  color="green-9"
                  to="/presentacion"
                  class="back-link"
                />
                <span class="secure-pill"><q-icon name="lock" /> Acceso seguro</span>
              </div>

              <div class="login-mobile-brand">
                <div class="mobile-brand-final">
                  <img src="/motrix-logo-oficial.png" alt="Logo oficial MOTRIX" />
                  <div><strong>MOTRIX</strong><small>Movilidad · Gestión · Seguridad</small></div>
                </div>
              </div>

              <div class="login-heading">
                <span class="login-eyebrow">BIENVENIDO</span>
                <h2>Inicia sesión en MOTRIX</h2>
                <p>Usa tu celular, correo o usuario para continuar.</p>
              </div>

              <div v-if="mostrarGoogleWeb" class="google-login-block">
                <div ref="googleButton" class="google-button-host" />
                <div class="google-divider">
                  <span />
                  <small>O CONTINÚA CON TUS DATOS</small>
                  <span />
                </div>
              </div>

              <q-form greedy class="login-form" @submit.prevent="iniciarSesion">
                <q-input
                  v-model.trim="form.login"
                  label="Celular, correo o usuario"
                  outlined
                  hide-bottom-space
                  autocomplete="username"
                  class="login-field"
                  lazy-rules
                  :rules="reglasLogin"
                >
                  <template #prepend>
                    <q-icon name="account_circle" color="green-8" />
                  </template>
                </q-input>

                <q-input
                  v-model="form.password"
                  label="Contraseña"
                  outlined
                  hide-bottom-space
                  autocomplete="current-password"
                  :type="mostrarPassword ? 'text' : 'password'"
                  class="login-field"
                  lazy-rules
                  :rules="reglasPassword"
                >
                  <template #prepend>
                    <q-icon name="lock" color="green-8" />
                  </template>
                  <template #append>
                    <q-icon
                      :name="mostrarPassword ? 'visibility_off' : 'visibility'"
                      class="cursor-pointer"
                      color="grey-7"
                      @click="mostrarPassword = !mostrarPassword"
                    />
                  </template>
                </q-input>

                <div class="form-links">
                  <span class="role-hint"><q-icon name="shield" /> Acceso automático según tu rol</span>
                  <q-btn
                    flat dense no-caps color="green-8"
                    label="¿Olvidaste tu contraseña?"
                    to="/recuperar-contrasena"
                  />
                </div>

                <q-btn
                  type="submit"
                  color="green-8"
                  label="Ingresar a MOTRIX"
                  icon="login"
                  unelevated
                  no-caps
                  class="full-width login-submit"
                  :loading="cargando"
                />
              </q-form>

              <div class="login-register">
                <span>¿Primera vez en MOTRIX?</span>
                <q-btn
                  outline
                  no-caps
                  color="green-8"
                  icon="person_add"
                  label="Crear cuenta de pasajero"
                  to="/registro-pasajero"
                  class="full-width"
                />
              </div>

              <div class="login-assurance">
                <span><q-icon name="verified_user" /> Conexión protegida</span>
                <span><q-icon name="badge" /> Acceso por perfiles</span>
                <span><q-icon name="qr_code_2" /> Identidad verificada</span>
              </div>

              <div class="login-footer-copy">MOTRIX · Trinidad, Beni · Bolivia</div>
            </div>
          </section>
        </div>
      </q-page>
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { nextTick, onMounted, reactive, ref } from 'vue'
import { useQuasar } from 'quasar'
import { useRoute, useRouter } from 'vue-router'

import { api } from '../boot/axios.js'
import {
  autenticarConGoogle,
  esContenedorNativo,
  googleClientConfigurado,
  guardarGooglePendiente,
  guardarSesionMotrix,
  renderizarBotonGoogle
} from '../services/googleAuth.js'

const $q = useQuasar()
const route = useRoute()
const router = useRouter()

const cargando = ref(false)
const mostrarPassword = ref(false)
const googleButton = ref(null)
const esAppNativa = esContenedorNativo()

const mostrarGoogleWeb = ref(
  googleClientConfigurado() && !esAppNativa
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


const form = reactive({
  login: '',
  password: ''
})

const reglasLogin = [
  valor =>
    Boolean(String(valor || '').trim())
    || 'El celular, correo o usuario es obligatorio'
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

  if (rol === 'admin_registro') {
    return '/registro'
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

    const destinoGoogle =
      typeof route.query.redirect === 'string'
      && route.query.redirect.startsWith('/pasajero')
        ? route.query.redirect
        : '/pasajero'

    await router.replace(destinoGoogle)
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
:global(html), :global(body), :global(#q-app) {
  min-height: 100%;
}

.login-page {
  position: relative;
  z-index: 1;
  isolation: isolate;
  min-height: 100dvh;
  overflow: hidden;
  color: #17361f;
  background: #f4f8f5;
}

.login-shell {
  width: 100%;
  min-height: 100dvh;
  display: grid;
  grid-template-columns: minmax(400px, 40%) minmax(0, 60%);
  background: #f4f8f5;
}

/* ===== PANEL VISUAL ===== */
.login-visual {
  position: relative;
  min-width: 0;
  min-height: 100dvh;
  overflow: hidden;
  padding: 30px clamp(28px, 3.2vw, 52px);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  color: #fff;
  background:
    url('/motrix-login-hero-final.jpg') center 45% / cover no-repeat,
    #073a22;
}

.login-visual__shade {
  position: absolute;
  inset: 0;
  background:
    linear-gradient(90deg, rgba(2, 37, 19, .90) 0%, rgba(4, 60, 31, .70) 52%, rgba(4, 55, 29, .36) 100%),
    linear-gradient(0deg, rgba(2, 28, 15, .38) 0%, transparent 48%);
}

.login-visual__top,
.login-visual__content,
.login-visual__bottom {
  position: relative;
  z-index: 2;
}

.login-visual__top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
}

.visual-brand-final {
  min-width: 0;
  display: inline-flex;
  align-items: center;
  gap: 13px;
}

.visual-brand-final > img {
  width: 62px;
  height: 62px;
  flex: 0 0 62px;
  object-fit: contain;
  border-radius: 17px;
  background: #079047;
  box-shadow: 0 12px 28px rgba(0, 0, 0, .18);
}

.visual-brand-final > div {
  min-width: 0;
  display: grid;
  line-height: 1;
}

.visual-brand-final strong {
  color: #fff;
  font-size: 29px;
  font-weight: 900;
  letter-spacing: -.035em;
}

.visual-brand-final small {
  margin-top: 6px;
  color: #d8f5e1;
  font-size: 8px;
  font-weight: 900;
  letter-spacing: .06em;
}

.visual-city {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  border: 1px solid rgba(255, 255, 255, .24);
  border-radius: 999px;
  background: rgba(255, 255, 255, .10);
  backdrop-filter: blur(10px);
  color: #fff;
  font-size: 10px;
  font-weight: 850;
  white-space: nowrap;
}

.login-visual__content {
  width: min(100%, 470px);
  margin: auto 0;
}

.visual-eyebrow {
  display: inline-block;
  margin-bottom: 11px;
  color: #aaf0c2;
  font-size: 10px;
  font-weight: 900;
  letter-spacing: .13em;
}

.login-visual h1 {
  max-width: 520px;
  margin: 0;
  color: #fff;
  font-size: clamp(34px, 3.4vw, 50px);
  line-height: .99;
  letter-spacing: -.045em;
  font-weight: 700;
}

.login-visual h1 strong {
  color: #54e98f;
  font-weight: 900;
}

.login-visual p {
  max-width: 490px;
  margin: 17px 0 0;
  color: rgba(255, 255, 255, .92);
  font-size: 14px;
  line-height: 1.55;
}

.visual-benefits {
  margin-top: 24px;
  display: grid;
  gap: 10px;
}

.visual-benefits > div {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #fff;
  font-size: 12px;
  font-weight: 750;
}

.visual-benefits .q-icon {
  width: 34px;
  height: 34px;
  display: grid;
  place-items: center;
  border: 1px solid rgba(255, 255, 255, .14);
  border-radius: 11px;
  background: rgba(255, 255, 255, .11);
  color: #baf5cf;
  font-size: 18px;
}

.login-visual__bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  color: rgba(255, 255, 255, .72);
  font-size: 9px;
  font-weight: 700;
}

.login-visual__bottom span:first-child {
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

/* ===== FORMULARIO ===== */
.login-panel {
  position: relative;
  min-width: 0;
  min-height: 100dvh;
  overflow: hidden;
  padding: 32px clamp(30px, 4.5vw, 68px);
  display: flex;
  align-items: center;
  justify-content: center;
  background:
    radial-gradient(circle at 100% 0%, rgba(20, 160, 77, .10), transparent 30%),
    linear-gradient(145deg, #fbfdfb 0%, #eef6f0 100%);
}

.login-panel::before,
.login-panel::after {
  content: '';
  position: absolute;
  border: 1px solid rgba(23, 115, 59, .06);
  border-radius: 50%;
  pointer-events: none;
}

.login-panel::before {
  width: 330px;
  height: 330px;
  right: -185px;
  bottom: -190px;
}

.login-panel::after {
  width: 210px;
  height: 210px;
  left: -125px;
  top: -120px;
}

.login-panel__inner {
  position: relative;
  z-index: 2;
  width: min(100%, 460px);
}

.panel-topbar {
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.back-link {
  margin-left: -8px;
  font-size: 12px;
  font-weight: 750;
}

.secure-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 7px 10px;
  border: 1px solid #d8eadc;
  border-radius: 999px;
  background: #edf8f0;
  color: #26813c;
  font-size: 9px;
  font-weight: 850;
  white-space: nowrap;
}

.login-mobile-brand {
  display: none;
}

.mobile-brand-final {
  display: flex;
  align-items: center;
  gap: 10px;
}

.mobile-brand-final > img {
  width: 48px;
  height: 48px;
  object-fit: contain;
  border-radius: 13px;
  background: #079047;
}

.mobile-brand-final > div {
  display: grid;
  line-height: 1;
}

.mobile-brand-final strong {
  color: #17361f;
  font-size: 22px;
  font-weight: 900;
}

.mobile-brand-final small {
  margin-top: 4px;
  color: #708078;
  font-size: 8px;
  font-weight: 800;
}

.login-heading {
  margin-bottom: 18px;
}

.login-eyebrow {
  color: #16833f;
  font-size: 9px;
  font-weight: 900;
  letter-spacing: .13em;
}

.login-heading h2 {
  margin: 5px 0 6px;
  color: #182a1e;
  font-size: clamp(28px, 2.5vw, 35px);
  line-height: 1.06;
  letter-spacing: -.035em;
  font-weight: 850;
}

.login-heading p {
  margin: 0;
  color: #77837a;
  font-size: 13px;
}

.google-login-block {
  margin-bottom: 15px;
}

.google-button-host {
  width: 100%;
  min-height: 44px;
  overflow: hidden;
  display: flex;
  justify-content: center;
}

.google-button-host > div,
.google-button-host iframe {
  max-width: 100% !important;
}

.google-divider {
  margin-top: 12px;
  display: flex;
  align-items: center;
  gap: 10px;
  color: #9ca69f;
}

.google-divider span {
  flex: 1;
  height: 1px;
  background: #e1e9e3;
}

.google-divider small {
  font-size: 8px;
  font-weight: 850;
  letter-spacing: .07em;
  white-space: nowrap;
}

.login-form {
  display: grid;
  gap: 12px;
}

.login-field {
  margin: 0;
}

.login-panel__inner :deep(.q-field__control) {
  min-height: 52px;
  border-radius: 14px;
  background: #fff;
}

.login-panel__inner :deep(.q-field--outlined .q-field__control:before) {
  border-color: #d7e3da;
}

.login-panel__inner :deep(.q-field--focused .q-field__control:after) {
  border-width: 1.5px;
  color: #159447;
}

.login-panel__inner :deep(.q-field__native),
.login-panel__inner :deep(.q-field__input) {
  background: transparent !important;
}

.login-panel__inner :deep(input:-webkit-autofill),
.login-panel__inner :deep(input:-webkit-autofill:hover),
.login-panel__inner :deep(input:-webkit-autofill:focus) {
  -webkit-box-shadow: 0 0 0 1000px #fff inset !important;
  -webkit-text-fill-color: #17361f !important;
  transition: background-color 9999s ease-out 0s !important;
}

.form-links {
  margin-top: -2px;
  display: grid;
  grid-template-columns: 1fr auto;
  align-items: center;
  gap: 10px;
}

.role-hint {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  color: #6f7b72;
  font-size: 9px;
  font-weight: 650;
}

.role-hint .q-icon {
  color: #16833f;
  font-size: 14px;
}

.form-links :deep(.q-btn__content) {
  font-size: 10px;
  font-weight: 800;
}

.login-submit {
  min-height: 52px;
  margin-top: 2px;
  border-radius: 14px;
  font-size: 14px;
  font-weight: 850;
  box-shadow: 0 12px 24px rgba(38, 125, 50, .17);
}

.login-register {
  margin-top: 17px;
  padding-top: 15px;
  display: grid;
  gap: 8px;
  border-top: 1px solid #e6eee8;
  text-align: center;
}

.login-register > span {
  color: #8a958c;
  font-size: 10px;
}

.login-register :deep(.q-btn) {
  min-height: 44px;
  border-radius: 13px;
  font-weight: 800;
}

.login-assurance {
  margin-top: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  flex-wrap: wrap;
  color: #758078;
  font-size: 8px;
  font-weight: 700;
}

.login-assurance span {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.login-assurance .q-icon {
  color: #16833f;
  font-size: 13px;
}

.login-footer-copy {
  margin-top: 10px;
  color: #a2aaa4;
  font-size: 8px;
  text-align: center;
}

/* ===== ALTURA REDUCIDA EN ESCRITORIO ===== */
@media (max-height: 760px) and (min-width: 981px) {
  .login-visual {
    padding-top: 24px;
    padding-bottom: 24px;
  }
  .visual-brand-final > img {
    width: 54px;
    height: 54px;
    flex-basis: 54px;
  }
  .visual-brand-final strong {
    font-size: 25px;
  }
  .login-visual h1 {
    font-size: 40px;
  }
  .login-visual p {
    margin-top: 11px;
    font-size: 12px;
  }
  .visual-benefits {
    margin-top: 16px;
    gap: 7px;
  }
  .visual-benefits .q-icon {
    width: 29px;
    height: 29px;
  }
  .login-panel {
    padding-top: 22px;
    padding-bottom: 22px;
  }
  .panel-topbar,
  .login-heading {
    margin-bottom: 11px;
  }
  .login-heading h2 {
    font-size: 28px;
  }
  .google-login-block {
    margin-bottom: 9px;
  }
  .login-form {
    gap: 8px;
  }
  .login-panel__inner :deep(.q-field__control),
  .login-submit {
    min-height: 47px;
  }
  .login-register {
    margin-top: 10px;
    padding-top: 10px;
  }
  .login-assurance {
    margin-top: 8px;
  }
}

/* ===== TABLET / MÓVIL: LOGIN LIMPIO, SIN PANEL LATERAL ===== */
@media (max-width: 1199px) {
  .login-page {
    min-height: 100dvh;
    overflow-x: hidden;
    overflow-y: auto;
    background: linear-gradient(180deg, #eef7f1 0%, #f8fbf9 100%);
  }

  .login-shell {
    width: 100%;
    min-height: 100dvh;
    display: block;
    background: transparent;
  }

  /* En tablet y celular NO mostramos el panel lateral gigante. */
  .login-visual {
    display: none !important;
  }

  .login-panel {
    width: 100%;
    min-height: 100dvh;
    padding: 24px 18px 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: visible;
    background:
      radial-gradient(circle at 90% 4%, rgba(13, 147, 70, .10), transparent 28%),
      linear-gradient(180deg, #f7fbf8 0%, #eef6f0 100%);
  }

  .login-panel__inner {
    width: min(100%, 520px);
    margin: 0 auto;
    padding: 24px 22px 26px;
    border: 1px solid #dce9df;
    border-radius: 24px;
    background: rgba(255,255,255,.98);
    box-shadow: 0 22px 60px rgba(12, 69, 35, .12);
  }

  .panel-topbar {
    margin-bottom: 16px;
  }

  .secure-pill {
    display: inline-flex;
  }

  .login-mobile-brand {
    display: block;
    margin-bottom: 18px;
  }

  .mobile-brand-final {
    justify-content: center;
    text-align: left;
  }

  .mobile-brand-final > img {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    box-shadow: 0 10px 24px rgba(9, 118, 55, .15);
  }

  .mobile-brand-final strong {
    font-size: 26px;
  }

  .mobile-brand-final small {
    font-size: 8px;
  }

  .login-heading {
    margin-bottom: 17px;
    text-align: center;
  }

  .login-heading h2 {
    font-size: clamp(27px, 5.5vw, 33px);
  }

  .login-heading p {
    font-size: 13px;
  }

  .google-login-block {
    margin-bottom: 14px;
  }

  .google-button-host {
    width: 100%;
  }

  .login-panel__inner :deep(.q-field__control),
  .login-submit {
    min-height: 52px;
  }

  .form-links {
    grid-template-columns: 1fr auto;
    gap: 8px;
  }

  .login-register {
    margin-top: 15px;
    padding-top: 14px;
  }
}

@media (max-width: 600px) {
  .login-panel {
    min-height: 100dvh;
    padding: 12px 8px 18px;
    align-items: flex-start;
  }

  .login-panel__inner {
    width: 100%;
    margin-top: 6px;
    padding: 18px 14px 20px;
    border-radius: 20px;
    box-shadow: 0 16px 36px rgba(12, 69, 35, .10);
  }

  .panel-topbar {
    margin-bottom: 10px;
  }

  .secure-pill {
    display: none;
  }

  .back-link {
    margin-left: -8px;
  }

  .login-mobile-brand {
    margin-bottom: 12px;
  }

  .mobile-brand-final {
    justify-content: flex-start;
  }

  .mobile-brand-final > img {
    width: 48px;
    height: 48px;
  }

  .mobile-brand-final strong {
    font-size: 22px;
  }

  .mobile-brand-final small {
    display: block;
    font-size: 7px;
  }

  .login-heading {
    margin-bottom: 14px;
    text-align: left;
  }

  .login-heading h2 {
    margin: 4px 0 5px;
    font-size: 25px;
  }

  .login-heading p {
    font-size: 12px;
  }

  .login-form {
    gap: 10px;
  }

  .form-links {
    grid-template-columns: 1fr;
    gap: 0;
  }

  .form-links :deep(.q-btn) {
    justify-self: end;
    margin-right: -8px;
  }

  .role-hint {
    grid-row: 2;
    margin-top: 2px;
  }

  .login-assurance {
    display: none;
  }

  .login-footer-copy {
    margin-top: 8px;
  }
}
</style>
