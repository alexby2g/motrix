<template>
  <q-page class="q-pa-md q-pa-lg-lg registro-page">
    <div class="registro-shell">
      <div class="hero-saludo q-mb-lg">
        <div class="hero-contenido">
          <div class="hero-kicker">MÓDULO DE REGISTRO</div>
          <div class="text-h4 text-weight-bolder hero-title">
            ¡Hola, {{ nombreUsuario }}!
          </div>
          <div class="text-body2 q-mt-xs hero-subtitle">
            {{ fechaHoy }}
          </div>
        </div>
      </div>

      <div class="row q-col-gutter-md q-mb-lg">
        <div class="col-12 col-sm-6 col-md-3">
          <q-card flat bordered class="card-stat">
            <q-card-section class="row items-center no-wrap">
              <q-avatar color="green-1" text-color="green-9" icon="groups" size="42px" class="q-mr-sm" />
              <div>
                <div class="text-caption text-grey-7">Sindicatos</div>
                <div class="text-h6 text-weight-bold text-green-9">
                  <q-skeleton v-if="cargando" type="text" width="30px" />
                  <span v-else>{{ sindicatos.length }}</span>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <q-card flat bordered class="card-stat">
            <q-card-section class="row items-center no-wrap">
              <q-avatar color="green-1" text-color="green-9" icon="two_wheeler" size="42px" class="q-mr-sm" />
              <div>
                <div class="text-caption text-grey-7">Mototaxistas activos</div>
                <div class="text-h6 text-weight-bold text-green-9">
                  <q-skeleton v-if="cargando" type="text" width="30px" />
                  <span v-else>{{ mototaxistasActivos }}</span>
                </div>
                <div v-if="!cargando" class="text-caption text-grey-6">
                  de {{ mototaxistas.length }} registrados
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <q-card flat bordered class="card-stat">
            <q-card-section class="row items-center no-wrap">
              <q-avatar color="grey-2" text-color="green-8" icon="two_wheeler" size="42px" class="q-mr-sm" />
              <div>
                <div class="text-caption text-grey-7">Motocicletas</div>
                <div class="text-h6 text-weight-bold text-green-9">
                  <q-skeleton v-if="cargando" type="text" width="30px" />
                  <span v-else>{{ motocicletas.length }}</span>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <q-card flat bordered class="card-stat">
            <q-card-section class="row items-center no-wrap">
              <q-avatar color="green-1" text-color="green-9" icon="payments" size="42px" class="q-mr-sm" />
              <div>
                <div class="text-caption text-grey-7">Recaudación sindical</div>
                <div class="text-h6 text-weight-bold text-green-9">
                  <q-skeleton v-if="cargando" type="text" width="75px" />
                  <span v-else>Bs. {{ totalRecaudado.toFixed(2) }}</span>
                </div>
                <div v-if="!cargando && pagosPendientes > 0" class="text-caption text-red-7">
                  {{ pagosPendientes }} pendiente{{ pagosPendientes === 1 ? '' : 's' }}
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <div class="row q-col-gutter-md">
        <div class="col-12 col-md-6">
          <q-card flat bordered class="panel-card">
            <q-card-section class="row items-center q-pb-none">
              <q-icon name="receipt_long" color="green-8" class="q-mr-sm" />
              <span class="text-subtitle1 text-weight-bold panel-title">Últimos pagos sindicales</span>
              <q-space />
              <q-btn flat dense no-caps color="green-8" label="Ver todos" to="/pagos-sindicales" />
            </q-card-section>

            <q-list separator>
              <template v-if="cargando">
                <q-item v-for="n in 4" :key="n">
                  <q-item-section>
                    <q-skeleton type="text" width="60%" />
                    <q-skeleton type="text" width="40%" />
                  </q-item-section>
                </q-item>
              </template>

              <template v-else>
                <q-item v-for="pago in ultimosPagos" :key="pago.id">
                  <q-item-section avatar>
                    <q-avatar size="34px" color="green-2" text-color="green-9">
                      {{ inicialesPago(pago) }}
                    </q-avatar>
                  </q-item-section>

                  <q-item-section>
                    <q-item-label>{{ nombreMototaxistaPago(pago) }}</q-item-label>
                    <q-item-label caption>
                      {{ pago.tipo_pago || 'Pago sindical' }} · {{ formatearFecha(pago.fecha) }}
                    </q-item-label>
                  </q-item-section>

                  <q-item-section side>
                    <div class="text-weight-bold text-green-8">
                      Bs. {{ Number(pago.monto || 0).toFixed(2) }}
                    </div>
                  </q-item-section>
                </q-item>

                <div v-if="ultimosPagos.length === 0" class="text-center text-grey-7 q-pa-lg">
                  Aún no existen pagos sindicales registrados.
                </div>
              </template>
            </q-list>
          </q-card>
        </div>

        <div class="col-12 col-md-6">
          <q-card flat bordered class="panel-card">
            <q-card-section class="row items-center">
              <q-icon name="bolt" color="green-8" class="q-mr-sm" />
              <span class="text-subtitle1 text-weight-bold panel-title">Accesos rápidos</span>
            </q-card-section>

            <q-card-section class="q-gutter-sm q-pt-none">
              <q-btn class="full-width" align="left" unelevated color="green-8" icon="account_tree" label="Federaciones" to="/federaciones" no-caps />
              <q-btn class="full-width" align="left" unelevated color="green-8" icon="groups" label="Sindicatos" to="/sindicatos" no-caps />
              <q-btn class="full-width" align="left" outline color="green-8" icon="badge" label="Personas" to="/personas" no-caps />
              <q-btn class="full-width" align="left" outline color="green-8" icon="people" label="Mototaxistas" to="/mototaxistas" no-caps />
              <q-btn class="full-width" align="left" outline color="green-8" icon="two_wheeler" label="Motocicletas" to="/motocicletas" no-caps />
              <q-btn class="full-width" align="left" outline color="green-8" icon="payments" label="Pagos sindicales" to="/pagos-sindicales" no-caps />
            </q-card-section>
          </q-card>
        </div>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useQuasar } from 'quasar'
