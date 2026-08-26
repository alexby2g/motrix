<template>
  <q-page class="incidencias-page q-pa-md q-pa-lg-lg">
    <div class="row justify-center">
      <div class="col-12 col-xl-11">
        <q-card class="incidencias-shell shadow-2">
          <q-card-section class="incidencias-header text-white">
            <div class="row items-center no-wrap">
              <q-avatar
                color="white"
                text-color="red-8"
                icon="sos"
                size="54px"
                class="q-mr-md"
              />

              <div class="col min-width-zero">
                <div class="text-h5 text-weight-bold">
                  Centro de incidencias
                </div>

                <div class="text-caption text-green-1">
                  {{ subtituloAmbito }}
                </div>
              </div>

              <q-btn
                flat
                round
                icon="refresh"
                color="white"
                :loading="cargando"
                @click="cargar"
              />
            </div>
          </q-card-section>

          <q-linear-progress
            v-if="cargando"
            indeterminate
            color="green-8"
          />

          <q-card-section class="q-pa-md q-pa-lg-md">
            <div class="row q-col-gutter-md q-mb-md">
              <div
                v-for="tarjeta in tarjetas"
                :key="tarjeta.key"
                class="col-6 col-md"
              >
                <q-card
                  flat
                  bordered
                  class="resumen-card full-height"
                >
                  <q-card-section class="text-center">
                    <q-icon
                      :name="tarjeta.icon"
                      :color="tarjeta.color"
                      size="28px"
                    />

                    <div
                      class="text-h5 text-weight-bold q-mt-xs"
                      :class="`text-${tarjeta.color}`"
                    >
                      {{ resumen[tarjeta.key] || 0 }}
                    </div>

                    <div class="text-caption text-grey-6">
                      {{ tarjeta.label }}
                    </div>
                  </q-card-section>
                </q-card>
              </div>
            </div>

            <q-card
              flat
              bordered
              class="filters-card q-mb-md"
            >
              <q-card-section class="row q-col-gutter-md">
                <div class="col-12 col-md-4">
                  <q-select
                    v-model="filtroEstado"
                    :options="estados"
                    outlined
                    dense
                    clearable
                    label="Estado"
                  />
                </div>

                <div class="col-12 col-md-5">
                  <q-select
                    v-model="filtroTipo"
                    :options="tipos"
                    outlined
                    dense
                    clearable
                    label="Tipo de incidencia"
                  />
                </div>

                <div class="col-12 col-md-3">
                  <q-input
                    v-model.number="filtroSolicitud"
                    outlined
                    dense
                    clearable
                    type="number"
                    min="1"
                    label="N.º de viaje"
                  />
                </div>
              </q-card-section>

              <q-card-actions align="right">
                <q-btn
                  flat
                  color="grey-7"
                  label="Limpiar"
                  no-caps
                  @click="limpiarFiltros"
                />

                <q-btn
                  color="green-8"
                  icon="filter_alt"
                  label="Aplicar"
                  unelevated
                  no-caps
                  @click="cargar"
                />
              </q-card-actions>
            </q-card>

            <div
              v-if="!cargando && incidencias.length === 0"
              class="empty-state"
            >
              <q-icon
                name="verified_user"
                color="green-5"
                size="64px"
              />

              <div class="text-h6 text-weight-bold text-grey-7 q-mt-md">
                No hay incidencias para mostrar
              </div>

              <div class="text-body2 text-grey-6 text-center">
                {{
                  esSecretario
                    ? 'Solo aparecerán incidencias relacionadas con mototaxistas de tu sindicato.'
                    : 'No existen incidencias con los filtros seleccionados.'
                }}
              </div>
            </div>

            <div
              v-else
              class="row q-col-gutter-md"
            >
              <div
                v-for="incidencia in incidencias"
                :key="incidencia.id"
                class="col-12 col-md-6 col-xl-4"
              >
                <q-card
                  flat
                  bordered
                  class="incidencia-card full-height"
                >
                  <q-card-section>
                    <div class="row items-start no-wrap">
                      <q-avatar
                        :color="colorEstado(incidencia.estado)"
                        text-color="white"
                        icon="warning"
                        size="48px"
                        class="q-mr-md"
                      />

                      <div class="col min-width-zero">
                        <div class="row items-start no-wrap">
                          <div class="col">
                            <div class="text-subtitle1 text-weight-bold text-grey-9">
                              {{ incidencia.codigo }}
                            </div>

                            <div class="text-caption text-grey-6">
                              Viaje #{{ incidencia.solicitud_id }}
                            </div>
                          </div>

                          <q-chip
                            dense
                            :color="colorEstado(incidencia.estado)"
                            text-color="white"
                            class="text-weight-bold"
                          >
                            {{ incidencia.estado }}
                          </q-chip>
                        </div>

                        <div class="text-body2 text-weight-bold text-red-9 q-mt-md">
                          {{ incidencia.tipo }}
                        </div>

                        <div class="text-caption text-grey-7 q-mt-xs">
                          Reportado por:
                          <strong>
                            {{ incidencia.reportado_por_nombre }}
                          </strong>
                        </div>

                        <div class="text-caption text-grey-7 q-mt-xs">
                          Mototaxista:
                          <strong>
                            {{
                              incidencia?.solicitud?.mototaxista?.persona?.nombre
                              || 'No registrado'
                            }}
                          </strong>
                        </div>

                        <div
                          v-if="incidencia?.solicitud?.mototaxista?.sindicato?.nombre"
                          class="text-caption text-grey-7 q-mt-xs"
                        >
                          Sindicato:
                          <strong>
                            {{
                              incidencia.solicitud.mototaxista.sindicato.nombre
                            }}
                          </strong>
                        </div>

                        <q-banner
                          v-if="incidencia.descripcion"
                          rounded
                          dense
                          class="bg-grey-2 text-grey-8 q-mt-md"
                        >
                          {{ incidencia.descripcion }}
                        </q-banner>

                        <div class="text-caption text-grey-6 q-mt-md">
                          {{ formatearFecha(incidencia.fecha_reportada) }}
                        </div>
                      </div>
                    </div>
                  </q-card-section>

                  <q-separator />

                  <q-card-actions align="right">
                    <q-btn
                      flat
                      color="green-8"
                      icon="visibility"
                      label="Atender"
                      no-caps
                      @click="abrir(incidencia)"
                    />
                  </q-card-actions>
                </q-card>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <q-dialog
      v-model="dialogo"
      persistent
    >
      <q-card class="dialog-card">
        <q-card-section class="dialog-header text-white">
          <div class="row items-center no-wrap">
            <q-icon
              name="sos"
              size="32px"
              class="q-mr-md"
            />

            <div class="col">
              <div class="text-h6 text-weight-bold">
                {{ seleccionada?.codigo }}
              </div>

              <div class="text-caption">
                Viaje #{{ seleccionada?.solicitud_id }}
              </div>
            </div>

            <q-btn
              flat
              round
              icon="close"
              v-close-popup
            />
          </div>
        </q-card-section>

        <q-card-section class="q-gutter-md">
          <div>
            <div class="text-caption text-grey-6">
              Tipo
            </div>
            <div class="text-subtitle1 text-weight-bold">
              {{ seleccionada?.tipo }}
            </div>
          </div>

          <div>
            <div class="text-caption text-grey-6">
              Descripción
            </div>
            <div class="text-body2">
              {{ seleccionada?.descripcion || 'Sin descripción' }}
            </div>
          </div>

          <q-select
            v-model="nuevoEstado"
            :options="estadosGestion"
            outlined
            label="Cambiar estado"
            :disable="seleccionada?.estado === 'Resuelto'"
          />

          <q-input
            v-model="nota"
            outlined
            type="textarea"
            autogrow
            maxlength="1000"
            counter
            label="Nota de atención"
          />
        </q-card-section>

        <q-card-actions
          align="right"
          class="q-pa-md bg-grey-1"
        >
          <q-btn
            flat
            color="grey-7"
            label="Cerrar"
            no-caps
            v-close-popup
          />

          <q-btn
            color="green-8"
            icon="save"
            label="Guardar estado"
            no-caps
            unelevated
            :loading="guardando"
            :disable="
              !nuevoEstado
              || seleccionada?.estado === 'Resuelto'
            "
            @click="guardarEstado"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import {
  computed,
  onMounted,
  ref
} from 'vue'

