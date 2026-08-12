<template>
  <q-page class="q-pa-md q-pa-lg-md mototaxistas-page">
    <div class="row items-center q-col-gutter-md q-mb-md">
      <div class="col">
        <div class="row items-center no-wrap">
          <q-avatar
            color="green-1"
            text-color="green-9"
            icon="two_wheeler"
            size="48px"
            class="q-mr-md"
          />

          <div>
            <div class="text-h5 text-weight-bold text-green-9">
              Mototaxistas
            </div>
            <div class="text-caption text-grey-7">
              Afiliación, chaleco, estado, QR y acceso como conductor.
            </div>
          </div>
        </div>
      </div>

      <div class="col-auto">
        <q-btn
          color="green-8"
          icon="person_add"
          label="Nuevo mototaxista"
          unelevated
          @click="abrirFormulario()"
        />
      </div>
    </div>

    <!-- RESUMEN -->
    <div class="row q-col-gutter-md q-mb-md">
      <div class="col-6 col-md-3">
        <q-card flat bordered class="stat-card">
          <q-card-section>
            <div class="text-caption text-grey-7">Registrados</div>
            <div class="text-h5 text-weight-bold text-green-9">
              {{ mototaxistas.length }}
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-6 col-md-3">
        <q-card flat bordered class="stat-card">
          <q-card-section>
            <div class="text-caption text-grey-7">Activos</div>
            <div class="text-h5 text-weight-bold text-positive">
              {{ totalActivos }}
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-6 col-md-3">
        <q-card flat bordered class="stat-card">
          <q-card-section>
            <div class="text-caption text-grey-7">Con QR</div>
            <div class="text-h5 text-weight-bold text-blue-8">
              {{ totalConQr }}
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-6 col-md-3">
        <q-card flat bordered class="stat-card">
          <q-card-section>
            <div class="text-caption text-grey-7">Con cuenta conductor</div>
            <div class="text-h5 text-weight-bold text-purple-7">
              {{ totalConCuenta }}
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- FILTROS -->
    <q-card flat bordered class="q-mb-md filtro-card">
      <q-card-section class="row q-col-gutter-md items-center">
        <div class="col-12 col-md">
          <q-input
            v-model="filtro"
            dense
            outlined
            debounce="250"
            placeholder="Buscar nombre, CI, chaleco, sindicato o teléfono"
          >
            <template #prepend>
              <q-icon name="search" color="green-8" />
            </template>
          </q-input>
        </div>

        <div class="col-12 col-sm-4 col-md-3">
          <q-select
            v-model="filtroEstado"
            :options="['Todos', 'Activo', 'Inactivo']"
            dense
            outlined
            label="Estado"
          />
        </div>

        <div class="col-12 col-sm-4 col-md-3">
          <q-select
            v-model="filtroSindicato"
            :options="opcionesSindicatoFiltro"
            dense
            outlined
            label="Sindicato"
          />
        </div>
      </q-card-section>
    </q-card>

    <q-linear-progress
      v-if="loading"
      indeterminate
      color="green-8"
      class="q-mb-md"
    />

    <div
      v-if="!loading && mototaxistasFiltrados.length"
      class="row q-col-gutter-md"
    >
      <div
        v-for="m in mototaxistasFiltrados"
        :key="m.id"
        class="col-12 col-md-6 col-xl-4"
      >
        <q-card
          flat
          bordered
          class="mototaxista-card full-height"
        >
          <div
            class="franja-estado"
            :class="
              m.estado === 'Activo'
                ? 'bg-positive'
                : 'bg-negative'
            "
          />

          <q-card-section class="row no-wrap items-start">
            <q-avatar
              size="62px"
              color="green-1"
              text-color="green-9"
              class="q-mr-md"
            >
              <img
                v-if="fotoUrl(m)"
                :src="fotoUrl(m)"
                alt="Foto"
              >
              <span v-else>
                {{ iniciales(m) }}
              </span>
            </q-avatar>

            <div class="col min-width-zero">
              <div class="text-subtitle1 text-weight-bold text-grey-9">
                {{ nombreCompleto(m) }}
              </div>

              <div class="text-caption text-grey-7">
                CI: {{ m.persona?.ci || '—' }}
              </div>

              <div class="text-caption text-grey-7">
                {{ m.sindicato?.nombre || 'Sin sindicato' }}
                · Chaleco {{ m.nro_chaleco || '—' }}
              </div>

              <div class="row q-gutter-xs q-mt-sm">
                <q-badge
                  :color="
                    m.estado === 'Activo'
                      ? 'positive'
                      : 'negative'
                  "
                >
                  {{ m.estado || 'Sin estado' }}
                </q-badge>

                <q-badge
                  :color="
                    m.codigo_qr
                      ? 'blue-8'
                      : 'grey-6'
                  "
                >
                  {{ m.codigo_qr ? 'QR listo' : 'Sin QR' }}
                </q-badge>

                <q-badge
                  :color="
                    m.usuario_conductor
                      ? 'purple-7'
                      : 'grey-6'
                  "
                >
                  {{
                    m.usuario_conductor
                      ? 'Cuenta conductor'
                      : 'Sin cuenta'
                  }}
                </q-badge>
              </div>
            </div>

            <q-btn
              flat
              round
              dense
              icon="more_vert"
              color="grey-7"
            >
              <q-menu>
                <q-list style="min-width: 215px">
                  <q-item
                    clickable
                    v-close-popup
                    @click="abrirDetalle(m)"
                  >
                    <q-item-section avatar>
                      <q-icon name="visibility" color="blue-8" />
                    </q-item-section>
                    <q-item-section>Ver detalle</q-item-section>
                  </q-item>

                  <q-item
                    v-if="esAdminGeneral"
                    clickable
                    v-close-popup
                    @click="abrirSoporteConductor(m)"
                  >
                    <q-item-section avatar>
                      <q-icon name="support_agent" color="purple-7" />
                    </q-item-section>
                    <q-item-section>Modo soporte</q-item-section>
                  </q-item>

                  <q-item
                    clickable
                    v-close-popup
                    @click="abrirFormulario(m)"
                  >
                    <q-item-section avatar>
                      <q-icon name="edit" color="green-8" />
                    </q-item-section>
                    <q-item-section>Editar</q-item-section>
                  </q-item>

                  <q-item
                    clickable
                    v-close-popup
                    @click="cambiarEstado(m)"
                  >
                    <q-item-section avatar>
                      <q-icon
                        :name="
                          m.estado === 'Activo'
                            ? 'toggle_off'
                            : 'toggle_on'
                        "
                        :color="
                          m.estado === 'Activo'
                            ? 'negative'
                            : 'positive'
                        "
                      />
                    </q-item-section>
                    <q-item-section>
                      {{
                        m.estado === 'Activo'
                          ? 'Marcar Inactivo'
                          : 'Marcar Activo'
                      }}
                    </q-item-section>
                  </q-item>

                  <q-item
                    clickable
                    v-close-popup
                    @click="generarQr(m)"
                  >
                    <q-item-section avatar>
                      <q-icon name="qr_code_2" color="blue-8" />
                    </q-item-section>
                    <q-item-section>
                      {{
                        m.codigo_qr
                          ? 'Ver código QR'
                          : 'Generar código QR'
                      }}
                    </q-item-section>
                  </q-item>

                  <q-item
                    v-if="!m.usuario_conductor"
                    clickable
                    v-close-popup
                    @click="abrirCuenta(m)"
                  >
                    <q-item-section avatar>
                      <q-icon name="login" color="purple-7" />
                    </q-item-section>
                    <q-item-section>Crear cuenta conductor</q-item-section>
                  </q-item>

                  <q-separator />

                  <q-item
                    clickable
                    v-close-popup
                    @click="confirmarEliminar(m)"
                  >
                    <q-item-section avatar>
                      <q-icon name="delete" color="negative" />
                    </q-item-section>
                    <q-item-section class="text-negative">
                      Eliminar
                    </q-item-section>
                  </q-item>
                </q-list>
              </q-menu>
            </q-btn>
          </q-card-section>

          <q-separator />

          <q-card-section class="q-py-sm">
            <div class="row items-center justify-between text-caption">
              <div class="text-grey-7">
                <q-icon name="phone" size="15px" />
                {{ m.telefono || m.persona?.telefono || 'Sin teléfono' }}
              </div>

              <div
                :class="
                  m.disponible
                    ? 'text-positive'
                    : 'text-grey-6'
                "
              >
                <q-icon
                  :name="
                    m.disponible
                      ? 'wifi'
                      : 'wifi_off'
                  "
                  size="15px"
                />
                {{
                  m.disponible
                    ? 'En línea'
                    : 'Fuera de línea'
                }}
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <div
      v-else-if="!loading"
      class="column items-center q-pa-xl text-grey-6"
    >
      <q-icon name="two_wheeler" size="58px" />
      <div class="text-subtitle1 q-mt-sm">
        No se encontraron mototaxistas
      </div>
    </div>

    <!-- FORMULARIO -->
    <q-dialog v-model="dialogForm" persistent>
      <q-card class="dialog-card">
        <q-card-section class="bg-green-8 text-white row items-center">
          <q-icon
            name="two_wheeler"
            size="28px"
            class="q-mr-sm"
          />
          <div>
            <div class="text-h6 text-weight-bold">
              {{ editando ? 'Editar mototaxista' : 'Nuevo mototaxista' }}
            </div>
            <div class="text-caption text-green-1">
              Afiliación sindical y datos operativos.
            </div>
          </div>
          <q-space />
          <q-btn
            flat
            round
            dense
            icon="close"
            @click="cerrarFormulario"
          />
        </q-card-section>

        <q-form ref="formRef" @submit.prevent="guardar">
          <q-card-section class="q-pa-lg">
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <q-select
                  v-model="form.id_persona"
                  :options="personasDisponibles"
                  option-value="id"
                  :option-label="labelPersona"
                  emit-value
                  map-options
                  use-input
                  input-debounce="0"
                  outlined
                  label="Persona *"
                  :disable="editando"
                  :rules="[requerido]"
                  @filter="filtrarPersonas"
                >
                  <template #prepend>
                    <q-icon name="person" color="green-8" />
                  </template>
                </q-select>
              </div>

              <div class="col-12 col-sm-7">
                <q-select
                  v-model="form.id_sindicato"
                  :options="sindicatos"
                  option-value="id"
                  option-label="nombre"
                  emit-value
                  map-options
                  outlined
                  label="Sindicato *"
                  :rules="[requerido]"
                >
                  <template #prepend>
                    <q-icon name="groups" color="green-8" />
                  </template>
                </q-select>
              </div>

              <div class="col-12 col-sm-5">
                <q-input
                  v-model.trim="form.nro_chaleco"
                  outlined
                  label="N.º de chaleco *"
                  maxlength="20"
                  :rules="[requerido]"
                >
                  <template #prepend>
                    <q-icon name="style" color="green-8" />
                  </template>
                </q-input>
              </div>

              <div class="col-12 col-sm-6">
                <q-input
                  v-model.trim="form.telefono"
                  outlined
                  label="Teléfono"
                  maxlength="20"
                >
                  <template #prepend>
                    <q-icon name="phone" color="green-8" />
                  </template>
                </q-input>
              </div>

              <div class="col-12 col-sm-6">
                <q-select
                  v-model="form.estado"
                  :options="['Activo', 'Inactivo']"
                  outlined
                  label="Estado *"
                  :rules="[requerido]"
                />
              </div>
            </div>

            <q-banner
              rounded
              class="bg-blue-1 text-blue-10 q-mt-sm"
            >
              <template #avatar>
                <q-icon name="info" color="blue-8" />
              </template>

              El estado de afiliación es distinto a estar En línea.
              Un mototaxista puede estar Activo y continuar fuera de línea.
            </q-banner>
          </q-card-section>

          <q-card-actions align="right" class="q-pa-md bg-grey-1">
            <q-btn
              flat
              label="Cancelar"
              color="grey-7"
              :disable="saving"
              @click="cerrarFormulario"
            />
            <q-btn
              type="submit"
              color="green-8"
              icon="save"
              :label="editando ? 'Guardar cambios' : 'Registrar'"
              unelevated
              :loading="saving"
            />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- DETALLE -->
    <q-dialog v-model="dialogDetalle">
      <q-card class="dialog-card">
        <q-card-section class="bg-green-8 text-white row items-center">
          <q-icon name="badge" size="28px" class="q-mr-sm" />
          <div class="text-h6 text-weight-bold">
            Perfil del mototaxista
          </div>
          <q-space />
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>

        <q-card-section v-if="seleccionado" class="q-pa-lg">
          <div class="row q-col-gutter-lg">
            <div class="col-12 col-sm-4 text-center">
              <q-avatar size="110px" color="green-1" text-color="green-9">
                <img
                  v-if="fotoUrl(seleccionado)"
                  :src="fotoUrl(seleccionado)"
                >
                <span v-else class="text-h5">
                  {{ iniciales(seleccionado) }}
                </span>
              </q-avatar>
            </div>

            <div class="col-12 col-sm-8">
              <div class="text-h6 text-weight-bold">
                {{ nombreCompleto(seleccionado) }}
              </div>
              <div class="text-body2 q-mt-sm">
                <strong>CI:</strong> {{ seleccionado.persona?.ci || '—' }}
              </div>
              <div class="text-body2">
                <strong>Teléfono:</strong>
                {{ seleccionado.telefono || seleccionado.persona?.telefono || '—' }}
              </div>
              <div class="text-body2">
                <strong>Sindicato:</strong> {{ seleccionado.sindicato?.nombre || '—' }}
              </div>
              <div class="text-body2">
                <strong>Federación:</strong>
                {{ seleccionado.sindicato?.federacion_relacion?.nombre || '—' }}
              </div>
              <div class="text-body2">
                <strong>Chaleco:</strong> {{ seleccionado.nro_chaleco || '—' }}
              </div>
              <div class="text-body2">
                <strong>Estado:</strong> {{ seleccionado.estado || '—' }}
              </div>
              <div class="text-body2">
                <strong>QR:</strong>
                {{ seleccionado.codigo_qr ? 'Generado' : 'Pendiente' }}
              </div>
              <div class="text-body2">
                <strong>Cuenta conductor:</strong>
                {{
                  seleccionado.usuario_conductor
                    ? seleccionado.usuario_conductor.email
                    : 'No creada'
                }}
              </div>
              <div class="text-body2">
                <strong>Motocicletas:</strong>
                {{ seleccionado.motocicletas?.length || 0 }}
              </div>
            </div>
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- QR -->
    <q-dialog v-model="dialogQr">
      <q-card class="qr-dialog">
        <q-card-section class="bg-blue-8 text-white row items-center">
          <q-icon name="qr_code_2" size="30px" class="q-mr-sm" />
          <div>
            <div class="text-h6 text-weight-bold">
              Código QR de verificación
            </div>
            <div class="text-caption text-blue-1">
              {{ nombreCompleto(seleccionadoQr) }}
            </div>
          </div>
          <q-space />
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>

        <q-card-section class="q-pa-lg">
          <div class="column items-center">
            <q-spinner
              v-if="generandoImagenQr"
              color="blue-8"
              size="48px"
            />

            <img
              v-else-if="qrDataUrl"
              :src="qrDataUrl"
              alt="Código QR MOTRIX"
              class="qr-image"
            >

            <q-banner
              v-else
              rounded
              class="bg-orange-1 text-orange-10 full-width"
            >
              No fue posible generar la imagen QR.
            </q-banner>
          </div>

          <q-banner
            rounded
            class="bg-blue-1 text-blue-10 q-mt-md"
          >
            <div class="text-caption">
              Enlace público codificado
            </div>
            <div class="text-body2 text-weight-bold codigo-break q-mt-xs">
              {{ qrPublicUrl }}
            </div>
          </q-banner>

          <div class="text-caption text-grey-7 q-mt-md">
            Al escanear este QR se abrirá la ficha pública y segura
            del mototaxista, sin necesidad de iniciar sesión.
          </div>
        </q-card-section>

        <q-card-actions
          align="center"
          class="q-pa-md bg-grey-1"
        >
          <q-btn
            outline
            color="blue-8"
            icon="open_in_new"
            label="Probar verificación"
            @click="abrirVerificacionPublica"
          />

          <q-btn
            color="green-8"
            icon="download"
            label="Descargar QR"
            unelevated
            :disable="!qrDataUrl"
            @click="descargarQr"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- CUENTA CONDUCTOR -->
    <q-dialog v-model="dialogCuenta" persistent>
      <q-card class="dialog-card">
        <q-card-section class="bg-purple-7 text-white row items-center">
          <q-icon name="login" size="28px" class="q-mr-sm" />
          <div>
            <div class="text-h6 text-weight-bold">
              Crear cuenta de conductor
            </div>
            <div class="text-caption">
              {{ nombreCompleto(seleccionadoCuenta) }}
            </div>
          </div>
          <q-space />
          <q-btn
            flat
            round
            dense
            icon="close"
            :disable="creandoCuenta"
            @click="dialogCuenta = false"
          />
        </q-card-section>

        <q-card-section class="q-pa-lg">
          <q-input
            v-model.trim="cuenta.email"
            outlined
            type="email"
            label="Correo *"
            class="q-mb-md"
          >
            <template #prepend>
              <q-icon name="email" color="purple-7" />
            </template>
          </q-input>

          <q-input
            v-model.trim="cuenta.nickname"
            outlined
            label="Nickname (opcional)"
            class="q-mb-md"
          >
            <template #prepend>
              <q-icon name="alternate_email" color="purple-7" />
            </template>
          </q-input>

          <q-input
            v-model="cuenta.password"
            outlined
            type="password"
            label="Contraseña *"
          >
            <template #prepend>
              <q-icon name="lock" color="purple-7" />
            </template>
          </q-input>

          <q-banner rounded class="bg-purple-1 text-purple-9 q-mt-md">
            El mototaxista debe estar Activo y tener QR generado.
          </q-banner>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md bg-grey-1">
          <q-btn
            flat
            label="Cancelar"
            color="grey-7"
            :disable="creandoCuenta"
            @click="dialogCuenta = false"
          />
          <q-btn
            color="purple-7"
            icon="person_add"
            label="Crear cuenta"
            unelevated
            :loading="creandoCuenta"
            @click="crearCuenta"
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