import { api } from 'src/boot/axios.js'

const $q = useQuasar()
const cargando = ref(false)
const sindicatos = ref([])
const mototaxistas = ref([])
const motocicletas = ref([])
const pagos = ref([])

const usuario = computed(() => {
  try {
    return JSON.parse(localStorage.getItem('motrix_user') || 'null')
  } catch {
    return null
  }
})

const nombreUsuario = computed(() => (
  usuario.value?.persona_nombre
  || usuario.value?.name
  || usuario.value?.nickname
  || 'Administrador de registro'
))

const fechaHoy = computed(() => new Date().toLocaleDateString('es-BO', {
  weekday: 'long',
  day: 'numeric',
  month: 'long',
  year: 'numeric'
}))

const mototaxistasActivos = computed(() => (
  mototaxistas.value.filter(m => String(m.estado || '').toLowerCase() === 'activo').length
))

const totalRecaudado = computed(() => pagos.value
  .filter(p => String(p.estado_pago || '').toLowerCase() === 'pagado')
  .reduce((total, p) => total + Number(p.monto || 0), 0))

const pagosPendientes = computed(() => pagos.value
  .filter(p => String(p.estado_pago || '').toLowerCase() === 'pendiente').length)

const ultimosPagos = computed(() => [...pagos.value]
  .sort((a, b) => Number(b.id || 0) - Number(a.id || 0))
  .slice(0, 5))

function nombreMototaxistaPago(pago) {
  const persona = pago?.mototaxista?.persona
  return `${persona?.nombre || ''} ${persona?.apellidos || ''}`.trim() || 'Mototaxista'
}

function inicialesPago(pago) {
  const partes = nombreMototaxistaPago(pago).split(/\s+/).filter(Boolean)
  return ((partes[0]?.[0] || '') + (partes[1]?.[0] || '')).toUpperCase() || 'M'
}

function formatearFecha(fecha) {
  if (!fecha) return 'Sin fecha'
  const valor = new Date(`${fecha}T00:00:00`)
  if (Number.isNaN(valor.getTime())) return fecha
  return valor.toLocaleDateString('es-BO', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}

async function cargarDatos() {
  cargando.value = true
  try {
    const [resSindicatos, resMototaxistas, resMotocicletas, resPagos] = await Promise.all([
      api.get('/sindicatos'),
      api.get('/mototaxistas'),
      api.get('/motocicletas'),
      api.get('/pagos-sindicales')
    ])

    sindicatos.value = Array.isArray(resSindicatos.data) ? resSindicatos.data : []
    mototaxistas.value = Array.isArray(resMototaxistas.data) ? resMototaxistas.data : []
    motocicletas.value = Array.isArray(resMotocicletas.data) ? resMotocicletas.data : []
    pagos.value = Array.isArray(resPagos.data) ? resPagos.data : []
  } catch (error) {
    console.error('Error al cargar el módulo de registro:', error)
    $q.notify({
      type: 'negative',
      position: 'top',
      message: error.response?.data?.message || 'No se pudo cargar el resumen del módulo de registro.'
    })
  } finally {
    cargando.value = false
  }
}

onMounted(cargarDatos)
</script>

<style scoped>
.registro-page {
  min-height: 100%;
  background: #f5f6f7;
}

.registro-shell {
  max-width: 1180px;
  margin: 0 auto;
}

.hero-saludo {
  padding: 8px 4px 4px;
  color: #30353b;
  background: transparent;
}

.hero-kicker {
  margin-bottom: 4px;
  color: #2e7d32;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.09em;
}

.hero-title {
  color: #202623;
  line-height: 1.12;
}

.hero-subtitle {
  color: #707780;
  text-transform: capitalize;
}

.card-stat,
.panel-card {
  border: 1px solid #dfe3e6;
  border-radius: 14px;
  background: #ffffff;
  box-shadow: 0 5px 18px rgba(34, 47, 40, 0.055);
}

.card-stat {
  min-height: 92px;
  transition: box-shadow 0.18s ease, transform 0.18s ease;
}

.card-stat:hover {
  transform: translateY(-1px);
  box-shadow: 0 8px 22px rgba(34, 47, 40, 0.085);
}

.panel-card {
  height: 100%;
  overflow: hidden;
}

.panel-title {
  color: #30353b;
}

:deep(.panel-card .q-item) {
  min-height: 62px;
}

:deep(.panel-card .q-btn) {
  border-radius: 9px;
}

@media (max-width: 599px) {
  .registro-page {
    padding: 14px;
  }

  .hero-saludo {
    padding-top: 2px;
  }

  .hero-title {
    font-size: 28px;
  }
}
</style>
