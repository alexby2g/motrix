<template>
  <div class="landing-page">
    <header class="landing-header">
      <div class="container header-inner">
        <button class="brand" type="button" @click="scrollTo('inicio')">
          <img src="/motrix-logo-oficial.png" alt="MOTRIX" />
          <span class="brand-copy">
            <strong>MOTRIX</strong>
            <small>TU MOTOTAXI MÁS CERCA</small>
          </span>
        </button>

        <nav class="desktop-nav" aria-label="Navegación principal">
          <button @click="scrollTo('inicio')">Inicio</button>
          <button @click="scrollTo('que-es')">¿Qué es MOTRIX?</button>
          <button @click="scrollTo('como-funciona')">Cómo funciona</button>
          <button @click="scrollTo('descarga')">Descargar app</button>
          <button @click="scrollTo('trinidad')">Trinidad</button>
        </nav>

        <div class="header-actions">
          <q-btn outline color="green-8" icon="person_outline" label="Iniciar sesión" no-caps @click="irLogin" />
          <q-btn unelevated color="green-8" icon="person_add" label="Regístrate" no-caps @click="irRegistro" />
        </div>

        <q-btn flat round color="green-9" icon="menu" class="mobile-menu-btn" @click="menuAbierto = !menuAbierto" />
      </div>

      <div v-if="menuAbierto" class="mobile-menu">
        <button @click="scrollMobile('inicio')">Inicio</button>
        <button @click="scrollMobile('que-es')">¿Qué es MOTRIX?</button>
        <button @click="scrollMobile('como-funciona')">Cómo funciona</button>
        <button @click="scrollMobile('descarga')">Descargar app</button>
        <button @click="scrollMobile('trinidad')">Trinidad</button>
        <div class="mobile-menu-actions">
          <q-btn outline color="green-8" label="Iniciar sesión" no-caps @click="irLogin" />
          <q-btn unelevated color="green-8" label="Regístrate" no-caps @click="irRegistro" />
        </div>
      </div>
    </header>

    <main>
      <section id="inicio" class="hero-split">
        <div class="hero-copy-panel">
          <div class="hero-copy-inner">
            <div class="location-pill"><q-icon name="location_on" /> TRINIDAD · BENI · BOLIVIA</div>
            <h1>Tu mototaxi <span>fácil, seguro</span> y al instante</h1>
            <p>MOTRIX conecta pasajeros, mototaxistas, sindicatos y federaciones en Trinidad para una movilidad más segura, ordenada y moderna.</p>

            <div class="hero-actions">
              <q-btn unelevated color="green-5" text-color="white" icon="download" label="Descargar APK" no-caps @click="descargarApk" />
              <q-btn outline color="white" icon="play_circle" label="Ver cómo funciona" no-caps @click="scrollTo('como-funciona')" />
            </div>

            <div class="hero-badges">
              <span><q-icon name="verified_user" /> Seguro</span>
              <span><q-icon name="groups" /> Confiable</span>
              <span><q-icon name="location_city" /> 100% Trinidad</span>
            </div>
          </div>
        </div>
        <div class="hero-photo-panel" aria-label="Mototaxista MOTRIX en Trinidad" />
      </section>

      <section id="que-es" class="about-section">
        <div class="container about-grid">
          <div class="about-copy">
            <span class="eyebrow">MOVILIDAD INTELIGENTE PARA TRINIDAD</span>
            <h2>¿Qué es <span>MOTRIX?</span></h2>
            <p class="lead">MOTRIX es una plataforma de movilidad que conecta pasajeros con mototaxistas registrados y, al mismo tiempo, integra a sindicatos y federaciones dentro de una misma herramienta digital.</p>
            <p>Su objetivo es hacer que solicitar un mototaxi sea más rápido, seguro y ordenado, con información clara del servicio, seguimiento del viaje y apoyo a la organización del sector local.</p>
            <q-btn unelevated color="green-8" icon-right="arrow_forward" label="Ver cómo funciona" no-caps @click="scrollTo('como-funciona')" />
          </div>

          <div class="about-cards">
            <article class="info-card">
              <div class="info-icon"><q-icon name="two_wheeler" /></div>
              <h3>Transporte seguro</h3>
              <p>Mototaxistas identificados y viajes monitoreados.</p>
            </article>
            <article class="info-card">
              <div class="info-icon"><q-icon name="bolt" /></div>
              <h3>Rápido y al instante</h3>
              <p>Solicita tu mototaxi desde el celular en segundos.</p>
            </article>
            <article class="info-card">
              <div class="info-icon"><q-icon name="groups" /></div>
              <h3>Apoyo al sector local</h3>
              <p>Conecta mototaxistas, sindicatos y federaciones.</p>
            </article>
            <article class="info-card">
              <div class="info-icon"><q-icon name="payments" /></div>
              <h3>Pagos flexibles</h3>
              <p>Efectivo o métodos digitales, tú eliges.</p>
            </article>
          </div>
        </div>
      </section>

      <section id="como-funciona" class="steps-section">
        <div class="container steps-layout">
          <div class="section-heading">
            <span class="eyebrow">ASÍ DE FÁCIL</span>
            <h2>¿Cómo funciona?</h2>
            <p>Solicita, viaja y llega a tu destino de forma rápida y segura.</p>
          </div>

          <div class="steps-grid">
            <article v-for="paso in pasos" :key="paso.numero" class="step-card">
              <span class="step-number">{{ paso.numero }}</span>
              <div class="step-icon"><q-icon :name="paso.icono" /></div>
              <h3>{{ paso.titulo }}</h3>
              <p>{{ paso.texto }}</p>
            </article>
          </div>
        </div>
      </section>

      <section id="descarga" class="download-section">
        <div class="container download-grid">
          <div class="download-card">
            <div class="app-mark"><img src="/motrix-logo-oficial.png" alt="MOTRIX" /></div>
            <span class="app-kicker">APP OFICIAL</span>
            <h3>MOTRIX para Android</h3>
            <p>Tu mototaxi, más cerca.</p>
            <div class="phone-preview">
              <div class="phone-top"><span>9:41</span><q-icon name="signal_cellular_alt" /></div>
              <div class="phone-brand"><img src="/motrix-logo-oficial.png" alt="" /><strong>MOTRIX</strong></div>
              <div class="phone-map">
                <q-icon name="location_on" class="pin pin-start" />
                <div class="route-line" />
                <q-icon name="two_wheeler" class="bike" />
                <q-icon name="place" class="pin pin-end" />
              </div>
              <div class="phone-status"><small>Tu mototaxi llegará en:</small><strong>3 min</strong></div>
            </div>
          </div>

          <div class="download-copy">
            <span class="eyebrow">DESCARGA OFICIAL</span>
            <h2>Descarga MOTRIX para Android</h2>
            <p>Instala la aplicación oficial de MOTRIX directamente desde nuestro sitio. Sin QR y sin pasos innecesarios.</p>
            <div class="download-points">
              <span><q-icon name="check_circle" /> Instalación guiada</span>
              <span><q-icon name="lock" /> Acceso seguro</span>
              <span><q-icon name="system_update_alt" /> Siempre actualizado</span>
            </div>
            <div class="download-actions">
              <q-btn unelevated color="green-8" icon="download" label="Descargar APK oficial" no-caps @click="descargarApk" />
              <q-btn outline color="green-8" icon="person_add" label="Crear cuenta" no-caps @click="irRegistro" />
            </div>
            <small class="download-note">Disponible para Android. La descarga inicia directamente desde motrixbolivia.com.</small>
          </div>
        </div>
      </section>

      <section id="trinidad" class="trinidad-section">
        <div class="trinidad-overlay" />
        <div class="container trinidad-inner">
          <div class="trinidad-copy">
            <h2>MOTRIX en Trinidad</h2>
            <p>Una ciudad en movimiento, más unida y más cerca de ti.</p>
          </div>
          <div class="trinidad-box">
            <strong><q-icon name="location_on" /> Trinidad, Beni, Bolivia</strong>
            <p>Apoyamos el transporte local y trabajamos junto a mototaxistas, sindicatos y federaciones para una Trinidad más conectada.</p>
          </div>
        </div>
      </section>
    </main>

    <footer class="landing-footer">
      <div class="container footer-grid">
        <div class="footer-brand">
          <img src="/motrix-logo-oficial.png" alt="MOTRIX" />
          <div><strong>MOTRIX</strong><small>TU MOTOTAXI MÁS CERCA</small></div>
        </div>
        <div class="footer-links">
          <strong>Enlaces</strong>
          <button @click="scrollTo('inicio')">Inicio</button>
          <button @click="scrollTo('que-es')">¿Qué es MOTRIX?</button>
          <button @click="scrollTo('como-funciona')">Cómo funciona</button>
          <button @click="scrollTo('descarga')">Descarga</button>
        </div>
        <div class="footer-links">
          <strong>Legal</strong>
          <span>Términos y condiciones</span>
          <span>Política de privacidad</span>
          <span>Centro de ayuda</span>
        </div>
        <div class="footer-location">
          <q-icon name="location_on" />
          <div><strong>Trinidad, Beni · Bolivia</strong><small>Tu mototaxi más cerca.</small></div>
        </div>
      </div>
      <div class="container footer-bottom"><span>© MOTRIX. Todos los derechos reservados.</span><span>Movilidad segura para Trinidad.</span></div>
    </footer>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const menuAbierto = ref(false)

