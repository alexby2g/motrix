<template>
  <div v-if="habilitado" class="motrix-chat-acceso">
    <q-btn
      unelevated
      no-caps
      icon="chat_bubble_outline"
      label="Enviar mensaje"
      color="positive"
      class="motrix-chat-boton full-width"
      @click="abierto = true"
    />
    <q-dialog v-model="abierto" position="bottom">
      <q-card class="motrix-chat-panel">
        <div class="motrix-chat-cabecera">
          <q-avatar color="green-1" text-color="positive" icon="forum" size="42px" />
          <div class="col min-width-zero">
            <div class="text-subtitle1 text-weight-bold">Mensajes del viaje</div>
            <div class="text-caption">Viaje #{{ viajeId }} · Solo pasajero y conductor</div>
          </div>
          <q-btn flat round dense icon="close" aria-label="Cerrar chat" @click="abierto = false" />
        </div>

        <div ref="contenedorMensajes" class="motrix-chat-mensajes">
          <div v-if="cargando" class="text-center q-pa-lg">
            <q-spinner color="positive" size="28px" />
            <div class="text-caption q-mt-sm">Cargando mensajes...</div>
          </div>
          <div v-else-if="!mensajes.length" class="motrix-chat-vacio">
            <q-icon name="chat_bubble_outline" size="42px" color="grey-5" />
            <div class="text-weight-medium q-mt-sm">Coordinen el punto de encuentro</div>
            <div class="text-caption text-grey-7">
              Este chat está disponible mientras el viaje esté activo.
            </div>
          </div>
          <div
            v-for="mensaje in mensajes"
            :key="mensaje.id"
            class="motrix-chat-fila"
            :class="{ 'motrix-chat-fila-propia': mensaje.remitente_tipo === rol }"
          >
            <div
              class="motrix-chat-burbuja"
              :class="{ 'motrix-chat-burbuja-propia': mensaje.remitente_tipo === rol }"
            >
              <div class="motrix-chat-remitente">
                {{ mensaje.remitente_tipo === rol ? 'Tú' : (mensaje.remitente_nombre || 'Participante') }}
              </div>
              <div class="motrix-chat-texto">{{ mensaje.mensaje }}</div>
              <div class="motrix-chat-hora">{{ horaMensaje(mensaje.creado_en) }}</div>
            </div>
          </div>
        </div>

        <div v-if="error" class="motrix-chat-error" role="alert">{{ error }}</div>
        <div class="motrix-chat-respuestas">
          <q-chip
            v-for="frase in respuestasRapidas"
            :key="frase"
            clickable
            outline
            color="positive"
            text-color="positive"
            @click="texto = frase"
          >
            {{ frase }}
          </q-chip>
        </div>
        <div class="motrix-chat-enviar">
          <q-input
            v-model="texto"
            outlined
            dense
            autogrow
            type="textarea"
            maxlength="1000"
            placeholder="Escribe un mensaje..."
            class="col"
            :disable="enviando"
            @keydown.enter.exact.prevent="enviarMensaje"
          />
          <q-btn
            round
            unelevated
            icon="send"
            color="positive"
            aria-label="Enviar mensaje"
            :loading="enviando"
            :disable="!texto.trim() || enviando"
            @click="enviarMensaje"
          />
        </div>
        <div class="motrix-chat-aviso">
          El chat se cierra al terminar o cancelar el viaje. Los mensajes se conservan
          en MOTRIX para seguridad y atención de incidentes.
        </div>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { api } from 'src/boot/axios.js'

const props = defineProps({
  viajeId: { type: [Number, String], required: true },
  estado: { type: String, required: true },
  rol: {
    type: String,
    required: true,
    validator: (valor) => ['pasajero', 'conductor'].includes(valor)
  }
})

const abierto = ref(false)
const cargando = ref(false)
const enviando = ref(false)
const consultando = ref(false)
const mensajes = ref([])
const texto = ref('')
const error = ref('')
const contenedorMensajes = ref(null)
let temporizador = null

const habilitado = computed(() =>
  Number(props.viajeId) > 0
  && ['Aceptado', 'Llegó', 'En Curso'].includes(String(props.estado || ''))
)

const respuestasRapidas = computed(() => props.rol === 'conductor'
  ? ['Voy en camino', 'Ya estoy en el punto de recogida', '¿Me confirma su ubicación?']
  : ['Estoy en el punto indicado', '¿Cuánto falta para llegar?', 'Gracias']
)

function rutaMensajes() {
  return '/' + props.rol + '/solicitudes/' + Number(props.viajeId) + '/mensajes'
}

function horaMensaje(valor) {
  const textoFecha = String(valor || '')
  return textoFecha.length >= 16 ? textoFecha.slice(11, 16) : ''
}

async function bajarAlUltimoMensaje() {
  await nextTick()
  const panel = contenedorMensajes.value
  if (panel) panel.scrollTop = panel.scrollHeight
}

