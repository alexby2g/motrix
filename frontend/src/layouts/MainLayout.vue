<template>
  <q-layout view="hHh Lpr lFf" class="motrix-layout">
    <!-- ENCABEZADO VERDE -->
    <q-header elevated class="motrix-header text-white">
      <q-toolbar class="motrix-toolbar">
        <q-btn
          flat
          dense
          round
          icon="menu"
          aria-label="Abrir o cerrar menú"
          class="q-mr-sm"
          @click="toggleLeftDrawer"
        />

        <q-icon
          name="two_wheeler"
          size="28px"
          class="q-mr-md"
        />

        <q-toolbar-title class="q-pa-none">
          <div class="row items-center no-wrap">
            <div class="brand-title">
              MOTRIX
            </div>

            <div
              v-if="$q.screen.gt.xs"
              class="brand-subtitle q-ml-sm"
            >
              Sistema Integral
            </div>
          </div>
        </q-toolbar-title>

        <q-space />

        <div
          v-if="$q.screen.gt.sm"
          class="row items-center no-wrap q-gutter-sm q-mr-md"
        >
          <q-icon name="place" size="20px" />

          <span class="text-subtitle2 text-weight-medium">
            Trinidad, Beni
          </span>
        </div>

        <q-chip
          v-if="puedeGestionarIncidencias && $q.screen.gt.xs"
          :color="adminWsConectado ? 'green-10' : 'orange-9'"
          text-color="white"
          :icon="adminWsConectado ? 'sensors' : 'sync'"
          dense
          class="q-mr-sm text-weight-bold"
        >
          {{ adminWsConectado ? 'EN VIVO' : 'RECONECTANDO' }}
        </q-chip>

        <q-btn
          v-if="puedeGestionarIncidencias"
          flat
          round
          icon="sos"
          color="white"
          aria-label="Abrir alertas SOS"
          class="q-mr-xs"
          @click="abrirCentroIncidenciaActual"
        >
          <q-badge
            v-if="incidenciasActivasCantidad > 0"
            color="negative"
            floating
            rounded
          >
            {{
              incidenciasActivasCantidad > 99
                ? '99+'
                : incidenciasActivasCantidad
            }}
          </q-badge>

          <q-tooltip>
            {{
              incidenciasActivasCantidad
                ? `${incidenciasActivasCantidad} alertas SOS activas`
                : 'Centro de incidencias SOS'
            }}
          </q-tooltip>
        </q-btn>

        <q-btn
          flat
          round
          icon="account_circle"
          aria-label="Cuenta"
        >
          <q-menu
            anchor="bottom right"
            self="top right"
            class="account-menu"
          >
            <q-list style="min-width: 245px">
              <q-item>
                <q-item-section avatar>
                  <q-avatar
                    color="green-8"
                    text-color="white"
                  >
                    {{ inicialUsuario }}
                  </q-avatar>
                </q-item-section>

                <q-item-section>
                  <q-item-label class="text-weight-bold">
                    {{ nombreUsuario }}
                  </q-item-label>

                  <q-item-label caption>
                    {{ etiquetaRol }}
                  </q-item-label>
                </q-item-section>
              </q-item>

              <q-separator />

              <q-item
                v-if="esPasajero || esConductor"
                clickable
                v-close-popup
                @click="abrirPerfilActual"
              >
                <q-item-section avatar>
                  <q-icon
                    name="account_circle"
                    color="green-8"
                  />
                </q-item-section>

                <q-item-section>
                  Mi perfil
                </q-item-section>
              </q-item>

              <q-separator
                v-if="esPasajero || esConductor"
              />

              <q-item
                clickable
                :disable="cerrandoSesion"
                @click="cerrarSesion"
              >
                <q-item-section avatar>
                  <q-icon
                    name="logout"
                    color="negative"
                  />
                </q-item-section>

                <q-item-section class="text-negative text-weight-medium">
                  Cerrar sesión
                </q-item-section>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>
      </q-toolbar>
    </q-header>

    <!-- MENÚ LATERAL VERDE OSCURO -->
    <q-drawer
      v-model="leftDrawerOpen"
      show-if-above
      bordered
      :width="250"
      class="motrix-drawer text-white"
      style="background: #0A2E0A;"
    >
      <div class="drawer-content">
        <!-- IDENTIDAD DEL SISTEMA -->
        <div class="drawer-brand">
          <q-icon
            name="two_wheeler"
            size="48px"
            class="drawer-logo"
          />

          <div class="text-h6 text-weight-bold q-mt-sm">
            MOTRIX
          </div>

          <div class="text-caption drawer-brand-caption">
            Sindicatos y servicios de mototaxis
          </div>
        </div>

        <!-- USUARIO -->
        <div class="drawer-user">
          <q-avatar
            color="green-7"
            text-color="white"
            :icon="iconoRol"
            size="42px"
            class="q-mr-sm"
          />

          <div class="col min-width-zero">
            <div class="text-body2 text-weight-bold ellipsis">
              {{ tituloPanel }}
            </div>

            <div class="text-caption drawer-user-name ellipsis">
              {{ nombreUsuario }}
            </div>
          </div>
        </div>

        <q-separator dark class="drawer-separator" />

        <!-- OPCIONES -->
        <q-scroll-area class="col">
          <q-list class="q-px-sm q-py-md">
            <template
              v-for="seccion in seccionesMenu"
              :key="seccion.titulo"
            >
              <q-item-label
                v-if="seccion.titulo"
                header
                class="drawer-section-title text-uppercase q-pt-sm q-pb-xs"
              >
                {{ seccion.titulo }}
              </q-item-label>

              <q-item
                v-for="opcion in seccion.opciones"
                :key="opcion.ruta"
                clickable
                :to="opcion.ruta"
                :exact="opcion.exact === true"
                :active-class="opcion.activeClass || 'menu-item-active'"
                class="menu-item"
                @click="cerrarDrawerMovil"
              >
                <q-item-section avatar>
                  <q-icon
                    :name="opcion.icono"
                    size="21px"
                  />
                </q-item-section>

                <q-item-section class="text-weight-medium">
                  {{ opcion.etiqueta }}
                </q-item-section>
              </q-item>
            </template>
          </q-list>
        </q-scroll-area>

        <!-- CERRAR SESIÓN -->
        <div class="drawer-footer">
          <q-btn
            outline
            color="negative"
            icon="logout"
            label="Cerrar sesión"
            class="full-width logout-button"
            :loading="cerrandoSesion"
            @click="cerrarSesion"
          />

          <div class="text-caption text-center q-mt-md drawer-footer-text">
            Instituto José Castillo · Trinidad - Beni 2026
          </div>
        </div>
      </div>
    </q-drawer>

    <!-- NAVEGACIÓN INFERIOR DEL CONDUCTOR EN MÓVIL -->
    <q-footer
      v-if="esConductor && $q.screen.lt.sm"
      elevated
      class="driver-bottom-footer"
    >
      <div class="driver-bottom-nav">
        <q-btn
          flat
          no-caps
          stack
          dense
          icon="two_wheeler"
          label="Operación"
          class="driver-nav-item"
          :class="{
            'driver-nav-active':
              esRutaConductorActiva('/conductor', true)
          }"
          @click="navegarConductor('/conductor')"
        />

        <q-btn
          flat
          no-caps
          stack
          dense
          icon="account_balance_wallet"
          label="Ganancias"
          class="driver-nav-item"
          :class="{
            'driver-nav-active':
              esRutaConductorActiva('/conductor/ganancias', true)
          }"
          @click="navegarConductor('/conductor/ganancias')"
        />

        <q-btn
          flat
          no-caps
          stack
          dense
          icon="account_circle"
          label="Perfil"
          class="driver-nav-item"
          :class="{
            'driver-nav-active':
              esRutaConductorActiva('/conductor/perfil', true)
          }"
          @click="navegarConductor('/conductor/perfil')"
        />
      </div>
    </q-footer>

    <!-- NAVEGACIÓN INFERIOR DEL PASAJERO EN MÓVIL -->
    <q-footer
      v-if="esPasajero && $q.screen.lt.sm"
      elevated
      class="passenger-bottom-footer"
    >
      <div class="passenger-bottom-nav">
        <q-btn
          flat
          no-caps
          stack
          dense
          icon="home"
          label="Inicio"
          class="passenger-nav-item"
          :class="{
            'passenger-nav-active':
              esRutaPasajeroActiva('/pasajero', true)
          }"
          @click="navegarPasajero('/pasajero')"
        />

        <q-btn
          flat
          no-caps
          stack
          dense
          icon="two_wheeler"
          label="Solicitar"
          class="passenger-nav-item"
          :class="{
            'passenger-nav-active':
              esRutaPasajeroActiva('/pasajero/solicitar', true)
          }"
          @click="navegarPasajero('/pasajero/solicitar')"
        />

        <div class="passenger-qr-slot">
          <q-btn
            round
            unelevated
            color="green-8"
            icon="qr_code_scanner"
            class="passenger-qr-button"
            aria-label="Escanear QR"
            @click="navegarPasajero('/pasajero/escanear')"
          />

          <span
            :class="{
              'passenger-qr-label-active':
                esRutaPasajeroActiva('/pasajero/escanear', true)
            }"
          >
            QR
          </span>
        </div>

        <q-btn
          flat
          no-caps
          stack
          dense
          icon="history"
          label="Viajes"
          class="passenger-nav-item"
          :class="{
            'passenger-nav-active':
              esRutaPasajeroActiva('/pasajero/historial', true)
          }"
          @click="navegarPasajero('/pasajero/historial')"
        />

        <q-btn
          flat
          no-caps
          stack
          dense
          icon="person"
          label="Perfil"
          class="passenger-nav-item"
          :class="{
            'passenger-nav-active':
              esRutaPasajeroActiva('/pasajero/perfil', true)
          }"
          @click="navegarPasajero('/pasajero/perfil')"
        />
      </div>
    </q-footer>

    <!-- CONTENIDO -->
    <q-page-container class="motrix-page-container">
      <router-view />
    </q-page-container>

    <!-- ALERTA GLOBAL DE INCIDENCIA SOS -->
    <q-dialog
      v-model="incidenciaDialogOpen"
      persistent
    >
      <q-card class="incidencia-global-card">
        <q-card-section
          class="bg-negative text-white row items-center no-wrap"
        >
          <q-avatar
            color="white"
            text-color="negative"
            icon="sos"
            size="48px"
            class="q-mr-md"
          />

          <div class="col min-width-zero">
            <div class="text-h6 text-weight-bold">
              Alerta SOS recibida
            </div>

            <div class="text-caption ellipsis">
              {{
                incidenciaSeleccionada?.codigo
                  || 'Incidencia MOTRIX'
              }}
              · Viaje
              #{{
                incidenciaSeleccionada?.solicitud_id
                  || '—'
              }}
            </div>
          </div>

          <q-chip
            v-if="incidenciaSeleccionada"
            :color="colorEstadoIncidencia(
              incidenciaSeleccionada.estado
            )"
            text-color="white"
            class="text-weight-bold text-uppercase q-mr-sm"
          >
            {{ incidenciaSeleccionada.estado }}
          </q-chip>

          <q-btn
            icon="close"
            flat
            round
            dense
            :disable="actualizandoIncidencia"
            @click="incidenciaDialogOpen = false"
          />
        </q-card-section>

        <q-card-section
          v-if="incidenciaSeleccionada"
          class="q-pa-md"
        >
          <q-banner
            rounded
            class="bg-red-1 text-red-10 q-mb-md"
          >
            <template #avatar>
              <q-icon
                name="notification_important"
                color="negative"
                size="34px"
              />
            </template>

            Esta es una alerta interna de MOTRIX. Para una
            emergencia inmediata también debe contactarse al
            servicio público correspondiente.
          </q-banner>

          <div class="row q-col-gutter-md">
            <div class="col-12 col-sm-6">
              <div class="incidencia-global-dato">
                <span>Reportado por</span>
                <strong>
                  {{
                    incidenciaSeleccionada.reportado_por_nombre
                      || 'Usuario MOTRIX'
                  }}
                </strong>
              </div>
            </div>

            <div class="col-12 col-sm-6">
              <div class="incidencia-global-dato">
                <span>Rol</span>
                <strong class="text-capitalize">
                  {{
                    incidenciaSeleccionada.reportado_por_rol
                      || '—'
                  }}
                </strong>
              </div>
            </div>

            <div class="col-12 col-sm-6">
              <div class="incidencia-global-dato">
                <span>Tipo</span>
                <strong>
                  {{ incidenciaSeleccionada.tipo || 'Otro' }}
                </strong>
              </div>
            </div>

            <div class="col-12 col-sm-6">
              <div class="incidencia-global-dato">
                <span>Prioridad</span>
                <q-chip
                  :color="colorPrioridadIncidencia(
                    incidenciaSeleccionada.prioridad
                  )"
                  text-color="white"
                  dense
                  class="q-ma-none text-weight-bold"
                >
                  {{
                    incidenciaSeleccionada.prioridad
                      || 'Media'
                  }}
                </q-chip>
              </div>
            </div>

            <div class="col-12">
              <div class="incidencia-global-dato">
                <span>Descripción</span>
                <strong>
                  {{
                    incidenciaSeleccionada.descripcion
                      || 'Sin descripción adicional.'
                  }}
                </strong>
              </div>
            </div>

            <div class="col-12">
              <div class="incidencia-global-dato">
                <span>Fecha y hora</span>
                <strong>
                  {{
                    formatearFechaHoraIncidencia(
                      incidenciaSeleccionada.fecha_reportada
                    )
                  }}
                </strong>
              </div>
            </div>
          </div>

          <q-btn
            v-if="tieneUbicacionIncidencia(
              incidenciaSeleccionada
            )"
            outline
            color="negative"
            icon="location_on"
            label="Abrir ubicación de la alerta"
            class="full-width q-mt-md"
            no-caps
            @click="abrirUbicacionIncidencia(
              incidenciaSeleccionada
            )"
          />
        </q-card-section>

        <q-separator />

        <q-card-actions
          align="right"
          class="q-pa-md q-gutter-sm"
        >
          <q-btn
            flat
            color="grey-7"
            label="Cerrar"
            no-caps
            :disable="actualizandoIncidencia"
            @click="incidenciaDialogOpen = false"
          />

          <q-btn
            v-if="
              incidenciaSeleccionada?.estado
                === 'Reportado'
            "
            color="orange-9"
            icon="done"
            label="Marcar recibida"
            no-caps
            unelevated
            :loading="actualizandoIncidencia"
            @click="cambiarEstadoIncidenciaRapido(
              'Recibido'
            )"
          />

          <q-btn
            v-if="[
              'Reportado',
              'Recibido'
            ].includes(
              incidenciaSeleccionada?.estado
            )"
            color="primary"
            icon="support_agent"
            label="En atención"
            no-caps
            unelevated
            :loading="actualizandoIncidencia"
            @click="cambiarEstadoIncidenciaRapido(
              'En atención'
            )"
          />

          <q-btn
            v-if="
              incidenciaSeleccionada?.estado
                !== 'Resuelto'
            "
            color="positive"
            icon="task_alt"
            label="Resolver"
            no-caps
            unelevated
            :loading="actualizandoIncidencia"
            @click="confirmarResolucionIncidencia"
          />

          <q-btn
            color="dark"
            icon="dashboard"
            label="Ver centro"
            no-caps
            unelevated
            @click="irCentroIncidencias"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-layout>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useQuasar } from 'quasar'
