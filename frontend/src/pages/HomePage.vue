<template>
  <q-page class="q-pa-lg bg-grey-2">
    <div class="container-dashboard">

      <!-- ENCABEZADO -->
      <div class="text-center q-mb-xl">
        <div
          class="text-h2 text-primary text-bold
                 tracking-tight text-uppercase"
        >
          MOTRIX
        </div>

        <div
          class="text-subtitle1 text-grey-8
                 q-mt-sm max-width-md mx-auto"
        >
          Sistema web y móvil para la administración del
          servicio de mototaxis de la ciudad de Trinidad.
        </div>
      </div>

      <!-- ESTADO Y ACTUALIZACIÓN -->
      <div
        class="row justify-end items-center
               q-mb-md q-gutter-sm"
      >
        <q-chip
          :color="wsConnected ? 'positive' : 'negative'"
          text-color="white"
          icon="sensors"
          size="sm"
          class="text-bold"
        >
          {{
            wsConnected
              ? 'CANAL EN VIVO ACTIVO'
              : 'CONECTANDO WS...'
          }}
        </q-chip>

        <q-btn
          color="primary"
          icon="refresh"
          label="Actualizar Estadísticas"
          class="text-bold shadow-1"
          flat
          dense
          :loading="loading"
          @click="cargarDatosDashboard"
        />
      </div>

      <!-- TARJETAS DE ESTADÍSTICAS -->
      <div class="q-mb-xl">

        <!-- FILA PRINCIPAL -->
        <div class="row q-col-gutter-md q-mb-md">
          <div
            v-for="card in mainCards"
            :key="card.title"
            class="col-12 col-sm-6 col-md-3"
          >
            <q-card
              v-ripple
              class="my-dashboard-card
                     text-center shadow-2 cursor-pointer"
              @click="goToModule(card.route)"
            >
              <q-card-section
                class="q-pa-md flex flex-center column"
              >
                <q-avatar
                  size="56px"
                  :color="card.bgColor"
                  :text-color="card.color"
                  class="q-mb-sm"
                >
                  <q-icon
                    :name="card.icon"
                    size="28px"
                  />
                </q-avatar>

                <div
                  class="text-caption text-grey-7
                         text-uppercase text-bold
                         tracking-wider"
                >
                  {{ card.title }}
                </div>

                <div
                  class="text-h4 text-bold q-mt-xs"
                  :class="`text-${card.color}`"
                >
                  {{ stats[card.key] }}
                </div>
              </q-card-section>
            </q-card>
          </div>
        </div>

        <!-- FILA OPERACIONAL -->
        <div
          class="row q-col-gutter-md justify-center"
        >
          <div
            v-for="card in operationalCards"
            :key="card.title"
            class="col-12 col-sm-4 col-md"
          >
            <q-card
              v-ripple
              class="my-dashboard-card
                     text-center shadow-2 cursor-pointer"
              @click="goToModule(card.route)"
            >
              <q-card-section
                class="q-pa-md flex flex-center column"
              >
                <q-avatar
                  size="52px"
                  :color="card.bgColor"
                  :text-color="card.color"
                  class="q-mb-sm"
                >
                  <q-icon
                    :name="card.icon"
                    size="24px"
                  />
                </q-avatar>

                <div
                  class="text-caption text-grey-7
                         text-uppercase text-bold
                         tracking-wider"
                >
                  {{ card.title }}
                </div>

                <div
                  class="text-h5 text-bold q-mt-xs"
                  :class="`text-${card.color}`"
                >
                  <span
                    v-if="card.key === 'pagos'"
                    class="text-subtitle1 text-bold"
                  >
                    Bs.
                  </span>

                  {{ stats[card.key] }}
                </div>
              </q-card-section>
            </q-card>
          </div>
        </div>
      </div>

      <!-- CENTRO DE INCIDENCIAS SOS -->
      <div
        id="centro-incidencias"
        ref="centroIncidenciasElemento"
        class="q-mb-xl centro-incidencias-anchor"
      >
        <q-card
          class="shadow-3 centro-incidencias-card overflow-hidden"
        >
          <q-card-section
            class="bg-negative text-white row items-center q-col-gutter-md"
          >
            <div class="col">
              <div class="row items-center no-wrap">
                <q-avatar
                  color="white"
                  text-color="negative"
                  icon="sos"
                  size="48px"
                  class="q-mr-md"
                />

                <div class="min-width-zero">
                  <div class="text-h6 text-weight-bold">
                    Centro de incidencias SOS
                  </div>

                  <div class="text-caption text-red-1">
                    Atención y seguimiento de alertas reportadas
                    durante los viajes
                  </div>
                </div>
              </div>
            </div>

            <div class="col-auto">
              <q-btn
                color="white"
                text-color="negative"
                icon="refresh"
                label="Actualizar"
                no-caps
                unelevated
                :loading="cargandoIncidencias"
                @click="cargarIncidencias(true)"
              />
            </div>
          </q-card-section>

          <q-card-section class="q-pa-md">
            <div class="row q-col-gutter-sm q-mb-md">
              <div class="col-6 col-md-3">
                <div class="resumen-incidencia resumen-incidencia-reportada">
                  <q-icon name="notification_important" size="27px" />

                  <div>
                    <div class="text-h5 text-weight-bold">
                      {{ resumenIncidencias.reportadas }}
                    </div>
                    <div class="text-caption">Reportadas</div>
                  </div>
                </div>
              </div>

              <div class="col-6 col-md-3">
                <div class="resumen-incidencia resumen-incidencia-recibida">
                  <q-icon name="done" size="27px" />

                  <div>
                    <div class="text-h5 text-weight-bold">
                      {{ resumenIncidencias.recibidas }}
                    </div>
                    <div class="text-caption">Recibidas</div>
                  </div>
                </div>
              </div>

              <div class="col-6 col-md-3">
                <div class="resumen-incidencia resumen-incidencia-atencion">
                  <q-icon name="support_agent" size="27px" />

                  <div>
                    <div class="text-h5 text-weight-bold">
                      {{ resumenIncidencias.en_atencion }}
                    </div>
                    <div class="text-caption">En atención</div>
                  </div>
                </div>
              </div>

              <div class="col-6 col-md-3">
                <div class="resumen-incidencia resumen-incidencia-resuelta">
                  <q-icon name="task_alt" size="27px" />

                  <div>
                    <div class="text-h5 text-weight-bold">
                      {{ resumenIncidencias.resueltas }}
                    </div>
                    <div class="text-caption">Resueltas</div>
                  </div>
                </div>
              </div>
            </div>

            <div class="row q-col-gutter-sm q-mb-md">
              <div class="col-12 col-sm-4">
                <q-select
                  v-model="filtroEstadoIncidencia"
                  :options="opcionesEstadoIncidencia"
                  outlined
                  dense
                  emit-value
                  map-options
                  label="Estado"
                />
              </div>

              <div class="col-12 col-sm-4">
                <q-select
                  v-model="filtroTipoIncidencia"
                  :options="opcionesTipoIncidencia"
                  outlined
                  dense
                  emit-value
                  map-options
                  label="Tipo de incidente"
                />
              </div>

              <div class="col-12 col-sm-4">
                <q-input
                  v-model="busquedaIncidencia"
                  outlined
                  dense
                  clearable
                  debounce="250"
                  label="Buscar código, viaje o persona"
                >
                  <template #prepend>
                    <q-icon name="search" />
                  </template>
                </q-input>
              </div>
            </div>

            <q-linear-progress
              v-if="cargandoIncidencias"
              indeterminate
              color="negative"
              class="q-mb-md"
            />

            <q-banner
              v-if="
                !cargandoIncidencias
                && !incidenciasFiltradas.length
              "
              rounded
              class="bg-green-1 text-green-10"
            >
              <template #avatar>
                <q-icon
                  name="verified"
                  color="positive"
                  size="36px"
                />
              </template>

              No existen incidencias que coincidan con los
              filtros seleccionados.
            </q-banner>

            <q-list
              v-else
              bordered
              separator
              class="lista-incidencias-sos"
            >
              <q-item
                v-for="incidencia in incidenciasFiltradas"
                :key="incidencia.id"
                class="incidencia-sos-item q-py-md"
              >
                <q-item-section avatar top>
                  <q-avatar
                    :color="colorPrioridadIncidencia(
                      incidencia.prioridad
                    )"
                    text-color="white"
                    :icon="iconoTipoIncidencia(
                      incidencia.tipo
                    )"
                    size="48px"
                  />
                </q-item-section>

                <q-item-section class="min-width-zero">
                  <q-item-label
                    class="row items-center q-gutter-xs"
                  >
                    <span class="text-weight-bold text-grey-9">
                      {{ incidencia.codigo }}
                    </span>

                    <q-chip
                      :color="colorEstadoIncidencia(
                        incidencia.estado
                      )"
                      text-color="white"
                      dense
                      class="q-ma-none text-weight-bold text-uppercase"
                    >
                      {{ incidencia.estado }}
                    </q-chip>

                    <q-chip
                      :color="colorPrioridadIncidencia(
                        incidencia.prioridad
                      )"
                      text-color="white"
                      dense
                      class="q-ma-none"
                    >
                      {{ incidencia.prioridad }}
                    </q-chip>
                  </q-item-label>

                  <q-item-label
                    class="text-subtitle2 text-weight-bold text-negative q-mt-xs"
                  >
                    {{ incidencia.tipo }}
                    · Viaje #{{ incidencia.solicitud_id }}
                  </q-item-label>

                  <q-item-label
                    caption
                    class="q-mt-xs"
                  >
                    Reportó:
                    <strong>
                      {{
                        incidencia.reportado_por_nombre
                          || 'Usuario MOTRIX'
                      }}
                    </strong>
                    ({{ incidencia.reportado_por_rol || '—' }})
                  </q-item-label>

                  <q-item-label
                    caption
                    class="q-mt-xs"
                  >
                    Pasajero:
                    <strong>
                      {{
                        nombrePasajeroIncidencia(incidencia)
                      }}
                    </strong>
                    · Conductor:
                    <strong>
                      {{
                        nombreConductorIncidencia(incidencia)
                      }}
                    </strong>
                  </q-item-label>

                  <q-item-label
                    v-if="incidencia.descripcion"
                    class="incidencia-descripcion q-mt-sm"
                  >
                    {{ incidencia.descripcion }}
                  </q-item-label>

                  <q-item-label
                    v-if="incidencia.nota_administrador"
                    caption
                    class="incidencia-nota q-mt-sm"
                  >
                    <q-icon name="admin_panel_settings" />
                    Nota administrativa:
                    {{ incidencia.nota_administrador }}
                  </q-item-label>

                  <q-item-label
                    caption
                    class="q-mt-sm"
                  >
                    <q-icon name="schedule" />
                    {{
                      formatearFechaHoraIncidencia(
                        incidencia.fecha_reportada
                      )
                    }}

                    <template v-if="incidencia.atendido_por_nombre">
                      · Atendió:
                      <strong>
                        {{ incidencia.atendido_por_nombre }}
                      </strong>
                    </template>
                  </q-item-label>
                </q-item-section>

                <q-item-section side top>
                  <div class="column q-gutter-xs acciones-incidencia">
                    <q-btn
                      v-if="tieneUbicacionIncidencia(incidencia)"
                      outline
                      dense
                      color="negative"
                      icon="location_on"
                      label="Ubicación"
                      no-caps
                      @click.stop="abrirUbicacionIncidencia(
                        incidencia
                      )"
                    />

                    <q-btn
                      v-if="incidencia.estado === 'Reportado'"
                      color="orange-9"
                      dense
                      unelevated
                      icon="done"
                      label="Recibir"
                      no-caps
                      :loading="
                        incidenciaActualizandoId
                          === incidencia.id
                      "
                      @click.stop="actualizarEstadoIncidencia(
                        incidencia,
                        'Recibido'
                      )"
                    />

                    <q-btn
                      v-if="[
                        'Reportado',
                        'Recibido'
                      ].includes(incidencia.estado)"
                      color="primary"
                      dense
                      unelevated
                      icon="support_agent"
                      label="Atender"
                      no-caps
                      :loading="
                        incidenciaActualizandoId
                          === incidencia.id
                      "
                      @click.stop="actualizarEstadoIncidencia(
                        incidencia,
                        'En atención'
                      )"
                    />

                    <q-btn
                      v-if="incidencia.estado !== 'Resuelto'"
                      color="positive"
                      dense
                      unelevated
                      icon="task_alt"
                      label="Resolver"
                      no-caps
                      :loading="
                        incidenciaActualizandoId
                          === incidencia.id
                      "
                      @click.stop="confirmarResolverIncidencia(
                        incidencia
                      )"
                    />
                  </div>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>
        </q-card>
      </div>


      <!-- MONITOREO OPERATIVO -->
      <div class="q-mb-xl">
        <q-card
          class="shadow-2 border-radius-md
                 overflow-hidden"
        >
          <q-card-section
            class="row items-center justify-between
                   q-col-gutter-md"
          >
            <div>
              <div
                class="text-h6 text-bold text-grey-9"
              >
                Monitoreo operativo
              </div>

              <div class="text-caption text-grey-6">
                Conductores, ubicaciones y viajes activos
              </div>
            </div>

            <div
              class="row items-center q-gutter-sm"
            >
              <q-chip
                color="green-1"
                text-color="green-9"
                icon="update"
                size="sm"
              >
                {{
                  ultimaActualizacionMonitoreo
                    || 'Actualizando...'
                }}
              </q-chip>

              <q-btn
                color="primary"
                icon="refresh"
                label="Actualizar mapa"
                unelevated
                no-caps
                :loading="refrescandoMonitoreo"
                @click="cargarMonitoreo(true)"
              />
            </div>
          </q-card-section>

          <q-separator />

          <q-card-section>
            <div
              class="row q-col-gutter-sm q-mb-md"
            >
              <div
                class="col-6 col-sm-3"
              >
                <div
                  class="resumen-monitoreo
                         resumen-disponible"
                >
                  <q-icon
                    name="wifi"
                    size="24px"
                  />

                  <div>
                    <div class="text-h6 text-bold">
                      {{ resumenMonitoreo.disponibles }}
                    </div>

                    <div class="text-caption">
                      Disponibles
                    </div>
                  </div>
                </div>
              </div>

              <div
                class="col-6 col-sm-3"
              >
                <div
                  class="resumen-monitoreo
                         resumen-viaje"
                >
                  <q-icon
                    name="route"
                    size="24px"
                  />

                  <div>
                    <div class="text-h6 text-bold">
                      {{ resumenMonitoreo.enViaje }}
                    </div>

                    <div class="text-caption">
                      En viaje
                    </div>
                  </div>
                </div>
              </div>

              <div
                class="col-6 col-sm-3"
              >
                <div
                  class="resumen-monitoreo
                         resumen-ubicacion"
                >
                  <q-icon
                    name="my_location"
                    size="24px"
                  />

                  <div>
                    <div class="text-h6 text-bold">
                      {{ resumenMonitoreo.conUbicacion }}
                    </div>

                    <div class="text-caption">
                      Con ubicación
                    </div>
                  </div>
                </div>
              </div>

              <div
                class="col-6 col-sm-3"
              >
                <div
                  class="resumen-monitoreo
                         resumen-desconectado"
                >
                  <q-icon
                    name="wifi_off"
                    size="24px"
                  />

                  <div>
                    <div class="text-h6 text-bold">
                      {{ resumenMonitoreo.fueraLinea }}
                    </div>

                    <div class="text-caption">
                      Fuera de línea
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- ALERTAS OPERATIVAS -->
            <q-card
              flat
              bordered
              class="alertas-operativas q-mb-md"
            >
              <q-card-section
                class="row items-center justify-between
                       q-col-gutter-sm q-py-sm"
              >
                <div class="row items-center no-wrap">
                  <q-avatar
                    size="38px"
                    :color="
                      alertasOperativas.length
                        ? 'red-1'
                        : 'green-1'
                    "
                    :text-color="
                      alertasOperativas.length
                        ? 'negative'
                        : 'positive'
                    "
                    :icon="
                      alertasOperativas.length
                        ? 'notification_important'
                        : 'verified'
                    "
                    class="q-mr-sm"
                  />

                  <div>
                    <div
                      class="text-subtitle1 text-bold
                             text-grey-9"
                    >
                      Alertas operativas
                    </div>

                    <div class="text-caption text-grey-6">
                      Detección automática de demoras
                      y problemas de ubicación
                    </div>
                  </div>
                </div>

                <div class="row items-center q-gutter-xs">
                  <q-chip
                    v-if="resumenAlertas.criticas"
                    color="negative"
                    text-color="white"
                    icon="error"
                    dense
                  >
                    {{ resumenAlertas.criticas }}
                    críticas
                  </q-chip>

                  <q-chip
                    v-if="resumenAlertas.atencion"
                    color="orange-8"
                    text-color="white"
                    icon="warning"
                    dense
                  >
                    {{ resumenAlertas.atencion }}
                    requieren atención
                  </q-chip>

                  <q-chip
                    v-if="!alertasOperativas.length"
                    color="green-1"
                    text-color="green-9"
                    icon="check_circle"
                    dense
                  >
                    Operación normal
                  </q-chip>
                </div>
              </q-card-section>

              <q-separator />

              <q-list
                v-if="alertasOperativas.length"
                separator
                class="lista-alertas"
              >
                <q-item
                  v-for="alerta in alertasOperativas"
                  :key="alerta.clave"
                  clickable
                  v-ripple
                  @click="atenderAlerta(alerta)"
                >
                  <q-item-section avatar>
                    <q-avatar
                      :color="alerta.colorFondo"
                      :text-color="alerta.colorTexto"
                      :icon="alerta.icono"
                    />
                  </q-item-section>

                  <q-item-section>
                    <q-item-label
                      class="text-bold text-grey-9"
                    >
                      {{ alerta.titulo }}
                    </q-item-label>

                    <q-item-label caption>
                      {{ alerta.detalle }}
                    </q-item-label>

                    <q-item-label
                      v-if="alerta.accionTexto"
                      caption
                      class="text-primary text-bold q-mt-xs"
                    >
                      {{ alerta.accionTexto }}
                    </q-item-label>
                  </q-item-section>

                  <q-item-section side>
                    <q-chip
                      dense
                      :color="alerta.colorChip"
                      text-color="white"
                      :icon="alerta.iconoTiempo"
                    >
                      {{ alerta.tiempoTexto }}
                    </q-chip>
                  </q-item-section>
                </q-item>
              </q-list>

              <q-card-section
                v-else
                class="estado-operativo-normal"
              >
                <q-icon
                  name="task_alt"
                  color="positive"
                  size="24px"
                  class="q-mr-sm"
                />

                <div>
                  <div class="text-bold text-green-9">
                    Sin alertas activas
                  </div>

                  <div class="text-caption text-green-8">
                    No se detectaron solicitudes demoradas,
                    viajes detenidos ni GPS desactualizados.
                  </div>
                </div>
              </q-card-section>
            </q-card>

            <div class="row q-col-gutter-lg">
              <div class="col-12 col-lg-8">
                <div
                  ref="mapaMonitoreoElemento"
                  class="mapa-monitoreo"
                />

                <div
                  class="leyenda-mapa row
                         items-center q-gutter-md
                         q-mt-sm"
                >
                  <span>
                    <i class="punto-leyenda punto-disponible" />
                    Disponible
                  </span>

                  <span>
                    <i class="punto-leyenda punto-viaje" />
                    En viaje
                  </span>

                  <span>
                    <i class="punto-leyenda punto-desconectado" />
                    Fuera de línea
                  </span>
                </div>
              </div>

              <div class="col-12 col-lg-4">
                <div
                  class="text-subtitle1 text-bold
                         text-grey-9 q-mb-sm"
                >
                  Estado de conductores
                </div>

                <q-input
                  v-model="busquedaConductor"
                  outlined
                  dense
                  clearable
                  debounce="250"
                  placeholder="Buscar conductor"
                  class="q-mb-sm"
                >
                  <template #prepend>
                    <q-icon name="search" />
                  </template>
                </q-input>

                <q-btn-toggle
                  v-model="filtroMonitoreo"
                  spread
                  no-caps
                  unelevated
                  toggle-color="primary"
                  color="grey-2"
                  text-color="grey-8"
                  :options="opcionesFiltroMonitoreo"
                  class="filtros-monitoreo q-mb-sm"
                />

                <q-list
                  v-if="conductoresFiltrados.length"
                  bordered
                  separator
                  class="lista-conductores"
                >
                  <q-item
                    v-for="conductor in conductoresFiltrados"
                    :key="conductor.id"
                    clickable
                    v-ripple
                    @click="centrarConductor(conductor)"
                  >
                    <q-item-section avatar>
                      <q-avatar
                        :color="conductor.colorAvatar"
                        text-color="white"
                        icon="two_wheeler"
                      />
                    </q-item-section>

                    <q-item-section>
                      <q-item-label
                        class="text-bold text-grey-9"
                      >
                        {{ conductor.nombre }}
                      </q-item-label>

                      <q-item-label caption>
                        {{ conductor.textoEstado }}
                      </q-item-label>

                      <q-item-label
                        v-if="conductor.viaje"
                        caption
                        class="text-primary"
                      >
                        Viaje #{{ conductor.viaje.id }}
                        · {{ conductor.viaje.estado }}
                      </q-item-label>

                      <q-btn
                        v-if="conductor.viaje"
                        flat
                        dense
                        no-caps
                        color="primary"
                        icon="visibility"
                        label="Ver detalle"
                        class="boton-detalle-viaje q-mt-xs"
                        @click.stop="
                          abrirDetalleViaje(
                            conductor.viaje
                          )
                        "
                      />

                      <q-item-label
                        v-if="conductor.ultimaConexion"
                        caption
                      >
                        Última conexión:
                        {{
                          formatearFechaHora(
                            conductor.ultimaConexion
                          )
                        }}
                      </q-item-label>

                      <q-chip
                        v-if="conductor.tieneUbicacion"
                        dense
                        size="sm"
                        :color="conductor.colorEstadoGps"
                        :text-color="
                          conductor.estadoGps === 'reciente'
                            ? 'green-10'
                            : 'white'
                        "
                        :icon="conductor.iconoEstadoGps"
                        class="gps-status-chip q-mt-xs q-ml-none"
                      >
                        {{ conductor.textoEstadoGps }}
                      </q-chip>
                    </q-item-section>

                    <q-item-section side>
                      <q-icon
                        :name="
                          conductor.tieneUbicacion
                            ? 'location_on'
                            : 'location_off'
                        "
                        :color="
                          conductor.colorIconoGps
                        "
                      />
                    </q-item-section>
                  </q-item>
                </q-list>

                <div
                  v-else
                  class="text-center text-grey-5
                         q-pa-xl bordered-empty"
                >
                  <q-icon
                    name="two_wheeler"
                    size="42px"
                  />

                  <div class="q-mt-sm">
                    No hay conductores que coincidan
                    con el filtro.
                  </div>
                </div>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <!-- ANALÍTICA -->
      <div class="row q-col-gutter-lg">

        <!-- GRÁFICO -->
        <div class="col-12 col-lg-7">
          <q-card
            class="shadow-2 border-radius-md
                   card-igualdad"
          >
            <q-card-section
              class="row items-center
                     justify-between q-pb-none"
            >
              <div>
                <div
                  class="text-h6 text-bold text-grey-9"
                >
                  Frecuencia de Viajes
                </div>

                <div class="text-caption text-grey-6">
                  Solicitudes registradas durante los
                  últimos siete días
                </div>
              </div>

              <q-icon
                name="bar_chart"
                color="primary"
                size="md"
              />
            </q-card-section>

            <q-card-section class="q-pt-sm">
              <div id="chart">
                <apexchart
                  type="area"
                  height="300"
                  :options="chartOptions"
                  :series="chartSeries"
                />
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- ÚLTIMAS SOLICITUDES -->
        <div class="col-12 col-lg-5">
          <q-card
            class="shadow-2 border-radius-md
                   card-igualdad"
          >
            <q-card-section
              class="row items-center
                     justify-between q-pb-none"
            >
              <div>
                <div
                  class="text-h6 text-bold text-grey-9"
                >
                  Últimas Solicitudes
                </div>

                <div class="text-caption text-grey-6">
                  Monitoreo de actividad reciente
                </div>
              </div>

              <q-badge
                color="positive"
                class="q-px-sm text-bold"
              >
                EN VIVO
              </q-badge>
            </q-card-section>

            <q-card-section
              class="scroll-area-solicitudes"
            >
              <q-list
                v-if="ultimosViajes.length > 0"
                separator
              >
                <q-item
                  v-for="viaje in ultimosViajes"
                  :key="viaje.id"
                  clickable
                  v-ripple
                  class="q-px-none q-py-sm"
                  :class="{
                    'nuevo-viaje-animacion':
                      viaje.esNuevo
                  }"
                  @click="abrirDetalleViaje(viaje)"
                >
                  <q-item-section avatar>
                    <q-avatar
                      color="blue-1"
                      text-color="primary"
                      icon="location_on"
                      size="38px"
                    />
                  </q-item-section>

                  <q-item-section>
                    <q-item-label
                      class="text-bold text-grey-9
                             text-subtitle2"
                    >
                      {{
                        obtenerNombrePasajero(viaje)
                      }}
                    </q-item-label>

                    <q-item-label
                      caption
                      class="text-grey-7
                             truncate-address"
                    >
                      <b>De:</b>
                      {{ viaje.origen || 'Sin origen' }}
                    </q-item-label>

                    <q-item-label
                      caption
                      class="text-grey-7
                             truncate-address"
                    >
                      <b>A:</b>
                      {{ viaje.destino || 'Sin destino' }}
                    </q-item-label>
                  </q-item-section>

                  <q-item-section
                    side
                    class="text-right"
                  >
                    <div
                      class="text-bold text-positive
                             text-subtitle2"
                    >
                      Bs.
                      {{
                        formatearMonto(viaje.precio)
                      }}
                    </div>

                    <q-chip
                      size="xs"
                      :color="
                        getEstadoColor(viaje.estado)
                      "
                      text-color="white"
                      class="text-bold text-uppercase
                             q-ma-none q-mt-xs"
                    >
                      {{ viaje.estado || 'Sin estado' }}
                    </q-chip>
                  </q-item-section>
                </q-item>
              </q-list>

              <div
                v-else
                class="text-center text-grey-5 q-py-xl"
              >
                <q-icon
                  name="hourglass_empty"
                  size="lg"
                />

                <p class="q-mt-md">
                  No se registran viajes recientes.
                </p>
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>
    </div>

    <!-- DETALLE OPERATIVO DEL VIAJE -->
    <q-dialog
      v-model="detalleViajeAbierto"
      :maximized="$q.screen.lt.sm"
      transition-show="scale"
      transition-hide="scale"
    >
      <q-card class="detalle-viaje-card">
        <q-card-section
          class="detalle-viaje-header text-white"
        >
          <div
            class="row items-center justify-between
                   no-wrap"
          >
            <div class="row items-center no-wrap">
              <q-avatar
                color="white"
                text-color="green-9"
                icon="route"
                size="48px"
                class="q-mr-md"
              />

              <div>
                <div class="text-h6 text-bold">
                  Viaje
                  #{{ detalleViaje?.id || '—' }}
                </div>

                <div class="text-caption opacity-80">
                  Seguimiento y datos operativos
                </div>
              </div>
            </div>

            <div class="row items-center no-wrap">
              <q-chip
                v-if="detalleViaje"
                :color="
                  getEstadoColor(
                    detalleViaje.estado
                  )
                "
                text-color="white"
                class="text-bold text-uppercase"
              >
                {{
                  detalleViaje.estado
                    || 'Sin estado'
                }}
              </q-chip>

              <q-btn
                flat
                round
                dense
                icon="close"
                aria-label="Cerrar detalle"
                @click="cerrarDetalleViaje"
              />
            </div>
          </div>
        </q-card-section>

        <q-linear-progress
          v-if="cargandoDetalleViaje"
          indeterminate
          color="positive"
        />

        <q-card-section
          v-if="detalleViaje"
          class="q-pa-md q-pa-sm-lg"
        >
          <!-- RESUMEN PRINCIPAL -->
          <div
            class="row q-col-gutter-sm q-mb-md"
          >
            <div class="col-6 col-sm-3">
              <div class="dato-resumen-viaje">
                <q-icon
                  name="calendar_today"
                  color="primary"
                  size="22px"
                />

                <div>
                  <div class="dato-resumen-label">
                    Fecha
                  </div>

                  <div class="dato-resumen-valor">
                    {{
                      formatearFechaHora(
                        detalleViaje.fecha
                        || detalleViaje.created_at
                      )
                    }}
                  </div>
                </div>
              </div>
            </div>

            <div class="col-6 col-sm-3">
              <div class="dato-resumen-viaje">
                <q-icon
                  name="straighten"
                  color="teal-8"
                  size="22px"
                />

                <div>
                  <div class="dato-resumen-label">
                    Distancia
                  </div>

                  <div class="dato-resumen-valor">
                    {{
                      formatearDistanciaDetalle(
                        detalleViaje.distancia_km
                      )
                    }}
                  </div>
                </div>
              </div>
            </div>

            <div class="col-6 col-sm-3">
              <div class="dato-resumen-viaje">
                <q-icon
                  name="payments"
                  color="positive"
                  size="22px"
                />

                <div>
                  <div class="dato-resumen-label">
                    Tarifa
                  </div>

                  <div class="dato-resumen-valor">
                    Bs.
                    {{
                      formatearMonto(
                        detalleViaje.precio
                      )
                    }}
                  </div>
                </div>
              </div>
            </div>

            <div class="col-6 col-sm-3">
              <div class="dato-resumen-viaje">
                <q-icon
                  name="account_balance_wallet"
                  color="purple-7"
                  size="22px"
                />

                <div>
                  <div class="dato-resumen-label">
                    Método
                  </div>

                  <div class="dato-resumen-valor">
                    {{
                      detallePago?.metodo
                      || detalleViaje.metodo_pago
                      || 'No registrado'
                    }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- RUTA -->
          <q-card
            flat
            bordered
            class="seccion-detalle-viaje q-mb-md"
          >
            <q-card-section>
              <div
                class="text-subtitle1 text-bold
                       text-grey-9 q-mb-md"
              >
                <q-icon
                  name="alt_route"
                  color="primary"
                  class="q-mr-xs"
                />
                Ruta solicitada
              </div>

              <div class="ruta-detalle-viaje">
                <div class="ruta-detalle-punto">
                  <div
                    class="ruta-circulo
                           ruta-circulo-origen"
                  />

                  <div>
                    <div
                      class="text-caption text-grey-6"
                    >
                      Origen
                    </div>

                    <div
                      class="text-body2 text-bold
                             text-grey-9"
                    >
                      {{
                        detalleViaje.origen
                        || 'Origen no registrado'
                      }}
                    </div>
                  </div>
                </div>

                <div class="ruta-linea-detalle" />

                <div class="ruta-detalle-punto">
                  <q-icon
                    name="location_on"
                    color="negative"
                    size="24px"
                  />

                  <div>
                    <div
                      class="text-caption text-grey-6"
                    >
                      Destino
                    </div>

                    <div
                      class="text-body2 text-bold
                             text-grey-9"
                    >
                      {{
                        detalleViaje.destino
                        || 'Destino no registrado'
                      }}
                    </div>
                  </div>
                </div>
              </div>
            </q-card-section>
          </q-card>

          <!-- PERSONAS -->
          <div class="row q-col-gutter-md q-mb-md">
            <div class="col-12 col-md-6">
              <q-card
                flat
                bordered
                class="seccion-detalle-viaje
                       full-height"
              >
                <q-card-section>
                  <div
                    class="row items-center no-wrap"
                  >
                    <q-avatar
                      color="indigo-1"
                      text-color="indigo-8"
                      icon="person_pin_circle"
                      size="46px"
                      class="q-mr-md"
                    />

                    <div>
                      <div
                        class="text-caption text-grey-6"
                      >
                        Pasajero
                      </div>

                      <div
                        class="text-subtitle1 text-bold
                               text-grey-9"
                      >
                        {{ nombrePasajeroDetalle }}
                      </div>

                      <div
                        v-if="telefonoPasajeroDetalle"
                        class="text-caption text-grey-7"
                      >
                        Tel.
                        {{ telefonoPasajeroDetalle }}
                      </div>
                    </div>
                  </div>
                </q-card-section>
              </q-card>
            </div>

            <div class="col-12 col-md-6">
              <q-card
                flat
                bordered
                class="seccion-detalle-viaje
                       full-height"
              >
                <q-card-section>
                  <div
                    class="row items-center no-wrap"
                  >
                    <q-avatar
                      :color="
                        conductorDetalle
                          ? 'green-1'
                          : 'grey-3'
                      "
                      :text-color="
                        conductorDetalle
                          ? 'green-9'
                          : 'grey-7'
                      "
                      icon="two_wheeler"
                      size="46px"
                      class="q-mr-md"
                    />

                    <div class="col">
                      <div
                        class="text-caption text-grey-6"
                      >
                        Mototaxista
                      </div>

                      <div
                        class="text-subtitle1 text-bold
                               text-grey-9"
                      >
                        {{ nombreConductorDetalle }}
                      </div>

                      <div
                        v-if="conductorDetalle"
                        class="text-caption text-grey-7"
                      >
                        {{
                          conductorDetalle.textoEstado
                          || 'Conductor asignado'
                        }}
                      </div>
                    </div>
                  </div>
                </q-card-section>
              </q-card>
            </div>
          </div>

          <!-- SERVICIO Y PAGO -->
          <div class="row q-col-gutter-md q-mb-md">
            <div class="col-12 col-md-6">
              <q-card
                flat
                bordered
                class="seccion-detalle-viaje
                       full-height"
              >
                <q-card-section>
                  <div
                    class="row items-center justify-between
                           q-mb-sm"
                  >
                    <div
                      class="text-subtitle1 text-bold
                             text-grey-9"
                    >
                      <q-icon
                        name="local_taxi"
                        color="teal-8"
                        class="q-mr-xs"
                      />
                      Servicio
                    </div>

                    <q-chip
                      v-if="detalleServicio"
                      color="teal-1"
                      text-color="teal-9"
                      dense
                    >
                      #{{ detalleServicio.id }}
                    </q-chip>
                  </div>

                  <div
                    v-if="detalleServicio"
                    class="detalle-datos-lista"
                  >
                    <div>
                      <span>Estado</span>
                      <strong>
                        {{
                          detalleServicio.estado
                          || 'Sin estado'
                        }}
                      </strong>
                    </div>

                    <div>
                      <span>Hora de inicio</span>
                      <strong>
                        {{
                          formatearHoraDetalle(
                            detalleServicio.hora_inicio
                          )
                        }}
                      </strong>
                    </div>

                    <div>
                      <span>Hora de finalización</span>
                      <strong>
                        {{
                          formatearHoraDetalle(
                            detalleServicio.hora_fin
                          )
                        }}
                      </strong>
                    </div>
                  </div>

                  <div
                    v-else
                    class="text-caption text-grey-6"
                  >
                    Todavía no existe un servicio
                    registrado para esta solicitud.
                  </div>
                </q-card-section>
              </q-card>
            </div>

            <div class="col-12 col-md-6">
              <q-card
                flat
                bordered
                class="seccion-detalle-viaje
                       full-height"
              >
                <q-card-section>
                  <div
                    class="row items-center justify-between
                           q-mb-sm"
                  >
                    <div
                      class="text-subtitle1 text-bold
                             text-grey-9"
                    >
                      <q-icon
                        name="receipt_long"
                        color="purple-7"
                        class="q-mr-xs"
                      />
                      Pago
                    </div>

                    <q-chip
                      v-if="detallePago"
                      color="purple-1"
                      text-color="purple-9"
                      dense
                    >
                      #{{ detallePago.id }}
                    </q-chip>
                  </div>

                  <div
                    v-if="detallePago"
                    class="detalle-datos-lista"
                  >
                    <div>
                      <span>Monto</span>
                      <strong>
                        Bs.
                        {{
                          formatearMonto(
                            detallePago.monto
                          )
                        }}
                      </strong>
                    </div>

                    <div>
                      <span>Método</span>
                      <strong>
                        {{
                          detallePago.metodo
                          || 'No registrado'
                        }}
                      </strong>
                    </div>

                    <div>
                      <span>Estado</span>
                      <strong>
                        {{
                          detallePago.estado
                          || 'Sin estado'
                        }}
                      </strong>
                    </div>
                  </div>

                  <div
                    v-else
                    class="text-caption text-grey-6"
                  >
                    Todavía no existe un pago
                    vinculado a este viaje.
                  </div>
                </q-card-section>
              </q-card>
            </div>
          </div>

          <!-- CALIFICACIÓN -->
          <q-card
            v-if="detalleViaje.calificacion"
            flat
            bordered
            class="seccion-detalle-viaje
                   calificacion-detalle-viaje"
          >
            <q-card-section>
              <div
                class="row items-center justify-between
                       q-col-gutter-sm"
              >
                <div>
                  <div
                    class="text-subtitle1 text-bold
                           text-grey-9"
                  >
                    Calificación del pasajero
                  </div>

                  <div class="row items-center q-mt-xs">
                    <q-rating
                      :model-value="
                        Number(
                          detalleViaje.calificacion
                        )
                      "
                      readonly
                      size="24px"
                      color="amber-8"
                      icon="star_border"
                      icon-selected="star"
                    />

                    <span
                      class="text-bold text-amber-10
                             q-ml-sm"
                    >
                      {{
                        Number(
                          detalleViaje.calificacion
                        ).toFixed(1)
                      }}/5
                    </span>
                  </div>
                </div>

                <q-chip
                  v-if="detalleViaje.calificado_en"
                  color="amber-1"
                  text-color="amber-10"
                  icon="event_available"
                >
                  {{
                    formatearFechaHora(
                      detalleViaje.calificado_en
                    )
                  }}
                </q-chip>
              </div>

              <div
                v-if="
                  detalleViaje.comentario_calificacion
                "
                class="comentario-calificacion-detalle
                       q-mt-md"
              >
                “{{
                  detalleViaje
                    .comentario_calificacion
                }}”
              </div>
            </q-card-section>
          </q-card>
        </q-card-section>

        <q-card-section
          v-else-if="cargandoDetalleViaje"
          class="flex flex-center column q-pa-xl"
        >
          <q-spinner-dots
            color="primary"
            size="48px"
          />

          <div class="text-grey-7 q-mt-md">
            Cargando información del viaje...
          </div>
        </q-card-section>

        <q-separator />

        <q-card-actions
          align="right"
          class="q-pa-md q-gutter-sm"
        >
          <q-btn
            v-if="
              conductorDetalle
              && conductorDetalle.tieneUbicacion
            "
            outline
            color="primary"
            icon="my_location"
            label="Ver en el mapa"
            no-caps
            @click="verDetalleEnMapa"
          />

          <q-btn
            outline
            color="teal-8"
            icon="local_taxi"
            label="Servicios"
            no-caps
            @click="
              irModuloDesdeDetalle(
                '/servicios'
              )
            "
          />

          <q-btn
            outline
            color="indigo-8"
            icon="add_road"
            label="Solicitudes"
            no-caps
            @click="
              irModuloDesdeDetalle(
                '/solicitudes'
              )
            "
          />

          <q-btn
            color="primary"
            icon="close"
            label="Cerrar"
            no-caps
            unelevated
            @click="cerrarDetalleViaje"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import {
  ref,
  computed,
  nextTick,
  watch,
  onMounted,
  onBeforeUnmount
} from 'vue'

import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import apexchart from 'vue3-apexcharts'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

import { api } from '../boot/axios.js'


const router = useRouter()
const $q = useQuasar()

const loading = ref(false)
const wsConnected = ref(false)

const centroIncidenciasElemento = ref(null)
const cargandoIncidencias = ref(false)
const incidencias = ref([])
const incidenciaActualizandoId = ref(null)
const filtroEstadoIncidencia = ref('activas')
const filtroTipoIncidencia = ref('todos')
const busquedaIncidencia = ref('')

const resumenIncidencias = ref({
  reportadas: 0,
  recibidas: 0,
  en_atencion: 0,
  resueltas: 0,
  activas: 0
})

const opcionesEstadoIncidencia = [
  {
    label: 'Alertas activas',
    value: 'activas'
  },
  {
    label: 'Todos los estados',
    value: 'todos'
  },
  {
    label: 'Reportado',
    value: 'Reportado'
  },
  {
    label: 'Recibido',
    value: 'Recibido'
  },
  {
    label: 'En atención',
    value: 'En atención'
  },
  {
    label: 'Resuelto',
    value: 'Resuelto'
  }
]

const opcionesTipoIncidencia = [
  {
    label: 'Todos los tipos',
    value: 'todos'
  },
  {
    label: 'Accidente',
    value: 'Accidente'
  },
  {
    label: 'Emergencia médica',
    value: 'Emergencia médica'
  },
  {
    label: 'Situación de inseguridad',
    value: 'Situación de inseguridad'
  },
  {
    label: 'Falla de la motocicleta',
    value: 'Falla de la motocicleta'
  },
  {
    label: 'Pasajero no localizado',
    value: 'Pasajero no localizado'
  },
  {
    label: 'Conductor no localizado',
    value: 'Conductor no localizado'
  },
  {
    label: 'Otro',
    value: 'Otro'
  }
]

let intervaloMonitoreo = null
let temporizadorEventoTiempoReal = null
let actualizacionTiempoRealEnCurso = false
let mapaMonitoreo = null
let capaMonitoreo = null
let marcadoresConductores = new Map()

const mapaMonitoreoElemento = ref(null)
const refrescandoMonitoreo = ref(false)
const ultimaActualizacionMonitoreo = ref('')
const mototaxistasMonitoreo = ref([])
const solicitudesMonitoreo = ref([])
const serviciosMonitoreo = ref([])
const pagosMonitoreo = ref([])

const detalleViajeAbierto = ref(false)
const cargandoDetalleViaje = ref(false)
const detalleViaje = ref(null)
const detalleServicio = ref(null)
const detallePago = ref(null)

const filtroMonitoreo = ref('todos')
const busquedaConductor = ref('')

const opcionesFiltroMonitoreo = [
  {
    label: 'Todos',
    value: 'todos'
  },
  {
    label: 'Disponibles',
    value: 'disponibles'
  },
  {
    label: 'En viaje',
    value: 'viaje'
  },
  {
    label: 'Fuera de línea',
    value: 'fuera'
  }
]

const stats = ref({
  personas: 0,
  mototaxistas: 0,
  motocicletas: 0,
  pasajeros: 0,
  sindicatos: 0,
  solicitudes: 0,
  servicios: 0,
  pagos: '0.00',
  reportes: 0
})

const ultimosViajes = ref([])

const mainCards = [
  {
    title: 'Personas',
    icon: 'groups',
    color: 'blue',
    bgColor: 'blue-1',
    key: 'personas',
    route: '/personas'
  },
  {
    title: 'Mototaxistas',
    icon: 'sports_motorsports',
    color: 'green',
    bgColor: 'green-1',
    key: 'mototaxistas',
    route: '/mototaxistas'
  },
  {
    title: 'Motocicletas',
    icon: 'motorcycle',
    color: 'orange',
    bgColor: 'orange-1',
    key: 'motocicletas',
    route: '/motocicletas'
  },
  {
    title: 'Pasajeros',
    icon: 'person',
    color: 'red',
    bgColor: 'red-1',
    key: 'pasajeros',
    route: '/pasajeros'
  }
]

const operationalCards = [
  {
    title: 'Sindicatos',
    icon: 'business',
    color: 'indigo',
    bgColor: 'indigo-1',
    key: 'sindicatos',
    route: '/sindicatos'
  },
  {
    title: 'Solicitudes',
    icon: 'add_road',
    color: 'cyan',
    bgColor: 'cyan-1',
    key: 'solicitudes',
    route: '/solicitudes'
  },
  {
    title: 'Servicios',
    icon: 'local_taxi',
    color: 'teal',
    bgColor: 'teal-1',
    key: 'servicios',
    route: '/servicios'
  },
  {
    title: 'Pagos',
    icon: 'payments',
    color: 'purple',
    bgColor: 'purple-1',
    key: 'pagos',
    route: '/pagos'
  },
  {
    title: 'Reportes',
    icon: 'bar_chart',
    color: 'grey-9',
    bgColor: 'grey-3',
    key: 'reportes',
    route: '/reportes'
  }
]

const chartSeries = ref([
  {
    name: 'Solicitudes',
    data: [0, 0, 0, 0, 0, 0, 0]
  }
])

const chartOptions = ref({
  chart: {
    height: 300,
    type: 'area',
    toolbar: {
      show: false
    },
    zoom: {
      enabled: false
    }
  },

  colors: ['#027be3'],

  dataLabels: {
    enabled: false
  },

  stroke: {
    curve: 'smooth',
    width: 3
  },

  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.5,
      opacityTo: 0.1,
      stops: [0, 90, 100]
    }
  },

  xaxis: {
    categories: [
      'Lun',
      'Mar',
      'Mié',
      'Jue',
      'Vie',
      'Sáb',
      'Dom'
    ],

    labels: {
      style: {
        colors: '#757575',
        fontWeight: 600
      }
    }
  },

  yaxis: {
    min: 0,
    forceNiceScale: true,

    labels: {
      style: {
        colors: '#757575'
      },

      formatter: (valor) => {
        return Math.round(valor)
      }
    }
  },

  tooltip: {
    theme: 'light',

    y: {
      formatter: (valor) => {
        return `${valor} solicitudes`
      }
    }
  },

  noData: {
    text: 'Sin información disponible'
  }
})

