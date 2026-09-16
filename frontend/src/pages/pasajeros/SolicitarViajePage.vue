<template>
  <q-page class="q-pa-sm q-pa-md-md bg-grey-2">
    <div class="row justify-center">
      <div class="col-12 col-xl-10">
        <q-card class="solicitud-card shadow-2">
          <!-- ENCABEZADO -->
          <q-card-section class="bg-positive text-white">
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
                text-color="positive"
                icon="two_wheeler"
                size="52px"
                class="q-mr-md"
              />

              <div class="col">
                <div class="text-h5 text-weight-bold">
                  Solicitar mototaxi
                </div>

                <div class="text-caption text-green-1">
                  Marca tu origen y destino en el mapa
                </div>
              </div>

              <q-btn
                flat
                round
                icon="refresh"
                color="white"
                :loading="cargandoViaje"
                @click="cargarViajeActivo"
              >
                <q-tooltip>
                  Actualizar información
                </q-tooltip>
              </q-btn>
            </div>
          </q-card-section>

          <!-- CARGANDO -->
          <q-card-section
            v-if="cargandoInicial"
            class="column flex-center q-pa-xl"
          >
            <q-spinner
              color="positive"
              size="52px"
            />

            <div class="text-grey-7 q-mt-md">
              Consultando tus viajes...
            </div>
          </q-card-section>

          <!-- VIAJE ACTIVO -->
          <template v-else-if="viajeActivo">
            <q-card-section class="q-pa-md q-pa-lg-md">
              <q-banner
                rounded
                :class="bannerEstadoClase"
              >
                <template #avatar>
                  <q-spinner-dots
                    v-if="estaBuscando"
                    color="orange-9"
                    size="36px"
                  />

                  <q-icon
                    v-else
                    :name="estadoIcono"
                    :color="estadoColor"
                    size="36px"
                  />
                </template>

                <div class="text-subtitle1 text-weight-bold">
                  {{ tituloEstado }}
                </div>

                <div class="text-body2">
                  {{ descripcionEstado }}
                </div>
              </q-banner>

              <div class="row q-col-gutter-md q-mt-sm">
                <!-- DATOS DEL VIAJE -->
                <div class="col-12 col-md-7">
                  <q-card
                    flat
                    bordered
                    class="full-height"
                  >
                    <q-card-section>
                      <div class="row items-center justify-between q-mb-md">
                        <div>
                          <div class="text-h6 text-weight-bold">
                            Viaje #{{ viajeActivo.id }}
                          </div>

                          <div class="text-caption text-grey-6">
                            {{ formatearFecha(viajeActivo.fecha) }}
                          </div>
                        </div>

                        <q-chip
                          :color="estadoColor"
                          text-color="white"
                          class="text-weight-bold text-uppercase"
                        >
                          {{ viajeActivo.estado }}
                        </q-chip>
                      </div>

                      <div class="route-item">
                        <q-icon
                          name="radio_button_checked"
                          color="positive"
                          size="22px"
                        />

                        <div>
                          <div class="text-caption text-grey-6">
                            Origen
                          </div>

                          <div class="text-body1 text-weight-medium">
                            {{ viajeActivo.origen }}
                          </div>
                        </div>
                      </div>

                      <div class="route-connector" />

                      <div class="route-item">
                        <q-icon
                          name="location_on"
                          color="negative"
                          size="24px"
                        />

                        <div>
                          <div class="text-caption text-grey-6">
                            Destino
                          </div>

                          <div class="text-body1 text-weight-medium">
                            {{ viajeActivo.destino }}
                          </div>
                        </div>
                      </div>

                      <q-separator class="q-my-md" />

                      <div class="row q-col-gutter-md">
                        <div class="col-6">
                          <div class="text-caption text-grey-6">
                            Distancia
                          </div>

                          <div class="text-subtitle1 text-weight-bold">
                            {{ formatearDistancia(viajeActivo.distancia_km) }}
                          </div>
                        </div>

                        <div class="col-6">
                          <div class="text-caption text-grey-6">
                            Tarifa
                          </div>

                          <div class="text-h6 text-weight-bold text-positive">
                            {{ formatearPrecio(viajeActivo.precio) }}
                          </div>
                        </div>

                        <div class="col-12">
                          <div class="text-caption text-grey-6">
                            Método de pago
                          </div>

                          <div class="text-subtitle1 text-weight-medium">
                            {{ viajeActivo.metodo_pago || 'Efectivo' }}
                          </div>
                        </div>
                      </div>

                      <q-card
                        v-if="requiereQrViajeActivo && viajeActivo?.mototaxista_id"
                        flat
                        bordered
                        class="qr-pago-card q-mt-md"
                      >
                        <q-card-section class="text-center">
                          <div class="text-subtitle1 text-weight-bold text-indigo-9">
                            QR de cobro del mototaxista
                          </div>
                          <div class="text-caption text-grey-7 q-mb-md">
                            Usa este QR para la parte digital del pago.
                          </div>

                          <img
                            v-if="qrPagoConductorUrl"
                            :src="qrPagoConductorUrl"
                            alt="QR de cobro del mototaxista"
                            class="qr-pago-image"
                          >

                          <q-banner
                            v-else
                            rounded
                            class="bg-orange-1 text-orange-10 text-left"
                          >
                            Este mototaxista aún no registró un QR de cobro.
                            Coordina el pago directamente con el conductor.
                          </q-banner>

                          <div
                            v-if="qrPagoConductorUrl"
                            class="q-mt-md"
                          >
                            <div class="text-weight-bold">
                              {{ qrPagoMetodoConductor }}
                            </div>
                            <div class="text-caption text-grey-7">
                              Titular: {{ qrPagoTitularConductor }}
                            </div>
                          </div>
                        </q-card-section>
                      </q-card>
                    </q-card-section>
                  </q-card>
                </div>

                <!-- CONDUCTOR -->
                <div class="col-12 col-md-5">
                  <q-card
                    flat
                    bordered
                    class="full-height"
                  >
                    <q-card-section class="text-center">
                      <q-avatar
                        size="78px"
                        :color="nombreConductor ? 'primary' : 'grey-4'"
                        :text-color="nombreConductor ? 'white' : 'grey-7'"
                        class="conductor-avatar"
                        :class="{ 'cursor-pointer': Boolean(fotoConductorUrl) }"
                        @click="abrirFotoConductor"
                      >
                        <img
                          v-if="fotoConductorUrl"
                          :src="fotoConductorUrl"
                          :alt="`Foto de ${nombreConductor || 'mototaxista'}`"
                        >
                        <q-icon v-else name="two_wheeler" size="40px" />
                      </q-avatar>

                      <div class="text-caption text-grey-6 q-mt-md">
                        Mototaxista asignado
                      </div>

                      <div
                        v-if="nombreConductor"
                        class="text-h6 text-weight-bold text-primary"
                      >
                        {{ nombreConductor }}
                      </div>

                      <div
                        v-else
                        class="text-subtitle1 text-grey-7"
                      >
                        Buscando conductor cercano...
                      </div>

                      <div
                        v-if="nombreConductor"
                        class="row items-center justify-center q-gutter-xs q-mt-xs"
                      >
                        <q-rating
                          v-if="totalCalificacionesConductor > 0"
                          :model-value="promedioCalificacionConductor"
                          readonly
                          size="20px"
                          color="amber-7"
                          icon="star_border"
                          icon-selected="star"
                        />
                        <span
                          v-if="totalCalificacionesConductor > 0"
                          class="text-caption text-weight-bold text-grey-9"
                        >
                          {{ promedioCalificacionConductor.toFixed(1) }}
                          ({{ totalCalificacionesConductor }})
                        </span>
                        <span v-else class="text-caption text-grey-6">
                          Sin calificaciones
                        </span>
                      </div>

                      <div
                        v-if="viajeActivo.mototaxista_id"
                        class="text-caption text-grey-6 q-mt-xs"
                      >
                        Código de conductor:
                        #{{ viajeActivo.mototaxista_id }}
                      </div>

                      <q-separator class="q-my-md" />

                      <q-banner
                        v-if="incidenciaActiva"
                        rounded
                        class="bg-red-1 text-red-10 q-mt-sm text-left"
                      >
                        <template #avatar>
                          <q-icon name="warning" color="negative" />
                        </template>
                        <div class="text-weight-bold">
                          Alerta {{ incidenciaActiva.codigo || '' }} en {{ incidenciaActiva.estado }}
                        </div>
                        <div class="text-caption">
                          {{ incidenciaActiva.tipo }} · La central MOTRIX fue informada.
                        </div>
                      </q-banner>

                      <q-btn
                        v-if="sosDisponible"
                        color="negative"
                        icon="sos"
                        :label="incidenciaActiva ? 'Reportar otra emergencia' : 'SOS / Reportar incidente'"
                        class="full-width text-weight-bold q-mt-sm"
                        unelevated
                        @click="abrirDialogoSos"
                      />

                      <q-btn
                        v-if="puedeMostrarSeguimiento"
                        outline
                        color="green-8"
                        icon="share_location"
                        label="Compartir seguimiento"
                        class="full-width q-mt-sm"
                        :loading="compartiendoViaje"
                        @click="compartirViaje"
                      />

                      <q-btn
                        outline
                        color="primary"
                        icon="refresh"
                        label="Actualizar estado"
                        class="full-width q-mt-sm"
                        :loading="cargandoViaje"
                        @click="cargarViajeActivo"
                      />

                      <q-btn
                        v-if="puedeCancelar"
                        outline
                        color="negative"
                        icon="cancel"
                        label="Cancelar solicitud"
                        class="full-width q-mt-sm"
                        :loading="cancelando"
                        @click="confirmarCancelacion"
                      />
                    </q-card-section>
                  </q-card>
                </div>
              </div>

              <!-- SEGUIMIENTO DEL MOTOTAXISTA -->
              <q-card
                v-if="puedeMostrarSeguimiento"
                flat
                bordered
                class="seguimiento-card q-mt-md"
              >
                <q-card-section class="row items-center q-col-gutter-sm">
                  <div class="col">
                    <div class="row items-center no-wrap">
                      <q-avatar
                        color="blue-1"
                        text-color="primary"
                        icon="my_location"
                        size="46px"
                        class="q-mr-md"
                      />

                      <div class="min-width-zero">
                        <div class="text-h6 text-weight-bold text-grey-9">
                          Seguimiento en tiempo real
                        </div>

                        <div class="text-caption text-grey-6">
                          {{ tituloSeguimiento }}
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="col-auto">
                    <q-chip
                      :color="estadoColor"
                      text-color="white"
                      icon="two_wheeler"
                      class="text-weight-bold"
                    >
                      {{ estadoSeguimientoTexto }}
                    </q-chip>
                  </div>
                </q-card-section>

                <q-separator />

                <q-card-section>
                  <div class="row q-col-gutter-sm q-mb-md">
                    <div class="col-6 col-md-4">
                      <div class="tracking-info-box">
                        <q-icon name="route" color="primary" size="24px" />

                        <div>
                          <div class="text-caption text-grey-6">
                            Distancia restante
                          </div>

                          <div class="text-subtitle1 text-weight-bold">
                            {{ distanciaSeguimientoTexto }}
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="col-6 col-md-4">
                      <div class="tracking-info-box">
                        <q-icon name="schedule" color="orange-8" size="24px" />

                        <div>
                          <div class="text-caption text-grey-6">
                            Tiempo aproximado
                          </div>

                          <div class="text-subtitle1 text-weight-bold">
                            {{ tiempoSeguimientoTexto }}
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="col-12 col-md-4">
                      <div class="tracking-info-box">
                        <q-icon name="gps_fixed" color="positive" size="24px" />

                        <div>
                          <div class="text-caption text-grey-6">
                            Última ubicación
                          </div>

                          <div class="text-body2 text-weight-bold">
                            {{ ultimaActualizacionTexto }}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <q-banner
                    v-if="!tieneUbicacionConductor"
                    rounded
                    class="bg-orange-1 text-orange-10"
                  >
                    <template #avatar>
                      <q-spinner-dots color="orange-9" size="34px" />
                    </template>

                    Esperando la ubicación GPS del mototaxista. El mapa se
                    mostrará automáticamente cuando el conductor comparta su
                    posición.
                  </q-banner>

                  <template v-else>
                    <div class="map-wrapper seguimiento-map-wrapper">
                      <div
                        id="mapa-seguimiento-pasajero"
                        class="mapa-viaje"
                      />

                      <div class="map-actions">
                        <q-btn
                          round
                          color="primary"
                          icon="center_focus_strong"
                          size="sm"
                          @click="centrarMapaSeguimiento"
                        >
                          <q-tooltip>
                            Centrar la ruta
                          </q-tooltip>
                        </q-btn>
                      </div>
                    </div>

                    <div class="row items-center justify-center q-gutter-md q-mt-sm">
                      <div class="row items-center">
                        <span class="leyenda leyenda-conductor" />
                        <span class="text-caption">Mototaxista</span>
                      </div>

                      <div class="row items-center">
                        <span class="leyenda leyenda-origen" />
                        <span class="text-caption">Tu ubicación</span>
                      </div>

                      <div class="row items-center">
                        <span class="leyenda leyenda-destino" />
                        <span class="text-caption">Destino</span>
                      </div>

                      <div class="row items-center">
                        <span class="leyenda-ruta" />
                        <span class="text-caption">Ruta actual</span>
                      </div>
                    </div>
                  </template>
                </q-card-section>
              </q-card>
            </q-card-section>
          </template>

          <!-- VIAJE FINALIZADO PENDIENTE DE CALIFICACIÓN -->
          <template v-else-if="viajeFinalizado">
            <q-card-section class="q-pa-md q-pa-lg-md">
              <q-banner
                rounded
                class="bg-green-1 text-green-10 q-mb-md"
              >
                <template #avatar>
                  <q-icon
                    name="check_circle"
                    color="positive"
                    size="38px"
                  />
                </template>

                <div class="text-h6 text-weight-bold">
                  Viaje finalizado correctamente
                </div>

                <div class="text-body2">
                  Revisa el resumen y califica la atención del mototaxista.
                </div>
              </q-banner>

              <div class="row q-col-gutter-md">
                <div class="col-12 col-md-7">
                  <q-card
                    flat
                    bordered
                    class="resumen-final-card full-height"
                  >
                    <q-card-section>
                      <div class="row items-center justify-between q-mb-md">
                        <div>
                          <div class="text-h6 text-weight-bold">
                            Resumen del viaje #{{ viajeFinalizado.id }}
                          </div>

                          <div class="text-caption text-grey-6">
                            {{ formatearFecha(viajeFinalizado.fecha) }}
                          </div>
                        </div>

                        <q-chip
                          color="positive"
                          text-color="white"
                          icon="done_all"
                          class="text-weight-bold"
                        >
                          FINALIZADO
                        </q-chip>
                      </div>

                      <div class="route-item">
                        <q-icon
                          name="radio_button_checked"
                          color="positive"
                          size="22px"
                        />

                        <div>
                          <div class="text-caption text-grey-6">
                            Origen
                          </div>

                          <div class="text-body1 text-weight-medium">
                            {{ viajeFinalizado.origen }}
                          </div>
                        </div>
                      </div>

                      <div class="route-connector" />

                      <div class="route-item">
                        <q-icon
                          name="location_on"
                          color="negative"
                          size="24px"
                        />

                        <div>
                          <div class="text-caption text-grey-6">
                            Destino
                          </div>

                          <div class="text-body1 text-weight-medium">
                            {{ viajeFinalizado.destino }}
                          </div>
                        </div>
                      </div>

                      <q-separator class="q-my-md" />

                      <div class="row q-col-gutter-md">
                        <div class="col-6">
                          <div class="text-caption text-grey-6">
                            Distancia
                          </div>

                          <div class="text-subtitle1 text-weight-bold">
                            {{
                              formatearDistancia(
                                viajeFinalizado.distancia_km
                              )
                            }}
                          </div>
                        </div>

                        <div class="col-6">
                          <div class="text-caption text-grey-6">
                            Monto pagado
                          </div>

                          <div class="text-h6 text-weight-bold text-positive">
                            {{ formatearPrecio(viajeFinalizado.precio) }}
                          </div>
                        </div>

                        <div class="col-12">
                          <div class="text-caption text-grey-6">
                            Método de pago
                          </div>

                          <div class="text-subtitle1 text-weight-medium">
                            {{
                              viajeFinalizado.metodo_pago
                              || 'Efectivo'
                            }}
                          </div>
                        </div>
                      </div>

                      <q-separator class="q-my-md" />

                      <div class="row items-center no-wrap">
                        <q-avatar
                          color="blue-1"
                          text-color="primary"
                          icon="two_wheeler"
                          size="48px"
                          class="q-mr-md"
                        />

                        <div>
                          <div class="text-caption text-grey-6">
                            Mototaxista
                          </div>

                          <div class="text-subtitle1 text-weight-bold text-primary">
                            {{ nombreConductorFinalizado }}
                          </div>
                        </div>
                      </div>
                    </q-card-section>
                  </q-card>
                </div>

                <div class="col-12 col-md-5">
                  <q-card
                    flat
                    bordered
                    class="calificacion-card full-height"
                  >
                    <q-card-section class="text-center">
                      <q-avatar
                        color="amber-1"
                        text-color="amber-9"
                        icon="star"
                        size="64px"
                      />

                      <div class="text-h6 text-weight-bold q-mt-md">
                        ¿Cómo estuvo tu viaje?
                      </div>

                      <div class="text-body2 text-grey-6 q-mt-xs">
                        Selecciona de una a cinco estrellas.
                      </div>

                      <q-rating
                        v-model="calificacion"
                        :max="5"
                        size="44px"
                        color="amber"
                        icon="star_border"
                        icon-selected="star"
                        class="q-my-lg"
                      />

                      <q-input
                        v-model="comentarioCalificacion"
                        outlined
                        type="textarea"
                        autogrow
                        maxlength="500"
                        counter
                        label="Comentario opcional"
                        placeholder="Cuéntanos cómo fue la atención"
                      />

                      <q-btn
                        color="positive"
                        icon="send"
                        label="Enviar calificación"
                        class="full-width q-mt-md text-weight-bold"
                        unelevated
                        :loading="enviandoCalificacion"
                        :disable="calificacion < 1"
                        @click="enviarCalificacion"
                      />

                      <q-btn
                        flat
                        color="grey-7"
                        label="Calificar después"
                        class="full-width q-mt-sm"
                        :disable="enviandoCalificacion"
                        @click="omitirCalificacion"
                      />
                    </q-card-section>
                  </q-card>
                </div>
              </div>
            </q-card-section>
          </template>

          <!-- FORMULARIO NUEVA SOLICITUD -->
          <template v-else>
            <q-form @submit.prevent="enviarSolicitud">
              <q-card-section class="q-pa-md q-pa-lg-md">
                <q-banner
                  rounded
                  class="bg-blue-1 text-primary q-mb-md"
                >
                  <template #avatar>
                    <q-icon
                      name="touch_app"
                      size="30px"
                    />
                  </template>

                  Marca tu <strong>origen</strong> en el mapa o usa tu
                  ubicación actual. Para el <strong>destino</strong>, escribe
                  una calle, barrio o lugar y pulsa buscar. También puedes
                  marcar el destino directamente en el mapa.
                </q-banner>

                <div class="row q-col-gutter-lg">
                  <!-- FORMULARIO -->
                  <div class="col-12 col-md-5">
                    <div class="q-gutter-y-md">
                      <q-input
                        v-model="form.origen"
                        outlined
                        label="Punto de origen"
                        readonly
                        :rules="[
                          valor => Boolean(valor) ||
                            'Marca el punto de origen en el mapa'
                        ]"
                      >
                        <template #prepend>
                          <q-icon
                            name="radio_button_checked"
                            color="positive"
                          />
                        </template>
                      </q-input>

                      <q-btn
                        outline
                        color="positive"
                        icon="my_location"
                        label="Usar mi ubicación como origen"
                        class="full-width"
                        :loading="obteniendoUbicacion"
                        @click="usarUbicacionActual"
                      />

                      <q-input
                        v-model="busquedaDestino"
                        outlined
                        clearable
                        label="Punto de destino"
                        placeholder="Ej. Paitití, una calle o un lugar"
                        :loading="buscandoDestino"
                        :rules="[
                          () => Boolean(form.destino) ||
                            'Busca y selecciona un destino o márcalo en el mapa'
                        ]"
                        @update:model-value="alEditarBusquedaDestino"
                        @keydown.enter.prevent="buscarDestino"
                        @clear="limpiarDestinoSeleccionado(true)"
                      >
                        <template #prepend>
                          <q-icon
                            name="location_on"
                            color="negative"
                          />
                        </template>

                        <template #append>
                          <q-btn
                            round
                            flat
                            dense
                            color="primary"
                            icon="search"
                            :loading="buscandoDestino"
                            :disable="buscandoDestino"
                            @click="buscarDestino"
                          >
                            <q-tooltip>Buscar destino en Trinidad</q-tooltip>
                          </q-btn>
                        </template>
                      </q-input>

                      <q-card
                        v-if="resultadosDestino.length"
                        flat
                        bordered
                        class="destino-resultados"
                      >
                        <q-list separator>
                          <q-item
                            v-for="resultado in resultadosDestino"
                            :key="resultado.id"
                            clickable
                            v-ripple
                            class="destino-resultado"
                            @click="seleccionarResultadoDestino(resultado)"
                          >
                            <q-item-section avatar>
                              <q-avatar
                                color="red-1"
                                text-color="negative"
                                icon="place"
                              />
                            </q-item-section>

                            <q-item-section>
                              <q-item-label class="text-weight-medium">
                                {{ resultado.nombre }}
                              </q-item-label>

                              <q-item-label caption lines="2">
                                {{ resultado.direccion }}
                              </q-item-label>
                            </q-item-section>
                          </q-item>
                        </q-list>
                      </q-card>

                      <q-banner
                        v-else-if="busquedaDestinoRealizada && !buscandoDestino"
                        dense
                        rounded
                        class="bg-orange-1 text-orange-10"
                      >
                        <template #avatar>
                          <q-icon name="search_off" color="orange-9" />
                        </template>

                        No encontramos ese destino cerca de Trinidad. Prueba
                        con otra referencia o márcalo manualmente en el mapa.
                      </q-banner>

                      <div class="row q-col-gutter-sm">
                        <div class="col-12 col-sm-6">
                          <q-input
                            v-model="form.fecha"
                            outlined
                            type="date"
                            label="Fecha"
                            stack-label
                            readonly
                          />
                        </div>

                        <div class="col-12 col-sm-6">
                          <q-select
                            v-model="form.metodo_pago"
                            :options="metodosPago"
                            outlined
                            label="Método de pago"
                            emit-value
                            map-options
                          >
                            <template #prepend>
                              <q-icon name="payments" />
                            </template>
                          </q-select>
                        </div>
                      </div>

                      <q-card
                        flat
                        bordered
                        class="tarifa-card"
                      >
                        <q-card-section>
                          <div class="row items-center">
                            <q-avatar
                              color="green-1"
                              text-color="positive"
                              icon="payments"
                              size="50px"
                              class="q-mr-md"
                            />

                            <div class="col">
                              <div class="text-caption text-grey-6">
                                Tarifa estimada
                              </div>

                              <div class="text-h5 text-weight-bold text-positive">
                                {{ distanciaKm > 0 ? formatearPrecio(form.precio) : '—' }}
                              </div>
                            </div>

                            <div class="text-right">
                              <div class="text-caption text-grey-6">
                                Distancia
                              </div>

                              <div class="text-subtitle1 text-weight-bold">
                                {{ distanciaKm > 0
                                  ? `${distanciaKm} km`
                                  : '—'
                                }}
                              </div>
                            </div>
                          </div>
                        </q-card-section>
                      </q-card>

                      <q-banner
                        dense
                        rounded
                        class="bg-grey-2 text-grey-8"
                      >
                        <template #avatar>
                          <q-icon name="info" color="primary" />
                        </template>

                        Tarifas de referencia en Trinidad: Bs. 6 hasta
                        2 km; Bs. 8 hasta 4 km; Bs. 10 para viajes más largos.
                        De 23:00 a 06:00: Bs. 15.
                      </q-banner>
                    </div>
                  </div>

                  <!-- MAPA -->
                  <div class="col-12 col-md-7">
                    <div class="map-wrapper">
                      <div
                        id="mapa-viaje-pasajero"
                        class="mapa-viaje"
                      />

                      <div class="map-actions">
                        <q-btn
                          round
                          color="negative"
                          icon="restart_alt"
                          size="sm"
                          @click="limpiarMapa"
                        >
                          <q-tooltip>
                            Reiniciar origen y destino
                          </q-tooltip>
                        </q-btn>
                      </div>
                    </div>

                    <div class="row items-center justify-center q-gutter-md q-mt-sm">
                      <div class="row items-center">
                        <span class="leyenda leyenda-origen" />
                        <span class="text-caption">Origen</span>
                      </div>

                      <div class="row items-center">
                        <span class="leyenda leyenda-destino" />
                        <span class="text-caption">Destino</span>
                      </div>

                      <div class="row items-center">
                        <span class="leyenda-ruta" />
                        <span class="text-caption">Ruta estimada</span>
                      </div>
                    </div>
                  </div>
                </div>
              </q-card-section>

              <q-card-actions
                align="right"
                class="q-pa-md bg-grey-1"
              >
                <q-btn
                  flat
                  color="grey-7"
                  label="Volver"
                  @click="volver"
                />

                <q-btn
                  type="submit"
                  color="positive"
                  icon="two_wheeler"
                  label="Solicitar mototaxi"
                  :loading="enviando"
                  :disable="!rutaCompleta"
                  unelevated
                />
              </q-card-actions>
            </q-form>
          </template>
        </q-card>
      </div>
    </div>


    <!-- MODAL SOS DEL PASAJERO -->
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
              Reportar una emergencia
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
            La central MOTRIX recibirá esta alerta y los datos del viaje. Para una emergencia inmediata, comunícate también con el servicio público correspondiente.
          </q-banner>

          <q-select
            v-model="sosTipo"
            :options="tiposIncidenciaPasajero"
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
            placeholder="Ejemplo: no puedo localizar al conductor..."
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

    <!-- NOTIFICACIÓN DESTACADA DEL ESTADO DEL VIAJE -->
    <q-dialog
      v-model="dialogNotificacion"
      persistent
    >
      <q-card class="notificacion-viaje-card">
        <q-card-section
          :class="[
            `bg-${notificacionActual.color}`,
            'text-white'
          ]"
        >
          <div class="row items-center no-wrap">
            <q-avatar
              color="white"
              :text-color="notificacionActual.color"
              :icon="notificacionActual.icono"
              size="58px"
              class="q-mr-md"
            />

            <div class="col">
              <div class="text-h6 text-weight-bold">
                {{ notificacionActual.titulo }}
              </div>

              <div class="text-caption text-white">
                Viaje #{{ notificacionActual.solicitudId || '—' }}
              </div>
            </div>
          </div>
        </q-card-section>

        <q-card-section class="q-pa-lg">
          <div class="text-body1 text-grey-9">
            {{ notificacionActual.mensaje }}
          </div>

          <q-banner
            v-if="notificacionActual.conductor"
            rounded
            class="bg-blue-1 text-primary q-mt-md"
          >
            <template #avatar>
              <q-avatar
                size="58px"
                color="white"
                text-color="primary"
                class="text-weight-bold"
              >
                <img
                  v-if="notificacionActual.foto && !notificacionFotoError"
                  :src="notificacionActual.foto"
                  :alt="`Foto de ${notificacionActual.conductor}`"
                  @error="notificacionFotoError = true"
                />

                <span v-else>
                  {{ notificacionActual.iniciales || 'MT' }}
                </span>
              </q-avatar>
            </template>

            <div class="text-caption">
              Mototaxista asignado
            </div>

            <div class="text-subtitle1 text-weight-bold">
              {{ notificacionActual.conductor }}
            </div>

            <div
              v-if="notificacionActual.sindicato"
              class="text-body2 q-mt-xs"
            >
              <q-icon name="groups" size="17px" class="q-mr-xs" />
              Sindicato {{ notificacionActual.sindicato }}
            </div>

            <div
              v-if="notificacionActual.chaleco"
              class="text-caption q-mt-xs"
            >
              N.º de chaleco: {{ notificacionActual.chaleco }}
            </div>
          </q-banner>
        </q-card-section>

        <q-card-actions
          align="right"
          class="q-pa-md bg-grey-1"
        >
          <q-btn
            color="primary"
            icon="check"
            label="Entendido"
            unelevated
            @click="dialogNotificacion = false"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <PhotoViewerDialog
      v-model="visorFotoConductor"
      :src="fotoConductorUrl"
      :title="nombreConductor || 'Mototaxista MOTRIX'"
    />
  </q-page>
