<template>
  <q-page
    :class="$q.screen.lt.sm
      ? 'q-pa-sm bg-grey-2'
      : 'q-pa-lg bg-grey-2'"
  >
    <!-- =========================================================
         ENCABEZADO
    ========================================================== -->
    <div class="row items-center justify-between q-mb-lg q-col-gutter-sm">
      <div class="col-12 col-sm">
        <div
          :class="$q.screen.lt.sm ? 'text-h5' : 'text-h4'"
          class="text-weight-bold text-grey-9"
        >
          Solicitudes de Viajes
        </div>

        <div class="text-subtitle2 text-grey-6">
          Historial y solicitudes en tiempo real de los pasajeros
        </div>
      </div>

      <div class="col-12 col-sm-auto">
        <q-btn
          color="positive"
          icon="add"
          label="Nueva Solicitud"
          class="q-px-md text-bold full-width"
          unelevated
          @click="openDialogForm()"
        />
      </div>
    </div>

    <!-- =========================================================
         TABLA EN PANTALLAS GRANDES / TARJETAS EN PANTALLAS PEQUEÑAS
    ========================================================== -->
    <q-card class="shadow-2 border-radius-md overflow-hidden">
      <q-card-section class="q-pa-none">
        <q-table
          class="motrix-table"
          :rows="solicitudes"
          :columns="columns"
          row-key="id"
          :filter="filter"
          :loading="loading"
          :grid="$q.screen.lt.lg"
          :hide-header="$q.screen.lt.lg"
          :rows-per-page-options="[6, 12, 24, 0]"
          rows-per-page-label="Registros por página"
          no-data-label="No existen solicitudes registradas"
          no-results-label="No se encontraron resultados"
          loading-label="Cargando solicitudes..."
          flat
          binary-state-sort
        >
          <!-- BUSCADOR -->
          <template v-slot:top>
            <div class="row items-center full-width q-col-gutter-sm q-pa-sm">
              <div class="col-12 col-sm">
                <div class="text-subtitle1 text-weight-bold text-grey-8">
                  {{ solicitudes.length }} solicitudes registradas
                </div>
              </div>

              <div class="col-12 col-sm-auto">
                <q-input
                  v-model="filter"
                  outlined
                  dense
                  debounce="300"
                  placeholder="Buscar solicitud..."
                  class="bg-white solicitudes-search"
                  clearable
                >
                  <template v-slot:append>
                    <q-icon name="search" />
                  </template>
                </q-input>
              </div>
            </div>
          </template>

          <!-- PASAJERO EN TABLA -->
          <template v-slot:body-cell-pasajero="props">
            <q-td :props="props">
              <div class="row items-center no-wrap">
                <q-avatar
                  color="positive"
                  text-color="white"
                  size="34px"
                  class="q-mr-sm"
                >
                  {{ getIniciales(getPasajeroNombre(props.row)) }}
                </q-avatar>

                <div>
                  <div class="text-weight-bold text-grey-9">
                    {{ getPasajeroNombre(props.row) }}
                  </div>

                  <div class="text-caption text-grey-6">
                    Pasajero #{{ getPasajeroId(props.row) || '—' }}
                  </div>
                </div>
              </div>
            </q-td>
          </template>

          <!-- CONDUCTOR EN TABLA -->
          <template v-slot:body-cell-mototaxista="props">
            <q-td :props="props">
              <div
                v-if="getMototaxistaNombre(props.row)"
                class="row items-center no-wrap"
              >
                <q-icon
                  name="motorcycle"
                  color="primary"
                  size="sm"
                  class="q-mr-xs"
                />

                <span class="text-bold text-primary">
                  {{ getMototaxistaNombre(props.row) }}
                </span>
              </div>

              <div
                v-else
                class="row items-center no-wrap text-grey-6 italic"
              >
                <q-spinner-dots
                  color="orange-8"
                  size="18px"
                  class="q-mr-xs"
                />

                Buscando conductor...
              </div>
            </q-td>
          </template>

          <!-- PRECIO EN TABLA -->
          <template v-slot:body-cell-precio="props">
            <q-td :props="props">
              <span class="text-weight-bold text-positive">
                {{ formatearPrecio(props.row.precio) }}
              </span>
            </q-td>
          </template>

          <!-- FECHA EN TABLA -->
          <template v-slot:body-cell-fecha="props">
            <q-td :props="props">
              {{ formatearFecha(props.row.fecha) }}
            </q-td>
          </template>

          <!-- ESTADO EN TABLA -->
          <template v-slot:body-cell-estado="props">
            <q-td :props="props" class="text-center">
              <q-chip
                :color="getEstadoColor(props.row.estado)"
                text-color="white"
                icon="sports_motorsports"
                class="text-bold text-uppercase q-px-md"
                square
              >
                {{ props.row.estado || 'Sin estado' }}
              </q-chip>
            </q-td>
          </template>

          <!-- MENÚ DE TRES PUNTOS EN TABLA -->
          <template v-slot:body-cell-actions="props">
            <q-td :props="props" class="text-center">
              <q-btn
                flat
                round
                dense
                icon="more_vert"
                color="grey-8"
                aria-label="Acciones de la solicitud"
              >
                <q-menu
                  auto-close
                  anchor="bottom right"
                  self="top right"
                >
                  <q-list style="min-width: 220px">
                    <q-item
                      clickable
                      @click="abrirHistorialPasajero(props.row)"
                    >
                      <q-item-section avatar>
                        <q-icon
                          name="history"
                          color="positive"
                        />
                      </q-item-section>

                      <q-item-section>
                        Historial del pasajero
                      </q-item-section>
                    </q-item>

                    <q-item
                      v-if="false"
                      clickable
                      @click="abrirConversacion(props.row)"
                    >
                      <q-item-section avatar>
                        <q-icon
                          name="forum"
                          color="primary"
                        />
                      </q-item-section>

                      <q-item-section>
                        Ver conversación
                      </q-item-section>
                    </q-item>

                    <q-item
                      v-if="puedeAsignarManual(props.row)"
                      clickable
                      @click="abrirAsignacionManual(props.row)"
                    >
                      <q-item-section avatar>
                        <q-icon
                          name="assignment_ind"
                          color="orange-9"
                        />
                      </q-item-section>

                      <q-item-section>
                        Asignar conductor
                      </q-item-section>
                    </q-item>

                    <q-separator />

                    <q-item
                      clickable
                      @click="openDialogForm(props.row)"
                    >
                      <q-item-section avatar>
                        <q-icon
                          name="edit"
                          color="primary"
                        />
                      </q-item-section>

                      <q-item-section>
                        Editar solicitud
                      </q-item-section>
                    </q-item>

                    <q-item
                      clickable
                      @click="confirmDelete(props.row)"
                    >
                      <q-item-section avatar>
                        <q-icon
                          name="delete"
                          color="negative"
                        />
                      </q-item-section>

                      <q-item-section class="text-negative">
                        Eliminar solicitud
                      </q-item-section>
                    </q-item>
                  </q-list>
                </q-menu>
              </q-btn>
            </q-td>
          </template>

          <!-- =====================================================
               TARJETAS RESPONSIVAS
          ====================================================== -->
          <template v-slot:item="props">
            <div class="q-pa-sm col-12 col-md-6">
              <q-card
                class="solicitud-mobile-card border-radius-md shadow-1 full-height"
              >
                <!-- CABECERA DE LA TARJETA -->
                <q-card-section class="q-pb-sm">
                  <div class="row items-start no-wrap">
                    <q-avatar
                      color="positive"
                      text-color="white"
                      size="44px"
                      class="q-mr-sm"
                    >
                      {{ getIniciales(getPasajeroNombre(props.row)) }}
                    </q-avatar>

                    <div class="col min-width-zero">
                      <div
                        class="text-subtitle1 text-weight-bold text-grey-9 ellipsis"
                      >
                        {{ getPasajeroNombre(props.row) }}
                      </div>

                      <div class="text-caption text-grey-6">
                        Solicitud #{{ props.row.id }}
                        ·
                        {{ formatearFecha(props.row.fecha) }}
                      </div>
                    </div>

                    <q-btn
                      flat
                      round
                      dense
                      icon="more_vert"
                      color="grey-8"
                    >
                      <q-menu
                        auto-close
                        anchor="bottom right"
                        self="top right"
                      >
                        <q-list style="min-width: 220px">
                          <q-item
                            clickable
                            @click="abrirHistorialPasajero(props.row)"
                          >
                            <q-item-section avatar>
                              <q-icon
                                name="history"
                                color="positive"
                              />
                            </q-item-section>

                            <q-item-section>
                              Historial del pasajero
                            </q-item-section>
                          </q-item>

                          <q-item
                            v-if="false"
                            clickable
                            @click="abrirConversacion(props.row)"
                          >
                            <q-item-section avatar>
                              <q-icon
                                name="forum"
                                color="primary"
                              />
                            </q-item-section>

                            <q-item-section>
                              Ver conversación
                            </q-item-section>
                          </q-item>

                          <q-item
                            v-if="puedeAsignarManual(props.row)"
                            clickable
                            @click="abrirAsignacionManual(props.row)"
                          >
                            <q-item-section avatar>
                              <q-icon
                                name="assignment_ind"
                                color="orange-9"
                              />
                            </q-item-section>

                            <q-item-section>
                              Asignar conductor
                            </q-item-section>
                          </q-item>

                          <q-separator />

                          <q-item
                            clickable
                            @click="openDialogForm(props.row)"
                          >
                            <q-item-section avatar>
                              <q-icon
                                name="edit"
                                color="primary"
                              />
                            </q-item-section>

                            <q-item-section>
                              Editar solicitud
                            </q-item-section>
                          </q-item>

                          <q-item
                            clickable
                            @click="confirmDelete(props.row)"
                          >
                            <q-item-section avatar>
                              <q-icon
                                name="delete"
                                color="negative"
                              />
                            </q-item-section>

                            <q-item-section class="text-negative">
                              Eliminar solicitud
                            </q-item-section>
                          </q-item>
                        </q-list>
                      </q-menu>
                    </q-btn>
                  </div>
                </q-card-section>

                <q-separator />

                <q-card-section class="q-gutter-y-md">
                  <!-- ORIGEN -->
                  <div class="route-line">
                    <q-icon
                      name="radio_button_checked"
                      color="positive"
                      size="18px"
                    />

                    <div class="min-width-zero">
                      <div class="text-caption text-grey-6">
                        Origen
                      </div>

                      <div class="text-body2 text-weight-medium text-wrap">
                        {{ props.row.origen || 'Sin origen registrado' }}
                      </div>
                    </div>
                  </div>

                  <!-- DESTINO -->
                  <div class="route-line">
                    <q-icon
                      name="location_on"
                      color="negative"
                      size="20px"
                    />

                    <div class="min-width-zero">
                      <div class="text-caption text-grey-6">
                        Destino
                      </div>

                      <div class="text-body2 text-weight-medium text-wrap">
                        {{ props.row.destino || 'Sin destino registrado' }}
                      </div>
                    </div>
                  </div>

                  <!-- CONDUCTOR -->
                  <div class="route-line">
                    <q-icon
                      name="motorcycle"
                      :color="getMototaxistaNombre(props.row)
                        ? 'primary'
                        : 'orange-8'"
                      size="20px"
                    />

                    <div class="min-width-zero">
                      <div class="text-caption text-grey-6">
                        Conductor
                      </div>

                      <div
                        v-if="getMototaxistaNombre(props.row)"
                        class="text-body2 text-weight-bold text-primary"
                      >
                        {{ getMototaxistaNombre(props.row) }}
                      </div>

                      <div
                        v-else
                        class="row items-center text-grey-6 italic"
                      >
                        <q-spinner-dots
                          color="orange-8"
                          size="18px"
                          class="q-mr-xs"
                        />

                        Buscando conductor...
                      </div>
                    </div>
                  </div>

                  <!-- ESTADO Y PRECIO -->
                  <div class="row items-center justify-between q-pt-sm">
                    <q-chip
                      :color="getEstadoColor(props.row.estado)"
                      text-color="white"
                      dense
                      class="text-weight-bold text-uppercase"
                    >
                      {{ props.row.estado || 'Sin estado' }}
                    </q-chip>

                    <div class="text-h6 text-weight-bold text-positive">
                      {{ formatearPrecio(props.row.precio) }}
                    </div>
                  </div>
                </q-card-section>
              </q-card>
            </div>
          </template>
        </q-table>
      </q-card-section>
    </q-card>

    <!-- =========================================================
         MODAL CREAR / EDITAR SOLICITUD
    ========================================================== -->
    <q-dialog
      v-model="dialogOpen"
      persistent
      @show="onModalShow"
      @hide="onModalHide"
    >
      <q-card class="border-radius-md solicitud-dialog">
        <q-card-section
          class="bg-positive text-white row items-center"
        >
          <q-icon
            name="map"
            size="sm"
            class="q-mr-sm"
          />

          <div class="text-h6 text-bold">
            {{
              isEditing
                ? 'Modificar Solicitud'
                : 'Crear Solicitud de Viaje'
            }}
          </div>

          <q-space />

          <q-btn
            icon="close"
            flat
            round
            dense
            v-close-popup
          />
        </q-card-section>

        <q-form @submit.prevent="saveSolicitud">
          <q-card-section class="q-pa-md">
            <div class="row q-col-gutter-md">
              <!-- CAMPOS DEL FORMULARIO -->
              <div class="col-12 col-md-5 q-gutter-y-md">
                <q-select
                  v-model="form.id_pasajero"
                  :options="pasajerosOptions"
                  outlined
                  dense
                  label="Pasajero que solicita *"
                  emit-value
                  map-options
                  option-value="id"
                  option-label="nombre_completo"
                  :rules="[
                    val => !!val || 'El pasajero es obligatorio'
                  ]"
                />

                <q-input
                  v-model="form.origen"
                  outlined
                  dense
                  label="Punto de Origen *"
                  placeholder="Haz clic en el mapa o escribe una referencia"
                  :rules="[
                    val => !!val || 'El origen es obligatorio'
                  ]"
                >
                  <template v-slot:prepend>
                    <q-icon
                      name="my_location"
                      color="green"
                    />
                  </template>
                </q-input>

                <q-input
                  v-model="form.destino"
                  outlined
                  dense
                  label="Punto de Destino *"
                  placeholder="Haz clic en el mapa para marcar destino"
                  :rules="[
                    val => !!val || 'El destino es obligatorio'
                  ]"
                >
                  <template v-slot:prepend>
                    <q-icon
                      name="place"
                      color="red"
                    />
                  </template>
                </q-input>

                <div class="row q-col-gutter-sm">
                  <div class="col-12 col-sm-6">
                    <q-input
                      v-model="form.fecha"
                      outlined
                      dense
                      type="date"
                      label="Fecha *"
                      stack-label
                    />
                  </div>

                  <div class="col-12 col-sm-6">
                    <q-select
                      v-model="form.estado"
                      :options="estadosOptions"
                      outlined
                      dense
                      label="Estado *"
                    />
                  </div>
                </div>

                <q-input
                  v-model="form.precio"
                  outlined
                  dense
                  label="Tarifa de Viaje *"
                  prefix="Bs."
                  readonly
                  disable
                  bg-color="grey-2"
                  hint="Calculado automáticamente por distancia"
                >
                  <template v-slot:append>
                    <q-icon
                      name="speed"
                      color="primary"
                    >
                      <q-tooltip>
                        Ruta de {{ distanciaKm }} km.
                        Tarifas Trinidad:
                        <br>
                        - Corto (≤1.2 km): Bs. 5
                        <br>
                        - Medio (≤2.8 km): Bs. 8
                        <br>
                        - Largo (>2.8 km): Bs. 10
                        <br>
                        - Nocturno (10 PM - 6 AM): tarifa fija Bs. 15
                      </q-tooltip>
                    </q-icon>
                  </template>
                </q-input>

                <div
                  v-if="distanciaKm > 0"
                  class="text-caption text-grey-7"
                >
                  Distancia estimada por mapa:

                  <span class="text-bold text-primary">
                    {{ distanciaKm }} km
                  </span>
                </div>
              </div>

              <!-- MAPA -->
              <div class="col-12 col-md-7">
                <div
                  class="map-container border-radius-sm overflow-hidden"
                >
                  <div
                    id="mapa-solicitud"
                    class="full-width full-height"
                  />

                  <q-btn
                    round
                    color="red"
                    icon="refresh"
                    size="sm"
                    class="absolute-top-right q-ma-sm"
                    style="z-index: 1000"
                    @click="limpiarMapa"
                  >
                    <q-tooltip>
                      Reiniciar marcadores
                    </q-tooltip>
                  </q-btn>
                </div>

                <div
                  class="text-caption text-grey-6 q-mt-xs text-center"
                >
                  Instrucciones: 1° clic en el mapa define el
                  <b>Origen</b>
                  (verde). 2° clic define el
                  <b>Destino</b>
                  (rojo).
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
              label="Cancelar"
              color="grey-7"
              v-close-popup
            />

            <q-btn
              type="submit"
              :label="isEditing ? 'Actualizar' : 'Solicitar'"
              color="positive"
              class="text-bold"
              :loading="saving"
              unelevated
            />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- =========================================================
         MODAL ASIGNACIÓN MANUAL DE CONDUCTOR
    ========================================================== -->
    <q-dialog
      v-model="asignacionDialogOpen"
      persistent
    >
      <q-card class="asignacion-dialog">
        <q-card-section
          class="bg-positive text-white
                 row items-center no-wrap"
        >
          <q-avatar
            color="white"
            text-color="positive"
            icon="assignment_ind"
            size="44px"
            class="q-mr-sm"
          />

          <div class="col min-width-zero">
            <div class="text-h6 text-weight-bold">
              Asignar conductor
            </div>

            <div class="text-caption ellipsis">
              Solicitud
              #{{ solicitudAsignacion?.id || '—' }}
            </div>
          </div>

          <q-btn
            icon="close"
            flat
            round
            dense
            :disable="asignandoConductor"
            @click="cerrarAsignacionManual"
          />
        </q-card-section>

        <q-card-section
          v-if="solicitudAsignacion"
          class="q-pb-sm"
        >
          <q-card
            flat
            bordered
            class="resumen-solicitud-asignacion"
          >
            <q-card-section class="q-pa-sm">
              <div class="text-caption text-grey-6">
                Pasajero
              </div>

              <div class="text-weight-bold text-grey-9">
                {{
                  getPasajeroNombre(
                    solicitudAsignacion
                  )
                }}
              </div>

              <div
                class="row q-col-gutter-sm q-mt-sm"
              >
                <div class="col-12 col-sm-6">
                  <div class="text-caption text-grey-6">
                    Origen
                  </div>

                  <div class="text-body2">
                    {{
                      solicitudAsignacion.origen
                      || 'Sin origen'
                    }}
                  </div>
                </div>

                <div class="col-12 col-sm-6">
                  <div class="text-caption text-grey-6">
                    Destino
                  </div>

                  <div class="text-body2">
                    {{
                      solicitudAsignacion.destino
                      || 'Sin destino'
                    }}
                  </div>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </q-card-section>

        <q-card-section
          class="q-pt-sm asignacion-contenido"
        >
          <q-inner-loading
            :showing="cargandoConductores"
          >
            <q-spinner
              color="positive"
              size="46px"
            />

            <div class="q-mt-sm text-grey-7">
              Buscando conductores disponibles...
            </div>
          </q-inner-loading>

          <div
            v-if="
              !cargandoConductores
              && conductoresAsignacion.length
            "
          >
            <div
              class="text-subtitle2 text-weight-bold
                     text-grey-8 q-mb-sm"
            >
              Selecciona un conductor disponible
            </div>

            <q-list
              bordered
              separator
              class="lista-conductores-asignacion"
            >
              <q-item
                v-for="conductor in conductoresAsignacion"
                :key="conductor.id"
                clickable
                v-ripple
                :active="
                  Number(conductorSeleccionadoId)
                    === Number(conductor.id)
                "
                active-class="conductor-asignacion-activo"
                @click="
                  conductorSeleccionadoId =
                    conductor.id
                "
              >
                <q-item-section avatar>
                  <q-avatar
                    color="green-1"
                    text-color="green-9"
                    icon="two_wheeler"
                  />
                </q-item-section>

                <q-item-section>
                  <q-item-label
                    class="text-weight-bold text-grey-9"
                  >
                    {{
                      obtenerNombreConductorAsignacion(
                        conductor
                      )
                    }}
                  </q-item-label>

                  <q-item-label caption>
                    {{
                      textoDistanciaConductor(
                        conductor
                      )
                    }}
                    ·
                    {{
                      textoUltimaConexionConductor(
                        conductor
                      )
                    }}
                  </q-item-label>

                  <q-item-label
                    caption
                    class="q-mt-xs"
                  >
                    <q-chip
                      dense
                      size="sm"
                      :color="
                        colorGpsConductor(
                          conductor.estado_gps
                        )
                      "
                      :text-color="
                        conductor.estado_gps === 'actualizado'
                          ? 'green-10'
                          : 'white'
                      "
                      :icon="
                        iconoGpsConductor(
                          conductor.estado_gps
                        )
                      "
                      class="q-ma-none"
                    >
                      {{
                        textoGpsConductor(
                          conductor
                        )
                      }}
                    </q-chip>
                  </q-item-label>
                </q-item-section>

                <q-item-section side>
                  <q-radio
                    v-model="conductorSeleccionadoId"
                    :val="conductor.id"
                    color="positive"
                  />
                </q-item-section>
              </q-item>
            </q-list>

            <q-banner
              rounded
              class="bg-blue-1 text-blue-10 q-mt-md"
            >
              <template #avatar>
                <q-icon name="info" color="blue-8" />
              </template>

              El conductor recibirá la solicitud
              y deberá aceptarla desde su panel.
            </q-banner>
          </div>

          <div
            v-else-if="!cargandoConductores"
            class="column items-center text-center
                   text-grey-6 q-pa-xl"
          >
            <q-icon
              name="no_accounts"
              size="56px"
              class="q-mb-sm"
            />

            <div class="text-subtitle1 text-weight-bold">
              No hay conductores disponibles
            </div>

            <div class="text-caption">
              Revisa que algún mototaxista esté
              en línea, disponible y sin otra solicitud.
            </div>
          </div>
        </q-card-section>

        <q-separator />

        <q-card-actions
          align="right"
          class="q-pa-md bg-grey-1"
        >
          <q-btn
            flat
            label="Cancelar"
            color="grey-7"
            :disable="asignandoConductor"
            @click="cerrarAsignacionManual"
          />

          <q-btn
            color="positive"
            icon="assignment_turned_in"
            label="Asignar conductor"
            unelevated
            :loading="asignandoConductor"
            :disable="
              !conductorSeleccionadoId
              || !conductoresAsignacion.length
            "
            @click="confirmarAsignacionManual"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- =========================================================
         MODAL HISTORIAL DEL PASAJERO
    ========================================================== -->
    <q-dialog v-model="historialDialogOpen">
      <q-card class="border-radius-md historial-dialog">
        <q-card-section
          class="bg-positive text-white row items-center no-wrap"
        >
          <q-avatar
            color="white"
            text-color="positive"
            size="42px"
            class="q-mr-sm"
          >
            {{ getIniciales(pasajeroSeleccionado.nombre) }}
          </q-avatar>

          <div class="col min-width-zero">
            <div class="text-h6 text-weight-bold">
              Historial del pasajero
            </div>

            <div class="text-caption ellipsis">
              {{ pasajeroSeleccionado.nombre }}
            </div>
          </div>

          <q-btn
            icon="close"
            flat
            round
            dense
            v-close-popup
          />
        </q-card-section>

        <q-card-section
          class="q-pa-none relative-position historial-contenido"
        >
          <q-inner-loading :showing="historialLoading">
            <q-spinner
              color="positive"
              size="42px"
            />
          </q-inner-loading>

          <q-list
            v-if="historialPasajero.length"
            separator
          >
            <q-item
              v-for="viaje in historialPasajero"
              :key="viaje.id"
              class="q-py-md historial-item"
            >
              <q-item-section avatar top>
                <q-avatar
                  :color="getEstadoColor(viaje.estado)"
                  text-color="white"
                  icon="two_wheeler"
                />
              </q-item-section>

              <q-item-section class="min-width-zero">
                <q-item-label
                  class="text-weight-bold text-grey-9 text-wrap"
                >
                  {{ viaje.origen || 'Sin origen' }}

                  <q-icon
                    name="arrow_forward"
                    size="16px"
                    class="q-mx-xs"
                  />

                  {{ viaje.destino || 'Sin destino' }}
                </q-item-label>

                <q-item-label
                  caption
                  class="q-mt-xs"
                >
                  {{ formatearFecha(viaje.fecha) }}
                  ·
                  Solicitud #{{ viaje.id }}
                </q-item-label>

                <q-item-label
                  caption
                  class="q-mt-xs"
                >
                  Conductor:

                  <span
                    :class="
                      getMototaxistaNombre(viaje)
                        ? 'text-primary text-weight-bold'
                        : 'text-grey-6 italic'
                    "
                  >
                    {{
                      getMototaxistaNombre(viaje)
                        || 'Buscando conductor...'
                    }}
                  </span>
                </q-item-label>
              </q-item-section>

              <q-item-section side top>
                <div
                  class="text-subtitle1 text-weight-bold text-positive"
                >
                  {{ formatearPrecio(viaje.precio) }}
                </div>

                <q-chip
                  :color="getEstadoColor(viaje.estado)"
                  text-color="white"
                  dense
                  class="text-caption text-uppercase q-mt-xs"
                >
                  {{ viaje.estado || 'Sin estado' }}
                </q-chip>
              </q-item-section>
            </q-item>
          </q-list>

          <div
            v-else
            class="column items-center q-pa-xl text-grey-6"
          >
            <q-icon
              name="history_toggle_off"
              size="56px"
              class="q-mb-sm"
            />

            <div class="text-subtitle1 text-weight-medium">
              No existen viajes registrados
            </div>

            <div class="text-caption text-center">
              Este pasajero todavía no tiene historial.
            </div>
          </div>
        </q-card-section>

        <q-card-actions
          align="right"
          class="q-pa-md bg-grey-1"
        >
          <q-btn
            flat
            label="Cerrar"
            color="grey-7"
            v-close-popup
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- =========================================================
         CONVERSACIÓN DEL VIAJE — ADMINISTRADOR SOLO LECTURA
    ========================================================== -->
    <q-dialog
      v-model="conversacionDialogOpen"
      :maximized="$q.screen.lt.sm"
      transition-show="scale"
      transition-hide="scale"
      @hide="alCerrarConversacion"
    >
      <q-card class="conversacion-admin-card column no-wrap">
        <q-card-section class="bg-primary text-white q-pa-md">
          <div class="row items-center no-wrap">
            <q-avatar
              color="white"
              text-color="primary"
              icon="forum"
              size="48px"
              class="q-mr-md"
            />

            <div class="col min-width-zero">
              <div class="text-h6 text-weight-bold ellipsis">
                Conversación del viaje
                #{{ solicitudConversacion?.id || '—' }}
              </div>

              <div class="text-caption text-blue-1 ellipsis">
                {{ getPasajeroNombre(solicitudConversacion) }}
                ·
                {{ getMototaxistaNombre(solicitudConversacion)
                  || 'Sin conductor asignado' }}
              </div>
            </div>

            <q-chip
              v-if="solicitudConversacion"
              :color="getEstadoColor(solicitudConversacion.estado)"
              text-color="white"
              dense
              class="q-mr-sm text-weight-bold text-uppercase"
            >
              {{ solicitudConversacion.estado || 'Sin estado' }}
            </q-chip>

            <q-btn
              flat
              round
              dense
              icon="close"
              aria-label="Cerrar conversación"
              @click="conversacionDialogOpen = false"
            />
          </div>
        </q-card-section>

        <q-card-section class="q-pa-sm bg-grey-2">
          <div class="row q-col-gutter-sm">
            <div class="col-12 col-sm-4">
              <div class="conversacion-resumen-item">
                <q-icon name="person" color="positive" size="22px" />
                <div class="min-width-zero">
                  <div class="text-caption text-grey-6">Pasajero</div>
                  <div class="text-body2 text-weight-bold ellipsis">
                    {{ getPasajeroNombre(solicitudConversacion) }}
                  </div>
                </div>
              </div>
            </div>

            <div class="col-12 col-sm-4">
              <div class="conversacion-resumen-item">
                <q-icon name="two_wheeler" color="primary" size="22px" />
                <div class="min-width-zero">
                  <div class="text-caption text-grey-6">Conductor</div>
                  <div class="text-body2 text-weight-bold ellipsis">
                    {{ getMototaxistaNombre(solicitudConversacion)
                      || 'No asignado' }}
                  </div>
                </div>
              </div>
            </div>

            <div class="col-6 col-sm-2">
              <div class="conversacion-resumen-item">
                <q-icon name="mark_chat_read" color="indigo" size="22px" />
                <div>
                  <div class="text-caption text-grey-6">Mensajes</div>
                  <div class="text-body2 text-weight-bold">
                    {{ mensajesConversacion.length }}
                  </div>
                </div>
              </div>
            </div>

            <div class="col-6 col-sm-2">
              <div class="conversacion-resumen-item">
                <q-icon
                  :name="conversacionEnVivo ? 'sensors' : 'sync'"
                  :color="conversacionEnVivo ? 'positive' : 'orange-8'"
                  size="22px"
                />
                <div>
                  <div class="text-caption text-grey-6">Canal</div>
                  <div
                    class="text-body2 text-weight-bold"
                    :class="conversacionEnVivo
                      ? 'text-positive'
                      : 'text-orange-9'"
                  >
                    {{ conversacionEnVivo ? 'En vivo' : 'Respaldo' }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </q-card-section>

        <q-separator />

        <q-linear-progress
          v-if="conversacionLoading"
          indeterminate
          color="primary"
        />

        <q-card-section
          ref="conversacionContenedor"
          class="conversacion-admin-mensajes col"
        >
          <div
            v-if="!conversacionLoading && mensajesConversacion.length === 0"
            class="conversacion-vacia column flex-center text-center text-grey-6"
          >
            <q-avatar
              color="blue-1"
              text-color="primary"
              icon="speaker_notes_off"
              size="72px"
              class="q-mb-md"
            />

            <div class="text-h6 text-weight-bold text-grey-8">
              Conversación vacía
            </div>

            <div class="text-body2 q-mt-xs">
              El pasajero y el conductor todavía no enviaron mensajes.
            </div>
          </div>

          <div
            v-for="mensaje in mensajesConversacion"
            :key="mensaje.id"
            class="conversacion-mensaje-fila"
            :class="claseFilaMensajeConversacion(mensaje)"
          >
            <div
              class="conversacion-mensaje-burbuja"
              :class="claseBurbujaMensajeConversacion(mensaje)"
            >
              <div class="row items-center no-wrap q-mb-xs">
                <q-icon
                  :name="iconoRemitenteConversacion(mensaje)"
                  size="17px"
                  class="q-mr-xs"
                />

                <div class="text-caption text-weight-bold ellipsis">
                  {{ mensaje.remitente_nombre
                    || etiquetaRemitenteConversacion(mensaje) }}
                </div>
              </div>

              <div class="text-body2 conversacion-mensaje-texto">
                {{ mensaje.mensaje }}
              </div>

              <div class="text-caption conversacion-mensaje-hora">
                {{ formatearFechaHoraConversacion(mensaje.creado_en) }}
              </div>
            </div>
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section class="q-pa-sm bg-blue-1">
          <q-banner dense rounded class="bg-white text-primary">
            <template #avatar>
              <q-icon name="visibility" color="primary" />
            </template>

            <div class="text-weight-bold">Modo solo lectura</div>
            <div class="text-caption">
              El administrador puede supervisar esta conversación, pero no
              puede enviar, editar ni eliminar mensajes.
            </div>
          </q-banner>
        </q-card-section>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import {
  ref,
  onMounted,
  onBeforeUnmount,
  nextTick
} from 'vue'

import { useQuasar } from 'quasar'

import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

import axios from 'axios'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { api } from 'src/boot/axios.js'
import { BROADCAST_AUTH_URL, echoOptions } from 'src/config/runtime.js'

import solicitudService from 'src/services/solicitudService'
import pasajeroService from 'src/services/pasajeroService'

window.Pusher = Pusher

const $q = useQuasar()

/* =========================================================
   ESTADOS GENERALES
========================================================= */

const solicitudes = ref([])
const pasajerosOptions = ref([])

const filter = ref('')
const loading = ref(false)
const saving = ref(false)

let intervaloActualizacionSolicitudes = null
let temporizadorEventoTiempoReal = null
let actualizandoSolicitudes = false

const dialogOpen = ref(false)
const isEditing = ref(false)

const historialDialogOpen = ref(false)
const historialLoading = ref(false)
const historialPasajero = ref([])

const asignacionDialogOpen = ref(false)
const cargandoConductores = ref(false)
const asignandoConductor = ref(false)
const solicitudAsignacion = ref(null)
const conductoresAsignacion = ref([])
const conductorSeleccionadoId = ref(null)

/* =========================================================
   CONVERSACIÓN DEL VIAJE — ADMINISTRADOR SOLO LECTURA
========================================================= */

const conversacionDialogOpen = ref(false)
const conversacionLoading = ref(false)
const solicitudConversacion = ref(null)
const mensajesConversacion = ref([])
const conversacionEnVivo = ref(false)
const conversacionContenedor = ref(null)

let echoConversacion = null
let canalConversacionId = null
let intervaloConversacion = null
let cargandoConversacionSilenciosa = false

const pasajeroSeleccionado = ref({
  id: null,
  nombre: 'Pasajero'
})

const estadosOptions = [
  'Pendiente',
  'Aceptado',
  'En Curso',
  'Finalizado',
  'Cancelado'
]

/* =========================================================
   MAPA
========================================================= */

const LAT_TRINIDAD = -14.8308
const LNG_TRINIDAD = -64.9024

let mapa = null
let marcadorOrigen = null
let marcadorDestino = null
let lineaRuta = null

const origenCoords = ref(null)
const destinoCoords = ref(null)
const distanciaKm = ref(0)

/* =========================================================
   FORMULARIO
========================================================= */

const obtenerFechaActual = () => {
  const fecha = new Date()

  const year = fecha.getFullYear()
  const month = String(fecha.getMonth() + 1).padStart(2, '0')
  const day = String(fecha.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

const crearFormularioDefault = () => ({
  id: null,
  origen: '',
  destino: '',
  fecha: obtenerFechaActual(),
  estado: 'Pendiente',
  id_pasajero: null,
  precio: 8
})

const form = ref(crearFormularioDefault())

/* =========================================================
   NORMALIZACIÓN DE PASAJERO Y CONDUCTOR
========================================================= */

const extraerNombrePersona = (entidad) => {
  if (!entidad) {
    return null
  }

  const posiblesNombres = [
    entidad?.persona?.nombre,
    entidad?.persona?.nombre_completo,
    entidad?.persona?.nombres,
    entidad?.persona_asignada?.nombre,
    entidad?.persona_asignada,
    entidad?.usuario?.persona?.nombre,
    entidad?.user?.persona?.nombre,
    entidad?.perfil?.persona?.nombre,
    entidad?.nombre_completo,
    entidad?.nombre,
    entidad?.nombres
  ]

  const nombreEncontrado = posiblesNombres.find((valor) => {
    return typeof valor === 'string' && valor.trim() !== ''
  })

  return nombreEncontrado
    ? nombreEncontrado.trim()
    : null
}

const getPasajeroNombre = (solicitud) => {
  return (
    extraerNombrePersona(solicitud?.pasajero)
    || extraerNombrePersona(solicitud?.cliente)
    || solicitud?.pasajero_nombre
    || 'Pasajero no identificado'
  )
}

const getPasajeroId = (solicitud) => {
  return (
    solicitud?.id_pasajero
    ?? solicitud?.pasajero_id
    ?? solicitud?.pasajero?.id
    ?? null
  )
}

const getMototaxistaNombre = (solicitud) => {
  const relacionConductor = (
    solicitud?.mototaxista
    ?? solicitud?.conductor
    ?? solicitud?.asignado
    ?? solicitud?.transportista
    ?? null
  )

  return (
    extraerNombrePersona(relacionConductor)
    || solicitud?.mototaxista_nombre
    || solicitud?.conductor_nombre
    || null
  )
}

const getIniciales = (nombre = '') => {
  const partes = String(nombre)
    .trim()
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)

  const iniciales = partes
    .map((parte) => parte.charAt(0).toUpperCase())
    .join('')

  return iniciales || 'P'
}

const formatearPrecio = (precio) => {
  const monto = Number.parseFloat(precio)

  return `Bs. ${
    Number.isFinite(monto)
      ? monto.toFixed(2)
      : '0.00'
  }`
}

const formatearFecha = (fecha) => {
  if (!fecha) {
    return 'Fecha no registrada'
  }

  const fechaNormalizada = String(fecha).includes('T')
    ? new Date(fecha)
    : new Date(`${fecha}T00:00:00`)

  if (Number.isNaN(fechaNormalizada.getTime())) {
    return String(fecha)
  }

  return new Intl.DateTimeFormat('es-BO', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  }).format(fechaNormalizada)
}

/* =========================================================
   COLUMNAS DE LA TABLA
========================================================= */

const columns = [
  {
    name: 'id',
    label: 'ID',
    align: 'left',
    field: (row) => row.id,
    sortable: true
  },
  {
    name: 'pasajero',
    label: 'Pasajero',
    align: 'left',
    field: (row) => getPasajeroNombre(row),
    sortable: true
  },
  {
    name: 'mototaxista',
    label: 'Mototaxista asignado',
    align: 'left',
    field: (row) => {
      return getMototaxistaNombre(row) || 'Buscando conductor'
    },
    sortable: true
  },
  {
    name: 'origen',
    label: 'Origen',
    align: 'left',
    field: 'origen',
    sortable: true
  },
  {
    name: 'destino',
    label: 'Destino',
    align: 'left',
    field: 'destino',
    sortable: true
  },
  {
    name: 'precio',
    label: 'Tarifa',
    align: 'right',
    field: (row) => Number.parseFloat(row.precio) || 0,
    sortable: true
  },
  {
    name: 'fecha',
    label: 'Fecha',
    align: 'left',
    field: 'fecha',
    sortable: true
  },
  {
    name: 'estado',
    label: 'Estado',
    align: 'center',
    field: 'estado',
    sortable: true
  },
  {
    name: 'actions',
    label: '',
    align: 'center',
    field: 'actions'
  }
]

/* =========================================================
   COLORES DE ESTADO
========================================================= */

const getEstadoColor = (estado) => {
  if (!estado) {
    return 'grey-7'
  }

  const estadoNormalizado = String(estado)
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

  return 'grey-7'
}

/* =========================================================
   HISTORIAL DEL PASAJERO
========================================================= */

const abrirHistorialPasajero = async (solicitudSeleccionada) => {
  const pasajeroId = getPasajeroId(solicitudSeleccionada)

  pasajeroSeleccionado.value = {
    id: pasajeroId,
    nombre: getPasajeroNombre(solicitudSeleccionada)
  }

  historialDialogOpen.value = true
  historialLoading.value = true

  try {
    if (pasajeroId === null || pasajeroId === undefined) {
      historialPasajero.value = [solicitudSeleccionada]
      return
    }

    historialPasajero.value = solicitudes.value
      .filter((solicitud) => {
        return (
          String(getPasajeroId(solicitud))
          === String(pasajeroId)
        )
      })
      .sort((a, b) => Number(b.id) - Number(a.id))
  } finally {
    historialLoading.value = false
  }
}

/* =========================================================
   CONVERSACIÓN DEL VIAJE — ADMINISTRADOR SOLO LECTURA
========================================================= */

const obtenerEndpointAutorizacionConversacion = () => {
  const baseConfigurada = String(
    api?.defaults?.baseURL || ''
  ).trim().replace(/\/+$/, '')

  if (/^https?:\/\//i.test(baseConfigurada)) {
    return `${baseConfigurada}/broadcasting/auth`
  }

  return BROADCAST_AUTH_URL
}

const obtenerCabecerasAutorizacionConversacion = () => {
  const token = localStorage.getItem('motrix_token') || ''

  return {
    Accept: 'application/json',
    ...(token
      ? { Authorization: `Bearer ${token}` }
      : {})
  }
}

const etiquetaRemitenteConversacion = (mensaje) => {
  const tipo = String(mensaje?.remitente_tipo || '').toLowerCase()

  if (tipo === 'pasajero') return 'Pasajero'
  if (tipo === 'conductor') return 'Mototaxista'
  if (tipo === 'admin') return 'Administrador'

  return 'Usuario'
}

const iconoRemitenteConversacion = (mensaje) => {
  const tipo = String(mensaje?.remitente_tipo || '').toLowerCase()

  if (tipo === 'pasajero') return 'person'
  if (tipo === 'conductor') return 'two_wheeler'

  return 'admin_panel_settings'
}

const claseFilaMensajeConversacion = (mensaje) => {
  const tipo = String(mensaje?.remitente_tipo || '').toLowerCase()

  if (tipo === 'conductor') return 'conversacion-fila-conductor'
  if (tipo === 'admin') return 'conversacion-fila-admin'

  return 'conversacion-fila-pasajero'
}

const claseBurbujaMensajeConversacion = (mensaje) => {
  const tipo = String(mensaje?.remitente_tipo || '').toLowerCase()

  if (tipo === 'conductor') return 'conversacion-burbuja-conductor'
  if (tipo === 'admin') return 'conversacion-burbuja-admin'

  return 'conversacion-burbuja-pasajero'
}

const formatearFechaHoraConversacion = (fecha) => {
  if (!fecha) return 'Hora no registrada'

  const valorTexto = String(fecha)
  const normalizada = valorTexto.includes('T')
    ? valorTexto
    : valorTexto.replace(' ', 'T')

  const valor = new Date(normalizada)

  if (Number.isNaN(valor.getTime())) {
    return valorTexto
  }

  return new Intl.DateTimeFormat('es-BO', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  }).format(valor)
}