const goToModule = (ruta) => {
  router.push(ruta)
}

const normalizarLista = (respuesta) => {
  if (Array.isArray(respuesta)) {
    return respuesta
  }

  if (Array.isArray(respuesta?.data)) {
    return respuesta.data
  }

  return []
}

const formatearMonto = (monto) => {
  const numero = Number.parseFloat(monto)

  return Number.isFinite(numero)
    ? numero.toFixed(2)
    : '0.00'
}

const obtenerNombrePasajero = (viaje) => {
  return (
    viaje?.pasajero?.persona?.nombre
    || viaje?.pasajero?.persona_asignada
    || viaje?.pasajero_nombre
    || 'Pasajero solicitante'
  )
}

const getEstadoColor = (estado) => {
  const estadoNormalizado = String(
    estado || ''
  )
    .trim()
    .toLowerCase()

  if (estadoNormalizado === 'pendiente') {
    return 'orange-8'
  }

  if (estadoNormalizado === 'aceptado') {
    return 'blue-7'
  }

  if (estadoNormalizado === 'en curso') {
    return 'indigo-9'
  }

  if (estadoNormalizado === 'finalizado') {
    return 'green-7'
  }

  if (estadoNormalizado === 'cancelado') {
    return 'red-8'
  }

  if (estadoNormalizado === 'expirado') {
    return 'grey-8'
  }

  return 'grey-7'
}