</template>

<script setup>
import { fechaDDMMYYYY, fechaISOHoyBolivia } from 'src/utils/motrixDate.js'

import {
  computed,
  nextTick,
  onBeforeUnmount,
  onMounted,
  reactive,
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
import PhotoViewerDialog from '../../components/PhotoViewerDialog.vue'
window.Pusher = Pusher

const $q = useQuasar()
const router = useRouter()

const PASAJERO_ID = Number(
  leerUsuario()?.pasajero_id || 0
)

const LAT_TRINIDAD = -14.8308
const LNG_TRINIDAD = -64.9024

const TARIFA_CORTA_MAX_KM = 2
const TARIFA_MEDIA_MAX_KM = 4
const TARIFA_CORTA_BS = 6
const TARIFA_MEDIA_BS = 8
const TARIFA_LARGA_BS = 10
const TARIFA_NOCTURNA_BS = 15
const HORA_INICIO_NOCTURNA = 23
const HORA_FIN_NOCTURNA = 6

const RADIO_BUSQUEDA_TRINIDAD_KM = 35
const NOMINATIM_SEARCH_URL = 'https://nominatim.openstreetmap.org/search'

const cargandoInicial = ref(true)
const cargandoViaje = ref(false)
const enviando = ref(false)
const cancelando = ref(false)
const obteniendoUbicacion = ref(false)
const buscandoDestino = ref(false)
const busquedaDestino = ref('')
const resultadosDestino = ref([])
const busquedaDestinoRealizada = ref(false)

const viajeActivo = ref(null)
const viajeFinalizado = ref(null)
const calificacion = ref(0)
const comentarioCalificacion = ref('')
const enviandoCalificacion = ref(false)
const distanciaKm = ref(0)

const dialogNotificacion = ref(false)
const websocketConectado = ref(false)
const visorFotoConductor = ref(false)
const compartiendoViaje = ref(false)


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

const tiposIncidenciaPasajero = [
  'Accidente',
  'Emergencia médica',
  'Situación de inseguridad',
  'Conductor no localizado',
  'Otro'
]

const notificacionActual = ref({
  solicitudId: null,
  titulo: '',
  mensaje: '',
  conductor: '',
  sindicato: '',
  chaleco: '',
  foto: '',
  iniciales: '',
  icono: 'notifications_active',
  color: 'primary'
})
const notificacionFotoError = ref(false)

const distanciaSeguimientoKm = ref(null)
const tiempoSeguimientoMin = ref(null)
const actualizandoSeguimiento = ref(false)

const origenCoords = ref(null)
const destinoCoords = ref(null)

let mapa = null
let inicializandoMapa = false
let marcadorOrigen = null
let marcadorDestino = null
let lineaRuta = null

let mapaSeguimiento = null
let inicializandoMapaSeguimiento = false
let marcadorConductorSeguimiento = null
let marcadorOrigenSeguimiento = null
let marcadorDestinoSeguimiento = null
let lineaRutaSeguimiento = null
let ultimaClaveRutaSeguimiento = null
let ultimaRutaSeguimientoEn = 0
let intervaloActualizacion = null
let intervaloIncidencias = null
let cargaViajeEnCurso = false
let ultimaSincronizacionViaje = 0
let canalIncidenciasSolicitudId = null
let echoInstance = null
let audioContexto = null
let notificacionesHabilitadas = false

const firmasNotificadas = new Set()

const metodosPago = [
  {
    label: 'Efectivo',
    value: 'Efectivo'
  },
  {
    label: 'Pago por QR',
    value: 'QR'
  },
  {
    label: 'Pago mixto: efectivo + QR',
    value: 'Mixto'
  }
]

function obtenerFechaActual() {
  return fechaISOHoyBolivia()
}

const form = reactive({
  origen: '',
  destino: '',
  fecha: obtenerFechaActual(),
  precio: TARIFA_CORTA_BS,
  metodo_pago: 'Efectivo'
})

const rutaCompleta = computed(() => {
  return Boolean(
    form.origen
    && form.destino
    && origenCoords.value
    && destinoCoords.value
    && distanciaKm.value > 0
  )
})

const estadoNormalizado = computed(() => {
  return String(viajeActivo.value?.estado || '')
    .trim()
    .toLowerCase()
})

const estaBuscando = computed(() => {
  return ['pendiente', 'buscando conductor']
    .includes(estadoNormalizado.value)
})

const puedeCancelar = computed(() => {
  return [
    'pendiente',
    'buscando conductor',
    'aceptado',
    'llegó'
  ].includes(estadoNormalizado.value)
})

const estadoColor = computed(() => {
  if (estadoNormalizado.value === 'pendiente') {
    return 'orange-8'
  }

  if (estadoNormalizado.value === 'buscando conductor') {
    return 'orange-8'
  }

  if (estadoNormalizado.value === 'aceptado') {
    return 'blue-7'
  }

  if (estadoNormalizado.value === 'llegó') {
    return 'positive'
  }

  if (estadoNormalizado.value === 'en curso') {
    return 'indigo-9'
  }

  if (estadoNormalizado.value === 'finalizado') {
    return 'positive'
  }

  if (estadoNormalizado.value === 'cancelado') {
    return 'negative'
  }

  return 'grey-7'
})

const estadoIcono = computed(() => {
  if (estadoNormalizado.value === 'aceptado') {
    return 'person_pin_circle'
  }

  if (estadoNormalizado.value === 'llegó') {
    return 'where_to_vote'
  }

  if (estadoNormalizado.value === 'en curso') {
    return 'navigation'
  }

  return 'two_wheeler'
})

const tituloEstado = computed(() => {
  if (estaBuscando.value) {
    return 'Buscando un mototaxista cercano'
  }

  if (estadoNormalizado.value === 'aceptado') {
    return 'Tu mototaxista aceptó la solicitud'
  }

  if (estadoNormalizado.value === 'llegó') {
    return 'Tu mototaxista llegó'
  }

  if (estadoNormalizado.value === 'en curso') {
    return 'Tu viaje está en curso'
  }

  return 'Estado del viaje'
})

const descripcionEstado = computed(() => {
  if (estaBuscando.value) {
    return 'MOTRIX está localizando al conductor disponible más cercano.'
  }

  if (estadoNormalizado.value === 'aceptado') {
    return 'El conductor se dirige hacia el punto de origen.'
  }

  if (estadoNormalizado.value === 'llegó') {
    return 'El conductor te espera en el punto de recogida. Acércate para iniciar el viaje.'
  }

  if (estadoNormalizado.value === 'en curso') {
    return 'Te encuentras en camino hacia tu destino.'
  }

  return 'Actualiza la pantalla para consultar los cambios.'
})

const bannerEstadoClase = computed(() => {
  if (estaBuscando.value) {
    return 'bg-orange-1 text-orange-10'
  }

  if (estadoNormalizado.value === 'aceptado') {
    return 'bg-blue-1 text-primary'
  }

  if (estadoNormalizado.value === 'llegó') {
    return 'bg-green-1 text-green-10'
  }

  if (estadoNormalizado.value === 'en curso') {
    return 'bg-indigo-1 text-indigo-10'
  }

  return 'bg-grey-2 text-grey-9'
})

function nombreCompletoPersona(persona) {
  const nombreCompleto = String(
    persona?.nombre_completo || ''
  ).trim()

  if (nombreCompleto) return nombreCompleto

  const nombre = String(persona?.nombre || '').trim()
  const apellidos = String(persona?.apellidos || '').trim()

  return [nombre, apellidos]
    .filter(Boolean)
    .join(' ')
    .trim()
}

function resolverUrlImagenPersona(ruta) {
  const valor = String(ruta || '').trim()

  if (!valor) return ''

  if (/^https?:\/\//i.test(valor)) {
    return valor
  }

  const baseApi = String(api.defaults.baseURL || '')
    .replace(/\/api\/?$/i, '')
    .replace(/\/$/, '')

  let limpia = valor
    .replace(/^\/+/, '')
    .replace(/^public\//i, '')

  if (limpia.startsWith('storage/')) {
    return `${baseApi}/${limpia}`
  }

  return `${baseApi}/storage/${limpia}`
}

function fotoPersonaUrl(persona) {
  const imagenes = Array.isArray(persona?.imagenes)
    ? [...persona.imagenes]
    : []

  imagenes.sort(
    (a, b) => Number(b?.id || 0) - Number(a?.id || 0)
  )

  const ruta = (
    imagenes[0]?.ruta
    || persona?.foto
    || persona?.imagen
    || persona?.foto_url
    || persona?.imagen_url
    || ''
  )

  return resolverUrlImagenPersona(ruta)
}

function inicialesNombre(nombre) {
  const partes = String(nombre || '')
    .trim()
    .split(/\s+/)
    .filter(Boolean)

  if (!partes.length) return 'MT'

  return (
    (partes[0]?.charAt(0) || '')
    + (partes.length > 1
      ? partes[partes.length - 1]?.charAt(0)
      : '')
  ).toUpperCase()
}

const nombreConductor = computed(() => {
  const persona = viajeActivo.value?.mototaxista?.persona

  return (
    nombreCompletoPersona(persona)
    || viajeActivo.value?.mototaxista?.nombre
    || null
  )
})

const fotoConductorUrl = computed(() => {
  return fotoPersonaUrl(viajeActivo.value?.mototaxista?.persona)
})

const requiereQrViajeActivo = computed(() => {
  return ['QR', 'Transferencia / QR', 'Mixto'].includes(
    String(viajeActivo.value?.metodo_pago || '')
  )
})

const qrPagoConductorUrl = computed(() => {
  return resolverUrlImagenPersona(
    viajeActivo.value?.mototaxista?.qr_pago_ruta
  )
})

const qrPagoMetodoConductor = computed(() => {
  return String(
    viajeActivo.value?.mototaxista?.qr_pago_metodo
    || 'QR / billetera móvil'
  )
})

const qrPagoTitularConductor = computed(() => {
  return String(
    viajeActivo.value?.mototaxista?.qr_pago_titular
    || nombreConductor.value
    || 'Mototaxista MOTRIX'
  )
})

const promedioCalificacionConductor = computed(() => {
  return Number(viajeActivo.value?.mototaxista?.promedio_calificacion || 0)
})

const totalCalificacionesConductor = computed(() => {
  return Number(viajeActivo.value?.mototaxista?.total_calificaciones || 0)
})

function abrirFotoConductor() {
  if (fotoConductorUrl.value) {
    visorFotoConductor.value = true
  }
}

const sosDisponible = computed(() => {
  const estado = String(viajeActivo.value?.estado || '')

  return Boolean(
    viajeActivo.value?.id
    && ['Aceptado', 'Llegó', 'En Curso'].includes(estado)
  )
})

const nombreConductorFinalizado = computed(() => {
  const persona = viajeFinalizado.value?.mototaxista?.persona

  return (
    nombreCompletoPersona(persona)
    || viajeFinalizado.value?.mototaxista?.nombre
    || 'Mototaxista no identificado'
  )
})

const puedeMostrarSeguimiento = computed(() => {
  return Boolean(
    viajeActivo.value?.mototaxista_id
    && ['aceptado', 'llegó', 'en curso'].includes(estadoNormalizado.value)
  )
})

const coordenadasConductor = computed(() => {
  const latitud = numeroSeguro(
    viajeActivo.value?.mototaxista?.latitud
    ?? viajeActivo.value?.latitud_mototaxista
  )

  const longitud = numeroSeguro(
    viajeActivo.value?.mototaxista?.longitud
    ?? viajeActivo.value?.longitud_mototaxista
  )

  if (latitud === null || longitud === null) {
    return null
  }

  return [latitud, longitud]
})

const tieneUbicacionConductor = computed(() => {
  return Boolean(coordenadasConductor.value)
})

const tituloSeguimiento = computed(() => {
  if (estadoNormalizado.value === 'aceptado') {
    return 'El mototaxista se dirige hacia tu punto de recogida.'
  }

  if (estadoNormalizado.value === 'llegó') {
    return 'El mototaxista ya está esperando en tu punto de recogida.'
  }

  if (estadoNormalizado.value === 'en curso') {
    return 'Estás avanzando junto al mototaxista hacia el destino.'
  }

  return 'Consultando la ubicación del mototaxista.'
})

const estadoSeguimientoTexto = computed(() => {
  if (estadoNormalizado.value === 'aceptado') {
    return 'Conductor acercándose'
  }

  if (estadoNormalizado.value === 'llegó') {
    return 'Conductor llegó'
  }

  if (estadoNormalizado.value === 'en curso') {
    return 'Viaje en curso'
  }

  return 'Actualizando'
})

const distanciaSeguimientoTexto = computed(() => {
  const distancia = Number.parseFloat(distanciaSeguimientoKm.value)

  return Number.isFinite(distancia)
    ? `${distancia.toFixed(2)} km`
    : 'Calculando...'
})

const tiempoSeguimientoTexto = computed(() => {
  const minutos = Number.parseInt(tiempoSeguimientoMin.value, 10)

  return Number.isFinite(minutos)
    ? `${Math.max(1, minutos)} min`
    : 'Calculando...'
})

const ultimaActualizacionTexto = computed(() => {
  const fecha = (
    viajeActivo.value?.mototaxista?.ultima_conexion
    || viajeActivo.value?.mototaxista?.updated_at
    || null
  )

  if (!fecha) {
    return 'Actualizando ahora'
  }

  const objetoFecha = new Date(String(fecha).replace(' ', 'T'))

  if (Number.isNaN(objetoFecha.getTime())) {
    return 'Ubicación recibida'
  }

  return new Intl.DateTimeFormat('es-BO', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  }).format(objetoFecha)
})

function leerUsuario() {
  try {
    return JSON.parse(
      localStorage.getItem('motrix_user') || 'null'
    )
  } catch {
    return null
  }
}

function validarSesion() {
  const usuario = leerUsuario()

  if (
    usuario?.role !== 'pasajero'
    || !usuario?.pasajero_id
  ) {
    $q.notify({
      type: 'negative',
      message: 'Debes iniciar sesión con una cuenta de pasajero.'
    })

    router.replace('/login')
    return false
  }

  return true
}

function formatearPrecio(precio) {
  const numero = Number.parseFloat(precio)

  return `Bs. ${Number.isFinite(numero)
    ? numero.toFixed(2)
    : '0.00'
  }`
}

function formatearDistancia(distancia) {
  const numero = Number.parseFloat(distancia)

  return Number.isFinite(numero)
    ? `${numero.toFixed(2)} km`
    : 'No registrada'
}

function formatearFecha(fecha) {
  return fechaDDMMYYYY(fecha, 'Fecha no registrada')
}

function extraerMensajeError(error) {
  const erroresValidacion = error?.response?.data?.errors

  if (erroresValidacion) {
    const primerGrupo = Object.values(erroresValidacion)[0]

    if (Array.isArray(primerGrupo) && primerGrupo.length) {
      return primerGrupo[0]
    }
  }

  return (
    error?.response?.data?.mensaje
    || error?.response?.data?.message
    || 'Ocurrió un error al procesar la solicitud.'
  )
}

function calcularTarifa() {
  const hora = new Date().getHours()
  const esNocturno = (
    hora >= HORA_INICIO_NOCTURNA
    || hora < HORA_FIN_NOCTURNA
  )

  if (esNocturno) {
    form.precio = TARIFA_NOCTURNA_BS
    return
  }

  if (distanciaKm.value <= TARIFA_CORTA_MAX_KM) {
    form.precio = TARIFA_CORTA_BS
    return
  }

  if (distanciaKm.value <= TARIFA_MEDIA_MAX_KM) {
    form.precio = TARIFA_MEDIA_BS
    return
  }

  form.precio = TARIFA_LARGA_BS
}

function numeroSeguro(valor) {
  const numero = Number.parseFloat(valor)
  return Number.isFinite(numero) ? numero : null
}

function obtenerCoordenadasViaje(tipo) {
  const esOrigen = tipo === 'origen'

  const latitud = numeroSeguro(
    esOrigen
      ? viajeActivo.value?.latitud_origen
      : viajeActivo.value?.latitud_destino
  )

  const longitud = numeroSeguro(
    esOrigen
      ? viajeActivo.value?.longitud_origen
      : viajeActivo.value?.longitud_destino
  )

  if (latitud === null || longitud === null) {
    return null
  }

  return [latitud, longitud]
}

function crearIconoSeguimiento(tipo) {
  const configuracion = {
    conductor: {
      clase: 'pin-conductor-seguimiento',
      icono: 'two_wheeler',
      tamano: [44, 44],
      ancla: [22, 22]
    },
    origen: {
      clase: 'pin-origen-seguimiento',
      icono: 'person_pin_circle',
      tamano: [38, 38],
      ancla: [19, 38]
    },
    destino: {
      clase: 'pin-destino-seguimiento',
      icono: 'location_on',
      tamano: [38, 38],
      ancla: [19, 38]
    }
  }

  const opcion = configuracion[tipo] || configuracion.conductor

  return L.divIcon({
    className: 'marcador-seguimiento',
    html: `
      <div class="${opcion.clase}">
        <span class="material-icons">${opcion.icono}</span>
      </div>
    `,
    iconSize: opcion.tamano,
    iconAnchor: opcion.ancla,
    popupAnchor: [0, -30]
  })
}

function calcularDistanciaRecta(origen, destino) {
  const radioTierra = 6371
  const convertirRadianes = valor => valor * Math.PI / 180

  const diferenciaLatitud = convertirRadianes(destino[0] - origen[0])
  const diferenciaLongitud = convertirRadianes(destino[1] - origen[1])

  const latitudOrigen = convertirRadianes(origen[0])
  const latitudDestino = convertirRadianes(destino[0])

  const a = (
    Math.sin(diferenciaLatitud / 2) ** 2
    + Math.cos(latitudOrigen)
      * Math.cos(latitudDestino)
      * Math.sin(diferenciaLongitud / 2) ** 2
  )

  return radioTierra * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))
}

