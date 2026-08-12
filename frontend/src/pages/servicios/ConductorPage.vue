<template>
  <q-page
    :class="$q.screen.lt.sm
      ? 'q-pa-sm bg-grey-2'
      : 'q-pa-md bg-grey-2'"
  >
    <!-- ENCABEZADO -->
    <q-card class="bg-primary text-white border-radius-lg shadow-2 q-mb-md">
      <q-card-section class="row items-center q-col-gutter-md">
        <div class="col">
          <div class="row items-center no-wrap">
            <q-avatar
              color="white"
              text-color="primary"
              icon="two_wheeler"
              size="48px"
              class="q-mr-md"
            />

            <div class="min-width-zero">
              <div class="text-h6 text-bold">
                Panel Mototaxista
              </div>

              <div class="text-caption text-blue-1 ellipsis">
                {{ nombreMototaxista }}
              </div>
            </div>
          </div>
        </div>

        <div class="col-auto">
          <q-btn
            flat
            round
            icon="refresh"
            :loading="loading"
            @click="cargarTodo(true)"
          >
            <q-tooltip>Actualizar panel</q-tooltip>
          </q-btn>
        </div>
      </q-card-section>
    </q-card>

    <!-- ESTADO DEL CONDUCTOR -->
    <q-card class="border-radius-lg shadow-1 q-mb-md">
      <q-card-section>
        <div class="row items-center q-col-gutter-md">
          <div class="col-12 col-sm">
            <div class="row items-center no-wrap">
              <q-avatar
                :color="estadoConductorColor"
                text-color="white"
                :icon="estadoConductorIcono"
                size="48px"
                class="q-mr-md"
              />

              <div>
                <div class="text-subtitle1 text-weight-bold text-grey-9">
                  {{ estadoConductorTexto }}
                </div>

                <div class="text-caption text-grey-6">
                  {{ estadoConductorDescripcion }}
                </div>
              </div>
            </div>
          </div>

          <div class="col-12 col-sm-auto">
            <div class="row items-center justify-between no-wrap">
              <span
                class="text-body2 text-weight-medium q-mr-md"
                :class="disponible ? 'text-positive' : 'text-grey-7'"
              >
                {{ disponible ? 'En línea' : 'Fuera de línea' }}
              </span>

              <q-toggle
                :model-value="disponible"
                color="positive"
                checked-icon="check"
                unchecked-icon="power_settings_new"
                size="lg"
                :disable="tieneViajeActivo || cambiandoDisponibilidad"
                @update:model-value="cambiarDisponibilidad"
              />
            </div>
          </div>
        </div>

        <q-separator class="q-my-md" />

        <div class="row q-col-gutter-sm">
          <div class="col-12 col-sm-6">
            <div class="status-info-box">
              <q-icon name="gps_fixed" :color="gpsColor" size="22px" />

              <div>
                <div class="text-caption text-grey-6">Ubicación GPS</div>
                <div
                  class="text-body2 text-weight-medium"
                  :class="`text-${gpsColor}`"
                >
                  {{ gpsTexto }}
                </div>
              </div>
            </div>
          </div>

          <div class="col-12 col-sm-6">
            <div class="status-info-box">
              <q-icon name="schedule" color="primary" size="22px" />

              <div>
                <div class="text-caption text-grey-6">Última conexión</div>
                <div class="text-body2 text-weight-medium text-grey-9">
                  {{ formatearFechaHora(mototaxista?.ultima_conexion) }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- VIAJE ACTIVO -->
    <div v-if="tieneViajeActivo" class="q-mb-md">
      <div class="row items-center q-mb-sm">
        <q-icon
          name="navigation"
          color="primary"
          size="24px"
          class="q-mr-sm"
        />

        <div class="text-h6 text-weight-bold text-grey-9">
          Viaje actual
        </div>

        <q-space />

        <q-chip
          :color="getEstadoColor(viajeActivo.estado)"
          text-color="white"
          class="text-weight-bold text-uppercase"
        >
          {{ viajeActivo.estado }}
        </q-chip>
      </div>

      <q-card class="viaje-activo-card border-radius-lg shadow-3">
        <q-card-section class="bg-dark text-white">
          <div class="row items-center no-wrap">
            <q-avatar
              color="positive"
              text-color="white"
              size="46px"
              class="q-mr-md"
            >
              {{ getIniciales(getPasajeroNombre(viajeActivo)) }}
            </q-avatar>

            <div class="col min-width-zero">
              <div class="text-caption text-grey-4">Pasajero</div>
              <div class="text-h6 text-weight-bold ellipsis">
                {{ getPasajeroNombre(viajeActivo) }}
              </div>
              <div class="text-caption text-grey-4">
                Viaje #{{ viajeActivo.id }}
              </div>
            </div>

            <div class="text-right">
              <div class="text-caption text-grey-4">Tarifa</div>
              <div class="text-h5 text-weight-bold text-amber">
                {{ formatearMonto(viajeActivo.precio) }}
              </div>
            </div>
          </div>
        </q-card-section>

        <!-- CHAT CON EL PASAJERO -->
        <q-card-section class="q-py-sm bg-blue-1">
          <q-btn
            v-if="chatDisponible"
            color="primary"
            icon="chat"
            label="Chat con el pasajero"
            class="full-width text-weight-bold"
            unelevated
            @click="abrirChatViaje"
          >
            <q-badge
              v-if="chatNoLeidos > 0"
              color="negative"
              floating
              rounded
            >
              {{ chatNoLeidos > 99 ? '99+' : chatNoLeidos }}
            </q-badge>
          </q-btn>
        </q-card-section>

        <!-- SOS E INCIDENCIAS DEL VIAJE -->
        <q-card-section
          v-if="sosDisponible"
          class="q-py-sm bg-red-1"
        >
          <q-banner
            v-if="incidenciaActiva"
            rounded
            class="bg-white text-negative q-mb-sm"
          >
            <template #avatar>
              <q-icon name="warning" color="negative" />
            </template>

            <div class="text-weight-bold">
              Alerta {{ incidenciaActiva.codigo || '' }} en {{ incidenciaActiva.estado }}
            </div>
            <div class="text-caption text-grey-8">
              {{ incidenciaActiva.tipo }} · La central MOTRIX ya puede visualizarla.
            </div>
          </q-banner>

          <q-btn
            color="negative"
            icon="sos"
            :label="incidenciaActiva ? 'Reportar otra emergencia' : 'SOS / Reportar incidente'"
            class="full-width text-weight-bold"
            unelevated
            @click="abrirDialogoSos"
          />
        </q-card-section>

        <q-card-section class="q-gutter-y-md">
          <div class="route-row">
            <div class="route-point route-point-origin" />
            <div class="min-width-zero">
              <div class="text-caption text-grey-6">Punto de recogida</div>
              <div class="text-body1 text-weight-medium text-grey-9">
                {{ viajeActivo.origen || 'Origen no registrado' }}
              </div>
            </div>
          </div>

          <div class="route-connector" />

          <div class="route-row">
            <div class="route-point route-point-destination" />
            <div class="min-width-zero">
              <div class="text-caption text-grey-6">Destino</div>
              <div class="text-body1 text-weight-medium text-grey-9">
                {{ viajeActivo.destino || 'Destino no registrado' }}
              </div>
            </div>
          </div>

          <q-separator />

          <div class="row q-col-gutter-md">
            <div class="col-6 col-sm-4">
              <div class="detail-box">
                <q-icon name="route" color="primary" size="22px" />
                <div>
                  <div class="text-caption text-grey-6">Distancia</div>
                  <div class="text-body2 text-weight-bold">
                    {{ formatearDistancia(viajeActivo.distancia_km) }}
                  </div>
                </div>
              </div>
            </div>

            <div class="col-6 col-sm-4">
              <div class="detail-box">
                <q-icon
                  :name="getMetodoPagoIcono(viajeActivo.metodo_pago)"
                  color="positive"
                  size="22px"
                />
                <div>
                  <div class="text-caption text-grey-6">Pago</div>
                  <div class="text-body2 text-weight-bold">
                    {{ viajeActivo.metodo_pago || 'Por confirmar' }}
                  </div>
                </div>
              </div>
            </div>

            <div class="col-12 col-sm-4">
              <div class="detail-box">
                <q-icon name="event" color="indigo" size="22px" />
                <div>
                  <div class="text-caption text-grey-6">Fecha</div>
                  <div class="text-body2 text-weight-bold">
                    {{ formatearFecha(viajeActivo.fecha) }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </q-card-section>

        <!-- MAPA DE NAVEGACIÓN DEL CONDUCTOR -->
        <q-card-section class="q-pt-none">
          <q-card
            flat
            bordered
            class="navegacion-card"
          >
            <q-card-section class="row items-center q-col-gutter-sm q-pb-sm">
              <div class="col">
                <div class="row items-center no-wrap">
                  <q-avatar
                    :color="
                      viajeActivo.estado === 'En Curso'
                        ? 'indigo-1'
                        : viajeActivo.estado === 'Llegó'
                          ? 'green-1'
                          : 'blue-1'
                    "
                    :text-color="
                      viajeActivo.estado === 'En Curso'
                        ? 'indigo-9'
                        : viajeActivo.estado === 'Llegó'
                          ? 'positive'
                          : 'primary'
                    "
                    :icon="
                      viajeActivo.estado === 'Llegó'
                        ? 'person_pin_circle'
                        : 'map'
                    "
                    size="42px"
                    class="q-mr-sm"
                  />

                  <div class="min-width-zero">
                    <div class="text-subtitle1 text-weight-bold text-grey-9">
                      {{ tituloNavegacion }}
                    </div>

                    <div class="text-caption text-grey-6 ellipsis">
                      {{ descripcionNavegacion }}
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-auto">
                <q-btn
                  flat
                  round
                  color="primary"
                  icon="my_location"
                  @click="centrarMapaNavegacion"
                >
                  <q-tooltip>Centrar el mapa</q-tooltip>
                </q-btn>
              </div>
            </q-card-section>

            <div class="row q-col-gutter-sm q-px-md q-pb-md">
              <div class="col-6 col-sm-4">
                <div class="navigation-stat">
                  <q-icon name="route" color="primary" size="21px" />

                  <div>
                    <div class="text-caption text-grey-6">Distancia restante</div>
                    <div class="text-body2 text-weight-bold text-grey-9">
                      {{ distanciaNavegacionTexto }}
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-6 col-sm-4">
                <div class="navigation-stat">
                  <q-icon name="schedule" color="orange-8" size="21px" />

                  <div>
                    <div class="text-caption text-grey-6">Tiempo estimado</div>
                    <div class="text-body2 text-weight-bold text-grey-9">
                      {{ tiempoNavegacionTexto }}
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-12 col-sm-4">
                <div class="navigation-stat">
                  <q-icon
                    :name="
                      viajeActivo.estado === 'En Curso'
                        ? 'flag'
                        : viajeActivo.estado === 'Llegó'
                          ? 'done_all'
                          : 'person_pin_circle'
                    "
                    :color="
                      viajeActivo.estado === 'En Curso'
                        ? 'negative'
                        : 'positive'
                    "
                    size="21px"
                  />

                  <div class="min-width-zero">
                    <div class="text-caption text-grey-6">Objetivo actual</div>
                    <div class="text-body2 text-weight-bold text-grey-9 ellipsis">
                      {{ objetivoNavegacionTexto }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="mapa-navegacion-wrapper">
              <div
                id="mapa-navegacion-conductor"
                class="mapa-navegacion"
              />

              <q-inner-loading
                :showing="navegacionCargando"
                label="Calculando la mejor ruta..."
                label-class="text-primary text-weight-bold"
                color="primary"
              />

              <div
                v-if="!ubicacionConductorDisponible"
                class="mapa-aviso bg-white text-grey-8 shadow-2"
              >
                <q-icon name="gps_off" color="orange-8" size="22px" />
                <span>Esperando la ubicación GPS del conductor.</span>
              </div>
            </div>

            <q-card-section class="q-py-sm">
              <div class="row items-center justify-center q-gutter-md">
                <div class="row items-center no-wrap">
                  <span class="map-legend conductor" />
                  <span class="text-caption">Tu ubicación</span>
                </div>

                <div class="row items-center no-wrap">
                  <span class="map-legend origen" />
                  <span class="text-caption">Pasajero</span>
                </div>

                <div class="row items-center no-wrap">
                  <span class="map-legend destino" />
                  <span class="text-caption">Destino</span>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </q-card-section>

        <q-card-actions
          v-if="viajeActivo.estado === 'Aceptado'"
          class="q-pa-md bg-grey-1"
        >
          <div class="row full-width q-col-gutter-sm">
            <div class="col-12 col-sm-4">
              <q-btn
                outline
                color="negative"
                icon="close"
                label="Cancelar"
                class="full-width"
                :loading="accionando === `cancelar-${viajeActivo.id}`"
                @click="confirmarCancelacion(viajeActivo)"
              />
            </div>

            <div class="col-12 col-sm-4">
              <q-btn
                outline
                color="primary"
                icon="near_me"
                label="Ir al pasajero"
                class="full-width"
                @click="abrirNavegacion(viajeActivo, 'origen')"
              />
            </div>

            <div class="col-12 col-sm-4">
              <q-btn
                color="positive"
                icon="done_all"
                label="Llegué al punto"
                class="full-width text-weight-bold"
                :loading="accionando === `llegue-${viajeActivo.id}`"
                @click="marcarLlegada(viajeActivo)"
              />
            </div>
          </div>
        </q-card-actions>

        <q-card-actions
          v-if="viajeActivo.estado === 'Llegó'"
          class="q-pa-md bg-green-1"
        >
          <div class="full-width">
            <q-banner
              dense
              rounded
              class="bg-white text-positive q-mb-sm"
            >
              <template #avatar>
                <q-icon name="person_pin_circle" color="positive" />
              </template>

              Ya estás en el punto de recogida. El pasajero fue informado.
            </q-banner>

            <div class="row full-width q-col-gutter-sm">
              <div class="col-12 col-sm-6">
                <q-btn
                  outline
                  color="negative"
                  icon="close"
                  label="Cancelar"
                  class="full-width"
                  :loading="accionando === `cancelar-${viajeActivo.id}`"
                  @click="confirmarCancelacion(viajeActivo)"
                />
              </div>

              <div class="col-12 col-sm-6">
                <q-btn
                  color="primary"
                  icon="navigation"
                  label="Iniciar viaje"
                  class="full-width text-weight-bold"
                  :loading="accionando === `iniciar-${viajeActivo.id}`"
                  @click="iniciarViaje(viajeActivo)"
                />
              </div>
            </div>
          </div>
        </q-card-actions>

        <q-card-actions
          v-if="viajeActivo.estado === 'En Curso'"
          class="q-pa-md bg-grey-1"
        >
          <div class="row full-width q-col-gutter-sm">
            <div class="col-12 col-sm-6">
              <q-btn
                outline
                color="primary"
                icon="map"
                label="Navegar al destino"
                class="full-width"
                @click="abrirNavegacion(viajeActivo, 'destino')"
              />
            </div>

            <div class="col-12 col-sm-6">
              <q-btn
                color="dark"
                icon="payments"
                label="Finalizar y cobrar"
                class="full-width text-weight-bold"
                @click="abrirModalCobro(viajeActivo)"
              />
            </div>
          </div>
        </q-card-actions>
      </q-card>
    </div>

    <!-- SOLICITUDES DISPONIBLES -->
    <div v-else class="q-mb-lg">
      <div class="row items-center q-mb-sm">
        <q-icon
          name="notifications_active"
          color="positive"
          size="24px"
          class="q-mr-sm"
        />

        <div class="text-h6 text-weight-bold text-grey-9">
          Solicitudes disponibles
        </div>

        <q-space />

        <q-badge
          v-if="disponible"
          color="positive"
          rounded
          class="q-pa-sm text-weight-bold"
        >
          {{ solicitudesVisibles.length }}
        </q-badge>
      </div>

      <q-card
        v-if="!disponible"
        flat
        bordered
        class="border-radius-lg"
      >
        <q-card-section class="column items-center text-center q-pa-xl">
          <q-avatar
            color="grey-3"
            text-color="grey-7"
            icon="power_settings_new"
            size="72px"
            class="q-mb-md"
          />

          <div class="text-h6 text-weight-bold text-grey-8">
            Estás fuera de línea
          </div>

          <div class="text-body2 text-grey-6 q-mt-xs">
            Activa el interruptor para comenzar a recibir solicitudes.
          </div>

          <q-btn
            color="positive"
            icon="play_arrow"
            label="Conectarme"
            class="q-mt-lg q-px-xl"
            :loading="cambiandoDisponibilidad"
            @click="cambiarDisponibilidad(true)"
          />
        </q-card-section>
      </q-card>

      <q-card
        v-else-if="solicitudesVisibles.length === 0"
        flat
        bordered
        class="border-radius-lg"
      >
        <q-card-section class="column items-center text-center q-pa-xl">
          <q-spinner-radio
            color="positive"
            size="64px"
            class="q-mb-md"
          />

          <div class="text-h6 text-weight-bold text-grey-8">
            Esperando solicitudes
          </div>

          <div class="text-body2 text-grey-6 q-mt-xs">
            Estás disponible. Te mostraremos los nuevos viajes automáticamente.
          </div>
        </q-card-section>
      </q-card>

      <div v-else class="row q-col-gutter-md">
        <div
          v-for="viaje in solicitudesVisibles"
          :key="viaje.id"
          class="col-12 col-md-6"
        >
          <q-card class="solicitud-card border-radius-lg shadow-2 full-height">
            <q-card-section class="q-pb-sm">
              <div class="row items-start no-wrap">
                <q-avatar
                  color="positive"
                  text-color="white"
                  size="44px"
                  class="q-mr-sm"
                >
                  {{ getIniciales(getPasajeroNombre(viaje)) }}
                </q-avatar>

                <div class="col min-width-zero">
                  <div class="text-caption text-grey-6">
                    Nueva solicitud #{{ viaje.id }}
                  </div>
                  <div class="text-subtitle1 text-weight-bold text-grey-9 ellipsis">
                    {{ getPasajeroNombre(viaje) }}
                  </div>
                </div>

                <q-chip
                  color="orange-8"
                  text-color="white"
                  icon="timer"
                  dense
                  class="text-weight-bold"
                >
                  {{ tiempoRestante(viaje.expira_en) }}
                </q-chip>
              </div>
            </q-card-section>

            <q-separator />

            <q-card-section class="q-gutter-y-md">
              <div class="route-row">
                <div class="route-point route-point-origin" />
                <div class="min-width-zero">
                  <div class="text-caption text-grey-6">Recoger en</div>
                  <div class="text-body2 text-weight-medium text-grey-9">
                    {{ viaje.origen || 'Origen no registrado' }}
                  </div>
                </div>
              </div>

              <div class="route-connector small" />

              <div class="route-row">
                <div class="route-point route-point-destination" />
                <div class="min-width-zero">
                  <div class="text-caption text-grey-6">Llevar hasta</div>
                  <div class="text-body2 text-weight-medium text-grey-9">
                    {{ viaje.destino || 'Destino no registrado' }}
                  </div>
                </div>
              </div>

              <div class="row q-col-gutter-sm">
                <div class="col-6">
                  <div class="request-detail">
                    <q-icon name="near_me" color="primary" size="20px" />
                    <div>
                      <div class="text-caption text-grey-6">Hasta pasajero</div>
                      <div class="text-weight-bold">
                        {{ formatearDistancia(viaje.distancia_recogida_km) }}
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-6">
                  <div class="request-detail">
                    <q-icon name="route" color="indigo" size="20px" />
                    <div>
                      <div class="text-caption text-grey-6">Recorrido</div>
                      <div class="text-weight-bold">
                        {{ formatearDistancia(viaje.distancia_km) }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <q-separator />

              <div class="row items-end justify-between">
                <div>
                  <div class="text-caption text-grey-6">Método de pago</div>
                  <div class="row items-center text-weight-medium">
                    <q-icon
                      :name="getMetodoPagoIcono(viaje.metodo_pago)"
                      color="positive"
                      size="20px"
                      class="q-mr-xs"
                    />
                    {{ viaje.metodo_pago || 'Por confirmar' }}
                  </div>
                </div>

                <div class="text-right">
                  <div class="text-caption text-grey-6">Tarifa fija</div>
                  <div class="text-h5 text-weight-bold text-positive">
                    {{ formatearMonto(viaje.precio) }}
                  </div>
                </div>
              </div>
            </q-card-section>

            <q-card-actions class="q-pa-md bg-grey-1">
              <div class="row full-width q-col-gutter-sm">
                <div class="col-5">
                  <q-btn
                    outline
                    color="grey-7"
                    icon="close"
                    label="Rechazar"
                    class="full-width"
                    :loading="accionando === `rechazar-${viaje.id}`"
                    @click="rechazarViaje(viaje)"
                  />
                </div>

                <div class="col-7">
                  <q-btn
                    color="positive"
                    icon="check"
                    label="Aceptar viaje"
                    class="full-width text-weight-bold"
                    :loading="accionando === `aceptar-${viaje.id}`"
                    @click="aceptarViaje(viaje)"
                  />
                </div>
              </div>
            </q-card-actions>
          </q-card>
        </div>
      </div>
    </div>

    <!-- GANANCIAS Y REPUTACIÓN -->
    <div class="row q-col-gutter-md q-mb-md">
      <div class="col-12 col-md-7">
        <q-card class="bg-dark text-white border-radius-lg shadow-2 full-height">
          <q-card-section class="row items-center justify-between">
            <div>
              <div class="text-caption text-grey-4">
                Total recaudado
              </div>

              <div class="text-h4 text-bold text-amber">
                {{ formatearMonto(ganancias.total_recaudado) }}
              </div>

              <div class="text-caption text-grey-4">
                {{ ganancias.viajes_totales }}
                viajes completados
              </div>
            </div>

            <q-avatar
              color="amber"
              text-color="dark"
              icon="account_balance_wallet"
              size="58px"
            />
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-5">
        <q-card class="reputacion-card border-radius-lg shadow-2 full-height">
          <q-card-section class="row items-center no-wrap">
            <q-avatar
              color="amber-1"
              text-color="amber-9"
              icon="workspace_premium"
              size="62px"
              class="q-mr-md"
            />

            <div class="col min-width-zero">
              <div class="text-caption text-grey-6 text-uppercase text-weight-bold">
                Mi reputación
              </div>

              <div class="row items-center no-wrap q-mt-xs">
                <q-rating
                  :model-value="promedioEstrellas"
                  :max="5"
                  size="25px"
                  color="amber"
                  icon="star_border"
                  icon-selected="star"
                  readonly
                />

                <span class="text-h6 text-weight-bold text-amber-9 q-ml-sm">
                  {{ ganancias.promedio_calificacion.toFixed(2) }}/5
                </span>
              </div>

              <div class="text-caption text-grey-7 q-mt-xs">
                {{ textoTotalCalificaciones }}
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-6">
        <q-card class="bg-green-1 border-radius-lg shadow-1 full-height">
          <q-card-section class="text-center">
            <q-icon
              name="payments"
              color="positive"
              size="28px"
            />

            <div class="text-caption text-weight-medium q-mt-xs">
              En efectivo
            </div>

            <div class="text-h6 text-bold text-positive">
              {{ formatearMonto(ganancias.ganancia_efectivo) }}
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-6">
        <q-card class="bg-indigo-1 border-radius-lg shadow-1 full-height">
          <q-card-section class="text-center">
            <q-icon
              name="qr_code_2"
              color="indigo-7"
              size="28px"
            />

            <div class="text-caption text-weight-medium q-mt-xs">
              Digital / QR
            </div>

            <div class="text-h6 text-bold text-indigo-7">
              {{ formatearMonto(ganancias.ganancia_qr) }}
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- HISTORIAL Y CALIFICACIONES RECIBIDAS -->
    <q-expansion-item
      icon="history"
      label="Historial de viajes completados"
      header-class="bg-white text-grey-9 text-bold shadow-1 border-radius-lg"
      class="overflow-hidden q-mb-md"
    >
      <q-card class="bg-grey-2 q-pa-sm">
        <div
          v-if="historial.length === 0"
          class="column items-center q-pa-lg text-grey-6"
        >
          <q-icon
            name="history_toggle_off"
            size="48px"
            class="q-mb-sm"
          />

          <div class="text-body2">
            Todavía no existen viajes completados.
          </div>
        </div>

        <div
          v-else
          class="column q-gutter-sm"
        >
          <q-card
            v-for="item in historial"
            :key="item.id"
            flat
            bordered
            class="historial-viaje-card border-radius-md shadow-1"
          >
            <q-card-section class="q-pa-md">
              <div class="row items-start no-wrap">
                <q-avatar
                  :color="
                    item.metodo === 'Efectivo'
                      ? 'green-1'
                      : 'indigo-1'
                  "
                  :text-color="
                    item.metodo === 'Efectivo'
                      ? 'positive'
                      : 'indigo-7'
                  "
                  :icon="getMetodoPagoIcono(item.metodo)"
                  class="q-mr-md"
                />

                <div class="col min-width-zero">
                  <div class="row items-start justify-between no-wrap">
                    <div class="col min-width-zero">
                      <div class="text-subtitle1 text-bold text-grey-9 ellipsis">
                        {{ item.destino || 'Destino no registrado' }}
                      </div>

                      <div class="text-caption text-grey-6">
                        Viaje #{{ item.solicitud_id || item.id }}
                        · {{ formatearFecha(item.fecha) }}
                      </div>
                    </div>

                    <q-badge
                      color="positive"
                      class="text-bold text-subtitle2 q-pa-sm q-ml-sm"
                    >
                      {{ formatearMonto(item.monto) }}
                    </q-badge>
                  </div>

                  <div class="text-caption text-grey-7 q-mt-xs">
                    Desde:
                    <strong>
                      {{ item.origen || 'Origen no registrado' }}
                    </strong>
                  </div>

                  <div class="text-caption text-grey-7">
                    Pago mediante
                    <strong>
                      {{ item.metodo || 'No especificado' }}
                    </strong>
                  </div>
                </div>
              </div>

              <q-separator class="q-my-sm" />

              <div
                v-if="tieneCalificacion(item)"
                class="calificacion-recibida"
              >
                <div class="row items-center no-wrap">
                  <q-avatar
                    color="amber-1"
                    text-color="amber-9"
                    icon="star"
                    size="38px"
                    class="q-mr-sm"
                  />

                  <div class="col">
                    <div class="text-caption text-grey-6">
                      Calificación recibida
                    </div>

                    <div class="row items-center no-wrap">
                      <q-rating
                        :model-value="obtenerCalificacion(item)"
                        :max="5"
                        size="21px"
                        color="amber"
                        icon="star_border"
                        icon-selected="star"
                        readonly
                      />

                      <span class="text-weight-bold text-amber-9 q-ml-sm">
                        {{ obtenerCalificacion(item).toFixed(1) }}/5
                      </span>
                    </div>
                  </div>
                </div>

                <div
                  v-if="item.comentario_calificacion"
                  class="comentario-recibido q-mt-sm"
                >
                  “{{ item.comentario_calificacion }}”
                </div>

                <div
                  v-if="item.calificado_en"
                  class="text-caption text-grey-6 q-mt-xs"
                >
                  Recibida el
                  {{ formatearFechaHora(item.calificado_en) }}
                </div>
              </div>

              <q-banner
                v-else
                dense
                rounded
                class="bg-grey-2 text-grey-7"
              >
                <template #avatar>
                  <q-icon
                    name="star_outline"
                    color="grey-6"
                  />
                </template>

                Este viaje todavía no recibió una calificación.
              </q-banner>
            </q-card-section>
          </q-card>
        </div>
      </q-card>
    </q-expansion-item>

    <!-- ALERTA DESTACADA DE NUEVA SOLICITUD -->
    <q-dialog
      v-model="dialogNuevaSolicitud"
      persistent
      transition-show="jump-down"
      transition-hide="jump-up"
    >
      <q-card
        v-if="solicitudDestacada"
        class="nueva-solicitud-dialog border-radius-lg shadow-10"
      >
        <q-card-section class="bg-positive text-white">
          <div class="row items-center no-wrap">
            <q-avatar
              color="white"
              text-color="positive"
              icon="notifications_active"
              size="54px"
              class="q-mr-md solicitud-alerta-avatar"
            />

            <div class="col min-width-zero">
              <div class="text-h6 text-weight-bold">
                Nueva solicitud de viaje
              </div>

              <div class="text-caption text-green-1 ellipsis">
                Solicitud #{{ solicitudDestacada.id }} · responde antes de que expire
              </div>
            </div>

            <q-chip
              color="orange-9"
              text-color="white"
              icon="timer"
              class="text-weight-bold"
            >
              {{ tiempoRestante(solicitudDestacada.expira_en) }}
            </q-chip>
          </div>
        </q-card-section>

        <q-card-section>
          <div class="row items-center no-wrap q-mb-md">
            <q-avatar
              color="green-1"
              text-color="positive"
              size="48px"
              class="q-mr-md"
            >
              {{ getIniciales(getPasajeroNombre(solicitudDestacada)) }}
            </q-avatar>

            <div class="col min-width-zero">
              <div class="text-caption text-grey-6">Pasajero</div>
              <div class="text-subtitle1 text-weight-bold text-grey-9 ellipsis">
                {{ getPasajeroNombre(solicitudDestacada) }}
              </div>
            </div>

            <div class="text-right q-ml-md">
              <div class="text-caption text-grey-6">Tarifa</div>
              <div class="text-h5 text-weight-bold text-positive">
                {{ formatearMonto(solicitudDestacada.precio) }}
              </div>
            </div>
          </div>

          <div class="route-row">
            <div class="route-point route-point-origin" />
            <div class="min-width-zero">
              <div class="text-caption text-grey-6">Recoger en</div>
              <div class="text-body1 text-weight-medium text-grey-9">
                {{ solicitudDestacada.origen || 'Origen no registrado' }}
              </div>
            </div>
          </div>

          <div class="route-connector small" />

          <div class="route-row">
            <div class="route-point route-point-destination" />
            <div class="min-width-zero">
              <div class="text-caption text-grey-6">Destino</div>
              <div class="text-body1 text-weight-medium text-grey-9">
                {{ solicitudDestacada.destino || 'Destino no registrado' }}
              </div>
            </div>
          </div>

          <div class="row q-col-gutter-sm q-mt-md">
            <div class="col-6">
              <div class="request-detail">
                <q-icon name="near_me" color="primary" size="22px" />
                <div>
                  <div class="text-caption text-grey-6">Hasta el pasajero</div>
                  <div class="text-weight-bold">
                    {{ formatearDistancia(solicitudDestacada.distancia_recogida_km) }}
                  </div>
                </div>
              </div>
            </div>

            <div class="col-6">
              <div class="request-detail">
                <q-icon name="route" color="indigo" size="22px" />
                <div>
                  <div class="text-caption text-grey-6">Recorrido</div>
                  <div class="text-weight-bold">
                    {{ formatearDistancia(solicitudDestacada.distancia_km) }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </q-card-section>

        <q-card-actions class="q-pa-md bg-grey-1">
          <div class="row full-width q-col-gutter-sm">
            <div class="col-5">
              <q-btn
                outline
                color="negative"
                icon="close"
                label="Rechazar"
                class="full-width"
                :loading="accionando === `rechazar-${solicitudDestacada.id}`"
                :disable="Boolean(accionando)"
                @click="rechazarViaje(solicitudDestacada)"
              />
            </div>

            <div class="col-7">
              <q-btn
                color="positive"
                icon="check"
                label="Aceptar viaje"
                class="full-width text-weight-bold"
                :loading="accionando === `aceptar-${solicitudDestacada.id}`"
                :disable="Boolean(accionando)"
                @click="aceptarViaje(solicitudDestacada)"
              />
            </div>
          </div>
        </q-card-actions>
      </q-card>
    </q-dialog>


    <!-- MODAL SOS DEL CONDUCTOR -->
    <q-dialog
      v-model="dialogSos"
      persistent
      :maximized="$q.screen.lt.sm"
    >
      <q-card class="sos-dialog-card">
        <q-card-section class="bg-negative text-white row items-center no-wrap">
          <q-avatar
            color="white"
            text-color="negative"
            icon="sos"
            size="50px"
            class="q-mr-md"
          />

          <div class="col min-width-zero">
            <div class="text-h6 text-weight-bold">
              Reportar una incidencia
            </div>
            <div class="text-caption">
              Viaje #{{ viajeActivo?.id || '—' }} · Alerta interna MOTRIX
            </div>
          </div>

          <q-btn
            flat
            round
            dense
            icon="close"
            :disable="sosEnviando"
            @click="dialogSos = false"
          />
        </q-card-section>

        <q-card-section>
          <q-banner rounded class="bg-red-1 text-red-10 q-mb-md">
            <template #avatar>
              <q-icon name="emergency" color="negative" />
            </template>
            Usa este botón cuando necesites ayuda de la central MOTRIX. Esta alerta no reemplaza una llamada directa a la Policía, ambulancia o bomberos.
          </q-banner>

          <q-select
            v-model="sosTipo"
            :options="tiposIncidenciaConductor"
            outlined
            label="Tipo de incidente *"
            class="q-mb-md"
          >
            <template #prepend>
              <q-icon name="report_problem" color="negative" />
            </template>
          </q-select>

          <q-input
            v-model="sosDescripcion"
            outlined
            type="textarea"
            autogrow
            maxlength="500"
            counter
            label="Describe brevemente lo ocurrido"
            placeholder="Ejemplo: tuve una falla en la motocicleta y estoy detenido..."
            class="q-mb-md"
          />

          <q-card flat bordered class="sos-location-card">
            <q-card-section class="row items-center no-wrap">
              <q-avatar
                :color="sosUbicacion.latitud !== null ? 'green-1' : 'orange-1'"
                :text-color="sosUbicacion.latitud !== null ? 'positive' : 'orange-9'"
                :icon="sosUbicacion.latitud !== null ? 'my_location' : 'location_off'"
                class="q-mr-md"
              />

              <div class="col min-width-zero">
                <div class="text-weight-bold text-grey-9">
                  Ubicación de la alerta
                </div>
                <div class="text-caption text-grey-7">
                  {{ sosEstadoGps }}
                </div>
                <div
                  v-if="sosUbicacion.latitud !== null"
                  class="text-caption text-grey-6 ellipsis"
                >
                  {{ Number(sosUbicacion.latitud).toFixed(6) }},
                  {{ Number(sosUbicacion.longitud).toFixed(6) }}
                  <span v-if="sosUbicacion.precision_metros !== null">
                    · precisión {{ Math.round(sosUbicacion.precision_metros) }} m
                  </span>
                </div>
              </div>

              <q-btn
                flat
                round
                color="primary"
                icon="gps_fixed"
                :loading="sosObteniendoUbicacion"
                @click="obtenerUbicacionSos"
              >
                <q-tooltip>Actualizar ubicación</q-tooltip>
              </q-btn>
            </q-card-section>
          </q-card>
        </q-card-section>

        <q-separator />

        <q-card-actions align="right" class="q-pa-md bg-grey-1">
          <q-btn
            flat
            label="Cancelar"
            color="grey-7"
            :disable="sosEnviando"
            @click="dialogSos = false"
          />
          <q-btn
            color="negative"
            icon="send"
            label="Enviar alerta SOS"
            unelevated
            class="text-weight-bold"
            :loading="sosEnviando"
            :disable="!sosTipo"
            @click="enviarAlertaSos"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>


    <!-- CHAT EN TIEMPO REAL CON EL PASAJERO -->
    <q-dialog
      v-model="dialogChat"
      :maximized="$q.screen.lt.sm"
      transition-show="slide-up"
      transition-hide="slide-down"
      @hide="alCerrarChat"
    >
      <q-card class="chat-dialog-card column no-wrap">
        <q-card-section class="bg-primary text-white row items-center no-wrap">
          <q-avatar
            color="white"
            text-color="primary"
            icon="chat"
            size="46px"
            class="q-mr-md"
          />

          <div class="col min-width-zero">
            <div class="text-h6 text-weight-bold ellipsis">
              Chat con {{ getPasajeroNombre(viajeActivo || {}) }}
            </div>
            <div class="text-caption text-blue-1">
              Viaje #{{ viajeActivo?.id || '—' }} · {{ viajeActivo?.estado || 'Sin estado' }}
            </div>
          </div>

          <q-chip
            :color="websocketConectado ? 'positive' : 'orange-8'"
            text-color="white"
            :icon="websocketConectado ? 'sensors' : 'sync_problem'"
            dense
            class="q-mr-sm"
          >
            {{ websocketConectado ? 'EN VIVO' : 'RESPALDO' }}
          </q-chip>

          <q-btn
            flat
            round
            dense
            icon="close"
            aria-label="Cerrar chat"
            @click="dialogChat = false"
          />
        </q-card-section>

        <q-linear-progress
          v-if="chatCargando"
          indeterminate
          color="primary"
        />

        <div
          ref="chatContenedor"
          class="chat-messages col"
        >
          <div
            v-if="!chatCargando && chatMensajes.length === 0"
            class="column items-center justify-center text-center text-grey-6 chat-empty"
          >
            <q-icon name="forum" size="54px" color="grey-5" />
            <div class="text-subtitle1 text-weight-bold q-mt-sm">
              Todavía no hay mensajes
            </div>
            <div class="text-caption">
              Escribe al pasajero para coordinar el punto de recogida.
            </div>
          </div>

          <div
            v-for="mensaje in chatMensajes"
            :key="mensaje.id"
            class="chat-row"
            :class="esMensajeChatPropio(mensaje) ? 'chat-row-own' : 'chat-row-other'"
          >
            <div
              class="chat-bubble"
              :class="esMensajeChatPropio(mensaje) ? 'chat-bubble-own' : 'chat-bubble-other'"
            >
              <div
                v-if="!esMensajeChatPropio(mensaje)"
                class="text-caption text-weight-bold text-primary q-mb-xs"
              >
                {{ mensaje.remitente_nombre || 'Pasajero' }}
              </div>

              <div class="text-body2 chat-message-text">
                {{ mensaje.mensaje }}
              </div>

              <div class="chat-message-time">
                {{ formatearHoraChat(mensaje.creado_en) }}
                <q-icon
                  v-if="esMensajeChatPropio(mensaje)"
                  :name="mensaje.leido_pasajero_en ? 'done_all' : 'done'"
                  :color="mensaje.leido_pasajero_en ? 'light-blue-7' : 'grey-6'"
                  size="16px"
                  class="q-ml-xs"
                />
              </div>
            </div>
          </div>
        </div>

        <q-separator />

        <q-card-section class="q-py-sm bg-grey-1">
          <div class="text-caption text-grey-7 q-mb-xs">
            Mensajes rápidos
          </div>

          <div class="row q-gutter-xs no-wrap chat-quick-scroll">
            <q-btn
              v-for="texto in mensajesRapidosConductor"
              :key="texto"
              outline
              rounded
              no-caps
              dense
              color="primary"
              :label="texto"
              :disable="chatEnviando || !chatHabilitado"
              @click="enviarMensajeChat(texto)"
            />
          </div>
        </q-card-section>

        <q-card-section class="q-pt-sm">
          <q-banner
            v-if="!chatHabilitado"
            rounded
            class="bg-orange-1 text-orange-10 q-mb-sm"
          >
            El chat está cerrado porque el viaje finalizó o fue cancelado.
          </q-banner>

          <div class="row items-end q-col-gutter-sm">
            <div class="col">
              <q-input
                v-model="chatTexto"
                outlined
                autogrow
                type="textarea"
                maxlength="1000"
                counter
                label="Escribe un mensaje"
                :disable="!chatHabilitado || chatEnviando"
                @keydown.enter.exact.prevent="enviarMensajeChat()"
              >
                <template #prepend>
                  <q-icon name="message" color="primary" />
                </template>
              </q-input>
            </div>

            <div class="col-auto">
              <q-btn
                round
                color="primary"
                icon="send"
                size="lg"
                :loading="chatEnviando"
                :disable="!chatHabilitado || !chatTexto.trim()"
                @click="enviarMensajeChat()"
              >
                <q-tooltip>Enviar mensaje</q-tooltip>
              </q-btn>
            </div>
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- MODAL DE COBRO -->
    <q-dialog v-model="dialogCobro" persistent position="bottom">
      <q-card class="q-pa-sm border-radius-lg cobro-dialog">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6 text-bold text-grey-9">Confirmar finalización</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section
          v-if="viajeSeleccionado"
          class="q-pt-sm text-center"
        >
          <div class="text-subtitle1 text-grey-7">Monto total a cobrar</div>
          <div class="text-h3 text-bold text-positive q-my-sm">
            {{ formatearMonto(viajeSeleccionado.precio) }}
          </div>
          <div class="text-caption text-grey-6">
            Selecciona cómo pagó el pasajero
          </div>
        </q-card-section>

        <q-card-section class="q-py-md">
          <div class="column q-gutter-sm">
            <q-btn
              color="green-7"
              icon="payments"
              label="Efectivo"
              class="text-bold q-py-md text-subtitle1"
              :loading="procesandoCobro"
              @click="procesarFinalizacionConPago('Efectivo')"
            />

            <q-btn
              color="indigo-7"
              icon="qr_code_2"
              label="Transferencia / QR"
              class="text-bold q-py-md text-subtitle1"
              :loading="procesandoCobro"
              @click="procesarFinalizacionConPago('Transferencia / QR')"
            />
          </div>
        </q-card-section>

        <q-card-actions align="center" class="q-pb-md">
          <q-btn
            flat
            label="Volver al viaje"
            color="grey-7"
            class="text-bold"
            v-close-popup
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import {
  computed,
  nextTick,
  onBeforeUnmount,
  onMounted,
  ref,
  watch
} from 'vue'

import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import { api } from '../../boot/axios.js'
import { BROADCAST_AUTH_URL, echoOptions } from '../../config/runtime.js'

window.Pusher = Pusher

const $q = useQuasar()
const router = useRouter()

const usuarioAutenticado = (() => {
  try {
    return JSON.parse(localStorage.getItem('motrix_user') || 'null')
  } catch {
    return null
  }
})()

const MOTOTAXISTA_ID = Number(
  usuarioAutenticado?.mototaxista_id
  || localStorage.getItem('mototaxista_id')
  || 0
)

const validarSesionConductor = () => {
  if (
    usuarioAutenticado?.role !== 'conductor'
    || !MOTOTAXISTA_ID
  ) {
    $q.notify({
      type: 'negative',
      message: 'Debes iniciar sesión con una cuenta de conductor.'
    })

    router.replace('/login')
    return false
  }

  return true
}

const mototaxista = ref(null)
const disponible = ref(false)
const solicitudes = ref([])
const viajeActivo = ref(null)
const loading = ref(false)
const cambiandoDisponibilidad = ref(false)
const accionando = ref(null)
const historial = ref([])
const dialogNuevaSolicitud = ref(false)
const solicitudDestacada = ref(null)
const websocketConectado = ref(false)

const dialogChat = ref(false)
const chatMensajes = ref([])
const chatTexto = ref('')
const chatCargando = ref(false)
const chatEnviando = ref(false)
const chatHabilitado = ref(false)
const chatNoLeidos = ref(0)
const chatContenedor = ref(null)

const dialogSos = ref(false)
const sosTipo = ref('')
const sosDescripcion = ref('')
const sosEnviando = ref(false)
const sosObteniendoUbicacion = ref(false)
const sosEstadoGps = ref('La ubicación se capturará al abrir esta ventana.')
const sosUbicacion = ref({
  latitud: null,
  longitud: null,
  precision_metros: null
})
const incidenciasViaje = ref([])
const incidenciaActiva = ref(null)

const tiposIncidenciaConductor = [
  'Accidente',
  'Emergencia médica',
  'Situación de inseguridad',
  'Falla de la motocicleta',
  'Pasajero no localizado',
  'Otro'
]

const mensajesRapidosConductor = [
  'Ya estoy llegando',
  'Estoy en el punto de recogida',
  'No encuentro la ubicación',
  'Espérame un momento'
]

const ganancias = ref({
  viajes_totales: 0,
  ganancia_efectivo: 0,
  ganancia_qr: 0,
  total_recaudado: 0,
  promedio_calificacion: 0,
  total_calificaciones: 0
})

const dialogCobro = ref(false)
const viajeSeleccionado = ref(null)
const procesandoCobro = ref(false)
const gpsEstado = ref('inactivo')
const gpsMensaje = ref('GPS desactivado')
const ultimaUbicacion = ref(null)
const ahora = ref(Date.now())

const navegacionCargando = ref(false)
const navegacionDistanciaKm = ref(0)
const navegacionDuracionMin = ref(0)
const navegacionError = ref(null)

let gpsWatchId = null
let intervaloActualizacion = null
let intervaloReloj = null
let intervaloChat = null
let canalChatSolicitudId = null
let canalIncidenciasSolicitudId = null
let ultimaUbicacionEnviada = 0
let echoInstance = null
let audioContexto = null
let solicitudesInicializadas = false
const solicitudesNotificadas = new Set()

let mapaNavegacion = null
let marcadorConductor = null
let marcadorOrigenViaje = null
let marcadorDestinoViaje = null
let lineaRutaNavegacion = null
let ultimaRutaCalculadaEn = 0
let ultimoObjetivoRuta = ''

const tieneViajeActivo = computed(() => {
  return Boolean(viajeActivo.value && viajeActivo.value.id)
})

const nombreMototaxista = computed(() => {
  return mototaxista.value?.persona?.nombre || `Mototaxista #${MOTOTAXISTA_ID}`
})

/*
 * El chat se conserva programado, pero permanece oculto
 * temporalmente por decisión de alcance del proyecto.
 */
const chatDisponible = computed(() => false)

const sosDisponible = computed(() => {
  const estado = String(viajeActivo.value?.estado || '')

  return Boolean(
    viajeActivo.value?.id
    && ['Aceptado', 'Llegó', 'En Curso'].includes(estado)
  )
})

const solicitudesVisibles = computed(() => solicitudes.value)

const promedioEstrellas = computed(() => {
  const promedio = Number(
    ganancias.value.promedio_calificacion
  )

  return Number.isFinite(promedio)
    ? Math.round(
      Math.min(5, Math.max(0, promedio))
    )
    : 0
})

const textoTotalCalificaciones = computed(() => {
  const total = Number(
    ganancias.value.total_calificaciones
  ) || 0

  if (total === 0) {
    return 'Todavía no recibiste calificaciones'
  }

  if (total === 1) {
    return '1 calificación recibida'
  }

  return `${total} calificaciones recibidas`
})

const ubicacionConductorDisponible = computed(() => {
  return Boolean(
    Number.isFinite(Number(ultimaUbicacion.value?.latitud))
    && Number.isFinite(Number(ultimaUbicacion.value?.longitud))
  )
})

const tituloNavegacion = computed(() => {
  if (viajeActivo.value?.estado === 'En Curso') {
    return 'Ruta hacia el destino'
  }

  if (viajeActivo.value?.estado === 'Llegó') {
    return 'Llegaste al punto de recogida'
  }

  return 'Ruta para recoger al pasajero'
})

const descripcionNavegacion = computed(() => {
  if (viajeActivo.value?.estado === 'En Curso') {
    return 'Sigue la línea azul hasta el destino solicitado.'
  }

  if (viajeActivo.value?.estado === 'Llegó') {
    return 'Espera al pasajero y comienza el viaje cuando suba.'
  }

  return 'Sigue la línea azul hasta el punto donde espera el pasajero.'
})

const objetivoNavegacionTexto = computed(() => {
  if (!viajeActivo.value) return 'Sin viaje activo'

  if (viajeActivo.value.estado === 'En Curso') {
    return viajeActivo.value.destino || 'Destino del viaje'
  }

  if (viajeActivo.value.estado === 'Llegó') {
    return 'Pasajero notificado'
  }

  return viajeActivo.value.origen || 'Punto de recogida'
})

const distanciaNavegacionTexto = computed(() => {
  return navegacionDistanciaKm.value > 0
    ? `${navegacionDistanciaKm.value.toFixed(2)} km`
    : 'Calculando...'
})

const tiempoNavegacionTexto = computed(() => {
  if (navegacionDuracionMin.value <= 0) {
    return 'Calculando...'
  }

  const minutos = Math.max(1, Math.round(navegacionDuracionMin.value))
  return `${minutos} min aprox.`
})

const estadoConductorTexto = computed(() => {
  if (tieneViajeActivo.value) return 'Conductor ocupado'
  return disponible.value
    ? 'Disponible para recibir viajes'
    : 'Conductor desconectado'
})

const estadoConductorDescripcion = computed(() => {
  if (tieneViajeActivo.value) {
    return `Viaje #${viajeActivo.value.id} en proceso`
  }

  if (disponible.value) {
    return 'MOTRIX está buscando solicitudes cercanas.'
  }

  return 'No recibirás solicitudes mientras estés fuera de línea.'
})

const estadoConductorColor = computed(() => {
  if (tieneViajeActivo.value) return 'primary'
  return disponible.value ? 'positive' : 'grey-6'
})

const estadoConductorIcono = computed(() => {
  if (tieneViajeActivo.value) return 'navigation'
  return disponible.value ? 'wifi' : 'wifi_off'
})

const gpsColor = computed(() => {
  if (gpsEstado.value === 'activo') return 'positive'
  if (gpsEstado.value === 'error') return 'negative'
  if (gpsEstado.value === 'buscando') return 'orange-8'
  return 'grey-6'
})

const gpsTexto = computed(() => gpsMensaje.value)

const obtenerMensajeError = (error, mensajeDefecto) => {
  return (
    error?.response?.data?.mensaje
    || error?.response?.data?.message
    || mensajeDefecto
  )
}

const convertirFechaJavaScript = (fecha) => {
  if (!fecha) return null

  if (fecha instanceof Date) {
    return Number.isNaN(fecha.getTime()) ? null : fecha
  }

  const textoFecha = String(fecha).trim().replace(' ', 'T')
  const objetoFecha = new Date(textoFecha)
  return Number.isNaN(objetoFecha.getTime()) ? null : objetoFecha
}

const normalizarViajeActivo = (respuesta) => {
  let datos = respuesta

  if (
    datos === null
    || datos === undefined
    || datos === ''
    || datos === 'null'
  ) {
    return null
  }

  if (Array.isArray(datos)) {
    datos = datos.length > 0 ? datos[0] : null
  }

  if (
    datos
    && typeof datos === 'object'
    && !Array.isArray(datos)
    && !datos.id
  ) {
    if (Object.prototype.hasOwnProperty.call(datos, 'viaje')) {
      datos = datos.viaje
    } else if (Object.prototype.hasOwnProperty.call(datos, 'solicitud')) {
      datos = datos.solicitud
    } else if (Object.prototype.hasOwnProperty.call(datos, 'data')) {
      datos = datos.data
    }
  }

  if (Array.isArray(datos)) {
    datos = datos.length > 0 ? datos[0] : null
  }

  if (!datos || typeof datos !== 'object' || !datos.id) {
    return null
  }

  return datos
}

const getPasajeroNombre = (viaje) => {
  return (
    viaje?.pasajero?.persona?.nombre
    || viaje?.pasajero?.persona?.nombre_completo
    || viaje?.pasajero?.nombre
    || 'Pasajero no identificado'
  )
}

const getIniciales = (nombre = '') => {
  const partes = String(nombre)
    .trim()
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)

  return partes
    .map((parte) => parte.charAt(0).toUpperCase())
    .join('') || 'P'
}

const formatearMonto = (monto) => {
  const numero = Number.parseFloat(monto)
  return `Bs. ${Number.isFinite(numero) ? numero.toFixed(2) : '0.00'}`
}

const formatearDistancia = (distancia) => {
  const numero = Number.parseFloat(distancia)

  if (!Number.isFinite(numero)) {
    return 'Por calcular'
  }

  return `${numero.toFixed(2)} km`
}

/*
 * CORRECCIÓN: las fechas tipo YYYY-MM-DD se muestran directamente,
 * sin convertirlas a UTC ni restar un día en Bolivia.
 */
const formatearFecha = (fecha) => {
  if (!fecha) {
    return 'Sin fecha'
  }

  const fechaLimpia = String(fecha).slice(0, 10)
  const partes = fechaLimpia.split('-')

  if (partes.length !== 3) {
    return String(fecha)
  }

  const [anio, mes, dia] = partes
  const numeroMes = Number(mes)

  const meses = [
    'ene',
    'feb',
    'mar',
    'abr',
    'may',
    'jun',
    'jul',
    'ago',
    'sep',
    'oct',
    'nov',
    'dic'
  ]

  if (
    !/^\d{4}$/.test(anio)
    || !/^\d{2}$/.test(mes)
    || !/^\d{2}$/.test(dia)
    || numeroMes < 1
    || numeroMes > 12
  ) {
    return String(fecha)
  }

  return `${dia} ${meses[numeroMes - 1]} ${anio}`
}

const formatearFechaHora = (fecha) => {
  if (!fecha) return 'Sin registro'

  const objetoFecha = convertirFechaJavaScript(fecha)
  if (!objetoFecha) return String(fecha)

  return new Intl.DateTimeFormat('es-BO', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  }).format(objetoFecha)
}

const getMetodoPagoIcono = (metodo) => {
  if (!metodo) return 'account_balance_wallet'
  return metodo === 'Efectivo' ? 'payments' : 'qr_code_2'
}

const getEstadoColor = (estado) => {
  const valor = String(estado || '').trim().toLowerCase()

  if (valor === 'pendiente' || valor === 'buscando conductor') {
    return 'orange-8'
  }

  if (valor === 'aceptado') return 'primary'
  if (valor === 'llegó' || valor === 'llego') return 'positive'
  if (valor === 'en curso') return 'indigo-9'
  if (valor === 'finalizado') return 'positive'
  if (valor === 'cancelado') return 'negative'
  return 'grey-7'
}

const tiempoRestante = (fechaExpiracion) => {
  ahora.value

  if (!fechaExpiracion) return 'Disponible'

  const vencimiento = convertirFechaJavaScript(fechaExpiracion)
  if (!vencimiento) return 'Disponible'

  const diferencia = vencimiento.getTime() - ahora.value
  if (diferencia <= 0) return 'Expirada'

  const minutos = Math.floor(diferencia / 60000)
  const segundos = Math.floor((diferencia % 60000) / 1000)

  return `${minutos}:${String(segundos).padStart(2, '0')}`
}

const numeroSeguro = (valor) => {
  const numero = Number.parseFloat(valor)
  return Number.isFinite(numero) ? numero : null
}

const obtenerCoordenadasConductor = () => {
  const latitud = numeroSeguro(ultimaUbicacion.value?.latitud)
  const longitud = numeroSeguro(ultimaUbicacion.value?.longitud)

  if (latitud === null || longitud === null) {
    return null
  }

  return [latitud, longitud]
}

const obtenerCoordenadasOrigenViaje = () => {
  const latitud = numeroSeguro(viajeActivo.value?.latitud_origen)
  const longitud = numeroSeguro(viajeActivo.value?.longitud_origen)

  if (latitud === null || longitud === null) {
    return null
  }

  return [latitud, longitud]
}

const obtenerCoordenadasDestinoViaje = () => {
  const latitud = numeroSeguro(viajeActivo.value?.latitud_destino)
  const longitud = numeroSeguro(viajeActivo.value?.longitud_destino)

  if (latitud === null || longitud === null) {
    return null
  }

  return [latitud, longitud]
}

const obtenerObjetivoNavegacion = () => {
  return viajeActivo.value?.estado === 'En Curso'
    ? obtenerCoordenadasDestinoViaje()
    : obtenerCoordenadasOrigenViaje()
}

const crearIconoNavegacion = (tipo) => {
  const configuracion = {
    conductor: {
      clase: 'driver',
      contenido: '🛵'
    },
    origen: {
      clase: 'pickup',
      contenido: 'P'
    },
    destino: {
      clase: 'destination',
      contenido: 'D'
    }
  }

  const datos = configuracion[tipo] || configuracion.conductor

  return L.divIcon({
    className: 'motrix-map-marker-wrapper',
    html: `
      <div class="motrix-map-marker ${datos.clase}">
        <span>${datos.contenido}</span>
      </div>
    `,
    iconSize: [38, 44],
    iconAnchor: [19, 42],
    popupAnchor: [0, -40]
  })
}

const eliminarCapaMapa = (capa) => {
  if (mapaNavegacion && capa) {
    mapaNavegacion.removeLayer(capa)
  }
}

const destruirMapaNavegacion = () => {
  if (mapaNavegacion) {
    mapaNavegacion.remove()
  }

  mapaNavegacion = null
  marcadorConductor = null
  marcadorOrigenViaje = null
  marcadorDestinoViaje = null
  lineaRutaNavegacion = null
  navegacionDistanciaKm.value = 0
  navegacionDuracionMin.value = 0
  navegacionCargando.value = false
  navegacionError.value = null
  ultimaRutaCalculadaEn = 0
  ultimoObjetivoRuta = ''
}

const colocarMarcadoresFijos = () => {
  if (!mapaNavegacion || !viajeActivo.value) return

  const origen = obtenerCoordenadasOrigenViaje()
  const destino = obtenerCoordenadasDestinoViaje()

  eliminarCapaMapa(marcadorOrigenViaje)
  eliminarCapaMapa(marcadorDestinoViaje)

  marcadorOrigenViaje = null
  marcadorDestinoViaje = null

  if (origen) {
    marcadorOrigenViaje = L.marker(origen, {
      icon: crearIconoNavegacion('origen')
    })
      .addTo(mapaNavegacion)
      .bindPopup(
        `<b>Punto de recogida</b><br>${viajeActivo.value.origen || 'Origen del pasajero'}`
      )
  }

  if (destino) {
    marcadorDestinoViaje = L.marker(destino, {
      icon: crearIconoNavegacion('destino')
    })
      .addTo(mapaNavegacion)
      .bindPopup(
        `<b>Destino</b><br>${viajeActivo.value.destino || 'Destino del viaje'}`
      )
  }
}

const actualizarMarcadorConductor = () => {
  if (!mapaNavegacion) return

  const ubicacion = obtenerCoordenadasConductor()

  if (!ubicacion) {
    eliminarCapaMapa(marcadorConductor)
    marcadorConductor = null
    return
  }

  if (!marcadorConductor) {
    marcadorConductor = L.marker(ubicacion, {
      icon: crearIconoNavegacion('conductor'),
      zIndexOffset: 1000
    })
      .addTo(mapaNavegacion)
      .bindPopup('<b>Tu ubicación actual</b>')
  } else {
    marcadorConductor.setLatLng(ubicacion)
  }
}

const calcularDistanciaLineaRecta = (origen, destino) => {
  if (!origen || !destino) return 0

  return Number.parseFloat(
    (L.latLng(origen).distanceTo(L.latLng(destino)) / 1000).toFixed(2)
  )
}

const dibujarRutaAlternativa = (origen, destino) => {
  eliminarCapaMapa(lineaRutaNavegacion)

  lineaRutaNavegacion = L.polyline(
    [origen, destino],
    {
      color: '#1976d2',
      weight: 5,
      opacity: 0.8,
      dashArray: '10, 8'
    }
  ).addTo(mapaNavegacion)

  navegacionDistanciaKm.value = calcularDistanciaLineaRecta(
    origen,
    destino
  )

  navegacionDuracionMin.value = navegacionDistanciaKm.value > 0
    ? (navegacionDistanciaKm.value / 25) * 60
    : 0
}

const calcularRutaNavegacion = async (forzar = false) => {
  if (!mapaNavegacion || !viajeActivo.value) return

  const origen = obtenerCoordenadasConductor()
  const destino = obtenerObjetivoNavegacion()

  if (!origen || !destino) {
    navegacionDistanciaKm.value = 0
    navegacionDuracionMin.value = 0
    return
  }

  const objetivoActual = [
    viajeActivo.value.id,
    viajeActivo.value.estado,
    destino[0].toFixed(5),
    destino[1].toFixed(5)
  ].join('|')

  const ahoraRuta = Date.now()
  const rutaReciente = ahoraRuta - ultimaRutaCalculadaEn < 15000

  if (
    !forzar
    && rutaReciente
    && ultimoObjetivoRuta === objetivoActual
  ) {
    return
  }

  navegacionCargando.value = true
  navegacionError.value = null

  try {
    const coordenadas = (
      `${origen[1]},${origen[0]};${destino[1]},${destino[0]}`
    )

    const response = await axios.get(
      `https://router.project-osrm.org/route/v1/driving/${coordenadas}`,
      {
        params: {
          overview: 'full',
          geometries: 'geojson',
          steps: false
        },
        timeout: 12000
      }
    )

    const ruta = response.data?.routes?.[0]

    if (!ruta?.geometry) {
      throw new Error('OSRM no devolvió una ruta válida.')
    }

    eliminarCapaMapa(lineaRutaNavegacion)

    lineaRutaNavegacion = L.geoJSON(
      ruta.geometry,
      {
        style: {
          color: viajeActivo.value.estado === 'En Curso'
            ? '#303f9f'
            : '#1976d2',
          weight: 6,
          opacity: 0.88
        }
      }
    ).addTo(mapaNavegacion)

    navegacionDistanciaKm.value = Number.parseFloat(
      (Number(ruta.distance || 0) / 1000).toFixed(2)
    )

    navegacionDuracionMin.value = Number.parseFloat(
      (Number(ruta.duration || 0) / 60).toFixed(1)
    )

    ultimaRutaCalculadaEn = ahoraRuta
    ultimoObjetivoRuta = objetivoActual
  } catch (error) {
    console.error('Error calculando navegación interna:', error)
    navegacionError.value = 'No se pudo cargar la ruta por calles.'
    dibujarRutaAlternativa(origen, destino)
  } finally {
    navegacionCargando.value = false
  }
}

const ajustarVistaMapaNavegacion = () => {
  if (!mapaNavegacion) return

  const puntos = [
    obtenerCoordenadasConductor(),
    obtenerCoordenadasOrigenViaje(),
    obtenerCoordenadasDestinoViaje()
  ].filter(Boolean)

  if (puntos.length >= 2) {
    mapaNavegacion.fitBounds(
      L.latLngBounds(puntos),
      {
        padding: [35, 35],
        maxZoom: 16
      }
    )
    return
  }

  if (puntos.length === 1) {
    mapaNavegacion.setView(puntos[0], 16)
    return
  }

  mapaNavegacion.setView([-14.8308, -64.9024], 14)
}

const actualizarMapaNavegacion = async (forzarRuta = false) => {
  if (!mapaNavegacion || !tieneViajeActivo.value) return

  colocarMarcadoresFijos()
  actualizarMarcadorConductor()
  ajustarVistaMapaNavegacion()
  await calcularRutaNavegacion(forzarRuta)
}

const inicializarMapaNavegacion = async (forzarRuta = true) => {
  if (!tieneViajeActivo.value) {
    destruirMapaNavegacion()
    return
  }

  await nextTick()

  const contenedor = document.getElementById(
    'mapa-navegacion-conductor'
  )

  if (!contenedor) return

  if (!mapaNavegacion) {
    mapaNavegacion = L.map(contenedor, {
      zoomControl: true,
      attributionControl: true
    }).setView([-14.8308, -64.9024], 14)

    L.tileLayer(
      'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
      {
        attribution: '© OpenStreetMap',
        maxZoom: 19
      }
    ).addTo(mapaNavegacion)
  }

  window.setTimeout(() => {
    mapaNavegacion?.invalidateSize()
  }, 150)

  await actualizarMapaNavegacion(forzarRuta)
}

const centrarMapaNavegacion = async () => {
  if (!mapaNavegacion) {
    await inicializarMapaNavegacion(true)
    return
  }

  ajustarVistaMapaNavegacion()
  await calcularRutaNavegacion(true)
}

const esperar = (milisegundos) => {
  return new Promise((resolve) => {
    window.setTimeout(resolve, milisegundos)
  })
}

const prepararAudioNotificaciones = async () => {
  try {
    const AudioContexto = window.AudioContext || window.webkitAudioContext

    if (!AudioContexto) return false

    if (!audioContexto) {
      audioContexto = new AudioContexto()
    }

    if (audioContexto.state === 'suspended') {
      await audioContexto.resume()
    }

    return audioContexto.state === 'running'
  } catch (error) {
    console.warn('No se pudo habilitar el audio de notificaciones:', error)
    return false
  }
}

const reproducirSonidoSolicitud = async () => {
  const audioDisponible = await prepararAudioNotificaciones()

  if (!audioDisponible || !audioContexto) return

  try {
    const inicio = audioContexto.currentTime
    const tonos = [880, 1046, 1318]

    tonos.forEach((frecuencia, indice) => {
      const oscilador = audioContexto.createOscillator()
      const ganancia = audioContexto.createGain()
      const empieza = inicio + (indice * 0.24)
      const termina = empieza + 0.18

      oscilador.type = 'sine'
      oscilador.frequency.setValueAtTime(frecuencia, empieza)

      ganancia.gain.setValueAtTime(0.0001, empieza)
      ganancia.gain.exponentialRampToValueAtTime(0.32, empieza + 0.025)
      ganancia.gain.exponentialRampToValueAtTime(0.0001, termina)

      oscilador.connect(ganancia)
      ganancia.connect(audioContexto.destination)
      oscilador.start(empieza)
      oscilador.stop(termina)
    })

    if (typeof navigator.vibrate === 'function') {
      navigator.vibrate([250, 120, 250, 120, 400])
    }
  } catch (error) {
    console.warn('No se pudo reproducir el sonido de la solicitud:', error)
  }
}

const cerrarAlertaSolicitud = (solicitudId = null) => {
  if (
    solicitudId
    && Number(solicitudDestacada.value?.id) !== Number(solicitudId)
  ) {
    return
  }

  dialogNuevaSolicitud.value = false
  solicitudDestacada.value = null
}

const mostrarAlertaSolicitud = async (solicitud) => {
  const solicitudId = Number(solicitud?.id)

  if (
    !solicitudId
    || !disponible.value
    || tieneViajeActivo.value
    || solicitudesNotificadas.has(solicitudId)
  ) {
    return
  }

  solicitudesNotificadas.add(solicitudId)
  solicitudDestacada.value = solicitud
  dialogNuevaSolicitud.value = true

  $q.notify({
    color: 'positive',
    textColor: 'white',
    icon: 'notifications_active',
    message: `Nueva solicitud de ${getPasajeroNombre(solicitud)}.`,
    caption: `${formatearDistancia(solicitud.distancia_recogida_km)} hasta el pasajero · ${formatearMonto(solicitud.precio)}`,
    position: 'top',
    timeout: 7000,
    actions: [
      {
        label: 'VER',
        color: 'white',
        handler: () => {
          solicitudDestacada.value = solicitud
          dialogNuevaSolicitud.value = true
        }
      }
    ]
  })

  await reproducirSonidoSolicitud()
}

const sincronizarSolicitudRecibida = async (solicitudEvento) => {
  if (!disponible.value || tieneViajeActivo.value) return

  const solicitudId = Number(solicitudEvento?.id)

  for (let intento = 0; intento < 5; intento += 1) {
    await cargarSolicitudesDisponibles()

    const solicitudAsignada = solicitudes.value.find(
      (item) => Number(item?.id) === solicitudId
    )

    if (solicitudAsignada) {
      await mostrarAlertaSolicitud(solicitudAsignada)
      return
    }

    if (intento < 4) {
      await esperar(550)
    }
  }
}


const obtenerEndpointAutorizacionChat = () => {
  const baseConfigurada = String(
    api?.defaults?.baseURL || ''
  ).trim().replace(/\/+$/, '')

  if (/^https?:\/\//i.test(baseConfigurada)) {
    return `${baseConfigurada}/broadcasting/auth`
  }

  return BROADCAST_AUTH_URL
}

const obtenerCabecerasAutorizacionChat = () => {
  const token = localStorage.getItem('motrix_token') || ''

  return {
    Accept: 'application/json',
    ...(token
      ? { Authorization: `Bearer ${token}` }
      : {})
  }
}

const esMensajeChatPropio = (mensaje) => {
  return String(mensaje?.remitente_tipo || '').toLowerCase() === 'conductor'
}

const formatearHoraChat = (fecha) => {
  if (!fecha) return ''

  const normalizada = String(fecha).includes('T')
    ? String(fecha)
    : String(fecha).replace(' ', 'T')

  const valor = new Date(normalizada)

  if (Number.isNaN(valor.getTime())) {
    return String(fecha).slice(11, 16)
  }

  return valor.toLocaleTimeString('es-BO', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const desplazarChatAlFinal = async () => {
  await nextTick()

  if (chatContenedor.value) {
    chatContenedor.value.scrollTop = chatContenedor.value.scrollHeight
  }
}

const agregarMensajeChat = async (mensaje) => {
  if (!mensaje?.id) return false

  const existe = chatMensajes.value.some(
    (item) => Number(item.id) === Number(mensaje.id)
  )

  if (existe) return false

  chatMensajes.value.push(mensaje)
  await desplazarChatAlFinal()
  return true
}

const marcarMensajesChatLeidos = async () => {
  if (!viajeActivo.value?.id) return

  try {
    await api.post(
      `/conductor/solicitudes/${viajeActivo.value.id}/mensajes/leidos`
    )

    const fechaLectura = new Date().toISOString()

    chatMensajes.value = chatMensajes.value.map((mensaje) => {
      if (
        String(mensaje.remitente_tipo).toLowerCase() === 'pasajero'
        && !mensaje.leido_conductor_en
      ) {
        return {
          ...mensaje,
          leido_conductor_en: fechaLectura
        }
      }

      return mensaje
    })

    chatNoLeidos.value = 0
  } catch (error) {
    console.warn('No se pudieron marcar los mensajes como leídos:', error)
  }
}

const cargarChatViaje = async (silencioso = false) => {
  if (!viajeActivo.value?.id) {
    chatMensajes.value = []
    chatNoLeidos.value = 0
    chatHabilitado.value = false
    return
  }

  if (!silencioso) {
    chatCargando.value = true
  }

  try {
    const response = await api.get(
      `/conductor/solicitudes/${viajeActivo.value.id}/mensajes`,
      { params: { _t: Date.now() } }
    )

    chatMensajes.value = Array.isArray(response.data?.mensajes)
      ? response.data.mensajes
      : []

    chatHabilitado.value = Boolean(response.data?.chat_habilitado)
    chatNoLeidos.value = Number(response.data?.no_leidos || 0)

    if (dialogChat.value) {
      await marcarMensajesChatLeidos()
    }

    await desplazarChatAlFinal()
  } catch (error) {
    if (!silencioso) {
      $q.notify({
        type: 'negative',
        message: obtenerMensajeError(
          error,
          'No se pudo cargar el chat del viaje.'
        )
      })
    }
  } finally {
    if (!silencioso) {
      chatCargando.value = false
    }
  }
}

const reproducirSonidoChat = async () => {
  const disponibleAudio = await prepararAudioNotificaciones()

  if (!disponibleAudio || !audioContexto) return

  try {
    const inicio = audioContexto.currentTime
    const tonos = [740, 988]

    tonos.forEach((frecuencia, indice) => {
      const oscilador = audioContexto.createOscillator()
      const ganancia = audioContexto.createGain()
      const empieza = inicio + (indice * 0.16)
      const termina = empieza + 0.12

      oscilador.type = 'sine'
      oscilador.frequency.setValueAtTime(frecuencia, empieza)
      ganancia.gain.setValueAtTime(0.0001, empieza)
      ganancia.gain.exponentialRampToValueAtTime(0.20, empieza + 0.02)
      ganancia.gain.exponentialRampToValueAtTime(0.0001, termina)

      oscilador.connect(ganancia)
      ganancia.connect(audioContexto.destination)
      oscilador.start(empieza)
      oscilador.stop(termina)
    })
  } catch (error) {
    console.warn('No se pudo reproducir el sonido del chat:', error)
  }
}

const abrirChatViaje = async () => {
  if (!chatDisponible.value) {
    $q.notify({
      type: 'warning',
      message: 'El chat estará disponible cuando el viaje sea aceptado.'
    })
    return
  }

  dialogChat.value = true
  await cargarChatViaje()
  await marcarMensajesChatLeidos()
}

const alCerrarChat = () => {
  chatTexto.value = ''
}

const enviarMensajeChat = async (mensajeRapido = null) => {
  const texto = String(
    mensajeRapido ?? chatTexto.value
  ).trim()

  if (!texto || !viajeActivo.value?.id || chatEnviando.value) {
    return
  }

  chatEnviando.value = true

  try {
    const response = await api.post(
      `/conductor/solicitudes/${viajeActivo.value.id}/mensajes`,
      { mensaje: texto }
    )

    await agregarMensajeChat(response.data?.chat_mensaje)
    chatTexto.value = ''
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: obtenerMensajeError(
        error,
        'No se pudo enviar el mensaje.'
      )
    })
  } finally {
    chatEnviando.value = false
  }
}

const procesarMensajeChatRecibido = async (data) => {
  const mensaje = data?.mensaje

  if (
    !mensaje
    || Number(mensaje.solicitud_id) !== Number(viajeActivo.value?.id)
  ) {
    return
  }

  const agregado = await agregarMensajeChat(mensaje)

  if (!agregado || esMensajeChatPropio(mensaje)) {
    return
  }

  if (dialogChat.value) {
    await marcarMensajesChatLeidos()
    return
  }

  chatNoLeidos.value += 1
  await reproducirSonidoChat()

  $q.notify({
    color: 'primary',
    textColor: 'white',
    icon: 'chat',
    message: mensaje.remitente_nombre || 'Nuevo mensaje del pasajero',
    caption: mensaje.mensaje,
    position: 'top',
    timeout: 7000,
    actions: [
      {
        label: 'ABRIR',
        color: 'white',
        handler: abrirChatViaje
      }
    ]
  })
}

const normalizarIncidenciaActiva = () => {
  incidenciaActiva.value = incidenciasViaje.value.find((item) => {
    return ['Reportado', 'Recibido', 'En atención'].includes(
      String(item?.estado || '')
    )
  }) || null
}

const agregarOActualizarIncidencia = (incidencia) => {
  if (!incidencia?.id) return

  const indice = incidenciasViaje.value.findIndex(
    (item) => Number(item?.id) === Number(incidencia.id)
  )

  if (indice >= 0) {
    incidenciasViaje.value.splice(indice, 1, incidencia)
  } else {
    incidenciasViaje.value.unshift(incidencia)
  }

  normalizarIncidenciaActiva()
}

const cargarIncidenciasViaje = async (silencioso = false) => {
  if (!viajeActivo.value?.id) {
    incidenciasViaje.value = []
    incidenciaActiva.value = null
    return
  }

  try {
    const response = await api.get(
      `/conductor/solicitudes/${viajeActivo.value.id}/incidencias`,
      { params: { _t: Date.now() } }
    )

    incidenciasViaje.value = Array.isArray(response.data?.incidencias)
      ? response.data.incidencias
      : []

    incidenciaActiva.value = response.data?.incidencia_activa || null
  } catch (error) {
    if (!silencioso) {
      $q.notify({
        type: 'negative',
        message: obtenerMensajeError(
          error,
          'No se pudieron consultar las incidencias del viaje.'
        )
      })
    }
  }
}

const obtenerUbicacionSos = async () => {
  if (!navigator.geolocation) {
    sosEstadoGps.value = 'Este dispositivo no permite obtener la ubicación.'
    return
  }

  sosObteniendoUbicacion.value = true
  sosEstadoGps.value = 'Obteniendo ubicación actual...'

  await new Promise((resolve) => {
    navigator.geolocation.getCurrentPosition(
      (posicion) => {
        sosUbicacion.value = {
          latitud: posicion.coords.latitude,
          longitud: posicion.coords.longitude,
          precision_metros: posicion.coords.accuracy ?? null
        }
        sosEstadoGps.value = 'Ubicación capturada correctamente.'
        resolve()
      },
      (error) => {
        console.warn('No se pudo obtener la ubicación SOS:', error)

        const latitudGuardada = Number(ultimaUbicacion.value?.latitud)
        const longitudGuardada = Number(ultimaUbicacion.value?.longitud)

        if (
          Number.isFinite(latitudGuardada)
          && Number.isFinite(longitudGuardada)
        ) {
          sosUbicacion.value = {
            latitud: latitudGuardada,
            longitud: longitudGuardada,
            precision_metros: null
          }
          sosEstadoGps.value = 'Se utilizará la última ubicación registrada del conductor.'
        } else {
          sosEstadoGps.value = 'No se pudo obtener el GPS. La alerta se enviará sin ubicación.'
        }

        resolve()
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 15000
      }
    )
  })

  sosObteniendoUbicacion.value = false
}

const abrirDialogoSos = async () => {
  if (!sosDisponible.value) {
    $q.notify({
      type: 'warning',
      message: 'El botón SOS solo está disponible durante un viaje activo.'
    })
    return
  }

  sosTipo.value = ''
  sosDescripcion.value = ''
  sosUbicacion.value = {
    latitud: null,
    longitud: null,
    precision_metros: null
  }
  sosEstadoGps.value = 'Obteniendo ubicación actual...'
  dialogSos.value = true
  await obtenerUbicacionSos()
}

const enviarAlertaSos = async () => {
  if (!sosTipo.value || !viajeActivo.value?.id || sosEnviando.value) {
    return
  }

  sosEnviando.value = true

  try {
    const payload = {
      tipo: sosTipo.value,
      descripcion: String(sosDescripcion.value || '').trim() || null,
      latitud: sosUbicacion.value.latitud,
      longitud: sosUbicacion.value.longitud,
      precision_metros: sosUbicacion.value.precision_metros
    }

    const response = await api.post(
      `/conductor/solicitudes/${viajeActivo.value.id}/incidencias`,
      payload
    )

    agregarOActualizarIncidencia(response.data?.incidencia)
    dialogSos.value = false

    $q.notify({
      color: 'negative',
      textColor: 'white',
      icon: 'sos',
      message: response.data?.mensaje || 'La alerta fue enviada a la central MOTRIX.',
      caption: response.data?.advertencia,
      position: 'top',
      timeout: 10000
    })
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: obtenerMensajeError(
        error,
        'No se pudo enviar la alerta SOS.'
      )
    })
  } finally {
    sosEnviando.value = false
  }
}

const procesarIncidenciaTiempoReal = async (data, actualizada = false) => {
  const incidencia = data?.incidencia

  if (
    !incidencia
    || Number(incidencia.solicitud_id) !== Number(viajeActivo.value?.id)
  ) {
    return
  }

  agregarOActualizarIncidencia(incidencia)

  if (actualizada) {
    $q.notify({
      color: incidencia.estado === 'Resuelto' ? 'positive' : 'orange-9',
      textColor: 'white',
      icon: incidencia.estado === 'Resuelto' ? 'check_circle' : 'support_agent',
      message: `La alerta ${incidencia.codigo || ''} está ${incidencia.estado}.`,
      caption: incidencia.nota_administrador || 'La central MOTRIX actualizó la atención.',
      position: 'top',
      timeout: 7000
    })
  }
}

const salirCanalIncidencias = () => {
  if (!echoInstance || !canalIncidenciasSolicitudId) return

  echoInstance.leave(`viajes.incidencias.${canalIncidenciasSolicitudId}`)
  canalIncidenciasSolicitudId = null
}

const sincronizarCanalIncidencias = async () => {
  const solicitudId = Number(viajeActivo.value?.id || 0)

  if (!echoInstance || !solicitudId) {
    salirCanalIncidencias()
    incidenciasViaje.value = []
    incidenciaActiva.value = null
    return
  }

  if (Number(canalIncidenciasSolicitudId) === solicitudId) {
    await cargarIncidenciasViaje(true)
    return
  }

  salirCanalIncidencias()
  canalIncidenciasSolicitudId = solicitudId

  echoInstance
    .private(`viajes.incidencias.${solicitudId}`)
    .listen('.IncidenciaViajeReportada', (data) => {
      procesarIncidenciaTiempoReal(data, false).catch(console.error)
    })
    .listen('.IncidenciaViajeActualizada', (data) => {
      procesarIncidenciaTiempoReal(data, true).catch(console.error)
    })

  await cargarIncidenciasViaje(true)
}

const salirCanalChat = () => {
  if (!echoInstance || !canalChatSolicitudId) return

  echoInstance.leave(`viajes.chat.${canalChatSolicitudId}`)
  canalChatSolicitudId = null
}

const sincronizarCanalChat = async () => {
  const solicitudId = Number(viajeActivo.value?.id || 0)

  if (!echoInstance || !solicitudId) {
    salirCanalChat()
    chatNoLeidos.value = 0
    chatMensajes.value = []
    chatHabilitado.value = false
    return
  }

  if (Number(canalChatSolicitudId) === solicitudId) {
    return
  }

  salirCanalChat()
  canalChatSolicitudId = solicitudId

  echoInstance
    .private(`viajes.chat.${solicitudId}`)
    .listen('.MensajeViajeEnviado', (data) => {
      procesarMensajeChatRecibido(data).catch((error) => {
        console.error('Error procesando mensaje del chat:', error)
      })
    })

  await cargarChatViaje(true)
}

const inicializarWebsocket = () => {
  if (echoInstance) return

  try {
    echoInstance = new Echo({
      ...echoOptions(),
      authEndpoint: obtenerEndpointAutorizacionChat(),
      auth: {
        headers: obtenerCabecerasAutorizacionChat()
      }
    })

    const conexion = echoInstance.connector?.pusher?.connection

    conexion?.bind('connected', () => {
      websocketConectado.value = true
    })

    conexion?.bind('disconnected', () => {
      websocketConectado.value = false
    })

    conexion?.bind('error', (error) => {
      console.error('Error de conexión con Reverb:', error)
      websocketConectado.value = false
    })

    echoInstance
      .channel('solicitudes')
      .listen('.SolicitudCreada', (data) => {
        sincronizarSolicitudRecibida(data?.solicitud).catch((error) => {
          console.error('Error procesando solicitud en tiempo real:', error)
        })
      })
  } catch (error) {
    console.error('Error inicializando Laravel Echo:', error)
    websocketConectado.value = false
  }
}

const desconectarWebsocket = () => {
  if (!echoInstance) return

  salirCanalChat()
  salirCanalIncidencias()
  echoInstance.leaveChannel('solicitudes')
  echoInstance.disconnect()
  echoInstance = null
  websocketConectado.value = false
}

const cargarMototaxista = async () => {
  const response = await api.get(
    '/conductor/perfil',
    { params: { _t: Date.now() } }
  )

  mototaxista.value = response.data || null
  disponible.value = Boolean(response.data?.disponible)

  const latitud = numeroSeguro(response.data?.latitud)
  const longitud = numeroSeguro(response.data?.longitud)

  if (latitud !== null && longitud !== null) {
    ultimaUbicacion.value = {
      latitud,
      longitud
    }
  }
}

const obtenerViajeActivo = async () => {
  try {
    const response = await api.get(
      '/conductor/viaje-activo',
      { params: { _t: Date.now() } }
    )

    viajeActivo.value = normalizarViajeActivo(response.data)
  } catch (error) {
    console.error('Error obteniendo viaje activo:', error)
    viajeActivo.value = null
  }
}

const cargarSolicitudesDisponibles = async () => {
  if (!disponible.value || tieneViajeActivo.value) {
    solicitudes.value = []
    solicitudesInicializadas = true
    cerrarAlertaSolicitud()
    return
  }

  const idsAnteriores = new Set(
    solicitudes.value.map((item) => Number(item?.id)).filter(Boolean)
  )

  try {
    const response = await api.get(
      '/conductor/solicitudes-disponibles',
      { params: { _t: Date.now() } }
    )

    const datos = response.data
    let solicitudesActuales = []

    if (Array.isArray(datos)) {
      solicitudesActuales = datos
    } else if (Array.isArray(datos?.data)) {
      solicitudesActuales = datos.data
    } else if (Array.isArray(datos?.solicitudes)) {
      solicitudesActuales = datos.solicitudes
    }

    solicitudes.value = solicitudesActuales

    const idsActuales = new Set(
      solicitudesActuales.map((item) => Number(item?.id)).filter(Boolean)
    )

    if (
      solicitudDestacada.value?.id
      && !idsActuales.has(Number(solicitudDestacada.value.id))
    ) {
      cerrarAlertaSolicitud(solicitudDestacada.value.id)
    }

    if (solicitudesInicializadas) {
      const nuevaSolicitud = solicitudesActuales.find((item) => {
        const itemId = Number(item?.id)
        return itemId && !idsAnteriores.has(itemId)
      })

      if (nuevaSolicitud) {
        await mostrarAlertaSolicitud(nuevaSolicitud)
      }
    }

    solicitudesInicializadas = true
  } catch (error) {
    console.error('Error cargando solicitudes disponibles:', error)
    solicitudes.value = []
  }
}

const obtenerCalificacion = (item) => {
  const numero = Number.parseFloat(
    item?.calificacion
  )

  return Number.isFinite(numero)
    ? Math.min(5, Math.max(1, numero))
    : 0
}

const tieneCalificacion = (item) => {
  return obtenerCalificacion(item) > 0
}

const obtenerGanancias = async () => {
  try {
    const response = await api.get(
      '/conductor/ganancias',
      { params: { _t: Date.now() } }
    )

    ganancias.value = {
      viajes_totales:
        Number(response.data?.viajes_totales) || 0,

      ganancia_efectivo:
        Number(response.data?.ganancia_efectivo) || 0,

      ganancia_qr:
        Number(response.data?.ganancia_qr) || 0,

      total_recaudado:
        Number(response.data?.total_recaudado) || 0,

      promedio_calificacion:
        Number(response.data?.promedio_calificacion) || 0,

      total_calificaciones:
        Number(response.data?.total_calificaciones) || 0
    }

    historial.value = Array.isArray(response.data?.detalles_pagos)
      ? response.data.detalles_pagos
      : []
  } catch (error) {
    console.error('Error cargando ganancias:', error)
  }
}

const refrescarOperacion = async () => {
  await cargarMototaxista()
  await obtenerViajeActivo()
  await cargarSolicitudesDisponibles()
}

const cargarTodo = async (mostrarCarga = false) => {
  if (mostrarCarga) loading.value = true

  try {
    await refrescarOperacion()
    await obtenerGanancias()
  } catch (error) {
    console.error('Error actualizando el panel:', error)

    if (mostrarCarga) {
      $q.notify({
        type: 'negative',
        message: obtenerMensajeError(error, 'No se pudo actualizar el panel.')
      })
    }
  } finally {
    loading.value = false
  }
}

const cambiarDisponibilidad = async (nuevoEstado) => {
  if (cambiandoDisponibilidad.value) return

  if (nuevoEstado) {
    await prepararAudioNotificaciones()
  }

  const estadoAnterior = disponible.value
  cambiandoDisponibilidad.value = true

  try {
    if (nuevoEstado) {
      const posicion = await obtenerPosicionActual()
      await enviarUbicacion(posicion)

      await api.patch(
        '/conductor/disponibilidad',
        { disponible: true }
      )

      disponible.value = true
      iniciarSeguimientoGPS()

      $q.notify({
        type: 'positive',
        icon: 'wifi',
        message: 'Estás en línea y disponible para recibir viajes.',
        position: 'top'
      })
    } else {
      await api.patch(
        '/conductor/disponibilidad',
        { disponible: false }
      )

      disponible.value = false
      solicitudes.value = []
      detenerSeguimientoGPS()

      $q.notify({
        type: 'info',
        icon: 'wifi_off',
        message: 'Ahora estás fuera de línea.',
        position: 'top'
      })
    }

    await refrescarOperacion()
  } catch (error) {
    console.error('Error cambiando disponibilidad:', error)
    disponible.value = estadoAnterior

    $q.notify({
      type: 'negative',
      message: obtenerMensajeError(
        error,
        nuevoEstado
          ? 'No se pudo activar el modo en línea. Verifica el GPS.'
          : 'No se pudo cambiar la disponibilidad.'
      )
    })
  } finally {
    cambiandoDisponibilidad.value = false
  }
}

const obtenerPosicionActual = () => {
  return new Promise((resolve, reject) => {
    if (!navigator.geolocation) {
      reject(new Error('El navegador no admite geolocalización.'))
      return
    }

    gpsEstado.value = 'buscando'
    gpsMensaje.value = 'Obteniendo ubicación...'

    navigator.geolocation.getCurrentPosition(
      (posicion) => {
        gpsEstado.value = 'activo'
        gpsMensaje.value = 'Ubicación activa'
        resolve(posicion)
      },
      (error) => {
        gpsEstado.value = 'error'

        if (error.code === 1) {
          gpsMensaje.value = 'Permiso de ubicación denegado'
        } else if (error.code === 2) {
          gpsMensaje.value = 'Ubicación no disponible'
        } else if (error.code === 3) {
          gpsMensaje.value = 'Tiempo de espera agotado'
        } else {
          gpsMensaje.value = 'No se pudo obtener el GPS'
        }

        reject(error)
      },
      {
        enableHighAccuracy: true,
        timeout: 15000,
        maximumAge: 5000
      }
    )
  })
}

const enviarUbicacion = async (posicion) => {
  const latitud = posicion.coords.latitude
  const longitud = posicion.coords.longitude

  await api.patch(
    '/conductor/ubicacion',
    { latitud, longitud }
  )

  ultimaUbicacion.value = { latitud, longitud }
  gpsEstado.value = 'activo'
  gpsMensaje.value = 'Ubicación compartida'
  ultimaUbicacionEnviada = Date.now()
}

const iniciarSeguimientoGPS = () => {
  if (!navigator.geolocation || gpsWatchId !== null) return

  gpsEstado.value = 'buscando'
  gpsMensaje.value = 'Activando seguimiento GPS...'

  gpsWatchId = navigator.geolocation.watchPosition(
    async (posicion) => {
      gpsEstado.value = 'activo'
      gpsMensaje.value = 'Ubicación activa'

      if (Date.now() - ultimaUbicacionEnviada < 10000) return

      try {
        await enviarUbicacion(posicion)
      } catch (error) {
        console.error('Error enviando ubicación:', error)
        gpsEstado.value = 'error'
        gpsMensaje.value = 'Error sincronizando ubicación'
      }
    },
    (error) => {
      console.error('Error de seguimiento GPS:', error)
      gpsEstado.value = 'error'

      if (error.code === 1) {
        gpsMensaje.value = 'Permiso de ubicación denegado'
      } else {
        gpsMensaje.value = 'Seguimiento GPS interrumpido'
      }
    },
    {
      enableHighAccuracy: true,
      timeout: 15000,
      maximumAge: 5000
    }
  )
}

const detenerSeguimientoGPS = () => {
  if (gpsWatchId !== null && navigator.geolocation) {
    navigator.geolocation.clearWatch(gpsWatchId)
  }

  gpsWatchId = null
  gpsEstado.value = 'inactivo'
  gpsMensaje.value = 'GPS desactivado'
}

const aceptarViaje = async (viaje) => {
  accionando.value = `aceptar-${viaje.id}`

  try {
    await api.post(
      `/conductor/solicitudes/${viaje.id}/aceptar`,
      {}
    )

    solicitudes.value = []
    disponible.value = false
    cerrarAlertaSolicitud(viaje.id)

    $q.notify({
      type: 'positive',
      icon: 'check_circle',
      message: 'Viaje aceptado correctamente.',
      position: 'top'
    })

    await refrescarOperacion()
  } catch (error) {
    console.error('Error aceptando viaje:', error)

    $q.notify({
      type: 'negative',
      message: obtenerMensajeError(error, 'No se pudo aceptar el viaje.')
    })

    await refrescarOperacion()
  } finally {
    accionando.value = null
  }
}

const rechazarViaje = async (viaje) => {
  accionando.value = `rechazar-${viaje.id}`

  try {
    const response = await api.post(
      `/conductor/solicitudes/${viaje.id}/rechazar`,
      {}
    )

    solicitudes.value = []
    cerrarAlertaSolicitud(viaje.id)

    $q.notify({
      type: response.data?.reasignado ? 'info' : 'warning',
      message: response.data?.mensaje || 'Solicitud rechazada.',
      position: 'top'
    })

    await refrescarOperacion()
  } catch (error) {
    console.error('Error rechazando viaje:', error)

    $q.notify({
      type: 'negative',
      message: obtenerMensajeError(
        error,
        'No se pudo rechazar la solicitud.'
      )
    })

    await refrescarOperacion()
  } finally {
    accionando.value = null
  }
}

const cambiarEstadoViaje = async (
  viaje,
  nuevoEstado,
  datosAdicionales = {}
) => {
  await api.put(
    `/conductor/solicitudes/${viaje.id}/estado`,
    {
      estado: nuevoEstado,
      ...datosAdicionales
    }
  )

  await refrescarOperacion()
  await obtenerGanancias()
}

const marcarLlegada = async (viaje) => {
  accionando.value = `llegue-${viaje.id}`

  try {
    await cambiarEstadoViaje(viaje, 'Llegó')

    $q.notify({
      type: 'positive',
      icon: 'person_pin_circle',
      message: 'Llegada confirmada. El pasajero fue notificado.',
      position: 'top'
    })
  } catch (error) {
    console.error('Error confirmando llegada:', error)

    $q.notify({
      type: 'negative',
      message: obtenerMensajeError(
        error,
        'No se pudo confirmar la llegada.'
      )
    })
  } finally {
    accionando.value = null
  }
}

const iniciarViaje = async (viaje) => {
  accionando.value = `iniciar-${viaje.id}`

  try {
    await cambiarEstadoViaje(viaje, 'En Curso')

    $q.notify({
      type: 'positive',
      icon: 'navigation',
      message: 'Viaje iniciado. Dirígete al destino.',
      position: 'top'
    })
  } catch (error) {
    console.error('Error iniciando viaje:', error)

    $q.notify({
      type: 'negative',
      message: obtenerMensajeError(error, 'No se pudo iniciar el viaje.')
    })
  } finally {
    accionando.value = null
  }
}

const confirmarCancelacion = (viaje) => {
  $q.dialog({
    title: 'Cancelar viaje',
    message: 'Indica brevemente el motivo de cancelación.',
    prompt: {
      model: '',
      type: 'text',
      isValid: (valor) => String(valor || '').trim().length >= 3
    },
    cancel: {
      label: 'Volver',
      flat: true
    },
    ok: {
      label: 'Cancelar viaje',
      color: 'negative'
    },
    persistent: true
  }).onOk(async (motivo) => {
    accionando.value = `cancelar-${viaje.id}`

    try {
      await cambiarEstadoViaje(
        viaje,
        'Cancelado',
        { motivo_cancelacion: String(motivo).trim() }
      )

      $q.notify({
        type: 'warning',
        message: 'El viaje fue cancelado.',
        position: 'top'
      })
    } catch (error) {
      console.error('Error cancelando viaje:', error)

      $q.notify({
        type: 'negative',
        message: obtenerMensajeError(error, 'No se pudo cancelar el viaje.')
      })
    } finally {
      accionando.value = null
    }
  })
}

const abrirModalCobro = (viaje) => {
  viajeSeleccionado.value = viaje
  dialogCobro.value = true
}

const procesarFinalizacionConPago = async (metodoPago) => {
  if (!viajeSeleccionado.value?.id) return

  procesandoCobro.value = true

  try {
    await cambiarEstadoViaje(
      viajeSeleccionado.value,
      'Finalizado',
      { metodo_pago: metodoPago }
    )

    dialogCobro.value = false
    viajeSeleccionado.value = null

    $q.notify({
      type: 'positive',
      icon: 'payments',
      message: `Viaje finalizado. Pago registrado: ${metodoPago}.`,
      position: 'top'
    })

    if (disponible.value) {
      iniciarSeguimientoGPS()
    }
  } catch (error) {
    console.error('Error finalizando viaje:', error)

    $q.notify({
      type: 'negative',
      message: obtenerMensajeError(error, 'No se pudo finalizar el viaje.')
    })
  } finally {
    procesandoCobro.value = false
  }
}

const abrirNavegacion = (viaje, punto) => {
  const esOrigen = punto === 'origen'

  const latitud = esOrigen
    ? viaje.latitud_origen
    : viaje.latitud_destino

  const longitud = esOrigen
    ? viaje.longitud_origen
    : viaje.longitud_destino

  const direccion = esOrigen ? viaje.origen : viaje.destino

  let destino

  if (
    latitud !== null
    && latitud !== undefined
    && longitud !== null
    && longitud !== undefined
  ) {
    destino = `${latitud},${longitud}`
  } else {
    destino = direccion || 'Trinidad, Beni, Bolivia'
  }

  const url = (
    'https://www.google.com/maps/dir/?api=1'
    + `&destination=${encodeURIComponent(destino)}`
  )

  window.open(url, '_blank', 'noopener,noreferrer')
}

watch(
  () => [
    viajeActivo.value?.id || null,
    viajeActivo.value?.estado || null
  ],
  async ([nuevoId, nuevoEstado], [idAnterior, estadoAnterior]) => {
    if (!nuevoId) {
      destruirMapaNavegacion()
      return
    }

    const cambioViaje = nuevoId !== idAnterior
    const cambioEstado = nuevoEstado !== estadoAnterior

    await inicializarMapaNavegacion(
      cambioViaje || cambioEstado
    )
  }
)

watch(
  () => [
    ultimaUbicacion.value?.latitud || null,
    ultimaUbicacion.value?.longitud || null
  ],
  async () => {
    if (tieneViajeActivo.value) {
      await inicializarMapaNavegacion(false)
    }
  }
)


watch(
  () => viajeActivo.value?.id || null,
  async () => {
    await sincronizarCanalChat()
    await sincronizarCanalIncidencias()

    if (!viajeActivo.value?.id) {
      dialogChat.value = false
      dialogSos.value = false
    }
  }
)

onMounted(async () => {
  if (!validarSesionConductor()) {
    return
  }

  window.addEventListener(
    'pointerdown',
    prepararAudioNotificaciones,
    { once: true }
  )

  inicializarWebsocket()
  await cargarTodo(true)
  await sincronizarCanalChat()
  await sincronizarCanalIncidencias()

  if (disponible.value || tieneViajeActivo.value) {
    iniciarSeguimientoGPS()
  }

  if (tieneViajeActivo.value) {
    await inicializarMapaNavegacion(true)
  }

  intervaloActualizacion = window.setInterval(async () => {
    try {
      /*
       * Refresca tanto el viaje como las ganancias y la reputación.
       * Así, cuando el pasajero califica, el conductor ve la nueva
       * puntuación automáticamente sin recargar toda la página.
       */
      await refrescarOperacion()
      await obtenerGanancias()
    } catch (error) {
      console.error('Error en actualización automática:', error)
    }
  }, 8000)

  intervaloReloj = window.setInterval(() => {
    ahora.value = Date.now()
  }, 1000)

  intervaloChat = window.setInterval(() => {
    if (viajeActivo.value?.id) {
      cargarChatViaje(true).catch(() => {})
      cargarIncidenciasViaje(true).catch(() => {})
    }
  }, 15000)
})

onBeforeUnmount(() => {
  detenerSeguimientoGPS()
  destruirMapaNavegacion()
  desconectarWebsocket()
  cerrarAlertaSolicitud()

  if (audioContexto && audioContexto.state !== 'closed') {
    audioContexto.close().catch(() => {})
  }

  audioContexto = null

  if (intervaloActualizacion) {
    window.clearInterval(intervaloActualizacion)
  }

  if (intervaloReloj) {
    window.clearInterval(intervaloReloj)
  }

  if (intervaloChat) {
    window.clearInterval(intervaloChat)
  }
})
</script>

<style scoped>
.sos-dialog-card {
  width: min(94vw, 580px);
  border-radius: 18px;
  overflow: hidden;
}

.sos-location-card {
  border-radius: 14px;
  background: #fafafa;
}

.border-radius-md {
  border-radius: 10px;
}

.border-radius-lg {
  border-radius: 16px;
}

.overflow-hidden {
  overflow: hidden;
}

.min-width-zero {
  min-width: 0;
}

.nueva-solicitud-dialog {
  width: min(94vw, 560px);
  max-width: 560px;
  overflow: hidden;
  border-top: 6px solid #21ba45;
}

.solicitud-alerta-avatar {
  animation: pulso-solicitud 1.15s ease-in-out infinite;
}

@keyframes pulso-solicitud {
  0%,
  100% {
    transform: scale(1);
  }

  50% {
    transform: scale(1.1);
  }
}

.reputacion-card {
  overflow: hidden;
  border-left: 5px solid #ffb300;
  background:
    linear-gradient(
      135deg,
      #fffdf5 0%,
      #fff8e1 100%
    );
}

.historial-viaje-card {
  overflow: hidden;
  border-left: 4px solid #21ba45;
  background: #ffffff;
}

.calificacion-recibida {
  padding: 10px;
  border-radius: 10px;
  background: #fffdf5;
  border: 1px solid #ffe082;
}

.comentario-recibido {
  padding: 8px 10px;
  color: #5d4037;
  font-size: 13px;
  font-style: italic;
  line-height: 1.45;
  border-radius: 8px;
  background: #fff8e1;
}

.status-info-box {
  display: flex;
  align-items: center;
  gap: 12px;
  min-height: 64px;
  padding: 12px;
  background: #f7f8fa;
  border-radius: 12px;
}

.viaje-activo-card {
  overflow: hidden;
  border-left: 6px solid #1976d2;
}

.solicitud-card {
  overflow: hidden;
  border-left: 5px solid #21ba45;
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease;
}

.solicitud-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.14);
}

