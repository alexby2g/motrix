<template>
  <div>
    <q-btn
      v-if="!activa"
      outline
      color="positive"
      icon="phone"
      label="Llamar por MOTRIX"
      class="full-width"
      :loading="ocupado"
      @click="iniciar"
    />

    <q-btn
      v-else
      outline
      color="positive"
      icon="phone_in_talk"
      label="Ver llamada"
      class="full-width"
      @click="abierto = true"
    />

    <q-dialog v-model="abierto" persistent>
      <q-card style="width: 380px; max-width: 95vw">
        <q-card-section class="text-center">
          <q-icon
            name="phone_in_talk"
            color="positive"
            size="48px"
          />
          <div class="text-h6 q-mt-sm">
            Llamada privada MOTRIX
          </div>
          <div class="text-caption">
            Viaje #{{ viajeId }}
          </div>
        </q-card-section>

        <q-card-section class="text-center">
          <div class="text-subtitle1">
            {{ mensaje }}
          </div>
          <div v-if="error" class="text-negative q-mt-sm">
            {{ error }}
          </div>
        </q-card-section>

        <q-card-actions
          vertical
          class="q-pa-md"
        >
          <template v-if="estadoLlamada === 'pendiente'">
            <template v-if="soyReceptor">
              <q-btn
                color="positive"
                icon="call"
                label="Contestar"
                :loading="ocupado"
                @click="responder('aceptar')"
              />
              <q-btn
                outline
                color="negative"
                icon="call_end"
                label="Rechazar"
                :disable="ocupado"
                @click="responder('rechazar')"
              />
            </template>

            <q-btn
              v-else
              outline
              color="negative"
              icon="call_end"
              label="Cancelar llamada"
              :loading="ocupado"
              @click="finalizar"
            />
          </template>

          <template v-else-if="estadoLlamada === 'contestada'">
            <q-btn
              v-if="!conexion"
              color="positive"
              icon="mic"
              label="Conectar audio"
              :loading="conectando"
              @click="conectarAudio"
            />

            <q-btn
              v-if="conexion && requiereActivarAudio"
              color="positive"
              icon="volume_up"
              label="Activar altavoz"
              :loading="activandoAudio"
              @click="activarAudio"
            />

            <q-btn
              v-if="conexion"
              outline
              icon="mic_off"
              :label="silenciado ? 'Activar microfono' : 'Silenciar'"
              @click="alternarMicrofono"
            />

            <q-btn
              color="negative"
              icon="call_end"
              label="Colgar"
              :loading="ocupado"
              @click="finalizar"
            />
          </template>

          <q-btn
            v-else
            flat
            label="Cerrar"
            @click="abierto = false"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import {
  ref,
  shallowRef,
  computed,
  onMounted,
  onUnmounted,
  watch
} from 'vue'
import { api } from 'src/boot/axios.js'
import { iniciarLlamadaPrivada } from 'src/services/llamadasPrivadas.js'

const props = defineProps({
  viajeId: { type: [Number, String], required: true },
  estado: { type: String, required: true }
})

const abierto = ref(false)
const ocupado = ref(false)
const conectando = ref(false)
const activandoAudio = ref(false)
const requiereActivarAudio = ref(false)
const silenciado = ref(false)
const soyReceptor = ref(false)
const estadoLlamada = ref('sin_llamada')
const error = ref('')
const conexion = shallowRef(null)

let intervalo = null
let consultando = false
let autoconectarIntentado = false
let desmontado = false
let siguienteConsultaPermitida = 0

let contextoTono = null
let intervaloTono = null

let audioTimbre = null

function reproducirTimbre() {
  if (!intervaloTono) return

  if (!audioTimbre) {
    audioTimbre = new Audio('/sounds/motrix_llamada.wav')
    audioTimbre.volume = 1
  }

  audioTimbre.pause()
  audioTimbre.currentTime = 0
  audioTimbre.play().catch(() => {})
}

function iniciarTonoEntrante() {
  if (intervaloTono) return

  intervaloTono = window.setInterval(reproducirTimbre, 2300)
  reproducirTimbre()
}

function detenerTonoEntrante() {
  if (audioTimbre) {
    audioTimbre.pause()
    audioTimbre.currentTime = 0
  }
  if (intervaloTono) {
    window.clearInterval(intervaloTono)
    intervaloTono = null
  }

  if (contextoTono) {
    contextoTono.suspend().catch(() => {})
  }
}

const estadosPermitidos = ['Aceptado', 'Llegó', 'En Curso']

const activa = computed(() =>
  ['pendiente', 'contestada'].includes(estadoLlamada.value)
)

const mensaje = computed(() => {
  if (estadoLlamada.value === 'pendiente') {
    return soyReceptor.value
      ? 'Tienes una llamada entrante'
      : 'Llamando... Esperando respuesta'
  }

  if (estadoLlamada.value === 'contestada') {
    return conexion.value
      ? 'Audio conectado'
      : 'Llamada contestada'
  }

  if (estadoLlamada.value === 'rechazada') {
    return 'La llamada fue rechazada'
  }

  if (estadoLlamada.value === 'expirada') {
    return 'La llamada no fue contestada'
  }

  return 'Llamada finalizada'
})

async function desconectarAudio() {
  const actual = conexion.value
  conexion.value = null
  silenciado.value = false
  requiereActivarAudio.value = false

  if (actual) {
    try {
      await actual.colgar()
    } catch {
      // La conexion ya puede estar cerrada.
    }
  }
}

