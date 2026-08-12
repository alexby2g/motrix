<template>
  <q-page class="native-qr-page q-pa-md">
    <div class="row justify-center">
      <div class="col-12 col-sm-8 col-md-6">
        <q-card class="native-qr-card shadow-3">
          <q-card-section class="native-qr-header text-white">
            <div class="row items-center no-wrap">
              <q-btn
                flat
                round
                icon="arrow_back"
                color="white"
                class="q-mr-sm"
                @click="volver"
              />

              <q-avatar
                color="white"
                text-color="green-8"
                icon="qr_code_scanner"
                size="52px"
                class="q-mr-md"
              />

              <div class="col min-width-zero">
                <div class="text-h5 text-weight-bold">
                  Escanear QR
                </div>
                <div class="text-caption text-green-1">
                  Lector nativo MOTRIX
                </div>
              </div>
            </div>
          </q-card-section>

          <q-card-section class="q-pa-lg">
            <div class="scanner-hero">
              <q-icon
                name="qr_code_2"
                size="118px"
                color="green-8"
              />

              <div class="text-h6 text-weight-bold q-mt-md text-center">
                Verifica al mototaxista desde la cámara
              </div>

              <div class="text-body2 text-grey-7 text-center q-mt-sm">
                MOTRIX abrirá el lector del teléfono y buscará únicamente
                códigos QR.
              </div>
            </div>

            <q-btn
              color="green-8"
              icon="qr_code_scanner"
              label="Abrir cámara y escanear"
              class="full-width q-mt-lg scan-button"
              unelevated
              no-caps
              :loading="escaneando"
              @click="escanear"
            />

            <q-banner
              v-if="mensaje"
              rounded
              class="q-mt-md"
              :class="error
                ? 'bg-orange-1 text-orange-10'
                : 'bg-green-1 text-green-10'"
            >
              <template #avatar>
                <q-icon
                  :name="error ? 'warning' : 'info'"
                  :color="error ? 'orange-9' : 'green-8'"
                />
              </template>
              {{ mensaje }}
            </q-banner>

            <q-separator class="q-my-lg" />

            <div class="text-subtitle2 text-weight-bold text-grey-9">
              Ingresar código manualmente
            </div>

            <div class="text-caption text-grey-6 q-mb-sm">
              También puedes pegar el código o el enlace completo del QR.
            </div>

            <q-input
              v-model.trim="codigoManual"
              outlined
              clearable
              label="Código o enlace MOTRIX"
              @keyup.enter="verificarManual"
            >
              <template #prepend>
                <q-icon name="qr_code_2" color="green-8" />
              </template>
            </q-input>

            <q-btn
              outline
              color="green-8"
              icon="verified"
              label="Verificar código"
              class="full-width q-mt-sm"
              no-caps
              :disable="!codigoManual"
              @click="verificarManual"
            />

            <q-banner
              rounded
              class="privacy-note q-mt-lg"
            >
              <template #avatar>
                <q-icon name="privacy_tip" color="green-8" />
              </template>
              La cámara se abre solo durante el escaneo y se cierra al
              detectar el código o cancelar.
            </q-banner>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'

const $q = useQuasar()
const router = useRouter()

const escaneando = ref(false)
const mensaje = ref('')
const error = ref(false)
const codigoManual = ref('')

function volver() {
  router.push('/pasajero')
}

function extraerCodigo(valor) {
  const texto = String(valor || '').trim()
  if (!texto) return ''

  const coincidenciaRuta = texto.match(
    /(?:#\/|\/)verificar\/([^/?#\s]+)/i
  )

  return coincidenciaRuta?.[1]
    ? decodeURIComponent(coincidenciaRuta[1])
    : texto
}

async function abrirVerificacion(valor) {
  const codigo = extraerCodigo(valor)

  if (!codigo) {
    mensaje.value = 'No se encontró un código válido para verificar.'
    error.value = true
    return
  }

  await router.push(`/verificar/${encodeURIComponent(codigo)}`)
}

async function escanear() {
  if (escaneando.value) return

  escaneando.value = true
  mensaje.value = ''
  error.value = false

  try {
    if (typeof window.__MOTRIX_NATIVE_SCAN_QR__ !== 'function') {
      throw new Error('El lector QR nativo no está disponible.')
    }

    const valor = await window.__MOTRIX_NATIVE_SCAN_QR__()

    if (!valor) {
      mensaje.value = 'Escaneo cancelado o sin resultado.'
      return
    }

    if (navigator.vibrate) {
      navigator.vibrate(120)
    }

    $q.notify({
      type: 'positive',
      icon: 'qr_code_scanner',
      position: 'top',
      message: 'QR detectado. Verificando...'
    })

    await abrirVerificacion(valor)
  } catch (err) {
    console.error('Error en lector QR nativo:', err)
    error.value = true
    mensaje.value = err?.message || 'No fue posible abrir el lector QR.'
  } finally {
    escaneando.value = false
  }
}

function verificarManual() {
  abrirVerificacion(codigoManual.value)
}
</script>

<style scoped>
.native-qr-page {
  min-height: 100%;
  background: transparent;
}

.native-qr-card {
  overflow: hidden;
  border-radius: 18px;
}

.native-qr-header {
  background: linear-gradient(135deg, #1b5e20, #2e7d32);
}

.scanner-hero {
  min-height: 285px;
  padding: 28px 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border: 1px solid #d6e7d3;
  border-radius: 18px;
  background: linear-gradient(145deg, #edf7eb, #fbfdfb);
}

.scan-button {
  min-height: 52px;
  border-radius: 12px;
  font-weight: 800;
}

.privacy-note {
  color: #365239;
  background: #f0f7ef;
  border: 1px solid #d7e5d4;
}

.min-width-zero {
  min-width: 0;
}

@media (max-width: 599px) {
  .native-qr-page {
    padding: 9px 9px 22px;
  }

  .native-qr-card {
    border-radius: 14px;
  }

  .scanner-hero {
    min-height: 245px;
  }
}
</style>