.route-row {
  display: grid;
  grid-template-columns: 18px minmax(0, 1fr);
  gap: 10px;
  align-items: start;
}

.route-point {
  width: 14px;
  height: 14px;
  margin-top: 4px;
  border-radius: 50%;
  border: 3px solid white;
  box-shadow: 0 0 0 2px currentColor;
}

.route-point-origin {
  color: #21ba45;
  background: #21ba45;
}

.route-point-destination {
  color: #c10015;
  background: #c10015;
}

.route-connector {
  width: 2px;
  height: 24px;
  margin-left: 6px;
  background:
    repeating-linear-gradient(
      to bottom,
      #bdbdbd 0,
      #bdbdbd 4px,
      transparent 4px,
      transparent 8px
    );
}

.route-connector.small {
  height: 18px;
}

.detail-box,
.request-detail {
  display: flex;
  align-items: center;
  gap: 9px;
  min-height: 62px;
  padding: 10px;
  background: #f6f7f9;
  border-radius: 10px;
}

.navegacion-card {
  overflow: hidden;
  border-radius: 14px;
  background: #ffffff;
}

.navigation-stat {
  display: flex;
  align-items: center;
  gap: 9px;
  min-height: 58px;
  padding: 9px 10px;
  background: #f6f8fb;
  border-radius: 10px;
}