const obtenerClaveFecha = (valor) => {
  if (!valor) {
    return null
  }

  const texto = String(valor)

  const coincidencia = texto.match(
    /^(\d{4})-(\d{2})-(\d{2})/
  )

  if (coincidencia) {
    return (
      `${coincidencia[1]}-`
      + `${coincidencia[2]}-`
      + `${coincidencia[3]}`
    )
  }

  const fecha = new Date(valor)

  if (Number.isNaN(fecha.getTime())) {
    return null
  }

  return crearClaveFecha(fecha)
}

const crearClaveFecha = (fecha) => {
  const anio = fecha.getFullYear()

  const mes = String(
    fecha.getMonth() + 1
  ).padStart(2, '0')

  const dia = String(
    fecha.getDate()
  ).padStart(2, '0')

  return `${anio}-${mes}-${dia}`
}

const capitalizar = (texto) => {
  if (!texto) {
    return ''
  }

  return (
    texto.charAt(0).toUpperCase()
    + texto.slice(1)
  )
}

const construirGraficoSemanal = (
  solicitudes
) => {
  const hoy = new Date()

  const dias = []

  for (let cantidad = 6; cantidad >= 0; cantidad--) {
    const fecha = new Date(
      hoy.getFullYear(),
      hoy.getMonth(),
      hoy.getDate() - cantidad
    )

    dias.push({
      clave: crearClaveFecha(fecha),

      etiqueta: capitalizar(
        new Intl.DateTimeFormat(
          'es-BO',
          {
            weekday: 'short'
          }
        )
          .format(fecha)
          .replace('.', '')
      ),

      total: 0
    })
  }

  const diasPorClave = new Map(
    dias.map((dia) => [
      dia.clave,
      dia
    ])
  )

  solicitudes.forEach((solicitud) => {
    const clave = obtenerClaveFecha(
      solicitud.fecha
      || solicitud.created_at
    )

    if (
      clave
      && diasPorClave.has(clave)
    ) {
      diasPorClave.get(clave).total++
    }
  })

  chartSeries.value = [
    {
      name: 'Solicitudes',
      data: dias.map((dia) => dia.total)
    }
  ]

  chartOptions.value = {
    ...chartOptions.value,

    xaxis: {
      ...chartOptions.value.xaxis,

      categories: dias.map(
        (dia) => dia.etiqueta
      )
    }
  }
}


