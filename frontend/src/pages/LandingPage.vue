<template>
  <div class="landing-page">
    <!-- =========================================================
         ENCABEZADO PÚBLICO
    ========================================================== -->
    <header class="public-header">
      <div class="header-inner">
        <button
          type="button"
          class="brand"
          aria-label="Ir al inicio"
          @click="irInicio"
        >
          <span class="brand-mark">
            <q-icon name="two_wheeler" />
          </span>

          <span class="brand-text">
            <strong>MOTRIX</strong>
            <small>Movilidad · Gestión · Seguridad</small>
          </span>
        </button>

        <nav class="desktop-nav" aria-label="Navegación principal">
          <button type="button" @click="irA('proposito')">
            Propósito
          </button>

          <button type="button" @click="irA('funciones')">
            Funciones
          </button>

          <button type="button" @click="irA('flujo')">
            Cómo funciona
          </button>

          <button type="button" @click="irA('seguridad')">
            Seguridad
          </button>
        </nav>

        <q-btn
          color="green-8"
          icon-right="arrow_forward"
          label="Ingresar"
          unelevated
          no-caps
          class="header-login"
          @click="irLogin"
        />
      </div>
    </header>

    <!-- =========================================================
         HERO
    ========================================================== -->
    <section class="hero-section">
      <div class="hero-glow hero-glow-one" />
      <div class="hero-glow hero-glow-two" />

      <div class="hero-grid">
        <!-- VISUAL -->
        <div class="hero-visual-wrap">
          <div class="hero-visual">
            <div class="visual-badge">
              <q-icon name="location_city" />
              TRINIDAD · BENI · 2026
            </div>

            <div class="visual-map">
              <div class="road road-a" />
              <div class="road road-b" />
              <div class="road road-c" />

              <div class="map-pin pin-origin">
                <q-icon name="my_location" />
              </div>

              <div class="map-pin pin-destination">
                <q-icon name="place" />
              </div>

              <div class="moto-main">
                <div class="moto-ring">
                  <q-icon name="two_wheeler" />
                </div>

                <div class="moto-caption">
                  <strong>MOTRIX</strong>
                  <span>Conectando movilidad</span>
                </div>
              </div>

              <div class="route-line route-one" />
              <div class="route-line route-two" />

              <div class="mini-card passenger-card">
                <q-avatar
                  color="green-1"
                  text-color="green-8"
                  icon="person_pin_circle"
                  size="42px"
                />

                <div>
                  <span>Pasajero</span>
                  <strong>Solicita un viaje</strong>
                </div>
              </div>

              <div class="mini-card driver-card">
                <q-avatar
                  color="green-1"
                  text-color="green-8"
                  icon="two_wheeler"
                  size="42px"
                />

                <div>
                  <span>Mototaxista</span>
                  <strong>Recibe la solicitud</strong>
                </div>
              </div>
            </div>

            <div class="visual-bottom">
              <div>
                <q-icon name="near_me" />
                Ubicación y ruta
              </div>

              <div>
                <q-icon name="shield" />
                Seguridad
              </div>
            </div>
          </div>
        </div>

        <!-- TEXTO -->
        <div class="hero-copy">
          <div class="eyebrow">
            <span class="eyebrow-line" />
            PROYECTO TECNOLÓGICO · FERIA 2026
          </div>

          <h1>
            <span class="hero-brand">MOTRIX</span>

            <span class="hero-title">
              Sistema de Gestión y Solicitud de Mototaxis mediante
              Plataforma Web y Aplicación Móvil en la Ciudad de Trinidad
            </span>
          </h1>

          <p class="hero-description">
            Una plataforma orientada a mejorar la organización,
            solicitud, seguimiento y seguridad del servicio de
            mototaxis, conectando pasajeros, conductores y
            administración en un mismo sistema.
          </p>

          <div class="hero-actions">
            <q-btn
              color="green-8"
              icon="login"
              icon-right="arrow_forward"
              label="Ingresar al sistema"
              unelevated
              no-caps
              class="primary-action"
              @click="irLogin"
            />

            <q-btn
              outline
              color="green-9"
              icon="explore"
              label="Conocer la plataforma"
              no-caps
              class="secondary-action"
              @click="irA('proposito')"
            />
          </div>

          <div class="hero-meta">
            <div class="meta-item">
              <span class="meta-icon meta-green">
                <q-icon name="two_wheeler" />
              </span>

              <div>
                <small>SERVICIO</small>
                <strong>Mototaxis urbanos</strong>
              </div>
            </div>

            <div class="meta-item">
              <span class="meta-icon meta-blue">
                <q-icon name="place" />
              </span>

              <div>
                <small>COBERTURA</small>
                <strong>Ciudad de Trinidad</strong>
              </div>
            </div>
          </div>
        </div>
      </div>

      <button
        type="button"
        class="discover-button"
        @click="irA('proposito')"
      >
        <span>Descubre MOTRIX</span>
        <q-icon name="keyboard_arrow_down" />
      </button>
    </section>

    <!-- =========================================================
         PILARES
    ========================================================== -->
    <section class="pillar-strip">
      <div class="pillar-grid">
        <article class="pillar-item">
          <span class="pillar-icon pillar-blue">
            <q-icon name="manage_accounts" />
          </span>

          <div>
            <strong>Gestión</strong>
            <span>Organización del servicio</span>
          </div>
        </article>

        <article class="pillar-item">
          <span class="pillar-icon pillar-orange">
            <q-icon name="route" />
          </span>

          <div>
            <strong>Solicitud</strong>
            <span>Viajes y asignación</span>
          </div>
        </article>

        <article class="pillar-item">
          <span class="pillar-icon pillar-green">
            <q-icon name="verified_user" />
          </span>

          <div>
            <strong>Seguridad</strong>
            <span>Seguimiento y alertas</span>
          </div>
        </article>

        <article class="pillar-item">
          <span class="pillar-icon pillar-purple">
            <q-icon name="devices" />
          </span>

          <div>
            <strong>Tecnología</strong>
            <span>Web y experiencia móvil</span>
          </div>
        </article>
      </div>
    </section>

    <!-- =========================================================
         PROPÓSITO
    ========================================================== -->
    <section
      id="proposito"
      class="section light-section"
    >
      <div class="section-heading centered-heading">
        <span>PROPÓSITO</span>

        <h2>
          Tecnología para fortalecer la
          <em>movilidad urbana</em>
        </h2>

        <p>
          MOTRIX centraliza en una misma plataforma los procesos de
          gestión y solicitud del servicio, buscando brindar una
          experiencia más organizada para pasajeros, mototaxistas y
          administradores.
        </p>
      </div>

      <div class="purpose-grid">
        <article class="purpose-card">
          <span class="purpose-number">01</span>

          <span class="purpose-icon purpose-blue">
            <q-icon name="hail" />
          </span>

          <h3>Facilitar la solicitud</h3>

          <p>
            El pasajero puede indicar origen y destino, consultar la
            tarifa estimada y solicitar un mototaxi desde la plataforma.
          </p>
        </article>

        <article class="purpose-card">
          <span class="purpose-number">02</span>

          <span class="purpose-icon purpose-orange">
            <q-icon name="two_wheeler" />
          </span>

          <h3>Optimizar la operación</h3>

          <p>
            Los conductores gestionan su disponibilidad, reciben
            solicitudes y actualizan el estado de cada servicio.
          </p>
        </article>

        <article class="purpose-card">
          <span class="purpose-number">03</span>

          <span class="purpose-icon purpose-green">
            <q-icon name="shield" />
          </span>

          <h3>Mejorar la seguridad</h3>

          <p>
            El sistema incorpora seguimiento, comunicación durante el
            viaje y herramientas de alerta para situaciones de riesgo.
          </p>
        </article>

        <article class="purpose-card">
          <span class="purpose-number">04</span>

          <span class="purpose-icon purpose-purple">
            <q-icon name="monitoring" />
          </span>

          <h3>Fortalecer la gestión</h3>

          <p>
            La administración dispone de información operativa para
            monitorear solicitudes, servicios e incidencias.
          </p>
        </article>
      </div>
    </section>

    <!-- =========================================================
         FUNCIONES
    ========================================================== -->
    <section
      id="funciones"
      class="section dark-section"
    >
      <div class="dark-decoration decoration-one" />
      <div class="dark-decoration decoration-two" />

      <div class="dark-inner">
        <div class="section-heading dark-heading">
          <span>FUNCIONES PRINCIPALES</span>

          <h2>
            Un sistema que integra la
            <em>operación del servicio</em>
          </h2>

          <p>
            Los módulos trabajan de forma coordinada para cubrir desde
            el registro y gestión hasta la solicitud y finalización del
            viaje.
          </p>
        </div>

        <div class="feature-grid">
          <article class="feature-card">
            <span class="feature-number">01</span>
            <span class="feature-icon feature-green">
              <q-icon name="badge" />
            </span>
            <h3>Registro y control</h3>
            <p>
              Personas, pasajeros, mototaxistas, sindicatos y
              administración de información base.
            </p>
          </article>

          <article class="feature-card">
            <span class="feature-number">02</span>
            <span class="feature-icon feature-orange">
              <q-icon name="map" />
            </span>
            <h3>Solicitud de viajes</h3>
            <p>
              Selección de origen y destino, cálculo de tarifa y
              búsqueda de conductor disponible.
            </p>
          </article>

          <article class="feature-card">
            <span class="feature-number">03</span>
            <span class="feature-icon feature-blue">
              <q-icon name="gps_fixed" />
            </span>
            <h3>Seguimiento operativo</h3>
            <p>
              Estados del viaje, ubicación del conductor y monitoreo
              del servicio desde la plataforma.
            </p>
          </article>

          <article class="feature-card">
            <span class="feature-number">04</span>
            <span class="feature-icon feature-purple">
              <q-icon name="chat" />
            </span>
            <h3>Comunicación</h3>
            <p>
              Chat entre pasajero y conductor vinculado directamente
              con la solicitud activa.
            </p>
          </article>

          <article class="feature-card">
            <span class="feature-number">05</span>
            <span class="feature-icon feature-red">
              <q-icon name="sos" />
            </span>
            <h3>Seguridad e incidencias</h3>
            <p>
              Alertas SOS y centro de incidencias para el seguimiento
              administrativo de eventos durante los viajes.
            </p>
          </article>
        </div>
      </div>
    </section>

    <!-- =========================================================
         CÓMO FUNCIONA
    ========================================================== -->
    <section
      id="flujo"
      class="section workflow-section"
    >
      <div class="section-heading centered-heading compact-heading">
        <span>CÓMO FUNCIONA</span>

        <h2>
          Del pedido al destino en
          <em>cuatro etapas</em>
        </h2>
      </div>

      <div class="workflow-grid">
        <article class="workflow-card">
          <span class="workflow-step">1</span>

          <q-icon name="pin_drop" />

          <h3>Solicitar</h3>

          <p>
            El pasajero define su origen y destino y confirma la
            solicitud.
          </p>
        </article>

        <div class="workflow-arrow">
          <q-icon name="arrow_forward" />
        </div>

        <article class="workflow-card">
          <span class="workflow-step">2</span>

          <q-icon name="two_wheeler" />

          <h3>Asignar</h3>

          <p>
            MOTRIX busca un conductor disponible para atender el
            servicio.
          </p>
        </article>

        <div class="workflow-arrow">
          <q-icon name="arrow_forward" />
        </div>

        <article class="workflow-card">
          <span class="workflow-step">3</span>

          <q-icon name="route" />

          <h3>Realizar</h3>

          <p>
            Pasajero y conductor disponen de seguimiento, estados y
            comunicación.
          </p>
        </article>

        <div class="workflow-arrow">
          <q-icon name="arrow_forward" />
        </div>

        <article class="workflow-card">
          <span class="workflow-step">4</span>

          <q-icon name="task_alt" />

          <h3>Finalizar</h3>

          <p>
            El servicio queda registrado para historial, pago y
            calificación.
          </p>
        </article>
      </div>
    </section>

    <!-- =========================================================
         SEGURIDAD
    ========================================================== -->
    <section
      id="seguridad"
      class="section security-section"
    >
      <div class="security-grid">
        <div class="security-copy">
          <div class="section-heading">
            <span>SEGURIDAD</span>

            <h2>
              Herramientas para un servicio
              <em>más confiable</em>
            </h2>

            <p>
              MOTRIX incorpora funciones pensadas para mantener
              información del viaje y facilitar la atención ante
              incidencias durante el servicio.
            </p>
          </div>

          <div class="security-list">
            <div>
              <q-icon name="verified_user" />
              <span>
                <strong>Control de acceso</strong>
                <small>Perfiles separados para cada tipo de usuario.</small>
              </span>
            </div>

            <div>
              <q-icon name="my_location" />
              <span>
                <strong>Ubicación operativa</strong>
                <small>Datos de posición asociados a la operación del conductor.</small>
              </span>
            </div>

            <div>
              <q-icon name="forum" />
              <span>
                <strong>Chat del viaje</strong>
                <small>Comunicación vinculada directamente al servicio.</small>
              </span>
            </div>

            <div>
              <q-icon name="sos" />
              <span>
                <strong>Alertas SOS</strong>
                <small>Registro y seguimiento de incidencias desde administración.</small>
              </span>
            </div>
          </div>
        </div>

        <div class="security-panel">
          <div class="security-orbit orbit-one" />
          <div class="security-orbit orbit-two" />

          <div class="shield-main">
            <q-icon name="shield" />
          </div>

          <div class="security-bubble bubble-one">
            <q-icon name="person_pin_circle" />
            Pasajero
          </div>

          <div class="security-bubble bubble-two">
            <q-icon name="two_wheeler" />
            Conductor
          </div>

          <div class="security-bubble bubble-three">
            <q-icon name="admin_panel_settings" />
            Administración
          </div>

          <div class="security-bubble bubble-four">
            <q-icon name="sos" />
            SOS
          </div>
        </div>
      </div>
    </section>

    <!-- =========================================================
         CTA FINAL
    ========================================================== -->
    <section class="final-cta">
      <div class="cta-pattern" />

      <div class="cta-icon">
        <q-icon name="rocket_launch" />
      </div>

      <h2>
        ¿Listo para conocer
        <span>MOTRIX?</span>
      </h2>

      <p>
        Ingresa a la plataforma para acceder a los módulos de gestión
        y solicitud del servicio de mototaxis.
      </p>

      <div class="cta-actions">
        <q-btn
          color="white"
          text-color="green-9"
          icon="login"
          icon-right="arrow_forward"
          label="Iniciar sesión"
          unelevated
          no-caps
          class="cta-login"
          @click="irLogin"
        />

        <q-btn
          outline
          color="white"
          icon="keyboard_arrow_up"
          label="Volver arriba"
          no-caps
          @click="irInicio"
        />
      </div>
    </section>

    <!-- =========================================================
         FOOTER
    ========================================================== -->
    <footer class="public-footer">
      <div>
        <strong>MOTRIX</strong>
        <span>
          Sistema de Gestión y Solicitud de Mototaxis
        </span>
      </div>

      <div class="footer-center">
        Proyecto tecnológico · Trinidad 2026
      </div>

      <div>
        <q-icon name="location_on" />
        Trinidad · Beni
      </div>
    </footer>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'

