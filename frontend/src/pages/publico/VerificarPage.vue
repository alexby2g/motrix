<template>
  <q-page class="verify-page">
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
            <div class="text-h5 text-weight-bold text-white">
              MOTRIX
            </div>

            <div class="text-caption text-green-1">
              Verificación pública de mototaxistas
            </div>
          </div>
        </div>

        <div class="text-caption text-green-2 gt-xs">
          Trinidad · Beni
        </div>
      </div>
    </header>

    <main class="verify-content">
      <div
        v-if="loading"
        class="column items-center q-pa-xl"
      >
        <q-spinner
          color="green-8"
          size="54px"
        />

        <div class="text-subtitle1 text-grey-7 q-mt-md">
          Verificando código QR...
        </div>
      </div>

      <q-card
        v-else-if="error"
        flat
        bordered
        class="verify-card error-card"
      >
        <q-card-section class="text-center q-pa-xl">
          <q-avatar
            color="red-1"
            text-color="negative"
            icon="gpp_bad"
            size="76px"
          />

          <div class="text-h5 text-weight-bold text-negative q-mt-md">
            Código no válido
          </div>

          <div class="text-body1 text-grey-7 q-mt-sm">
            {{ error }}
          </div>

          <q-btn
            color="green-8"
            icon="home"
            label="Ir a MOTRIX"
            class="q-mt-lg"
            unelevated
            @click="irInicio"
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
          :class="
            datos.habilitado
              ? 'bg-positive'
              : 'bg-orange-8'
          "
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
              >

              <q-icon
                v-else
                name="person"
                size="62px"
              />
            </q-avatar>

            <div
              class="text-h5 text-weight-bold text-grey-9 q-mt-md"
            >
              {{ mototaxista.nombre || 'Mototaxista MOTRIX' }}
            </div>

            <q-chip
              :color="
                datos.habilitado
                  ? 'green-1'
                  : 'orange-1'
              "
              :text-color="
                datos.habilitado
                  ? 'green-9'
                  : 'orange-10'
              "
              :icon="
                datos.habilitado
                  ? 'verified'
                  : 'warning'
              "
              class="q-mt-sm text-weight-bold"
            >
              {{
                datos.habilitado
                  ? 'MOTOTAXISTA VERIFICADO'
                  : 'REGISTRO ENCONTRADO · NO HABILITADO'
              }}
            </q-chip>
          </div>

          <q-banner
            rounded
            :class="
              datos.habilitado
                ? 'bg-green-1 text-green-10'
                : 'bg-orange-1 text-orange-10'
            "
            class="q-mt-lg"
          >
            <template #avatar>
              <q-icon
                :name="
                  datos.habilitado
                    ? 'verified_user'
                    : 'report_problem'
                "
                :color="
                  datos.habilitado
                    ? 'green-8'
                    : 'orange-9'
                "
              />
            </template>

            {{
              datos.habilitado
                ? 'El código pertenece a un mototaxista Activo y con cuenta de conductor en MOTRIX.'
                : mensajeEstado
            }}
          </q-banner>

          <q-separator class="q-my-lg" />

          <div class="text-subtitle1 text-weight-bold text-green-9 q-mb-sm">
            Datos de identificación
          </div>

          <div class="info-grid">
            <div class="info-item">
              <q-icon
                name="style"
                color="green-8"
                size="22px"
              />
              <div>
                <div class="info-label">N.º de chaleco</div>
                <div class="info-value">
                  {{ mototaxista.nro_chaleco || 'No registrado' }}
                </div>
              </div>
            </div>

            <div class="info-item">
              <q-icon
                name="fingerprint"
                color="green-8"
                size="22px"
              />
              <div>
                <div class="info-label">CI protegido</div>
                <div class="info-value">
                  {{ mototaxista.ci || 'No registrado' }}
                </div>
              </div>
            </div>

            <div class="info-item">
              <q-icon
                name="groups"
                color="green-8"
                size="22px"
              />
              <div>
                <div class="info-label">Sindicato</div>
                <div class="info-value">
                  {{ mototaxista.sindicato || 'No registrado' }}
                </div>
              </div>
            </div>

            <div class="info-item">
              <q-icon
                name="account_tree"
                color="green-8"
                size="22px"
              />
              <div>
                <div class="info-label">Federación</div>
                <div class="info-value">
                  {{ mototaxista.federacion || 'No registrada' }}
                </div>
              </div>
            </div>

            <div class="info-item">
              <q-icon
                name="toggle_on"
                :color="
                  mototaxista.estado === 'Activo'
                    ? 'positive'
                    : 'negative'
                "
                size="22px"
              />
              <div>
                <div class="info-label">Estado</div>
                <div
                  class="info-value"
                  :class="
                    mototaxista.estado === 'Activo'
                      ? 'text-positive'
                      : 'text-negative'
                  "
                >
                  {{ mototaxista.estado || 'No registrado' }}
                </div>
              </div>
            </div>

            <div class="info-item">
              <q-icon
                name="manage_accounts"
                :color="
                  mototaxista.cuenta_conductor
                    ? 'positive'
                    : 'grey-6'
                "
                size="22px"
              />
              <div>
                <div class="info-label">Cuenta MOTRIX</div>
                <div class="info-value">
                  {{
                    mototaxista.cuenta_conductor
                      ? 'Habilitada como conductor'
                      : 'No habilitada'
                  }}
                </div>
              </div>
            </div>
          </div>

          <q-separator class="q-my-lg" />

          <div class="row items-center q-mb-sm">
            <q-icon
              name="two_wheeler"
              color="green-8"
              size="24px"
              class="q-mr-sm"
            />
            <div class="text-subtitle1 text-weight-bold text-green-9">
              Motocicleta registrada
            </div>
          </div>

          <div
            v-if="motocicletas.length"
            class="column q-gutter-sm"
          >
            <q-card
              v-for="moto in motocicletas"
              :key="moto.id"
              flat
              bordered
              class="moto-card"
            >
              <q-card-section class="row items-center q-col-gutter-md">
                <div class="col-auto">
                  <q-avatar
                    color="green-1"
                    text-color="green-9"
                    icon="two_wheeler"
                    size="48px"
                  />
                </div>

                <div class="col">
                  <div class="text-weight-bold text-grey-9">
                    {{
                      moto.modelo
                      || 'Motocicleta registrada'
                    }}
                  </div>

                  <div class="text-caption text-grey-7">
                    Placa:
                    <strong>
                      {{ moto.placa || 'No registrada' }}
                    </strong>
                    <span v-if="moto.color">
                      · Color: {{ moto.color }}
                    </span>
                  </div>
                </div>

                <div class="col-auto">
                  <q-chip
                    v-if="moto.tiene_soat === true"
                    color="green-1"
                    text-color="green-9"
                    icon="verified"
                    dense
                  >
                    SOAT registrado
                  </q-chip>

                  <q-chip
                    v-else-if="moto.tiene_soat === false"
                    color="red-1"
                    text-color="red-9"
                    icon="warning"
                    dense
                  >
                    Sin SOAT
                  </q-chip>

                  <q-chip
                    v-else
                    color="grey-2"
                    text-color="grey-7"
                    icon="help_outline"
                    dense
                  >
                    SOAT sin dato
                  </q-chip>
                </div>
              </q-card-section>
            </q-card>
          </div>

          <q-banner
            v-else
            rounded
            class="bg-grey-2 text-grey-7"
          >
            <template #avatar>
              <q-icon name="two_wheeler" />
            </template>

            No hay una motocicleta asociada a este registro.
          </q-banner>

          <q-separator class="q-my-lg" />

          <q-banner
            rounded
            class="bg-blue-1 text-blue-10 privacy-banner"
          >
            <template #avatar>
              <q-icon
                name="privacy_tip"
                color="blue-8"
              />
            </template>

            Por seguridad, esta página no publica la ubicación en
            tiempo real, teléfono, correo, dirección particular ni
            el número completo de CI del conductor.
          </q-banner>

          <div class="text-center text-caption text-grey-6 q-mt-lg">
            Verificación emitida por MOTRIX · Trinidad, Beni
          </div>
        </q-card-section>
      </q-card>
    </main>
  </q-page>