const normalizarEstado = (estado) => {
  return String(estado || '')
    .trim()
    .toLowerCase()
}

const estadosViajeActivo = [
  'aceptado',
  'llegó',
  'en curso'
]

const esViajeActivo = (solicitud) => {
  return estadosViajeActivo.includes(
    normalizarEstado(solicitud?.estado)
  )
}

const obtenerViajeActivoConductor = (
  mototaxistaId
) => {
  return solicitudesMonitoreo.value.find(
    (solicitud) => {
      return (
        Number(solicitud.mototaxista_id)
          === Number(mototaxistaId)
        && esViajeActivo(solicitud)
      )
    }
  ) || null
}

const tieneCoordenadasValidas = (registro) => {
  const latitud = Number.parseFloat(
    registro?.latitud
  )

  const longitud = Number.parseFloat(
    registro?.longitud
  )

  return (
    Number.isFinite(latitud)
    && Number.isFinite(longitud)
    && latitud >= -90
    && latitud <= 90
    && longitud >= -180
    && longitud <= 180
  )
}

const obtenerNombreConductor = (mototaxista) => {
  const persona = mototaxista?.persona || {}

  const nombreCompleto = [
    persona.nombre,
    persona.apellido_paterno,
    persona.apellido_materno
  ]
    .filter(Boolean)
    .join(' ')
    .trim()

  return (
    nombreCompleto
    || mototaxista?.nombre
    || `Conductor #${mototaxista?.id}`
  )
}

const obtenerMinutosSinConexion = (
  valor
) => {
  if (!valor) {
    return null
  }

  const textoFecha = String(valor)
    .trim()
    .replace(' ', 'T')

  const fecha = new Date(textoFecha)

  if (Number.isNaN(fecha.getTime())) {
    return null
  }

  const diferencia = (
    Date.now() - fecha.getTime()
  ) / 60000

  return Math.max(
    0,
    Math.floor(diferencia)
  )
}

const obtenerEstadoGps = (
  ultimaConexion,
  tieneUbicacion
) => {
  if (!tieneUbicacion) {
    return {
      estado: 'sin-ubicacion',
      minutos: null,
      texto: 'Sin ubicación',
      colorChip: 'grey-6',
      colorIcono: 'grey-5',
      icono: 'location_off'
    }
  }

  const minutos =
    obtenerMinutosSinConexion(
      ultimaConexion
    )

  if (minutos === null) {
    return {
      estado: 'desconocido',
      minutos: null,
      texto: 'Hora GPS no disponible',
      colorChip: 'grey-6',
      colorIcono: 'grey-6',
      icono: 'help_outline'
    }
  }

  if (minutos <= 3) {
    return {
      estado: 'reciente',
      minutos,
      texto: 'GPS actualizado',
      colorChip: 'green-1',
      colorIcono: 'positive',
      icono: 'gps_fixed'
    }
  }

  if (minutos <= 10) {
    return {
      estado: 'atencion',
      minutos,
      texto: `GPS hace ${minutos} min`,
      colorChip: 'orange-7',
      colorIcono: 'orange-8',
      icono: 'gps_not_fixed'
    }
  }

  return {
    estado: 'desactualizado',
    minutos,
    texto: `GPS desactualizado · ${minutos} min`,
    colorChip: 'negative',
    colorIcono: 'negative',
    icono: 'location_disabled'
  }
}

const conductoresMonitoreados = computed(() => {
  return mototaxistasMonitoreo.value
    .map((mototaxista) => {
      const viaje = obtenerViajeActivoConductor(
        mototaxista.id
      )

      const disponible = Boolean(
        mototaxista.disponible
      )

      const tieneUbicacion =
        tieneCoordenadasValidas(mototaxista)

      const ultimaConexion = (
        mototaxista.ultima_conexion
        || mototaxista.updated_at
        || null
      )

      const informacionGps =
        obtenerEstadoGps(
          ultimaConexion,
          tieneUbicacion
        )

      let textoEstado = 'Fuera de línea'
      let colorAvatar = 'grey-7'
      let prioridad = 3

      if (viaje) {
        textoEstado = `En viaje · ${viaje.estado}`
        colorAvatar = 'blue-8'
        prioridad = 1
      } else if (disponible) {
        textoEstado = 'Disponible para viajes'
        colorAvatar = 'positive'
        prioridad = 2
      }

      return {
        ...mototaxista,
        nombre:
          obtenerNombreConductor(mototaxista),
        viaje,
        disponible,
        tieneUbicacion,
        textoEstado,
        colorAvatar,
        prioridad,
        ultimaConexion,

        estadoGps:
          informacionGps.estado,

        minutosSinConexion:
          informacionGps.minutos,

        textoEstadoGps:
          informacionGps.texto,

        colorEstadoGps:
          informacionGps.colorChip,

        colorIconoGps:
          informacionGps.colorIcono,

        iconoEstadoGps:
          informacionGps.icono
      }
    })
    .sort((a, b) => {
      if (a.prioridad !== b.prioridad) {
        return a.prioridad - b.prioridad
      }

      return a.nombre.localeCompare(
        b.nombre,
        'es'
      )
    })
})

const conductoresFiltrados = computed(() => {
  const termino = String(
    busquedaConductor.value || ''
  )
    .trim()
    .toLowerCase()

  return conductoresMonitoreados.value.filter(
    (conductor) => {
      const coincideBusqueda = (
        termino === ''
        || conductor.nombre
          .toLowerCase()
          .includes(termino)
        || String(
          conductor.viaje?.id || ''
        ).includes(termino)
      )

      if (!coincideBusqueda) {
        return false
      }

      if (
        filtroMonitoreo.value
          === 'disponibles'
      ) {
        return (
          conductor.disponible
          && !conductor.viaje
        )
      }

      if (
        filtroMonitoreo.value
          === 'viaje'
      ) {
        return Boolean(conductor.viaje)
      }

      if (
        filtroMonitoreo.value
          === 'fuera'
      ) {
        return (
          !conductor.disponible
          && !conductor.viaje
        )
      }

      return true
    }
  )
})