import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import QRCode from 'qrcode'
import { api } from 'src/boot/axios.js'
import { API_ORIGIN } from 'src/config/runtime.js'

const $q = useQuasar()
const router = useRouter()

const esAdminGeneral = computed(() => {
  try {
    const usuario = JSON.parse(
      localStorage.getItem('motrix_user') || 'null'
    )

    return String(
      usuario?.role || ''
    )
      .trim()
      .toLowerCase() === 'admin_general'
  } catch {
    return false
  }
})


const mototaxistas = ref([])
const personas = ref([])
const personasDisponibles = ref([])
const sindicatos = ref([])

const loading = ref(false)
const saving = ref(false)
const filtro = ref('')
const filtroEstado = ref('Todos')
const filtroSindicato = ref('Todos')

const dialogForm = ref(false)
const editando = ref(false)
const formRef = ref(null)

const dialogDetalle = ref(false)
const seleccionado = ref(null)

const dialogQr = ref(false)
const seleccionadoQr = ref(null)
const qrDataUrl = ref('')
const qrPublicUrl = ref('')
const generandoImagenQr = ref(false)

const dialogCuenta = ref(false)
const seleccionadoCuenta = ref(null)
const creandoCuenta = ref(false)

const formDefault = {
  id: null,
  id_persona: null,
  id_sindicato: null,
  nro_chaleco: '',
  telefono: '',
  estado: 'Activo'
}