function destruirMapaSeguimiento() {
  if (mapaSeguimiento) {
    mapaSeguimiento.remove()
  }

  mapaSeguimiento = null
  inicializandoMapaSeguimiento = false
  marcadorConductorSeguimiento = null
  marcadorOrigenSeguimiento = null
  marcadorDestinoSeguimiento = null
  lineaRutaSeguimiento = null
  ultimaClaveRutaSeguimiento = null
  ultimaRutaSeguimientoEn = 0

  distanciaSeguimientoKm.value = null
  tiempoSeguimientoMin.value = null
}

function inicializarMapaSeguimiento() {
  if (
    mapaSeguimiento
    || inicializandoMapaSeguimiento
    || !puedeMostrarSeguimiento.value
  ) {
    return
  }

  const contenedor = document.getElementById(
    'mapa-seguimiento-pasajero'
  )

  if (!contenedor) return

  inicializandoMapaSeguimiento = true

  try {
    if (contenedor._leaflet_id) {
      contenedor._leaflet_id = null
    }

    mapaSeguimiento = L.map(
      contenedor,
      { zoomControl: true }
    ).setView(
      [LAT_TRINIDAD, LNG_TRINIDAD],
      14
    )

    L.tileLayer(
      'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
      { attribution: '© OpenStreetMap' }
    ).addTo(mapaSeguimiento)

    setTimeout(() => {
      mapaSeguimiento?.invalidateSize()
    }, 200)
  } finally {
    inicializandoMapaSeguimiento = false
  }
}