const desplazarConversacionAlFinal = async () => {
  await nextTick()

  if (conversacionContenedor.value) {
    conversacionContenedor.value.scrollTop =
      conversacionContenedor.value.scrollHeight
  }
}

const agregarMensajeConversacion = async (mensaje) => {
  if (!mensaje?.id) return false

  const existe = mensajesConversacion.value.some((item) => {
    return Number(item.id) === Number(mensaje.id)
  })

  if (existe) return false

  mensajesConversacion.value.push(mensaje)
  mensajesConversacion.value.sort((a, b) => Number(a.id) - Number(b.id))

  await desplazarConversacionAlFinal()
  return true
}

const cargarConversacion = async (silencioso = false) => {
  const solicitudId = Number(solicitudConversacion.value?.id)

  if (!solicitudId) {
    mensajesConversacion.value = []
    return
  }

  if (silencioso && cargandoConversacionSilenciosa) {
    return
  }

  if (silencioso) {
    cargandoConversacionSilenciosa = true
  } else {
    conversacionLoading.value = true
  }

  try {
    const response = await api.get(
      `/solicitudes/${solicitudId}/mensajes`,
      { params: { _t: Date.now() } }
    )

    mensajesConversacion.value = Array.isArray(response.data?.mensajes)
      ? response.data.mensajes
      : []

    await desplazarConversacionAlFinal()
  } catch (error) {
    if (!silencioso) {
      const mensaje = (
        error?.response?.data?.mensaje
        || error?.response?.data?.message
        || 'No se pudo cargar la conversación del viaje.'
      )

      $q.notify({
        type: 'negative',
        message: mensaje
      })
    }
  } finally {
    if (silencioso) {
      cargandoConversacionSilenciosa = false
    } else {
      conversacionLoading.value = false
    }
  }
}