import {
  useQuasar
} from 'quasar'

import {
  api
} from 'src/boot/axios.js'

const $q = useQuasar()

const cargando = ref(false)
const guardando = ref(false)
const incidencias = ref([])

const resumen = ref({
  reportadas: 0,
  recibidas: 0,
  en_atencion: 0,
  resueltas: 0,
  activas: 0
})

const filtroEstado = ref(null)
const filtroTipo = ref(null)
const filtroSolicitud = ref(null)

const dialogo = ref(false)
const seleccionada = ref(null)
const nuevoEstado = ref(null)
const nota = ref('')

const usuario = (() => {
  try {
    return JSON.parse(
      localStorage.getItem('motrix_user')
      || 'null'
    )
  } catch {
    return null
  }
})()

const esSecretario =
  String(usuario?.role || '')
    .toLowerCase()
  === 'secretario'

const subtituloAmbito = computed(() => {
  if (esSecretario) {
    return usuario?.sindicato_nombre
      ? `Incidencias del sindicato ${usuario.sindicato_nombre}`
      : 'Incidencias exclusivas de tu sindicato'
  }

  return 'Control general de alertas SOS de MOTRIX'
})

const estados = [
  'Reportado',
  'Recibido',
  'En atención',
  'Resuelto'
]

const estadosGestion = [
  'Recibido',
  'En atención',
  'Resuelto'
]