import { useRoute, useRouter } from 'vue-router'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

import { api } from '../boot/axios.js'
import { echoOptions } from '../config/runtime.js'

window.Pusher = Pusher

const $q = useQuasar()
const router = useRouter()
const route = useRoute()

const leftDrawerOpen = ref(true)
const cerrandoSesion = ref(false)
const adminWsConectado = ref(false)

const incidenciaDialogOpen = ref(false)
const incidenciaSeleccionada = ref(null)
const actualizandoIncidencia = ref(false)
const incidenciasActivasCantidad = ref(0)

let echoAdministrador = null
let contextoAudioAdministrador = null
let sonidoAdministradorHabilitado = false
let ultimoEventoAdministrador = {
  clave: '',
  momento: 0
}

let ultimoEventoIncidencia = {
  clave: '',
  momento: 0
}

function leerUsuario() {
  try {
    return JSON.parse(localStorage.getItem('motrix_user') || 'null')
  } catch {
    return null
  }
}

const usuario = ref(leerUsuario())

const rol = computed(() => {
  const valor = String(
    usuario.value?.role || ''
  )
    .trim()
    .toLowerCase()

  if (valor === 'admin') {
    return 'admin_general'
  }

  if (valor === 'cajero') {
    return 'admin_servicios'
  }

  return valor
})

