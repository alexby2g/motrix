<template>
  <div class="landing-page">
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

          <span class="brand-copy">
            <strong>MOTRIX</strong>
            <small>Movilidad · Gestión · Seguridad</small>
          </span>
        </button>

        <nav
          class="desktop-nav"
          aria-label="Navegación principal"
        >
          <button
            type="button"
            @click="irA('como-funciona')"
          >
            Cómo funciona
          </button>

          <button
            type="button"
            @click="irA('beneficios')"
          >
            Beneficios
          </button>

          <button
            type="button"
            @click="irA('seguridad')"
          >
            Seguridad
          </button>
        </nav>

        <q-btn
          color="green-8"
          icon="login"
          label="Ingresar"
          unelevated
          no-caps
          class="header-login"
          @click="irLogin"
        />
      </div>
    </header>

    <main>
      <section class="hero-section">
        <div class="hero-glow hero-glow-one" />
        <div class="hero-glow hero-glow-two" />

        <div class="hero-grid">
          <div class="hero-copy">
            <div class="hero-kicker">
              <span class="hero-kicker-dot" />
              MOVILIDAD URBANA · TRINIDAD
            </div>

            <h1>
              <span class="hero-brand">MOTRIX</span>
              <span class="hero-title">
                Mototaxis conectados en una sola plataforma
              </span>
            </h1>

            <p class="hero-description">
              Sistema de gestión y solicitud de mototaxis que conecta
              pasajeros, conductores y administración para coordinar
              viajes, seguimiento, seguridad y operación en tiempo real.
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
                icon="route"
                label="Ver cómo funciona"
                no-caps
                class="secondary-action"
                @click="irA('como-funciona')"
              />
            </div>

            <div class="hero-trust">
              <div class="trust-item">
                <q-icon name="verified_user" />
                <span>
                  <strong>Acceso por perfiles</strong>
                  <small>Pasajero · Mototaxista · Administración</small>
                </span>
              </div>

              <div class="trust-item">
                <q-icon name="sensors" />
                <span>
                  <strong>Operación en tiempo real</strong>
                  <small>Solicitudes, estados y seguimiento</small>
                </span>
              </div>
            </div>
          </div>

          <div class="hero-visual-wrap">
            <div class="mobility-card">
              <div class="visual-topbar">
                <div class="city-badge">
                  <q-icon name="location_on" />
                  TRINIDAD · BENI
                </div>

                <div class="live-badge">
                  <span class="live-dot" />
                  EN VIVO
                </div>
              </div>

              <div class="map-surface">
                <div class="road road-one" />
                <div class="road road-two" />
                <div class="road road-three" />

                <svg
                  class="route-svg"
                  viewBox="0 0 600 390"
                  aria-hidden="true"
                >
                  <path
                    class="route-shadow"
                    d="M94 286 C176 236 208 292 281 228 C348 170 372 112 493 92"
                  />
                  <path
                    class="route-path"
                    d="M94 286 C176 236 208 292 281 228 C348 170 372 112 493 92"
                  />
                </svg>

                <div class="route-point route-point-origin">
                  <q-icon name="my_location" />
                </div>

                <div class="route-point route-point-destination">
                  <q-icon name="place" />
                </div>

                <div class="motrix-core">
                  <div class="motrix-core-ring">
                    <q-icon name="two_wheeler" />
                  </div>

                </div>

                <div class="floating-card passenger-card">
                  <q-avatar
                    color="green-1"
                    text-color="green-9"
                    icon="person_pin_circle"
                    size="42px"
                  />

                  <div>
                    <small>PASAJERO</small>
                    <strong>Solicita un viaje</strong>
                  </div>
                </div>

                <div class="floating-card driver-card">
                  <q-avatar
                    color="green-1"
                    text-color="green-9"
                    icon="two_wheeler"
                    size="42px"
                  />

                  <div>
                    <small>MOTOTAXISTA</small>
                    <strong>Recibe la solicitud</strong>
                  </div>
                </div>

                <div class="status-card">
                  <span class="status-icon">
                    <q-icon name="shield" />
                  </span>

                  <div>
                    <small>SEGURIDAD</small>
                    <strong>Viaje acompañado</strong>
                  </div>

                  <q-icon
                    name="check_circle"
                    color="positive"
                    size="22px"
                  />
                </div>
              </div>

              <div class="visual-footer">
                <div>
                  <q-icon name="route" />
                  Solicitud y asignación
                </div>

                <div>
                  <q-icon name="my_location" />
                  Seguimiento
                </div>

                <div>
                  <q-icon name="sos" />
                  Seguridad
                </div>
              </div>
            </div>
          </div>
        </div>

        <button
          type="button"
          class="discover-button"
          @click="irA('como-funciona')"
        >
          <span>Descubre MOTRIX</span>
          <q-icon name="keyboard_arrow_down" />
        </button>
      </section>

      <section class="value-strip">
        <div class="value-grid">
          <article>
            <span class="value-icon">
              <q-icon name="person_pin_circle" />
            </span>

            <div>
              <strong>Pasajeros</strong>
              <span>Solicitud simple y seguimiento del viaje</span>
            </div>
          </article>

          <article>
            <span class="value-icon">
              <q-icon name="two_wheeler" />
            </span>

            <div>
              <strong>Mototaxistas</strong>
              <span>Solicitudes, disponibilidad y operación</span>
            </div>
          </article>

          <article>
            <span class="value-icon">
              <q-icon name="admin_panel_settings" />
            </span>

            <div>
              <strong>Administración</strong>
              <span>Control, monitoreo y trazabilidad</span>
            </div>
          </article>

          <article>
            <span class="value-icon">
              <q-icon name="verified_user" />
            </span>

            <div>
              <strong>Seguridad</strong>
              <span>Alertas, incidencias y verificación</span>
            </div>
          </article>
        </div>
      </section>

      <section
        id="como-funciona"
        class="section workflow-section"
      >
        <div class="section-heading">
          <span class="section-kicker">CÓMO FUNCIONA</span>

          <h2>
            Un flujo claro desde la solicitud
            <em>hasta el destino</em>
          </h2>

          <p>
            MOTRIX prioriza una experiencia directa. Cada usuario ve
            solamente las acciones necesarias para completar su parte
            del servicio.
          </p>
        </div>

        <div class="workflow-grid">
          <article class="workflow-card">
            <span class="workflow-step">1</span>
            <q-icon name="pin_drop" />

            <h3>Solicita</h3>

            <p>
              El pasajero selecciona origen y destino y confirma el
              viaje.
            </p>
          </article>

          <div class="workflow-arrow">
            <q-icon name="arrow_forward" />
          </div>

          <article class="workflow-card">
            <span class="workflow-step">2</span>
            <q-icon name="two_wheeler" />

            <h3>Asigna</h3>

            <p>
              MOTRIX conecta la solicitud con un mototaxista
              disponible.
            </p>
          </article>

          <div class="workflow-arrow">
            <q-icon name="arrow_forward" />
          </div>

          <article class="workflow-card">
            <span class="workflow-step">3</span>
            <q-icon name="route" />

            <h3>Viaja</h3>

            <p>
              Pasajero y conductor disponen de estados, ubicación y
              comunicación.
            </p>
          </article>

          <div class="workflow-arrow">
            <q-icon name="arrow_forward" />
          </div>

          <article class="workflow-card">
            <span class="workflow-step">4</span>
            <q-icon name="task_alt" />

            <h3>Finaliza</h3>

            <p>
              El servicio queda registrado para historial, pago y
              calificación.
            </p>
          </article>
        </div>
      </section>

      <section
        id="beneficios"
        class="section benefits-section"
      >
        <div class="benefits-grid">
          <div class="benefits-copy">
            <span class="section-kicker">UNA PLATAFORMA, TRES EXPERIENCIAS</span>

            <h2>
              Diseñado para operar con
              <em>menos pasos y más claridad</em>
            </h2>

            <p>
              La interfaz organiza la información según el rol para
              evitar recorridos innecesarios y mantener visible lo más
              importante en cada momento.
            </p>

            <div class="benefit-list">
              <div>
                <q-icon name="speed" />
                <span>
                  <strong>Acciones directas</strong>
                  <small>Menos pantallas para completar tareas frecuentes.</small>
                </span>
              </div>

              <div>
                <q-icon name="visibility" />
                <span>
                  <strong>Información visible</strong>
                  <small>Estados, totales y alertas fáciles de identificar.</small>
                </span>
              </div>

              <div>
                <q-icon name="phone_android" />
                <span>
                  <strong>Experiencia móvil</strong>
                  <small>Flujos pensados para uso diario desde el teléfono.</small>
                </span>
              </div>

              <div>
                <q-icon name="sync" />
                <span>
                  <strong>Sincronización</strong>
                  <small>Datos operativos actualizados entre usuarios.</small>
                </span>
              </div>
            </div>
          </div>

          <div class="role-stack">
            <article class="role-card role-passenger">
              <div class="role-icon">
                <q-icon name="person_pin_circle" />
              </div>

              <div>
                <small>PASAJERO</small>
                <h3>Solicita y sigue su viaje</h3>
                <p>
                  Origen, destino, conductor asignado, seguridad e
                  historial en un flujo simple.
                </p>
              </div>
            </article>

            <article class="role-card role-driver">
              <div class="role-icon">
                <q-icon name="two_wheeler" />
              </div>

              <div>
                <small>MOTOTAXISTA</small>
                <h3>Recibe y gestiona servicios</h3>
                <p>
                  Disponibilidad, solicitudes, navegación, ganancias y
                  suscripción MOTRIX.
                </p>
              </div>
            </article>

            <article class="role-card role-admin">
              <div class="role-icon">
                <q-icon name="monitoring" />
              </div>

              <div>
                <small>ADMINISTRACIÓN</small>
                <h3>Controla la operación</h3>
                <p>
                  Registros, monitoreo, incidencias, pagos, reportes y
                  trazabilidad.
                </p>
              </div>
            </article>
          </div>
        </div>
      </section>

      <section
        id="seguridad"
        class="section security-section"
      >
        <div class="security-grid">
          <div class="security-panel">
            <div class="shield-orbit orbit-one" />
            <div class="shield-orbit orbit-two" />

            <div class="shield-core">
              <q-icon name="shield" />
            </div>

            <div class="security-bubble bubble-passenger">
              <q-icon name="person_pin_circle" />
              Pasajero
            </div>

            <div class="security-bubble bubble-driver">
              <q-icon name="two_wheeler" />
              Mototaxista
            </div>

            <div class="security-bubble bubble-admin">
              <q-icon name="admin_panel_settings" />
              Administración
            </div>

            <div class="security-bubble bubble-sos">
              <q-icon name="sos" />
              SOS
            </div>
          </div>

          <div class="security-copy">
            <span class="section-kicker">SEGURIDAD Y CONTROL</span>

            <h2>
              Herramientas para una operación
              <em>más confiable</em>
            </h2>

            <p>
              MOTRIX integra funciones de control de acceso, seguimiento
              e incidencias para que cada servicio mantenga información
              útil desde la solicitud hasta su cierre.
            </p>

            <div class="security-list">
              <div>
                <q-icon name="badge" />
                <span>
                  <strong>Perfiles separados</strong>
                  <small>Permisos distintos según el rol de cada usuario.</small>
                </span>
              </div>

              <div>
                <q-icon name="my_location" />
                <span>
                  <strong>Ubicación operativa</strong>
                  <small>Seguimiento asociado al conductor y al viaje activo.</small>
                </span>
              </div>

              <div>
                <q-icon name="forum" />
                <span>
                  <strong>Comunicación vinculada</strong>
                  <small>Interacción asociada al servicio en curso.</small>
                </span>
              </div>

              <div>
                <q-icon name="sos" />
                <span>
                  <strong>Centro de incidencias</strong>
                  <small>Alertas SOS y seguimiento administrativo.</small>
                </span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="final-cta">
        <div class="cta-pattern" />

        <div class="cta-icon">
          <q-icon name="two_wheeler" />
        </div>

        <span class="cta-kicker">MOTRIX · TRINIDAD</span>

        <h2>
          Una movilidad más conectada empieza
          <span>con un mejor flujo</span>
        </h2>

        <p>
          Ingresa a MOTRIX para acceder a las funciones disponibles
          según tu perfil.
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
    </main>

    <footer class="public-footer">
      <div class="footer-brand">
        <span class="footer-mark">
          <q-icon name="two_wheeler" />
        </span>

        <span>
          <strong>MOTRIX</strong>
          <small>Sistema de Gestión y Solicitud de Mototaxis</small>
        </span>
      </div>

      <div class="footer-location">
        <q-icon name="location_on" />
        Trinidad · Beni · Bolivia
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
  --green: #2e7d32;
  --green-dark: #1b5e20;
  --green-deep: #0d3b22;
  --green-soft: #eaf6e9;
  --surface: #f5f8f2;
  --text: #18351d;
  --muted: #6b7a6d;
  --line: rgba(25, 72, 35, 0.1);
  --danger: #d32f2f;

  min-height: 100vh;
  overflow-x: hidden;
  color: var(--text);
  background: var(--surface);
  font-family:
    Inter,
    "Segoe UI",
    Arial,
    sans-serif;
}

