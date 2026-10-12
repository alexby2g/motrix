<template>
  <div class="landing-page">
    <header class="landing-header">
      <div class="container header-inner">
        <button class="brand" type="button" @click="scrollToSection('inicio')">
          <img src="/motrix-logo-oficial.png" alt="Logo oficial MOTRIX">
          <div class="brand-copy">
            <strong>MOTRIX</strong>
            <small>TU MOTOTAXI MÁS CERCA</small>
          </div>
        </button>

        <nav class="desktop-nav" aria-label="Navegación principal">
          <button @click="scrollToSection('inicio')">Inicio</button>
          <button @click="scrollToSection('beneficios')">Beneficios</button>
          <button @click="scrollToSection('como-funciona')">Cómo funciona</button>
          <button @click="scrollToSection('descarga')">Descargar app</button>
          <button @click="scrollToSection('trinidad')">Trinidad</button>
        </nav>

        <div class="header-actions">
          <q-btn outline color="green-8" no-caps icon="person_outline" label="Iniciar sesión" @click="router.push('/login')" />
          <q-btn unelevated color="green-8" no-caps icon="person_add" label="Regístrate" @click="router.push('/registro-pasajero')" />
        </div>

        <q-btn
          class="mobile-menu-btn"
          flat
          round
          icon="menu"
          color="green-9"
          @click="menuOpen = !menuOpen"
        />
      </div>

      <div v-if="menuOpen" class="mobile-menu">
        <button @click="goMobile('inicio')">Inicio</button>
        <button @click="goMobile('beneficios')">Beneficios</button>
        <button @click="goMobile('como-funciona')">Cómo funciona</button>
        <button @click="goMobile('descarga')">Descargar app</button>
        <button @click="goMobile('trinidad')">Trinidad</button>

        <div class="mobile-menu-actions">
          <q-btn outline color="green-8" no-caps label="Iniciar sesión" @click="goLogin" />
          <q-btn unelevated color="green-8" no-caps label="Regístrate" @click="goRegister" />
        </div>
      </div>
    </header>

    <!-- HERO: igual al estilo de la segunda referencia, dividido 50/50 -->
    <section id="inicio" class="hero-split">
      <div class="hero-copy-panel">
        <div class="hero-copy-inner">
          <div class="location-pill">
            <q-icon name="location_on" />
            <span>TRINIDAD · BENI · BOLIVIA</span>
          </div>

          <h1>
            Tu mototaxi
            <span>fácil, seguro</span>
            y al instante
          </h1>

          <p>
            MOTRIX conecta pasajeros, mototaxistas, sindicatos y federaciones
            en Trinidad para una movilidad más segura, ordenada y moderna.
          </p>

          <div class="hero-actions">
            <q-btn
              unelevated
              color="green-6"
              no-caps
              icon="download"
              label="Descargar APK"
              @click="descargarApk"
            />
            <q-btn
              outline
              color="white"
              no-caps
              icon="play_circle"
              label="Ver cómo funciona"
              @click="scrollToSection('como-funciona')"
            />
          </div>

          <div class="hero-badges">
            <span><q-icon name="verified_user" /> Seguro</span>
            <span><q-icon name="groups" /> Confiable</span>
            <span><q-icon name="location_city" /> 100% Trinidad</span>
          </div>
        </div>
      </div>

      <div class="hero-photo-panel" aria-label="MOTRIX en Trinidad" />
    </section>

    <section id="beneficios" class="benefits">
      <div class="container benefits-grid">
        <article v-for="item in benefits" :key="item.title" class="benefit-card">
          <div class="benefit-icon">
            <q-icon :name="item.icon" />
          </div>
          <h3>{{ item.title }}</h3>
          <p>{{ item.text }}</p>
        </article>
      </div>
    </section>

    <section id="como-funciona" class="steps">
      <div class="container">
        <div class="section-heading center">
          <h2>¿Cómo funciona?</h2>
          <p>Tu mototaxi en 4 simples pasos.</p>
        </div>

        <div class="steps-grid">
          <article v-for="(step, index) in steps" :key="step.title" class="step-card">
            <span class="step-number">{{ index + 1 }}</span>
            <div class="step-icon">
              <q-icon :name="step.icon" />
            </div>
            <h3>{{ step.title }}</h3>
            <p>{{ step.text }}</p>
          </article>
        </div>
      </div>
    </section>

    <!-- DESCARGA: sin la imagen fea, reemplazada por tarjeta visual limpia -->
    <section id="descarga" class="download">
      <div class="container download-grid">
        <div class="download-card">
          <div class="app-mark">
            <img src="/motrix-logo-oficial.png" alt="MOTRIX">
          </div>

          <div class="download-card-copy">
            <span class="app-kicker">APP OFICIAL</span>
            <strong>MOTRIX para Android</strong>
            <p>Tu mototaxi, más cerca.</p>
          </div>

          <div class="phone-preview">
            <div class="phone-top">
              <span>9:41</span>
              <q-icon name="signal_cellular_alt" />
            </div>

            <div class="phone-brand">
              <img src="/motrix-logo-oficial.png" alt="">
              <strong>MOTRIX</strong>
            </div>

            <div class="phone-map">
              <q-icon name="location_on" class="pin pin-start" />
              <div class="route-line" />
              <q-icon name="two_wheeler" class="bike" />
              <q-icon name="location_on" class="pin pin-end" />
            </div>

            <div class="phone-status">
              <small>Tu mototaxi llegará en</small>
              <strong>3 min</strong>
            </div>
          </div>

          <div class="download-card-footer">
            <span><q-icon name="verified" /> APK oficial</span>
            <span><q-icon name="security" /> Descarga segura</span>
          </div>
        </div>

        <div class="download-copy">
          <span class="eyebrow">DESCARGA OFICIAL</span>
          <h2>Descarga MOTRIX para Android</h2>
          <p>
            Instala la aplicación oficial de MOTRIX directamente desde nuestro sitio.
            Sin QR y sin pasos innecesarios.
          </p>

          <div class="download-points">
            <span><q-icon name="check_circle" /> Instalación guiada</span>
            <span><q-icon name="lock" /> Acceso seguro</span>
            <span><q-icon name="system_update_alt" /> Siempre actualizada</span>
          </div>

          <div class="download-actions">
            <q-btn
              unelevated
              color="green-8"
              no-caps
              icon="download"
              label="Descargar APK oficial"
              @click="descargarApk"
            />
            <q-btn
              outline
              color="green-8"
              no-caps
              icon="person_add"
              label="Crear cuenta"
              @click="router.push('/registro-pasajero')"
            />
          </div>

          <small class="download-note">
            Disponible para Android. La descarga inicia directamente desde motrixbolivia.com.
          </small>
        </div>
      </div>
    </section>

    <section id="trinidad" class="trinidad">
      <div class="trinidad-overlay" />
      <div class="container trinidad-inner">
        <div class="trinidad-copy">
          <h2>MOTRIX en Trinidad</h2>
          <p>Una ciudad en movimiento, más unida y más cerca de ti.</p>
        </div>

        <div class="trinidad-box">
          <strong><q-icon name="place" /> Trinidad, Beni, Bolivia</strong>
          <p>
            Apoyamos el transporte local y trabajamos junto a mototaxistas,
            sindicatos y federaciones para una Trinidad más conectada.
          </p>
        </div>
      </div>
    </section>

    <footer class="landing-footer">
      <div class="container footer-grid">
        <div class="footer-brand">
          <img src="/motrix-logo-oficial.png" alt="Logo MOTRIX">
          <div>
            <strong>MOTRIX</strong>
            <small>TU MOTOTAXI MÁS CERCA</small>
          </div>
        </div>

        <div class="footer-links">
          <strong>Enlaces</strong>
          <button @click="scrollToSection('inicio')">Inicio</button>
          <button @click="scrollToSection('beneficios')">Beneficios</button>
          <button @click="scrollToSection('como-funciona')">Cómo funciona</button>
          <button @click="scrollToSection('descarga')">Descarga</button>
        </div>

        <div class="footer-links">
          <strong>Legal</strong>
          <span>Términos y condiciones</span>
          <span>Política de privacidad</span>
          <span>Centro de ayuda</span>
        </div>

        <div class="footer-location">
          <q-icon name="location_on" />
          <div>
            <strong>Trinidad, Beni · Bolivia</strong>
            <small>Tu mototaxi más cerca.</small>
          </div>
        </div>
      </div>

      <div class="container footer-bottom">
        <span>© MOTRIX. Todos los derechos reservados.</span>
        <span>Movilidad segura para Trinidad.</span>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const menuOpen = ref(false)

