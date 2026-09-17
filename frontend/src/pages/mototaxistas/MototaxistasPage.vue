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
              {{ estadisticas.total }}
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
          class="mototaxista-card full-height cursor-pointer"
          @click="abrirDetalle(m)"
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

                <q-badge
                  :color="colorEstadoSindical(m.estado_sindical)"
                >
                  {{ m.estado_sindical || 'No habilitado' }}
                </q-badge>
              </div>
            </div>

            <q-btn
              flat
              round
              dense
              icon="more_vert"
              color="grey-7"
              @click.stop
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
                    v-if="puedeGestionarHabilitacion"
                    clickable
                    v-close-popup
                    @click="abrirHabilitacionSindical(m)"
                  >
                    <q-item-section avatar>
                      <q-icon name="fact_check" color="orange-9" />
                    </q-item-section>
                    <q-item-section>Habilitación sindical</q-item-section>
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
      v-if="
        !loading
        && mototaxistasFiltrados.length
        && pagination.lastPage > 1
      "
      class="row justify-center q-py-lg"
    >
      <q-pagination
        v-model="pagination.page"
        :max="pagination.lastPage"
        :max-pages="7"
        boundary-numbers
        direction-links
        color="green-8"
        @update:model-value="cargarTodo"
      />
    </div>

    <div
      v-else-if="!loading && !mototaxistasFiltrados.length"
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
                  fill-input
                  hide-selected
                  clearable
                  input-debounce="300"
                  outlined
                  label="Persona *"
                  :hint="
                    form.id_persona
                      ? undefined
                      : 'Escribe al menos 2 caracteres del nombre, apellido o CI.'
                  "
                  hide-bottom-space
                  :disable="editando"
                  :loading="buscandoPersonas"
                  :rules="[requerido]"
                  @filter="filtrarPersonas"
                  @update:model-value="alCambiarPersona"
                >
                  <template #prepend>
                    <q-icon name="person_search" color="green-8" />
                  </template>

                  <template #selected-item="scope">
                    <div class="persona-seleccionada q-py-xs">
                      <div class="persona-seleccionada__nombre">
                        {{ formatearNombrePersona(scope.opt) }}
                      </div>

                      <div class="persona-seleccionada__ci">
                        CI {{ scope.opt?.ci || 'no registrado' }}
                      </div>
                    </div>
                  </template>

                  <template #option="scope">
                    <q-item v-bind="scope.itemProps">
                      <q-item-section avatar>
                        <q-avatar
                          color="green-1"
                          text-color="green-9"
                          icon="person"
                        />
                      </q-item-section>

                      <q-item-section>
                        <q-item-label class="text-weight-medium">
                          {{ formatearNombrePersona(scope.opt) }}
                        </q-item-label>

                        <q-item-label caption>
                          CI {{ scope.opt.ci || 'no registrado' }}
                        </q-item-label>
                      </q-item-section>
                    </q-item>
                  </template>

                  <template #no-option>
                    <q-item>
                      <q-item-section class="text-grey-7">
                        {{ mensajeBusquedaPersona }}
                      </q-item-section>
                    </q-item>
                  </template>
                </q-select>
              </div>

              <div
                v-if="requiereCiAfiliacion"
                class="col-12"
              >
                <q-input
                  v-model.trim="form.ci"
                  outlined
                  label="Cédula de identidad *"
                  maxlength="20"
                  :rules="[requerido]"
                  hint="Obligatorio para afiliar a esta persona como mototaxista."
                >
                  <template #prepend>
                    <q-icon name="badge" color="green-8" />
                  </template>
                </q-input>
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

    <!-- DETALLE / CREDENCIAL -->
    <q-dialog v-model="dialogDetalle">
      <q-card class="credencial-dialog">
        <q-card-section class="bg-green-8 text-white row items-center">
          <q-icon name="badge" size="30px" class="q-mr-sm" />
          <div>
            <div class="text-h6 text-weight-bold">
              Credencial del mototaxista
            </div>
            <div class="text-caption text-green-1">
              Identificación, motocicleta registrada y código QR.
            </div>
          </div>
          <q-space />
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>

        <q-card-section v-if="seleccionado" class="q-pa-lg">
          <div class="credencial-motrix">
            <div class="credencial-encabezado row items-center">
              <div>
                <div class="text-overline text-green-8 text-weight-bold">
                  MOTRIX · CONDUCTOR REGISTRADO
                </div>
                <div class="text-caption text-grey-7">
                  Sistema de Gestión y Solicitud de Mototaxis
                </div>
              </div>
              <q-space />
              <q-chip
                dense
                :color="seleccionado.estado === 'Activo' ? 'green-1' : 'red-1'"
                :text-color="seleccionado.estado === 'Activo' ? 'green-9' : 'red-9'"
                icon="verified_user"
              >
                {{ seleccionado.estado || 'Sin estado' }}
              </q-chip>
            </div>

            <q-separator class="q-my-md" />

            <div class="row q-col-gutter-lg items-stretch">
              <!-- FOTO Y DATOS DEL CONDUCTOR -->
              <div class="col-12 col-md-7">
                <div class="row q-col-gutter-md">
                  <div class="col-12 col-sm-auto text-center">
                    <q-avatar
                      size="128px"
                      color="green-1"
                      text-color="green-9"
                      class="credencial-foto"
                    >
                      <img
                        v-if="fotoUrl(seleccionado)"
                        :src="fotoUrl(seleccionado)"
                        alt="Fotografía del mototaxista"
                      >
                      <span v-else class="text-h4">
                        {{ iniciales(seleccionado) }}
                      </span>
                    </q-avatar>
                  </div>

                  <div class="col min-width-zero">
                    <div class="text-h5 text-weight-bold text-grey-9">
                      {{ nombreCompleto(seleccionado) }}
                    </div>

                    <div class="text-body2 q-mt-sm">
                      <strong>CI:</strong>
                      {{ seleccionado.persona?.ci || '—' }}
                    </div>
                    <div class="text-body2">
                      <strong>Teléfono:</strong>
                      {{ seleccionado.telefono || seleccionado.persona?.telefono || '—' }}
                    </div>
                    <div class="text-body2">
                      <strong>Sindicato:</strong>
                      {{ seleccionado.sindicato?.nombre || '—' }}
                    </div>
                    <div class="text-body2">
                      <strong>Federación:</strong>
                      {{ seleccionado.sindicato?.federacion_relacion?.nombre || '—' }}
                    </div>
                    <div class="text-body2">
                      <strong>N.º de chaleco:</strong>
                      {{ seleccionado.nro_chaleco || '—' }}
                    </div>
                    <div class="text-body2">
                      <strong>Cuenta conductor:</strong>
                      {{
                        seleccionado.usuario_conductor
                          ? (seleccionado.usuario_conductor.nickname
                            || seleccionado.telefono
                            || seleccionado.persona?.telefono
                            || 'Cuenta creada')
                          : 'No creada'
                      }}
                    </div>
                    <div class="text-body2 q-mt-xs">
                      <strong>Estado sindical:</strong>
                      <q-badge :color="colorEstadoSindical(seleccionado.estado_sindical)" class="q-ml-xs">
                        {{ seleccionado.estado_sindical || 'No habilitado' }}
                      </q-badge>
                    </div>
                    <div class="text-caption text-grey-7 q-mt-xs">
                      Documentación: {{ seleccionado.documentacion_en_regla ? 'En regla' : 'Incompleta' }} ·
                      Aportes: {{ seleccionado.aportes_al_dia ? 'Al día' : 'Pendientes' }}
                    </div>
                    <div v-if="seleccionado.motivo_inhabilitacion || seleccionado.motivo_estado_sindical" class="text-caption text-orange-10 q-mt-xs">
                      {{ seleccionado.motivo_inhabilitacion || seleccionado.motivo_estado_sindical }}
                    </div>
                  </div>
                </div>

                <q-separator class="q-my-md" />

                <!-- MOTOCICLETA -->
                <div class="text-subtitle2 text-weight-bold text-green-9 q-mb-sm">
                  <q-icon name="two_wheeler" class="q-mr-xs" />
                  Motocicleta registrada
                </div>

                <div
                  v-if="motocicletaPrincipal(seleccionado)"
                  class="moto-credencial row no-wrap items-center"
                >
                  <q-img
                    v-if="fotoMotoUrl(seleccionado)"
                    :src="fotoMotoUrl(seleccionado)"
                    width="150px"
                    height="105px"
                    fit="cover"
                    class="moto-credencial-img"
                  />
                  <div
                    v-else
                    class="moto-credencial-placeholder column items-center justify-center"
                  >
                    <q-icon name="two_wheeler" size="52px" color="green-7" />
                  </div>

                  <div class="q-ml-md min-width-zero">
                    <div class="text-subtitle1 text-weight-bold ellipsis">
                      {{ motocicletaPrincipal(seleccionado)?.modelo || 'Motocicleta' }}
                    </div>
                    <div class="text-body2 text-grey-8">
                      Color: {{ motocicletaPrincipal(seleccionado)?.color || '—' }}
                    </div>
                    <div class="row q-gutter-xs q-mt-xs">
                      <q-badge
                        :color="motocicletaTienePlaca(motocicletaPrincipal(seleccionado)) ? 'blue-8' : 'grey-7'"
                      >
                        {{
                          motocicletaTienePlaca(motocicletaPrincipal(seleccionado))
                            ? (motocicletaPrincipal(seleccionado)?.placa || 'Con placa')
                            : 'Sin placa'
                        }}
                      </q-badge>
                      <q-badge
                        :color="motocicletaPrincipal(seleccionado)?.tiene_soat ? 'positive' : 'negative'"
                      >
                        {{ motocicletaPrincipal(seleccionado)?.tiene_soat ? 'SOAT: Sí' : 'SOAT: No' }}
                      </q-badge>
                    </div>
                  </div>
                </div>

                <q-banner
                  v-else
                  rounded
                  class="bg-orange-1 text-orange-10"
                >
                  Este mototaxista todavía no tiene una motocicleta registrada.
                </q-banner>
              </div>

              <!-- QR -->
              <div class="col-12 col-md-5">
                <div class="qr-credencial column items-center justify-center full-height">
                  <div class="text-subtitle2 text-weight-bold text-green-9 q-mb-sm">
                    Verificación pública
                  </div>

                  <img
                    v-if="detalleQrDataUrl"
                    :src="detalleQrDataUrl"
                    alt="Código QR del mototaxista"
                    class="qr-credencial-img"
                  >

                  <div
                    v-else
                    class="column items-center text-grey-6 q-pa-md"
                  >
                    <q-icon name="qr_code_2" size="82px" />
                    <div class="text-caption text-center q-mt-sm">
                      Código QR pendiente
                    </div>
                  </div>

                  <q-btn
                    v-if="!seleccionado.codigo_qr"
                    color="blue-8"
                    icon="qr_code_2"
                    label="Generar QR"
                    no-caps
                    unelevated
                    class="q-mt-sm"
                    :loading="generandoQrDetalle"
                    @click="generarQrDesdeDetalle"
                  />

                  <div
                    v-else
                    class="text-caption text-grey-7 text-center q-mt-sm"
                  >
                    Escanea para verificar la afiliación del conductor.
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- CAMBIAR FOTO DEL CONDUCTOR -->
          <q-expansion-item
            icon="photo_camera"
            label="Cambiar fotografía del mototaxista"
            header-class="text-green-9 text-weight-medium q-mt-md"
            expand-separator
          >
            <q-card flat class="bg-grey-1">
              <q-card-section>
                <div class="row q-col-gutter-md items-end">
                  <div class="col-12 col-md">
                    <q-file
                      v-model="fotoMototaxistaNueva"
                      outlined
                      dense
                      accept=".jpg,.jpeg,.png,.webp"
                      max-file-size="4194304"
                      label="Seleccionar fotografía"
                      @rejected="fotoMototaxistaRechazada"
                    >
                      <template #prepend>
                        <q-icon name="image" color="green-8" />
                      </template>
                    </q-file>
                  </div>
                  <div class="col-12 col-md-auto">
                    <q-btn
                      color="green-8"
                      icon="cloud_upload"
                      label="Actualizar foto"
                      no-caps
                      unelevated
                      :disable="!fotoMototaxistaNueva"
                      :loading="subiendoFotoDetalle"
                      @click="actualizarFotoMototaxista"
                    />
                  </div>
                </div>
              </q-card-section>
            </q-card>
          </q-expansion-item>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md bg-grey-1">
          <q-btn flat color="grey-7" label="Cerrar" v-close-popup />
          <q-btn
            v-if="puedeGestionarHabilitacion"
            outline
            color="orange-9"
            icon="fact_check"
            label="Habilitación sindical"
            @click="abrirHabilitacionSindical(seleccionado)"
          />
          <q-btn
            color="green-8"
            icon="edit"
            label="Editar datos"
            unelevated
            @click="editarDesdeDetalleMototaxista"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- HABILITACIÓN SINDICAL -->
    <q-dialog v-model="dialogHabilitacion" persistent>
      <q-card class="habilitacion-dialog-card">
        <q-card-section class="bg-orange-9 text-white row items-center">
          <q-icon name="fact_check" size="30px" class="q-mr-sm" />
          <div class="col">
            <div class="text-h6 text-weight-bold">Habilitación sindical</div>
            <div class="text-caption text-orange-1">
              {{ nombreCompleto(seleccionadoHabilitacion) }}
            </div>
          </div>
          <q-btn flat round dense icon="close" :disable="guardandoHabilitacion" @click="dialogHabilitacion = false" />
        </q-card-section>

        <q-card-section class="q-pa-lg habilitacion-dialog-body">
          <q-toggle
            v-model="formHabilitacion.documentacion_en_regla"
            color="green-8"
            label="Documentación en regla"
            class="full-width q-mb-md"
          />
          <q-toggle
            v-model="formHabilitacion.aportes_al_dia"
            color="green-8"
            label="Aportes sindicales al día"
            class="full-width q-mb-md"
          />
          <q-select
            v-model="formHabilitacion.estado_sindical"
            outlined
            label="Estado sindical"
            :options="['Habilitado', 'No habilitado', 'Expulsado']"
            class="q-mb-md"
          />
          <q-input
            v-model.trim="formHabilitacion.motivo"
            outlined
            type="textarea"
            autogrow
            maxlength="255"
            label="Motivo / observación"
            hint="Obligatorio de hecho cuando corresponda explicar una inhabilitación o expulsión."
          />

          <q-banner rounded class="bg-orange-1 text-orange-10 q-mt-md">
            <template #avatar><q-icon name="info" color="orange-9" /></template>
            Para operar en MOTRIX necesita cuenta de conductor, estado administrativo Activo, documentación en regla, aportes al día y estado sindical Habilitado. Un Expulsado no se reactiva automáticamente.
          </q-banner>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md bg-grey-1">
          <q-btn flat label="Cancelar" color="grey-7" :disable="guardandoHabilitacion" @click="dialogHabilitacion = false" />
          <q-btn color="orange-9" icon="save" label="Guardar habilitación" unelevated :loading="guardandoHabilitacion" @click="guardarHabilitacionSindical" />
        </q-card-actions>
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
            v-model.trim="cuenta.telefono"
            outlined
            type="tel"
            label="Número de celular *"
            hint="Este número será el usuario de acceso del mototaxista."
            class="q-mb-md"
            :rules="[
              val => String(val || '').replace(/\D+/g, '').length >= 7 || 'Ingresa un celular válido'
            ]"
          >
            <template #prepend>
              <q-icon name="phone_android" color="purple-7" />
            </template>
          </q-input>

          <q-input
            v-model="cuenta.password"
            outlined
            type="password"
            label="Contraseña *"
            hint="Mínimo 8 caracteres"
            :rules="[
              val => String(val || '').length >= 8 || 'Mínimo 8 caracteres'
            ]"
          >
            <template #prepend>
              <q-icon name="lock" color="purple-7" />
            </template>
          </q-input>

          <q-banner rounded class="bg-purple-1 text-purple-9 q-mt-md">
            El mototaxista debe estar Activo y tener QR generado. El celular será su usuario para ingresar a MOTRIX.
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
  ref,
  watch
} from 'vue'