const obtenerNombrePersonaCompleto = (
  persona
) => {
  if (!persona) {
    return ''
  }

  return [
    persona.nombre,
    persona.apellido_paterno,
    persona.apellido_materno
  ]
    .filter(Boolean)
    .join(' ')
    .trim()
}

const nombrePasajeroDetalle = computed(() => {
  const persona =
    detalleViaje.value
      ?.pasajero
      ?.persona

  return (
    obtenerNombrePersonaCompleto(persona)
    || detalleViaje.value?.pasajero_nombre
    || 'Pasajero no identificado'
  )
})

const telefonoPasajeroDetalle = computed(() => {
  const persona =
    detalleViaje.value
      ?.pasajero
      ?.persona

  return (
    persona?.telefono
    || persona?.celular
    || detalleViaje.value
      ?.pasajero
      ?.telefono
    || ''
  )
})

const conductorDetalle = computed(() => {
  const mototaxistaId = Number(
    detalleViaje.value?.mototaxista_id
    || detalleViaje.value
      ?.mototaxista
      ?.id
    || 0
  )

  if (!mototaxistaId) {
    return null
  }

  return (
    conductoresMonitoreados.value.find(
      (conductor) => {
        return (
          Number(conductor.id)
            === mototaxistaId
        )
      }
    )
    || null
  )
})

const nombreConductorDetalle = computed(() => {
  const persona =
    detalleViaje.value
      ?.mototaxista
      ?.persona

  return (
    conductorDetalle.value?.nombre
    || obtenerNombrePersonaCompleto(persona)
    || detalleViaje.value?.mototaxista_nombre
    || 'Sin mototaxista asignado'
  )
})

const formatearDistanciaDetalle = (
  distancia
) => {
  const numero =
    Number.parseFloat(distancia)

  return Number.isFinite(numero)
    ? `${numero.toFixed(2)} km`
    : 'No registrada'
}

const formatearHoraDetalle = (hora) => {
  if (!hora) {
    return 'No registrada'
  }

  return String(hora).slice(0, 5)
}

const obtenerServicioPorSolicitud = (
  solicitudId,
  servicios = serviciosMonitoreo.value
) => {
  return servicios.find((servicio) => {
    return (
      Number(
        servicio.id_solicitud
        ?? servicio.solicitud?.id
        ?? 0
      ) === Number(solicitudId)
    )
  }) || null
}

const obtenerPagoPorViaje = (
  solicitudId,
  servicio,
  pagos = pagosMonitoreo.value
) => {
  return pagos.find((pago) => {
    const servicioIdPago = Number(
      pago.id_servicio
      ?? pago.servicio?.id
      ?? 0
    )

    const solicitudIdPago = Number(
      pago.servicio?.id_solicitud
      ?? pago.servicio?.solicitud?.id
      ?? 0
    )

    return (
      (
        servicio
        && servicioIdPago
          === Number(servicio.id)
      )
      || solicitudIdPago
        === Number(solicitudId)
    )
  }) || null
}

const cerrarDetalleViaje = () => {
  detalleViajeAbierto.value = false
}

const abrirDetalleViaje = async (
  viajeOId
) => {
  const solicitudId = Number(
    typeof viajeOId === 'object'
      ? viajeOId?.id
      : viajeOId
  )

  if (!solicitudId) {
    return
  }

  const viajeInicial = (
    typeof viajeOId === 'object'
      ? viajeOId
      : solicitudesMonitoreo.value.find(
        (solicitud) => {
          return (
            Number(solicitud.id)
              === solicitudId
          )
        }
      )
  )

  detalleViaje.value =
    viajeInicial || null

  detalleServicio.value =
    obtenerServicioPorSolicitud(
      solicitudId
    )

  detallePago.value =
    obtenerPagoPorViaje(
      solicitudId,
      detalleServicio.value
    )

  detalleViajeAbierto.value = true
  cargandoDetalleViaje.value = true

  try {
    const [
      respuestaSolicitud,
      respuestaServicios,
      respuestaPagos
    ] = await Promise.all([
      api.get(
        `/solicitudes/${solicitudId}`
      ),

      api.get('/servicios'),

      api.get('/pagos')
    ])

    const servicios = normalizarLista(
      respuestaServicios.data
    )

    const pagos = normalizarLista(
      respuestaPagos.data
    )

    detalleViaje.value =
      respuestaSolicitud.data

    serviciosMonitoreo.value =
      servicios

    pagosMonitoreo.value =
      pagos

    detalleServicio.value =
      obtenerServicioPorSolicitud(
        solicitudId,
        servicios
      )

    detallePago.value =
      obtenerPagoPorViaje(
        solicitudId,
        detalleServicio.value,
        pagos
      )
  } catch (error) {
    console.error(
      'Error cargando el detalle del viaje:',
      error
    )

    if (!detalleViaje.value) {
      detalleViajeAbierto.value = false
    }

    $q.notify({
      type: 'warning',
      icon: 'info',
      message:
        'Se mostró la información disponible, pero algunos datos complementarios no pudieron actualizarse.',
      timeout: 4500
    })
  } finally {
    cargandoDetalleViaje.value = false
  }
}

const verDetalleEnMapa = async () => {
  const conductor =
    conductorDetalle.value

  if (
    !conductor
    || !conductor.tieneUbicacion
  ) {
    return
  }

  detalleViajeAbierto.value = false

  await nextTick()

  centrarConductor(conductor)
}

const irModuloDesdeDetalle = async (
  ruta
) => {
  detalleViajeAbierto.value = false
  await router.push(ruta)
}

const extraerFechaIso = (valor) => {
  if (!valor) {
    return null
  }

  const coincidencia = String(valor)
    .trim()
    .match(/^(\d{4}-\d{2}-\d{2})/)

  return coincidencia
    ? coincidencia[1]
    : null
}

const construirFechaInicioServicio = (
  servicio,
  solicitud
) => {
  if (!servicio) {
    return null
  }

  /*
   * Algunos backends guardan una fecha completa.
   * Se priorizan esos campos cuando existen.
   */
  const fechaCompleta = (
    servicio.iniciado_en
    || servicio.fecha_inicio
  )

  if (fechaCompleta) {
    const fecha = new Date(
      String(fechaCompleta)
        .trim()
        .replace(' ', 'T')
    )

    if (!Number.isNaN(fecha.getTime())) {
      return fecha
    }
  }

  /*
   * En MOTRIX el servicio dispone de hora_inicio.
   * Se combina con la fecha del servicio o solicitud.
   */
  const horaInicio = String(
    servicio.hora_inicio || ''
  ).trim()

  if (!horaInicio) {
    return null
  }

  const fechaBase = (
    extraerFechaIso(servicio.fecha)
    || extraerFechaIso(servicio.created_at)
    || extraerFechaIso(solicitud?.fecha)
    || extraerFechaIso(solicitud?.created_at)
    || extraerFechaIso(solicitud?.updated_at)
  )

  if (!fechaBase) {
    return null
  }

  const horaNormalizada = (
    horaInicio.length === 5
      ? `${horaInicio}:00`
      : horaInicio
  )

  const fecha = new Date(
    `${fechaBase}T${horaNormalizada}`
  )

  return Number.isNaN(fecha.getTime())
    ? null
    : fecha
}

const obtenerMinutosDesdeFecha = (fecha) => {
  if (!(fecha instanceof Date)) {
    return null
  }

  if (Number.isNaN(fecha.getTime())) {
    return null
  }

  const diferencia = (
    Date.now() - fecha.getTime()
  ) / 60000

  /*
   * Si existe una diferencia de zona horaria que
   * deja la fecha unos minutos en el futuro,
   * no se genera una alerta falsa.
   */
  return Math.max(
    0,
    Math.floor(diferencia)
  )
}

const obtenerMinutosSolicitud = (
  solicitud
) => {
  const estado =
    normalizarEstado(solicitud?.estado)

  /*
   * En MOTRIX, hora_inicio se registra cuando
   * el conductor acepta la solicitud.
   *
   * Por eso, tanto Aceptado como En Curso deben
   * calcularse desde el servicio asociado y no
   * desde solicitud.updated_at, que puede conservar
   * una fecha antigua y producir alertas falsas.
   */
  if (
    [
      'aceptado',
      'en curso'
    ].includes(estado)
  ) {
    const servicio =
      obtenerServicioPorSolicitud(
        solicitud.id
      )

    const fechaInicio =
      construirFechaInicioServicio(
        servicio,
        solicitud
      )

    const minutosServicio =
      obtenerMinutosDesdeFecha(
        fechaInicio
      )

    /*
     * Si no se puede determinar el inicio real,
     * es preferible no mostrar una alerta antes
     * que usar una fecha antigua.
     */
    return minutosServicio
  }

  /*
   * Las solicitudes pendientes deben calcularse
   * desde un DATETIME real.
   *
   * El campo fecha es de tipo DATE y representa
   * las 00:00; por eso producía alertas falsas
   * de más de mil minutos.
   */
  if (
    [
      'pendiente',
      'buscando conductor'
    ].includes(estado)
  ) {
    if (!solicitud?.creado_en) {
      /*
       * Sin una hora real no se genera una alerta.
       * Es preferible omitirla antes que mostrar
       * un tiempo falso.
       */
      return null
    }

    return obtenerMinutosSinConexion(
      solicitud.creado_en
    )
  }

  /*
   * Para el estado Llegó se utiliza updated_at.
   * Los estados restantes no generan alertas
   * de duración dentro del monitoreo.
   */
  if (estado === 'llegó') {
    return obtenerMinutosSinConexion(
      solicitud?.updated_at
    )
  }

  return null
}

const crearAlertaOperativa = ({
  clave,
  nivel,
  titulo,
  detalle,
  minutos,
  icono,
  conductorId = null,
  solicitudId = null,
  accionTexto = null
}) => {
  const configuracion = {
    critica: {
      prioridad: 1,
      colorFondo: 'red-1',
      colorTexto: 'negative',
      colorChip: 'negative',
      iconoTiempo: 'priority_high'
    },

    alta: {
      prioridad: 2,
      colorFondo: 'deep-orange-1',
      colorTexto: 'deep-orange-9',
      colorChip: 'deep-orange-8',
      iconoTiempo: 'warning'
    },

    media: {
      prioridad: 3,
      colorFondo: 'amber-1',
      colorTexto: 'amber-10',
      colorChip: 'orange-8',
      iconoTiempo: 'schedule'
    }
  }[nivel]

  return {
    clave,
    nivel,
    prioridad: configuracion.prioridad,
    titulo,
    detalle,
    minutos: minutos ?? 0,
    tiempoTexto: minutos === null
      ? 'Sin hora'
      : `${minutos} min`,
    icono,
    conductorId,
    solicitudId,
    accionTexto,
    ...configuracion
  }
}

const alertasOperativas = computed(() => {
  const alertas = []

  /*
   * Alertas de los conductores que están atendiendo
   * un viaje y requieren ubicación actualizada.
   */
  conductoresMonitoreados.value.forEach(
    (conductor) => {
      if (!conductor.viaje) {
        return
      }

      if (!conductor.tieneUbicacion) {
        alertas.push(
          crearAlertaOperativa({
            clave:
              `viaje-sin-gps-${conductor.id}`,
            nivel: 'critica',
            titulo:
              'Conductor en viaje sin ubicación',
            detalle:
              `${conductor.nombre} atiende el viaje `
              + `#${conductor.viaje.id}, pero no tiene `
              + 'coordenadas registradas.',
            minutos:
              conductor.minutosSinConexion,
            icono: 'location_off',
            conductorId: conductor.id,
            solicitudId: conductor.viaje.id,
            accionTexto:
              'Abrir conductor en el mapa'
          })
        )

        return
      }

      if (
        conductor.estadoGps
          === 'desactualizado'
      ) {
        alertas.push(
          crearAlertaOperativa({
            clave:
              `gps-critico-${conductor.id}`,
            nivel: 'critica',
            titulo:
              'GPS desactualizado durante un viaje',
            detalle:
              `${conductor.nombre} atiende el viaje `
              + `#${conductor.viaje.id} y no actualiza `
              + 'su ubicación.',
            minutos:
              conductor.minutosSinConexion,
            icono: 'location_disabled',
            conductorId: conductor.id,
            solicitudId: conductor.viaje.id,
            accionTexto:
              'Centrar conductor en el mapa'
          })
        )
      } else if (
        conductor.estadoGps === 'atencion'
      ) {
        alertas.push(
          crearAlertaOperativa({
            clave:
              `gps-atencion-${conductor.id}`,
            nivel: 'media',
            titulo:
              'Ubicación del viaje necesita actualizarse',
            detalle:
              `${conductor.nombre} atiende el viaje `
              + `#${conductor.viaje.id}.`,
            minutos:
              conductor.minutosSinConexion,
            icono: 'gps_not_fixed',
            conductorId: conductor.id,
            solicitudId: conductor.viaje.id,
            accionTexto:
              'Revisar conductor'
          })
        )
      }
    }
  )

  /*
   * Alertas por demoras según el estado de cada solicitud.
   * Los tiempos se calculan con fecha/created_at para
   * solicitudes pendientes y updated_at para estados activos.
   */
  solicitudesMonitoreo.value.forEach(
    (solicitud) => {
      const estado =
        normalizarEstado(solicitud.estado)

      const minutos =
        obtenerMinutosSolicitud(solicitud)

      if (minutos === null) {
        return
      }

      if (
        [
          'pendiente',
          'buscando conductor'
        ].includes(estado)
        && minutos >= 5
      ) {
        alertas.push(
          crearAlertaOperativa({
            clave:
              `solicitud-espera-${solicitud.id}`,
            nivel: minutos >= 10
              ? 'critica'
              : 'alta',
            titulo:
              'Solicitud esperando conductor',
            detalle:
              `La solicitud #${solicitud.id} lleva `
              + `${minutos} minutos sin ser aceptada.`,
            minutos,
            icono: 'person_search',
            solicitudId: solicitud.id,
            accionTexto:
              'Abrir módulo de solicitudes'
          })
        )
      }

      if (
        estado === 'aceptado'
        && minutos >= 10
      ) {
        alertas.push(
          crearAlertaOperativa({
            clave:
              `viaje-aceptado-${solicitud.id}`,
            nivel: minutos >= 20
              ? 'alta'
              : 'media',
            titulo:
              'Viaje aceptado con demora',
            detalle:
              `El viaje #${solicitud.id} continúa `
              + `en estado Aceptado desde hace `
              + `${minutos} minutos.`,
            minutos,
            icono: 'schedule',
            conductorId:
              solicitud.mototaxista_id,
            solicitudId: solicitud.id,
            accionTexto:
              'Revisar viaje y conductor'
          })
        )
      }

      if (
        estado === 'llegó'
        && minutos >= 10
      ) {
        alertas.push(
          crearAlertaOperativa({
            clave:
              `viaje-llego-${solicitud.id}`,
            nivel: 'media',
            titulo:
              'Conductor esperando al pasajero',
            detalle:
              `El viaje #${solicitud.id} permanece `
              + `en “Llegó” desde hace `
              + `${minutos} minutos.`,
            minutos,
            icono: 'person_pin_circle',
            conductorId:
              solicitud.mototaxista_id,
            solicitudId: solicitud.id,
            accionTexto:
              'Revisar ubicación del conductor'
          })
        )
      }

      if (
        estado === 'en curso'
        && minutos >= 45
      ) {
        alertas.push(
          crearAlertaOperativa({
            clave:
              `viaje-extenso-${solicitud.id}`,
            nivel: minutos >= 75
              ? 'critica'
              : 'alta',
            titulo:
              'Viaje en curso prolongado',
            detalle:
              `El viaje #${solicitud.id} lleva `
              + `${minutos} minutos en curso.`,
            minutos,
            icono: 'route',
            conductorId:
              solicitud.mototaxista_id,
            solicitudId: solicitud.id,
            accionTexto:
              'Revisar recorrido en el mapa'
          })
        )
      }
    }
  )

  return alertas
    .sort((a, b) => {
      if (a.prioridad !== b.prioridad) {
        return a.prioridad - b.prioridad
      }

      return b.minutos - a.minutos
    })
    .slice(0, 8)
})