const form = ref({
  ...formDefault
})

const cuenta = ref({
  email: '',
  nickname: '',
  password: ''
})

const requerido = valor =>
  Boolean(
    String(valor ?? '').trim()
  )
  || 'Campo obligatorio'

const totalActivos = computed(() =>
  mototaxistas.value.filter(
    m => m.estado === 'Activo'
  ).length
)

const totalConQr = computed(() =>
  mototaxistas.value.filter(
    m =>
      Boolean(
        String(m.codigo_qr || '').trim()
      )
  ).length
)

const totalConCuenta = computed(() =>
  mototaxistas.value.filter(
    m => Boolean(m.usuario_conductor)
  ).length
)

const opcionesSindicatoFiltro = computed(() => {
  const nombres = sindicatos.value
    .map(s => s.nombre)
    .filter(Boolean)

  return [
    'Todos',
    ...nombres
  ]
})

const mototaxistasFiltrados = computed(() => {
  let lista = [...mototaxistas.value]

  const texto =
    normalizar(filtro.value)

  if (texto) {
    lista = lista.filter(m => {
      const contenido = [
        nombreCompleto(m),
        m.persona?.ci,
        m.nro_chaleco,
        m.sindicato?.nombre,
        m.telefono,
        m.persona?.telefono
      ]
        .map(normalizar)
        .join(' ')

      return contenido.includes(texto)
    })
  }

  if (filtroEstado.value !== 'Todos') {
    lista = lista.filter(
      m =>
        m.estado === filtroEstado.value
    )
  }

  if (filtroSindicato.value !== 'Todos') {
    lista = lista.filter(
      m =>
        m.sindicato?.nombre
        === filtroSindicato.value
    )
  }

  return lista
})

