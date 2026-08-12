<template>
  <q-page class="scanner-page q-pa-md q-pa-lg-md">
    <div class="row justify-center">
      <div class="col-12 col-md-9 col-lg-7">
        <q-card class="scanner-card shadow-2">
          <q-card-section class="scanner-header text-white">
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
                  Verificación de mototaxistas MOTRIX
                </div>
              </div>
            </div>
          </q-card-section>

          <q-card-section class="q-pa-md q-pa-lg-md">
            <q-banner
              rounded
              class="bg-green-1 text-green-10 q-mb-md"
            >
              <template #avatar>
                <q-icon
                  name="verified_user"
                  color="green-8"
                  size="30px"
                />
              </template>

              Escanea el QR del mototaxista para consultar su ficha
              de verificación registrada en MOTRIX.
            </q-banner>

            <div class="camera-box">
              <video
                ref="videoRef"
                autoplay
                muted
                playsinline
                class="camera-video"
              />

              <div
                v-if="!camaraActiva"
                class="camera-placeholder"
              >
                <q-icon
                  name="photo_camera"
                  size="72px"
                  color="green-7"
                />

                <div class="text-h6 text-weight-bold q-mt-md">
                  Cámara detenida
                </div>

                <div class="text-body2 text-grey-6 text-center q-mt-xs">
                  Presiona el botón para permitir el acceso a la cámara.
                </div>
              </div>

              <div
                v-if="camaraActiva"
                class="scan-frame"
              >
                <span class="corner corner-a" />
                <span class="corner corner-b" />
                <span class="corner corner-c" />
                <span class="corner corner-d" />

                <div class="scan-line" />
              </div>

              <q-chip
                v-if="camaraActiva"
                color="green-9"
                text-color="white"
                icon="sensors"
                dense
                class="camera-status"
              >
                Buscando QR
              </q-chip>
            </div>

            <q-banner
              v-if="mensajeCamara"
              rounded
              class="q-mt-md"
              :class="
                errorCamara
                  ? 'bg-orange-1 text-orange-10'
                  : 'bg-grey-2 text-grey-8'
              "
            >
              <template #avatar>
                <q-icon
                  :name="
                    errorCamara
                      ? 'warning'
                      : 'info'
                  "
                  :color="
                    errorCamara
                      ? 'orange-9'
                      : 'grey-7'
                  "
              />
              </template>

              {{ mensajeCamara }}
            </q-banner>

            <div class="row q-col-gutter-sm q-mt-sm">
              <div class="col-12 col-sm-6">
                <q-btn
                  v-if="!camaraActiva"
                  color="green-8"
                  icon="photo_camera"
                  label="Activar cámara"
                  class="full-width"
                  unelevated
                  no-caps
                  :loading="iniciandoCamara"
                  @click="iniciarCamara"
                />

                <q-btn
                  v-else
                  outline
                  color="negative"
                  icon="videocam_off"
                  label="Detener cámara"
                  class="full-width"
                  no-caps
                  @click="detenerCamara"
                />
              </div>

              <div class="col-12 col-sm-6">
                <q-btn
                  outline
                  color="green-8"
                  icon="flip_camera_android"
                  label="Cambiar cámara"
                  class="full-width"
                  no-caps
                  :disable="!camaraActiva"
                  @click="cambiarCamara"
                />
              </div>
            </div>

            <q-separator class="q-my-lg" />

            <div class="text-subtitle2 text-weight-bold text-grey-9">
              Ingresar código manualmente
            </div>

            <div class="text-caption text-grey-6 q-mb-sm">
              También puedes pegar el código o el enlace completo del QR.
            </div>

            <div class="row q-col-gutter-sm">
              <div class="col">
                <q-input
                  v-model.trim="codigoManual"
                  outlined
                  dense
                  clearable
                  label="Código o enlace MOTRIX"
                  @keyup.enter="verificarManual"
                >
                  <template #prepend>
                    <q-icon
                      name="qr_code_2"
                      color="green-8"
                    />
                  </template>
                </q-input>
              </div>

              <div class="col-auto">
                <q-btn
                  color="green-8"
                  icon="verified"
                  label="Verificar"
                  unelevated
                  no-caps
                  class="manual-button"
                  :disable="!codigoManual"
                  @click="verificarManual"
                />
              </div>
            </div>

            <q-banner
              rounded
              class="privacy-note q-mt-lg"
            >
              <template #avatar>
                <q-icon
                  name="privacy_tip"
                  color="green-8"
                />
              </template>

              La cámara se utiliza únicamente mientras esta pantalla
              está abierta. MOTRIX detiene la transmisión al salir.
            </q-banner>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import {
  nextTick,
  onBeforeUnmount,
  ref
} from 'vue'