</template>

<script setup>
import {
  computed,
  onMounted,
  ref
} from 'vue'

import {
  useRoute,
  useRouter
} from 'vue-router'

import { api } from 'src/boot/axios.js'
import { API_ORIGIN } from 'src/config/runtime.js'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const error = ref('')
const datos = ref(null)

const mototaxista = computed(
  () => datos.value?.mototaxista || {}
)

const motocicletas = computed(
  () =>
    Array.isArray(datos.value?.motocicletas)
      ? datos.value.motocicletas
      : []
)

function apiOrigen() {
  try {
    return new URL(
      api.defaults.baseURL
    ).origin
  } catch {
    return API_ORIGIN
  }
}

const fotoUrl = computed(() => {
  const ruta =
    mototaxista.value?.foto_ruta

  if (!ruta) return ''

  if (/^https?:\/\//i.test(ruta)) {
    return ruta
  }

  return (
    `${apiOrigen()}/storage/`
    + String(ruta)
      .replace(/^\/+/, '')
  )
})

const mensajeEstado = computed(() => {
  if (
    mototaxista.value.estado
    !== 'Activo'
  ) {
    return (
      'El registro existe, pero actualmente figura como '
      + (
        mototaxista.value.estado
        || 'Inactivo'
      )
      + '.'
    )
  }

  if (
    !mototaxista.value
      .cuenta_conductor
  ) {
    return (
      'El registro está Activo, pero todavía no tiene una '
      + 'cuenta de conductor habilitada en MOTRIX.'
    )
  }

  return 'Registro encontrado.'
})