function normalizar(valor) {
  return String(valor || '')
    .trim()
    .toLocaleLowerCase('es')
}

function nombreCompleto(m) {
  if (!m) return 'Mototaxista'

  return [
    m.persona?.nombre,
    m.persona?.apellidos
  ]
    .filter(Boolean)
    .join(' ')
    .trim()
    || `Mototaxista #${m.id || '—'}`
}

function labelPersona(p) {
  if (!p) return ''

  return [
    p.nombre,
    p.apellidos
  ]
    .filter(Boolean)
    .join(' ')
    + (
      p.ci
        ? ` · CI ${p.ci}`
        : ''
    )
}

function iniciales(m) {
  const partes =
    nombreCompleto(m)
      .split(/\s+/)
      .filter(Boolean)

  return (
    (
      partes[0]?.charAt(0)
      || ''
    )
    + (
      partes.length > 1
        ? partes[
            partes.length - 1
          ].charAt(0)
        : ''
    )
  )
    .toUpperCase()
    .slice(0, 2)
    || 'M'
}

function apiOrigen() {
  try {
    return new URL(
      api.defaults.baseURL
    ).origin
  } catch {
    return API_ORIGIN
  }
}

function fotoUrl(m) {
  const imagenes =
    Array.isArray(
      m?.persona?.imagenes
    )
      ? m.persona.imagenes
      : []

  const ruta =
    imagenes[
      imagenes.length - 1
    ]?.ruta

  if (!ruta) return ''

  if (
    /^https?:\/\//i.test(ruta)
  ) {
    return ruta
  }

  return (
    `${apiOrigen()}/storage/`
    + String(ruta)
      .replace(/^\/+/, '')
  )
}