function actualizarMarcadorSeguimiento(
  marcadorActual,
  coordenadas,
  tipo,
  textoPopup
) {
  if (!mapaSeguimiento || !coordenadas) {
    return marcadorActual
  }

  if (!marcadorActual) {
    return L.marker(
      coordenadas,
      {
        icon: crearIconoSeguimiento(tipo)
      }
    )
      .addTo(mapaSeguimiento)
      .bindPopup(textoPopup)
  }

  marcadorActual.setLatLng(coordenadas)
  marcadorActual.setPopupContent(textoPopup)
  return marcadorActual
}

function puntosRelevantesSeguimiento() {
  const conductor = coordenadasConductor.value
  const objetivo = estadoNormalizado.value === 'en curso'
    ? obtenerCoordenadasViaje('destino')
    : obtenerCoordenadasViaje('origen')

  return [conductor, objetivo].filter(Boolean)
}

function centrarMapaSeguimiento() {
  if (!mapaSeguimiento) {
    return
  }

  if (lineaRutaSeguimiento) {
    mapaSeguimiento.fitBounds(
      lineaRutaSeguimiento.getBounds(),
      {
        padding: [35, 35]
      }
    )
    return
  }

  const puntos = puntosRelevantesSeguimiento()

  if (puntos.length === 1) {
    mapaSeguimiento.setView(puntos[0], 16)
    return
  }

  if (puntos.length > 1) {
    mapaSeguimiento.fitBounds(
      L.latLngBounds(puntos),
      {
        padding: [35, 35]
      }
    )
  }
}

