<template>
  <q-layout view="hHh lpr fff">
    <q-page-container>
      <q-page class="register-page">
        <div class="register-shell">
          <section class="register-visual" aria-label="Identidad MOTRIX">
            <div class="register-visual__shade" />

            <div class="register-visual__top">
              <div class="visual-brand-final">
                <img src="/motrix-logo-oficial.png" alt="Logo oficial MOTRIX" />
                <div>
                  <strong>MOTRIX</strong>
                  <small>MOVILIDAD · GESTIÓN · SEGURIDAD</small>
                </div>
              </div>

              <span class="visual-city">
                <q-icon name="location_on" />
                Trinidad · Beni
              </span>
            </div>

            <div class="register-visual__content">
              <span class="visual-eyebrow">CUENTA DE PASAJERO</span>
              <h1>
                Tu viaje empieza<br>
                <strong>desde aquí.</strong>
              </h1>
              <p>
                Regístrate una sola vez para solicitar viajes, consultar tu
                historial y calificar tus servicios dentro de MOTRIX.
              </p>

              <div class="visual-benefits">
                <div>
                  <q-icon name="my_location" />
                  <span>Solicita desde tu ubicación</span>
                </div>
                <div>
                  <q-icon name="payments" />
                  <span>Conoce la tarifa antes de confirmar</span>
                </div>
                <div>
                  <q-icon name="verified_user" />
                  <span>Viaja con mototaxistas registrados</span>
                </div>
              </div>
            </div>

            <div class="register-visual__bottom">
              <span><q-icon name="shield" /> Registro oficial MOTRIX</span>
              <span>motrixbolivia.com</span>
            </div>
          </section>

          <section class="register-panel">
            <div class="register-panel__inner">
              <div class="panel-topbar">
                <q-btn
                  flat
                  dense
                  no-caps
                  icon="arrow_back"
                  label="Volver"
                  color="green-9"
                  class="back-link"
                  @click="volver"
                />

                <span class="secure-pill">
                  <q-icon name="lock" /> Registro seguro
                </span>
              </div>

              <div class="register-mobile-brand">
                <div class="mobile-brand-final">
                  <img src="/motrix-logo-oficial.png" alt="Logo oficial MOTRIX" />
                  <div>
                    <strong>MOTRIX</strong>
                    <small>Movilidad · Gestión · Seguridad</small>
                  </div>
                </div>
              </div>

              <div class="register-heading">
                <span class="register-eyebrow">NUEVO PASAJERO</span>
                <h2>Crea tu cuenta MOTRIX</h2>
                <p>Completa tus datos para comenzar a solicitar viajes.</p>
              </div>

              <q-form greedy class="register-form" @submit.prevent="registrar">
                <div class="form-grid">
                  <q-input
                    v-model.trim="form.nombre"
                    outlined
                    label="Nombre *"
                    autocomplete="given-name"
                    hide-bottom-space
                    lazy-rules
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
                    hide-bottom-space
                    lazy-rules
                    :rules="[reglaObligatoria('Los apellidos son obligatorios')]"
                  >
                    <template #prepend>
                      <q-icon name="badge" color="green-8" />
                    </template>
                  </q-input>

                  <q-input
                    v-model.trim="form.ci"
                    outlined
                    label="Cédula de identidad (opcional)"
                    autocomplete="off"
                    hide-bottom-space
                  >
                    <template #prepend>
                      <q-icon name="credit_card" color="green-8" />
                    </template>
                  </q-input>

                  <q-input
                    v-model.trim="form.telefono"
                    outlined
                    label="Número de celular *"
                    type="tel"
                    autocomplete="tel"
                    hide-bottom-space
                    lazy-rules
                    :rules="reglasTelefono"
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
                  hide-bottom-space
                >
                  <template #prepend>
                    <q-icon name="home" color="green-8" />
                  </template>
                </q-input>

                <q-input
                  v-model.trim="form.email"
                  outlined
                  label="Correo electrónico (opcional)"
                  type="email"
                  autocomplete="email"
                  hide-bottom-space
                  lazy-rules
                  :rules="reglasEmailOpcional"
                >
                  <template #prepend>
                    <q-icon name="mail" color="green-8" />
                  </template>
                </q-input>

                <div class="form-grid">
                  <q-input
                    v-model="form.password"
                    outlined
                    label="Contraseña *"
                    autocomplete="new-password"
                    hide-bottom-space
                    lazy-rules
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
                        color="grey-7"
                        @click="mostrarPassword = !mostrarPassword"
                      />
                    </template>
                  </q-input>

                  <q-input
                    v-model="form.password_confirmation"
                    outlined
                    label="Confirmar contraseña *"
                    autocomplete="new-password"
                    hide-bottom-space
                    lazy-rules
                    :type="mostrarPassword ? 'text' : 'password'"
                    :rules="reglasConfirmacion"
                  >
                    <template #prepend>
                      <q-icon name="lock_reset" color="green-8" />
                    </template>
                  </q-input>
                </div>

                <q-banner rounded class="motrix-info-banner">
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
                  no-caps
                  class="full-width register-submit"
                  :loading="cargando"
                />
              </q-form>

              <div class="login-link">
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

              <div class="register-footer-copy">MOTRIX · Trinidad, Beni · Bolivia</div>
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
import { useRouter } from 'vue-router'