const detenerRespaldoConversacion = () => {
  if (intervaloConversacion) {
    window.clearInterval(intervaloConversacion)
    intervaloConversacion = null
  }
}

const desconectarConversacionTiempoReal = () => {
  if (echoConversacion && canalConversacionId) {
    echoConversacion.leave(`viajes.chat.${canalConversacionId}`)
  }

  if (echoConversacion) {
    echoConversacion.disconnect()
  }

  echoConversacion = null
  canalConversacionId = null
  conversacionEnVivo.value = false
}

const inicializarConversacionTiempoReal = (solicitudId) => {
  desconectarConversacionTiempoReal()

  try {
    echoConversacion = new Echo({
      ...echoOptions(),
      authEndpoint: obtenerEndpointAutorizacionConversacion(),
      auth: {
        headers: obtenerCabecerasAutorizacionConversacion()
      }
    })

    canalConversacionId = Number(solicitudId)

    const conexion = echoConversacion.connector?.pusher?.connection

    conexion?.bind('connected', () => {
      conversacionEnVivo.value = true
    })

    conexion?.bind('disconnected', () => {
      conversacionEnVivo.value = false
    })

    conexion?.bind('error', (error) => {
      console.error(
        'Error en el canal administrativo del chat:',
        error
      )

      conversacionEnVivo.value = false
    })

    echoConversacion
      .private(`viajes.chat.${solicitudId}`)
      .listen('.MensajeViajeEnviado', (data) => {
        agregarMensajeConversacion(data?.mensaje).catch((error) => {
          console.error(
            'Error agregando mensaje administrativo:',
            error
          )
        })
      })
  } catch (error) {
    console.error(
      'No se pudo inicializar el chat administrativo:',
      error
    )

    conversacionEnVivo.value = false
  }
}