async function dibujarRutaSeguimiento(conductor, objetivo) {
  if (!mapaSeguimiento || !conductor || !objetivo) {
    return
  }

  const claveRuta = [
    estadoNormalizado.value,
    conductor[0].toFixed(4),
    conductor[1].toFixed(4),
    objetivo[0].toFixed(5),
    objetivo[1].toFixed(5)
  ].join('|')

  if (claveRuta === ultimaClaveRutaSeguimiento && lineaRutaSeguimiento) {
    return
  }

  const ahoraRuta = Date.now()

  if (
    lineaRutaSeguimiento
    && ahoraRuta - ultimaRutaSeguimientoEn < 15000
  ) {
    return
  }

  ultimaClaveRutaSeguimiento = claveRuta
  ultimaRutaSeguimientoEn = ahoraRuta

  try {
    const origenTexto = `${conductor[1]},${conductor[0]}`
    const destinoTexto = `${objetivo[1]},${objetivo[0]}`

    const respuesta = await axios.get(
      'https://router.project-osrm.org/route/v1/driving/'
        + `${origenTexto};${destinoTexto}`,
      {
        params: {
          overview: 'full',
          geometries: 'geojson',
          steps: false
        }
      }
    )

    const ruta = respuesta.data?.routes?.[0]

    if (!ruta) {
      throw new Error('No se encontró una ruta de seguimiento.')
    }

    distanciaSeguimientoKm.value = Number.parseFloat(
      (ruta.distance / 1000).toFixed(2)
    )

    tiempoSeguimientoMin.value = Math.max(
      1,
      Math.round(ruta.duration / 60)
    )

    if (lineaRutaSeguimiento) {
      mapaSeguimiento.removeLayer(lineaRutaSeguimiento)
    }

    lineaRutaSeguimiento = L.geoJSON(
      ruta.geometry,
      {
        style: {
          color: '#1976d2',
          weight: 7,
          opacity: 0.88
        }
      }
    ).addTo(mapaSeguimiento)

    centrarMapaSeguimiento()
  } catch (error) {
    console.error('Error calculando seguimiento:', error)

    const distanciaRecta = calcularDistanciaRecta(
      conductor,
      objetivo
    )

    distanciaSeguimientoKm.value = Number.parseFloat(
      distanciaRecta.toFixed(2)
    )

    tiempoSeguimientoMin.value = Math.max(
      1,
      Math.round((distanciaRecta / 25) * 60)
    )

    if (lineaRutaSeguimiento) {
      mapaSeguimiento.removeLayer(lineaRutaSeguimiento)
    }

    lineaRutaSeguimiento = L.polyline(
      [conductor, objetivo],
      {
        color: '#1976d2',
        weight: 5,
        opacity: 0.65,
        dashArray: '8 8'
      }
    ).addTo(mapaSeguimiento)

    centrarMapaSeguimiento()
  }
}

async function actualizarMapaSeguimiento() {
  if (!puedeMostrarSeguimiento.value) {
    destruirMapaSeguimiento()
    return
  }

  if (!tieneUbicacionConductor.value) {
    destruirMapaSeguimiento()
    return
  }

  if (actualizandoSeguimiento.value) {
    return
  }

  actualizandoSeguimiento.value = true

  try {
    await nextTick()
    inicializarMapaSeguimiento()

    if (!mapaSeguimiento) {
      return
    }

    const conductor = coordenadasConductor.value
    const origen = obtenerCoordenadasViaje('origen')
    const destino = obtenerCoordenadasViaje('destino')

    marcadorConductorSeguimiento = actualizarMarcadorSeguimiento(
      marcadorConductorSeguimiento,
      conductor,
      'conductor',
      nombreConductor.value
        ? `Mototaxista: ${nombreConductor.value}`
        : 'Ubicación del mototaxista'
    )

    marcadorOrigenSeguimiento = actualizarMarcadorSeguimiento(
      marcadorOrigenSeguimiento,
      origen,
      'origen',
      viajeActivo.value?.origen || 'Punto de recogida'
    )

    marcadorDestinoSeguimiento = actualizarMarcadorSeguimiento(
      marcadorDestinoSeguimiento,
      destino,
      'destino',
      viajeActivo.value?.destino || 'Destino del viaje'
    )

    const objetivo = estadoNormalizado.value === 'en curso'
      ? destino
      : origen

    if (conductor && objetivo) {
      await dibujarRutaSeguimiento(conductor, objetivo)
    } else {
      centrarMapaSeguimiento()
    }

    mapaSeguimiento.invalidateSize()
  } finally {
    actualizandoSeguimiento.value = false
  }
}

function crearIconoMarcador(tipo) {
  const esOrigen = tipo === 'origen'

  return L.divIcon({
    className: 'marcador-personalizado',
    html: `
      <div class="pin-mapa ${esOrigen ? 'pin-origen' : 'pin-destino'}">
        <span></span>
      </div>
    `,
    iconSize: [30, 38],
    iconAnchor: [15, 38],
    popupAnchor: [0, -38]
  })
}

function formatearDireccionNominatim(datos) {
  const direccion = datos?.address || {}

  const via = String(
    direccion.road
    || direccion.pedestrian
    || direccion.residential
    || direccion.footway
    || direccion.path
    || ''
  ).trim()

  const numero = String(
    direccion.house_number || ''
  ).trim()

  const zona = String(
    direccion.neighbourhood
    || direccion.suburb
    || direccion.quarter
    || direccion.city_district
    || direccion.borough
    || ''
  ).trim()

  const ciudad = String(
    direccion.city
    || direccion.town
    || direccion.village
    || direccion.municipality
    || 'Trinidad'
  ).trim()

  const calle = [via, numero]
    .filter(Boolean)
    .join(' ')
    .trim()

  const partes = [calle, zona, ciudad]
    .filter(Boolean)
    .filter(
      (valor, indice, lista) =>
        lista.findIndex(
          (otro) =>
            otro.toLocaleLowerCase('es')
            === valor.toLocaleLowerCase('es')
        ) === indice
    )

  if (partes.length >= 2) {
    return partes.join(', ')
  }

  const displayName = String(
    datos?.display_name || ''
  ).trim()

  if (displayName) {
    return displayName
      .split(',')
      .map((parte) => parte.trim())
      .filter(Boolean)
      .slice(0, 3)
      .join(', ')
  }

  return ''
}

async function obtenerDireccion(lat, lng) {
  try {
    const respuesta = await axios.get(
      'https://nominatim.openstreetmap.org/reverse',
      {
        params: {
          lat,
          lon: lng,
          format: 'jsonv2',
          addressdetails: 1,
          zoom: 18
        }
      }
    )

    const direccion = formatearDireccionNominatim(
      respuesta.data
    )

    if (direccion) {
      return direccion
    }
  } catch (error) {
    console.error('Error obteniendo dirección:', error)
  }

  return `Lat. ${lat.toFixed(5)}, Lng. ${lng.toFixed(5)}`
}

function eliminarMarcador(marcador) {
  if (mapa && marcador) {
    mapa.removeLayer(marcador)
  }
}

async function colocarOrigen(lat, lng) {
  origenCoords.value = [lat, lng]
  form.origen = 'Obteniendo dirección...'

  eliminarMarcador(marcadorOrigen)

  marcadorOrigen = L.marker(
    [lat, lng],
    {
      draggable: true,
      icon: crearIconoMarcador('origen')
    }
  ).addTo(mapa)

  marcadorOrigen.on('dragend', async () => {
    const posicion = marcadorOrigen.getLatLng()
    await colocarOrigen(posicion.lat, posicion.lng)
  })

  form.origen = await obtenerDireccion(lat, lng)

  marcadorOrigen
    .bindPopup(form.origen)
    .openPopup()

  if (destinoCoords.value) {
    await obtenerRuta()
  }
}

function nombreResultadoDestino(resultado) {
  const direccion = resultado?.address || {}

  return String(
    resultado?.name
    || direccion?.road
    || direccion?.pedestrian
    || direccion?.neighbourhood
    || direccion?.suburb
    || direccion?.quarter
    || direccion?.amenity
    || direccion?.shop
    || resultado?.display_name?.split(',')?.[0]
    || 'Destino encontrado'
  ).trim()
}

function direccionResultadoDestino(resultado) {
  const direccionCorta = formatearDireccionNominatim(resultado)

  if (direccionCorta) {
    return direccionCorta
  }

  return 'Trinidad, Beni, Bolivia'
}

function limpiarDestinoSeleccionado(limpiarBusqueda = true) {
  eliminarMarcador(marcadorDestino)

  if (mapa && lineaRuta) {
    mapa.removeLayer(lineaRuta)
  }

  marcadorDestino = null
  lineaRuta = null
  destinoCoords.value = null
  distanciaKm.value = 0
  form.destino = ''
  form.precio = TARIFA_CORTA_BS

  resultadosDestino.value = []
  busquedaDestinoRealizada.value = false

  if (limpiarBusqueda) {
    busquedaDestino.value = ''
  }

  if (mapa && origenCoords.value) {
    mapa.setView(origenCoords.value, 16)
  }
}

function alEditarBusquedaDestino(valor) {
  resultadosDestino.value = []
  busquedaDestinoRealizada.value = false

  const texto = String(valor || '').trim()
  const destinoActual = String(form.destino || '').trim()

  if (
    destinoActual
    && texto !== destinoActual
  ) {
    limpiarDestinoSeleccionado(false)
  }
}

async function buscarDestino() {
  const consulta = String(busquedaDestino.value || '').trim()

  if (consulta.length < 3) {
    $q.notify({
      type: 'warning',
      message: 'Escribe al menos 3 caracteres para buscar el destino.'
    })
    return
  }

  buscandoDestino.value = true
  resultadosDestino.value = []
  busquedaDestinoRealizada.value = false

  try {
    const respuesta = await axios.get(
      NOMINATIM_SEARCH_URL,
      {
        params: {
          q: `${consulta}, Trinidad, Beni, Bolivia`,
          format: 'jsonv2',
          addressdetails: 1,
          limit: 5,
          countrycodes: 'bo',
          'accept-language': 'es',
          viewbox: '-65.02,-14.74,-64.80,-14.95'
        }
      }
    )

    const resultados = Array.isArray(respuesta.data)
      ? respuesta.data
      : []

    resultadosDestino.value = resultados
      .map((resultado) => {
        const lat = Number.parseFloat(resultado?.lat)
        const lng = Number.parseFloat(resultado?.lon)

        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
          return null
        }

        const distanciaCentro = calcularDistanciaRecta(
          [LAT_TRINIDAD, LNG_TRINIDAD],
          [lat, lng]
        )

        if (distanciaCentro > RADIO_BUSQUEDA_TRINIDAD_KM) {
          return null
        }

        return {
          id: resultado?.place_id || `${lat}-${lng}`,
          lat,
          lng,
          nombre: nombreResultadoDestino(resultado),
          direccion: direccionResultadoDestino(resultado)
        }
      })
      .filter(Boolean)

    busquedaDestinoRealizada.value = true
  } catch (error) {
    console.error('Error buscando destino:', error)

    busquedaDestinoRealizada.value = true

    $q.notify({
      type: 'warning',
      message: 'No se pudo buscar el destino en este momento. Puedes marcarlo manualmente en el mapa.'
    })
  } finally {
    buscandoDestino.value = false
  }
}