async function cargarMensajes(silencioso = false) {
  if (!habilitado.value || consultando.value || !abierto.value) return
  const idConsultado = Number(props.viajeId)
  consultando.value = true
  if (!silencioso) cargando.value = true

  try {
    const respuesta = await api.get(rutaMensajes())
    if (idConsultado !== Number(props.viajeId) || !abierto.value) return
    if (!respuesta.data?.chat_habilitado) {
      abierto.value = false
      return
    }
    const recibidos = Array.isArray(respuesta.data?.mensajes) ? respuesta.data.mensajes : []
    const ultimoAnterior = mensajes.value[mensajes.value.length - 1]?.id
    mensajes.value = recibidos
    error.value = ''
    if (recibidos[recibidos.length - 1]?.id !== ultimoAnterior) {
      await bajarAlUltimoMensaje()
    }
    if (Number(respuesta.data?.no_leidos || 0) > 0) {
      await api.post(rutaMensajes() + '/leidos')
    }
  } catch (e) {
    if (idConsultado === Number(props.viajeId)) {
      error.value = e.response?.data?.mensaje || 'No se pudieron cargar los mensajes. Intenta nuevamente.'
    }
  } finally {
    consultando.value = false
    cargando.value = false
  }
}

async function enviarMensaje() {
  const contenido = texto.value.trim()
  if (!contenido || enviando.value || !habilitado.value) return
  const idEnvio = Number(props.viajeId)
  enviando.value = true
  error.value = ''
  try {
    const respuesta = await api.post(rutaMensajes(), { mensaje: contenido })
    if (idEnvio !== Number(props.viajeId)) return
    texto.value = ''
    const nuevo = respuesta.data?.chat_mensaje
    if (nuevo && !mensajes.value.some((m) => Number(m.id) === Number(nuevo.id))) {
      mensajes.value = [...mensajes.value, nuevo]
    }
    await bajarAlUltimoMensaje()
  } catch (e) {
    error.value = e.response?.data?.mensaje || 'No se pudo enviar. Comprueba tu conexión.'
    if (e.response?.status === 409) abierto.value = false
  } finally {
    enviando.value = false
  }
}

function detenerConsulta() {
  if (temporizador !== null) {
    window.clearInterval(temporizador)
    temporizador = null
  }
}

watch(abierto, async (valor) => {
  detenerConsulta()
  if (!valor) return
  await cargarMensajes()
  if (abierto.value && habilitado.value) {
    temporizador = window.setInterval(() => cargarMensajes(true), 5000)
  }
})

watch(() => [props.viajeId, props.estado], () => {
  if (!habilitado.value) {
    abierto.value = false
    mensajes.value = []
    texto.value = ''
    error.value = ''
  } else if (abierto.value) {
    cargarMensajes(true)
  }
})

onBeforeUnmount(detenerConsulta)
</script>

<style scoped>
.motrix-chat-boton {
  min-height: 46px;
  border-radius: 14px;
  font-weight: 700;
}
.motrix-chat-panel {
  display: flex;
  flex-direction: column;
  width: 100%;
  max-width: 640px;
  height: min(76dvh, 680px);
  margin: 0 auto;
  border-radius: 24px 24px 0 0;
  overflow: hidden;
  background: #fff;
}
.motrix-chat-cabecera {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  color: #153b2b;
  background: #f3faf5;
  border-bottom: 1px solid #dcece0;
}
.min-width-zero { min-width: 0; }
.motrix-chat-mensajes {
  flex: 1 1 auto;
  min-height: 0;
  padding: 18px 14px;
  overflow-y: auto;
  overscroll-behavior: contain;
  background: #f7f9f8;
}
.motrix-chat-vacio {
  padding: 34px 12px;
  text-align: center;
}
.motrix-chat-fila {
  display: flex;
  justify-content: flex-start;
  margin-bottom: 12px;
}
.motrix-chat-fila-propia { justify-content: flex-end; }
.motrix-chat-burbuja {
  max-width: min(86%, 390px);
  padding: 10px 13px;
  border-radius: 16px 16px 16px 4px;
  background: #fff;
  box-shadow: 0 2px 8px rgba(22, 54, 36, .08);
  overflow-wrap: anywhere;
}
.motrix-chat-burbuja-propia {
  color: #fff;
  background: #126338;
  border-radius: 16px 16px 4px 16px;
}
.motrix-chat-remitente { font-size: 11px; font-weight: 800; opacity: .75; }
.motrix-chat-texto { white-space: pre-wrap; line-height: 1.45; }
.motrix-chat-hora { margin-top: 4px; font-size: 10px; text-align: right; opacity: .7; }
.motrix-chat-error { padding: 8px 16px; color: #a31621; background: #fff1f2; font-size: 12px; }
.motrix-chat-respuestas {
  display: flex;
  gap: 3px;
  padding: 7px 12px 0;
  overflow-x: auto;
  white-space: nowrap;
}
.motrix-chat-respuestas :deep(.q-chip) { flex: 0 0 auto; font-size: 12px; }
.motrix-chat-enviar {
  display: flex;
  align-items: flex-end;
  gap: 10px;
  padding: 10px 14px;
}
.motrix-chat-enviar :deep(.q-field__control) { border-radius: 14px; }
.motrix-chat-aviso {
  padding: 0 14px calc(12px + env(safe-area-inset-bottom));
  color: #64756a;
  font-size: 11px;
  line-height: 1.4;
  text-align: center;
}
@media (max-width: 599px) {
  .motrix-chat-panel { height: min(78dvh, 700px); }
}
</style>