async function verificar() {
  loading.value = true
  error.value = ''
  datos.value = null

  const codigo = String(
    route.params.codigo || ''
  ).trim()

  if (!codigo) {
    error.value =
      'No se recibió un código de verificación.'

    loading.value = false
    return
  }

  try {
    const respuesta = await api.get(
      `/verificar/${encodeURIComponent(codigo)}`
    )

    datos.value = respuesta.data
  } catch (err) {
    error.value =
      err.response?.data?.mensaje
      || 'No fue posible verificar este código QR.'
  } finally {
    loading.value = false
  }
}

function irInicio() {
  router.push('/login')
}

onMounted(
  verificar
)
</script>

<style scoped>
.verify-page {
  min-height: 100vh;
  background:
    radial-gradient(
      circle at 90% 5%,
      rgba(102, 187, 106, 0.16),
      transparent 30%
    ),
    #f1f8e9;
}

.verify-header {
  background:
    linear-gradient(
      135deg,
      #1b5e20,
      #2e7d32
    );
  border-bottom: 4px solid #c62828;
  box-shadow:
    0 5px 18px rgba(0, 0, 0, 0.18);
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
  padding: 32px 0 50px;
}

.verify-card {
  position: relative;
  overflow: hidden;
  border-color: #cfe0cc;
  border-radius: 20px;
  box-shadow:
    0 16px 38px rgba(27, 94, 32, 0.12);
}

.status-strip {
  height: 7px;
}

.profile-avatar {
  border: 4px solid white;
}

.info-grid {
  display: grid;
  grid-template-columns:
    repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 12px;
  min-height: 72px;
  padding: 13px 14px;
  background: #fafcf9;
  border: 1px solid #dce9d9;
  border-radius: 12px;
}

.info-label {
  color: #7a8577;
  font-size: 12px;
}

.info-value {
  color: #293329;
  font-weight: 700;
  overflow-wrap: anywhere;
}

.moto-card {
  border-color: #dce9d9;
  border-radius: 12px;
}

.privacy-banner {
  line-height: 1.55;
}

.error-card {
  border-color: #efcaca;
}

@media (max-width: 599px) {
  .verify-header-inner {
    min-height: 72px;
    padding: 10px 14px;
  }

  .verify-content {
    width: calc(100% - 18px);
    padding-top: 18px;
  }

  .verify-card {
    border-radius: 14px;
  }

  .info-grid {
    grid-template-columns: 1fr;
  }
}
</style>