const resumenAlertas = computed(() => {
  return {
    criticas: alertasOperativas.value.filter(
      (alerta) => {
        return alerta.nivel === 'critica'
      }
    ).length,

    atencion: alertasOperativas.value.filter(
      (alerta) => {
        return alerta.nivel !== 'critica'
      }
    ).length
  }
})

const atenderAlerta = async (alerta) => {
  if (alerta.solicitudId) {
    await abrirDetalleViaje(
      alerta.solicitudId
    )

    return
  }

  if (alerta.conductorId) {
    const conductor =
      conductoresMonitoreados.value.find(
        (registro) => {
          return (
            Number(registro.id)
              === Number(alerta.conductorId)
          )
        }
      )

    if (
      conductor
      && conductor.tieneUbicacion
    ) {
      centrarConductor(conductor)
    }
  }
}

const resumenMonitoreo = computed(() => {
  const conductores =
    conductoresMonitoreados.value

  return {
    disponibles: conductores.filter(
      (conductor) => {
        return (
          conductor.disponible
          && !conductor.viaje
        )
      }
    ).length,

    enViaje: conductores.filter(
      (conductor) => conductor.viaje
    ).length,

    conUbicacion: conductores.filter(
      (conductor) => conductor.tieneUbicacion
    ).length,

    fueraLinea: conductores.filter(
      (conductor) => {
        return (
          !conductor.disponible
          && !conductor.viaje
        )
      }
    ).length
  }
})

const escaparHtml = (valor) => {
  return String(valor ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;')
}

const formatearFechaHora = (valor) => {
  if (!valor) {
    return 'Sin registro'
  }

  const fecha = new Date(valor)

  if (Number.isNaN(fecha.getTime())) {
    return String(valor)
  }

  return new Intl.DateTimeFormat(
    'es-BO',
    {
      dateStyle: 'short',
      timeStyle: 'short'
    }
  ).format(fecha)
}

const obtenerColorConductor = (conductor) => {
  if (conductor.viaje) {
    return '#1565c0'
  }

  if (conductor.disponible) {
    return '#21ba45'
  }

  return '#757575'
}

const inicializarMapaMonitoreo = () => {
  if (
    mapaMonitoreo
    || !mapaMonitoreoElemento.value
  ) {
    return
  }

  mapaMonitoreo = L.map(
    mapaMonitoreoElemento.value,
    {
      zoomControl: true
    }
  ).setView(
    [-14.8347, -64.9044],
    13
  )

  L.tileLayer(
    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    {
      maxZoom: 19,
      attribution:
        '&copy; OpenStreetMap contributors'
    }
  ).addTo(mapaMonitoreo)

  capaMonitoreo = L.layerGroup()
    .addTo(mapaMonitoreo)

  setTimeout(() => {
    mapaMonitoreo?.invalidateSize()
  }, 150)
}

const crearIconoConductor = (
  color,
  estadoGps
) => {
  const colorBorde = (
    estadoGps === 'desactualizado'
      ? '#c10015'
      : estadoGps === 'atencion'
        ? '#f2a900'
        : '#ffffff'
  )

  return L.divIcon({
    className: 'marcador-conductor-wrapper',

    html: `
      <div
        class="marcador-conductor"
        style="
          background:${color};
          border-color:${colorBorde};
        "
      >
        🏍️
      </div>
    `,

    iconSize: [42, 42],
    iconAnchor: [21, 21],
    popupAnchor: [0, -20]
  })
}

const CENTRO_TRINIDAD = {
  latitud: -14.8347,
  longitud: -64.9044
}

const calcularDistanciaKmMapa = (
  latitudOrigen,
  longitudOrigen,
  latitudDestino,
  longitudDestino
) => {
  const radioTierra = 6371

  const dLat = (
    latitudDestino - latitudOrigen
  ) * Math.PI / 180

  const dLng = (
    longitudDestino - longitudOrigen
  ) * Math.PI / 180

  const lat1 = latitudOrigen
    * Math.PI / 180

  const lat2 = latitudDestino
    * Math.PI / 180

  const a = (
    Math.sin(dLat / 2) ** 2
    + Math.cos(lat1)
    * Math.cos(lat2)
    * Math.sin(dLng / 2) ** 2
  )

  return (
    radioTierra
    * 2
    * Math.atan2(
      Math.sqrt(a),
      Math.sqrt(1 - a)
    )
  )
}

const estaCercaDeTrinidad = (
  latitud,
  longitud,
  limiteKm = 35
) => {
  return calcularDistanciaKmMapa(
    CENTRO_TRINIDAD.latitud,
    CENTRO_TRINIDAD.longitud,
    latitud,
    longitud
  ) <= limiteKm
}

/*
 * Separa visualmente los conductores que tienen coordenadas iguales
 * o demasiado cercanas. La ubicación real no se modifica en la base
 * de datos; solamente cambia unos metros la posición del marcador.
 */
const construirPosicionesVisuales = (
  conductores
) => {
  const grupos = new Map()
  const posiciones = new Map()

  conductores.forEach((conductor) => {
    if (!conductor.tieneUbicacion) {
      return
    }

    const latitud = Number.parseFloat(
      conductor.latitud
    )

    const longitud = Number.parseFloat(
      conductor.longitud
    )

    /*
     * Cuatro decimales agrupan posiciones que están aproximadamente
     * dentro de una distancia de 10 a 12 metros.
     */
    const clave = [
      latitud.toFixed(4),
      longitud.toFixed(4)
    ].join('|')

    if (!grupos.has(clave)) {
      grupos.set(clave, [])
    }

    grupos.get(clave).push({
      conductor,
      latitud,
      longitud
    })
  })

  grupos.forEach((grupo) => {
    if (grupo.length === 1) {
      const registro = grupo[0]

      posiciones.set(
        Number(registro.conductor.id),
        [
          registro.latitud,
          registro.longitud
        ]
      )

      return
    }

    grupo.forEach((registro, indice) => {
      const angulo = (
        2
        * Math.PI
        * indice
        / grupo.length
      )

      const radio = (
        0.00016
        + Math.floor(indice / 8)
        * 0.00008
      )

      const desplazamientoLatitud =
        Math.cos(angulo) * radio

      const cosenoLatitud = Math.max(
        Math.cos(
          registro.latitud
          * Math.PI
          / 180
        ),
        0.2
      )

      const desplazamientoLongitud =
        Math.sin(angulo)
        * radio
        / cosenoLatitud

      posiciones.set(
        Number(registro.conductor.id),
        [
          registro.latitud
            + desplazamientoLatitud,

          registro.longitud
            + desplazamientoLongitud
        ]
      )
    })
  })

  return posiciones
}

const actualizarMapaMonitoreo = (
  ajustarVista = false
) => {
  if (
    !mapaMonitoreo
    || !capaMonitoreo
  ) {
    return
  }

  capaMonitoreo.clearLayers()
  marcadoresConductores = new Map()

  const puntosConductoresTrinidad = []

  const posicionesVisuales =
    construirPosicionesVisuales(
      conductoresFiltrados.value
    )

  conductoresFiltrados.value.forEach(
    (conductor) => {
      if (!conductor.tieneUbicacion) {
        return
      }

      const latitudReal = Number.parseFloat(
        conductor.latitud
      )

      const longitudReal = Number.parseFloat(
        conductor.longitud
      )

      const posicionVisual = (
        posicionesVisuales.get(
          Number(conductor.id)
        )
        || [
          latitudReal,
          longitudReal
        ]
      )

      if (
        estaCercaDeTrinidad(
          latitudReal,
          longitudReal
        )
      ) {
        puntosConductoresTrinidad.push(
          posicionVisual
        )
      }

      const color =
        obtenerColorConductor(conductor)

      const viajeTexto = conductor.viaje
        ? `
          <div>
            <b>Viaje:</b>
            #${escaparHtml(conductor.viaje.id)}
            · ${escaparHtml(conductor.viaje.estado)}
          </div>
          <div>
            <b>Origen:</b>
            ${escaparHtml(
              conductor.viaje.origen
              || 'Sin origen'
            )}
          </div>
          <div>
            <b>Destino:</b>
            ${escaparHtml(
              conductor.viaje.destino
              || 'Sin destino'
            )}
          </div>
        `
        : ''

      const marcador = L.marker(
        posicionVisual,
        {
          icon: crearIconoConductor(
            color,
            conductor.estadoGps
          ),
          zIndexOffset: conductor.viaje
            ? 1000
            : 500
        }
      )
        .bindTooltip(
          escaparHtml(conductor.nombre),
          {
            direction: 'top',
            offset: [0, -22]
          }
        )
        .bindPopup(`
          <div class="popup-monitoreo">
            <strong>
              ${escaparHtml(conductor.nombre)}
            </strong>
            <div>
              ${escaparHtml(
                conductor.textoEstado
              )}
            </div>
            ${viajeTexto}
            <div>
              <b>Última conexión:</b>
              ${escaparHtml(
                formatearFechaHora(
                  conductor.ultimaConexion
                )
              )}
            </div>
            <div>
              <b>Estado GPS:</b>
              ${escaparHtml(
                conductor.textoEstadoGps
              )}
            </div>
            ${
              conductor.viaje
                ? `
                  <button
                    type="button"
                    class="popup-boton-detalle"
                    data-solicitud-id="${
                      escaparHtml(
                        conductor.viaje.id
                      )
                    }"
                  >
                    Ver detalle del viaje
                  </button>
                `
                : ''
            }
          </div>
        `)
        .on('popupopen', (evento) => {
          const elementoPopup =
            evento.popup.getElement()

          const botonDetalle =
            elementoPopup?.querySelector(
              '.popup-boton-detalle'
            )

          if (botonDetalle) {
            botonDetalle.addEventListener(
              'click',
              () => {
                abrirDetalleViaje(
                  botonDetalle.dataset
                    .solicitudId
                )
              },
              {
                once: true
              }
            )
          }
        })
        .addTo(capaMonitoreo)

      marcadoresConductores.set(
        Number(conductor.id),
        marcador
      )

      const viaje = conductor.viaje

      const origenLatitud =
        Number.parseFloat(
          viaje?.latitud_origen
        )

      const origenLongitud =
        Number.parseFloat(
          viaje?.longitud_origen
        )

      const destinoLatitud =
        Number.parseFloat(
          viaje?.latitud_destino
        )

      const destinoLongitud =
        Number.parseFloat(
          viaje?.longitud_destino
        )

      const origenValido = (
        Number.isFinite(origenLatitud)
        && Number.isFinite(origenLongitud)
      )

      const destinoValido = (
        Number.isFinite(destinoLatitud)
        && Number.isFinite(destinoLongitud)
      )

      /*
       * La ruta solamente se dibuja cuando conductor, origen y destino
       * están dentro del área de Trinidad. Así una coordenada antigua o
       * incorrecta no aleja ni distorsiona el mapa administrativo.
       */
      const rutaCercanaATrinidad = (
        viaje
        && origenValido
        && destinoValido
        && estaCercaDeTrinidad(
          latitudReal,
          longitudReal
        )
        && estaCercaDeTrinidad(
          origenLatitud,
          origenLongitud
        )
        && estaCercaDeTrinidad(
          destinoLatitud,
          destinoLongitud
        )
      )

      if (rutaCercanaATrinidad) {
        const origen = [
          origenLatitud,
          origenLongitud
        ]

        const destino = [
          destinoLatitud,
          destinoLongitud
        ]

        L.polyline(
          [
            posicionVisual,
            origen,
            destino
          ],
          {
            color: '#1565c0',
            weight: 4,
            opacity: 0.75,
            dashArray: '8 8'
          }
        ).addTo(capaMonitoreo)
      }
    }
  )

  if (ajustarVista) {
    if (
      puntosConductoresTrinidad.length === 1
    ) {
      mapaMonitoreo.setView(
        puntosConductoresTrinidad[0],
        15,
        {
          animate: true
        }
      )
    } else if (
      puntosConductoresTrinidad.length > 1
    ) {
      mapaMonitoreo.fitBounds(
        L.latLngBounds(
          puntosConductoresTrinidad
        ),
        {
          padding: [45, 45],
          maxZoom: 15
        }
      )
    } else {
      mapaMonitoreo.setView(
        [
          CENTRO_TRINIDAD.latitud,
          CENTRO_TRINIDAD.longitud
        ],
        13
      )
    }
  }

  setTimeout(() => {
    mapaMonitoreo?.invalidateSize()
  }, 100)
}

