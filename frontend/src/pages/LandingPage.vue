<template>
  <div class="landing-page">
    <header class="site-header">
      <div class="header-inner">
        <button class="brand" type="button" @click="goTop">
          <img src="/motrix-logo-oficial.png" alt="MOTRIX">
          <span>
            <strong>MOTRIX</strong>
            <small>Tu mototaxi más cerca</small>
          </span>
        </button>

        <nav class="desktop-nav" aria-label="Navegación principal">
          <button @click="goTop">Inicio</button>
          <button @click="goTo('beneficios')">Beneficios</button>
          <button @click="goTo('como')">Cómo funciona</button>
          <button @click="goTo('descarga')">Descargar app</button>
          <button @click="goTo('trinidad')">Trinidad</button>
        </nav>

        <div class="header-actions">
          <span class="city-pill"><q-icon name="place" /> Trinidad · Beni</span>
          <q-btn outline no-caps color="green-9" icon="person_outline" label="Iniciar sesión" @click="irLogin" />
          <q-btn unelevated no-caps color="green-8" icon="person_add" label="Regístrate" @click="irRegistro" />
        </div>

        <button class="mobile-menu-btn" type="button" aria-label="Abrir menú" @click="menuOpen = !menuOpen">
          <q-icon :name="menuOpen ? 'close' : 'menu'" />
        </button>
      </div>

      <transition name="menu-fade">
        <div v-if="menuOpen" class="mobile-menu">
          <button @click="mobileGo('top')">Inicio</button>
          <button @click="mobileGo('beneficios')">Beneficios</button>
          <button @click="mobileGo('como')">Cómo funciona</button>
          <button @click="mobileGo('descarga')">Descargar app</button>
          <button @click="mobileGo('trinidad')">Trinidad</button>
          <button @click="irLogin">Iniciar sesión</button>
          <button class="primary" @click="irRegistro">Regístrate</button>
        </div>
      </transition>
    </header>

    <main>
      <section class="hero">
        <div class="hero-copy">
          <span class="location-tag"><q-icon name="place" /> Trinidad · Beni · Bolivia</span>
          <h1>Tu mototaxi <span>fácil, seguro</span> y al instante</h1>
          <p>MOTRIX conecta pasajeros, mototaxistas, sindicatos y federaciones en Trinidad para una movilidad más segura, ordenada y moderna.</p>
          <div class="hero-actions">
            <button class="btn btn-primary" @click="irRegistro"><q-icon name="near_me" /> Comenzar ahora <q-icon name="arrow_forward" /></button>
            <button class="btn btn-secondary" @click="goTo('como')"><q-icon name="play_circle_outline" /> Ver cómo funciona</button>
          </div>
          <div class="hero-mini"><q-icon name="android" /> App Android oficial · descarga directa mientras llega Google Play.</div>
        </div>
        <div class="hero-visual" aria-hidden="true" />
      </section>

      <section id="beneficios" class="benefits section-shell">
        <article v-for="item in benefits" :key="item.title" class="benefit-card">
          <div class="benefit-icon"><q-icon :name="item.icon" /></div>
          <div><h2>{{ item.title }}</h2><p>{{ item.text }}</p></div>
        </article>
      </section>

      <section id="como" class="how section-shell">
        <div class="section-heading">
          <span>Cómo funciona</span>
          <h2>Tu próximo viaje en 4 pasos</h2>
          <p>Solicita, espera, viaja y llega. Así de simple.</p>
        </div>
        <div class="steps">
          <article v-for="(step, index) in steps" :key="step.title" class="step-card">
            <span class="step-number">{{ index + 1 }}</span>
            <div class="step-icon"><q-icon :name="step.icon" /></div>
            <h3>{{ step.title }}</h3>
            <p>{{ step.text }}</p>
          </article>
        </div>
      </section>

      <section id="descarga" class="download-wrap section-shell">
        <div class="download-panel">
          <div class="download-copy">
            <span class="eyebrow">Descarga oficial</span>
            <h2>Descarga MOTRIX para Android</h2>
            <p>Instala la APK oficial desde nuestro sitio mientras completamos la publicación en Google Play.</p>
            <div class="download-actions">
              <button class="btn btn-primary big" @click="descargarApk"><q-icon name="download" /> Descargar APK oficial</button>
              <span class="play-soon"><q-icon name="shop" /> Próximamente en Google Play</span>
            </div>
            <div class="trust-row"><span><q-icon name="verified_user" /> Sitio oficial</span><span><q-icon name="shield" /> Descarga segura</span><span><q-icon name="update" /> Siempre actualizado</span></div>
          </div>
          <div class="qr-box">
            <img src="/motrix-qr-presentacion-final-v34.png" alt="Código QR oficial de MOTRIX">
            <strong>Escanea y abre MOTRIX</strong>
            <small>piloto.motrixbolivia.com</small>
          </div>
        </div>
      </section>

      <section id="trinidad" class="city-section">
        <div class="city-overlay" />
        <div class="city-content section-shell">
          <span class="eyebrow light">MOTRIX en Trinidad</span>
          <h2>Una ciudad que se mueve contigo</h2>
          <p>Movilidad conectada, segura y organizada para nuestra ciudad.</p>
          <div class="city-grid">
            <article v-for="item in cityItems" :key="item.title"><q-icon :name="item.icon" /><div><strong>{{ item.title }}</strong><small>{{ item.text }}</small></div></article>
          </div>
        </div>
      </section>
    </main>

    <footer class="footer">
      <div class="footer-inner section-shell">
        <div class="footer-brand"><img src="/motrix-logo-oficial.png" alt="MOTRIX"><div><strong>MOTRIX</strong><small>Tu mototaxi más cerca</small></div></div>
        <div class="footer-links"><button @click="goTop">Inicio</button><button @click="goTo('beneficios')">Beneficios</button><button @click="goTo('como')">Cómo funciona</button><button @click="goTo('descarga')">Descargar app</button></div>
        <div class="footer-city"><q-icon name="place" /><div><strong>Trinidad · Beni · Bolivia</strong><small>Personas que mueven nuestra ciudad.</small></div></div>
      </div>
      <div class="footer-bottom section-shell">MOTRIX · Todos los derechos reservados.</div>
    </footer>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const menuOpen = ref(false)