import { useQuasar } from 'quasar'
import { useRoute, useRouter } from 'vue-router'
import QRCode from 'qrcode'
import { api } from 'src/boot/axios.js'
import { API_ORIGIN } from 'src/config/runtime.js'

const $q = useQuasar()
const router = useRouter()
const route = useRoute()

const rolActual = computed(() => {
  try {
    const usuario = JSON.parse(
      localStorage.getItem('motrix_user') || 'null'
    )

    return String(usuario?.role || '')
      .trim()
      .toLowerCase()
  } catch {
    return ''
  }
})

const esAdminGeneral = computed(() => rolActual.value === 'admin_general')
const esSecretario = computed(() => rolActual.value === 'secretario')
const puedeGestionarHabilitacion = computed(() => (
  esAdminGeneral.value || esSecretario.value
))


const mototaxistas = ref([])
const personasDisponibles = ref([])
const sindicatos = ref([])

const buscandoPersonas = ref(false)
const terminoBusquedaPersona = ref('')
let secuenciaBusquedaPersona = 0

const loading = ref(false)
const saving = ref(false)
const filtro = ref('')
const filtroEstado = ref('Todos')
const filtroSindicato = ref('Todos')

const pagination = ref({
  page: 1,
  rowsPerPage: 12,
  rowsNumber: 0,
  lastPage: 1
})