const abrirConversacion = async (solicitud) => {
  solicitudConversacion.value = solicitud
  mensajesConversacion.value = []
  conversacionDialogOpen.value = true

  await cargarConversacion()
  inicializarConversacionTiempoReal(solicitud.id)

  detenerRespaldoConversacion()
  intervaloConversacion = window.setInterval(
    () => cargarConversacion(true),
    5000
  )
}

const alCerrarConversacion = () => {
  detenerRespaldoConversacion()
  desconectarConversacionTiempoReal()

  solicitudConversacion.value = null
  mensajesConversacion.value = []
  conversacionLoading.value = false
  cargandoConversacionSilenciosa = false
}

/* =========================================================
   GEOCODIFICACIÓN INVERSA
========================================================= */

const obtenerDireccionDeCoordenadas = async (lat, lng) => {
  try {
    const response = await axios.get(
      'https://nominatim.openstreetmap.org/reverse',
      {
        params: {
          lat,
          lon: lng,
          format: 'json',
          addressdetails: 1
        }
      }
    )

    if (response.data?.display_name) {
      const partes = response.data.display_name.split(',')

      return partes
        .slice(0, 3)
        .join(',')
        .trim()
    }

    return `Lat: ${lat.toFixed(4)}, Lng: ${lng.toFixed(4)}`
  } catch (error) {
    console.error(
      'Error al traducir coordenadas:',
      error
    )

    return `Lat: ${lat.toFixed(4)}, Lng: ${lng.toFixed(4)}`
  }
}