import { api } from '../boot/axios.js'
import { esContenedorNativo } from '../services/googleAuth.js'

const $q = useQuasar()
const router = useRouter()
const esAppNativa = esContenedorNativo()

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

const reglasTelefono = [
  reglaObligatoria('El número de celular es obligatorio'),
  valor => {
    const numero = String(valor || '').replace(/\D+/g, '')
    return (numero.length >= 7 && numero.length <= 15)
      || 'Ingresa un número de celular válido'
  }
]

const reglasEmailOpcional = [
  valor => !String(valor || '').trim()
    || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(valor || ''))
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
      '/auth/registro-pasajero-celular',
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
      message: 'Tu cuenta fue creada. Desde ahora tu número de celular es tu usuario de acceso.'
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
  router.push(
    esAppNativa ? '/login' : '/presentacion'
  )
}

function irLogin() {
  router.push('/login')
}
</script>

<style scoped>
:global(html), :global(body), :global(#q-app) {
  min-height: 100%;
}

.register-page {
  min-height: 100dvh;
  overflow: hidden;
  color: #17361f;
  background: #f4f8f5;
}

.register-shell {
  width: 100%;
  min-height: 100dvh;
  display: grid;
  grid-template-columns: minmax(400px, 40%) minmax(0, 60%);
  background: #f4f8f5;
}