function mensajeError(error) {
  const data =
    error?.response?.data

  if (data?.errors) {
    const mensaje =
      Object.values(
        data.errors
      )
        .flat()
        .find(Boolean)

    if (mensaje) return mensaje
  }

  return (
    data?.mensaje
    || data?.message
    || 'No se pudo completar la operación.'
  )
}

async function cargarTodo() {
  loading.value = true

  try {
    const [
      resMototaxistas,
      resPersonas,
      resSindicatos
    ] = await Promise.all([
      api.get('/mototaxistas'),
      api.get('/personas'),
      api.get('/sindicatos')
    ])

    mototaxistas.value =
      Array.isArray(
        resMototaxistas.data
      )
        ? resMototaxistas.data
        : []

    personas.value =
      Array.isArray(
        resPersonas.data
      )
        ? resPersonas.data
        : []

    personasDisponibles.value =
      personas.value

    sindicatos.value =
      Array.isArray(
        resSindicatos.data
      )
        ? resSindicatos.data
        : []
  } catch (error) {
    console.error(error)

    $q.notify({
      type: 'negative',
      position: 'top',
      message: mensajeError(error)
    })
  } finally {
    loading.value = false
  }
}

function filtrarPersonas(
  valor,
  update
) {
  update(() => {
    const texto =
      normalizar(valor)

    if (!texto) {
      personasDisponibles.value =
        personas.value

      return
    }

    personasDisponibles.value =
      personas.value.filter(p =>
        normalizar(
          labelPersona(p)
        ).includes(texto)
      )
  })
}