/* =========================================================
   CÁLCULO DE TARIFA
========================================================= */

const calcularTarifaTrinidad = () => {
  const ahora = new Date()
  const hora = ahora.getHours()
  const esNocturno = hora >= 22 || hora < 6

  if (esNocturno) {
    form.value.precio = 15
    return
  }

  if (distanciaKm.value === 0) {
    form.value.precio = 8
    return
  }

  if (distanciaKm.value <= 1.2) {
    form.value.precio = 5
    return
  }

  if (distanciaKm.value <= 2.8) {
    form.value.precio = 8
    return
  }

  form.value.precio = 10
}

/* =========================================================
   ICONOS DEL MAPA
========================================================= */

const crearIconoMarcador = (color) => {
  return L.icon({
    iconUrl:
      `https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-${color}.png`,
    shadowUrl:
      'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    shadowSize: [41, 41]
  })
}

const cargarMarcadoresGuardados = async () => {
  if (!mapa) {
    return
  }

  if (origenCoords.value) {
    marcadorOrigen = L.marker(
      origenCoords.value,
      {
        draggable: true,
        icon: crearIconoMarcador('green')
      }
    )
      .addTo(mapa)
      .bindPopup(form.value.origen || 'Punto de origen')

    marcadorOrigen.on(
      'dragend',
      actualizarMarcadorOrigen
    )
  }

  if (destinoCoords.value) {
    marcadorDestino = L.marker(
      destinoCoords.value,
      {
        draggable: true,
        icon: crearIconoMarcador('red')
      }
    )
      .addTo(mapa)
      .bindPopup(form.value.destino || 'Punto de destino')

    marcadorDestino.on(
      'dragend',
      actualizarMarcadorDestino
    )
  }

  if (
    origenCoords.value
    && destinoCoords.value
  ) {
    await obtenerRutaOSRM()
  } else if (origenCoords.value) {
    mapa.setView(origenCoords.value, 15)
  } else if (destinoCoords.value) {
    mapa.setView(destinoCoords.value, 15)
  }
}