.mapa-navegacion-wrapper {
  position: relative;
  width: 100%;
  height: 430px;
  overflow: hidden;
  background: #eceff1;
  border-top: 1px solid #e0e0e0;
  border-bottom: 1px solid #e0e0e0;
}

.mapa-navegacion {
  width: 100%;
  height: 100%;
  z-index: 1;
}

.mapa-aviso {
  position: absolute;
  top: 12px;
  left: 50%;
  z-index: 500;
  display: flex;
  align-items: center;
  gap: 8px;
  max-width: calc(100% - 24px);
  padding: 9px 12px;
  border-radius: 10px;
  transform: translateX(-50%);
  font-size: 13px;
}

.map-legend {
  width: 12px;
  height: 12px;
  margin-right: 6px;
  border-radius: 50%;
  box-shadow: 0 0 0 2px #ffffff, 0 0 0 3px currentColor;
}

.map-legend.conductor {
  color: #1976d2;
  background: #1976d2;
}

.map-legend.origen {
  color: #21ba45;
  background: #21ba45;
}

.map-legend.destino {
  color: #c10015;
  background: #c10015;
}

:deep(.motrix-map-marker-wrapper) {
  background: transparent;
  border: 0;
}

:deep(.motrix-map-marker) {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  color: #ffffff;
  border: 3px solid #ffffff;
  border-radius: 50% 50% 50% 0;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.35);
  transform: rotate(-45deg);
}