const esAdminGeneral = computed(
  () => rol.value === 'admin_general'
)

const esAdminServicios = computed(
  () => rol.value === 'admin_servicios'
)

const esSecretario = computed(
  () => rol.value === 'secretario'
)

const esConductor = computed(
  () => rol.value === 'conductor'
)

const esPasajero = computed(
  () => rol.value === 'pasajero'
)

const puedeGestionarIncidencias = computed(
  () =>
    esAdminGeneral.value
    || esSecretario.value
)

const nombreUsuario = computed(() => {
  return (
    usuario.value?.persona?.nombre
    || usuario.value?.mototaxista?.persona?.nombre
    || usuario.value?.pasajero?.persona?.nombre
    || usuario.value?.name
    || usuario.value?.nombre
    || usuario.value?.email
    || 'Usuario MOTRIX'
  )
})

const inicialUsuario = computed(() => {
  return String(nombreUsuario.value).trim().charAt(0).toUpperCase() || 'M'
})

const etiquetaRol = computed(() => {
  if (esAdminGeneral.value) {
    return 'Administrador general'
  }

  if (esAdminServicios.value) {
    return 'Administrador de servicios'
  }

  if (esSecretario.value) {
    return 'Secretario de sindicato'
  }

  if (esConductor.value) {
    return 'Mototaxista'
  }

  if (esPasajero.value) {
    return 'Pasajero'
  }

  return 'Usuario'
})

const tituloPanel = computed(() => {
  if (esAdminGeneral.value) {
    return 'ADMINISTRACIÓN GENERAL'
  }

  if (esAdminServicios.value) {
    return 'ADMINISTRACIÓN DE SERVICIOS'
  }

  if (esSecretario.value) {
    return (
      usuario.value?.sindicato_nombre
        ? `SINDICATO ${usuario.value.sindicato_nombre}`
        : 'SECRETARÍA DE SINDICATO'
    )
  }

  if (esConductor.value) {
    return 'PANEL MOTOTAXISTA'
  }

  if (esPasajero.value) {
    return 'PANEL PASAJERO'
  }

  return 'PANEL MOTRIX'
})


const iconoRol = computed(() => {
  if (esConductor.value) return 'two_wheeler'
  if (esPasajero.value) return 'person_pin_circle'
  if (esSecretario.value) return 'badge'
  if (esAdminServicios.value) return 'support_agent'
  return 'admin_panel_settings'
})

const menuAdministradorGeneral = [
  {
    titulo: 'Control operativo',
    opciones: [
      { etiqueta: 'Monitoreo', icono: 'monitoring', ruta: '/monitoreo', exact: true },
      { etiqueta: 'Solicitudes', icono: 'add_road', ruta: '/solicitudes' },
      { etiqueta: 'Servicios', icono: 'local_taxi', ruta: '/servicios' },
      { etiqueta: 'Incidencias', icono: 'sos', ruta: '/incidencias' }
    ]
  },
  {
    titulo: 'Registro y control',
    opciones: [
      { etiqueta: 'Personas', icono: 'groups', ruta: '/personas' },
      { etiqueta: 'Pasajeros', icono: 'person_pin_circle', ruta: '/pasajeros' },
      { etiqueta: 'Federaciones', icono: 'account_tree', ruta: '/federaciones' },
      { etiqueta: 'Sindicatos', icono: 'business', ruta: '/sindicatos' },
      { etiqueta: 'Mototaxistas', icono: 'sports_motorsports', ruta: '/mototaxistas' },
      { etiqueta: 'Motocicletas', icono: 'motorcycle', ruta: '/motocicletas' },
      { etiqueta: 'Usuarios', icono: 'manage_accounts', ruta: '/usuarios' }
    ]
  },
  {
    titulo: 'Movimiento y finanzas',
    opciones: [
      { etiqueta: 'Pagos de viajes', icono: 'payments', ruta: '/pagos' },
      { etiqueta: 'Pagos sindicales', icono: 'account_balance_wallet', ruta: '/pagos-sindicales' },
      { etiqueta: 'Reportes', icono: 'bar_chart', ruta: '/reportes' }
    ]
  }
]

const menuSecretario = [
  {
    titulo: 'Mi sindicato',
    opciones: [
      {
        etiqueta: 'Datos del sindicato',
        icono: 'business',
        ruta: '/sindicatos'
      }
    ]
  },
  {
    titulo: 'Registro y control',
    opciones: [
      {
        etiqueta: 'Personas / afiliados',
        icono: 'groups',
        ruta: '/personas'
      },
      {
        etiqueta: 'Mototaxistas',
        icono: 'sports_motorsports',
        ruta: '/mototaxistas'
      },
      {
        etiqueta: 'Motocicletas',
        icono: 'motorcycle',
        ruta: '/motocicletas'
      }
    ]
  },
  {
    titulo: 'Finanzas del sindicato',
    opciones: [
      {
        etiqueta: 'Pagos y aportes',
        icono: 'account_balance_wallet',
        ruta: '/pagos-sindicales'
      }
    ]
  },
  {
    titulo: 'Seguridad',
    opciones: [
      {
        etiqueta: 'Incidencias',
        icono: 'sos',
        ruta: '/incidencias'
      }
    ]
  }
]