function abrirFormulario(m = null) {
  if (m) {
    editando.value = true

    form.value = {
      id: m.id,
      id_persona: m.id_persona,
      id_sindicato: m.id_sindicato,
      nro_chaleco:
        m.nro_chaleco || '',
      telefono:
        m.telefono || '',
      estado:
        m.estado || 'Activo'
    }
  } else {
    editando.value = false
    form.value = {
      ...formDefault
    }
  }

  personasDisponibles.value =
    personas.value

  dialogForm.value = true
}

function cerrarFormulario() {
  if (saving.value) return

  dialogForm.value = false
  form.value = {
    ...formDefault
  }
}

async function guardar() {
  const valido =
    await formRef.value?.validate()

  if (valido === false) return

  saving.value = true

  try {
    const payload = {
      id_persona:
        form.value.id_persona,
      id_sindicato:
        form.value.id_sindicato,
      nro_chaleco:
        form.value.nro_chaleco,
      telefono:
        form.value.telefono || null,
      estado:
        form.value.estado
    }

    if (editando.value) {
      await api.put(
        `/mototaxistas/${form.value.id}`,
        payload
      )
    } else {
      await api.post(
        '/mototaxistas',
        payload
      )
    }

    $q.notify({
      type: 'positive',
      position: 'top',
      message:
        editando.value
          ? 'Mototaxista actualizado.'
          : 'Mototaxista registrado.'
    })

    cerrarFormulario()
    await cargarTodo()
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      multiLine: true,
      message: mensajeError(error)
    })
  } finally {
    saving.value = false
  }
}