import {
  useQuasar
} from 'quasar'

import {
  useRouter
} from 'vue-router'

const $q = useQuasar()
const router = useRouter()

const videoRef = ref(null)
const camaraActiva = ref(false)
const iniciandoCamara = ref(false)
const mensajeCamara = ref('')
const errorCamara = ref(false)
const codigoManual = ref('')

let streamActual = null
let detectorQr = null
let intervaloDeteccion = null
let deteccionEnCurso = false
let facingMode = 'environment'

function volver() {
  router.push('/pasajero')
}

function extraerCodigo(valor) {
  const texto =
    String(valor || '').trim()

  if (!texto) return ''

  const coincidenciaRuta =
    texto.match(
      /(?:#\/|\/)verificar\/([^/?#\s]+)/i
    )

  if (coincidenciaRuta?.[1]) {
    return decodeURIComponent(
      coincidenciaRuta[1]
    )
  }

  return texto
}

async function abrirVerificacion(valor) {
  const codigo =
    extraerCodigo(valor)

  if (!codigo) {
    $q.notify({
      type: 'warning',
      position: 'top',
      message:
        'No se encontró un código para verificar.'
    })

    return
  }

  detenerCamara()

  await router.push(
    `/verificar/${encodeURIComponent(codigo)}`
  )
}

function verificarManual() {
  abrirVerificacion(
    codigoManual.value
  )
}

async function prepararDetector() {
  if (
    !('BarcodeDetector' in window)
  ) {
    detectorQr = null
    return false
  }

  try {
    const formatos =
      await window.BarcodeDetector
        .getSupportedFormats()

    if (
      !formatos.includes('qr_code')
    ) {
      detectorQr = null
      return false
    }

    detectorQr =
      new window.BarcodeDetector({
        formats: ['qr_code']
      })

    return true
  } catch {
    detectorQr = null
    return false
  }
}

async function iniciarCamara() {
  if (iniciandoCamara.value) return

  iniciandoCamara.value = true
  errorCamara.value = false
  mensajeCamara.value = ''

  try {
    if (
      !navigator.mediaDevices
        ?.getUserMedia
    ) {
      throw new Error(
        'Este navegador no permite acceder a la cámara.'
      )
    }

    detenerCamara(false)

    streamActual =
      await navigator.mediaDevices
        .getUserMedia({
          audio: false,
          video: {
            facingMode: {
              ideal: facingMode
            },
            width: {
              ideal: 1280
            },
            height: {
              ideal: 720
            }
          }
        })

    camaraActiva.value = true

    await nextTick()

    if (videoRef.value) {
      videoRef.value.srcObject =
        streamActual

      await videoRef.value.play()
    }

    const detectorDisponible =
      await prepararDetector()

    if (!detectorDisponible) {
      errorCamara.value = true
      mensajeCamara.value =
        'La cámara está activa, pero este navegador no incluye '
        + 'lector QR automático. Puedes ingresar el código manualmente. '
        + 'En la aplicación Android usaremos el lector de cámara del dispositivo.'

      return
    }

    mensajeCamara.value =
      'Coloca el código QR dentro del recuadro.'

    iniciarDeteccion()
  } catch (error) {
    console.error(
      'No se pudo iniciar la cámara:',
      error
    )

    detenerCamara()

    errorCamara.value = true

    if (
      error?.name === 'NotAllowedError'
    ) {
      mensajeCamara.value =
        'No se concedió permiso para usar la cámara. '
        + 'Habilita el permiso del navegador e inténtalo nuevamente.'
    } else if (
      error?.name === 'NotFoundError'
    ) {
      mensajeCamara.value =
        'No se encontró una cámara disponible en este dispositivo.'
    } else {
      mensajeCamara.value =
        error?.message
        || 'No fue posible iniciar la cámara.'
    }
  } finally {
    iniciandoCamara.value = false
  }
}

function iniciarDeteccion() {
  detenerIntervaloDeteccion()

  intervaloDeteccion =
    window.setInterval(
      detectarQrEnVideo,
      450
    )
}

async function detectarQrEnVideo() {
  if (
    deteccionEnCurso
    || !detectorQr
    || !videoRef.value
    || videoRef.value.readyState < 2
  ) {
    return
  }

  deteccionEnCurso = true

  try {
    const codigos =
      await detectorQr.detect(
        videoRef.value
      )

    const valor =
      codigos?.[0]?.rawValue

    if (valor) {
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
    }
  } catch {
    // La detección puede fallar en algunos fotogramas.
  } finally {
    deteccionEnCurso = false
  }
}

function detenerIntervaloDeteccion() {
  if (intervaloDeteccion) {
    window.clearInterval(
      intervaloDeteccion
    )

    intervaloDeteccion = null
  }
}

function detenerCamara(
  limpiarMensaje = true
) {
  detenerIntervaloDeteccion()

  if (streamActual) {
    streamActual
      .getTracks()
      .forEach(
        pista => pista.stop()
      )

    streamActual = null
  }

  if (videoRef.value) {
    videoRef.value.srcObject = null
  }

  camaraActiva.value = false
  deteccionEnCurso = false

  if (limpiarMensaje) {
    mensajeCamara.value = ''
    errorCamara.value = false
  }
}

async function cambiarCamara() {
  facingMode =
    facingMode === 'environment'
      ? 'user'
      : 'environment'

  await iniciarCamara()
}

onBeforeUnmount(() => {
  detenerCamara()
})
</script>

<style scoped>
.scanner-page {
  min-height: 100%;
  background: transparent;
}

.scanner-card {
  overflow: hidden;
  border-radius: 18px;
}

.scanner-header {
  background:
    linear-gradient(
      135deg,
      #1b5e20,
      #2e7d32
    );
}

.camera-box {
  position: relative;
  min-height: 390px;
  overflow: hidden;
  background: #102713;
  border: 1px solid #c9dbc5;
  border-radius: 18px;
}

.camera-video {
  width: 100%;
  height: 390px;
  display: block;
  object-fit: cover;
  background: #102713;
}

.camera-placeholder {
  position: absolute;
  inset: 0;
  padding: 30px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #315535;
  background:
    linear-gradient(
      145deg,
      #edf7eb,
      #f8fbf7
    );
}

.scan-frame {
  position: absolute;
  top: 50%;
  left: 50%;
  width: min(255px, 70%);
  aspect-ratio: 1;
  transform:
    translate(-50%, -50%);
}

.corner {
  position: absolute;
  width: 42px;
  height: 42px;
  border-color: #ffffff;
  border-style: solid;
}

.corner-a {
  top: 0;
  left: 0;
  border-width: 5px 0 0 5px;
  border-radius: 12px 0 0;
}

.corner-b {
  top: 0;
  right: 0;
  border-width: 5px 5px 0 0;
  border-radius: 0 12px 0 0;
}

.corner-c {
  bottom: 0;
  left: 0;
  border-width: 0 0 5px 5px;
  border-radius: 0 0 0 12px;
}

.corner-d {
  right: 0;
  bottom: 0;
  border-width: 0 5px 5px 0;
  border-radius: 0 0 12px;
}

.scan-line {
  position: absolute;
  right: 12px;
  left: 12px;
  top: 50%;
  height: 3px;
  background: #81c784;
  box-shadow:
    0 0 12px rgba(129, 199, 132, 0.9);
  animation:
    scanMove 1.8s ease-in-out infinite alternate;
}

@keyframes scanMove {
  from {
    transform: translateY(-92px);
  }

  to {
    transform: translateY(92px);
  }
}

.camera-status {
  position: absolute;
  top: 12px;
  left: 12px;
}

.manual-button {
  min-height: 40px;
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
  .scanner-page {
    padding: 9px 9px 20px;
  }

  .scanner-card {
    border-radius: 14px;
  }

  .camera-box,
  .camera-video {
    min-height: 330px;
    height: 330px;
  }

  .manual-button {
    width: 100%;
  }
}
</style>