const benefits = [
  {
    icon: 'two_wheeler',
    title: 'Solicita tu mototaxi',
    text: 'Pide un mototaxi desde tu celular en segundos.'
  },
  {
    icon: 'shield',
    title: 'Viaja con seguridad',
    text: 'Conductores verificados y viajes monitoreados.'
  },
  {
    icon: 'groups',
    title: 'Gestión sindical',
    text: 'Conectamos mototaxistas, sindicatos y federaciones.'
  },
  {
    icon: 'payments',
    title: 'Pagos flexibles',
    text: 'Efectivo o métodos digitales, tú eliges.'
  }
]

const steps = [
  {
    icon: 'smartphone',
    title: 'Solicita',
    text: 'Ingresa tu destino y solicita tu mototaxi.'
  },
  {
    icon: 'person_pin_circle',
    title: 'Espera',
    text: 'Un mototaxista cercano acepta tu viaje.'
  },
  {
    icon: 'two_wheeler',
    title: 'Viaja',
    text: 'Sigue el estado de tu viaje en tiempo real.'
  },
  {
    icon: 'flag',
    title: 'Llega',
    text: 'Finaliza y califica tu servicio.'
  }
]

function scrollToSection(id) {
  menuOpen.value = false
  requestAnimationFrame(() => {
    document.getElementById(id)?.scrollIntoView({
      behavior: 'smooth',
      block: 'start'
    })
  })
}