function abrirSoporteConductor(m) {
  if (!esAdminGeneral.value || !m?.id) {
    return
  }

  router.push(
    `/soporte/conductor/${m.id}`
  )
}

async function abrirDetalle(m) {
  try {
    const respuesta =
      await api.get(
        `/mototaxistas/${m.id}`
      )

    seleccionado.value =
      respuesta.data

    dialogDetalle.value = true
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      message: mensajeError(error)
    })
  }
}

async function cambiarEstado(m) {
  try {
    const respuesta =
      await api.post(
        `/mototaxistas/${m.id}/cambiar-estado`
      )

    $q.notify({
      type: 'positive',
      position: 'top',
      message:
        respuesta.data?.mensaje
        || 'Estado actualizado.'
    })

    await cargarTodo()
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      multiLine: true,
      message: mensajeError(error)
    })
  }
}

function construirUrlPublicaQr(codigo) {
  const base =
    `${window.location.origin}${window.location.pathname}`
      .replace(/\/$/, '')

  return (
    `${base}/#/verificar/`
    + encodeURIComponent(codigo)
  )
}

async function prepararImagenQr(codigo) {
  qrDataUrl.value = ''
  qrPublicUrl.value =
    construirUrlPublicaQr(codigo)

  generandoImagenQr.value = true

  try {
    qrDataUrl.value =
      await QRCode.toDataURL(
        qrPublicUrl.value,
        {
          width: 340,
          margin: 2,
          errorCorrectionLevel: 'H'
        }
      )
  } finally {
    generandoImagenQr.value = false
  }
}

async function generarQr(m) {
  try {
    const respuesta =
      await api.post(
        `/mototaxistas/${m.id}/generar-qr`
      )

    seleccionadoQr.value =
      respuesta.data?.data
      || {
        ...m,
        codigo_qr:
          respuesta.data?.codigo_qr
      }

    const codigo =
      seleccionadoQr.value?.codigo_qr
      || respuesta.data?.codigo_qr

    if (!codigo) {
      throw new Error(
        'El backend no devolvió el código QR.'
      )
    }

    await prepararImagenQr(codigo)

    dialogQr.value = true

    await cargarTodo()
  } catch (error) {
    console.error(
      'Error generando QR:',
      error
    )

    $q.notify({
      type: 'negative',
      position: 'top',
      message:
        error?.message
        || mensajeError(error)
    })
  }
}

function abrirVerificacionPublica() {
  if (!qrPublicUrl.value) return

  window.open(
    qrPublicUrl.value,
    '_blank',
    'noopener,noreferrer'
  )
}