const estadisticas = ref({
  total: 0,
  activos: 0,
  con_qr: 0,
  con_cuenta: 0
})

let temporizadorFiltros = null

const dialogForm = ref(false)
const editando = ref(false)
const formRef = ref(null)

const dialogDetalle = ref(false)
const seleccionado = ref(null)
const detalleQrDataUrl = ref('')
const generandoQrDetalle = ref(false)
const fotoMototaxistaNueva = ref(null)
const subiendoFotoDetalle = ref(false)

const dialogQr = ref(false)
const seleccionadoQr = ref(null)
const qrDataUrl = ref('')
const qrPublicUrl = ref('')
const generandoImagenQr = ref(false)

const dialogCuenta = ref(false)
const seleccionadoCuenta = ref(null)
const creandoCuenta = ref(false)

const dialogHabilitacion = ref(false)
const guardandoHabilitacion = ref(false)
const seleccionadoHabilitacion = ref(null)
const formHabilitacion = ref({
  documentacion_en_regla: false,
  aportes_al_dia: false,
  estado_sindical: 'No habilitado',
  motivo: ''
})

const formDefault = {
  id: null,
  id_persona: null,
  ci: '',
  id_sindicato: null,
  nro_chaleco: '',
  telefono: '',
  estado: 'Activo'
}