const menuAdminServicios = [
  {
    titulo: 'Clientes y operación',
    opciones: [
      {
        etiqueta: 'Personas / clientes',
        icono: 'groups',
        ruta: '/personas'
      },
      {
        etiqueta: 'Pasajeros / clientes',
        icono: 'person_pin_circle',
        ruta: '/pasajeros'
      },
      {
        etiqueta: 'Solicitudes',
        icono: 'add_road',
        ruta: '/solicitudes'
      },
      {
        etiqueta: 'Servicios',
        icono: 'local_taxi',
        ruta: '/servicios'
      }
    ]
  },
  {
    titulo: 'Movimiento y reportes',
    opciones: [
      {
        etiqueta: 'Pagos',
        icono: 'payments',
        ruta: '/pagos'
      },
      {
        etiqueta: 'Reportes',
        icono: 'bar_chart',
        ruta: '/reportes'
      }
    ]
  }
]

const menuConductor = [
  {
    titulo: 'Operación en ruta',
    opciones: [
      {
        etiqueta: 'Panel Mototaxista',
        icono: 'two_wheeler',
        ruta: '/conductor',
        exact: true,
        color: 'positive',
        activeClass: 'menu-item-active'
      }
    ]
  },
  {
    titulo: 'Mi actividad',
    opciones: [
      {
        etiqueta: 'Ganancias e historial',
        icono: 'account_balance_wallet',
        ruta: '/conductor/ganancias',
        exact: true,
        color: 'positive',
        activeClass: 'menu-item-active'
      }
    ]
  },
  {
    titulo: 'Mi cuenta',
    opciones: [
      {
        etiqueta: 'Mi perfil',
        icono: 'account_circle',
        ruta: '/conductor/perfil',
        exact: true,
        color: 'positive',
        activeClass: 'menu-item-active'
      }
    ]
  }
]

const menuPasajero = [
  {
    titulo: 'Mis viajes',
    opciones: [
      {
        etiqueta: 'Inicio',
        icono: 'home',
        ruta: '/pasajero',
        exact: true,
        color: 'positive',
        activeClass: 'menu-item-active'
      },
      {
        etiqueta: 'Solicitar mototaxi',
        icono: 'two_wheeler',
        ruta: '/pasajero/solicitar',
        exact: true,
        color: 'positive',
        activeClass: 'menu-item-active'
      },
      {
        etiqueta: 'Escanear QR',
        icono: 'qr_code_scanner',
        ruta: '/pasajero/escanear',
        exact: true,
        color: 'positive',
        activeClass: 'menu-item-active'
      },
      {
        etiqueta: 'Historial',
        icono: 'history',
        ruta: '/pasajero/historial',
        exact: true,
        color: 'positive',
        activeClass: 'menu-item-active'
      }
    ]
  },
  {
    titulo: 'Mi cuenta',
    opciones: [
      {
        etiqueta: 'Mi perfil',
        icono: 'account_circle',
        ruta: '/pasajero/perfil',
        exact: true,
        color: 'positive',
        activeClass: 'menu-item-active'
      }
    ]
  }
]

const seccionesMenu = computed(() => {
  if (esAdminGeneral.value) {
    return menuAdministradorGeneral
  }

  if (esAdminServicios.value) {
    return menuAdminServicios
  }

  if (esSecretario.value) {
    return menuSecretario
  }

  if (esConductor.value) {
    return menuConductor
  }

  if (esPasajero.value) {
    return menuPasajero
  }

  return []
})


function obtenerNombrePasajero(solicitud) {
  return (
    solicitud?.pasajero?.persona?.nombre
    || solicitud?.pasajero?.persona?.nombres
    || solicitud?.pasajero?.nombre
    || `Pasajero #${solicitud?.id_pasajero || '—'}`
  )
}

function obtenerNombreConductor(solicitud) {
  return (
    solicitud?.mototaxista?.persona?.nombre
    || solicitud?.mototaxista?.persona?.nombres
    || solicitud?.mototaxista?.nombre
    || (
      solicitud?.mototaxista_id
        ? `Conductor #${solicitud.mototaxista_id}`
        : 'Sin conductor'
    )
  )
}


function colorEstadoIncidencia(estado) {
  const valor = String(estado || '').trim().toLowerCase()

  if (valor === 'reportado') return 'negative'
  if (valor === 'recibido') return 'orange-9'
  if (valor === 'en atención') return 'primary'
  if (valor === 'resuelto') return 'positive'

  return 'grey-7'
}

function colorPrioridadIncidencia(prioridad) {
  const valor = String(prioridad || '').trim().toLowerCase()

  if (valor === 'crítica') return 'negative'
  if (valor === 'alta') return 'deep-orange-8'
  return 'amber-9'
}

function formatearFechaHoraIncidencia(fecha) {
  if (!fecha) return 'Fecha no disponible'

  const texto = String(fecha)
  const normalizada = texto.includes('T')
    ? new Date(texto)
    : new Date(texto.replace(' ', 'T'))

  if (Number.isNaN(normalizada.getTime())) {
    return texto
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
  ).format(normalizada)
}

function tieneUbicacionIncidencia(incidencia) {
  return (
    Number.isFinite(
      Number.parseFloat(incidencia?.latitud)
    )
    && Number.isFinite(
      Number.parseFloat(incidencia?.longitud)
    )
  )
}

function abrirUbicacionIncidencia(incidencia) {
  if (!tieneUbicacionIncidencia(incidencia)) return

  const latitud = Number.parseFloat(incidencia.latitud)
  const longitud = Number.parseFloat(incidencia.longitud)

  window.open(
    `https://www.openstreetmap.org/?mlat=${latitud}&mlon=${longitud}#map=18/${latitud}/${longitud}`,
    '_blank',
    'noopener,noreferrer'
  )
}

function eventoIncidenciaDuplicado(incidencia, tipo) {
  const ahora = Date.now()
  const clave = [
    tipo,
    incidencia?.id,
    incidencia?.estado
  ].join(':')

  if (
    ultimoEventoIncidencia.clave === clave
    && (
      ahora
      - ultimoEventoIncidencia.momento
    ) < 1200
  ) {
    return true
  }

  ultimoEventoIncidencia = {
    clave,
    momento: ahora
  }

  return false
}

function reproducirSonidoEmergencia() {
  habilitarSonidoAdministrador()

  if (
    !sonidoAdministradorHabilitado
    || !contextoAudioAdministrador
  ) {
    return
  }

  const inicio =
    contextoAudioAdministrador.currentTime
    + 0.03

  ;[
    [920, 0.00],
    [620, 0.18],
    [920, 0.38],
    [620, 0.56],
    [1040, 0.78]
  ].forEach(([frecuencia, desplazamiento]) => {
    emitirTonoAdministrador(
      frecuencia,
      inicio + desplazamiento,
      0.15,
      0.12
    )
  })
}

function emitirEventoGlobalIncidencia(
  origen,
  incidencia
) {
  window.dispatchEvent(
    new CustomEvent(
      'motrix:incidencia-cambio',
      {
        detail: {
          origen,
          incidencia
        }
      }
    )
  )
}