:deep(.motrix-map-marker span) {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 15px;
  font-weight: 800;
  line-height: 1;
  transform: rotate(45deg);
}

:deep(.motrix-map-marker.driver) {
  background: #1976d2;
}

:deep(.motrix-map-marker.driver span) {
  font-size: 17px;
}

:deep(.motrix-map-marker.pickup) {
  background: #21ba45;
}

:deep(.motrix-map-marker.destination) {
  background: #c10015;
}

.cobro-dialog {
  width: 100%;
  max-width: 460px;
}

@media (max-width: 599px) {
  .mapa-navegacion-wrapper {
    height: 340px;
  }

  .navigation-stat {
    min-height: 54px;
  }

  .status-info-box {
    min-height: 58px;
  }

  .detail-box,
  .request-detail {
    min-height: 58px;
  }
}

.chat-dialog-card {
  width: min(720px, 96vw);
  height: min(760px, 92vh);
  max-width: 720px;
  border-radius: 18px;
  overflow: hidden;
}

.chat-messages {
  min-height: 280px;
  padding: 16px;
  overflow-y: auto;
  background: #eef2f5;
  scroll-behavior: smooth;
}

.chat-empty {
  min-height: 100%;
  padding: 42px 20px;
}

.chat-row {
  display: flex;
  width: 100%;
  margin-bottom: 10px;
}

.chat-row-own {
  justify-content: flex-end;
}

.chat-row-other {
  justify-content: flex-start;
}

.chat-bubble {
  max-width: 82%;
  padding: 9px 12px 6px;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
}

.chat-bubble-own {
  color: #1b4332;
  background: #d8f3dc;
  border-bottom-right-radius: 5px;
}

.chat-bubble-other {
  color: #263238;
  background: #ffffff;
  border-bottom-left-radius: 5px;
}

.chat-message-text {
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}

.chat-message-time {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  margin-top: 3px;
  color: #78909c;
  font-size: 11px;
}

.chat-quick-scroll {
  padding-bottom: 4px;
  overflow-x: auto;
}

@media (max-width: 599px) {
  .chat-dialog-card {
    width: 100%;
    height: 100%;
    max-width: none;
    border-radius: 0;
  }

  .chat-bubble {
    max-width: 90%;
  }
}

</style>