const form = ref({
  ...formDefault
})

const personaSeleccionada = computed(() =>
  personasDisponibles.value.find(
    (persona) => Number(persona?.id) === Number(form.value.id_persona)
  ) || null
)

const requiereCiAfiliacion = computed(() => (
  Boolean(form.value.id_persona)
  && String(personaSeleccionada.value?.ci || '').trim() === ''
))

const mensajeBusquedaPersona = computed(() => {
  const texto = String(
    terminoBusquedaPersona.value || ''
  ).trim()

  if (buscandoPersonas.value) {
    return 'Buscando personas...'
  }

  if (texto.length < 2) {
    return 'Escribe al menos 2 caracteres para buscar.'
  }

  return 'No se encontraron personas disponibles con ese criterio.'
})


const cuenta = ref({
  telefono: '',
  password: ''
})

const requerido = valor =>
  Boolean(
    String(valor ?? '').trim()
  )
  || 'Campo obligatorio'

const totalActivos = computed(
  () => estadisticas.value.activos
)

const totalConQr = computed(
  () => estadisticas.value.con_qr
)

const totalConCuenta = computed(
  () => estadisticas.value.con_cuenta
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

const mototaxistasFiltrados = computed(
  () => mototaxistas.value
)


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

function formatearNombrePersona(p) {
  if (!p) return ''

  return [
    p.nombre,
    p.apellidos
  ]
    .filter(Boolean)
    .join(' ')
    .trim()
    .toLocaleLowerCase('es-BO')
    .replace(
      /(^|[\s-])([a-záéíóúñü])/gu,
      (coincidencia, separador, letra) =>
        `${separador}${letra.toLocaleUpperCase('es-BO')}`
    )
}

function labelPersona(p) {
  if (!p) return ''

  const nombre =
    formatearNombrePersona(p)

  return nombre
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


function motocicletaPrincipal(m) {
  const lista = Array.isArray(m?.motocicletas)
    ? [...m.motocicletas]
    : []

  if (!lista.length) return null

  return lista.sort(
    (a, b) => Number(b?.id || 0) - Number(a?.id || 0)
  )[0]
}

function motocicletaTienePlaca(moto) {
  if (!moto) return false

  if (typeof moto.tiene_placa === 'boolean') {
    return moto.tiene_placa
  }

  return Boolean(String(moto.placa || '').trim())
}

function fotoMotoUrl(m) {
  const moto = motocicletaPrincipal(m)
  const imagenes = Array.isArray(moto?.imagenes)
    ? moto.imagenes
    : []
  const ruta = imagenes[imagenes.length - 1]?.ruta

  if (!ruta) return ''

  if (/^https?:\/\//i.test(ruta)) {
    return ruta
  }

  return `${apiOrigen()}/storage/${String(ruta).replace(/^\/+/, '')}`
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

async function cargarTodo(
  pagina = pagination.value.page,
  cargarCatalogos = false
) {
  loading.value = true

  try {
    const peticiones = [
      api.get(
        '/mototaxistas',
        {
          params: {
            paginated: 1,
            page: pagina,
            per_page:
              pagination.value.rowsPerPage,
            q: String(
              filtro.value || ''
            ).trim() || undefined,
            estado:
              filtroEstado.value !== 'Todos'
                ? filtroEstado.value
                : undefined,
            sindicato:
              filtroSindicato.value !== 'Todos'
                ? filtroSindicato.value
                : undefined
          }
        }
      )
    ]

    if (
      cargarCatalogos
      || sindicatos.value.length === 0
    ) {
      peticiones.push(
        api.get('/sindicatos')
      )
    }

    const respuestas =
      await Promise.all(peticiones)

    const resMototaxistas =
      respuestas[0]

    mototaxistas.value =
      Array.isArray(
        resMototaxistas.data?.data
      )
        ? resMototaxistas.data.data
        : []

    const meta =
      resMototaxistas.data?.meta || {}

    pagination.value.page =
      Number(meta.current_page || pagina)

    pagination.value.lastPage =
      Math.max(
        Number(meta.last_page || 1),
        1
      )

    pagination.value.rowsNumber =
      Number(meta.total || 0)

    estadisticas.value = {
      total:
        Number(meta.stats?.total || 0),
      activos:
        Number(meta.stats?.activos || 0),
      con_qr:
        Number(meta.stats?.con_qr || 0),
      con_cuenta:
        Number(
          meta.stats?.con_cuenta || 0
        )
    }

    personasDisponibles.value = []

    if (respuestas[1]) {
      sindicatos.value =
        Array.isArray(
          respuestas[1].data
        )
          ? respuestas[1].data
          : []
    }
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

async function filtrarPersonas(
  valor,
  update
) {
  const texto = String(
    valor || ''
  ).trim()

  terminoBusquedaPersona.value =
    texto

  if (editando.value) {
    update(() => {})
    return
  }

  if (texto.length < 2) {
    secuenciaBusquedaPersona += 1
    buscandoPersonas.value = false

    update(() => {
      personasDisponibles.value = []
    })

    return
  }

  const secuencia =
    ++secuenciaBusquedaPersona

  buscandoPersonas.value = true

  try {
    const response = await api.get(
      '/personas/opciones-mototaxista',
      {
        params: {
          q: texto
        }
      }
    )

    if (
      secuencia
      !== secuenciaBusquedaPersona
    ) {
      return
    }

    const lista =
      Array.isArray(response.data)
        ? response.data
        : []

    update(() => {
      personasDisponibles.value =
        lista
    })
  } catch (error) {
    if (
      secuencia
      !== secuenciaBusquedaPersona
    ) {
      return
    }

    console.error(
      'Error buscando personas:',
      error
    )

    update(() => {
      personasDisponibles.value = []
    })
  } finally {
    if (
      secuencia
      === secuenciaBusquedaPersona
    ) {
      buscandoPersonas.value = false
    }
  }
}

function alCambiarPersona() {
  form.value.ci = ''
}

function abrirFormulario(m = null) {
  if (m) {
    editando.value = true

    form.value = {
      id: m.id,
      id_persona: m.id_persona,
      ci: '',
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

  secuenciaBusquedaPersona += 1
  terminoBusquedaPersona.value = ''
  buscandoPersonas.value = false

  if (
    editando.value
    && m?.persona
  ) {
    personasDisponibles.value = [
      m.persona
    ]
  } else {
    personasDisponibles.value = []
  }

  dialogForm.value = true
}

function cerrarFormulario() {
  if (saving.value) return

  dialogForm.value = false
  secuenciaBusquedaPersona += 1
  terminoBusquedaPersona.value = ''
  buscandoPersonas.value = false
  personasDisponibles.value = []

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
      ci:
        requiereCiAfiliacion.value
          ? form.value.ci
          : null,
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

function colorEstadoSindical(estado) {
  const valor = String(estado || '').toLowerCase()
  if (valor === 'habilitado') return 'positive'
  if (valor === 'expulsado') return 'negative'
  return 'orange-9'
}

function abrirHabilitacionSindical(m) {
  if (!puedeGestionarHabilitacion.value || !m?.id) return

  seleccionadoHabilitacion.value = m
  formHabilitacion.value = {
    documentacion_en_regla: Boolean(m.documentacion_en_regla),
    aportes_al_dia: Boolean(m.aportes_al_dia),
    estado_sindical: m.estado_sindical || 'No habilitado',
    motivo: m.motivo_estado_sindical || ''
  }
  dialogHabilitacion.value = true
}

async function guardarHabilitacionSindical() {
  const id = seleccionadoHabilitacion.value?.id
  if (!id || guardandoHabilitacion.value) return

  guardandoHabilitacion.value = true
  try {
    const { data } = await api.patch(
      `/mototaxistas/${id}/habilitacion-sindical`,
      formHabilitacion.value
    )

    $q.notify({
      type: 'positive',
      position: 'top',
      message: data?.message || 'Habilitación sindical actualizada.'
    })

    dialogHabilitacion.value = false
    await cargarTodo()

    if (seleccionado.value?.id === id) {
      await abrirDetalle({ id })
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      message: mensajeError(error)
    })
  } finally {
    guardandoHabilitacion.value = false
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
    const respuesta = await api.get(
      `/mototaxistas/${m.id}`
    )

    seleccionado.value = respuesta.data
    fotoMototaxistaNueva.value = null
    detalleQrDataUrl.value = ''

    if (seleccionado.value?.codigo_qr) {
      detalleQrDataUrl.value = await QRCode.toDataURL(
        construirUrlPublicaQr(seleccionado.value.codigo_qr),
        {
          width: 260,
          margin: 2,
          errorCorrectionLevel: 'H'
        }
      )
    }

    dialogDetalle.value = true
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      message: mensajeError(error)
    })
  }
}

function editarDesdeDetalleMototaxista() {
  if (!seleccionado.value) return

  const item = { ...seleccionado.value }
  dialogDetalle.value = false
  abrirFormulario(item)
}

function fotoMototaxistaRechazada() {
  $q.notify({
    type: 'warning',
    position: 'top',
    message: 'Usa una imagen JPG, PNG o WEBP de máximo 4 MB.'
  })
}

async function actualizarFotoMototaxista() {
  const personaId = seleccionado.value?.persona?.id

  if (!personaId || !fotoMototaxistaNueva.value) {
    return
  }

  subiendoFotoDetalle.value = true

  try {
    const formData = new FormData()
    formData.append('imagen', fotoMototaxistaNueva.value)

    await api.post(
      `/personas/${personaId}/imagen`,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      }
    )

    const respuesta = await api.get(
      `/mototaxistas/${seleccionado.value.id}`
    )

    seleccionado.value = respuesta.data
    fotoMototaxistaNueva.value = null
    await cargarTodo()

    $q.notify({
      type: 'positive',
      position: 'top',
      message: 'Fotografía del mototaxista actualizada correctamente.'
    })
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      message: mensajeError(error)
    })
  } finally {
    subiendoFotoDetalle.value = false
  }
}

async function generarQrDesdeDetalle() {
  if (!seleccionado.value?.id) return

  generandoQrDetalle.value = true

  try {
    const respuesta = await api.post(
      `/mototaxistas/${seleccionado.value.id}/generar-qr`
    )

    const codigo = respuesta.data?.codigo_qr
      || respuesta.data?.data?.codigo_qr

    if (!codigo) {
      throw new Error('El backend no devolvió el código QR.')
    }

    const detalle = await api.get(
      `/mototaxistas/${seleccionado.value.id}`
    )

    seleccionado.value = detalle.data
    detalleQrDataUrl.value = await QRCode.toDataURL(
      construirUrlPublicaQr(codigo),
      {
        width: 260,
        margin: 2,
        errorCorrectionLevel: 'H'
      }
    )

    await cargarTodo()

    $q.notify({
      type: 'positive',
      position: 'top',
      message: 'Código QR generado correctamente.'
    })
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      message: error?.message || mensajeError(error)
    })
  } finally {
    generandoQrDetalle.value = false
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
    telefono: m.telefono || m.persona?.telefono || '',
    password: ''
  }

  dialogCuenta.value = true
}