async function cargarResumenIncidencias() {
  if (!puedeGestionarIncidencias.value) return

  try {
    const response = await api.get('/incidencias')

    incidenciasActivasCantidad.value = Number(
      response?.data?.resumen?.activas || 0
    )

    const incidencias = Array.isArray(
      response?.data?.incidencias
    )
      ? response.data.incidencias
      : []

    const primeraActiva = incidencias.find(
      incidencia => [
        'Reportado',
        'Recibido',
        'En atención'
      ].includes(incidencia?.estado)
    )

    if (
      primeraActiva
      && !incidenciaSeleccionada.value
    ) {
      incidenciaSeleccionada.value = primeraActiva
    }
  } catch (error) {
    console.error(
      'No se pudo consultar el resumen de incidencias:',
      error
    )
  }
}

function mostrarAlertaIncidencia(incidencia) {
  incidenciaSeleccionada.value = incidencia
  incidenciaDialogOpen.value = true

  reproducirSonidoEmergencia()

  if (navigator.vibrate) {
    navigator.vibrate([
      250,
      100,
      250,
      100,
      400
    ])
  }

  $q.notify({
    color: 'negative',
    textColor: 'white',
    icon: 'sos',
    message:
      `ALERTA SOS · ${incidencia?.codigo || 'MOTRIX'}`,
    caption:
      `${incidencia?.tipo || 'Incidencia'} · Viaje #${
        incidencia?.solicitud_id || '—'
      } · ${incidencia?.reportado_por_nombre || 'Usuario'}`,
    position: 'top',
    timeout: 0,
    multiLine: true,
    actions: [
      {
        label: 'ATENDER',
        color: 'white',
        handler: () => {
          incidenciaSeleccionada.value = incidencia
          incidenciaDialogOpen.value = true
        }
      },
      {
        icon: 'close',
        color: 'white'
      }
    ]
  })
}

function procesarIncidenciaReportada(data) {
  const incidencia = data?.incidencia

  if (
    !incidencia
    || eventoIncidenciaDuplicado(
      incidencia,
      'reportada'
    )
  ) {
    return
  }

  incidenciasActivasCantidad.value += 1
  mostrarAlertaIncidencia(incidencia)

  emitirEventoGlobalIncidencia(
    'reportada',
    incidencia
  )
}

function procesarIncidenciaActualizada(data) {
  const incidencia = data?.incidencia

  if (
    !incidencia
    || eventoIncidenciaDuplicado(
      incidencia,
      'actualizada'
    )
  ) {
    return
  }

  if (
    Number(incidenciaSeleccionada.value?.id)
    === Number(incidencia.id)
  ) {
    incidenciaSeleccionada.value = incidencia
  }

  cargarResumenIncidencias()

  emitirEventoGlobalIncidencia(
    'actualizada',
    incidencia
  )

  $q.notify({
    type:
      incidencia.estado === 'Resuelto'
        ? 'positive'
        : 'info',
    icon:
      incidencia.estado === 'Resuelto'
        ? 'task_alt'
        : 'support_agent',
    message:
      `${incidencia.codigo || 'Incidencia'}: ${
        incidencia.estado
      }`,
    position: 'top-right',
    timeout: 4500
  })
}

async function cambiarEstadoIncidenciaRapido(
  estado,
  notaAdministrador = null
) {
  const incidenciaId =
    incidenciaSeleccionada.value?.id

  if (!incidenciaId || actualizandoIncidencia.value) {
    return
  }

  actualizandoIncidencia.value = true

  try {
    const response = await api.put(
      `/incidencias/${incidenciaId}/estado`,
      {
        estado,
        nota_administrador:
          notaAdministrador
      }
    )

    incidenciaSeleccionada.value =
      response?.data?.incidencia
      || incidenciaSeleccionada.value

    await cargarResumenIncidencias()

    $q.notify({
      type: 'positive',
      icon: 'check_circle',
      message:
        response?.data?.mensaje
        || 'Incidencia actualizada.',
      position: 'top'
    })
  } catch (error) {
    $q.notify({
      type: 'negative',
      icon: 'error',
      message:
        error?.response?.data?.message
        || 'No se pudo actualizar la incidencia.',
      position: 'top'
    })
  } finally {
    actualizandoIncidencia.value = false
  }
}

function confirmarResolucionIncidencia() {
  $q.dialog({
    title: 'Resolver incidencia',
    message:
      'Agrega una nota breve sobre la atención realizada.',
    prompt: {
      model: '',
      type: 'textarea',
      outlined: true,
      label: 'Nota administrativa'
    },
    cancel: true,
    persistent: true,
    ok: {
      label: 'Marcar resuelta',
      color: 'positive'
    }
  }).onOk((nota) => {
    cambiarEstadoIncidenciaRapido(
      'Resuelto',
      String(nota || '').trim() || null
    )
  })
}

function abrirCentroIncidenciaActual() {
  if (incidenciaSeleccionada.value) {
    incidenciaDialogOpen.value = true
    return
  }

  irCentroIncidencias()
}

function irCentroIncidencias() {
  incidenciaDialogOpen.value = false

  router.push({
    path: '/monitoreo',
    hash: '#centro-incidencias'
  })
}

function publicarEstadoWebsocket(conectado) {
  adminWsConectado.value = Boolean(conectado)
  window.__MOTRIX_ADMIN_WS_CONNECTED__ = Boolean(conectado)

  window.dispatchEvent(
    new CustomEvent(
      'motrix:ws-status',
      {
        detail: {
          conectado: Boolean(conectado)
        }
      }
    )
  )
}

function habilitarSonidoAdministrador() {
  sonidoAdministradorHabilitado = true

  try {
    const AudioContextClass =
      window.AudioContext
      || window.webkitAudioContext

    if (!AudioContextClass) return

    if (!contextoAudioAdministrador) {
      contextoAudioAdministrador =
        new AudioContextClass()
    }

    if (
      contextoAudioAdministrador.state
      === 'suspended'
    ) {
      contextoAudioAdministrador
        .resume()
        .catch(() => {})
    }
  } catch (error) {
    console.warn(
      'No se pudo habilitar el sonido administrativo:',
      error
    )
  }
}

function emitirTonoAdministrador(
  frecuencia,
  inicio,
  duracion,
  volumen = 0.08
) {
  if (
    !sonidoAdministradorHabilitado
    || !contextoAudioAdministrador
  ) {
    return
  }

  const oscilador =
    contextoAudioAdministrador
      .createOscillator()

  const ganancia =
    contextoAudioAdministrador
      .createGain()

  oscilador.type = 'sine'
  oscilador.frequency.value = frecuencia

  ganancia.gain.setValueAtTime(
    0.0001,
    inicio
  )

  ganancia.gain.exponentialRampToValueAtTime(
    volumen,
    inicio + 0.015
  )

  ganancia.gain.exponentialRampToValueAtTime(
    0.0001,
    inicio + duracion
  )

  oscilador.connect(ganancia)
  ganancia.connect(
    contextoAudioAdministrador.destination
  )

  oscilador.start(inicio)
  oscilador.stop(inicio + duracion + 0.03)
}