const actualizarMarcadorOrigen = async () => {
  if (!marcadorOrigen) {
    return
  }

  const posicion = marcadorOrigen.getLatLng()

  origenCoords.value = [
    posicion.lat,
    posicion.lng
  ]

  form.value.origen = 'Obteniendo dirección...'

  const direccion = await obtenerDireccionDeCoordenadas(
    posicion.lat,
    posicion.lng
  )

  form.value.origen = direccion

  marcadorOrigen
    .bindPopup(direccion)
    .openPopup()

  await obtenerRutaOSRM()
}

const actualizarMarcadorDestino = async () => {
  if (!marcadorDestino) {
    return
  }

  const posicion = marcadorDestino.getLatLng()

  destinoCoords.value = [
    posicion.lat,
    posicion.lng
  ]

  form.value.destino = 'Obteniendo dirección...'

  const direccion = await obtenerDireccionDeCoordenadas(
    posicion.lat,
    posicion.lng
  )

  form.value.destino = direccion

  marcadorDestino
    .bindPopup(direccion)
    .openPopup()

  await obtenerRutaOSRM()
}

/* =========================================================
   INICIALIZACIÓN DEL MAPA
========================================================= */

const inicializarMapa = () => {
  if (mapa) {
    return
  }

  const contenedorMapa = document.getElementById(
    'mapa-solicitud'
  )

  if (!contenedorMapa) {
    return
  }

  mapa = L
    .map('mapa-solicitud')
    .setView(
      [LAT_TRINIDAD, LNG_TRINIDAD],
      14
    )

  L.tileLayer(
    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    {
      attribution: '© OpenStreetMap'
    }
  ).addTo(mapa)

  mapa.on('click', async (evento) => {
    const {
      lat,
      lng
    } = evento.latlng

    if (!origenCoords.value) {
      origenCoords.value = [lat, lng]
      form.value.origen = 'Obteniendo dirección...'

      const direccionOrigen =
        await obtenerDireccionDeCoordenadas(lat, lng)

      form.value.origen = direccionOrigen

      marcadorOrigen = L.marker(
        [lat, lng],
        {
          draggable: true,
          icon: crearIconoMarcador('green')
        }
      )
        .addTo(mapa)
        .bindPopup(direccionOrigen)
        .openPopup()

      marcadorOrigen.on(
        'dragend',
        actualizarMarcadorOrigen
      )

      return
    }

    if (!destinoCoords.value) {
      destinoCoords.value = [lat, lng]
      form.value.destino = 'Obteniendo dirección...'

      const direccionDestino =
        await obtenerDireccionDeCoordenadas(lat, lng)

      form.value.destino = direccionDestino

      marcadorDestino = L.marker(
        [lat, lng],
        {
          draggable: true,
          icon: crearIconoMarcador('red')
        }
      )
        .addTo(mapa)
        .bindPopup(direccionDestino)
        .openPopup()

      marcadorDestino.on(
        'dragend',
        actualizarMarcadorDestino
      )

      await obtenerRutaOSRM()
    }
  })

  setTimeout(() => {
    mapa?.invalidateSize()
  }, 150)
}

/* =========================================================
   RUTA OSRM
========================================================= */

const obtenerRutaOSRM = async () => {
  if (
    !origenCoords.value
    || !destinoCoords.value
    || !mapa
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

    const response = await axios.get(url, {
      params: {
        overview: 'full',
        geometries: 'geojson'
      }
    })

    const ruta = response.data?.routes?.[0]

    if (!ruta) {
      return
    }

    distanciaKm.value = Number.parseFloat(
      (ruta.distance / 1000).toFixed(2)
    )

    calcularTarifaTrinidad()

    if (lineaRuta) {
      mapa.removeLayer(lineaRuta)
    }

    lineaRuta = L.geoJSON(
      ruta.geometry,
      {
        style: {
          color: '#027be3',
          weight: 6,
          opacity: 0.8
        }
      }
    ).addTo(mapa)

    mapa.fitBounds(
      lineaRuta.getBounds(),
      {
        padding: [40, 40]
      }
    )
  } catch (error) {
    console.error(
      'Error cargando la ruta OSRM:',
      error
    )

    $q.notify({
      type: 'warning',
      message: 'No se pudo calcular la ruta en este momento.'
    })
  }
}

/* =========================================================
   LIMPIAR MAPA
========================================================= */

const limpiarMapa = () => {
  if (mapa && marcadorOrigen) {
    mapa.removeLayer(marcadorOrigen)
  }

  if (mapa && marcadorDestino) {
    mapa.removeLayer(marcadorDestino)
  }

  if (mapa && lineaRuta) {
    mapa.removeLayer(lineaRuta)
  }

  origenCoords.value = null
  destinoCoords.value = null

  marcadorOrigen = null
  marcadorDestino = null
  lineaRuta = null

  distanciaKm.value = 0

  form.value.origen = ''
  form.value.destino = ''
  form.value.precio = 8

  mapa?.setView(
    [LAT_TRINIDAD, LNG_TRINIDAD],
    14
  )
}

/* =========================================================
   EVENTOS DEL MODAL
========================================================= */

const onModalShow = async () => {
  await nextTick()

  inicializarMapa()

  setTimeout(async () => {
    mapa?.invalidateSize()
    await cargarMarcadoresGuardados()
  }, 200)
}