const centrarConductor = (conductor) => {
  if (
    !mapaMonitoreo
    || !conductor.tieneUbicacion
  ) {
    $q.notify({
      type: 'warning',
      message:
        'Este conductor todavía no tiene una ubicación registrada.'
    })

    return
  }

  const marcador =
    marcadoresConductores.get(
      Number(conductor.id)
    )

  if (marcador) {
    mapaMonitoreo.setView(
      marcador.getLatLng(),
      17,
      {
        animate: true
      }
    )

    setTimeout(() => {
      marcador.openPopup()
    }, 250)

    return
  }

  mapaMonitoreo.setView(
    [
      Number.parseFloat(
        conductor.latitud
      ),
      Number.parseFloat(
        conductor.longitud
      )
    ],
    17,
    {
      animate: true
    }
  )
}


const extraerNombreIncidencia = (entidad) => {
  return (
    entidad?.persona?.nombre
    || entidad?.persona?.nombres
    || entidad?.nombre
    || entidad?.nombre_completo
    || null
  )
}

const nombrePasajeroIncidencia = (incidencia) => {
  return (
    extraerNombreIncidencia(
      incidencia?.solicitud?.pasajero
    )
    || `Pasajero #${
      incidencia?.solicitud?.id_pasajero
      || '—'
    }`
  )
}

const nombreConductorIncidencia = (incidencia) => {
  return (
    extraerNombreIncidencia(
      incidencia?.solicitud?.mototaxista
    )
    || (
      incidencia?.solicitud?.mototaxista_id
        ? `Conductor #${
          incidencia.solicitud.mototaxista_id
        }`
        : 'Sin conductor'
    )
  )
}

const colorEstadoIncidencia = (estado) => {
  const valor = String(estado || '').trim().toLowerCase()

  if (valor === 'reportado') return 'negative'
  if (valor === 'recibido') return 'orange-9'
  if (valor === 'en atención') return 'primary'
  if (valor === 'resuelto') return 'positive'

  return 'grey-7'
}

const colorPrioridadIncidencia = (prioridad) => {
  const valor = String(prioridad || '').trim().toLowerCase()

  if (valor === 'crítica') return 'negative'
  if (valor === 'alta') return 'deep-orange-8'
  return 'amber-9'
}

const iconoTipoIncidencia = (tipo) => {
  const valor = String(tipo || '').trim().toLowerCase()

  if (valor === 'accidente') return 'car_crash'
  if (valor === 'emergencia médica') return 'medical_services'
  if (valor === 'situación de inseguridad') return 'gpp_bad'
  if (valor === 'falla de la motocicleta') return 'build'
  if (valor.includes('no localizado')) return 'person_search'

  return 'sos'
}

const formatearFechaHoraIncidencia = (fecha) => {
  if (!fecha) return 'Fecha no disponible'

  const texto = String(fecha)
  const valor = texto.includes('T')
    ? new Date(texto)
    : new Date(texto.replace(' ', 'T'))

  if (Number.isNaN(valor.getTime())) {
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
  ).format(valor)
}

const tieneUbicacionIncidencia = (incidencia) => {
  return (
    Number.isFinite(
      Number.parseFloat(incidencia?.latitud)
    )
    && Number.isFinite(
      Number.parseFloat(incidencia?.longitud)
    )
  )
}

const abrirUbicacionIncidencia = (incidencia) => {
  if (!tieneUbicacionIncidencia(incidencia)) return

  const latitud = Number.parseFloat(incidencia.latitud)
  const longitud = Number.parseFloat(incidencia.longitud)

  window.open(
    `https://www.openstreetmap.org/?mlat=${latitud}&mlon=${longitud}#map=18/${latitud}/${longitud}`,
    '_blank',
    'noopener,noreferrer'
  )
}

const incidenciasFiltradas = computed(() => {
  const texto = String(
    busquedaIncidencia.value || ''
  )
    .trim()
    .toLowerCase()

  return incidencias.value.filter((incidencia) => {
    const coincideEstado =
      filtroEstadoIncidencia.value === 'todos'
      || (
        filtroEstadoIncidencia.value === 'activas'
          ? [
            'Reportado',
            'Recibido',
            'En atención'
          ].includes(incidencia?.estado)
          : incidencia?.estado
            === filtroEstadoIncidencia.value
      )

    const coincideTipo =
      filtroTipoIncidencia.value === 'todos'
      || incidencia?.tipo
        === filtroTipoIncidencia.value

    const contenido = [
      incidencia?.codigo,
      incidencia?.solicitud_id,
      incidencia?.tipo,
      incidencia?.estado,
      incidencia?.reportado_por_nombre,
      nombrePasajeroIncidencia(incidencia),
      nombreConductorIncidencia(incidencia)
    ]
      .join(' ')
      .toLowerCase()

    const coincideBusqueda =
      !texto
      || contenido.includes(texto)

    return (
      coincideEstado
      && coincideTipo
      && coincideBusqueda
    )
  })
})

const cargarIncidencias = async (
  mostrarError = false
) => {
  cargandoIncidencias.value = true

  try {
    const response = await api.get('/incidencias')

    incidencias.value = Array.isArray(
      response?.data?.incidencias
    )
      ? response.data.incidencias
      : []

    resumenIncidencias.value = {
      reportadas: Number(
        response?.data?.resumen?.reportadas || 0
      ),
      recibidas: Number(
        response?.data?.resumen?.recibidas || 0
      ),
      en_atencion: Number(
        response?.data?.resumen?.en_atencion || 0
      ),
      resueltas: Number(
        response?.data?.resumen?.resueltas || 0
      ),
      activas: Number(
        response?.data?.resumen?.activas || 0
      )
    }
  } catch (error) {
    console.error(
      'Error cargando incidencias SOS:',
      error
    )

    if (mostrarError) {
      $q.notify({
        type: 'negative',
        icon: 'error',
        message:
          error?.response?.data?.message
          || 'No se pudieron cargar las incidencias.',
        position: 'top'
      })
    }
  } finally {
    cargandoIncidencias.value = false
  }
}

const actualizarEstadoIncidencia = async (
  incidencia,
  estado,
  notaAdministrador = null
) => {
  if (
    !incidencia?.id
    || incidenciaActualizandoId.value
  ) {
    return
  }

  incidenciaActualizandoId.value = incidencia.id

  try {
    const response = await api.put(
      `/incidencias/${incidencia.id}/estado`,
      {
        estado,
        nota_administrador:
          notaAdministrador
      }
    )

    const actualizada =
      response?.data?.incidencia

    if (actualizada) {
      const indice = incidencias.value.findIndex(
        item => Number(item.id)
          === Number(actualizada.id)
      )

      if (indice >= 0) {
        incidencias.value.splice(
          indice,
          1,
          actualizada
        )
      }
    }

    await cargarIncidencias(false)

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
    incidenciaActualizandoId.value = null
  }
}

const confirmarResolverIncidencia = (incidencia) => {
  $q.dialog({
    title: `Resolver ${incidencia?.codigo || 'incidencia'}`,
    message:
      'Escribe una nota breve con la atención o solución aplicada.',
    prompt: {
      model: incidencia?.nota_administrador || '',
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
    actualizarEstadoIncidencia(
      incidencia,
      'Resuelto',
      String(nota || '').trim() || null
    )
  })
}

const manejarCambioIncidenciaGlobal = () => {
  cargarIncidencias(false)
}

const desplazarCentroIncidencias = () => {
  if (
    window.location.hash
    !== '#centro-incidencias'
  ) {
    return
  }

  window.setTimeout(() => {
    centroIncidenciasElemento.value
      ?.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      })
  }, 250)
}

const cargarMonitoreo = async (
  ajustarVista = false
) => {
  refrescandoMonitoreo.value = true

  try {
    const [
      respuestaMototaxistas,
      respuestaSolicitudes,
      respuestaServicios
    ] = await Promise.all([
      api.get('/mototaxistas'),
      api.get('/solicitudes'),
      api.get('/servicios')
    ])

    mototaxistasMonitoreo.value =
      normalizarLista(
        respuestaMototaxistas.data
      )

    solicitudesMonitoreo.value =
      normalizarLista(
        respuestaSolicitudes.data
      )

    serviciosMonitoreo.value =
      normalizarLista(
        respuestaServicios.data
      )

    ultimaActualizacionMonitoreo.value =
      new Intl.DateTimeFormat(
        'es-BO',
        {
          hour: '2-digit',
          minute: '2-digit',
          second: '2-digit'
        }
      ).format(new Date())

    await nextTick()

    inicializarMapaMonitoreo()

    actualizarMapaMonitoreo(
      ajustarVista
    )
  } catch (error) {
    console.error(
      'Error actualizando el monitoreo:',
      error
    )
  } finally {
    refrescandoMonitoreo.value = false
  }
}

const cargarDatosDashboard = async () => {
  loading.value = true

  try {
    /*
     * En la base integrada algunos módulos pueden estar en proceso
     * de adaptación. No dejamos que un solo endpoint con error ponga
     * TODO el dashboard en cero. Cada recurso se consulta de forma
     * independiente y se conserva lo que sí pudo cargarse.
     */
    const recursosDashboard = [
      ['personas', '/personas'],
      ['mototaxistas', '/mototaxistas'],
      ['motocicletas', '/motocicletas'],
      ['pasajeros', '/pasajeros'],
      ['sindicatos', '/sindicatos'],
      ['solicitudes', '/solicitudes'],
      ['servicios', '/servicios'],
      ['pagos', '/pagos']
    ]

    const resultadosDashboard = await Promise.all(
      recursosDashboard.map(
        async ([nombre, ruta]) => {
          try {
            const respuesta = await api.get(ruta)

            return {
              nombre,
              datos: normalizarLista(respuesta.data),
              error: null
            }
          } catch (error) {
            console.error(
              `Error cargando ${nombre} desde ${ruta}:`,
              error
            )

            return {
              nombre,
              datos: [],
              error
            }
          }
        }
      )
    )

    const mapaResultados = Object.fromEntries(
      resultadosDashboard.map((resultado) => [
        resultado.nombre,
        resultado.datos
      ])
    )

    const personas = mapaResultados.personas || []
    const mototaxistas = mapaResultados.mototaxistas || []
    const motocicletas = mapaResultados.motocicletas || []
    const pasajeros = mapaResultados.pasajeros || []
    const sindicatos = mapaResultados.sindicatos || []
    const solicitudes = mapaResultados.solicitudes || []
    const servicios = mapaResultados.servicios || []
    const pagos = mapaResultados.pagos || []

    const modulosConError = resultadosDashboard
      .filter((resultado) => resultado.error)
      .map((resultado) => resultado.nombre)

    if (modulosConError.length) {
      $q.notify({
        type: 'warning',
        position: 'top',
        timeout: 7000,
        message:
          'La base integrada funciona, pero faltan adaptar estos módulos: '
          + modulosConError.join(', ')
      })
    }

    serviciosMonitoreo.value =
      servicios

    pagosMonitoreo.value =
      pagos

    mototaxistasMonitoreo.value =
      mototaxistas

    solicitudesMonitoreo.value =
      solicitudes

    ultimaActualizacionMonitoreo.value =
      new Intl.DateTimeFormat(
        'es-BO',
        {
          hour: '2-digit',
          minute: '2-digit',
          second: '2-digit'
        }
      ).format(new Date())

    await nextTick()

    actualizarMapaMonitoreo(false)

    const serviciosFinalizados =
      servicios.filter((servicio) => {
        const estado = String(
          servicio.estado || ''
        )
          .trim()
          .toLowerCase()

        return (
          estado === 'finalizado'
          || estado === 'completado'
        )
      })

    const pagosValidos = pagos.filter(
      (pago) => {
        const estado = String(
          pago.estado || ''
        )
          .trim()
          .toLowerCase()

        return ![
          'pendiente',
          'cancelado',
          'anulado',
          'rechazado'
        ].includes(estado)
      }
    )

    const totalPagos = pagosValidos.reduce(
      (total, pago) => {
        return (
          total
          + (
            Number.parseFloat(pago.monto)
            || 0
          )
        )
      },
      0
    )

    stats.value = {
      personas: personas.length,
      mototaxistas: mototaxistas.length,
      motocicletas: motocicletas.length,
      pasajeros: pasajeros.length,
      sindicatos: sindicatos.length,
      solicitudes: solicitudes.length,
      servicios: servicios.length,
      pagos: totalPagos.toFixed(2),
      reportes: serviciosFinalizados.length
    }

    ultimosViajes.value = [
      ...solicitudes
    ]
      .sort((a, b) => {
        return Number(b.id) - Number(a.id)
      })
      .slice(0, 4)

    construirGraficoSemanal(solicitudes)
  } catch (error) {
    console.error(
      'Error al cargar el dashboard:',
      error
    )

    const estadoHttp =
      error?.response?.status

    const mensajeBackend =
      error?.response?.data?.message

    if (estadoHttp === 401) {
      $q.notify({
        type: 'negative',
        message:
          'La sesión expiró. Inicia sesión nuevamente.',
        timeout: 5000
      })

      return
    }

    $q.notify({
      type: 'negative',
      message:
        mensajeBackend
        || 'No se pudieron cargar las estadísticas del backend.',
      timeout: 5000
    })

    stats.value = {
      personas: 0,
      mototaxistas: 0,
      motocicletas: 0,
      pasajeros: 0,
      sindicatos: 0,
      solicitudes: 0,
      servicios: 0,
      pagos: '0.00',
      reportes: 0
    }

    ultimosViajes.value = []
    mototaxistasMonitoreo.value = []
    solicitudesMonitoreo.value = []
    serviciosMonitoreo.value = []
    pagosMonitoreo.value = []

    actualizarMapaMonitoreo(false)

    construirGraficoSemanal([])
  } finally {
    loading.value = false
  }
}