async function seleccionarResultadoDestino(resultado) {
  if (!resultado) {
    return
  }

  resultadosDestino.value = []
  busquedaDestinoRealizada.value = false
  busquedaDestino.value = resultado.direccion

  await colocarDestino(
    resultado.lat,
    resultado.lng,
    resultado.direccion
  )

  if (!origenCoords.value) {
    mapa?.setView(
      [resultado.lat, resultado.lng],
      16
    )
  }
}

async function colocarDestino(lat, lng, direccionConocida = null) {
  destinoCoords.value = [lat, lng]
  form.destino = 'Obteniendo dirección...'

  eliminarMarcador(marcadorDestino)

  marcadorDestino = L.marker(
    [lat, lng],
    {
      draggable: true,
      icon: crearIconoMarcador('destino')
    }
  ).addTo(mapa)

  marcadorDestino.on('dragend', async () => {
    const posicion = marcadorDestino.getLatLng()
    await colocarDestino(posicion.lat, posicion.lng)
  })

  form.destino = direccionConocida
    || await obtenerDireccion(lat, lng)

  busquedaDestino.value = form.destino
  resultadosDestino.value = []
  busquedaDestinoRealizada.value = false

  marcadorDestino
    .bindPopup(form.destino)
    .openPopup()

  if (origenCoords.value) {
    await obtenerRuta()
  }
}

function inicializarMapa() {
  if (
    mapa
    || inicializandoMapa
    || viajeActivo.value
  ) {
    return
  }

  const contenedor = document.getElementById(
    'mapa-viaje-pasajero'
  )

  if (!contenedor) return

  inicializandoMapa = true

  try {
    if (contenedor._leaflet_id) {
      contenedor._leaflet_id = null
    }

    mapa = L.map(contenedor).setView(
      [LAT_TRINIDAD, LNG_TRINIDAD],
      14
    )

    L.tileLayer(
      'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
      { attribution: '© OpenStreetMap' }
    ).addTo(mapa)

    mapa.on('click', async (evento) => {
      const { lat, lng } = evento.latlng

      if (!origenCoords.value) {
        await colocarOrigen(lat, lng)
        return
      }

      if (!destinoCoords.value) {
        await colocarDestino(lat, lng)
        return
      }

      $q.notify({
        type: 'info',
        message: 'Reinicia el mapa para seleccionar una nueva ruta.'
      })
    })

    setTimeout(() => {
      mapa?.invalidateSize()
    }, 200)
  } finally {
    inicializandoMapa = false
  }
}

async function obtenerRuta() {
  if (
    !mapa
    || !origenCoords.value
    || !destinoCoords.value
  ) {
    return
  }

  try {
    const origen = (
      `${origenCoords.value[1]},${origenCoords.value[0]}`
    )

    const destino = (
      `${destinoCoords.value[1]},${destinoCoords.value[0]}`
    )

    const url = (
      'https://router.project-osrm.org/route/v1/driving/'
      + `${origen};${destino}`
    )

    const respuesta = await axios.get(url, {
      params: {
        overview: 'full',
        geometries: 'geojson'
      }
    })

    const ruta = respuesta.data?.routes?.[0]

    if (!ruta) {
      throw new Error('No se encontró una ruta.')
    }

    distanciaKm.value = Number.parseFloat(
      (ruta.distance / 1000).toFixed(2)
    )

    calcularTarifa()

    if (lineaRuta) {
      mapa.removeLayer(lineaRuta)
    }

    lineaRuta = L.geoJSON(
      ruta.geometry,
      {
        style: {
          color: '#1976d2',
          weight: 6,
          opacity: 0.85
        }
      }
    ).addTo(mapa)

    mapa.fitBounds(
      lineaRuta.getBounds(),
      {
        padding: [35, 35]
      }
    )
  } catch (error) {
    console.error('Error calculando ruta:', error)

    distanciaKm.value = 0

    $q.notify({
      type: 'warning',
      message: 'No se pudo calcular la ruta. Vuelve a marcar los puntos.'
    })
  }
}

function limpiarMapa() {
  eliminarMarcador(marcadorOrigen)
  eliminarMarcador(marcadorDestino)

  if (mapa && lineaRuta) {
    mapa.removeLayer(lineaRuta)
  }

  marcadorOrigen = null
  marcadorDestino = null
  lineaRuta = null

  origenCoords.value = null
  destinoCoords.value = null
  distanciaKm.value = 0

  form.origen = ''
  form.destino = ''
  form.precio = TARIFA_CORTA_BS

  busquedaDestino.value = ''
  resultadosDestino.value = []
  busquedaDestinoRealizada.value = false

  mapa?.setView(
    [LAT_TRINIDAD, LNG_TRINIDAD],
    14
  )
}

function usarUbicacionActual() {
  if (!navigator.geolocation) {
    $q.notify({
      type: 'negative',
      message: 'Tu navegador no permite obtener la ubicación.'
    })

    return
  }

  obteniendoUbicacion.value = true

  navigator.geolocation.getCurrentPosition(
    async (posicion) => {
      const lat = posicion.coords.latitude
      const lng = posicion.coords.longitude

      await colocarOrigen(lat, lng)

      mapa?.setView([lat, lng], 16)
      obteniendoUbicacion.value = false
    },
    (error) => {
      console.error('Error de GPS:', error)

      obteniendoUbicacion.value = false

      $q.notify({
        type: 'negative',
        message: 'No se pudo obtener tu ubicación. Revisa el permiso del GPS.'
      })
    },
    {
      enableHighAccuracy: true,
      timeout: 12000,
      maximumAge: 30000
    }
  )
}


function normalizarEstadoSolicitud(solicitud) {
  return String(solicitud?.estado || '')
    .trim()
    .toLowerCase()
}

function obtenerPasajeroIdSolicitud(solicitud) {
  return Number(
    solicitud?.id_pasajero
    ?? solicitud?.pasajero_id
    ?? 0
  )
}

function obtenerDetalleConductorSolicitud(solicitud) {
  const mototaxista = solicitud?.mototaxista || {}
  const persona = mototaxista?.persona || {}
  const nombre = (
    nombreCompletoPersona(persona)
    || mototaxista?.nombre
    || ''
  )

  return {
    nombre,
    sindicato: String(
      mototaxista?.sindicato?.nombre || ''
    ).trim(),
    chaleco: String(
      mototaxista?.nro_chaleco || ''
    ).trim(),
    foto: fotoPersonaUrl(persona),
    iniciales: inicialesNombre(nombre)
  }
}

function firmaEstadoSolicitud(solicitud) {
  return [
    Number(solicitud?.id || 0),
    normalizarEstadoSolicitud(solicitud),
    Number(solicitud?.mototaxista_id || 0)
  ].join('|')
}

function registrarFirmaSolicitud(solicitud) {
  const firma = firmaEstadoSolicitud(solicitud)

  if (!firma.startsWith('0|')) {
    firmasNotificadas.add(firma)
  }
}

async function prepararAudioNotificaciones() {
  try {
    const AudioContexto = (
      window.AudioContext
      || window.webkitAudioContext
    )

    if (!AudioContexto) {
      return false
    }

    if (!audioContexto) {
      audioContexto = new AudioContexto()
    }

    if (audioContexto.state === 'suspended') {
      await audioContexto.resume()
    }

    return audioContexto.state === 'running'
  } catch (error) {
    console.warn(
      'No se pudo habilitar el audio del pasajero:',
      error
    )

    return false
  }
}

async function reproducirSonidoEstado(estado) {
  const audioDisponible = await prepararAudioNotificaciones()

  if (!audioDisponible || !audioContexto) {
    return
  }

  const tonosPorEstado = {
    'buscando conductor': [659, 784],
    aceptado: [659, 784, 988],
    'llegó': [880, 1046, 1318],
    'en curso': [523, 659, 784],
    finalizado: [784, 988, 1175],
    cancelado: [392, 330],
    expirado: [392, 330]
  }

  const tonos = tonosPorEstado[estado] || [659, 784]

  try {
    const inicio = audioContexto.currentTime

    tonos.forEach((frecuencia, indice) => {
      const oscilador = audioContexto.createOscillator()
      const ganancia = audioContexto.createGain()
      const empieza = inicio + (indice * 0.22)
      const termina = empieza + 0.16

      oscilador.type = estado === 'cancelado'
        || estado === 'expirado'
        ? 'triangle'
        : 'sine'

      oscilador.frequency.setValueAtTime(
        frecuencia,
        empieza
      )

      ganancia.gain.setValueAtTime(
        0.0001,
        empieza
      )

      ganancia.gain.exponentialRampToValueAtTime(
        0.28,
        empieza + 0.025
      )

      ganancia.gain.exponentialRampToValueAtTime(
        0.0001,
        termina
      )

      oscilador.connect(ganancia)
      ganancia.connect(audioContexto.destination)
      oscilador.start(empieza)
      oscilador.stop(termina)
    })

    if (
      typeof navigator.vibrate === 'function'
      && ['llegó', 'finalizado'].includes(estado)
    ) {
      navigator.vibrate([300, 120, 300])
    }
  } catch (error) {
    console.warn(
      'No se pudo reproducir el sonido del viaje:',
      error
    )
  }
}

function construirNotificacionEstado(
  solicitud,
  tipo = 'estado_actualizado'
) {
  const estado = normalizarEstadoSolicitud(solicitud)
  const detalleConductor = obtenerDetalleConductorSolicitud(solicitud)
  const conductor = detalleConductor.nombre
  const solicitudId = Number(solicitud?.id || 0)
  const datosConductorNotificacion = {
    conductor,
    sindicato: detalleConductor.sindicato,
    chaleco: detalleConductor.chaleco,
    foto: detalleConductor.foto,
    iniciales: detalleConductor.iniciales
  }

  if (
    estado === 'buscando conductor'
    || estado === 'pendiente'
  ) {
    if (solicitud?.mototaxista_id) {
      return {
        solicitudId,
        titulo: tipo === 'conductor_reasignado'
          ? 'Nuevo conductor asignado'
          : 'Conductor asignado',
        mensaje: conductor
          ? `${conductor} recibió tu solicitud y debe confirmarla.`
          : 'Un mototaxista recibió tu solicitud y debe confirmarla.',
        ...datosConductorNotificacion,
        icono: 'person_search',
        color: 'orange-8',
        destacada: true
      }
    }

    return {
      solicitudId,
      titulo: tipo === 'conductor_rechazo'
        ? 'Buscando otro conductor'
        : 'Buscando conductor cercano',
      mensaje: tipo === 'conductor_rechazo'
        ? 'El conductor anterior no pudo atenderte. MOTRIX continúa buscando otro mototaxista.'
        : 'Tu solicitud fue enviada. Estamos buscando al conductor disponible más cercano.',
      conductor: '',
      sindicato: '',
      chaleco: '',
      foto: '',
      iniciales: '',
      icono: 'travel_explore',
      color: 'orange-8',
      destacada: false
    }
  }

  if (estado === 'aceptado') {
    return {
      solicitudId,
      titulo: 'Conductor en camino',
      mensaje: conductor
        ? `${conductor} aceptó el viaje y se dirige a recogerte.`
        : 'El mototaxista aceptó el viaje y se dirige a recogerte.',
      ...datosConductorNotificacion,
      icono: 'two_wheeler',
      color: 'primary',
      destacada: true
    }
  }

  if (estado === 'llegó') {
    return {
      solicitudId,
      titulo: 'Tu mototaxi llegó',
      mensaje: conductor
        ? `${conductor} ya está en el punto de recogida.`
        : 'El mototaxista ya está en el punto de recogida.',
      ...datosConductorNotificacion,
      icono: 'person_pin_circle',
      color: 'positive',
      destacada: true
    }
  }

  if (estado === 'en curso') {
    return {
      solicitudId,
      titulo: 'Viaje iniciado',
      mensaje: 'Tu viaje está en curso. Puedes seguir el recorrido desde el mapa.',
      ...datosConductorNotificacion,
      icono: 'navigation',
      color: 'indigo-9',
      destacada: true
    }
  }

  if (estado === 'finalizado') {
    return {
      solicitudId,
      titulo: 'Viaje finalizado',
      mensaje: 'El viaje terminó correctamente. Revisa el resumen y califica la atención.',
      ...datosConductorNotificacion,
      icono: 'check_circle',
      color: 'positive',
      destacada: true
    }
  }

  if (estado === 'cancelado') {
    return {
      solicitudId,
      titulo: 'Viaje cancelado',
      mensaje: solicitud?.motivo_cancelacion
        || 'El viaje fue cancelado.',
      ...datosConductorNotificacion,
      icono: 'cancel',
      color: 'negative',
      destacada: true
    }
  }

  if (estado === 'expirado') {
    return {
      solicitudId,
      titulo: 'Solicitud expirada',
      mensaje: 'No se confirmó un conductor dentro del tiempo disponible. Puedes realizar una nueva solicitud.',
      conductor: '',
      sindicato: '',
      chaleco: '',
      foto: '',
      iniciales: '',
      icono: 'timer_off',
      color: 'negative',
      destacada: true
    }
  }

  return null
}