const router = useRouter()

function irLogin() {
  router.push('/login')
}

function irInicio() {
  window.scrollTo({
    top: 0,
    behavior: 'smooth'
  })
}

function irA(id) {
  document
    .getElementById(id)
    ?.scrollIntoView({
      behavior: 'smooth',
      block: 'start'
    })
}
</script>

<style scoped>
:global(html) {
  scroll-behavior: smooth;
}

.landing-page {
  --navy: #0a2e0a;
  --navy-dark: #061f08;
  --blue: #2e7d32;
  --green: #2e7d32;
  --green-dark: #1b5e20;
  --orange: #c62828;
  --cream: #f1f8e9;
  --text: #17351b;
  --muted: #6f7f70;

  min-height: 100vh;
  overflow-x: hidden;
  color: var(--text);
  background: #f4f8ef;
  font-family:
    Inter,
    "Segoe UI",
    Arial,
    sans-serif;
}

/* HEADER */
.public-header {
  position: sticky;
  top: 0;
  z-index: 50;
  height: 76px;
  background:
    rgba(255, 255, 255, 0.94);
  border-bottom:
    1px solid rgba(23, 52, 91, 0.08);
  box-shadow:
    0 8px 30px rgba(18, 49, 86, 0.08);
  backdrop-filter: blur(14px);
}