async function consultar() {
  if (consultando || desmontado) return
  if (!estadosPermitidos.includes(props.estado)) return
  if (Date.now() < siguienteConsultaPermitida) return

  consultando = true

  try {
    const { data } = await api.get(
      `/llamadas/${props.viajeId}/estado`
    )

    if (desmontado) return

    const anterior = estadoLlamada.value
    const siguiente = data?.estado || 'sin_llamada'

    soyReceptor.value = Boolean(data?.soy_receptor)
    estadoLlamada.value = siguiente

    if (siguiente === 'pendiente' && soyReceptor.value) {
      abierto.value = true
      iniciarTonoEntrante()
    } else {
      detenerTonoEntrante()
    }

    if (siguiente === 'contestada' && !autoconectarIntentado) {
      autoconectarIntentado = true
      await conectarAudio()
    }

    if (!['pendiente', 'contestada'].includes(siguiente)) {
      if (anterior === 'pendiente' || anterior === 'contestada') {
        await desconectarAudio()
        abierto.value = true
      }
      autoconectarIntentado = false
    }
  } catch (e) {
    // Respetar el limite de solicitudes del servidor sin cerrar la llamada.
    if (e?.response?.status === 429) {
      const cabecera = Number(e?.response?.headers?.['retry-after'] || 0)
      const segundos = Number.isFinite(cabecera) && cabecera > 0 ? cabecera : 30
      siguienteConsultaPermitida = Date.now() + Math.max(10, segundos) * 1000
      error.value = 'El servidor esta ocupado. Se reintentara automaticamente.'
    }
  } finally {
    consultando = false
  }
}

async function iniciar() {
  if (ocupado.value || activa.value) return

  ocupado.value = true
  error.value = ''

  try {
    const { data } = await api.post(
      `/llamadas/${props.viajeId}/iniciar`
    )

    if (data?.estado !== 'pendiente') {
      throw new Error('No se pudo iniciar la llamada')
    }

    soyReceptor.value = false
    estadoLlamada.value = 'pendiente'
    abierto.value = true
    autoconectarIntentado = false
  } catch (e) {
    error.value =
      e?.response?.data?.message ||
      e?.message ||
      'No se pudo iniciar la llamada'
    abierto.value = true
  } finally {
    ocupado.value = false
  }
}

async function responder(accion) {
  if (ocupado.value) return
  detenerTonoEntrante()

  ocupado.value = true
  error.value = ''

  try {
    const { data } = await api.post(
      `/llamadas/${props.viajeId}/responder`,
      { accion }
    )

    estadoLlamada.value = data.estado

    if (data.estado === 'contestada') {
      autoconectarIntentado = true
      await conectarAudio()
    }
  } catch (e) {
    error.value =
      e?.response?.data?.message ||
      'No se pudo responder la llamada'
  } finally {
    ocupado.value = false
  }
}

async function conectarAudio() {
  if (conexion.value || conectando.value || desmontado) return
  if (estadoLlamada.value !== 'contestada') return

  conectando.value = true
  error.value = ''

  try {
    requiereActivarAudio.value = false
    const nueva = await iniciarLlamadaPrivada(
      props.viajeId,
      (estado) => {
        if (estado === 'audio_bloqueado') requiereActivarAudio.value = true
      }
    )

    if (desmontado || estadoLlamada.value !== 'contestada') {
      await nueva.colgar()
      return
    }

    conexion.value = nueva
  } catch (e) {
    error.value =
      e?.response?.data?.message ||
      e?.message ||
      'No se pudo conectar el microfono'
  } finally {
    conectando.value = false
  }
}

async function activarAudio() {
  if (!conexion.value || activandoAudio.value) return
  activandoAudio.value = true
  try {
    // Se invoca directamente desde el toque del usuario.
    await conexion.value.activarAudio()
    requiereActivarAudio.value = false
    error.value = ''
  } catch {
    error.value = 'No se pudo activar el altavoz. Comprueba el volumen del telefono.'
  } finally {
    activandoAudio.value = false
  }
}

async function alternarMicrofono() {
  if (!conexion.value) return

  try {
    await conexion.value.silenciar(!silenciado.value)
    silenciado.value = !silenciado.value
  } catch {
    error.value = 'No se pudo cambiar el microfono'
  }
}

async function finalizar() {
  if (ocupado.value) return

  ocupado.value = true

  try {
    await api.post(`/llamadas/${props.viajeId}/finalizar`)
    estadoLlamada.value = 'finalizada'
    await desconectarAudio()
    abierto.value = false
  } catch (e) {
    error.value =
      e?.response?.data?.message ||
      'No se pudo finalizar la llamada'
  } finally {
    ocupado.value = false
  }
}

watch(
  () => props.estado,
  async (nuevo) => {
    if (!estadosPermitidos.includes(nuevo)) {
      if (activa.value) {
        api.post(`/llamadas/${props.viajeId}/finalizar`)
          .catch(() => {})
      }

      await desconectarAudio()
      estadoLlamada.value = 'finalizada'
      abierto.value = false
    }
  }
)

onMounted(() => {
  consultar()
  intervalo = window.setInterval(consultar, 5000)
})

onUnmounted(() => {
  desmontado = true
  detenerTonoEntrante()
  if (intervalo) window.clearInterval(intervalo)

  if (activa.value) {
    api.post(`/llamadas/${props.viajeId}/finalizar`)
      .catch(() => {})
  }

  void desconectarAudio()
})
</script>