async function mostrarNotificacionEstado(
  solicitud,
  tipo = 'estado_actualizado'
) {
  if (!solicitud?.id) {
    return
  }

  const firma = firmaEstadoSolicitud(solicitud)

  if (firmasNotificadas.has(firma)) {
    return
  }

  firmasNotificadas.add(firma)

  if (!notificacionesHabilitadas) {
    return
  }

  const notificacion = construirNotificacionEstado(
    solicitud,
    tipo
  )

  if (!notificacion) {
    return
  }

  notificacionFotoError.value = false
  notificacionActual.value = notificacion

  $q.notify({
    color: notificacion.color,
    textColor: 'white',
    icon: notificacion.icono,
    message: notificacion.titulo,
    caption: notificacion.mensaje,
    position: 'top',
    timeout: 7000,
    actions: notificacion.destacada
      ? [
          {
            label: 'VER',
            color: 'white',
            handler: () => {
              dialogNotificacion.value = true
            }
          }
        ]
      : []
  })

  if (notificacion.destacada) {
    dialogNotificacion.value = true
  }

  await reproducirSonidoEstado(
    normalizarEstadoSolicitud(solicitud)
  )
}

async function aplicarSolicitudTiempoReal(
  solicitud,
  tipo = 'estado_actualizado',
  notificar = true
) {
  if (
    !solicitud?.id
    || obtenerPasajeroIdSolicitud(solicitud) !== PASAJERO_ID
  ) {
    return
  }

  const estado = normalizarEstadoSolicitud(solicitud)
  ultimaSincronizacionViaje = Date.now()

  if (estado === 'finalizado') {
    viajeActivo.value = null
    viajeFinalizado.value = solicitud
    destruirMapaSeguimiento()
  } else if (
    estado === 'cancelado'
    || estado === 'expirado'
  ) {
    viajeActivo.value = null
    viajeFinalizado.value = null
    destruirMapaSeguimiento()

    await nextTick()
    inicializarMapa()
  } else {
    viajeActivo.value = solicitud
    viajeFinalizado.value = null

    await nextTick()
    await actualizarMapaSeguimiento()
  }

  if (notificar) {
    await mostrarNotificacionEstado(
      solicitud,
      tipo
    )
  } else {
    registrarFirmaSolicitud(solicitud)
  }
}

async function procesarEventoSolicitud(
  solicitud,
  tipo = 'estado_actualizado'
) {
  if (
    !solicitud?.id
    || obtenerPasajeroIdSolicitud(solicitud) !== PASAJERO_ID
  ) {
    return
  }

  /*
   * Los eventos de Echo se usan solo como señal de actualización.
   * La solicitud vigente siempre se vuelve a consultar al backend para
   * evitar que un evento atrasado reemplace un viaje ya aceptado por
   * otro conductor.
   */
  const notificar = tipo !== 'cancelado_por_pasajero'

  await cargarViajeActivo(true, notificar, tipo)
}


function obtenerEndpointAutorizacionRealtime() {
  const baseConfigurada = String(
    api?.defaults?.baseURL || ''
  ).trim().replace(/\/+$/, '')

  if (/^https?:\/\//i.test(baseConfigurada)) {
    return `${baseConfigurada}/broadcasting/auth`
  }

  return BROADCAST_AUTH_URL
}

function obtenerCabecerasAutorizacionRealtime() {
  const token = localStorage.getItem('motrix_token') || ''

  return {
    Accept: 'application/json',
    ...(token
      ? { Authorization: `Bearer ${token}` }
      : {})
  }
}

function normalizarIncidenciaActiva() {
  incidenciaActiva.value = incidenciasViaje.value.find((item) => {
    return ['Reportado', 'Recibido', 'En atención'].includes(
      String(item?.estado || '')
    )
  }) || null
}