function descargarQr() {
  if (!qrDataUrl.value) return

  const enlace =
    document.createElement('a')

  const chaleco =
    seleccionadoQr.value?.nro_chaleco
    || seleccionadoQr.value?.id
    || 'mototaxista'

  enlace.href =
    qrDataUrl.value

  enlace.download =
    `MOTRIX_QR_${chaleco}.png`

  document.body.appendChild(enlace)
  enlace.click()
  enlace.remove()
}

function abrirCuenta(m) {
  if (m.estado !== 'Activo') {
    $q.notify({
      type: 'warning',
      position: 'top',
      message:
        'Primero marca al mototaxista como Activo.'
    })

    return
  }

  if (!m.codigo_qr) {
    $q.notify({
      type: 'warning',
      position: 'top',
      message:
        'Primero genera el código QR.'
    })

    return
  }

  seleccionadoCuenta.value = m

  cuenta.value = {
    email: '',
    nickname: '',
    password: ''
  }

  dialogCuenta.value = true
}

async function crearCuenta() {
  if (
    !seleccionadoCuenta.value?.id
    || !cuenta.value.email
    || !cuenta.value.password
  ) {
    $q.notify({
      type: 'negative',
      position: 'top',
      message:
        'Correo y contraseña son obligatorios.'
    })

    return
  }

  creandoCuenta.value = true

  try {
    await api.post(
      `/mototaxistas/${seleccionadoCuenta.value.id}/cuenta-conductor`,
      {
        email:
          cuenta.value.email,
        nickname:
          cuenta.value.nickname || null,
        password:
          cuenta.value.password
      }
    )

    $q.notify({
      type: 'positive',
      position: 'top',
      message:
        'Cuenta de conductor creada correctamente.'
    })

    dialogCuenta.value = false
    await cargarTodo()
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      multiLine: true,
      message: mensajeError(error)
    })
  } finally {
    creandoCuenta.value = false
  }
}

function confirmarEliminar(m) {
  $q.dialog({
    title: 'Eliminar mototaxista',
    message:
      `¿Eliminar a ${nombreCompleto(m)}? `
      + 'Si tiene historial, MOTRIX bloqueará la eliminación.',
    cancel: true,
    persistent: true
  }).onOk(async () => {
    try {
      await api.delete(
        `/mototaxistas/${m.id}`
      )

      $q.notify({
        type: 'positive',
        position: 'top',
        message:
          'Mototaxista eliminado correctamente.'
      })

      await cargarTodo()
    } catch (error) {
      $q.notify({
        type: 'negative',
        position: 'top',
        multiLine: true,
        message: mensajeError(error)
      })
    }
  })
}

onMounted(
  cargarTodo
)
</script>

<style scoped>
.mototaxistas-page {
  min-height: 100%;
  background: transparent;
}

.stat-card,
.filtro-card,
.mototaxista-card {
  border-color: #d8e7d5;
  border-radius: 14px;
}

.stat-card {
  border-left: 4px solid #2e7d32;
}

.mototaxista-card {
  position: relative;
  overflow: hidden;
  transition:
    transform 0.16s ease,
    box-shadow 0.16s ease;
}

.mototaxista-card:hover {
  transform: translateY(-2px);
  box-shadow:
    0 8px 22px rgba(46, 125, 50, 0.14);
}

.franja-estado {
  height: 4px;
  width: 100%;
}

.min-width-zero {
  min-width: 0;
}

.dialog-card {
  width: 720px;
  max-width: 94vw;
  border-radius: 16px;
}

.qr-dialog {
  width: 540px;
  max-width: 94vw;
  border-radius: 16px;
}

.codigo-break {
  overflow-wrap: anywhere;
  user-select: all;
}

.qr-image {
  width: min(340px, 82vw);
  height: auto;
  padding: 12px;
  background: white;
  border: 1px solid #d7e2ed;
  border-radius: 14px;
  box-shadow:
    0 8px 24px rgba(21, 101, 192, 0.12);
}

@media (max-width: 599px) {
  .dialog-card,
  .qr-dialog {
    width: 100vw;
    max-width: 100vw;
    border-radius: 0;
  }
}
</style>