function goMobile(id) {
  scrollToSection(id)
}

function goLogin() {
  menuOpen.value = false
  router.push('/login')
}

function goRegister() {
  menuOpen.value = false
  router.push('/registro-pasajero')
}

function descargarApk() {
  window.location.href = 'https://motrixbolivia.com/downloads/MOTRIX.apk'
}
</script>

<style scoped>
:global(html) { scroll-behavior: smooth; }
:global(body) { margin: 0; background: #fff; }

.landing-page {
  background: #fff;
  color: #10271a;
  overflow-x: hidden;
}

.container {
  width: min(1180px, calc(100% - 36px));
  margin: 0 auto;
}

/* HEADER */
.landing-header {
  position: sticky;
  top: 0;
  z-index: 100;
  background: rgba(255,255,255,.98);
  backdrop-filter: blur(14px);
  border-bottom: 1px solid #e5ebe7;
}

.header-inner {
  min-height: 86px;
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  gap: 24px;
}

.brand,
.desktop-nav button,
.footer-links button,
.mobile-menu button {
  border: 0;
  background: transparent;
  cursor: pointer;
}

.brand {
  display: flex;
  align-items: center;
  gap: 12px;
  color: inherit;
}

.brand img,
.footer-brand img {
  width: 56px;
  height: 56px;
  object-fit: contain;
  border-radius: 16px;
}

.brand-copy,
.footer-brand div {
  display: grid;
  text-align: left;
}

.brand-copy strong,
.footer-brand strong {
  font-size: 26px;
  line-height: 1;
  font-weight: 900;
  letter-spacing: -.03em;
}

.brand-copy small,
.footer-brand small {
  margin-top: 4px;
  color: #108845;
  font-size: 9px;
  font-weight: 900;
}

.desktop-nav {
  display: flex;
  justify-content: center;
  gap: 30px;
}

.desktop-nav button {
  color: #21382b;
  font-size: 15px;
  font-weight: 800;
}

.header-actions {
  display: flex;
  gap: 10px;
}

.header-actions :deep(.q-btn) {
  min-height: 44px;
  border-radius: 14px;
  font-weight: 800;
  padding-inline: 18px;
}

.mobile-menu-btn,
.mobile-menu {
  display: none;
}

/* HERO SPLIT */
.hero-split {
  display: grid;
  grid-template-columns: 48% 52%;
  min-height: 620px;
  background: #04371f;
}

.hero-copy-panel {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  background: #04371f;
}

.hero-copy-inner {
  width: min(100%, 570px);
  padding: 54px 56px 54px 28px;
  color: #fff;
}

.hero-photo-panel {
  min-height: 620px;
  background:
    url('/motrix-hero-visual-final.jpg')
    58% center / cover no-repeat;
}

.location-pill {
  width: fit-content;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 9px 14px;
  border-radius: 999px;
  border: 1px solid #35dc84;
  background: rgba(6,92,49,.28);
  font-size: 11px;
  font-weight: 900;
}

.hero-split h1 {
  margin: 20px 0 18px;
  font-size: clamp(58px, 5vw, 78px);
  line-height: .92;
  letter-spacing: -.06em;
  font-weight: 900;
}

.hero-split h1 span {
  display: block;
  color: #35ea86;
}

.hero-split p {
  margin: 0;
  max-width: 500px;
  font-size: 18px;
  line-height: 1.55;
  color: rgba(255,255,255,.96);
}

.hero-actions {
  margin-top: 28px;
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.hero-actions :deep(.q-btn) {
  min-height: 54px;
  border-radius: 14px;
  font-weight: 900;
  padding-inline: 24px;
}

.hero-badges {
  margin-top: 22px;
  display: flex;
  flex-wrap: wrap;
  gap: 18px;
  font-size: 12px;
  font-weight: 800;
}

.hero-badges span {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

/* BENEFITS */
.benefits {
  padding: 26px 0;
  background: linear-gradient(180deg,#eef8f2,#fbfdfc);
}

.benefits-grid {
  display: grid;
  grid-template-columns: repeat(4,1fr);
  gap: 16px;
}

.benefit-card {
  padding: 24px 18px;
  text-align: center;
  border-radius: 18px;
  background: #fff;
  border: 1px solid #e2ece5;
  box-shadow: 0 12px 28px rgba(16,84,49,.06);
}

.benefit-icon {
  width: 64px;
  height: 64px;
  margin: 0 auto 12px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: linear-gradient(145deg,#17b85f,#0a873f);
  color: #fff;
  font-size: 30px;
}

.benefit-card h3 {
  margin: 0 0 8px;
  font-size: 18px;
  font-weight: 900;
}

.benefit-card p {
  margin: 0;
  color: #66766d;
  font-size: 13px;
  line-height: 1.45;
}

/* STEPS */
.steps {
  padding: 70px 0 56px;
  background:
    radial-gradient(circle at 50% 0%,#effbf4 0%,transparent 58%),
    #fff;
}

.section-heading.center {
  text-align: center;
}

.section-heading h2 {
  margin: 0;
  font-size: clamp(38px,4vw,54px);
  font-weight: 900;
  line-height: .98;
  letter-spacing: -.04em;
}

.section-heading p {
  margin: 8px 0 0;
  color: #607167;
  font-size: 16px;
}

.steps-grid {
  margin-top: 36px;
  display: grid;
  grid-template-columns: repeat(4,1fr);
  gap: 18px;
}

.step-card {
  position: relative;
  padding: 16px;
  text-align: center;
}

.step-number {
  position: absolute;
  top: 0;
  left: calc(50% - 58px);
  width: 32px;
  height: 32px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: #0a8b43;
  color: #fff;
  font-weight: 900;
}

.step-icon {
  width: 108px;
  height: 108px;
  margin: 0 auto 14px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: #eaf8ef;
  color: #0a8740;
  font-size: 50px;
}

.step-card h3 {
  margin: 0 0 6px;
  font-size: 19px;
  font-weight: 900;
}

.step-card p {
  margin: 0 auto;
  max-width: 220px;
  color: #69786f;
  font-size: 13px;
  line-height: 1.45;
}

/* DOWNLOAD */
.download {
  padding: 56px 0 64px;
  background: #fff;
}

.download-grid {
  display: grid;
  grid-template-columns: .92fr 1.08fr;
  align-items: center;
  gap: 56px;
}

.download-card {
  position: relative;
  min-height: 440px;
  padding: 32px;
  overflow: hidden;
  border-radius: 30px;
  color: #fff;
  background:
    radial-gradient(circle at 80% 20%,rgba(64,232,135,.32),transparent 32%),
    linear-gradient(145deg,#0d7f41,#04371f);
  box-shadow: 0 26px 68px rgba(6,73,39,.22);
}

.app-mark {
  width: 78px;
  height: 78px;
  display: grid;
  place-items: center;
  border-radius: 22px;
  background: #fff;
  box-shadow: 0 12px 30px rgba(0,0,0,.16);
}

.app-mark img {
  width: 64px;
  height: 64px;
  object-fit: contain;
}

.download-card-copy {
  margin-top: 22px;
  display: grid;
}

.app-kicker {
  color: #8ff2b5;
  font-size: 11px;
  font-weight: 900;
  letter-spacing: .13em;
}

.download-card-copy strong {
  margin-top: 5px;
  font-size: 34px;
  line-height: 1;
  font-weight: 900;
}

.download-card-copy p {
  margin: 7px 0 0;
  color: rgba(255,255,255,.82);
  font-size: 14px;
}

.phone-preview {
  position: absolute;
  right: 28px;
  bottom: -48px;
  width: 205px;
  height: 350px;
  padding: 14px 13px;
  border: 8px solid #111;
  border-radius: 34px;
  background: #f8fbf9;
  color: #123423;
  box-shadow: 0 26px 54px rgba(0,0,0,.28);
  transform: rotate(4deg);
}

.phone-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 9px;
  font-weight: 800;
}

.phone-brand {
  margin-top: 13px;
  display: flex;
  align-items: center;
  gap: 7px;
  color: #08783c;
}

.phone-brand img {
  width: 27px;
  height: 27px;
  object-fit: contain;
}

.phone-brand strong {
  font-size: 15px;
}

.phone-map {
  position: relative;
  height: 160px;
  margin-top: 13px;
  overflow: hidden;
  border-radius: 16px;
  background:
    linear-gradient(90deg,transparent 49%,#e7eee9 50%,transparent 51%),
    linear-gradient(0deg,transparent 49%,#e7eee9 50%,transparent 51%),
    #eef5f0;
  background-size: 34px 34px;
}

.pin,
.bike {
  position: absolute;
  z-index: 3;
  color: #0d9348;
}

.pin-start {
  left: 22px;
  bottom: 25px;
  color: #e74636;
}

.pin-end {
  right: 18px;
  top: 20px;
}

.bike {
  left: 83px;
  top: 82px;
}

.route-line {
  position: absolute;
  left: 38px;
  top: 92px;
  width: 110px;
  height: 4px;
  background: #15964d;
  border-radius: 99px;
  transform: rotate(-32deg);
}

.phone-status {
  margin-top: 12px;
  text-align: center;
}

.phone-status small {
  display: block;
  color: #76867d;
  font-size: 9px;
}

.phone-status strong {
  display: block;
  margin-top: 2px;
  color: #0b8b43;
  font-size: 25px;
}

.download-card-footer {
  position: absolute;
  left: 32px;
  bottom: 26px;
  display: grid;
  gap: 7px;
  font-size: 12px;
  font-weight: 800;
}

.download-card-footer span {
  display: flex;
  align-items: center;
  gap: 6px;
}

.download-copy .eyebrow {
  display: inline-block;
  margin-bottom: 10px;
  color: #0f8a43;
  font-size: 12px;
  font-weight: 900;
  letter-spacing: .14em;
}

.download-copy h2 {
  margin: 0 0 12px;
  font-size: clamp(42px,4.5vw,60px);
  line-height: .98;
  letter-spacing: -.05em;
  font-weight: 900;
}

.download-copy p {
  margin: 0;
  max-width: 560px;
  color: #5f7067;
  font-size: 16px;
  line-height: 1.58;
}

.download-points {
  margin-top: 22px;
  display: flex;
  flex-wrap: wrap;
  gap: 18px;
  color: #274537;
  font-size: 13px;
  font-weight: 800;
}

.download-points span {
  display: inline-flex;
  align-items: center;
  gap: 7px;
}

.download-points .q-icon {
  color: #169247;
  font-size: 20px;
}

.download-actions {
  margin-top: 28px;
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.download-actions :deep(.q-btn) {
  min-height: 54px;
  border-radius: 14px;
  font-weight: 900;
  padding-inline: 22px;
}

.download-note {
  display: block;
  margin-top: 12px;
  color: #87948c;
  font-size: 12px;
}

/* TRINIDAD */
.trinidad {
  position: relative;
  min-height: 320px;
  display: flex;
  align-items: center;
  background: url('/motrix-trinidad-final.png') center center / cover no-repeat;
}

.trinidad-overlay {
  position: absolute;
  inset: 0;
  background:
    linear-gradient(90deg,rgba(3,37,22,.84),rgba(3,37,22,.52) 55%,rgba(3,37,22,.24));
}

.trinidad-inner {
  position: relative;
  z-index: 2;
  padding: 58px 0;
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 30px;
  color: #fff;
}

.trinidad-copy h2 {
  margin: 0;
  font-size: clamp(44px,4vw,62px);
  line-height: .95;
  font-weight: 900;
  letter-spacing: -.04em;
}

.trinidad-copy p {
  margin: 14px 0 0;
  font-size: 21px;
}

.trinidad-box {
  width: min(390px,100%);
  padding: 18px 20px;
  border-radius: 18px;
  border: 1px solid rgba(255,255,255,.18);
  background: rgba(3,35,20,.58);
  backdrop-filter: blur(10px);
}

.trinidad-box strong {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 15px;
}

.trinidad-box p {
  margin: 10px 0 0;
  font-size: 13px;
  line-height: 1.5;
  color: rgba(255,255,255,.9);
}

/* FOOTER */
.landing-footer {
  padding: 34px 0 20px;
  background: #063a22;
  color: #fff;
}

.footer-grid {
  display: grid;
  grid-template-columns: 1.2fr .8fr .8fr 1.1fr;
  gap: 30px;
}

.footer-brand {
  display: flex;
  align-items: center;
  gap: 12px;
}

.footer-links {
  display: grid;
  gap: 7px;
}

.footer-links strong {
  margin-bottom: 4px;
  font-size: 13px;
}

.footer-links button,
.footer-links span {
  padding: 0;
  text-align: left;
  color: rgba(255,255,255,.76);
  font-size: 12px;
}

.footer-location {
  display: flex;
  align-items: flex-start;
  gap: 10px;
}

.footer-location > .q-icon {
  font-size: 28px;
  color: #93e7b1;
}

.footer-location div {
  display: grid;
}

.footer-location strong {
  font-size: 14px;
}

.footer-location small {
  margin-top: 5px;
  color: rgba(255,255,255,.66);
  font-size: 11px;
}

.footer-bottom {
  margin-top: 28px;
  padding-top: 16px;
  border-top: 1px solid rgba(255,255,255,.12);
  display: flex;
  justify-content: space-between;
  gap: 14px;
  color: rgba(255,255,255,.6);
  font-size: 11px;
}

/* TABLET */
@media (max-width: 1080px) {
  .desktop-nav,
  .header-actions {
    display: none;
  }

  .mobile-menu-btn {
    display: inline-flex;
    justify-self: end;
  }

  .header-inner {
    grid-template-columns: 1fr auto;
  }

  .mobile-menu {
    display: grid;
    gap: 4px;
    padding: 10px 18px 18px;
    background: #fff;
    border-top: 1px solid #e7eee9;
  }

  .mobile-menu button {
    padding: 12px 6px;
    text-align: left;
    font-weight: 800;
    color: #1d3929;
  }

  .mobile-menu-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-top: 8px;
  }

  .benefits-grid {
    grid-template-columns: repeat(2,1fr);
  }

  .steps-grid {
    grid-template-columns: repeat(2,1fr);
  }

  .download-grid {
    grid-template-columns: 1fr;
  }

  .download-card {
    max-width: 720px;
    width: 100%;
    margin: 0 auto;
  }

  .trinidad-inner {
    flex-direction: column;
    align-items: flex-start;
  }

  .footer-grid {
    grid-template-columns: 1fr 1fr;
  }
}

/* MOBILE */
@media (max-width: 767px) {
  .container {
    width: min(100% - 24px,640px);
  }

  .header-inner {
    min-height: 72px;
  }

  .brand img {
    width: 48px;
    height: 48px;
  }

  .brand-copy strong {
    font-size: 22px;
  }

  .brand-copy small {
    font-size: 8px;
  }

  .hero-split {
    grid-template-columns: 1fr;
    min-height: 0;
  }

  .hero-photo-panel {
    order: 1;
    min-height: 330px;
    background-position: 58% center;
  }

  .hero-copy-panel {
    order: 2;
    justify-content: stretch;
  }

  .hero-copy-inner {
    width: 100%;
    padding: 30px 20px 42px;
  }

  .hero-split h1 {
    font-size: clamp(44px,13vw,58px);
  }

  .hero-split p {
    font-size: 15px;
  }

  .hero-actions {
    display: grid;
    grid-template-columns: 1fr;
  }

  .hero-actions :deep(.q-btn) {
    width: 100%;
  }

  .benefits-grid {
    grid-template-columns: repeat(2,1fr);
    gap: 10px;
  }

  .benefit-card {
    padding: 18px 12px;
  }

  .benefit-icon {
    width: 54px;
    height: 54px;
    font-size: 26px;
  }

  .benefit-card h3 {
    font-size: 15px;
  }

  .benefit-card p {
    font-size: 11px;
  }

  .steps {
    padding: 50px 0 34px;
  }

  .steps-grid {
    grid-template-columns: 1fr;
    gap: 12px;
    margin-top: 26px;
  }

  .step-card {
    min-height: 92px;
    padding: 14px 12px 14px 92px;
    text-align: left;
  }

  .step-number {
    left: 4px;
    top: 6px;
    width: 26px;
    height: 26px;
    font-size: 11px;
  }

  .step-icon {
    position: absolute;
    left: 22px;
    top: 18px;
    width: 58px;
    height: 58px;
    margin: 0;
    font-size: 30px;
  }

  .step-card h3 {
    font-size: 17px;
  }

  .step-card p {
    max-width: none;
    margin: 0;
    font-size: 12px;
  }

  .download {
    padding: 38px 0 48px;
  }

  .download-card {
    min-height: 430px;
    padding: 24px;
    border-radius: 24px;
  }

  .download-card-copy strong {
    font-size: 28px;
  }

  .phone-preview {
    right: 18px;
    width: 175px;
    height: 315px;
  }

  .download-card-footer {
    left: 24px;
    bottom: 20px;
    max-width: 125px;
    font-size: 10px;
  }

  .download-copy h2 {
    font-size: 40px;
  }

  .download-copy p {
    font-size: 14px;
  }

  .download-points {
    display: grid;
    gap: 10px;
  }

  .download-actions {
    display: grid;
    grid-template-columns: 1fr;
  }

  .download-actions :deep(.q-btn) {
    width: 100%;
  }

  .trinidad {
    min-height: 380px;
    background-position: 56% center;
  }

  .trinidad-inner {
    padding: 48px 0;
  }

  .trinidad-copy h2 {
    font-size: 42px;
  }

  .trinidad-copy p {
    font-size: 17px;
  }

  .footer-grid {
    grid-template-columns: 1fr;
    gap: 24px;
  }

  .footer-bottom {
    flex-direction: column;
    gap: 6px;
  }
}

@media (max-width: 430px) {
  .brand-copy strong {
    font-size: 20px;
  }

  .brand-copy small {
    font-size: 7px;
  }

  .benefits-grid {
    grid-template-columns: 1fr;
  }

  .hero-photo-panel {
    min-height: 290px;
  }

  .hero-split h1 {
    font-size: 40px;
  }

  .phone-preview {
    right: 12px;
    width: 158px;
    height: 285px;
  }

  .download-card {
    min-height: 400px;
  }
}
</style>