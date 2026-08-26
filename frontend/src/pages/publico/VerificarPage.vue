<template>
  <div class="verify-page">
    <header class="verify-header">
      <div class="verify-header-inner">
        <div class="row items-center no-wrap">
          <q-avatar
            color="white"
            text-color="green-9"
            icon="two_wheeler"
            size="52px"
            class="q-mr-md shadow-2"
          />

          <div>
            <div class="text-h5 text-weight-bold text-white">MOTRIX</div>
            <div class="text-caption text-green-1">
              Verificación pública de mototaxistas
            </div>
          </div>
        </div>

        <q-chip
          dense
          color="green-9"
          text-color="white"
          icon="verified_user"
          class="gt-xs"
        >
          Verificación oficial
        </q-chip>
      </div>
    </header>

    <main class="verify-content">
      <q-card
        v-if="loading"
        flat
        bordered
        class="verify-card"
      >
        <q-card-section class="column items-center q-pa-xl">
          <q-spinner color="green-8" size="56px" />
          <div class="text-h6 text-grey-8 q-mt-md">
            Verificando mototaxista...
          </div>
          <div class="text-caption text-grey-6 q-mt-xs text-center">
            Estamos consultando el registro oficial de MOTRIX.
          </div>
        </q-card-section>
      </q-card>

      <q-card
        v-else-if="error"
        flat
        bordered
        class="verify-card"
      >
        <q-card-section class="text-center q-pa-xl">
          <q-avatar
            color="red-1"
            text-color="negative"
            icon="gpp_bad"
            size="78px"
          />

          <div class="text-h5 text-weight-bold text-negative q-mt-md">
            No se pudo verificar el QR
          </div>

          <div class="text-body1 text-grey-7 q-mt-sm">
            {{ error }}
          </div>

          <q-btn
            color="green-8"
            icon="refresh"
            label="Intentar nuevamente"
            unelevated
            class="q-mt-lg"
            @click="verificar"
          />
        </q-card-section>
      </q-card>

      <q-card
        v-else-if="datos"
        flat
        bordered
        class="verify-card"
      >
        <div
          class="status-strip"
          :class="datos.habilitado ? 'bg-positive' : 'bg-orange-8'"
        />

        <q-card-section class="q-pa-lg q-pa-xl-md">
          <div class="text-center">
            <q-avatar
              size="118px"
              color="green-1"
              text-color="green-9"
              class="profile-avatar shadow-2"
            >
              <img
                v-if="fotoUrl"
                :src="fotoUrl"
                alt="Fotografía del mototaxista"
                @error="fotoFallida = true"
              >
              <q-icon v-else name="person" size="64px" />
            </q-avatar>

            <div class="text-h5 text-weight-bold text-grey-9 q-mt-md">
              {{ mototaxista.nombre || 'Mototaxista MOTRIX' }}
            </div>

            <q-chip
              :color="datos.habilitado ? 'green-1' : 'orange-1'"
              :text-color="datos.habilitado ? 'green-9' : 'orange-10'"
              :icon="datos.habilitado ? 'verified' : 'warning'"
              class="q-mt-sm text-weight-bold"
            >
              {{
                datos.habilitado
                  ? 'MOTOTAXISTA VERIFICADO'
                  : 'REGISTRO ENCONTRADO'
              }}
            </q-chip>
          </div>

          <q-banner
            rounded
            :class="datos.habilitado
              ? 'bg-green-1 text-green-10'
              : 'bg-orange-1 text-orange-10'"
            class="q-mt-lg"
          >
            <template #avatar>
              <q-icon
                :name="datos.habilitado ? 'verified_user' : 'report_problem'"
                :color="datos.habilitado ? 'green-8' : 'orange-9'"
              />
            </template>
            {{ mensajeEstado }}
          </q-banner>

          <div class="section-title q-mt-lg">
            <q-icon name="badge" color="green-8" size="24px" />
            <span>Datos del mototaxista</span>
          </div>

          <div class="info-grid q-mt-sm">
            <div class="info-item highlight-item">
              <q-icon name="style" color="green-8" size="26px" />
              <div>
                <div class="info-label">N.º de chaleco</div>
                <div class="info-value info-value-big">
                  {{ mototaxista.nro_chaleco || 'No registrado' }}
                </div>
              </div>
            </div>

            <div class="info-item">
              <q-icon name="toggle_on" :color="estadoColor" size="26px" />
              <div>
                <div class="info-label">Estado</div>
                <div class="info-value" :class="`text-${estadoColor}`">
                  {{ mototaxista.estado || 'No registrado' }}
                </div>
              </div>
            </div>

            <div class="info-item">
              <q-icon name="groups" color="green-8" size="24px" />
              <div>
                <div class="info-label">Sindicato</div>
                <div class="info-value">
                  {{ mototaxista.sindicato || 'No registrado' }}
                </div>
              </div>
            </div>

            <div class="info-item">
              <q-icon name="account_tree" color="green-8" size="24px" />
              <div>
                <div class="info-label">Federación</div>
                <div class="info-value">
                  {{ mototaxista.federacion || 'No registrada' }}
                </div>
              </div>
            </div>

            <div class="info-item">
              <q-icon name="fingerprint" color="green-8" size="24px" />
              <div>
                <div class="info-label">CI protegido</div>
                <div class="info-value">
                  {{ mototaxista.ci || 'No registrado' }}
                </div>
              </div>
            </div>

            <div class="info-item">
              <q-icon
                name="manage_accounts"
                :color="mototaxista.cuenta_conductor ? 'positive' : 'grey-6'"
                size="24px"
              />
              <div>
                <div class="info-label">Cuenta MOTRIX</div>
                <div class="info-value">
                  {{
                    mototaxista.cuenta_conductor
                      ? 'Conductor habilitado'
                      : 'Sin cuenta de conductor'
                  }}
                </div>
              </div>
            </div>
          </div>

          <q-separator class="q-my-lg" />

          <div class="section-title">
            <q-icon name="two_wheeler" color="green-8" size="25px" />
            <span>Motocicleta registrada</span>
          </div>

          <div v-if="motocicletas.length" class="column q-gutter-sm q-mt-sm">
            <q-card
              v-for="moto in motocicletas"
              :key="moto.id || `${moto.placa}-${moto.modelo}`"
              flat
              bordered
              class="moto-card"
            >
              <q-card-section class="row items-center no-wrap q-gutter-md">
                <q-avatar
                  color="green-1"
                  text-color="green-9"
                  icon="two_wheeler"
                  size="52px"
                />

                <div class="col min-width-zero">
                  <div class="text-subtitle1 text-weight-bold text-grey-9 ellipsis">
                    {{ moto.modelo || 'Motocicleta registrada' }}
                  </div>
                  <div class="text-body2 text-grey-7">
                    Placa: <strong>{{ moto.placa || 'No registrada' }}</strong>
                  </div>
                  <div v-if="moto.color" class="text-caption text-grey-6">
                    Color: {{ moto.color }}
                  </div>
                </div>

                <q-icon
                  v-if="moto.tiene_soat === true"
                  name="verified"
                  color="positive"
                  size="28px"
                >
                  <q-tooltip>SOAT registrado</q-tooltip>
                </q-icon>
              </q-card-section>
            </q-card>
          </div>

          <q-banner
            v-else
            rounded
            class="bg-grey-2 text-grey-7 q-mt-sm"
          >
            <template #avatar>
              <q-icon name="two_wheeler" />
            </template>
            No hay una motocicleta asociada a este mototaxista.
          </q-banner>

          <q-banner
            rounded
            class="bg-blue-1 text-blue-10 q-mt-lg"
          >
            <template #avatar>
              <q-icon name="privacy_tip" color="blue-8" />
            </template>
            Esta ficha es pública para verificar la identidad del conductor.
            Por seguridad no muestra ubicación en tiempo real, teléfono,
            correo, dirección ni el CI completo.
          </q-banner>

          <div class="text-center text-caption text-grey-6 q-mt-lg">
            Verificación pública emitida por MOTRIX
          </div>
        </q-card-section>
      </q-card>
    </main>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { api } from 'src/boot/axios.js'