.register-visual {
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

.register-visual__shade {
  position: absolute;
  inset: 0;
  background:
    linear-gradient(90deg, rgba(2, 37, 19, .90) 0%, rgba(4, 60, 31, .70) 52%, rgba(4, 55, 29, .36) 100%),
    linear-gradient(0deg, rgba(2, 28, 15, .38) 0%, transparent 48%);
}

.register-visual__top,
.register-visual__content,
.register-visual__bottom {
  position: relative;
  z-index: 2;
}

.register-visual__top {
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
  color: #fff;
  font-size: 10px;
  font-weight: 850;
  white-space: nowrap;
}

.register-visual__content {
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

.register-visual h1 {
  margin: 0;
  color: #fff;
  font-size: clamp(34px, 3.4vw, 50px);
  line-height: .99;
  letter-spacing: -.045em;
  font-weight: 700;
}

.register-visual h1 strong {
  color: #54e98f;
  font-weight: 900;
}

.register-visual p {
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

.register-visual__bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  color: rgba(255, 255, 255, .72);
  font-size: 9px;
  font-weight: 700;
}

.register-panel {
  position: relative;
  min-width: 0;
  min-height: 100dvh;
  overflow: auto;
  padding: 28px clamp(28px, 4.2vw, 64px);
  display: flex;
  align-items: center;
  justify-content: center;
  background:
    radial-gradient(circle at 100% 0%, rgba(20, 160, 77, .10), transparent 30%),
    linear-gradient(145deg, #fbfdfb 0%, #eef6f0 100%);
}

.register-panel__inner {
  position: relative;
  z-index: 2;
  width: min(100%, 680px);
}

.panel-topbar {
  margin-bottom: 16px;
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

.register-mobile-brand {
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

.register-heading {
  margin-bottom: 16px;
}

.register-eyebrow {
  color: #16833f;
  font-size: 9px;
  font-weight: 900;
  letter-spacing: .13em;
}

.register-heading h2 {
  margin: 5px 0 6px;
  color: #182a1e;
  font-size: clamp(27px, 2.3vw, 34px);
  line-height: 1.06;
  letter-spacing: -.035em;
  font-weight: 850;
}

.register-heading p {
  margin: 0;
  color: #77837a;
  font-size: 13px;
}

.register-form {
  display: grid;
  gap: 12px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.register-panel__inner :deep(.q-field__control) {
  min-height: 52px;
  border-radius: 14px;
  background: #fff;
}

.register-panel__inner :deep(.q-field--outlined .q-field__control:before) {
  border-color: #d7e3da;
}

.register-panel__inner :deep(.q-field--focused .q-field__control:after) {
  border-width: 1.5px;
  color: #159447;
}

.register-panel__inner :deep(input:-webkit-autofill),
.register-panel__inner :deep(input:-webkit-autofill:hover),
.register-panel__inner :deep(input:-webkit-autofill:focus) {
  -webkit-box-shadow: 0 0 0 1000px #fff inset !important;
  -webkit-text-fill-color: #17361f !important;
  transition: background-color 9999s ease-out 0s !important;
}

.motrix-info-banner {
  border: 1px solid #d7e9f8;
  background: #eef7ff;
  color: #236a9e;
  font-size: 12px;
  line-height: 1.45;
}

.register-submit {
  min-height: 52px;
  border-radius: 14px;
  font-size: 14px;
  font-weight: 850;
  box-shadow: 0 12px 24px rgba(38, 125, 50, .17);
}

.login-link {
  margin-top: 15px;
  padding-top: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  border-top: 1px solid #e6eee8;
  color: #8a958c;
  font-size: 11px;
}

.login-link :deep(.q-btn__content) {
  font-size: 11px;
  font-weight: 800;
}

.register-footer-copy {
  margin-top: 8px;
  color: #a2aaa4;
  font-size: 8px;
  text-align: center;
}

@media (max-width: 1199px) {
  .register-page {
    overflow-x: hidden;
    overflow-y: auto;
    background: linear-gradient(180deg, #eef7f1 0%, #f8fbf9 100%);
  }

  .register-shell {
    display: block;
    background: transparent;
  }

  .register-visual {
    display: none !important;
  }

  .register-panel {
    width: 100%;
    min-height: 100dvh;
    padding: 24px 18px 32px;
    align-items: flex-start;
    overflow: visible;
  }

  .register-panel__inner {
    width: min(100%, 680px);
    margin: 0 auto;
    padding: 24px 22px 26px;
    border: 1px solid #dce9df;
    border-radius: 24px;
    background: rgba(255, 255, 255, .98);
    box-shadow: 0 22px 60px rgba(12, 69, 35, .12);
  }

  .register-mobile-brand {
    display: block;
    margin-bottom: 16px;
  }

  .mobile-brand-final {
    justify-content: center;
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

  .register-heading {
    text-align: center;
  }
}

@media (max-width: 620px) {
  .register-panel {
    padding: 12px 8px 18px;
  }

  .register-panel__inner {
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

  .register-mobile-brand {
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
    font-size: 7px;
  }

  .register-heading {
    margin-bottom: 14px;
    text-align: left;
  }

  .register-heading h2 {
    margin: 4px 0 5px;
    font-size: 25px;
  }

  .register-heading p {
    font-size: 12px;
  }

  .form-grid {
    grid-template-columns: 1fr;
    gap: 10px;
  }

  .register-form {
    gap: 10px;
  }

  .motrix-info-banner {
    font-size: 11px;
  }
}
</style>