const manejarEstadoWebsocketGlobal = (evento) => {
  wsConnected.value = Boolean(
    evento?.detail?.conectado
  )
}

const actualizarDashboardPorEvento = async (
  evento
) => {
  if (actualizacionTiempoRealEnCurso) {
    return
  }

  actualizacionTiempoRealEnCurso = true

  try {
    const solicitudId = Number(
      evento?.detail?.solicitud?.id || 0
    )

    await cargarDatosDashboard()

    if (solicitudId > 0) {
      const solicitudDestacada =
        ultimosViajes.value.find(
          viaje =>
            Number(viaje.id)
            === solicitudId
        )

      if (solicitudDestacada) {
        solicitudDestacada.esNuevo = true

        window.setTimeout(
          () => {
            solicitudDestacada.esNuevo = false
          },
          3000
        )
      }
    }
  } catch (error) {
    console.error(
      'Error actualizando el monitoreo por evento:',
      error
    )
  } finally {
    actualizacionTiempoRealEnCurso = false
  }
}

const manejarCambioSolicitudGlobal = (
  evento
) => {
  if (temporizadorEventoTiempoReal) {
    window.clearTimeout(
      temporizadorEventoTiempoReal
    )
  }

  temporizadorEventoTiempoReal =
    window.setTimeout(
      () => {
        actualizarDashboardPorEvento(evento)
      },
      180
    )
}

watch(
  [
    filtroMonitoreo,
    busquedaConductor
  ],
  async () => {
    await nextTick()
    actualizarMapaMonitoreo(true)
  }
)

onMounted(async () => {
  window.addEventListener(
    'motrix:solicitud-cambio',
    manejarCambioSolicitudGlobal
  )

  window.addEventListener(
    'motrix:ws-status',
    manejarEstadoWebsocketGlobal
  )

  window.addEventListener(
    'motrix:incidencia-cambio',
    manejarCambioIncidenciaGlobal
  )

  wsConnected.value = Boolean(
    window.__MOTRIX_ADMIN_WS_CONNECTED__
  )

  await Promise.all([
    cargarDatosDashboard(),
    cargarIncidencias(false)
  ])
  await nextTick()

  desplazarCentroIncidencias()
  inicializarMapaMonitoreo()
  actualizarMapaMonitoreo(true)

  intervaloMonitoreo = window.setInterval(
    () => {
      cargarMonitoreo(false)
      cargarIncidencias(false)
    },
    10000
  )
})

onBeforeUnmount(() => {
  window.removeEventListener(
    'motrix:solicitud-cambio',
    manejarCambioSolicitudGlobal
  )

  window.removeEventListener(
    'motrix:ws-status',
    manejarEstadoWebsocketGlobal
  )

  window.removeEventListener(
    'motrix:incidencia-cambio',
    manejarCambioIncidenciaGlobal
  )

  if (temporizadorEventoTiempoReal) {
    window.clearTimeout(
      temporizadorEventoTiempoReal
    )

    temporizadorEventoTiempoReal = null
  }

  if (intervaloMonitoreo) {
    window.clearInterval(
      intervaloMonitoreo
    )
  }

  if (mapaMonitoreo) {
    mapaMonitoreo.remove()
    mapaMonitoreo = null
    capaMonitoreo = null
    marcadoresConductores = new Map()
  }

})
</script>

<style scoped>
.container-dashboard {
  max-width: 1400px;
  margin: 0 auto;
}

.my-dashboard-card {
  height: 100%;
  background-color: #ffffff;
  border-radius: 12px;

  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease;
}

.my-dashboard-card:hover {
  transform: translateY(-4px);

  box-shadow:
    0 8px 24px
    rgba(0, 0, 0, 0.08) !important;
}

.max-width-md {
  max-width: 600px;
}

.mx-auto {
  margin-right: auto;
  margin-left: auto;
}

.card-igualdad {
  display: flex;
  flex-direction: column;
  height: 420px;
}

.scroll-area-solicitudes {
  flex-grow: 1;
  max-height: 330px;
  overflow-y: auto;
}

.border-radius-md {
  border-radius: 12px;
}

.truncate-address {
  max-width: 220px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

@media (min-width: 1024px) {
  .col-md {
    flex: 0 0 20% !important;
    max-width: 20% !important;
  }
}

@keyframes parpadeoFondo {
  0% {
    background-color:
      rgba(2, 123, 227, 0.15);
  }

  50% {
    background-color:
      rgba(2, 123, 227, 0.05);
  }

  100% {
    background-color: transparent;
  }
}

.nuevo-viaje-animacion {
  padding-left: 8px !important;
  border-left: 4px solid #027be3;
  border-radius: 4px;

  animation:
    parpadeoFondo
    1s ease-in-out
    infinite;
}


.detalle-viaje-card {
  width: min(920px, 96vw);
  max-width: 920px;
  max-height: 92vh;
  border-radius: 16px;
  overflow-y: auto;
}

.detalle-viaje-header {
  background:
    linear-gradient(
      90deg,
      #155f25 0%,
      #23752f 55%,
      #2f873b 100%
    );
}

.opacity-80 {
  opacity: 0.8;
}

.dato-resumen-viaje {
  display: flex;
  align-items: center;
  min-height: 76px;
  padding: 12px;
  gap: 10px;
  border: 1px solid #dde7d9;
  border-radius: 12px;
  background: #f8fbf6;
}

.dato-resumen-label {
  color: #778279;
  font-size: 11px;
}

.dato-resumen-valor {
  color: #273c2b;
  font-size: 14px;
  font-weight: 800;
  line-height: 1.25;
}

.seccion-detalle-viaje {
  border-color: #dce5d9;
  border-radius: 12px;
}

.ruta-detalle-viaje {
  padding-left: 5px;
}

.ruta-detalle-punto {
  display: grid;
  grid-template-columns: 25px minmax(0, 1fr);
  align-items: start;
  gap: 10px;
}

.ruta-circulo {
  width: 14px;
  height: 14px;
  margin-top: 3px;
  margin-left: 5px;
  border: 3px solid #ffffff;
  border-radius: 50%;
  box-shadow: 0 0 0 2px #21ba45;
}

.ruta-circulo-origen {
  background: #21ba45;
}

.ruta-linea-detalle {
  width: 2px;
  height: 28px;
  margin: 4px 0 4px 11px;
  border-left: 2px dashed #a7b6a8;
}

.detalle-datos-lista {
  display: grid;
  gap: 9px;
}

.detalle-datos-lista > div {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding-bottom: 8px;
  border-bottom: 1px solid #edf1eb;
}

.detalle-datos-lista > div:last-child {
  padding-bottom: 0;
  border-bottom: 0;
}

.detalle-datos-lista span {
  color: #7a847c;
  font-size: 12px;
}

.detalle-datos-lista strong {
  color: #2d4131;
  text-align: right;
}

.calificacion-detalle-viaje {
  border-color: #f1d78c;
  background: #fffaf0;
}

.comentario-calificacion-detalle {
  padding: 12px;
  color: #7c5a00;
  border-radius: 9px;
  background: #fff3cd;
  font-style: italic;
}

.boton-detalle-viaje {
  width: fit-content;
  min-height: 28px;
  padding-right: 4px;
  padding-left: 0;
  font-size: 12px;
}

:deep(.popup-boton-detalle) {
  width: 100%;
  margin-top: 8px;
  padding: 7px 10px;
  color: #ffffff;
  border: 0;
  border-radius: 6px;
  background: #23752f;
  cursor: pointer;
  font-weight: 700;
}

:deep(.popup-boton-detalle:hover) {
  background: #175d23;
}

@media (max-width: 599px) {
  .detalle-viaje-card {
    width: 100vw;
    max-width: none;
    max-height: 100vh;
    border-radius: 0;
  }

  .detalle-viaje-header {
    position: sticky;
    top: 0;
    z-index: 3;
  }

  .dato-resumen-viaje {
    min-height: 70px;
    padding: 10px;
  }
}

.alertas-operativas {
  border-color: #dde6da;
  border-radius: 12px;
  overflow: hidden;
  background: #ffffff;
}

.lista-alertas {
  max-height: 330px;
  overflow-y: auto;
}

.estado-operativo-normal {
  display: flex;
  align-items: center;
  padding-top: 14px;
  padding-bottom: 14px;
  background: #f2fbf2;
}

.alertas-operativas :deep(.q-item) {
  min-height: 70px;
}

.alertas-operativas :deep(.q-chip) {
  white-space: nowrap;
}

@media (max-width: 599px) {
  .alertas-operativas :deep(.q-item__section--side) {
    padding-left: 6px;
  }

  .alertas-operativas :deep(.q-chip) {
    font-size: 10px;
  }
}

.mapa-monitoreo {
  width: 100%;
  min-height: 460px;
  border: 1px solid #dfe7df;
  border-radius: 14px;
  background: #eef3ee;
  overflow: hidden;
  z-index: 1;
}

.gps-status-chip {
  display: inline-flex;
  width: fit-content;
  max-width: 100%;
  align-self: flex-start;
  padding-right: 8px;
  padding-left: 8px;
}

.gps-status-chip :deep(.q-chip__content) {
  white-space: nowrap;
}

.filtros-monitoreo {
  border: 1px solid #dfe5df;
  border-radius: 8px;
  overflow: hidden;
}

.filtros-monitoreo :deep(.q-btn) {
  min-height: 38px;
  padding-right: 6px;
  padding-left: 6px;
  font-size: 11px;
}

.lista-conductores {
  max-height: 460px;
  overflow-y: auto;
  border-color: #e0e6e0;
  border-radius: 12px;
}

.bordered-empty {
  border: 1px dashed #cfd8cf;
  border-radius: 12px;
}

.resumen-monitoreo {
  display: flex;
  align-items: center;
  min-height: 78px;
  padding: 12px 14px;
  gap: 12px;
  border-radius: 12px;
}

.resumen-disponible {
  color: #116b2e;
  background: #e8f5e9;
}

.resumen-viaje {
  color: #0d47a1;
  background: #e3f2fd;
}

.resumen-ubicacion {
  color: #00695c;
  background: #e0f2f1;
}

.resumen-desconectado {
  color: #424242;
  background: #eeeeee;
}

.leyenda-mapa {
  color: #616161;
  font-size: 12px;
}

.leyenda-mapa span {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.punto-leyenda {
  display: inline-block;
  width: 11px;
  height: 11px;
  border: 2px solid #ffffff;
  border-radius: 50%;
  box-shadow: 0 0 0 1px
    rgba(0, 0, 0, 0.18);
}

.punto-disponible {
  background: #21ba45;
}

.punto-viaje {
  background: #1565c0;
}

.punto-desconectado {
  background: #757575;
}

:deep(.marcador-conductor-wrapper) {
  background: transparent;
  border: 0;
}

:deep(.marcador-conductor) {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 42px;
  height: 42px;
  border: 3px solid #ffffff;
  border-radius: 50%;
  box-shadow:
    0 4px 12px
    rgba(0, 0, 0, 0.3);
  font-size: 21px;
}

:deep(.popup-monitoreo) {
  min-width: 210px;
  line-height: 1.55;
}

@media (max-width: 599px) {
  .mapa-monitoreo {
    min-height: 360px;
  }

  .lista-conductores {
    max-height: 360px;
  }
}


.centro-incidencias-anchor {
  scroll-margin-top: 90px;
}

.centro-incidencias-card {
  border: 1px solid #efb2b2;
  border-radius: 14px;
}

.resumen-incidencia {
  display: flex;
  align-items: center;
  min-height: 86px;
  padding: 14px;
  gap: 12px;
  border-radius: 12px;
}

.resumen-incidencia-reportada {
  color: #b71c1c;
  background: #ffebee;
}

.resumen-incidencia-recibida {
  color: #e65100;
  background: #fff3e0;
}

.resumen-incidencia-atencion {
  color: #0d47a1;
  background: #e3f2fd;
}

.resumen-incidencia-resuelta {
  color: #1b5e20;
  background: #e8f5e9;
}

.lista-incidencias-sos {
  max-height: 620px;
  overflow-y: auto;
  border-color: #ead6d6;
  border-radius: 12px;
}

.incidencia-sos-item {
  align-items: flex-start;
}

.incidencia-descripcion {
  padding: 10px 12px;
  color: #5d3030;
  border-left: 4px solid #c10015;
  border-radius: 5px;
  background: #fff4f5;
  white-space: normal;
}

.incidencia-nota {
  padding: 8px 10px;
  color: #075e54;
  border-radius: 6px;
  background: #e8f5e9;
  white-space: normal;
}

.acciones-incidencia {
  min-width: 120px;
}

@media (max-width: 700px) {
  .incidencia-sos-item {
    flex-wrap: wrap;
  }

  .incidencia-sos-item :deep(.q-item__section--side) {
    width: 100%;
    padding-top: 12px;
    padding-left: 60px;
  }

  .acciones-incidencia {
    width: 100%;
  }

  .acciones-incidencia :deep(.q-btn) {
    width: 100%;
  }
}

</style>