async function crearCuenta() {
  if (
    !seleccionadoCuenta.value?.id
    || !String(cuenta.value.telefono || '').replace(/\D+/g, '')
    || !cuenta.value.password
  ) {
    $q.notify({
      type: 'negative',
      position: 'top',
      message:
        'Celular y contraseña son obligatorios.'
    })

    return
  }

  if (String(cuenta.value.password || '').length < 8) {
    $q.notify({
      type: 'warning',
      position: 'top',
      message: 'La contraseña debe tener al menos 8 caracteres.'
    })
    return
  }

  creandoCuenta.value = true

  try {
    await api.post(
      `/mototaxistas/${seleccionadoCuenta.value.id}/cuenta-conductor-celular`,
      {
        telefono: cuenta.value.telefono,
        password: cuenta.value.password
      }
    )

    $q.notify({
      type: 'positive',
      position: 'top',
      message:
        'Cuenta de conductor creada. El celular es su usuario de acceso.'
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

watch(
  [
    filtro,
    filtroEstado,
    filtroSindicato
  ],
  () => {
    if (temporizadorFiltros) {
      window.clearTimeout(
        temporizadorFiltros
      )
    }

    temporizadorFiltros =
      window.setTimeout(
        () => {
          pagination.value.page = 1
          cargarTodo(1)
        },
        350
      )
  }
)

onMounted(async () => {
  await cargarTodo()

  const sindicatoId = Number(route.query.sindicato || 0)
  if (sindicatoId) {
    const sindicato = sindicatos.value.find(
      item => Number(item.id) === sindicatoId
    )
    if (sindicato?.nombre) {
      filtroSindicato.value = sindicato.nombre
    }
  }
})
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

.habilitacion-dialog-card {
  width: min(94vw, 560px);
  max-width: 94vw;
  max-height: calc(100dvh - 24px);
  display: flex;
  flex-direction: column;
  border-radius: 16px;
  overflow: hidden;
}

.habilitacion-dialog-body {
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
  overscroll-behavior: contain;
}

.habilitacion-dialog-card > .q-card-section:first-child,
.habilitacion-dialog-card > .q-card-actions {
  flex: 0 0 auto;
}

.credencial-dialog {
  width: min(1040px, 96vw);
  max-width: 1040px;
  border-radius: 18px;
  overflow: hidden;
}

.credencial-motrix {
  border: 1px solid #cfe3cc;
  border-radius: 18px;
  padding: 20px;
  background:
    radial-gradient(circle at 92% 10%, rgba(76, 175, 80, 0.12), transparent 30%),
    #ffffff;
  box-shadow: 0 10px 30px rgba(27, 94, 32, 0.08);
}

.credencial-dialog .text-body2 { font-size: 1rem; line-height: 1.7; }
.credencial-dialog .text-h5 { font-size: 1.65rem; }
.credencial-foto {
  border: 4px solid #e8f5e9;
  box-shadow: 0 6px 18px rgba(27, 94, 32, 0.12);
  overflow: hidden;
}

.credencial-foto :deep(img) {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.moto-credencial {
  border: 1px solid #dce8da;
  border-radius: 14px;
  padding: 10px;
  background: #f9fcf6;
}

.moto-credencial-img,
.moto-credencial-placeholder {
  width: 150px;
  min-width: 150px;
  height: 105px;
  border-radius: 12px;
  overflow: hidden;
  background: #e8f5e9;
}

.qr-credencial {
  min-height: 300px;
  border: 1px dashed #a5d6a7;
  border-radius: 16px;
  background: #f7fbf4;
  padding: 18px;
}

.qr-credencial-img {
  width: min(245px, 64vw);
  height: auto;
  padding: 8px;
  background: white;
  border: 1px solid #d7e2ed;
  border-radius: 12px;
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
  .qr-dialog,
  .credencial-dialog {
    width: 100vw;
    max-width: 100vw;
    border-radius: 0;
  }
}


.persona-seleccionada {
  display: flex;
  min-width: 0;
  flex-direction: column;
  line-height: 1.15;
}

.persona-seleccionada__nombre {
  overflow: hidden;
  color: #30353b;
  font-size: 14px;
  font-weight: 600;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.persona-seleccionada__ci {
  margin-top: 4px;
  color: #6b7280;
  font-size: 12px;
  font-weight: 500;
}

</style>