const apkUrl = '/downloads/MOTRIX.apk'

const benefits = [
  { icon: 'two_wheeler', title: 'Solicita tu mototaxi', text: 'Pide tu viaje desde la app estés donde estés en Trinidad.' },
  { icon: 'verified_user', title: 'Viaja con seguridad', text: 'Conductores identificados, viajes registrados y seguimiento.' },
  { icon: 'groups', title: 'Gestión sindical', text: 'Herramientas modernas para sindicatos y federaciones.' },
  { icon: 'payments', title: 'Pagos flexibles', text: 'Efectivo, QR o modalidad mixta según el servicio.' }
]

const steps = [
  { icon: 'phone_android', title: 'Solicita', text: 'Ingresa tu destino y solicita tu mototaxi.' },
  { icon: 'place', title: 'Espera', text: 'Un mototaxista cercano acepta tu viaje.' },
  { icon: 'two_wheeler', title: 'Viaja', text: 'Sigue el estado de tu viaje en tiempo real.' },
  { icon: 'flag', title: 'Llega', text: 'Finaliza y califica tu servicio.' }
]

const cityItems = [
  { icon: 'people', title: 'Pasajeros', text: 'Movilidad segura, rápida y accesible.' },
  { icon: 'two_wheeler', title: 'Mototaxistas', text: 'Más oportunidades y mejor organización.' },
  { icon: 'handshake', title: 'Sindicatos', text: 'Gestión moderna y transparente.' },
  { icon: 'location_city', title: 'Trinidad', text: 'Una ciudad más conectada y en movimiento.' }
]