function reproducirSonidoAdministrador(tipo) {
  habilitarSonidoAdministrador()

  if (
    !sonidoAdministradorHabilitado
    || !contextoAudioAdministrador
  ) {
    return
  }

  const ahora =
    contextoAudioAdministrador.currentTime
    + 0.03

  const esAlerta = [
    'cancelado_por_pasajero',
    'cancelado_por_conductor',
    'conductor_rechazo',
    'solicitud_expirada'
  ].includes(tipo)

  const esFinalizacion =
    tipo === 'viaje_finalizado'

  if (esAlerta) {
    emitirTonoAdministrador(
      720,
      ahora,
      0.16,
      0.09
    )

    emitirTonoAdministrador(
      520,
      ahora + 0.20,
      0.20,
      0.09
    )

    return
  }

  if (esFinalizacion) {
    emitirTonoAdministrador(
      660,
      ahora,
      0.13
    )

    emitirTonoAdministrador(
      820,
      ahora + 0.15,
      0.13
    )

    emitirTonoAdministrador(
      980,
      ahora + 0.30,
      0.18
    )

    return
  }

  emitirTonoAdministrador(
    760,
    ahora,
    0.14
  )

  emitirTonoAdministrador(
    960,
    ahora + 0.17,
    0.18
  )
}

function crearConfiguracionNotificacion(
  tipo,
  solicitud
) {
  const id = solicitud?.id || '—'
  const pasajero =
    obtenerNombrePasajero(solicitud)

  const conductor =
    obtenerNombreConductor(solicitud)

  const configuraciones = {
    nueva_solicitud: {
      color: 'positive',
      icono: 'add_location_alt',
      titulo: `Nueva solicitud #${id}`,
      detalle:
        `${pasajero} solicitó un viaje desde `
        + (
          solicitud?.origen
          || 'un origen no especificado'
        )
    },

    conductor_acepto: {
      color: 'positive',
      icono: 'check_circle',
      titulo: `Conductor aceptó el viaje #${id}`,
      detalle:
        `${conductor} aceptó la solicitud de ${pasajero}.`
    },

    conductor_llego: {
      color: 'teal-8',
      icono: 'person_pin_circle',
      titulo: `Conductor llegó · Viaje #${id}`,
      detalle:
        `${conductor} llegó al punto de recogida.`
    },

    viaje_iniciado: {
      color: 'primary',
      icono: 'navigation',
      titulo: `Viaje #${id} iniciado`,
      detalle:
        `${conductor} inició el traslado de ${pasajero}.`
    },

    viaje_finalizado: {
      color: 'dark',
      icono: 'paid',
      titulo: `Viaje #${id} finalizado`,
      detalle:
        `Se completó el viaje por Bs. ${
          Number(solicitud?.precio || 0).toFixed(2)
        }.`
    },

    cancelado_por_pasajero: {
      color: 'negative',
      icono: 'person_remove',
      titulo: `Pasajero canceló el viaje #${id}`,
      detalle:
        `${pasajero} canceló la solicitud.`
    },

    cancelado_por_conductor: {
      color: 'negative',
      icono: 'cancel',
      titulo: `Conductor canceló el viaje #${id}`,
      detalle:
        `${conductor} canceló la atención.`
    },

    conductor_reasignado: {
      color: 'orange-9',
      icono: 'swap_horiz',
      titulo: `Solicitud #${id} reasignada`,
      detalle:
        `La solicitud ahora fue enviada a ${conductor}.`
    },

    conductor_rechazo: {
      color: 'orange-9',
      icono: 'warning',
      titulo: `Solicitud #${id} sin conductor`,
      detalle:
        'El conductor rechazó y no se encontró una reasignación inmediata.'
    },

    solicitud_expirada: {
      color: 'grey-8',
      icono: 'timer_off',
      titulo: `Solicitud #${id} expirada`,
      detalle:
        'Terminó el tiempo de espera sin completar la asignación.'
    },

    actualizacion_admin: {
      color: 'blue-grey-8',
      icono: 'edit_note',
      titulo: `Solicitud #${id} actualizada`,
      detalle:
        'Otro administrador modificó los datos de la solicitud.'
    },

    estado_actualizado: {
      color: 'primary',
      icono: 'sync',
      titulo: `Solicitud #${id} actualizada`,
      detalle:
        `Nuevo estado: ${solicitud?.estado || 'sin estado'}.`
    }
  }

  return (
    configuraciones[tipo]
    || configuraciones.estado_actualizado
  )
}

function eventoDuplicado(tipo, solicitud) {
  const ahora = Date.now()

  const clave = [
    tipo,
    solicitud?.id,
    solicitud?.estado,
    solicitud?.mototaxista_id
  ].join(':')

  if (
    ultimoEventoAdministrador.clave === clave
    && (
      ahora
      - ultimoEventoAdministrador.momento
    ) < 1200
  ) {
    return true
  }

  ultimoEventoAdministrador = {
    clave,
    momento: ahora
  }

  return false
}

function emitirEventoGlobal(
  origen,
  tipo,
  solicitud
) {
  const detalle = {
    origen,
    tipo,
    solicitud
  }

  window.dispatchEvent(
    new CustomEvent(
      'motrix:solicitud-cambio',
      {
        detail: detalle
      }
    )
  )

  window.dispatchEvent(
    new CustomEvent(
      origen === 'creada'
        ? 'motrix:solicitud-creada'
        : 'motrix:solicitud-actualizada',
      {
        detail: detalle
      }
    )
  )
}

function mostrarNotificacionAdministrador(
  tipo,
  solicitud
) {
  if (eventoDuplicado(tipo, solicitud)) {
    return
  }

  const configuracion =
    crearConfiguracionNotificacion(
      tipo,
      solicitud
    )

  reproducirSonidoAdministrador(tipo)

  if (
    navigator.vibrate
    && document.visibilityState !== 'visible'
  ) {
    navigator.vibrate(
      tipo.includes('cancelado')
        ? [180, 100, 180]
        : [140, 80, 140]
    )
  }

  $q.notify({
    color: configuracion.color,
    textColor: 'white',
    icon: configuracion.icono,
    message: configuracion.titulo,
    caption: configuracion.detalle,
    position: 'top-right',
    timeout: 7500,
    multiLine: true,
    actions: [
      {
        label: 'VER',
        color: 'white',
        handler: () => {
          router.push('/solicitudes')
        }
      }
    ]
  })
}

function procesarSolicitudCreada(data) {
  const solicitud = data?.solicitud

  if (!solicitud) return

  mostrarNotificacionAdministrador(
    'nueva_solicitud',
    solicitud
  )

  emitirEventoGlobal(
    'creada',
    'nueva_solicitud',
    solicitud
  )
}

function procesarSolicitudActualizada(data) {
  const solicitud = data?.solicitud

  if (!solicitud) return

  const tipo =
    data?.tipo
    || 'estado_actualizado'

  mostrarNotificacionAdministrador(
    tipo,
    solicitud
  )

  emitirEventoGlobal(
    'actualizada',
    tipo,
    solicitud
  )
}