.header-inner {
  width: min(1240px, calc(100% - 40px));
  height: 100%;
  margin: 0 auto;
  display: flex;
  align-items: center;
  gap: 28px;
}

.brand {
  min-width: 0;
  padding: 0;
  display: inline-flex;
  align-items: center;
  gap: 12px;
  color: inherit;
  background: transparent;
  border: 0;
  cursor: pointer;
}

.brand-mark {
  width: 44px;
  height: 44px;
  display: grid;
  place-items: center;
  color: white;
  font-size: 25px;
  background:
    linear-gradient(
      145deg,
      var(--green),
      var(--green-dark)
    );
  border-radius: 14px;
  box-shadow:
    0 8px 18px rgba(35, 130, 56, 0.22);
}

.brand-text {
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.brand-text strong {
  color: var(--navy);
  font-size: 22px;
  line-height: 1;
  letter-spacing: 0.02em;
}

.brand-text small {
  margin-top: 5px;
  color: var(--muted);
  font-size: 11px;
  font-weight: 600;
}

.desktop-nav {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 4px;
}

.desktop-nav button {
  padding: 10px 12px;
  color: #526b8a;
  background: transparent;
  border: 0;
  border-radius: 9px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition:
    color 0.15s ease,
    background 0.15s ease;
}

.desktop-nav button:hover {
  color: var(--green);
  background: #edf7ed;
}

.header-login {
  min-height: 42px;
  padding-right: 17px;
  padding-left: 17px;
  border-radius: 11px;
  font-weight: 700;
}

/* HERO */
.hero-section {
  position: relative;
  min-height: calc(100vh - 76px);
  padding: 58px 0 86px;
  display: flex;
  align-items: center;
  background:
    linear-gradient(
      120deg,
      #f1f8e9 0%,
      #f8fbf6 55%,
      #e8f3e5 100%
    );
}

.hero-grid {
  position: relative;
  z-index: 2;
  width: min(1240px, calc(100% - 52px));
  margin: 0 auto;
  display: grid;
  grid-template-columns:
    minmax(420px, 0.88fr)
    minmax(520px, 1.12fr);
  align-items: center;
  gap: 68px;
}

.hero-glow {
  position: absolute;
  border-radius: 50%;
  filter: blur(2px);
  pointer-events: none;
}

.hero-glow-one {
  width: 420px;
  height: 420px;
  top: -160px;
  right: -160px;
  background:
    rgba(198, 40, 40, 0.08);
}

.hero-glow-two {
  width: 300px;
  height: 300px;
  left: -140px;
  bottom: -80px;
  background:
    rgba(35, 130, 56, 0.12);
}

/* HERO VISUAL */
.hero-visual-wrap {
  min-width: 0;
}

.hero-visual {
  position: relative;
  min-height: 570px;
  padding: 24px;
  overflow: hidden;
  background:
    linear-gradient(
      145deg,
      #ffffff,
      #edf6eb
    );
  border:
    1px solid rgba(38, 94, 148, 0.12);
  border-radius: 34px;
  box-shadow:
    0 28px 70px rgba(20, 66, 112, 0.15);
}

.visual-badge {
  position: absolute;
  z-index: 7;
  top: 28px;
  left: 28px;
  padding: 9px 14px;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  color: white;
  background: var(--blue);
  border-radius: 999px;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.06em;
  box-shadow:
    0 9px 22px rgba(46, 125, 50, 0.22);
}

.visual-map {
  position: absolute;
  inset: 92px 28px 72px;
  overflow: hidden;
  background:
    linear-gradient(
      rgba(255, 255, 255, 0.78),
      rgba(240, 248, 242, 0.86)
    ),
    repeating-linear-gradient(
      90deg,
      transparent 0 41px,
      rgba(16, 47, 95, 0.04) 42px 43px
    ),
    repeating-linear-gradient(
      0deg,
      transparent 0 41px,
      rgba(16, 47, 95, 0.04) 42px 43px
    );
  border:
    1px solid rgba(16, 47, 95, 0.08);
  border-radius: 26px;
}

.road {
  position: absolute;
  height: 16px;
  background: white;
  border:
    1px solid rgba(103, 130, 157, 0.12);
  border-radius: 20px;
  box-shadow:
    0 4px 9px rgba(57, 92, 126, 0.05);
}

.road-a {
  width: 130%;
  top: 35%;
  left: -15%;
  transform: rotate(-18deg);
}

.road-b {
  width: 120%;
  top: 62%;
  left: -10%;
  transform: rotate(19deg);
}

.road-c {
  width: 110%;
  top: 47%;
  left: -8%;
  transform: rotate(77deg);
}

.moto-main {
  position: absolute;
  z-index: 6;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
}

.moto-ring {
  width: 152px;
  height: 152px;
  display: grid;
  place-items: center;
  color: white;
  font-size: 78px;
  background:
    linear-gradient(
      145deg,
      var(--green),
      #31a548
    );
  border:
    12px solid rgba(255, 255, 255, 0.92);
  border-radius: 50%;
  box-shadow:
    0 22px 45px rgba(35, 130, 56, 0.26);
}

.moto-caption {
  margin-top: 9px;
  display: flex;
  flex-direction: column;
}

.moto-caption strong {
  color: var(--navy);
  font-size: 18px;
}

.moto-caption span {
  color: var(--muted);
  font-size: 11px;
}

.map-pin {
  position: absolute;
  z-index: 5;
  width: 42px;
  height: 42px;
  display: grid;
  place-items: center;
  color: white;
  border:
    5px solid rgba(255, 255, 255, 0.9);
  border-radius: 50%;
  box-shadow:
    0 8px 18px rgba(20, 56, 94, 0.2);
}

.pin-origin {
  top: 18%;
  left: 18%;
  background: var(--green);
}

.pin-destination {
  right: 14%;
  bottom: 18%;
  background: #c62828;
}

.route-line {
  position: absolute;
  z-index: 3;
  height: 5px;
  transform-origin: left center;
  background:
    repeating-linear-gradient(
      90deg,
      var(--blue) 0 8px,
      transparent 8px 15px
    );
  border-radius: 999px;
}

.route-one {
  width: 38%;
  top: 34%;
  left: 24%;
  transform: rotate(24deg);
}

.route-two {
  width: 37%;
  right: 17%;
  bottom: 31%;
  transform: rotate(24deg);
}

.mini-card {
  position: absolute;
  z-index: 8;
  min-width: 190px;
  padding: 11px 13px;
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(255, 255, 255, 0.96);
  border:
    1px solid rgba(20, 68, 111, 0.1);
  border-radius: 15px;
  box-shadow:
    0 12px 30px rgba(31, 71, 109, 0.13);
}

.mini-card > div {
  display: flex;
  flex-direction: column;
}

.mini-card span {
  color: var(--muted);
  font-size: 10px;
}

.mini-card strong {
  color: var(--navy);
  font-size: 12px;
}

.passenger-card {
  top: 18px;
  right: 16px;
}

.driver-card {
  bottom: 18px;
  left: 16px;
}

.visual-bottom {
  position: absolute;
  right: 28px;
  bottom: 23px;
  left: 28px;
  display: flex;
  justify-content: space-between;
  gap: 12px;
}

.visual-bottom div {
  flex: 1;
  padding: 9px 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  color: #667866;
  background: #f7faf5;
  border:
    1px solid rgba(16, 47, 95, 0.08);
  border-radius: 11px;
  font-size: 11px;
  font-weight: 700;
}

/* HERO COPY */
.hero-copy {
  min-width: 0;
}

.eyebrow {
  margin-bottom: 22px;
  display: flex;
  align-items: center;
  gap: 11px;
  color: var(--blue);
  font-size: 12px;
  font-weight: 900;
  letter-spacing: 0.12em;
}

.eyebrow-line {
  width: 44px;
  height: 4px;
  display: inline-block;
  background:
    linear-gradient(
      90deg,
      var(--green),
      var(--orange)
    );
  border-radius: 999px;
}

.hero-copy h1 {
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.hero-brand {
  color: var(--navy);
  font-size: clamp(54px, 6vw, 82px);
  font-weight: 950;
  line-height: 0.92;
  letter-spacing: -0.045em;
}

.hero-title {
  max-width: 720px;
  color: #204d27;
  font-size: clamp(24px, 2.5vw, 37px);
  font-weight: 850;
  line-height: 1.14;
  letter-spacing: -0.025em;
}

.hero-description {
  max-width: 720px;
  margin: 24px 0 0;
  color: #6d806d;
  font-size: 17px;
  line-height: 1.75;
}

.hero-actions {
  margin-top: 27px;
  display: flex;
  flex-wrap: wrap;
  gap: 13px;
}

.primary-action,
.secondary-action {
  min-height: 54px;
  padding-right: 22px;
  padding-left: 22px;
  border-radius: 14px;
  font-size: 15px;
  font-weight: 800;
}

.hero-meta {
  margin-top: 30px;
  display: flex;
  flex-wrap: wrap;
  gap: 18px;
}

.meta-item {
  min-width: 235px;
  padding: 11px 14px;
  display: flex;
  align-items: center;
  gap: 11px;
  background: rgba(255, 255, 255, 0.74);
  border:
    1px solid rgba(21, 71, 112, 0.09);
  border-radius: 14px;
}

.meta-icon {
  width: 44px;
  height: 44px;
  display: grid;
  place-items: center;
  border-radius: 13px;
  font-size: 20px;
}

.meta-green {
  color: var(--green);
  background: #e7f6e8;
}

.meta-blue {
  color: var(--green);
  background: #e6f3e4;
}

.meta-item > div {
  display: flex;
  flex-direction: column;
}

.meta-item small {
  color: #819081;
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.05em;
}

.meta-item strong {
  margin-top: 2px;
  color: var(--navy);
  font-size: 13px;
}

.discover-button {
  position: absolute;
  z-index: 5;
  bottom: 23px;
  left: 50%;
  transform: translateX(-50%);
  padding: 6px 18px;
  display: flex;
  flex-direction: column;
  align-items: center;
  color: #7a897a;
  background: transparent;
  border: 0;
  font-size: 10px;
  font-weight: 700;
  cursor: pointer;
}

.discover-button .q-icon {
  margin-top: 2px;
  font-size: 25px;
}

/* PILLARS */
.pillar-strip {
  position: relative;
  z-index: 4;
  margin: -1px 0 0;
  padding: 30px 0;
  background:
    linear-gradient(
      180deg,
      #e8f3e5,
      #f1f8e9
    );
}

.pillar-grid {
  width: min(1180px, calc(100% - 48px));
  margin: 0 auto;
  padding: 20px 28px;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  background: white;
  border:
    1px solid rgba(19, 69, 111, 0.09);
  border-radius: 22px;
  box-shadow:
    0 18px 45px rgba(28, 67, 105, 0.09);
}

.pillar-item {
  min-height: 76px;
  padding: 0 22px;
  display: flex;
  align-items: center;
  gap: 12px;
  border-right:
    1px solid #d8e6d4;
}

.pillar-item:last-child {
  border-right: 0;
}

.pillar-icon,
.purpose-icon,
.feature-icon {
  flex: 0 0 auto;
  display: grid;
  place-items: center;
  border-radius: 14px;
}

.pillar-icon {
  width: 48px;
  height: 48px;
  font-size: 22px;
}

.pillar-blue {
  color: var(--green);
  background: #e6f3e4;
}

.pillar-orange {
  color: #2e7d32;
  background: #edf6eb;
}

.pillar-green {
  color: var(--green);
  background: #e6f3e4;
}

.pillar-purple {
  color: #2e7d32;
  background: #e6f3e4;
}

.pillar-item > div {
  display: flex;
  flex-direction: column;
}

.pillar-item strong {
  color: var(--navy);
  font-size: 13px;
}

.pillar-item span:not(.pillar-icon) {
  margin-top: 3px;
  color: #768676;
  font-size: 10px;
}

/* COMMON SECTION */
.section {
  position: relative;
  padding: 100px 0;
}

.section-heading {
  max-width: 780px;
}

.section-heading > span {
  color: var(--blue);
  font-size: 11px;
  font-weight: 900;
  letter-spacing: 0.14em;
}

.section-heading h2 {
  margin: 16px 0 18px;
  color: var(--navy);
  font-size: clamp(38px, 4.3vw, 61px);
  font-weight: 950;
  line-height: 1.02;
  letter-spacing: -0.04em;
}

.section-heading h2 em {
  color: var(--blue);
  font-style: normal;
}

.section-heading p {
  margin: 0;
  color: #6f7f70;
  font-size: 16px;
  line-height: 1.75;
}

.centered-heading {
  margin-right: auto;
  margin-left: auto;
  text-align: center;
}

.compact-heading {
  max-width: 700px;
}

/* PURPOSE */
.light-section {
  background: #f4f8ef;
}

.purpose-grid {
  width: min(1240px, calc(100% - 52px));
  margin: 45px auto 0;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 18px;
}

.purpose-card {
  position: relative;
  min-height: 295px;
  padding: 29px 24px 26px;
  overflow: hidden;
  background: rgba(255, 255, 255, 0.94);
  border:
    1px solid rgba(25, 72, 113, 0.11);
  border-radius: 20px;
  box-shadow:
    0 13px 30px rgba(27, 67, 104, 0.06);
}

.purpose-number {
  position: absolute;
  top: 14px;
  right: 18px;
  color: rgba(16, 47, 95, 0.08);
  font-size: 46px;
  font-weight: 950;
}

.purpose-icon {
  width: 57px;
  height: 57px;
  font-size: 27px;
}

.purpose-blue {
  color: var(--blue);
  background: #e7f3e5;
}

.purpose-orange {
  color: #2e7d32;
  background: #edf6eb;
}

.purpose-green {
  color: var(--green);
  background: #e4f2e1;
}

.purpose-purple {
  color: #2e7d32;
  background: #e2f0df;
}

.purpose-card h3 {
  margin: 24px 0 13px;
  color: var(--navy);
  font-size: 19px;
  font-weight: 850;
}

.purpose-card p {
  margin: 0;
  color: #738173;
  font-size: 13px;
  line-height: 1.7;
}

/* DARK FEATURES */
.dark-section {
  overflow: hidden;
  background:
    linear-gradient(
      135deg,
      #0a2e0a,
      #1b5e20 100%
    );
}

.dark-inner {
  position: relative;
  z-index: 3;
  width: min(1240px, calc(100% - 52px));
  margin: 0 auto;
}

.dark-heading > span {
  color: #a5d6a7;
}

.dark-heading h2 {
  color: white;
}

.dark-heading h2 em {
  color: #a5d6a7;
}

.dark-heading p {
  color: #cde1ca;
}

.dark-decoration {
  position: absolute;
  border:
    1px solid rgba(255, 255, 255, 0.08);
  border-radius: 50%;
}

.decoration-one {
  width: 420px;
  height: 420px;
  top: -230px;
  right: -110px;
}

.decoration-two {
  width: 310px;
  height: 310px;
  top: -175px;
  right: -55px;
}

.feature-grid {
  margin-top: 48px;
  display: grid;
  grid-template-columns:
    repeat(5, minmax(0, 1fr));
  gap: 14px;
}

.feature-card {
  position: relative;
  min-height: 310px;
  padding: 27px 20px 24px;
  overflow: hidden;
  background:
    rgba(255, 255, 255, 0.09);
  border:
    1px solid rgba(255, 255, 255, 0.14);
  border-radius: 19px;
  backdrop-filter: blur(8px);
}

.feature-number {
  display: block;
  color: rgba(255, 255, 255, 0.23);
  font-size: 35px;
  font-weight: 950;
}

.feature-icon {
  width: 56px;
  height: 56px;
  margin-top: 17px;
  font-size: 26px;
}

.feature-green {
  color: #2e7d32;
  background: #dff0dc;
}

.feature-orange {
  color: #2e7d32;
  background: #e7f3e5;
}

.feature-blue {
  color: #2e7d32;
  background: #e0f0dd;
}

.feature-purple {
  color: #2e7d32;
  background: #dcebd9;
}

.feature-red {
  color: #bf3434;
  background: #ffd7d7;
}

.feature-card h3 {
  margin: 22px 0 11px;
  color: white;
  font-size: 16px;
  font-weight: 850;
}

.feature-card p {
  margin: 0;
  color: #d2e3cf;
  font-size: 12px;
  line-height: 1.65;
}

/* WORKFLOW */
.workflow-section {
  background: #f7faf5;
}

.workflow-grid {
  width: min(1180px, calc(100% - 52px));
  margin: 48px auto 0;
  display: grid;
  grid-template-columns:
    1fr auto 1fr auto 1fr auto 1fr;
  align-items: center;
  gap: 13px;
}

.workflow-card {
  position: relative;
  min-height: 232px;
  padding: 29px 22px;
  text-align: center;
  background: white;
  border:
    1px solid #dbe7d8;
  border-radius: 20px;
  box-shadow:
    0 12px 30px rgba(25, 68, 105, 0.06);
}

.workflow-card > .q-icon {
  margin-top: 15px;
  color: var(--green);
  font-size: 43px;
}

.workflow-step {
  position: absolute;
  top: 13px;
  left: 14px;
  width: 31px;
  height: 31px;
  display: grid;
  place-items: center;
  color: white;
  background: var(--navy);
  border-radius: 50%;
  font-size: 12px;
  font-weight: 850;
}

.workflow-card h3 {
  margin: 15px 0 9px;
  color: var(--navy);
  font-size: 17px;
  font-weight: 850;
}

.workflow-card p {
  margin: 0;
  color: #758575;
  font-size: 12px;
  line-height: 1.65;
}

.workflow-arrow {
  color: #9aaa98;
  font-size: 27px;
}

/* SECURITY */
.security-section {
  background:
    linear-gradient(
      145deg,
      #f1f8e9,
      #f7faf5
    );
}

.security-grid {
  width: min(1180px, calc(100% - 52px));
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr 0.9fr;
  align-items: center;
  gap: 85px;
}

.security-list {
  margin-top: 30px;
  display: grid;
  grid-template-columns:
    repeat(2, minmax(0, 1fr));
  gap: 13px;
}

.security-list > div {
  min-height: 88px;
  padding: 15px;
  display: flex;
  gap: 12px;
  background: white;
  border:
    1px solid #dce8db;
  border-radius: 14px;
}

.security-list > div > .q-icon {
  flex: 0 0 auto;
  color: var(--green);
  font-size: 26px;
}

.security-list span {
  display: flex;
  flex-direction: column;
}

.security-list strong {
  color: var(--navy);
  font-size: 13px;
}

.security-list small {
  margin-top: 4px;
  color: #758575;
  font-size: 11px;
  line-height: 1.45;
}

.security-panel {
  position: relative;
  min-height: 430px;
  overflow: hidden;
  background:
    linear-gradient(
      145deg,
      var(--navy),
      #1b5e20
    );
  border-radius: 32px;
  box-shadow:
    0 28px 60px rgba(11, 54, 97, 0.2);
}

.security-orbit {
  position: absolute;
  top: 50%;
  left: 50%;
  border:
    1px solid rgba(255, 255, 255, 0.17);
  border-radius: 50%;
  transform: translate(-50%, -50%);
}

.orbit-one {
  width: 270px;
  height: 270px;
}

.orbit-two {
  width: 390px;
  height: 390px;
}

.shield-main {
  position: absolute;
  z-index: 4;
  top: 50%;
  left: 50%;
  width: 120px;
  height: 120px;
  display: grid;
  place-items: center;
  color: white;
  background:
    linear-gradient(
      145deg,
      #2fb353,
      #1a7b34
    );
  border:
    10px solid rgba(255, 255, 255, 0.13);
  border-radius: 31px;
  transform: translate(-50%, -50%);
  box-shadow:
    0 22px 38px rgba(0, 0, 0, 0.18);
}

.shield-main .q-icon {
  font-size: 64px;
}

.security-bubble {
  position: absolute;
  z-index: 5;
  padding: 10px 13px;
  display: flex;
  align-items: center;
  gap: 7px;
  color: var(--navy);
  background: white;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 800;
  box-shadow:
    0 10px 25px rgba(0, 0, 0, 0.16);
}

.security-bubble .q-icon {
  color: var(--green);
  font-size: 19px;
}

.bubble-one {
  top: 50px;
  left: 46px;
}

.bubble-two {
  top: 70px;
  right: 35px;
}

.bubble-three {
  right: 30px;
  bottom: 62px;
}

.bubble-four {
  bottom: 55px;
  left: 50px;
}

/* CTA */
.final-cta {
  position: relative;
  min-height: 465px;
  padding: 78px 20px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  color: white;
  background:
    linear-gradient(
      135deg,
      #0a2e0a,
      #2e7d32
    );
}

.cta-pattern {
  position: absolute;
  inset: 0;
  opacity: 0.18;
  background-image:
    radial-gradient(
      circle,
      #ffffff 1.8px,
      transparent 1.8px
    );
  background-size: 42px 42px;
}

.cta-icon {
  position: relative;
  z-index: 2;
  width: 78px;
  height: 78px;
  display: grid;
  place-items: center;
  color: white;
  background: rgba(255, 255, 255, 0.14);
  border:
    1px solid rgba(255, 255, 255, 0.2);
  border-radius: 24px;
  font-size: 44px;
}

.final-cta h2 {
  position: relative;
  z-index: 2;
  max-width: 900px;
  margin: 24px 0 12px;
  font-size: clamp(42px, 5vw, 68px);
  font-weight: 950;
  line-height: 1.04;
  letter-spacing: -0.04em;
}

.final-cta h2 span {
  color: #c8e6c9;
}

.final-cta p {
  position: relative;
  z-index: 2;
  max-width: 720px;
  margin: 0;
  color: #d9ead6;
  font-size: 16px;
  line-height: 1.65;
}

.cta-actions {
  position: relative;
  z-index: 2;
  margin-top: 30px;
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 13px;
}

.cta-login {
  min-height: 54px;
  padding: 0 25px;
  border-radius: 14px;
  font-weight: 850;
}

/* FOOTER */
.public-footer {
  min-height: 120px;
  padding: 28px max(40px, calc((100% - 1200px) / 2));
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  gap: 25px;
  color: #c8d8e9;
  background: var(--navy-dark);
  font-size: 11px;
}

.public-footer > div:first-child {
  display: flex;
  flex-direction: column;
}

.public-footer strong {
  color: white;
  font-size: 14px;
}

.public-footer span {
  margin-top: 3px;
}

.footer-center {
  text-align: center;
}

.public-footer > div:last-child {
  justify-self: end;
  display: flex;
  align-items: center;
  gap: 6px;
}

/* RESPONSIVE */
@media (max-width: 1100px) {
  .desktop-nav {
    display: none;
  }

  .hero-grid {
    grid-template-columns: 0.9fr 1.1fr;
    gap: 38px;
  }

  .feature-grid {
    grid-template-columns:
      repeat(3, 1fr);
  }

  .feature-card:nth-child(4),
  .feature-card:nth-child(5) {
    min-height: 270px;
  }
}

@media (max-width: 900px) {
  .hero-section {
    padding-top: 38px;
  }

  .hero-grid {
    grid-template-columns: 1fr;
  }

  .hero-copy {
    order: 1;
    text-align: center;
  }

  .hero-visual-wrap {
    order: 2;
    width: min(580px, 100%);
    margin: 0 auto;
  }

  .hero-actions,
  .hero-meta {
    justify-content: center;
  }

  .eyebrow {
    justify-content: center;
  }

  .hero-description,
  .hero-title {
    margin-right: auto;
    margin-left: auto;
  }

  .pillar-grid,
  .purpose-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .pillar-item:nth-child(2) {
    border-right: 0;
  }

  .pillar-item:nth-child(-n + 2) {
    padding-bottom: 14px;
    border-bottom: 1px solid #d8e6d4;
  }

  .pillar-item:nth-child(n + 3) {
    padding-top: 14px;
  }

  .workflow-grid {
    grid-template-columns: 1fr 1fr;
  }

  .workflow-arrow {
    display: none;
  }

  .security-grid {
    grid-template-columns: 1fr;
    gap: 45px;
  }

  .security-panel {
    min-height: 410px;
  }
}

@media (max-width: 680px) {
  .public-header {
    height: 68px;
  }

  .header-inner {
    width: calc(100% - 22px);
    gap: 10px;
  }

  .brand-mark {
    width: 39px;
    height: 39px;
    font-size: 22px;
  }

  .brand-text strong {
    font-size: 19px;
  }

  .brand-text small {
    display: none;
  }

  .header-login {
    margin-left: auto;
    min-height: 39px;
  }

  .hero-section {
    min-height: auto;
    padding: 44px 0 86px;
  }

  .hero-grid,
  .purpose-grid,
  .dark-inner,
  .workflow-grid,
  .security-grid {
    width: calc(100% - 28px);
  }

  .hero-brand {
    font-size: 49px;
  }

  .hero-title {
    font-size: 25px;
  }

  .hero-description {
    font-size: 15px;
  }

  .hero-actions > * {
    width: 100%;
  }

  .hero-meta {
    flex-direction: column;
  }

  .meta-item {
    min-width: 0;
    width: 100%;
  }

  .hero-visual {
    min-height: 475px;
    padding: 16px;
    border-radius: 24px;
  }

  .visual-map {
    inset: 82px 16px 66px;
  }

  .moto-ring {
    width: 116px;
    height: 116px;
    font-size: 58px;
  }

  .mini-card {
    min-width: 157px;
    padding: 8px 9px;
  }

  .mini-card strong {
    font-size: 10px;
  }

  .passenger-card {
    top: 12px;
    right: 8px;
  }

  .driver-card {
    bottom: 12px;
    left: 8px;
  }

  .pillar-grid {
    width: calc(100% - 28px);
    padding: 15px;
    grid-template-columns: 1fr;
  }

  .pillar-item {
    padding: 13px 7px;
    border-right: 0;
    border-bottom: 1px solid #d8e6d4;
  }

  .pillar-item:last-child {
    border-bottom: 0;
  }

  .section {
    padding: 74px 0;
  }

  .section-heading {
    padding-right: 14px;
    padding-left: 14px;
  }

  .section-heading h2 {
    font-size: 39px;
  }

  .purpose-grid,
  .feature-grid,
  .workflow-grid {
    grid-template-columns: 1fr;
  }

  .purpose-card,
  .feature-card {
    min-height: auto;
  }

  .security-list {
    grid-template-columns: 1fr;
  }

  .security-panel {
    min-height: 350px;
  }

  .orbit-one {
    width: 210px;
    height: 210px;
  }

  .orbit-two {
    width: 310px;
    height: 310px;
  }

  .shield-main {
    width: 96px;
    height: 96px;
  }

  .shield-main .q-icon {
    font-size: 49px;
  }

  .security-bubble {
    padding: 8px 9px;
    font-size: 9px;
  }

  .bubble-one {
    top: 35px;
    left: 20px;
  }

  .bubble-two {
    top: 45px;
    right: 17px;
  }

  .bubble-three {
    right: 17px;
    bottom: 45px;
  }

  .bubble-four {
    bottom: 40px;
    left: 20px;
  }

  .public-footer {
    padding: 30px 18px;
    grid-template-columns: 1fr;
    text-align: center;
  }

  .public-footer > div:last-child {
    justify-self: center;
  }
}
</style>