import { API_ORIGIN } from 'src/config/runtime.js'

const route = useRoute()

const loading = ref(true)
const error = ref('')
const datos = ref(null)
const fotoFallida = ref(false)

const mototaxista = computed(() => datos.value?.mototaxista || {})
const motocicletas = computed(() => (
  Array.isArray(datos.value?.motocicletas)
    ? datos.value.motocicletas
    : []
))

const estadoColor = computed(() => (
  mototaxista.value?.estado === 'Activo'
    ? 'positive'
    : 'orange-9'
))

const mensajeEstado = computed(() => {
  if (datos.value?.habilitado) {
    return 'El código QR corresponde a un mototaxista activo y habilitado en MOTRIX.'
  }

  if (mototaxista.value?.estado && mototaxista.value.estado !== 'Activo') {
    return `El registro existe, pero actualmente figura como ${mototaxista.value.estado}.`
  }

  if (!mototaxista.value?.cuenta_conductor) {
    return 'El registro existe, pero todavía no tiene una cuenta de conductor habilitada.'
  }

  return 'El registro fue encontrado en MOTRIX.'
})

function apiOrigen() {
  try {
    return new URL(api.defaults.baseURL).origin
  } catch {
    return API_ORIGIN
  }
}

const fotoUrl = computed(() => {
  if (fotoFallida.value) return ''

  const ruta = String(mototaxista.value?.foto_ruta || '').trim()
  if (!ruta) return ''

  if (/^https?:\/\//i.test(ruta)) return ruta

  return `${apiOrigen()}/storage/${ruta.replace(/^\/+/, '')}`
})

async function verificar() {
  loading.value = true
  error.value = ''
  datos.value = null
  fotoFallida.value = false

  const codigo = String(route.params.codigo || '').trim()

  if (!codigo) {
    error.value = 'El enlace no contiene un código de verificación.'
    loading.value = false
    return
  }

  try {
    const respuesta = await api.get(
      `/verificar/${encodeURIComponent(codigo)}`,
      { timeout: 15000 }
    )

    if (!respuesta.data?.verificado) {
      throw new Error(
        respuesta.data?.mensaje || 'El código QR no corresponde a un mototaxista registrado.'
      )
    }

    datos.value = respuesta.data
  } catch (err) {
    console.error('Error verificando QR MOTRIX:', err)

    error.value =
      err.response?.data?.mensaje
      || err.message
      || 'No fue posible consultar el registro. Intenta nuevamente.'
  } finally {
    loading.value = false
  }
}

onMounted(verificar)
</script>

<style scoped>
.verify-page {
  min-height: 100vh;
  background:
    radial-gradient(circle at 92% 5%, rgba(76, 175, 80, 0.16), transparent 30%),
    #f1f8e9;
  color: #263238;
}

.verify-header {
  position: sticky;
  top: 0;
  z-index: 5;
  background: linear-gradient(135deg, #1b5e20, #2e7d32);
  border-bottom: 4px solid #c62828;
  box-shadow: 0 5px 18px rgba(0, 0, 0, 0.18);
}

.verify-header-inner {
  width: min(980px, 100%);
  min-height: 82px;
  margin: 0 auto;
  padding: 14px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.verify-content {
  width: min(760px, calc(100% - 28px));
  margin: 0 auto;
  padding: 30px 0 48px;
}

.verify-card {
  overflow: hidden;
  border-color: #cfe0cc;
  border-radius: 22px;
  box-shadow: 0 16px 38px rgba(27, 94, 32, 0.12);
  background: #fff;
}

.status-strip {
  height: 7px;
}

.profile-avatar {
  border: 4px solid #fff;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 1rem;
  font-weight: 700;
  color: #1b5e20;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.info-item {
  min-height: 76px;
  padding: 13px 14px;
  border: 1px solid #e2ebe0;
  border-radius: 14px;
  background: #fafcf9;
  display: flex;
  align-items: center;
  gap: 12px;
}

.highlight-item {
  background: #f1f8e9;
  border-color: #c5e1a5;
}

.info-label {
  color: #78909c;
  font-size: 0.78rem;
}

.info-value {
  color: #263238;
  font-size: 0.95rem;
  font-weight: 700;
  overflow-wrap: anywhere;
}

.info-value-big {
  color: #1b5e20;
  font-size: 1.2rem;
}

.moto-card {
  border-radius: 14px;
  border-color: #dce8da;
  background: #fcfefb;
}

.min-width-zero {
  min-width: 0;
}

@media (max-width: 599px) {
  .verify-header-inner {
    min-height: 74px;
    padding: 12px 15px;
  }

  .verify-content {
    width: min(100% - 20px, 760px);
    padding-top: 18px;
  }

  .verify-card {
    border-radius: 18px;
  }

  .info-grid {
    grid-template-columns: 1fr;
  }
}
</style>