const tipos = [
  'Accidente',
  'Emergencia médica',
  'Situación de inseguridad',
  'Falla de la motocicleta',
  'Pasajero no localizado',
  'Conductor no localizado',
  'Otro'
]

const tarjetas = [
  {
    key: 'activas',
    label: 'Activas',
    icon: 'notifications_active',
    color: 'red-8'
  },
  {
    key: 'reportadas',
    label: 'Reportadas',
    icon: 'report',
    color: 'orange-9'
  },
  {
    key: 'recibidas',
    label: 'Recibidas',
    icon: 'mark_email_read',
    color: 'blue-8'
  },
  {
    key: 'en_atencion',
    label: 'En atención',
    icon: 'health_and_safety',
    color: 'purple-7'
  },
  {
    key: 'resueltas',
    label: 'Resueltas',
    icon: 'verified',
    color: 'green-8'
  }
]

async function cargar() {
  if (cargando.value) return

  cargando.value = true

  try {
    const params = {}

    if (filtroEstado.value) {
      params.estado = filtroEstado.value
    }

    if (filtroTipo.value) {
      params.tipo = filtroTipo.value
    }

    if (Number(filtroSolicitud.value) > 0) {
      params.solicitud_id =
        Number(filtroSolicitud.value)
    }

    const respuesta =
      await api.get(
        '/incidencias',
        { params }
      )

    incidencias.value =
      Array.isArray(
        respuesta.data?.incidencias
      )
        ? respuesta.data.incidencias
        : []

    resumen.value = {
      ...resumen.value,
      ...(respuesta.data?.resumen || {})
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      message:
        error?.response?.data?.message
        || error?.response?.data?.mensaje
        || 'No se pudieron cargar las incidencias.'
    })
  } finally {
    cargando.value = false
  }
}

function limpiarFiltros() {
  filtroEstado.value = null
  filtroTipo.value = null
  filtroSolicitud.value = null
  cargar()
}

function abrir(incidencia) {
  seleccionada.value = incidencia
  nuevoEstado.value =
    siguienteEstado(incidencia?.estado)
  nota.value =
    incidencia?.nota_administrador
    || ''
  dialogo.value = true
}

function siguienteEstado(estado) {
  if (estado === 'Reportado') {
    return 'Recibido'
  }

  if (estado === 'Recibido') {
    return 'En atención'
  }

  if (estado === 'En atención') {
    return 'Resuelto'
  }

  return null
}

async function guardarEstado() {
  if (
    !seleccionada.value?.id
    || !nuevoEstado.value
  ) {
    return
  }

  guardando.value = true

  try {
    const respuesta =
      await api.put(
        `/incidencias/${seleccionada.value.id}/estado`,
        {
          estado: nuevoEstado.value,
          nota_administrador:
            nota.value.trim() || null
        }
      )

    $q.notify({
      type: 'positive',
      position: 'top',
      message:
        respuesta.data?.mensaje
        || 'Incidencia actualizada.'
    })

    dialogo.value = false
    await cargar()
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      message:
        error?.response?.data?.message
        || error?.response?.data?.mensaje
        || 'No se pudo actualizar la incidencia.'
    })
  } finally {
    guardando.value = false
  }
}

function colorEstado(estado) {
  if (estado === 'Reportado') {
    return 'red-8'
  }

  if (estado === 'Recibido') {
    return 'blue-8'
  }

  if (estado === 'En atención') {
    return 'purple-7'
  }

  if (estado === 'Resuelto') {
    return 'green-8'
  }

  return 'grey-7'
}

function formatearFecha(valor) {
  if (!valor) return 'Fecha no registrada'

  const fecha =
    new Date(
      String(valor)
        .replace(' ', 'T')
    )

  if (Number.isNaN(fecha.getTime())) {
    return String(valor)
  }

  return new Intl.DateTimeFormat(
    'es-BO',
    {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    }
  ).format(fecha)
}

onMounted(() => {
  cargar()
})
</script>

<style scoped>
.incidencias-page {
  min-height: 100%;
  background: #f1f8e9;
}

.incidencias-shell {
  overflow: hidden;
  border-radius: 18px;
}

.incidencias-header,
.dialog-header {
  background:
    linear-gradient(
      135deg,
      #0a2e0a,
      #2e7d32
    );
}

.resumen-card,
.filters-card,
.incidencia-card {
  border-color: #d6e4d2;
  border-radius: 14px;
}

.incidencia-card {
  border-top: 4px solid #c62828;
}

.empty-state {
  min-height: 260px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.dialog-card {
  width: min(620px, 94vw);
  max-width: 620px;
  border-radius: 16px;
  overflow: hidden;
}

.min-width-zero {
  min-width: 0;
}

@media (max-width: 599px) {
  .incidencias-page {
    padding: 8px 8px 20px;
  }

  .incidencias-shell {
    border-radius: 13px;
  }
}
</style>