const onModalHide = () => {
  if (mapa) {
    mapa.remove()
    mapa = null
  }

  origenCoords.value = null
  destinoCoords.value = null

  marcadorOrigen = null
  marcadorDestino = null
  lineaRuta = null

  distanciaKm.value = 0
}

/* =========================================================
   CARGA DE DATOS
========================================================= */

const cargarSolicitudes = async () => {
  const response = await solicitudService.getAll()

  solicitudes.value = Array.isArray(response?.data)
    ? response.data
    : []
}

/*
 * Actualiza únicamente las solicitudes sin mostrar
 * la pantalla de carga. Esto mantiene sincronizados
 * los cambios realizados desde el panel del conductor.
 */
const actualizarSolicitudesSilenciosamente = async (forzar = false) => {
  if (
    actualizandoSolicitudes
    || (
      !forzar
      && document.visibilityState !== 'visible'
    )
  ) {
    return
  }

  actualizandoSolicitudes = true

  try {
    await cargarSolicitudes()
  } catch (error) {
    console.error(
      'Error actualizando solicitudes en segundo plano:',
      error
    )
  } finally {
    actualizandoSolicitudes = false
  }
}

const manejarCambioSolicitudTiempoReal = () => {
  if (temporizadorEventoTiempoReal) {
    window.clearTimeout(
      temporizadorEventoTiempoReal
    )
  }

  temporizadorEventoTiempoReal =
    window.setTimeout(
      () => {
        actualizarSolicitudesSilenciosamente(
          true
        )
      },
      150
    )
}

const cargarPasajeros = async () => {
  const response = await pasajeroService.getAll()

  const pasajeros = Array.isArray(response?.data)
    ? response.data
    : []

  pasajerosOptions.value = pasajeros.map((pasajero) => {
    return {
      id: pasajero.id,
      nombre_completo:
        extraerNombrePersona(pasajero)
        || `Pasajero ID: ${pasajero.id}`
    }
  })
}

const cargarDatos = async () => {
  loading.value = true

  try {
    await Promise.all([
      cargarSolicitudes(),
      cargarPasajeros()
    ])
  } catch (error) {
    console.error(
      'Error cargando información:',
      error
    )

    $q.notify({
      type: 'negative',
      message: 'No se pudo cargar la información de solicitudes.'
    })
  } finally {
    loading.value = false
  }
}

/* =========================================================
   ASIGNACIÓN MANUAL DE CONDUCTOR
========================================================= */

const puedeAsignarManual = (solicitud) => {
  const estado = String(
    solicitud?.estado || ''
  )
    .trim()
    .toLowerCase()

  return [
    'pendiente',
    'buscando conductor'
  ].includes(estado)
}

const obtenerNombreConductorAsignacion = (
  conductor
) => {
  const persona = conductor?.persona || {}

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
    || conductor?.nombre
    || `Conductor #${conductor?.id}`
  )
}

const textoDistanciaConductor = (
  conductor
) => {
  const distancia = Number.parseFloat(
    conductor?.distancia_recogida_km
  )

  return Number.isFinite(distancia)
    ? `${distancia.toFixed(2)} km del pasajero`
    : 'Distancia no disponible'
}

const textoUltimaConexionConductor = (
  conductor
) => {
  const minutos = Number(
    conductor?.minutos_sin_conexion
  )

  if (!Number.isFinite(minutos)) {
    return 'Sin hora de conexión'
  }

  if (minutos <= 0) {
    return 'Conectado ahora'
  }

  return `Última conexión hace ${minutos} min`
}

const colorGpsConductor = (estado) => {
  if (estado === 'actualizado') {
    return 'green-1'
  }

  if (estado === 'atencion') {
    return 'orange-8'
  }

  if (estado === 'desactualizado') {
    return 'negative'
  }

  return 'grey-6'
}

const iconoGpsConductor = (estado) => {
  if (estado === 'actualizado') {
    return 'gps_fixed'
  }

  if (estado === 'atencion') {
    return 'gps_not_fixed'
  }

  if (estado === 'desactualizado') {
    return 'location_disabled'
  }

  return 'location_off'
}

const textoGpsConductor = (conductor) => {
  const estado = conductor?.estado_gps

  if (estado === 'actualizado') {
    return 'GPS actualizado'
  }

  if (estado === 'atencion') {
    return 'GPS necesita actualizarse'
  }

  if (estado === 'desactualizado') {
    return 'GPS desactualizado'
  }

  return 'Sin ubicación GPS'
}

const cerrarAsignacionManual = () => {
  if (asignandoConductor.value) {
    return
  }

  asignacionDialogOpen.value = false
  solicitudAsignacion.value = null
  conductoresAsignacion.value = []
  conductorSeleccionadoId.value = null
}

const abrirAsignacionManual = async (
  solicitud
) => {
  if (!puedeAsignarManual(solicitud)) {
    $q.notify({
      type: 'warning',
      message:
        'Esta solicitud ya no permite asignación manual.'
    })

    return
  }

  solicitudAsignacion.value = solicitud
  conductoresAsignacion.value = []
  conductorSeleccionadoId.value = null
  asignacionDialogOpen.value = true
  cargandoConductores.value = true

  try {
    const respuesta = await api.get(
      `/solicitudes/${solicitud.id}`
      + '/conductores-disponibles'
    )

    const conductores =
      respuesta?.data?.conductores

    conductoresAsignacion.value =
      Array.isArray(conductores)
        ? conductores
        : []

    if (conductoresAsignacion.value.length) {
      conductorSeleccionadoId.value =
        conductoresAsignacion.value[0].id
    }
  } catch (error) {
    console.error(
      'Error cargando conductores:',
      error
    )

    const mensaje = (
      error?.response?.data?.mensaje
      || error?.response?.data?.message
      || 'No se pudieron cargar los conductores disponibles.'
    )

    $q.notify({
      type: 'negative',
      message: mensaje
    })

    cerrarAsignacionManual()
  } finally {
    cargandoConductores.value = false
  }
}

const confirmarAsignacionManual = async () => {
  if (
    !solicitudAsignacion.value?.id
    || !conductorSeleccionadoId.value
  ) {
    return
  }

  const conductor =
    conductoresAsignacion.value.find(
      (registro) => {
        return (
          Number(registro.id)
            === Number(
              conductorSeleccionadoId.value
            )
        )
      }
    )

  if (!conductor) {
    return
  }

  const nombre =
    obtenerNombreConductorAsignacion(
      conductor
    )

  $q.dialog({
    title: 'Confirmar asignación',
    message:
      `¿Asignar la solicitud #${solicitudAsignacion.value.id} `
      + `a ${nombre}?`,
    cancel: {
      label: 'Cancelar',
      flat: true,
      color: 'grey-7'
    },
    ok: {
      label: 'Asignar',
      color: 'positive'
    },
    persistent: true
  }).onOk(async () => {
    asignandoConductor.value = true

    try {
      const respuesta = await api.post(
        `/solicitudes/${solicitudAsignacion.value.id}`
        + '/asignar-manualmente',
        {
          mototaxista_id:
            conductorSeleccionadoId.value
        }
      )

      asignacionDialogOpen.value = false

      await cargarSolicitudes()

      $q.notify({
        type: 'positive',
        icon: 'assignment_turned_in',
        message: (
          respuesta?.data?.mensaje
          || 'Conductor asignado correctamente.'
        ),
        timeout: 4500
      })

      solicitudAsignacion.value = null
      conductoresAsignacion.value = []
      conductorSeleccionadoId.value = null
    } catch (error) {
      console.error(
        'Error asignando conductor:',
        error
      )

      const mensaje = (
        error?.response?.data?.mensaje
        || error?.response?.data?.message
        || 'No se pudo asignar el conductor.'
      )

      $q.notify({
        type: 'negative',
        message: mensaje
      })
    } finally {
      asignandoConductor.value = false
    }
  })
}

/* =========================================================
   ABRIR FORMULARIO
========================================================= */

const openDialogForm = (solicitud = null) => {
  origenCoords.value = null
  destinoCoords.value = null
  distanciaKm.value = 0

  if (solicitud) {
    isEditing.value = true

    form.value = {
      id: solicitud.id,
      origen: solicitud.origen || '',
      destino: solicitud.destino || '',
      fecha: solicitud.fecha
        ? String(solicitud.fecha).slice(0, 10)
        : obtenerFechaActual(),
      estado: solicitud.estado || 'Pendiente',
      id_pasajero: getPasajeroId(solicitud),
      precio: Number.parseFloat(solicitud.precio) || 8
    }

    const latOrigen = Number.parseFloat(
      solicitud.latitud_origen
    )

    const lngOrigen = Number.parseFloat(
      solicitud.longitud_origen
    )

    const latDestino = Number.parseFloat(
      solicitud.latitud_destino
    )

    const lngDestino = Number.parseFloat(
      solicitud.longitud_destino
    )

    if (
      Number.isFinite(latOrigen)
      && Number.isFinite(lngOrigen)
    ) {
      origenCoords.value = [
        latOrigen,
        lngOrigen
      ]
    }

    if (
      Number.isFinite(latDestino)
      && Number.isFinite(lngDestino)
    ) {
      destinoCoords.value = [
        latDestino,
        lngDestino
      ]
    }

    distanciaKm.value = (
      Number.parseFloat(solicitud.distancia_km)
      || 0
    )
  } else {
    isEditing.value = false
    form.value = crearFormularioDefault()
  }

  dialogOpen.value = true
}