const pasos = [
  { numero: 1, icono: 'smartphone', titulo: 'Solicita', texto: 'Ingresa tu destino y solicita tu mototaxi.' },
  { numero: 2, icono: 'person_pin_circle', titulo: 'Espera', texto: 'Un mototaxista cercano acepta tu viaje.' },
  { numero: 3, icono: 'two_wheeler', titulo: 'Viaja', texto: 'Sigue el estado de tu viaje en tiempo real.' },
  { numero: 4, icono: 'flag', titulo: 'Llega', texto: 'Finaliza y califica tu servicio.' },
]

function scrollTo(id) {
  document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}
function scrollMobile(id) {
  menuAbierto.value = false
  requestAnimationFrame(() => scrollTo(id))
}
function irLogin() { router.push('/login') }
function irRegistro() { router.push('/registro-pasajero') }
function descargarApk() { window.location.href = 'https://motrixbolivia.com/downloads/MOTRIX.apk' }
</script>

<style scoped>
:global(html){scroll-behavior:smooth}:global(body){margin:0;background:#fff}.landing-page{overflow-x:hidden;background:#fff;color:#10271a}.container{width:min(1180px,calc(100% - 36px));margin:0 auto}.landing-header{position:sticky;top:0;z-index:100;background:rgba(255,255,255,.98);backdrop-filter:blur(14px);border-bottom:1px solid #e5ebe7}.header-inner{min-height:86px;display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:24px}.brand,.desktop-nav button,.footer-links button,.mobile-menu button{border:0;background:none;cursor:pointer}.brand{display:flex;align-items:center;gap:12px;color:inherit}.brand img,.footer-brand img{width:56px;height:56px;object-fit:contain;border-radius:16px}.brand-copy,.footer-brand div{display:grid;text-align:left}.brand-copy strong,.footer-brand strong{font-size:26px;line-height:1;font-weight:900;letter-spacing:-.03em}.brand-copy small,.footer-brand small{margin-top:4px;font-size:9px;font-weight:900;color:#108845}.desktop-nav{display:flex;justify-content:center;gap:30px}.desktop-nav button{font-size:15px;font-weight:800;color:#21382b}.header-actions{display:flex;gap:10px}.header-actions :deep(.q-btn){min-height:44px;border-radius:14px;padding-inline:18px;font-weight:800}.mobile-menu-btn,.mobile-menu{display:none}.hero-split{display:grid;grid-template-columns:48% 52%;min-height:620px;background:#04371f}.hero-copy-panel{display:flex;align-items:center;justify-content:flex-end;background:#04371f}.hero-copy-inner{width:min(100%,570px);padding:54px 56px 54px 28px;color:#fff}.hero-photo-panel{min-height:620px;background:url('/motrix-hero-lanzamiento-final.png') 52% center/cover no-repeat}.location-pill{display:inline-flex;align-items:center;gap:8px;width:fit-content;padding:9px 14px;border:1px solid #35dc84;border-radius:999px;background:#065c3147;font-size:11px;font-weight:900}.hero-split h1{margin:20px 0 18px;font-size:clamp(58px,5vw,78px);line-height:.92;font-weight:900;letter-spacing:-.06em}.hero-split h1 span{display:block;color:#35ea86}.hero-split p{max-width:500px;margin:0;color:#fffffff5;font-size:18px;line-height:1.55}.hero-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:28px}.hero-actions :deep(.q-btn){min-height:54px;border-radius:14px;padding-inline:24px;font-weight:900}.hero-badges{display:flex;flex-wrap:wrap;gap:18px;margin-top:22px;font-size:12px;font-weight:800}.hero-badges span{display:inline-flex;align-items:center;gap:6px}.about-section{padding:70px 0;background:linear-gradient(180deg,#fff,#eef9f2)}.about-grid{display:grid;grid-template-columns:.9fr 1.4fr;align-items:center;gap:44px}.eyebrow{display:inline-block;color:#0f8a43;font-size:12px;font-weight:900;letter-spacing:.14em}.about-copy h2,.section-heading h2,.download-copy h2{margin:10px 0 16px;font-size:clamp(42px,4.4vw,60px);line-height:.98;font-weight:900;letter-spacing:-.05em}.about-copy h2 span{color:#0d9a4d}.about-copy p{margin:0 0 14px;color:#586a61;font-size:16px;line-height:1.6}.about-copy .lead{color:#203a2c;font-size:18px}.about-copy :deep(.q-btn){margin-top:8px;border-radius:14px;min-height:50px;padding-inline:20px;font-weight:900}.about-cards{display:grid;grid-template-columns:repeat(2,1fr);gap:16px}.info-card{padding:24px 20px;text-align:center;background:#fff;border:1px solid #dfeae3;border-radius:20px;box-shadow:0 12px 30px rgba(16,84,49,.07)}.info-icon{display:grid;place-items:center;width:62px;height:62px;margin:0 auto 14px;border-radius:50%;background:#e8f8ee;color:#0a9146;font-size:30px}.info-card h3{margin:0 0 8px;font-size:18px;font-weight:900}.info-card p{margin:0;color:#67776e;font-size:13px;line-height:1.45}.steps-section{padding:30px 0;background:linear-gradient(180deg,#f3fbf6,#eef9f2);border-top:1px solid #e4f1e8;border-bottom:1px solid #e4f1e8}.steps-layout{display:grid;grid-template-columns:31% 69%;align-items:center;gap:22px}.section-heading h2{margin:8px 0 8px;font-size:clamp(38px,3.5vw,52px);line-height:.98;font-weight:900;letter-spacing:-.05em}.section-heading p{margin:0;color:#607167;font-size:15px;line-height:1.45}.steps-grid{display:grid;grid-template-columns:repeat(4,1fr);align-items:start;gap:8px;margin:0}.step-card{position:relative;padding:8px 6px;text-align:center}.step-card:not(:last-child)::after{content:'';position:absolute;top:43px;left:72%;width:58%;border-top:2px dotted #68bf88}.step-number{position:absolute;z-index:2;top:0;left:calc(50% - 42px);display:grid;place-items:center;width:28px;height:28px;border-radius:50%;background:#0a8b43;color:#fff;font-size:12px;font-weight:900}.step-icon{display:grid;place-items:center;width:72px;height:72px;margin:0 auto 10px;border-radius:50%;background:#e1f5e8;color:#0a8740;font-size:34px}.step-card h3{margin:0 0 4px;font-size:17px;font-weight:900}.step-card p{max-width:160px;margin:0 auto;color:#69786f;font-size:11px;line-height:1.35}.download-section{padding:32px 0 36px;background:#fff}.download-grid{display:grid;grid-template-columns:.95fr 1.05fr;align-items:center;gap:42px}.download-card{position:relative;min-height:300px;padding:26px;overflow:hidden;border-radius:24px;color:#fff;background:radial-gradient(circle at 80% 20%,rgba(64,232,135,.32),transparent 32%),linear-gradient(145deg,#0d7f41,#04371f);box-shadow:0 18px 48px rgba(6,73,39,.18)}.app-mark{display:grid;place-items:center;width:62px;height:62px;border-radius:18px;background:#fff;box-shadow:0 12px 30px #00000029}.app-mark img{width:50px;height:50px;object-fit:contain}.app-kicker{display:block;margin-top:14px;color:#8ff2b5;font-size:11px;font-weight:900;letter-spacing:.13em}.download-card h3{max-width:55%;margin:5px 0 0;font-size:27px;line-height:1.05;font-weight:900}.download-card>p{max-width:48%;margin:9px 0 0;color:#ffffffd1;font-size:14px;line-height:1.45}.phone-preview{position:absolute;right:22px;bottom:-58px;width:190px;height:320px;padding:14px 13px;border:8px solid #111;border-radius:34px;background:#f8fbf9;color:#123423;transform:rotate(4deg);box-shadow:0 26px 54px #00000047}.phone-top{display:flex;justify-content:space-between;align-items:center;font-size:9px;font-weight:800}.phone-brand{display:flex;align-items:center;gap:7px;margin-top:13px;color:#08783c}.phone-brand img{width:27px;height:27px;object-fit:contain}.phone-brand strong{font-size:15px}.phone-map{position:relative;height:135px;margin-top:13px;overflow:hidden;border-radius:16px;background-color:#eef5f0;background-image:linear-gradient(90deg,transparent 49%,#e7eee9 50%,transparent 51%),linear-gradient(transparent 49%,#e7eee9 50%,transparent 51%);background-size:34px 34px}.pin,.bike{position:absolute;z-index:3;color:#0d9348}.pin-start{left:22px;bottom:25px;color:#e74636}.pin-end{right:18px;top:20px}.bike{left:76px;top:70px}.route-line{position:absolute;left:34px;top:80px;width:100px;height:4px;border-radius:99px;background:#15964d;transform:rotate(-32deg)}.phone-status{margin-top:12px;text-align:center}.phone-status small{display:block;color:#76867d;font-size:9px}.phone-status strong{display:block;margin-top:2px;color:#0b8b43;font-size:25px}.download-copy p{max-width:620px;margin:0;color:#5f7067;font-size:16px;line-height:1.58}.download-points{display:flex;flex-wrap:wrap;gap:18px;margin-top:22px;color:#274537;font-size:13px;font-weight:800}.download-points span{display:inline-flex;align-items:center;gap:7px}.download-points .q-icon{color:#169247;font-size:20px}.download-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:18px}.download-actions :deep(.q-btn){min-height:54px;border-radius:14px;padding-inline:22px;font-weight:900}.download-note{display:block;margin-top:12px;color:#87948c;font-size:12px}.trinidad-section{position:relative;display:flex;align-items:center;min-height:185px;background:url('/motrix-trinidad-lanzamiento-final.jpg') center/cover no-repeat}.trinidad-overlay{position:absolute;inset:0;background:linear-gradient(90deg,rgba(3,37,22,.84),rgba(3,37,22,.52) 55%,rgba(3,37,22,.24))}.trinidad-inner{position:relative;z-index:2;display:flex;align-items:center;justify-content:space-between;gap:30px;padding:32px 0;color:#fff}.trinidad-copy h2{margin:0;font-size:clamp(34px,3.2vw,48px);line-height:.95;font-weight:900;letter-spacing:-.04em}.trinidad-copy p{margin:10px 0 0;font-size:17px}.trinidad-box{width:min(350px,100%);padding:14px 16px;border:1px solid #ffffff2e;border-radius:18px;background:#03231494;backdrop-filter:blur(10px)}.trinidad-box strong{display:flex;align-items:center;gap:7px;font-size:15px}.trinidad-box p{margin:10px 0 0;color:#ffffffe6;font-size:13px;line-height:1.5}.landing-footer{padding:24px 0 16px;background:#063a22;color:#fff}.footer-grid{display:grid;grid-template-columns:1.2fr .8fr .8fr 1.1fr;gap:30px}.footer-brand{display:flex;align-items:center;gap:12px}.footer-links{display:grid;gap:7px}.footer-links strong{margin-bottom:4px;font-size:13px}.footer-links button,.footer-links span{padding:0;text-align:left;color:#ffffffc2;font-size:12px}.footer-location{display:flex;align-items:flex-start;gap:10px}.footer-location>.q-icon{color:#93e7b1;font-size:28px}.footer-location div{display:grid}.footer-location strong{font-size:14px}.footer-location small{margin-top:5px;color:#ffffffa8;font-size:11px}.footer-bottom{display:flex;justify-content:space-between;gap:14px;margin-top:28px;padding-top:16px;border-top:1px solid #ffffff1f;color:#ffffff99;font-size:11px}
@media(max-width:1080px){.desktop-nav,.header-actions{display:none}.mobile-menu-btn{display:inline-flex;justify-self:end}.header-inner{grid-template-columns:1fr auto}.mobile-menu{display:grid;gap:4px;padding:10px 18px 18px;border-top:1px solid #e7eee9;background:#fff}.mobile-menu button{padding:12px 6px;text-align:left;color:#1d3929;font-weight:800}.mobile-menu-actions{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:8px}.about-grid,.download-grid{grid-template-columns:1fr}.steps-layout{grid-template-columns:1fr}.steps-grid{margin-top:18px}.about-copy{max-width:760px}.footer-grid{grid-template-columns:1fr 1fr}.trinidad-inner{flex-direction:column;align-items:flex-start}}
@media(max-width:767px){.container{width:min(calc(100% - 24px),640px)}.header-inner{min-height:72px}.brand img{width:48px;height:48px}.brand-copy strong{font-size:22px}.brand-copy small{font-size:8px}.hero-split{grid-template-columns:1fr;min-height:0}.hero-photo-panel{order:1;min-height:330px;background-position:50% center}.hero-copy-panel{order:2;justify-content:stretch}.hero-copy-inner{width:100%;padding:30px 20px 42px}.hero-split h1{font-size:clamp(44px,13vw,58px)}.hero-split p{font-size:15px}.hero-actions{display:grid;grid-template-columns:1fr}.hero-actions :deep(.q-btn){width:100%}.about-section{padding:48px 0}.about-cards{grid-template-columns:1fr 1fr;gap:10px}.info-card{padding:18px 12px}.info-icon{width:54px;height:54px;font-size:26px}.info-card h3{font-size:15px}.info-card p{font-size:11px}.steps-section{padding:50px 0 34px}.steps-grid{grid-template-columns:1fr;gap:12px;margin-top:26px}.step-card{min-height:92px;padding:14px 12px 14px 92px;text-align:left}.step-number{top:6px;left:4px;width:26px;height:26px;font-size:11px}.step-icon{position:absolute;top:18px;left:22px;width:58px;height:58px;margin:0;font-size:30px}.step-card h3{font-size:17px}.step-card p{max-width:none;margin:0;font-size:12px}.download-section{padding:38px 0 48px}.download-card{min-height:430px;padding:24px;border-radius:24px}.download-card h3{max-width:54%;font-size:27px}.download-card>p{max-width:50%;font-size:12px}.phone-preview{right:14px;width:172px;height:315px}.download-copy h2{font-size:40px}.download-copy p{font-size:14px}.download-points{display:grid;gap:10px}.download-actions{display:grid;grid-template-columns:1fr}.download-actions :deep(.q-btn){width:100%}.trinidad-section{min-height:380px;background-position:56%}.trinidad-inner{padding:48px 0}.trinidad-copy h2{font-size:42px}.trinidad-copy p{font-size:17px}.footer-grid{grid-template-columns:1fr;gap:24px}.footer-bottom{flex-direction:column;gap:6px}}
@media(max-width:767px){.steps-layout{display:block}.steps-grid{grid-template-columns:repeat(2,1fr);gap:12px}.step-card{min-height:auto;padding:10px;text-align:center}.step-card:not(:last-child)::after{display:none}.step-number{top:2px;left:calc(50% - 38px)}.step-icon{position:static;width:66px;height:66px;margin:0 auto 8px;font-size:32px}.step-card p{max-width:150px;margin:0 auto}.download-card{min-height:340px}.trinidad-section{min-height:300px}}
@media(max-width:430px){.brand-copy strong{font-size:20px}.brand-copy small{font-size:7px}.about-cards{grid-template-columns:1fr}.hero-photo-panel{min-height:300px}.hero-split h1{font-size:40px}.phone-preview{right:8px;width:150px;height:278px}.download-card h3{max-width:56%;font-size:24px}.download-card>p{max-width:54%;font-size:11px}.download-card{min-height:400px}}
</style>