function inicializarNotificacionesAdministrador() {
  if (
    !puedeGestionarIncidencias.value
    || echoAdministrador
  ) {
    return
  }

  try {
    echoAdministrador = new Echo({
      ...echoOptions(),
      authorizer: channel => ({
        authorize: (socketId, callback) => {
          api.post(
            '/broadcasting/auth',
            {
              socket_id: socketId,
              channel_name: channel.name
            }
          )
            .then(response => {
              callback(false, response.data)
            })
            .catch(error => {
              callback(true, error)
            })
        }
      })
    })

    const conexion =
      echoAdministrador
        ?.connector
        ?.pusher
        ?.connection

    conexion?.bind(
      'connected',
      () => publicarEstadoWebsocket(true)
    )

    conexion?.bind(
      'disconnected',
      () => publicarEstadoWebsocket(false)
    )

    conexion?.bind(
      'unavailable',
      () => publicarEstadoWebsocket(false)
    )

    conexion?.bind(
      'error',
      () => publicarEstadoWebsocket(false)
    )

    if (esAdminGeneral.value) {
      echoAdministrador
        .channel('solicitudes')
        .listen(
          '.SolicitudCreada',
          procesarSolicitudCreada
        )
        .listen(
          '.SolicitudActualizada',
          procesarSolicitudActualizada
        )
    }

    const canalIncidencias =
      esSecretario.value
        ? `sindicato.${Number(
            usuario.value?.sindicato_id || 0
          )}.incidencias`
        : 'administracion.incidencias'

    echoAdministrador
      .private(canalIncidencias)
      .listen(
        '.IncidenciaViajeReportada',
        procesarIncidenciaReportada
      )
      .listen(
        '.IncidenciaViajeActualizada',
        procesarIncidenciaActualizada
      )

    cargarResumenIncidencias()

    publicarEstadoWebsocket(
      conexion?.state === 'connected'
    )
  } catch (error) {
    console.error(
      'Error iniciando las notificaciones administrativas:',
      error
    )

    publicarEstadoWebsocket(false)
  }
}

function desconectarNotificacionesAdministrador() {
  if (echoAdministrador) {
    echoAdministrador.leaveChannel(
      'solicitudes'
    )

    const canalIncidencias =
      esSecretario.value
        ? `sindicato.${Number(
            usuario.value?.sindicato_id || 0
          )}.incidencias`
        : 'administracion.incidencias'

    echoAdministrador.leave(
      canalIncidencias
    )

    echoAdministrador.disconnect()
    echoAdministrador = null
  }

  publicarEstadoWebsocket(false)

  if (contextoAudioAdministrador) {
    contextoAudioAdministrador
      .close()
      .catch(() => {})

    contextoAudioAdministrador = null
  }

  sonidoAdministradorHabilitado = false
}

function esRutaConductorActiva(
  rutaObjetivo,
  exacta = false
) {
  if (exacta) {
    return route.path === rutaObjetivo
  }

  return route.path.startsWith(
    rutaObjetivo
  )
}

function navegarConductor(rutaDestino) {
  if (route.path !== rutaDestino) {
    router.push(rutaDestino)
  }

  cerrarDrawerMovil()
}

function abrirPerfilActual() {
  if (esPasajero.value) {
    navegarPasajero('/pasajero/perfil')
    return
  }

  if (esConductor.value) {
    navegarConductor('/conductor/perfil')
  }
}

function esRutaPasajeroActiva(
  rutaObjetivo,
  exacta = false
) {
  if (exacta) {
    return route.path === rutaObjetivo
  }

  return route.path.startsWith(
    rutaObjetivo
  )
}

function navegarPasajero(rutaDestino) {
  if (route.path !== rutaDestino) {
    router.push(rutaDestino)
  }

  cerrarDrawerMovil()
}

function cerrarDrawerMovil() {
  if ($q.screen.lt.md) {
    leftDrawerOpen.value = false
  }
}

function toggleLeftDrawer() {
  leftDrawerOpen.value = !leftDrawerOpen.value
}

function limpiarSesionLocal() {
  localStorage.removeItem('motrix_token')
  localStorage.removeItem('motrix_user')
  localStorage.removeItem('mototaxista_id')
  localStorage.removeItem('pasajero_id')
}

async function cerrarSesion() {
  if (cerrandoSesion.value) return

  cerrandoSesion.value = true

  try {
    await api.post('/auth/logout')
  } catch (error) {
    const estado = error?.response?.status

    if (estado !== 401) {
      console.warn('No se pudo cerrar la sesión en el servidor:', error)
    }
  } finally {
    limpiarSesionLocal()
    usuario.value = null
    cerrandoSesion.value = false

    await router.replace('/inicio')

    $q.notify({
      type: 'positive',
      icon: 'logout',
      message: 'Sesión cerrada correctamente.',
      position: 'top' 
    })
  }
}

onMounted(() => {
  if (!puedeGestionarIncidencias.value) return

  document.addEventListener(
    'pointerdown',
    habilitarSonidoAdministrador,
    {
      once: true,
      capture: true
    }
  )

  document.addEventListener(
    'keydown',
    habilitarSonidoAdministrador,
    {
      once: true,
      capture: true
    }
  )

  inicializarNotificacionesAdministrador()
})

onBeforeUnmount(() => {
  document.removeEventListener(
    'pointerdown',
    habilitarSonidoAdministrador,
    true
  )

  document.removeEventListener(
    'keydown',
    habilitarSonidoAdministrador,
    true
  )

  desconectarNotificacionesAdministrador()
})

</script>

<style scoped>
.motrix-layout {
  --motrix-green: #2e7d32;
  --motrix-green-dark: #0a2e0a;
  --motrix-green-medium: #1b5e20;
  --motrix-green-light: #81c784;
  --motrix-red: #c62828;
  --motrix-background: #f1f8e9;
}

.motrix-header {
  background:
    linear-gradient(
      135deg,
      #1b5e20 0%,
      #2e7d32 60%,
      #388e3c 100%
    );
  border-bottom: 3px solid #c62828;
  box-shadow:
    0 6px 20px rgba(0, 0, 0, 0.25);
}

.motrix-toolbar {
  min-height: 72px;
  padding-right: 18px;
  padding-left: 14px;
}

.brand-title {
  font-size: 24px;
  font-weight: 800;
  letter-spacing: 0.025em;
}

.brand-subtitle {
  padding-top: 4px;
  color: #c8e6c9;
  font-size: 12px;
  font-weight: 500;
}

.motrix-drawer {
  color: #ffffff !important;
  background: #0a2e0a !important;
  border-right: 1px solid #1b5e20;
}