/* =========================================================
   GUARDAR SOLICITUD
========================================================= */

const saveSolicitud = async () => {
  if (
    !form.value.origen
    || !form.value.destino
    || !form.value.fecha
    || !form.value.id_pasajero
  ) {
    $q.notify({
      type: 'negative',
      message: 'Complete todos los campos requeridos.'
    })

    return
  }

  if (
    !origenCoords.value
    || !destinoCoords.value
  ) {
    $q.notify({
      type: 'negative',
      message:
        'Marque en el mapa el origen y el destino del viaje.'
    })

    return
  }

  if (distanciaKm.value <= 0) {
    $q.notify({
      type: 'negative',
      message:
        'No se pudo calcular la distancia. Vuelva a marcar la ruta.'
    })

    return
  }

  const payload = {
    origen: form.value.origen.trim(),
    latitud_origen: origenCoords.value[0],
    longitud_origen: origenCoords.value[1],

    destino: form.value.destino.trim(),
    latitud_destino: destinoCoords.value[0],
    longitud_destino: destinoCoords.value[1],

    fecha: form.value.fecha,
    estado: form.value.estado,
    id_pasajero: form.value.id_pasajero,

    precio: Number.parseFloat(form.value.precio) || 8,
    distancia_km: Number.parseFloat(distanciaKm.value) || 0
  }

  saving.value = true

  try {
    if (isEditing.value) {
      await solicitudService.update(
        form.value.id,
        payload
      )
    } else {
      await solicitudService.create(payload)
    }

    dialogOpen.value = false

    await cargarDatos()

    $q.notify({
      type: 'positive',
      message: isEditing.value
        ? 'Solicitud actualizada correctamente.'
        : 'Solicitud creada correctamente.'
    })
  } catch (error) {
    console.error(
      'Error guardando solicitud:',
      error
    )

    const mensajeError = (
      error?.response?.data?.message
      || 'Error de servidor al guardar la solicitud.'
    )

    $q.notify({
      type: 'negative',
      message: mensajeError
    })
  } finally {
    saving.value = false
  }
}

/* =========================================================
   ELIMINAR SOLICITUD
========================================================= */

const confirmDelete = (solicitud) => {
  $q.dialog({
    title: 'Eliminar solicitud',
    message:
      `¿Está seguro de eliminar la solicitud #${solicitud.id}?`,
    cancel: {
      label: 'Cancelar',
      flat: true,
      color: 'grey-7'
    },
    ok: {
      label: 'Eliminar',
      color: 'negative'
    },
    persistent: true
  }).onOk(async () => {
    try {
      await solicitudService.delete(solicitud.id)

      await cargarDatos()

      $q.notify({
        type: 'positive',
        message: 'Solicitud eliminada correctamente.'
      })
    } catch (error) {
      console.error(
        'Error eliminando solicitud:',
        error
      )

      $q.notify({
        type: 'negative',
        message: 'No se pudo eliminar la solicitud.'
      })
    }
  })
}

/* =========================================================
   MONTAJE
========================================================= */

onMounted(async () => {
  window.addEventListener(
    'motrix:solicitud-cambio',
    manejarCambioSolicitudTiempoReal
  )

  await cargarDatos()

  /*
   * El conductor puede aceptar, iniciar o finalizar
   * un viaje desde otra ventana o dispositivo.
   * Por eso el administrador vuelve a consultar
   * las solicitudes cada 5 segundos.
   */
  intervaloActualizacionSolicitudes =
    window.setInterval(
      actualizarSolicitudesSilenciosamente,
      5000
    )
})

onBeforeUnmount(() => {
  detenerRespaldoConversacion()
  desconectarConversacionTiempoReal()

  window.removeEventListener(
    'motrix:solicitud-cambio',
    manejarCambioSolicitudTiempoReal
  )

  if (temporizadorEventoTiempoReal) {
    window.clearTimeout(
      temporizadorEventoTiempoReal
    )

    temporizadorEventoTiempoReal = null
  }

  if (intervaloActualizacionSolicitudes) {
    window.clearInterval(
      intervaloActualizacionSolicitudes
    )

    intervaloActualizacionSolicitudes = null
  }
})
</script>

<style scoped>
.border-radius-md {
  border-radius: 12px;
}

.border-radius-sm {
  border-radius: 8px;
}

.italic {
  font-style: italic;
}

.overflow-hidden {
  overflow: hidden;
}

.min-width-zero {
  min-width: 0;
}

.text-wrap {
  white-space: normal;
  overflow-wrap: anywhere;
  word-break: break-word;
}

.solicitudes-search {
  width: 280px;
}

.solicitud-mobile-card {
  border-left: 4px solid var(--q-positive);
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease;
}

.solicitud-mobile-card:hover {
  transform: translateY(-2px);
  box-shadow:
    0 4px 12px rgba(0, 0, 0, 0.12);
}

.route-line {
  display: grid;
  grid-template-columns: 24px minmax(0, 1fr);
  gap: 8px;
  align-items: start;
}

.solicitud-dialog {
  width: 900px;
  max-width: 95vw;
}

.map-container {
  position: relative;
  height: 380px;
  border: 1px solid #cccccc;
  background: #eeeeee;
}

.asignacion-dialog {
  width: 760px;
  max-width: 96vw;
  max-height: 90vh;
}

.asignacion-contenido {
  position: relative;
  min-height: 230px;
  max-height: 52vh;
  overflow-y: auto;
}

.resumen-solicitud-asignacion {
  border-color: #d8e5d5;
  border-radius: 10px;
  background: #f8fbf6;
}

.lista-conductores-asignacion {
  border-color: #dce6d9;
  border-radius: 10px;
  overflow: hidden;
}

.conductor-asignacion-activo {
  color: #174f22;
  background: #e9f7e7;
  box-shadow:
    inset 4px 0 0 var(--q-positive);
}

.historial-dialog {
  width: 720px;
  max-width: 95vw;
  max-height: 88vh;
}

.historial-contenido {
  max-height: 62vh;
  overflow-y: auto;
}

.historial-item {
  align-items: flex-start;
}


.conversacion-admin-card {
  width: 860px;
  max-width: 96vw;
  height: 760px;
  max-height: 92vh;
  border-radius: 18px;
  overflow: hidden;
}

.conversacion-resumen-item {
  display: grid;
  grid-template-columns: 28px minmax(0, 1fr);
  gap: 8px;
  align-items: center;
  height: 100%;
  min-height: 58px;
  padding: 9px 10px;
  border: 1px solid #dde5ec;
  border-radius: 10px;
  background: #ffffff;
}

.conversacion-admin-mensajes {
  min-height: 280px;
  overflow-y: auto;
  padding: 18px;
  background: #edf2f7;
}

.conversacion-vacia {
  min-height: 100%;
  padding: 48px 20px;
}

.conversacion-mensaje-fila {
  display: flex;
  width: 100%;
  margin-bottom: 12px;
}

.conversacion-fila-pasajero {
  justify-content: flex-start;
}

.conversacion-fila-conductor {
  justify-content: flex-end;
}

.conversacion-fila-admin {
  justify-content: center;
}

.conversacion-mensaje-burbuja {
  width: fit-content;
  max-width: min(72%, 590px);
  padding: 10px 12px 8px;
  border-radius: 14px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
}

.conversacion-burbuja-pasajero {
  color: #263238;
  background: #ffffff;
  border-top-left-radius: 4px;
}

.conversacion-burbuja-conductor {
  color: #163e23;
  background: #d9f7df;
  border-top-right-radius: 4px;
}

.conversacion-burbuja-admin {
  color: #3f2d00;
  background: #fff2c2;
}

.conversacion-mensaje-texto {
  white-space: pre-wrap;
  overflow-wrap: anywhere;
  word-break: break-word;
}

.conversacion-mensaje-hora {
  margin-top: 5px;
  text-align: right;
  opacity: 0.72;
}

@media (max-width: 599px) {
  .solicitudes-search {
    width: 100%;
  }

  .solicitud-dialog {
    width: 100%;
    max-width: 100vw;
  }

  .map-container {
    height: 300px;
  }

  .asignacion-dialog {
    width: 100%;
    max-width: 100vw;
    max-height: 94vh;
  }

  .asignacion-contenido {
    max-height: 58vh;
  }

  .historial-dialog {
    width: 100%;
    max-width: 100vw;
    max-height: 92vh;
  }

  .historial-contenido {
    max-height: 68vh;
  }

  .historial-item {
    padding-left: 12px;
    padding-right: 12px;
  }

  .conversacion-admin-card {
    width: 100%;
    max-width: 100vw;
    height: 100%;
    max-height: 100vh;
    border-radius: 0;
  }

  .conversacion-admin-mensajes {
    padding: 12px;
  }

  .conversacion-mensaje-burbuja {
    max-width: 88%;
  }
}

@media (min-width: 600px) and (max-width: 1439px) {
  .map-container {
    height: 350px;
  }
}
</style>