function irLogin () { menuOpen.value = false; router.push('/login') }
function irRegistro () { menuOpen.value = false; router.push('/registro-pasajero') }
function goTop () { menuOpen.value = false; window.scrollTo({ top: 0, behavior: 'smooth' }) }
function goTo (id) { menuOpen.value = false; document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' }) }
function mobileGo (id) { if (id === 'top') goTop(); else goTo(id) }
async function descargarApk () {
  try {
    const r = await fetch(apkUrl, { method: 'HEAD', cache: 'no-store' })
    if (r.ok) { window.location.href = apkUrl; return }
  } catch (error) {
    console.warn('No se pudo verificar la APK:', error)
  }
  window.alert('La APK oficial de MOTRIX aún está en preparación. Se habilitará aquí cuando esté publicada.')
}
</script>

<style scoped>
:global(html){scroll-behavior:smooth}:global(body){margin:0;background:#fff;color:#10271a;font-family:Inter,Roboto,Arial,sans-serif}.landing-page{--green:#078b42;--green2:#16bd63;--deep:#06321d;--ink:#10271a;--muted:#6e7d74;min-height:100vh;background:#fff}.section-shell{width:min(1180px,calc(100% - 36px));margin:0 auto}.site-header{position:sticky;top:0;z-index:80;border-bottom:1px solid #e5eee7;background:rgba(255,255,255,.97);backdrop-filter:blur(14px)}.header-inner{width:min(1180px,calc(100% - 28px));min-height:76px;margin:0 auto;display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:28px}.brand{display:flex;align-items:center;gap:11px;border:0;background:none;padding:0;text-align:left;color:var(--ink);cursor:pointer}.brand img{width:52px;height:52px;object-fit:contain;border-radius:14px}.brand span{display:grid;line-height:1}.brand strong{font-size:27px;font-weight:900;letter-spacing:-.04em}.brand small{margin-top:5px;color:#278040;font-size:9px;font-weight:900;text-transform:uppercase}.desktop-nav{display:flex;justify-content:center;gap:25px}.desktop-nav button,.footer-links button{border:0;background:none;color:#24392c;font-weight:800;cursor:pointer}.desktop-nav button:hover,.footer-links button:hover{color:var(--green)}.header-actions{display:flex;align-items:center;gap:10px}.city-pill{display:inline-flex;align-items:center;gap:6px;padding:11px 14px;border-radius:999px;background:#eef8f1;color:#176a37;font-size:11px;font-weight:900;white-space:nowrap}.header-actions :deep(.q-btn){min-height:43px;border-radius:13px;font-weight:800}.mobile-menu-btn,.mobile-menu{display:none}.hero{min-height:570px;display:grid;grid-template-columns:minmax(0,48%) minmax(0,52%);background:linear-gradient(135deg,#032c18 0%,#074d2b 100%)}.hero-copy{padding:72px max(32px,calc((100vw - 1180px)/2)) 62px;color:#fff;display:flex;flex-direction:column;justify-content:center}.location-tag{align-self:flex-start;display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border:1px solid rgba(75,235,142,.65);border-radius:999px;background:rgba(8,116,56,.38);font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.05em}.hero h1{max-width:610px;margin:20px 0 14px;font-size:clamp(46px,5.3vw,78px);line-height:.94;letter-spacing:-.055em;font-weight:900}.hero h1 span{color:#38e486}.hero-copy p{max-width:620px;margin:0;color:rgba(255,255,255,.92);font-size:18px;line-height:1.5}.hero-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:28px}.btn{min-height:52px;padding:0 20px;border-radius:14px;border:1px solid transparent;display:inline-flex;align-items:center;justify-content:center;gap:8px;font-weight:900;cursor:pointer}.btn-primary{color:#fff;background:linear-gradient(135deg,#18c765,#078b42);box-shadow:0 12px 28px rgba(0,0,0,.16)}.btn-secondary{color:#fff;border-color:rgba(255,255,255,.75);background:rgba(0,0,0,.18)}.hero-mini{margin-top:17px;color:rgba(255,255,255,.76);font-size:12px}.hero-visual{min-height:570px;background:url('/motrix-hero-visual-final.jpg') center/cover no-repeat}.benefits{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;padding:24px 0}.benefit-card{display:flex;gap:14px;padding:20px;border:1px solid #e1eae3;border-radius:18px;background:#fff;box-shadow:0 10px 26px rgba(11,73,36,.05)}.benefit-icon{flex:0 0 54px;width:54px;height:54px;display:grid;place-items:center;border-radius:50%;background:linear-gradient(135deg,#18c765,#078b42);color:#fff;font-size:27px}.benefit-card h2{margin:2px 0 6px;font-size:16px}.benefit-card p{margin:0;color:#6f7e74;font-size:12px;line-height:1.45}.how{padding:48px 0 56px}.section-heading span,.eyebrow{color:var(--green);font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.12em}.section-heading h2,.download-copy h2,.city-content h2{margin:7px 0 7px;font-size:clamp(32px,3.6vw,48px);line-height:1;letter-spacing:-.045em}.section-heading p,.download-copy p,.city-content>p{margin:0;color:#6d7b73;font-size:15px}.steps{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-top:28px}.step-card{position:relative;text-align:center;padding:18px 12px}.step-number{position:absolute;top:6px;left:calc(50% - 44px);width:28px;height:28px;display:grid;place-items:center;border-radius:50%;background:var(--green);color:#fff;font-weight:900}.step-icon{width:82px;height:82px;margin:0 auto 10px;display:grid;place-items:center;border-radius:50%;background:#eaf8ef;color:var(--green);font-size:42px}.step-card h3{margin:0 0 6px;font-size:17px}.step-card p{margin:0 auto;max-width:190px;color:#718077;font-size:12px;line-height:1.4}.download-wrap{padding-bottom:54px}.download-panel{display:grid;grid-template-columns:1fr auto;gap:28px;align-items:center;padding:34px 38px;border:1px solid #d8ecde;border-radius:24px;background:linear-gradient(135deg,#effbf3,#f8fffa)}.download-actions{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-top:20px}.btn.big{min-height:56px;padding-inline:24px}.play-soon{display:inline-flex;align-items:center;gap:7px;padding:14px 16px;border:1px solid #d6e8dc;border-radius:13px;background:#fff;color:#41614d;font-size:12px;font-weight:800}.trust-row{display:flex;gap:18px;flex-wrap:wrap;margin-top:17px;color:#4d6757;font-size:11px;font-weight:800}.trust-row span{display:inline-flex;align-items:center;gap:5px}.qr-box{width:220px;padding:17px;border:1px solid #dfe9e1;border-radius:20px;background:#fff;text-align:center}.qr-box img{width:150px;height:150px;object-fit:contain}.qr-box strong,.qr-box small{display:block}.qr-box strong{margin-top:8px}.qr-box small{margin-top:4px;color:#76827a}.city-section{position:relative;min-height:470px;background:url('/motrix-trinidad-final.png') center/cover no-repeat;color:#fff}.city-overlay{position:absolute;inset:0;background:linear-gradient(90deg,rgba(2,42,22,.94),rgba(3,54,29,.56) 55%,rgba(4,44,25,.3))}.city-content{position:relative;z-index:2;padding:58px 0}.eyebrow.light{color:#58ed9b}.city-content>p{color:rgba(255,255,255,.86)}.city-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-top:36px}.city-grid article{min-height:130px;padding:22px;display:flex;align-items:center;gap:14px;border:1px solid rgba(81,238,145,.36);border-radius:18px;background:rgba(4,74,38,.72);backdrop-filter:blur(8px)}.city-grid .q-icon{font-size:34px;color:#5cf09e}.city-grid strong,.city-grid small{display:block}.city-grid strong{font-size:17px}.city-grid small{margin-top:7px;color:rgba(255,255,255,.82);font-size:12px;line-height:1.4}.footer{background:#033d24;color:#fff}.footer-inner{padding:30px 0;display:grid;grid-template-columns:1fr 1fr 1fr;align-items:center;gap:24px}.footer-brand{display:flex;align-items:center;gap:12px}.footer-brand img{width:54px;height:54px;border-radius:14px}.footer-brand strong,.footer-brand small{display:block}.footer-brand strong{font-size:25px}.footer-brand small{margin-top:4px;color:#5df09c;font-size:9px;font-weight:900;text-transform:uppercase}.footer-links{display:grid;grid-template-columns:repeat(2,auto);gap:7px 18px}.footer-links button{color:#fff;text-align:left}.footer-city{display:flex;gap:10px;justify-content:flex-end}.footer-city>.q-icon{font-size:26px;color:#58ef9c}.footer-city strong,.footer-city small{display:block}.footer-city small{margin-top:6px;color:rgba(255,255,255,.72)}.footer-bottom{padding:14px 0 20px;border-top:1px solid rgba(255,255,255,.16);color:rgba(255,255,255,.68);font-size:11px}

@media (max-width:1050px){.header-inner{grid-template-columns:auto 1fr auto}.desktop-nav{display:none}.hero{grid-template-columns:1fr 1fr}.benefits{grid-template-columns:repeat(2,1fr)}.city-grid{grid-template-columns:repeat(2,1fr)}.header-actions .city-pill{display:none}}
@media (max-width:820px){.header-inner{min-height:64px;grid-template-columns:1fr auto}.brand img{width:44px;height:44px}.brand strong{font-size:23px}.brand small{font-size:8px}.header-actions{display:none}.mobile-menu-btn{display:grid;width:42px;height:42px;place-items:center;border:1px solid #dce8df;border-radius:12px;background:#fff;color:#17472b;font-size:23px}.mobile-menu{position:fixed;top:68px;left:10px;right:10px;z-index:90;display:grid;gap:6px;padding:12px;border:1px solid #dce8df;border-radius:18px;background:#fff;box-shadow:0 22px 50px rgba(7,62,33,.16)}.mobile-menu button{padding:13px 14px;border:0;border-radius:11px;background:#f3f9f5;color:#18462a;text-align:left;font-weight:850}.mobile-menu .primary{background:var(--green);color:#fff;text-align:center}.hero{display:flex;flex-direction:column;min-height:0}.hero-visual{order:1;min-height:280px;background-position:center}.hero-copy{order:2;padding:28px 18px 34px}.hero h1{font-size:clamp(40px,11vw,56px)}.hero-copy p{font-size:15px}.benefits{grid-template-columns:1fr;padding:16px 0}.how{padding:36px 0 42px}.steps{grid-template-columns:repeat(2,1fr)}.download-panel{grid-template-columns:1fr;padding:26px 22px}.qr-box{width:100%;display:grid;grid-template-columns:auto 1fr;align-items:center;gap:15px;text-align:left}.qr-box img{width:120px;height:120px}.city-grid{grid-template-columns:1fr 1fr}.footer-inner{grid-template-columns:1fr;align-items:start}.footer-city{justify-content:flex-start}.footer-links{grid-template-columns:repeat(2,max-content)}}
@media (max-width:520px){.section-shell{width:min(100% - 22px,1180px)}.hero-visual{min-height:235px;background-position:54% center}.hero-copy{padding:24px 13px 28px}.location-tag{font-size:9px}.hero h1{margin-top:14px;font-size:39px}.hero-copy p{font-size:13px}.hero-actions{display:grid}.btn{width:100%}.benefit-card{padding:15px}.steps{grid-template-columns:1fr}.step-card{display:grid;grid-template-columns:auto 1fr;grid-template-areas:'icon title' 'icon text';text-align:left;gap:2px 12px;padding:10px}.step-number{left:5px;top:5px}.step-icon{grid-area:icon;width:68px;height:68px;margin:0}.step-card h3{grid-area:title;align-self:end}.step-card p{grid-area:text;margin:0;max-width:none}.download-panel{padding:22px 16px}.download-copy h2,.city-content h2,.section-heading h2{font-size:31px}.download-actions{display:grid}.play-soon{justify-content:center}.trust-row{gap:10px}.qr-box{grid-template-columns:1fr;text-align:center}.qr-box img{margin:auto}.city-section{min-height:0}.city-content{padding:42px 0}.city-grid{grid-template-columns:1fr}.city-grid article{min-height:100px}.footer-inner{padding:24px 0}.footer-links{grid-template-columns:1fr 1fr}.footer-bottom{text-align:center}}
</style>