.drawer-content {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.drawer-brand {
  position: relative;
  padding: 26px 18px 22px;
  text-align: center;
  background:
    linear-gradient(
      180deg,
      rgba(27, 94, 32, 0.36),
      rgba(10, 46, 10, 0)
    );
}

.drawer-brand::after {
  content: '';
  position: absolute;
  right: 18px;
  bottom: 0;
  left: 18px;
  height: 3px;
  border-radius: 999px;
  background:
    linear-gradient(
      90deg,
      #c62828,
      rgba(198, 40, 40, 0.15),
      transparent
    );
}

.drawer-logo {
  color: #66bb6a;
  filter:
    drop-shadow(
      0 3px 5px rgba(0, 0, 0, 0.32)
    );
}

.drawer-brand-caption {
  margin-top: 3px;
  color: #a5d6a7;
}

.drawer-user {
  display: flex;
  align-items: center;
  margin: 12px 12px 4px;
  padding: 13px 14px;
  color: #ffffff;
  background: rgba(255, 255, 255, 0.075);
  border: 1px solid rgba(129, 199, 132, 0.16);
  border-radius: 12px;
}

.drawer-user-name {
  color: #a5d6a7;
}

.drawer-separator {
  margin: 8px 14px 0;
  opacity: 1;
  background: rgba(102, 187, 106, 0.28);
}

.drawer-section-title {
  color: #66bb6a !important;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.055em;
}

.menu-item {
  min-height: 42px;
  margin: 0 6px;
  color: #ffffff !important;
  border-radius: 8px;
  transition:
    background-color 0.18s ease,
    transform 0.18s ease,
    box-shadow 0.18s ease;
}

.menu-item :deep(.q-icon) {
  color: #66bb6a;
}

.menu-item:hover {
  color: #ffffff !important;
  background: rgba(46, 125, 50, 0.25);
  transform: translateX(2px);
}

:deep(.menu-item-active) {
  color: #ffffff !important;
  background: rgba(46, 125, 50, 0.38) !important;
  border-left: 4px solid #c62828;
  box-shadow:
    0 0 15px rgba(198, 40, 40, 0.23);
}

:deep(.menu-item-active .q-icon) {
  color: #a5d6a7 !important;
}

.drawer-footer {
  padding: 13px 11px 15px;
  background: rgba(0, 0, 0, 0.12);
  border-top: 1px solid rgba(102, 187, 106, 0.22);
}

.logout-button {
  color: #ff8a80 !important;
  border-radius: 7px;
}

.drawer-footer-text {
  color: #81c784;
  line-height: 1.45;
}

.motrix-page-container {
  min-height: 100vh;
  background:
    radial-gradient(
      circle at 95% 0%,
      rgba(129, 199, 132, 0.12),
      transparent 25%
    ),
    #f1f8e9;
}

.min-width-zero {
  min-width: 0;
}

/*
|--------------------------------------------------------------------------
| ADAPTACIÓN VISUAL DE LAS PÁGINAS EXISTENTES
|--------------------------------------------------------------------------
*/

:deep(.q-page.bg-grey-2) {
  background: transparent !important;
}

:deep(.bg-primary) {
  background: #2e7d32 !important;
}

:deep(.text-primary) {
  color: #2e7d32 !important;
}

:deep(.q-btn.bg-primary) {
  background: #2e7d32 !important;
}

:deep(.q-btn.text-primary) {
  color: #2e7d32 !important;
}

:deep(.q-card) {
  border-color: #d4e2d1;
}

:deep(.q-field--outlined .q-field__control:hover::before) {
  border-color: #388e3c;
}

.incidencia-global-card {
  width: min(720px, 96vw);
  max-width: 720px;
  max-height: 92vh;
  overflow-y: auto;
  border-radius: 16px;
}

.incidencia-global-dato {
  display: flex;
  flex-direction: column;
  min-height: 74px;
  padding: 12px 14px;
  gap: 5px;
  border: 1px solid #eadcdc;
  border-radius: 11px;
  background: #fffafa;
}

.incidencia-global-dato span {
  color: #8b7777;
  font-size: 12px;
}

.incidencia-global-dato strong {
  color: #3f2525;
  overflow-wrap: anywhere;
}

/* =========================================================
   NAVEGACIÓN INFERIOR DEL CONDUCTOR
========================================================= */
.driver-bottom-footer {
  color: #4f5f50;
  background: #ffffff !important;
  border-top: 1px solid #d5e4d2;
}

.driver-bottom-nav {
  min-height: 68px;
  display: grid;
  grid-template-columns:
    repeat(3, minmax(0, 1fr));
  align-items: center;
  padding:
    5px
    max(8px, env(safe-area-inset-right))
    calc(5px + env(safe-area-inset-bottom))
    max(8px, env(safe-area-inset-left));
}

.driver-nav-item {
  min-height: 54px;
  color: #718072;
  font-size: 10px;
  border-radius: 12px;
}

.driver-nav-item :deep(.q-icon) {
  font-size: 24px;
}

.driver-nav-active {
  color: #2e7d32 !important;
  font-weight: 900;
  background: #edf7eb;
}

/* =========================================================
   NAVEGACIÓN INFERIOR DEL PASAJERO
========================================================= */
.passenger-bottom-footer {
  color: #4f5f50;
  background: #ffffff !important;
  border-top: 1px solid #d5e4d2;
}

.passenger-bottom-nav {
  position: relative;
  min-height: 68px;
  display: grid;
  grid-template-columns:
    repeat(5, minmax(0, 1fr));
  align-items: end;
  padding:
    5px
    max(4px, env(safe-area-inset-right))
    calc(5px + env(safe-area-inset-bottom))
    max(4px, env(safe-area-inset-left));
}

.passenger-nav-item {
  min-height: 54px;
  color: #718072;
  font-size: 10px;
  border-radius: 12px;
}

.passenger-nav-item :deep(.q-icon) {
  font-size: 23px;
}

.passenger-nav-active {
  color: #2e7d32 !important;
  font-weight: 800;
}

.passenger-qr-slot {
  min-height: 63px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  color: #718072;
  font-size: 10px;
  font-weight: 700;
}

.passenger-qr-button {
  width: 54px;
  height: 54px;
  margin-top: -25px;
  margin-bottom: 1px;
  border: 4px solid #ffffff;
  box-shadow:
    0 7px 18px rgba(46, 125, 50, 0.32);
}

.passenger-qr-label-active {
  color: #2e7d32;
  font-weight: 900;
}

@media (max-width: 599px) {
  .motrix-toolbar {
    min-height: 60px;
    padding-right: 8px;
    padding-left: 8px;
  }

  .brand-title {
    font-size: 20px;
  }

  .incidencia-global-card {
    width: 100vw;
    max-width: none;
    max-height: 100vh;
    border-radius: 0;
  }
}

/* =========================================================
   CORRECCIÓN DE CONTRASTE DEL MENÚ LATERAL
   Mantiene el estilo original de la parte sindical.
========================================================= */
:deep(.q-drawer.motrix-drawer),
:deep(.motrix-drawer .q-drawer__content) {
  background: #0A2E0A !important;
  color: #ffffff !important;
}

.motrix-drawer .drawer-brand {
  color: #ffffff !important;
  background: transparent !important;
}

.motrix-drawer .drawer-user {
  background: rgba(255, 255, 255, 0.07) !important;
  border: 1px solid rgba(102, 187, 106, 0.20) !important;
  color: #ffffff !important;
}

.motrix-drawer .drawer-user-name,
.motrix-drawer .drawer-brand-caption {
  color: #81C784 !important;
}

.motrix-drawer .drawer-section-title {
  color: #66BB6A !important;
  opacity: 1 !important;
}

.motrix-drawer .menu-item {
  color: #ffffff !important;
  margin: 0 6px 4px !important;
  min-height: 46px;
  border-radius: 8px;
  opacity: 1 !important;
}

.motrix-drawer .menu-item :deep(.q-item__section),
.motrix-drawer .menu-item :deep(.q-item__label) {
  color: #ffffff !important;
  opacity: 1 !important;
}

.motrix-drawer .menu-item :deep(.q-icon) {
  color: #66BB6A !important;
  opacity: 1 !important;
}

.motrix-drawer .menu-item:hover {
  background: rgba(46, 125, 50, 0.26) !important;
}

.motrix-drawer :deep(.menu-item-active) {
  background: rgba(46, 125, 50, 0.48) !important;
  border-left: 4px solid #C62828 !important;
  box-shadow: 0 0 15px rgba(198, 40, 40, 0.25) !important;
}

.motrix-drawer :deep(.menu-item-active .q-item__section),
.motrix-drawer :deep(.menu-item-active .q-item__label) {
  color: #ffffff !important;
  font-weight: 700 !important;
}

.motrix-drawer :deep(.menu-item-active .q-icon) {
  color: #A5D6A7 !important;
}

.motrix-drawer .drawer-footer {
  background: rgba(0, 0, 0, 0.16) !important;
  border-top: 1px solid rgba(102, 187, 106, 0.20) !important;
}

.motrix-drawer .drawer-footer-text {
  color: #66BB6A !important;
}

</style>