.public-header {
  position: sticky;
  top: 0;
  z-index: 60;
  height: 76px;
  background: rgba(255, 255, 255, 0.94);
  border-bottom: 1px solid var(--line);
  box-shadow: 0 8px 28px rgba(20, 56, 28, 0.06);
  backdrop-filter: blur(16px);
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
  background: linear-gradient(145deg, var(--green), var(--green-dark));
  border-radius: 14px;
  box-shadow: 0 9px 20px rgba(35, 130, 56, 0.22);
}

.brand-copy {
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.brand-copy strong {
  color: var(--green-deep);
  font-size: 22px;
  line-height: 1;
  letter-spacing: 0.025em;
}

.brand-copy small {
  margin-top: 5px;
  color: var(--muted);
  font-size: 11px;
  font-weight: 650;
}

.desktop-nav {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 4px;
}

.desktop-nav button {
  padding: 10px 13px;
  color: #586c5b;
  background: transparent;
  border: 0;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition:
    color 0.16s ease,
    background 0.16s ease;
}

.desktop-nav button:hover {
  color: var(--green);
  background: #edf7ed;
}

.header-login {
  min-height: 42px;
  padding-right: 18px;
  padding-left: 18px;
  border-radius: 12px;
  font-weight: 750;
}

.hero-section {
  position: relative;
  min-height: calc(100vh - 76px);
  padding: 64px 0 90px;
  display: flex;
  align-items: center;
  background:
    radial-gradient(circle at 15% 20%, rgba(46, 125, 50, 0.1), transparent 28%),
    radial-gradient(circle at 88% 14%, rgba(46, 125, 50, 0.08), transparent 26%),
    linear-gradient(120deg, #f0f8eb 0%, #fbfdf9 48%, #edf6e9 100%);
}

.hero-glow {
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
}

.hero-glow-one {
  width: 420px;
  height: 420px;
  top: -190px;
  right: -120px;
  background: rgba(46, 125, 50, 0.06);
}

.hero-glow-two {
  width: 300px;
  height: 300px;
  bottom: -120px;
  left: -130px;
  background: rgba(46, 125, 50, 0.08);
}

.hero-grid {
  position: relative;
  z-index: 2;
  width: min(1240px, calc(100% - 52px));
  margin: 0 auto;
  display: grid;
  grid-template-columns: minmax(430px, 0.92fr) minmax(520px, 1.08fr);
  align-items: center;
  gap: 72px;
}

.hero-copy {
  min-width: 0;
}

.hero-kicker,
.section-kicker,
.cta-kicker {
  display: inline-flex;
  align-items: center;
  gap: 9px;
  color: var(--green);
  font-size: 12px;
  font-weight: 850;
  letter-spacing: 0.09em;
}

.hero-kicker-dot {
  width: 8px;
  height: 8px;
  background: var(--green);
  border-radius: 50%;
  box-shadow: 0 0 0 6px rgba(46, 125, 50, 0.1);
}

.hero-copy h1 {
  margin: 18px 0 18px;
}

.hero-brand {
  display: block;
  color: var(--green);
  font-size: clamp(42px, 5.2vw, 72px);
  font-weight: 900;
  line-height: 0.96;
  letter-spacing: -0.035em;
}

.hero-title {
  display: block;
  max-width: 720px;
  margin-top: 14px;
  color: var(--green-deep);
  font-size: clamp(30px, 3.2vw, 48px);
  font-weight: 850;
  line-height: 1.05;
  letter-spacing: -0.03em;
}

.hero-description {
  max-width: 650px;
  margin: 0;
  color: #607164;
  font-size: 17px;
  line-height: 1.72;
}

.hero-actions {
  margin-top: 30px;
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.primary-action,
.secondary-action {
  min-height: 50px;
  padding-right: 21px;
  padding-left: 21px;
  border-radius: 13px;
  font-weight: 750;
}

.hero-trust {
  margin-top: 30px;
  padding-top: 24px;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
  border-top: 1px solid rgba(34, 89, 44, 0.1);
}

.trust-item {
  min-width: 0;
  display: flex;
  align-items: flex-start;
  gap: 11px;
}

.trust-item > .q-icon {
  margin-top: 1px;
  color: var(--green);
  font-size: 23px;
}

.trust-item span {
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.trust-item strong {
  color: #274d2d;
  font-size: 13px;
}

.trust-item small {
  margin-top: 4px;
  color: #788579;
  font-size: 11px;
  line-height: 1.35;
}

.hero-visual-wrap {
  min-width: 0;
}

.mobility-card {
  position: relative;
  padding: 20px;
  background: rgba(255, 255, 255, 0.9);
  border: 1px solid rgba(37, 104, 49, 0.12);
  border-radius: 30px;
  box-shadow:
    0 28px 70px rgba(29, 75, 36, 0.13),
    0 6px 18px rgba(29, 75, 36, 0.06);
  backdrop-filter: blur(12px);
}

.visual-topbar {
  padding: 0 2px 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.city-badge,
.live-badge {
  min-height: 34px;
  padding: 0 13px;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 850;
  letter-spacing: 0.055em;
}

.city-badge {
  color: white;
  background: linear-gradient(135deg, var(--green), var(--green-dark));
  box-shadow: 0 9px 20px rgba(46, 125, 50, 0.2);
}

.live-badge {
  color: #1c6e2c;
  background: #edf8ec;
  border: 1px solid rgba(46, 125, 50, 0.13);
}

.live-dot {
  width: 7px;
  height: 7px;
  background: #31a34a;
  border-radius: 50%;
  box-shadow: 0 0 0 5px rgba(49, 163, 74, 0.1);
}

.map-surface {
  position: relative;
  height: 430px;
  overflow: hidden;
  background:
    radial-gradient(circle at 50% 45%, rgba(46, 125, 50, 0.09), transparent 30%),
    linear-gradient(145deg, #ffffff, #f2f7f0);
  border: 1px solid rgba(43, 99, 53, 0.1);
  border-radius: 24px;
}

.road {
  position: absolute;
  background: rgba(255, 255, 255, 0.96);
  border: 1px solid rgba(65, 103, 71, 0.08);
  box-shadow: 0 4px 10px rgba(54, 85, 58, 0.03);
}

.road::after {
  content: "";
  position: absolute;
  inset: 46% 0 auto;
  height: 1px;
  background: rgba(76, 110, 81, 0.07);
}

.road-one {
  width: 720px;
  height: 72px;
  top: 122px;
  left: -84px;
  transform: rotate(-17deg);
}

.road-two {
  width: 650px;
  height: 62px;
  right: -230px;
  bottom: 45px;
  transform: rotate(23deg);
}

.road-three {
  width: 600px;
  height: 58px;
  top: 180px;
  left: 42px;
  transform: rotate(77deg);
}

.route-svg {
  position: absolute;
  inset: 0;
  z-index: 2;
  width: 100%;
  height: 100%;
  pointer-events: none;
}

.route-shadow,
.route-path {
  fill: none;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.route-shadow {
  stroke: rgba(255, 255, 255, 0.96);
  stroke-width: 14;
}

.route-path {
  stroke: #2f8e3f;
  stroke-width: 6;
  stroke-dasharray: 8 12;
}

.route-point {
  position: absolute;
  z-index: 5;
  width: 48px;
  height: 48px;
  display: grid;
  place-items: center;
  color: white;
  border: 5px solid white;
  border-radius: 50%;
  box-shadow: 0 10px 26px rgba(38, 73, 43, 0.2);
}

.route-point-origin {
  left: 11%;
  bottom: 22%;
  background: var(--green);
}

.route-point-destination {
  top: 14%;
  right: 10%;
  background: #df2b34;
}

.motrix-core {
  position: absolute;
  z-index: 10;
  top: 50%;
  left: 50%;
  width: 190px;
  transform: translate(-50%, -50%);
  display: flex;
  flex-direction: column;
  align-items: center;
  pointer-events: none;
}

.motrix-core-ring {
  width: 142px;
  height: 142px;
  display: grid;
  place-items: center;
  color: white;
  font-size: 67px;
  background: linear-gradient(145deg, #35a44a, #247c35);
  border: 11px solid rgba(255, 255, 255, 0.96);
  border-radius: 50%;
  box-shadow:
    0 24px 42px rgba(33, 110, 49, 0.22),
    0 0 0 1px rgba(46, 125, 50, 0.08);
}


.floating-card,
.status-card {
  position: absolute;
  z-index: 12;
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(255, 255, 255, 0.96);
  border: 1px solid rgba(39, 93, 49, 0.1);
  box-shadow: 0 16px 34px rgba(37, 73, 42, 0.12);
  backdrop-filter: blur(10px);
}

.floating-card {
  min-width: 205px;
  padding: 13px 15px;
  border-radius: 17px;
}

.floating-card div {
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.floating-card small,
.status-card small {
  color: #7b877d;
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.07em;
}

.floating-card strong,
.status-card strong {
  margin-top: 3px;
  color: #234b29;
  font-size: 12px;
  line-height: 1.25;
}

.passenger-card {
  top: 18px;
  left: 18px;
}

.driver-card {
  right: 18px;
  bottom: 19px;
}

.status-card {
  left: 20px;
  bottom: 20px;
  padding: 12px 14px;
  border-radius: 16px;
}

.status-card > div {
  display: flex;
  flex-direction: column;
}

.status-icon {
  width: 36px;
  height: 36px;
  display: grid;
  place-items: center;
  color: var(--green);
  background: var(--green-soft);
  border-radius: 12px;
}

.visual-footer {
  padding: 16px 3px 1px;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 8px;
}

.visual-footer div {
  min-width: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  color: #5b705f;
  font-size: 11px;
  font-weight: 700;
}

.visual-footer .q-icon {
  color: var(--green);
  font-size: 18px;
}

.discover-button {
  position: absolute;
  z-index: 3;
  left: 50%;
  bottom: 23px;
  transform: translateX(-50%);
  padding: 6px 11px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #58715d;
  background: transparent;
  border: 0;
  font-size: 11px;
  font-weight: 750;
  cursor: pointer;
}

.value-strip {
  position: relative;
  z-index: 4;
  background: white;
  border-top: 1px solid var(--line);
  border-bottom: 1px solid var(--line);
}

.value-grid {
  width: min(1160px, calc(100% - 44px));
  margin: 0 auto;
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

.value-grid article {
  min-width: 0;
  padding: 25px 22px;
  display: flex;
  align-items: center;
  gap: 13px;
  border-right: 1px solid var(--line);
}

.value-grid article:last-child {
  border-right: 0;
}

.value-icon {
  flex: 0 0 auto;
  width: 42px;
  height: 42px;
  display: grid;
  place-items: center;
  color: var(--green);
  background: var(--green-soft);
  border-radius: 13px;
  font-size: 21px;
}

.value-grid article > div {
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.value-grid strong {
  color: #264c2c;
  font-size: 13px;
}

.value-grid span:not(.value-icon) {
  margin-top: 4px;
  color: #7a877c;
  font-size: 10px;
  line-height: 1.35;
}

.section {
  padding: 94px 0;
}

.section-heading {
  width: min(760px, calc(100% - 42px));
  margin: 0 auto 46px;
  text-align: center;
}

.section-heading h2,
.benefits-copy h2,
.security-copy h2 {
  margin: 13px 0 16px;
  color: var(--green-deep);
  font-size: clamp(31px, 4vw, 48px);
  font-weight: 850;
  line-height: 1.08;
  letter-spacing: -0.028em;
}

.section-heading h2 em,
.benefits-copy h2 em,
.security-copy h2 em {
  color: var(--green);
  font-style: normal;
}

.section-heading p,
.benefits-copy > p,
.security-copy > p {
  margin: 0;
  color: #718074;
  font-size: 15px;
  line-height: 1.7;
}

.workflow-section {
  background: #f7faf5;
}

.workflow-grid {
  width: min(1140px, calc(100% - 44px));
  margin: 0 auto;
  display: grid;
  grid-template-columns:
    minmax(0, 1fr) 38px
    minmax(0, 1fr) 38px
    minmax(0, 1fr) 38px
    minmax(0, 1fr);
  align-items: stretch;
  gap: 8px;
}

.workflow-card {
  position: relative;
  min-height: 240px;
  padding: 31px 25px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  background: white;
  border: 1px solid rgba(42, 94, 50, 0.1);
  border-radius: 22px;
  box-shadow: 0 12px 28px rgba(35, 71, 40, 0.06);
}

.workflow-card > .q-icon {
  width: 52px;
  height: 52px;
  display: grid;
  place-items: center;
  color: var(--green);
  background: var(--green-soft);
  border-radius: 16px;
  font-size: 27px;
}

.workflow-step {
  position: absolute;
  top: 18px;
  right: 18px;
  color: rgba(46, 125, 50, 0.18);
  font-size: 42px;
  font-weight: 900;
  line-height: 1;
}

.workflow-card h3 {
  margin: 22px 0 9px;
  color: #244b2a;
  font-size: 18px;
}

.workflow-card p {
  margin: 0;
  color: #77847a;
  font-size: 13px;
  line-height: 1.62;
}

.workflow-arrow {
  display: grid;
  place-items: center;
  color: #95aa98;
  font-size: 25px;
}

.benefits-section {
  background:
    linear-gradient(135deg, #103a22, #092d1a);
}

.benefits-grid,
.security-grid {
  width: min(1160px, calc(100% - 44px));
  margin: 0 auto;
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(460px, 0.92fr);
  align-items: center;
  gap: 72px;
}

.benefits-copy .section-kicker {
  color: #9be9a8;
}

.benefits-copy h2 {
  color: white;
}

.benefits-copy h2 em {
  color: #8ee09c;
}

.benefits-copy > p {
  color: rgba(255, 255, 255, 0.68);
}

.benefit-list,
.security-list {
  margin-top: 29px;
  display: grid;
  gap: 13px;
}

.benefit-list > div,
.security-list > div {
  display: flex;
  align-items: flex-start;
  gap: 13px;
}

.benefit-list .q-icon {
  flex: 0 0 auto;
  width: 38px;
  height: 38px;
  display: grid;
  place-items: center;
  color: #9be9a8;
  background: rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  font-size: 20px;
}

.benefit-list span,
.security-list span {
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.benefit-list strong {
  color: white;
  font-size: 13px;
}

.benefit-list small {
  margin-top: 4px;
  color: rgba(255, 255, 255, 0.6);
  font-size: 11px;
  line-height: 1.4;
}

.role-stack {
  display: grid;
  gap: 14px;
}

.role-card {
  padding: 22px;
  display: grid;
  grid-template-columns: 54px minmax(0, 1fr);
  gap: 16px;
  background: rgba(255, 255, 255, 0.96);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 20px;
  box-shadow: 0 18px 34px rgba(3, 20, 9, 0.16);
}

.role-card:nth-child(2) {
  transform: translateX(24px);
}

.role-icon {
  width: 54px;
  height: 54px;
  display: grid;
  place-items: center;
  color: var(--green);
  background: var(--green-soft);
  border-radius: 16px;
  font-size: 28px;
}

.role-card small {
  color: #7a897d;
  font-size: 9px;
  font-weight: 850;
  letter-spacing: 0.08em;
}

.role-card h3 {
  margin: 4px 0 7px;
  color: #244b2a;
  font-size: 17px;
}

.role-card p {
  margin: 0;
  color: #748078;
  font-size: 12px;
  line-height: 1.55;
}

.security-section {
  background: #f8fbf6;
}

.security-grid {
  grid-template-columns: minmax(460px, 0.92fr) minmax(0, 1fr);
}

.security-panel {
  position: relative;
  min-height: 480px;
  display: grid;
  place-items: center;
}

.shield-core {
  position: relative;
  z-index: 5;
  width: 145px;
  height: 145px;
  display: grid;
  place-items: center;
  color: white;
  background: linear-gradient(145deg, #3ca34e, #247a35);
  border: 10px solid white;
  border-radius: 50%;
  box-shadow:
    0 24px 48px rgba(42, 119, 56, 0.22),
    0 0 0 1px rgba(46, 125, 50, 0.1);
  font-size: 67px;
}

.shield-orbit {
  position: absolute;
  border: 1px dashed rgba(46, 125, 50, 0.2);
  border-radius: 50%;
}

.orbit-one {
  width: 300px;
  height: 300px;
}

.orbit-two {
  width: 430px;
  height: 430px;
}

.security-bubble {
  position: absolute;
  z-index: 6;
  padding: 11px 14px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #315338;
  background: white;
  border: 1px solid rgba(46, 125, 50, 0.1);
  border-radius: 14px;
  box-shadow: 0 11px 25px rgba(34, 74, 42, 0.09);
  font-size: 11px;
  font-weight: 750;
}

.security-bubble .q-icon {
  color: var(--green);
  font-size: 19px;
}

.bubble-passenger {
  top: 52px;
  left: 26px;
}

.bubble-driver {
  top: 72px;
  right: 17px;
}

.bubble-admin {
  bottom: 61px;
  left: 12px;
}

.bubble-sos {
  right: 31px;
  bottom: 70px;
  color: #9a3030;
}

.bubble-sos .q-icon {
  color: var(--danger);
}

.security-copy .section-kicker {
  color: var(--green);
}

.security-list .q-icon {
  flex: 0 0 auto;
  width: 39px;
  height: 39px;
  display: grid;
  place-items: center;
  color: var(--green);
  background: var(--green-soft);
  border-radius: 12px;
  font-size: 21px;
}

.security-list strong {
  color: #2d5133;
  font-size: 13px;
}

.security-list small {
  margin-top: 4px;
  color: #78847a;
  font-size: 11px;
  line-height: 1.4;
}

.final-cta {
  position: relative;
  overflow: hidden;
  padding: 88px 24px;
  text-align: center;
  color: white;
  background: linear-gradient(135deg, #2f8f40, #1f6f30 55%, #164f24);
}

.cta-pattern {
  position: absolute;
  inset: 0;
  opacity: 0.16;
  background-image:
    radial-gradient(circle at 25% 30%, white 0 1px, transparent 1.5px),
    radial-gradient(circle at 75% 70%, white 0 1px, transparent 1.5px);
  background-size: 28px 28px;
}

.cta-icon,
.cta-kicker,
.final-cta h2,
.final-cta p,
.cta-actions {
  position: relative;
  z-index: 2;
}

.cta-icon {
  width: 62px;
  height: 62px;
  margin: 0 auto 17px;
  display: grid;
  place-items: center;
  color: #257c36;
  background: white;
  border-radius: 19px;
  box-shadow: 0 15px 30px rgba(9, 49, 17, 0.2);
  font-size: 34px;
}

.cta-kicker {
  color: rgba(255, 255, 255, 0.76);
}

.final-cta h2 {
  max-width: 760px;
  margin: 13px auto 14px;
  font-size: clamp(32px, 4.6vw, 52px);
  font-weight: 850;
  line-height: 1.08;
  letter-spacing: -0.03em;
}

.final-cta h2 span {
  display: block;
  color: #d9ffdf;
}

.final-cta p {
  max-width: 620px;
  margin: 0 auto;
  color: rgba(255, 255, 255, 0.76);
  font-size: 15px;
  line-height: 1.65;
}

.cta-actions {
  margin-top: 27px;
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 11px;
}

.cta-login {
  border-radius: 12px;
  font-weight: 750;
}

.public-footer {
  min-height: 92px;
  padding: 20px max(28px, calc((100vw - 1160px) / 2));
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  color: #657467;
  background: #f4f8f2;
  border-top: 1px solid var(--line);
}

.footer-brand {
  display: flex;
  align-items: center;
  gap: 11px;
}

.footer-mark {
  width: 38px;
  height: 38px;
  display: grid;
  place-items: center;
  color: white;
  background: var(--green);
  border-radius: 12px;
  font-size: 20px;
}

.footer-brand > span:last-child {
  display: flex;
  flex-direction: column;
}

.footer-brand strong {
  color: var(--green-deep);
  font-size: 15px;
}

.footer-brand small {
  margin-top: 2px;
  font-size: 10px;
}

.footer-location {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  font-weight: 650;
}

@media (max-width: 1080px) {
  .hero-grid {
    grid-template-columns: minmax(0, 1fr);
    gap: 44px;
  }

  .hero-copy {
    max-width: 780px;
    margin: 0 auto;
    text-align: center;
  }

  .hero-description {
    margin-right: auto;
    margin-left: auto;
  }

  .hero-actions,
  .hero-trust {
    justify-content: center;
  }

  .hero-trust {
    max-width: 650px;
    margin-right: auto;
    margin-left: auto;
    text-align: left;
  }

  .hero-visual-wrap {
    width: min(680px, 100%);
    margin: 0 auto;
  }

  .benefits-grid,
  .security-grid {
    grid-template-columns: minmax(0, 1fr);
    gap: 48px;
  }

  .benefits-copy,
  .security-copy {
    max-width: 760px;
    margin: 0 auto;
  }

  .role-stack {
    width: min(720px, 100%);
    margin: 0 auto;
  }

  .security-panel {
    width: min(620px, 100%);
    margin: 0 auto;
    order: 2;
  }

  .security-copy {
    order: 1;
  }

  .workflow-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
  }

  .workflow-arrow {
    display: none;
  }

  .value-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .value-grid article:nth-child(2) {
    border-right: 0;
  }

  .value-grid article:nth-child(-n + 2) {
    border-bottom: 1px solid var(--line);
  }
}

@media (max-width: 760px) {
  .public-header {
    height: 68px;
  }

  .header-inner {
    width: calc(100% - 26px);
    gap: 12px;
  }

  .brand-mark {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    font-size: 23px;
  }

  .brand-copy small,
  .desktop-nav {
    display: none;
  }

  .brand-copy strong {
    font-size: 19px;
  }

  .header-login {
    margin-left: auto;
    min-height: 39px;
    padding-right: 13px;
    padding-left: 13px;
  }

  .hero-section {
    min-height: auto;
    padding: 54px 0 78px;
  }

  .hero-grid {
    width: calc(100% - 28px);
  }

  .hero-brand {
    font-size: 45px;
  }

  .hero-title {
    font-size: 31px;
  }

  .hero-description {
    font-size: 15px;
    line-height: 1.62;
  }

  .hero-actions {
    flex-direction: column;
  }

  .primary-action,
  .secondary-action {
    width: 100%;
  }

  .hero-trust {
    grid-template-columns: minmax(0, 1fr);
  }

  .mobility-card {
    padding: 13px;
    border-radius: 24px;
  }

  .visual-topbar {
    padding-bottom: 12px;
  }

  .city-badge,
  .live-badge {
    padding: 0 10px;
    font-size: 9px;
  }

  .map-surface {
    height: 380px;
    border-radius: 19px;
  }

  .motrix-core {
    width: 165px;
  }

  .motrix-core-ring {
    width: 122px;
    height: 122px;
    font-size: 58px;
  }

  .floating-card {
    min-width: 175px;
    padding: 10px 12px;
  }

  .passenger-card {
    top: 14px;
    left: 12px;
  }

  .driver-card {
    right: 12px;
    bottom: 14px;
  }

  .status-card {
    display: none;
  }

  .visual-footer {
    grid-template-columns: minmax(0, 1fr);
    gap: 7px;
  }

  .section {
    padding: 72px 0;
  }

  .workflow-grid,
  .value-grid {
    grid-template-columns: minmax(0, 1fr);
  }

  .value-grid article {
    border-right: 0;
    border-bottom: 1px solid var(--line);
  }

  .value-grid article:last-child {
    border-bottom: 0;
  }

  .role-card:nth-child(2) {
    transform: none;
  }

  .security-panel {
    min-height: 390px;
  }

  .orbit-one {
    width: 245px;
    height: 245px;
  }

  .orbit-two {
    width: 340px;
    height: 340px;
  }

  .shield-core {
    width: 122px;
    height: 122px;
    font-size: 57px;
  }

  .security-bubble {
    padding: 9px 11px;
    font-size: 9px;
  }

  .public-footer {
    padding: 24px 18px;
    flex-direction: column;
    align-items: flex-start;
  }
}

@media (max-width: 480px) {
  .hero-kicker {
    font-size: 10px;
  }

  .hero-title {
    font-size: 28px;
  }

  .map-surface {
    height: 350px;
  }

  .floating-card {
    min-width: 152px;
    max-width: 164px;
  }

  .floating-card strong {
    font-size: 10px;
  }

  .floating-card small {
    font-size: 8px;
  }

  .motrix-core-ring {
    width: 108px;
    height: 108px;
    font-size: 50px;
  }

  .security-panel {
    min-height: 350px;
  }

  .orbit-two {
    width: 300px;
    height: 300px;
  }

  .bubble-passenger {
    top: 42px;
    left: 0;
  }

  .bubble-driver {
    top: 55px;
    right: 0;
  }

  .bubble-admin {
    bottom: 48px;
    left: 0;
  }

  .bubble-sos {
    right: 0;
    bottom: 56px;
  }
}
</style>