function agregarOActualizarIncidencia(incidencia) {
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

async function cargarIncidenciasViaje(silencioso = false) {
  if (!viajeActivo.value?.id) {
    incidenciasViaje.value = []
    incidenciaActiva.value = null
    return
  }

  try {
    const respuesta = await api.get(
      `/pasajero/solicitudes/${viajeActivo.value.id}/incidencias`,
      { params: { _t: Date.now() } }
    )

    incidenciasViaje.value = Array.isArray(respuesta.data?.incidencias)
      ? respuesta.data.incidencias
      : []

    incidenciaActiva.value = respuesta.data?.incidencia_activa || null
  } catch (error) {
    if (!silencioso) {
      $q.notify({
        type: 'negative',
        message: extraerMensajeError(error)
      })
    }
  }
}

async function obtenerUbicacionSos() {
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

        const latitudOrigen = Number(viajeActivo.value?.latitud_origen)
        const longitudOrigen = Number(viajeActivo.value?.longitud_origen)

        if (
          Number.isFinite(latitudOrigen)
          && Number.isFinite(longitudOrigen)
        ) {
          sosUbicacion.value = {
            latitud: latitudOrigen,
            longitud: longitudOrigen,
            precision_metros: null
          }
          sosEstadoGps.value = 'Se utilizará el punto de origen del viaje como referencia.'
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

async function abrirDialogoSos() {
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

async function enviarAlertaSos() {
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

    const respuesta = await api.post(
      `/pasajero/solicitudes/${viajeActivo.value.id}/incidencias`,
      payload
    )

    agregarOActualizarIncidencia(respuesta.data?.incidencia)
    dialogSos.value = false

    $q.notify({
      color: 'negative',
      textColor: 'white',
      icon: 'sos',
      message: respuesta.data?.mensaje || 'La alerta fue enviada a la central MOTRIX.',
      caption: respuesta.data?.advertencia,
      position: 'top',
      timeout: 10000
    })
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: extraerMensajeError(error)
    })
  } finally {
    sosEnviando.value = false
  }
}

async function procesarIncidenciaTiempoReal(data, actualizada = false) {
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

function salirCanalIncidencias() {
  if (!echoInstance || !canalIncidenciasSolicitudId) return

  echoInstance.leave(`viajes.incidencias.${canalIncidenciasSolicitudId}`)
  canalIncidenciasSolicitudId = null
}

async function sincronizarCanalIncidencias() {
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

function inicializarWebsocketPasajero() {
  if (echoInstance) {
    return
  }

  try {
    echoInstance = new Echo({
      ...echoOptions(),
      authEndpoint: obtenerEndpointAutorizacionRealtime(),
      auth: {
        headers: obtenerCabecerasAutorizacionRealtime()
      }
    })

    const conexion = echoInstance
      .connector
      ?.pusher
      ?.connection

    conexion?.bind('connected', () => {
      websocketConectado.value = true
      cargarViajeActivo(true).catch(() => {})
    })

    conexion?.bind('disconnected', () => {
      websocketConectado.value = false
    })

    conexion?.bind('error', (error) => {
      console.error(
        'Error de conexión del pasajero con Reverb:',
        error
      )

      websocketConectado.value = false
    })

    echoInstance
      .private(`pasajero.${PASAJERO_ID}.solicitudes`)
      .listen('.SolicitudCreada', (data) => {
        procesarEventoSolicitud(
          data?.solicitud,
          'solicitud_creada'
        ).catch((error) => {
          console.error(
            'Error procesando la solicitud creada:',
            error
          )
        })
      })
      .listen('.SolicitudActualizada', (data) => {
        procesarEventoSolicitud(
          data?.solicitud,
          data?.tipo || 'estado_actualizado'
        ).catch((error) => {
          console.error(
            'Error procesando el estado del viaje:',
            error
          )
        })
      })
  } catch (error) {
    console.error(
      'Error inicializando Laravel Echo para pasajero:',
      error
    )

    websocketConectado.value = false
  }
}

function desconectarWebsocketPasajero() {
  if (!echoInstance) {
    return
  }

  salirCanalIncidencias()
  echoInstance.leave(`pasajero.${PASAJERO_ID}.solicitudes`)
  echoInstance.disconnect()
  echoInstance = null
  websocketConectado.value = false
}

function claveViajeOmitido(id) {
  return `motrix_calificacion_omitida_${id}`
}

async function cargarViajeFinalizado() {
  try {
    const respuesta = await api.get(
      '/pasajero/ultimo-viaje-finalizado'
    )

    const solicitud = respuesta.data?.solicitud || null

    if (
      solicitud
      && localStorage.getItem(
        claveViajeOmitido(solicitud.id)
      ) === '1'
    ) {
      viajeFinalizado.value = null
      return
    }

    viajeFinalizado.value = solicitud

    if (solicitud) {
      await mostrarNotificacionEstado(
        solicitud,
        'consulta'
      )

      calificacion.value = 0
      comentarioCalificacion.value = ''
    }
  } catch (error) {
    console.error(
      'Error consultando viaje finalizado:',
      error
    )

    viajeFinalizado.value = null
  }
}

async function enviarCalificacion() {
  if (
    !viajeFinalizado.value?.id
    || calificacion.value < 1
  ) {
    return
  }

  enviandoCalificacion.value = true

  try {
    const respuesta = await api.post(
      `/pasajero/solicitudes/${
        viajeFinalizado.value.id
      }/calificar`,
      {
        calificacion: Number(calificacion.value),
        comentario_calificacion:
          comentarioCalificacion.value.trim() || null
      }
    )

    const promedio = Number.parseFloat(
      respuesta.data?.promedio_mototaxista
    )

    localStorage.removeItem(
      claveViajeOmitido(viajeFinalizado.value.id)
    )

    viajeFinalizado.value = null
    calificacion.value = 0
    comentarioCalificacion.value = ''

    $q.notify({
      type: 'positive',
      icon: 'star',
      message: Number.isFinite(promedio)
        ? `Calificación registrada. Promedio del conductor: ${
          promedio.toFixed(2)
        }/5.`
        : 'Calificación registrada correctamente.'
    })

    await nextTick()
    inicializarMapa()
  } catch (error) {
    console.error(
      'Error enviando calificación:',
      error
    )

    $q.notify({
      type: 'negative',
      message: extraerMensajeError(error)
    })
  } finally {
    enviandoCalificacion.value = false
  }
}

async function omitirCalificacion() {
  if (!viajeFinalizado.value?.id) {
    return
  }

  localStorage.setItem(
    claveViajeOmitido(viajeFinalizado.value.id),
    '1'
  )

  viajeFinalizado.value = null
  calificacion.value = 0
  comentarioCalificacion.value = ''

  await nextTick()
  inicializarMapa()
}

async function cargarViajeActivo(
  silencioso = false,
  notificar = true,
  tipoNotificacion = 'consulta'
) {
  if (!validarSesion() || cargaViajeEnCurso) {
    return
  }

  cargaViajeEnCurso = true
  cargandoViaje.value = !silencioso
  const solicitudAnterior = viajeActivo.value

  try {
    const respuesta = await api.get(
      '/pasajero/viaje-activo'
    )

    const solicitudActual = (
      respuesta.data?.solicitud || null
    )

    viajeActivo.value = solicitudActual

    if (solicitudActual) {
      viajeFinalizado.value = null

      if (notificar) {
        await mostrarNotificacionEstado(
          solicitudActual,
          tipoNotificacion
        )
      } else {
        registrarFirmaSolicitud(solicitudActual)
      }

      await nextTick()
      await actualizarMapaSeguimiento()
    } else {
      destruirMapaSeguimiento()

      if (solicitudAnterior?.id) {
        try {
          const respuestaEstado = await api.get(
            `/pasajero/solicitudes/${solicitudAnterior.id}`
          )

          const solicitudCerrada = respuestaEstado.data

          if (
            ['cancelado', 'expirado', 'finalizado']
              .includes(
                normalizarEstadoSolicitud(
                  solicitudCerrada
                )
              )
          ) {
            await aplicarSolicitudTiempoReal(
              solicitudCerrada,
              tipoNotificacion,
              notificar
            )
          }
        } catch (errorEstado) {
          console.warn(
            'No se pudo consultar el estado final del viaje:',
            errorEstado
          )
        }
      }

      if (!viajeFinalizado.value) {
        await cargarViajeFinalizado()
      }

      if (!viajeFinalizado.value) {
        await nextTick()
        inicializarMapa()
      }
    }
  } catch (error) {
    console.error('Error consultando viaje activo:', error)

    if (!silencioso && error?.response?.status !== 401) {
      $q.notify({
        type: 'negative',
        message: extraerMensajeError(error)
      })
    }
  } finally {
    ultimaSincronizacionViaje = Date.now()
    cargaViajeEnCurso = false
    cargandoViaje.value = false
  }
}

async function enviarSolicitud() {
  if (!rutaCompleta.value) {
    $q.notify({
      type: 'negative',
      message: 'Selecciona el origen y un destino válido antes de solicitar.'
    })

    return
  }

  enviando.value = true

  const payload = {
    origen: form.origen.trim(),
    latitud_origen: origenCoords.value[0],
    longitud_origen: origenCoords.value[1],

    destino: form.destino.trim(),
    latitud_destino: destinoCoords.value[0],
    longitud_destino: destinoCoords.value[1],

    fecha: form.fecha,
    precio: Number.parseFloat(form.precio),
    distancia_km: Number.parseFloat(distanciaKm.value),
    metodo_pago: form.metodo_pago
  }

  try {
    const respuesta = await api.post(
      '/pasajero/solicitudes',
      payload
    )

    viajeActivo.value = respuesta.data?.solicitud || null
    viajeFinalizado.value = null
    destruirMapaSeguimiento()

    if (viajeActivo.value) {
      await mostrarNotificacionEstado(
        viajeActivo.value,
        'solicitud_creada'
      )
    }

    $q.notify({
      type: 'positive',
      message: (
        respuesta.data?.mensaje
        || 'Solicitud creada correctamente.'
      )
    })

    if (mapa) {
      mapa.remove()
      mapa = null
    }
  } catch (error) {
    console.error('Error creando solicitud:', error)

    if (
      error?.response?.status === 409
      && error?.response?.data?.solicitud
    ) {
      viajeActivo.value = error.response.data.solicitud
    }

    $q.notify({
      type: 'negative',
      message: extraerMensajeError(error)
    })
  } finally {
    enviando.value = false
  }
}

function confirmarCancelacion() {
  $q.dialog({
    title: 'Cancelar solicitud',
    message: '¿Confirmas que deseas cancelar esta solicitud?',
    prompt: {
      model: '',
      type: 'text',
      label: 'Motivo de cancelación (opcional)'
    },
    cancel: {
      label: 'Volver',
      flat: true
    },
    ok: {
      label: 'Cancelar solicitud',
      color: 'negative'
    },
    persistent: true
  }).onOk(async (motivo) => {
    await cancelarSolicitud(motivo)
  })
}

async function cancelarSolicitud(motivo) {
  if (!viajeActivo.value?.id) {
    return
  }

  cancelando.value = true

  try {
    const respuesta = await api.post(
      `/pasajero/solicitudes/${viajeActivo.value.id}/cancelar`,
      {
        motivo_cancelacion: motivo || 'Cancelado por el pasajero'
      }
    )

    viajeActivo.value = null
    destruirMapaSeguimiento()

    $q.notify({
      type: 'positive',
      message: (
        respuesta.data?.mensaje
        || 'Solicitud cancelada correctamente.'
      )
    })

    await nextTick()
    inicializarMapa()
  } catch (error) {
    console.error('Error cancelando solicitud:', error)

    $q.notify({
      type: 'negative',
      message: extraerMensajeError(error)
    })
  } finally {
    cancelando.value = false
  }
}

async function compartirViaje() {
  if (!viajeActivo.value?.id || compartiendoViaje.value) return

  compartiendoViaje.value = true

  try {
    const respuesta = await api.post(
      `/pasajero/solicitudes/${viajeActivo.value.id}/compartir`
    )

    const url = String(respuesta.data?.url || '').trim()
    if (!url) {
      throw new Error('El servidor no devolvió el enlace de seguimiento.')
    }

    if (navigator.share) {
      try {
        await navigator.share({
          title: 'Seguimiento de viaje MOTRIX',
          text: 'Puedes seguir temporalmente mi viaje MOTRIX desde este enlace:',
          url
        })
        return
      } catch (error) {
        if (error?.name === 'AbortError') return
      }
    }

    await navigator.clipboard?.writeText?.(url)

    $q.dialog({
      title: 'Seguimiento compartido',
      message: `Enlace temporal: ${url}`,
      ok: { label: 'Entendido', color: 'green-8' }
    })

    $q.notify({
      type: 'positive',
      icon: 'link',
      message: 'Enlace de seguimiento creado. También se copió cuando el dispositivo lo permitió.'
    })
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: extraerMensajeError(error)
    })
  } finally {
    compartiendoViaje.value = false
  }
}

function volver() {
  router.push('/pasajero')
}


watch(
  () => [
    viajeActivo.value?.id || null,
    viajeActivo.value?.mototaxista_id || null
  ],
  async () => {
    await sincronizarCanalIncidencias()

    if (!viajeActivo.value?.id) {
      dialogSos.value = false
    }
  }
)

onMounted(async () => {
  if (!validarSesion()) {
    return
  }

  window.addEventListener(
    'pointerdown',
    prepararAudioNotificaciones,
    { once: true }
  )

  inicializarWebsocketPasajero()

  await cargarViajeActivo()
  await sincronizarCanalIncidencias()

  if (viajeActivo.value) {
    registrarFirmaSolicitud(viajeActivo.value)
  }

  if (viajeFinalizado.value) {
    registrarFirmaSolicitud(viajeFinalizado.value)
  }

  notificacionesHabilitadas = true
  cargandoInicial.value = false

  if (!viajeActivo.value && !viajeFinalizado.value) {
    await nextTick()
    inicializarMapa()
  }

  intervaloActualizacion = window.setInterval(() => {
    const espera = viajeActivo.value?.id
      ? 5000
      : (websocketConectado.value ? 30000 : 10000)

    if (
      Date.now() - ultimaSincronizacionViaje >= espera
    ) {
      cargarViajeActivo(true).catch(() => {})
    }
  }, 1000)

  intervaloIncidencias = window.setInterval(() => {
    if (viajeActivo.value?.id) {
      cargarIncidenciasViaje(true).catch(() => {})
    }
  }, 15000)
})

onBeforeUnmount(() => {
  desconectarWebsocketPasajero()
  dialogNotificacion.value = false

  if (
    audioContexto
    && audioContexto.state !== 'closed'
  ) {
    audioContexto.close().catch(() => {})
  }

  audioContexto = null

  if (intervaloActualizacion) {
    window.clearInterval(intervaloActualizacion)
  }

  if (intervaloIncidencias) {
    window.clearInterval(intervaloIncidencias)
  }

  if (mapa) {
    mapa.remove()
    mapa = null
  }

  inicializandoMapa = false
  cargaViajeEnCurso = false
  destruirMapaSeguimiento()
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

.solicitud-card {
  border-radius: 16px;
  overflow: hidden;
}

.notificacion-viaje-card {
  width: min(92vw, 520px);
  border-radius: 18px;
  overflow: hidden;
}

.destino-resultados {
  max-height: 250px;
  overflow-y: auto;
  border-radius: 12px;
}

.destino-resultado {
  min-height: 66px;
}

.map-wrapper {
  position: relative;
  width: 100%;
  height: 500px;
  overflow: hidden;
  border: 1px solid #cccccc;
  border-radius: 14px;
  background: #eeeeee;
}

.mapa-viaje {
  width: 100%;
  height: 100%;
  z-index: 1;
}

.map-actions {
  position: absolute;
  top: 12px;
  right: 12px;
  z-index: 500;
}

.tarifa-card {
  border-left: 5px solid var(--q-positive);
  border-radius: 12px;
}

.route-item {
  display: grid;
  grid-template-columns: 28px minmax(0, 1fr);
  gap: 10px;
  align-items: start;
}

.route-connector {
  width: 2px;
  height: 30px;
  margin: 3px 0 3px 10px;
  background: #bdbdbd;
}

.leyenda {
  width: 13px;
  height: 13px;
  margin-right: 6px;
  border-radius: 50%;
}

.leyenda-origen {
  background: #21ba45;
}

.leyenda-destino {
  background: #c10015;
}

.leyenda-ruta {
  width: 24px;
  height: 4px;
  margin-right: 6px;
  border-radius: 3px;
  background: #1976d2;
}

:deep(.marcador-personalizado) {
  background: transparent;
  border: none;
}

:deep(.pin-mapa) {
  position: relative;
  width: 28px;
  height: 28px;
  transform: rotate(45deg);
  border: 3px solid white;
  border-radius: 50% 50% 50% 0;
  box-shadow: 0 2px 7px rgba(0, 0, 0, 0.35);
}

:deep(.pin-mapa span) {
  position: absolute;
  top: 7px;
  left: 7px;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: white;
}

:deep(.pin-origen) {
  background: #21ba45;
}

:deep(.pin-destino) {
  background: #c10015;
}

.resumen-final-card {
  border-left: 5px solid var(--q-positive);
  border-radius: 14px;
}

.calificacion-card {
  border-left: 5px solid #ffb300;
  border-radius: 14px;
}

.seguimiento-card {
  overflow: hidden;
  border-left: 5px solid var(--q-primary);
  border-radius: 14px;
}

.tracking-info-box {
  display: flex;
  align-items: center;
  gap: 10px;
  min-height: 70px;
  padding: 12px;
  background: #f5f7fa;
  border-radius: 12px;
}

.seguimiento-map-wrapper {
  height: 440px;
}

.min-width-zero {
  min-width: 0;
}

.leyenda-conductor {
  background: #1976d2;
}

:deep(.marcador-seguimiento) {
  background: transparent;
  border: none;
}

:deep(.pin-conductor-seguimiento),
:deep(.pin-origen-seguimiento),
:deep(.pin-destino-seguimiento) {
  display: flex;
  align-items: center;
  justify-content: center;
  border: 3px solid white;
  border-radius: 50%;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.38);
}

:deep(.pin-conductor-seguimiento) {
  width: 44px;
  height: 44px;
  color: white;
  background: #1976d2;
}

:deep(.pin-origen-seguimiento),
:deep(.pin-destino-seguimiento) {
  width: 38px;
  height: 38px;
  color: white;
}

:deep(.pin-origen-seguimiento) {
  background: #21ba45;
}

:deep(.pin-destino-seguimiento) {
  background: #c10015;
}

:deep(.pin-conductor-seguimiento .material-icons) {
  font-size: 25px;
}

:deep(.pin-origen-seguimiento .material-icons),
:deep(.pin-destino-seguimiento .material-icons) {
  font-size: 23px;
}

@media (max-width: 1023px) {
  .map-wrapper {
    height: 420px;
  }
}

@media (max-width: 599px) {
  .map-wrapper {
    height: 340px;
  }

  .seguimiento-map-wrapper {
    height: 360px;
  }

  .tracking-info-box {
    min-height: 64px;
    padding: 10px;
  }

  .solicitud-card {
    border-radius: 10px;
  }
}


.qr-pago-card {
  border-radius: 16px;
  background: #fafaff;
}

.qr-pago-image {
  display: block;
  width: min(100%, 320px);
  max-height: 360px;
  object-fit: contain;
  margin: 0 auto;
  border-radius: 14px;
  background: #fff;
  border: 1px solid #e3e7ef;
  padding: 10px;
}

</style>