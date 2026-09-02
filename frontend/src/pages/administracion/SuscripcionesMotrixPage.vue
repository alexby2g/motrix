<template>
  <q-page class="q-pa-md bg-grey-1">
    <div class="row items-center q-col-gutter-md q-mb-lg">
      <div class="col-12 col-md">
        <div class="text-h5 text-weight-bold text-green-9">
          Suscripciones MOTRIX
        </div>
        <div class="text-body2 text-grey-7">
          Control comercial de planes, vigencia, cobranza y liquidación de conductores.
        </div>
      </div>

      <div class="col-12 col-md-auto row q-gutter-sm">
        <q-btn
          v-if="esAdminGeneral"
          outline
          color="green-8"
          icon="workspace_premium"
          label="Gestionar planes"
          no-caps
          @click="abrirNuevoPlan"
        />
        <q-btn
          color="green-8"
          icon="person_add"
          label="Asignar suscripción"
          no-caps
          unelevated
          @click="abrirAsignacion"
        />
        <q-btn
          outline
          color="green-8"
          icon="assessment"
          label="Reportes"
          no-caps
          to="/reportes-suscripciones-motrix"
        />
        <q-btn
          outline
          color="green-8"
          icon="point_of_sale"
          label="Cobranza"
          no-caps
          to="/cobranza-motrix"
        />
        <q-btn
          outline
          color="green-8"
          icon="account_balance"
          label="Liquidaciones"
          no-caps
          to="/liquidaciones-motrix"
        />
      </div>
    </div>

    <q-card flat bordered class="q-mb-lg section-card">
      <q-card-section>
        <div class="row q-col-gutter-md items-end">
          <div class="col-12 col-sm-6 col-md-3">
            <q-input
              v-model="periodo"
              type="month"
              outlined
              dense
              label="Periodo comercial"
              @update:model-value="actualizarPanel"
            >
              <template #prepend>
                <q-icon name="calendar_month" color="green-8" />
              </template>
            </q-input>
          </div>

          <div v-if="esAdminGeneral" class="col-12 col-sm-6 col-md-5">
            <q-select
              v-model="sindicatoSeleccionado"
              :options="opcionesSindicato"
              emit-value
              map-options
              clearable
              outlined
              dense
              label="Sindicato (todos si está vacío)"
              :loading="cargandoSindicatos"
              @update:model-value="cambioSindicato"
            >
              <template #prepend>
                <q-icon name="business" color="green-8" />
              </template>
            </q-select>
          </div>

          <div v-else class="col-12 col-sm-6 col-md-5">
            <q-field outlined dense label="Sindicato" stack-label>
              <template #control>
                <div class="self-center full-width no-outline">
                  {{ usuario?.sindicato_nombre || 'Mi sindicato' }}
                </div>
              </template>
              <template #prepend>
                <q-icon name="business" color="green-8" />
              </template>
            </q-field>
          </div>

          <div class="col-12 col-md">
            <q-btn
              outline
              color="green-8"
              icon="refresh"
              label="Actualizar"
              no-caps
              class="full-width"
              :loading="loadingPanel || loadingLista || cargandoPlanes"
              @click="actualizarTodo"
            />
          </div>
        </div>
      </q-card-section>
    </q-card>

    <div class="row q-col-gutter-md q-mb-lg">
      <div
        v-for="tarjeta in tarjetasResumen"
        :key="tarjeta.etiqueta"
        class="col-12 col-sm-6 col-lg-3"
      >
        <q-card flat bordered class="stat-card">
          <q-card-section class="row items-center no-wrap">
            <q-avatar
              :color="tarjeta.fondo"
              :text-color="tarjeta.color"
              :icon="tarjeta.icono"
            />
            <div class="q-ml-md min-width-zero">
              <div class="text-caption text-grey-7">
                {{ tarjeta.etiqueta }}
              </div>
              <div class="text-h6 text-weight-bold ellipsis">
                {{ tarjeta.valor }}
              </div>
              <div class="text-caption text-grey-6 ellipsis">
                {{ tarjeta.detalle }}
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <q-card flat bordered class="q-mb-lg section-card">
      <q-card-section class="row items-center q-col-gutter-md">
        <div class="col-12 col-md">
          <div class="text-subtitle1 text-weight-bold">
            Gestión comercial
          </div>
          <div class="text-caption text-grey-7">
            Crea el plan comercial y asigna una suscripción a cada mototaxista.
          </div>
        </div>
        <div class="col-12 col-md-auto row q-gutter-sm">
          <q-btn
            v-if="esAdminGeneral"
            outline
            color="green-8"
            icon="add_card"
            label="Nuevo plan"
            no-caps
            @click="abrirNuevoPlan"
          />
          <q-btn
            color="green-8"
            icon="person_add"
            label="Asignar suscripción"
            no-caps
            unelevated
            @click="abrirAsignacion"
          />
        </div>
      </q-card-section>
    </q-card>

    <q-card flat bordered class="q-mb-lg section-card">
      <q-card-section class="row items-center">
        <div>
          <div class="text-subtitle1 text-weight-bold">
            Planes comerciales
          </div>
          <div class="text-caption text-grey-7">
            Precio, duración, gracia y aviso previo configurados en backend.
          </div>
        </div>
        <q-space />
        <q-btn
          v-if="esAdminGeneral"
          flat
          color="green-8"
          icon="refresh"
          label="Actualizar planes"
          no-caps
          :loading="cargandoPlanes"
          @click="cargarPlanes"
        />
      </q-card-section>

      <q-separator />

      <q-card-section v-if="planes.length" class="row q-col-gutter-md">
        <div
          v-for="plan in planes"
          :key="plan.id"
          class="col-12 col-md-6 col-lg-4"
        >
          <q-card flat bordered class="plan-card">
            <q-card-section>
              <div class="row items-start no-wrap">
                <q-avatar
                  :color="plan.activo ? 'green-1' : 'grey-3'"
                  :text-color="plan.activo ? 'green-9' : 'grey-8'"
                  icon="workspace_premium"
                />
                <div class="q-ml-md col min-width-zero">
                  <div class="row items-center no-wrap">
                    <div class="text-subtitle1 text-weight-bold ellipsis">
                      {{ plan.nombre }}
                    </div>
                    <q-space />
                    <q-chip
                      dense
                      :color="plan.activo ? 'green-1' : 'grey-3'"
                      :text-color="plan.activo ? 'green-9' : 'grey-8'"
                      :label="plan.activo ? 'Activo' : 'Inactivo'"
                    />
                  </div>
                  <div class="text-h5 text-green-9 text-weight-bold">
                    {{ dinero(plan.monto) }}
                  </div>
                  <div class="text-caption text-grey-7">
                    {{ plan.duracion_meses }} mes(es) ·
                    {{ plan.dias_gracia }} días de gracia ·
                    aviso {{ plan.aviso_dias_antes }} días antes
                  </div>
                  <div v-if="plan.descripcion" class="text-caption text-grey-7 q-mt-sm">
                    {{ plan.descripcion }}
                  </div>
                </div>
              </div>
            </q-card-section>
            <q-separator v-if="esAdminGeneral" />
            <q-card-actions v-if="esAdminGeneral" align="right">
              <q-btn
                flat
                color="green-8"
                icon="edit"
                label="Editar"
                no-caps
                @click="abrirEditarPlan(plan)"
              />
            </q-card-actions>
          </q-card>
        </div>
      </q-card-section>

      <q-card-section v-else>
        <q-banner rounded class="bg-orange-1 text-orange-10">
          <template #avatar>
            <q-icon name="info" color="orange-9" />
          </template>
          No existe un plan comercial disponible. El Administrador General debe crear el primer plan MOTRIX antes de asignar suscripciones.
          <template v-if="esAdminGeneral" #action>
            <q-btn
              flat
              color="orange-10"
              label="Crear plan"
              no-caps
              @click="abrirNuevoPlan"
            />
          </template>
        </q-banner>
      </q-card-section>
    </q-card>

    <q-card flat bordered class="q-mb-lg section-card">
      <q-card-section>
        <div class="row q-col-gutter-md items-end">
          <div class="col-12 col-md-5">
            <q-input
              v-model="busqueda"
              outlined
              dense
              clearable
              label="Buscar conductor, CI, chaleco o sindicato"
              @keyup.enter="reiniciarLista"
            >
              <template #prepend>
                <q-icon name="search" color="green-8" />
              </template>
            </q-input>
          </div>
          <div class="col-12 col-sm-6 col-md-3">
            <q-select
              v-model="estadoFiltro"
              :options="estados"
              outlined
              dense
              label="Estado"
              @update:model-value="reiniciarLista"
            />
          </div>
          <div class="col-12 col-sm-6 col-md">
            <q-btn
              color="green-8"
              icon="search"
              label="Buscar"
              no-caps
              unelevated
              class="full-width"
              :loading="loadingLista"
              @click="reiniciarLista"
            />
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <q-table
        flat
        row-key="id"
        :rows="suscripciones"
        :columns="columnas"
        :loading="loadingLista"
        v-model:pagination="paginacion"
        binary-state-sort
        no-data-label="No se encontraron suscripciones con estos filtros."
        @request="solicitarPagina"
      >
        <template #body-cell-conductor="props">
          <q-td :props="props">
            <div class="text-weight-medium">
              {{ nombreConductor(props.row) }}
            </div>
            <div class="text-caption text-grey-7">
              CI {{ props.row.mototaxista?.persona?.ci || '—' }} ·
              Chaleco {{ props.row.mototaxista?.nro_chaleco || '—' }}
            </div>
          </q-td>
        </template>

        <template #body-cell-sindicato="props">
          <q-td :props="props">
            {{ props.row.sindicato?.nombre || '—' }}
          </q-td>
        </template>

        <template #body-cell-plan="props">
          <q-td :props="props">
            <div>{{ props.row.plan?.nombre || 'Sin plan' }}</div>
            <div class="text-caption text-grey-7">
              {{ dinero(props.row.plan?.monto) }}
            </div>
          </q-td>
        </template>

        <template #body-cell-estado="props">
          <q-td :props="props">
            <q-chip
              dense
              :color="colorEstado(props.row.estado_calculado || props.row.estado)"
              text-color="white"
              :label="props.row.estado_calculado || props.row.estado"
            />
          </q-td>
        </template>

        <template #body-cell-vencimiento="props">
          <q-td :props="props">
            <div>{{ fecha(props.row.fecha_vencimiento) }}</div>
            <div
              :class="[
                'text-caption',
                Number(props.row.dias_restantes) < 0 ? 'text-negative' : 'text-grey-7'
              ]"
            >
              {{ textoDias(props.row.dias_restantes) }}
            </div>
          </q-td>
        </template>

        <template #body-cell-acciones="props">
          <q-td :props="props">
            <q-btn
              flat
              round
              dense
              color="blue-8"
              icon="visibility"
              @click="abrirDetalle(props.row)"
            >
              <q-tooltip>Ver detalle e historial</q-tooltip>
            </q-btn>
            <q-btn
              flat
              round
              dense
              color="green-8"
              icon="event_repeat"
              @click="prepararRenovacion(props.row)"
            >
              <q-tooltip>Preparar renovación</q-tooltip>
            </q-btn>
          </q-td>
        </template>
      </q-table>
    </q-card>

    <q-card
      v-if="esAdminGeneral && panel.sindicatos?.length"
      flat
      bordered
      class="section-card"
    >
      <q-card-section>
        <div class="text-subtitle1 text-weight-bold">
          Comparativo por sindicato
        </div>
        <div class="text-caption text-grey-7">
          Recaudación y estado comercial del periodo {{ periodo }}.
        </div>
      </q-card-section>
      <q-separator />
      <q-table
        flat
        row-key="id_sindicato"
        :rows="panel.sindicatos || []"
        :columns="columnasSindicatos"
        :pagination="{ rowsPerPage: 10 }"
      >
        <template #body-cell-recaudado="props">
          <q-td :props="props">
            {{ dinero(props.row.cobranza?.recaudado_sindicato) }}
          </q-td>
        </template>
        <template #body-cell-pendiente="props">
          <q-td :props="props">
            {{ dinero(props.row.cobranza?.pendiente_cobro) }}
          </q-td>
        </template>
        <template #body-cell-liquidar="props">
          <q-td :props="props">
            {{ dinero(props.row.liquidaciones?.pendiente_liquidar) }}
          </q-td>
        </template>
      </q-table>
    </q-card>

    <!-- DETALLE -->
    <q-dialog v-model="dialogDetalle">
      <q-card class="dialog-card">
        <q-card-section class="bg-green-8 text-white row items-center">
          <div>
            <div class="text-h6 text-weight-bold">Detalle de suscripción</div>
            <div class="text-caption text-green-1">
              {{ detalle ? nombreConductor(detalle) : '' }}
            </div>
          </div>
          <q-space />
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-card-section v-if="detalle">
          <div class="row q-col-gutter-md q-mb-md">
            <div class="col-6 col-sm-3">
              <div class="text-caption text-grey-7">Plan</div>
              <div class="text-weight-bold">{{ detalle.plan?.nombre || '—' }}</div>
            </div>
            <div class="col-6 col-sm-3">
              <div class="text-caption text-grey-7">Cuota</div>
              <div class="text-weight-bold">{{ dinero(detalle.plan?.monto) }}</div>
            </div>
            <div class="col-6 col-sm-3">
              <div class="text-caption text-grey-7">Estado</div>
              <div class="text-weight-bold">
                {{ detalle.estado_calculado || detalle.estado }}
              </div>
            </div>
            <div class="col-6 col-sm-3">
              <div class="text-caption text-grey-7">Vencimiento</div>
              <div class="text-weight-bold">{{ fecha(detalle.fecha_vencimiento) }}</div>
            </div>
          </div>
          <q-table
            flat
            bordered
            dense
            row-key="id"
            :rows="detalle.pagos || []"
            :columns="columnasPagos"
            :pagination="{ rowsPerPage: 8 }"
            no-data-label="No hay pagos o cuotas generadas."
          >
            <template #body-cell-monto="props">
              <q-td :props="props">{{ dinero(props.row.monto_esperado) }}</q-td>
            </template>
            <template #body-cell-estado="props">
              <q-td :props="props">
                <q-chip
                  dense
                  :color="colorPago(props.row.estado)"
                  text-color="white"
                  :label="props.row.estado"
                />
              </q-td>
            </template>
          </q-table>
        </q-card-section>
        <q-inner-loading :showing="cargandoDetalle">
          <q-spinner-dots size="42px" color="green-8" />
        </q-inner-loading>
      </q-card>
    </q-dialog>

    <!-- CREAR / EDITAR PLAN -->
    <q-dialog v-model="dialogPlan" persistent>
      <q-card class="dialog-form-card">
        <q-card-section class="bg-green-8 text-white row items-center">
          <div>
            <div class="text-h6 text-weight-bold">
              {{ planEditandoId ? 'Editar plan MOTRIX' : 'Nuevo plan MOTRIX' }}
            </div>
            <div class="text-caption text-green-1">
              Configuración comercial central administrada por MOTRIX.
            </div>
          </div>
          <q-space />
          <q-btn flat round dense icon="close" :disable="guardandoPlan" v-close-popup />
        </q-card-section>

        <q-form @submit.prevent="guardarPlan">
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <q-input
                  v-model="formPlan.nombre"
                  outlined
                  dense
                  label="Nombre del plan *"
                  maxlength="100"
                  :rules="[valor => !!String(valor || '').trim() || 'Ingresa el nombre del plan']"
                />
              </div>
              <div class="col-12">
                <q-input
                  v-model="formPlan.descripcion"
                  outlined
                  dense
                  type="textarea"
                  autogrow
                  label="Descripción"
                  maxlength="500"
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="formPlan.monto"
                  outlined
                  dense
                  type="number"
                  min="0.01"
                  step="0.01"
                  label="Cuota en Bs. *"
                  prefix="Bs."
                  :rules="[valor => Number(valor) > 0 || 'La cuota debe ser mayor a 0']"
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="formPlan.duracion_meses"
                  outlined
                  dense
                  type="number"
                  min="1"
                  max="24"
                  label="Duración en meses *"
                  :rules="[valor => Number(valor) >= 1 || 'Mínimo 1 mes']"
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="formPlan.dias_gracia"
                  outlined
                  dense
                  type="number"
                  min="0"
                  max="30"
                  label="Días de gracia *"
                  :rules="[valor => Number(valor) >= 0 || 'Ingresa los días de gracia']"
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="formPlan.aviso_dias_antes"
                  outlined
                  dense
                  type="number"
                  min="0"
                  max="30"
                  label="Avisar días antes *"
                  :rules="[valor => Number(valor) >= 0 || 'Ingresa los días de aviso']"
                />
              </div>
              <div class="col-12">
                <q-toggle
                  v-model="formPlan.activo"
                  color="green-8"
                  label="Plan activo y disponible para nuevas suscripciones"
                />
              </div>
            </div>
          </q-card-section>

          <q-separator />
          <q-card-actions align="right" class="q-pa-md">
            <q-btn flat label="Cancelar" no-caps :disable="guardandoPlan" v-close-popup />
            <q-btn
              color="green-8"
              icon="save"
              label="Guardar plan"
              no-caps
              unelevated
              type="submit"
              :loading="guardandoPlan"
            />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- ASIGNAR SUSCRIPCIÓN -->
    <q-dialog v-model="dialogAsignacion" persistent>
      <q-card class="dialog-form-card">
        <q-card-section class="bg-green-8 text-white row items-center">
          <div>
            <div class="text-h6 text-weight-bold">Asignar suscripción MOTRIX</div>
            <div class="text-caption text-green-1">
              Vincula un mototaxista activo con un plan comercial.
            </div>
          </div>
          <q-space />
          <q-btn flat round dense icon="close" :disable="guardandoAsignacion" v-close-popup />
        </q-card-section>

        <q-banner v-if="!planesActivos.length" class="bg-orange-1 text-orange-10 q-ma-md q-mb-none" rounded>
          No existe un plan activo. Primero crea o activa un plan MOTRIX.
        </q-banner>

        <q-form @submit.prevent="guardarAsignacion">
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <q-select
                  v-model="formAsignacion.id_mototaxista"
                  :options="opcionesMototaxistas"
                  emit-value
                  map-options
                  use-input
                  clearable
                  outlined
                  dense
                  input-debounce="350"
                  label="Buscar mototaxista *"
                  hint="Escribe al menos 2 caracteres: nombre, CI, chaleco o teléfono."
                  :loading="buscandoMototaxistas"
                  :rules="[valor => !!valor || 'Selecciona un mototaxista']"
                  @filter="filtrarMototaxistas"
                >
                  <template #prepend>
                    <q-icon name="two_wheeler" color="green-8" />
                  </template>
                  <template #no-option>
                    <q-item>
                      <q-item-section class="text-grey">
                        Escribe al menos 2 caracteres para buscar.
                      </q-item-section>
                    </q-item>
                  </template>
                </q-select>
              </div>

              <div class="col-12">
                <q-select
                  v-model="formAsignacion.plan_id"
                  :options="opcionesPlanesActivos"
                  emit-value
                  map-options
                  outlined
                  dense
                  label="Plan comercial *"
                  :disable="!planesActivos.length"
                  :rules="[valor => !!valor || 'Selecciona un plan']"
                >
                  <template #prepend>
                    <q-icon name="workspace_premium" color="green-8" />
                  </template>
                </q-select>
              </div>

              <div class="col-12 col-sm-6">
                <q-input
                  v-model="formAsignacion.fecha_inicio"
                  type="date"
                  outlined
                  dense
                  label="Fecha de inicio *"
                  :rules="[valor => !!valor || 'Selecciona la fecha de inicio']"
                >
                  <template #prepend>
                    <q-icon name="event" color="green-8" />
                  </template>
                </q-input>
              </div>

              <div class="col-12 col-sm-6">
                <q-field outlined dense label="Sindicato" stack-label>
                  <template #control>
                    <div class="self-center full-width no-outline">
                      {{ sindicatoAsignacionTexto }}
                    </div>
                  </template>
                  <template #prepend>
                    <q-icon name="business" color="green-8" />
                  </template>
                </q-field>
              </div>
            </div>

            <q-banner rounded class="bg-blue-1 text-blue-10 q-mt-md">
              <template #avatar>
                <q-icon name="verified_user" color="blue-9" />
              </template>
              El backend conserva la restricción por sindicato. Un secretario no puede administrar conductores de otro sindicato.
            </q-banner>
          </q-card-section>

          <q-separator />
          <q-card-actions align="right" class="q-pa-md">
            <q-btn flat label="Cancelar" no-caps :disable="guardandoAsignacion" v-close-popup />
            <q-btn
              color="green-8"
              icon="check_circle"
              label="Asignar suscripción"
              no-caps
              unelevated
              type="submit"
              :disable="!planesActivos.length"
              :loading="guardandoAsignacion"
            />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useQuasar } from 'quasar'
import { api } from 'src/boot/axios.js'

const $q = useQuasar()

const loadingPanel = ref(false)
const loadingLista = ref(false)
const cargandoSindicatos = ref(false)
const cargandoDetalle = ref(false)
const cargandoPlanes = ref(false)
const guardandoPlan = ref(false)
const guardandoAsignacion = ref(false)
const buscandoMototaxistas = ref(false)

const periodo = ref(periodoActual())
const sindicatoSeleccionado = ref(null)
const sindicatos = ref([])
const planes = ref([])
const panel = ref(panelVacio())

const busqueda = ref('')
const estadoFiltro = ref('Todos')
const suscripciones = ref([])
const detalle = ref(null)
const dialogDetalle = ref(false)
const dialogPlan = ref(false)
const dialogAsignacion = ref(false)
const planEditandoId = ref(null)
const opcionesMototaxistas = ref([])
const mototaxistasCache = ref([])

const paginacion = ref({
  page: 1,
  rowsPerPage: 12,
  rowsNumber: 0
})

const estados = [
  'Todos',
  'Activa',
  'Por vencer',
  'Periodo de gracia',
  'Vencida',
  'Suspendida'
]

const columnas = [
  { name: 'conductor', label: 'Conductor', field: 'id', align: 'left' },
  { name: 'sindicato', label: 'Sindicato', field: row => row.sindicato?.nombre, align: 'left' },
  { name: 'plan', label: 'Plan / cuota', field: row => row.plan?.nombre, align: 'left' },
  { name: 'estado', label: 'Estado', field: 'estado_calculado', align: 'center' },
  { name: 'vencimiento', label: 'Vencimiento', field: 'fecha_vencimiento', align: 'left' },
  { name: 'acciones', label: '', field: 'id', align: 'right' }
]

const columnasSindicatos = [
  { name: 'nombre', label: 'Sindicato', field: 'nombre', align: 'left' },
  { name: 'suscritos', label: 'Suscritos', field: row => row.suscripciones?.total || 0, align: 'center' },
  { name: 'pagadas', label: 'Pagadas', field: row => row.cobranza?.pagadas || 0, align: 'center' },
  { name: 'vencidas', label: 'Vencidas', field: row => row.suscripciones?.vencidas || 0, align: 'center' },
  { name: 'recaudado', label: 'Recaudado', field: row => row.cobranza?.recaudado_sindicato || 0, align: 'right' },
  { name: 'pendiente', label: 'Pendiente cobro', field: row => row.cobranza?.pendiente_cobro || 0, align: 'right' },
  { name: 'liquidar', label: 'Pendiente liquidar', field: row => row.liquidaciones?.pendiente_liquidar || 0, align: 'right' }
]

const columnasPagos = [
  { name: 'periodo', label: 'Periodo', field: 'periodo', align: 'left' },
  { name: 'monto', label: 'Cuota', field: 'monto_esperado', align: 'right' },
  { name: 'estado', label: 'Estado', field: 'estado', align: 'center' },
  { name: 'fecha_pago', label: 'Pago', field: row => fecha(row.fecha_pago), align: 'left' },
  { name: 'forma_pago', label: 'Medio', field: 'forma_pago', align: 'left' }
]

const formPlan = ref(planVacio())
const formAsignacion = ref(asignacionVacia())

function leerUsuario () {
  try {
    return JSON.parse(localStorage.getItem('motrix_user') || 'null')
  } catch {
    return null
  }
}

const usuario = ref(leerUsuario())

const rol = computed(() =>
  String(usuario.value?.role || '')
    .trim()
    .toLowerCase()
)

const esAdminGeneral = computed(() => rol.value === 'admin_general')

const opcionesSindicato = computed(() =>
  sindicatos.value.map(item => ({
    label: item.nombre,
    value: Number(item.id)
  }))
)

const planesActivos = computed(() =>
  planes.value.filter(plan => Boolean(plan.activo))
)

const opcionesPlanesActivos = computed(() =>
  planesActivos.value.map(plan => ({
    label: `${plan.nombre} · ${dinero(plan.monto)} · ${plan.duracion_meses} mes(es)`,
    value: Number(plan.id)
  }))
)

const sindicatoAsignacionTexto = computed(() => {
  const id = Number(formAsignacion.value.id_mototaxista || 0)
  const item = mototaxistasCache.value.find(mototaxista => Number(mototaxista.id) === id)
  return item?.sindicato?.nombre || usuario.value?.sindicato_nombre || 'Se tomará del mototaxista'
})

const tarjetasResumen = computed(() => {
  const resumen = panel.value.resumen || panelVacio().resumen

  return [
    {
      etiqueta: 'Suscripciones',
      valor: resumen.suscripciones?.total || 0,
      detalle: `${resumen.suscripciones?.activas || 0} activas · ${resumen.suscripciones?.por_vencer || 0} por vencer`,
      icono: 'workspace_premium',
      fondo: 'green-1',
      color: 'green-9'
    },
    {
      etiqueta: 'Pagadas del periodo',
      valor: resumen.cobranza?.pagadas || 0,
      detalle: `${resumen.cobranza?.pendientes || 0} pendientes · ${resumen.cobranza?.vencidas || 0} vencidas`,
      icono: 'task_alt',
      fondo: 'blue-1',
      color: 'blue-9'
    },
    {
      etiqueta: 'Recaudado sindicato',
      valor: dinero(resumen.cobranza?.recaudado_sindicato),
      detalle: `Directo MOTRIX: ${dinero(resumen.cobranza?.motrix_directo)}`,
      icono: 'payments',
      fondo: 'teal-1',
      color: 'teal-9'
    },
    {
      etiqueta: 'Pendiente de cobro',
      valor: dinero(resumen.cobranza?.pendiente_cobro),
      detalle: `${resumen.suscripciones?.vencidas || 0} suscripciones vencidas`,
      icono: 'pending_actions',
      fondo: 'orange-1',
      color: 'orange-9'
    },
    {
      etiqueta: 'Liquidado validado',
      valor: dinero(resumen.liquidaciones?.liquidado_validado),
      detalle: `En revisión: ${dinero(resumen.liquidaciones?.pendiente_validacion)}`,
      icono: 'verified',
      fondo: 'purple-1',
      color: 'purple-9'
    },
    {
      etiqueta: 'Pendiente de liquidar',
      valor: dinero(resumen.liquidaciones?.pendiente_liquidar),
      detalle: `Disponible: ${dinero(resumen.liquidaciones?.disponible_transferir)}`,
      icono: 'account_balance',
      fondo: 'red-1',
      color: 'red-9'
    },
    {
      etiqueta: 'Periodo de gracia',
      valor: resumen.suscripciones?.gracia || 0,
      detalle: 'Acceso temporal con advertencia',
      icono: 'schedule',
      fondo: 'amber-1',
      color: 'amber-10'
    },
    {
      etiqueta: 'Suspendidas',
      valor: resumen.suscripciones?.suspendidas || 0,
      detalle: 'Suspensiones administrativas',
      icono: 'block',
      fondo: 'grey-3',
      color: 'grey-9'
    }
  ]
})

function periodoActual () {
  const hoy = new Date()
  const mes = String(hoy.getMonth() + 1).padStart(2, '0')
  return `${hoy.getFullYear()}-${mes}`
}

function fechaHoy () {
  const hoy = new Date()
  const mes = String(hoy.getMonth() + 1).padStart(2, '0')
  const dia = String(hoy.getDate()).padStart(2, '0')
  return `${hoy.getFullYear()}-${mes}-${dia}`
}

function panelVacio () {
  return {
    periodo: periodoActual(),
    id_sindicato: null,
    resumen: {
      suscripciones: {
        total: 0,
        activas: 0,
        por_vencer: 0,
        gracia: 0,
        vencidas: 0,
        suspendidas: 0
      },
      cobranza: {
        pagadas: 0,
        pendientes: 0,
        vencidas: 0,
        recaudado_total: '0.00',
        recaudado_sindicato: '0.00',
        motrix_directo: '0.00',
        pendiente_cobro: '0.00'
      },
      liquidaciones: {
        liquidado_validado: '0.00',
        pendiente_validacion: '0.00',
        pendiente_liquidar: '0.00',
        disponible_transferir: '0.00'
      }
    },
    sindicatos: [],
    planes: []
  }
}

function planVacio () {
  return {
    nombre: 'MOTRIX Conductor',
    descripcion: 'Suscripción comercial mensual para conductores MOTRIX.',
    monto: 15,
    duracion_meses: 1,
    dias_gracia: 3,
    aviso_dias_antes: 7,
    activo: true
  }
}

function asignacionVacia () {
  return {
    id_mototaxista: null,
    plan_id: null,
    fecha_inicio: fechaHoy()
  }
}

function parametrosPanel () {
  const params = { periodo: periodo.value }

  if (esAdminGeneral.value && sindicatoSeleccionado.value) {
    params.id_sindicato = Number(sindicatoSeleccionado.value)
  }

  return params
}

function parametrosLista () {
  const params = {
    page: paginacion.value.page,
    per_page: paginacion.value.rowsPerPage
  }

  if (busqueda.value?.trim()) params.q = busqueda.value.trim()
  if (estadoFiltro.value && estadoFiltro.value !== 'Todos') params.estado = estadoFiltro.value

  if (esAdminGeneral.value && sindicatoSeleccionado.value) {
    params.id_sindicato = Number(sindicatoSeleccionado.value)
  }

  return params
}

async function cargarPanel () {
  loadingPanel.value = true

  try {
    const { data } = await api.get('/suscripciones-motrix/panel', {
      params: parametrosPanel()
    })
    panel.value = data?.data || panelVacio()
  } catch (error) {
    notificarError(error)
  } finally {
    loadingPanel.value = false
  }
}

async function cargarSuscripciones () {
  loadingLista.value = true

  try {
    const { data } = await api.get('/suscripciones-motrix', {
      params: parametrosLista()
    })

    suscripciones.value = data?.data || []
    paginacion.value = {
      ...paginacion.value,
      page: Number(data?.meta?.current_page || 1),
      rowsPerPage: Number(data?.meta?.per_page || paginacion.value.rowsPerPage),
      rowsNumber: Number(data?.meta?.total || 0)
    }
  } catch (error) {
    notificarError(error)
  } finally {
    loadingLista.value = false
  }
}

async function cargarSindicatos () {
  if (!esAdminGeneral.value) return
  cargandoSindicatos.value = true

  try {
    const { data } = await api.get('/sindicatos')
    sindicatos.value = Array.isArray(data) ? data : data?.data || []
  } catch (error) {
    notificarError(error)
  } finally {
    cargandoSindicatos.value = false
  }
}

async function cargarPlanes () {
  cargandoPlanes.value = true

  try {
    const { data } = await api.get('/planes-suscripcion-motrix', {
      params: esAdminGeneral.value ? { incluir_inactivos: 1 } : {}
    })
    planes.value = data?.data || []
  } catch (error) {
    notificarError(error)
  } finally {
    cargandoPlanes.value = false
  }
}

function abrirNuevoPlan () {
  planEditandoId.value = null
  formPlan.value = planVacio()
  dialogPlan.value = true
}

function abrirEditarPlan (plan) {
  planEditandoId.value = Number(plan.id)
  formPlan.value = {
    nombre: plan.nombre || '',
    descripcion: plan.descripcion || '',
    monto: numero(plan.monto),
    duracion_meses: Number(plan.duracion_meses || 1),
    dias_gracia: Number(plan.dias_gracia || 0),
    aviso_dias_antes: Number(plan.aviso_dias_antes || 0),
    activo: Boolean(plan.activo)
  }
  dialogPlan.value = true
}

async function guardarPlan () {
  if (!esAdminGeneral.value || guardandoPlan.value) return

  guardandoPlan.value = true

  const payload = {
    nombre: String(formPlan.value.nombre || '').trim(),
    descripcion: String(formPlan.value.descripcion || '').trim() || null,
    monto: Number(formPlan.value.monto),
    duracion_meses: Number(formPlan.value.duracion_meses),
    dias_gracia: Number(formPlan.value.dias_gracia),
    aviso_dias_antes: Number(formPlan.value.aviso_dias_antes),
    activo: Boolean(formPlan.value.activo)
  }

  try {
    const respuesta = planEditandoId.value
      ? await api.put(`/planes-suscripcion-motrix/${planEditandoId.value}`, payload)
      : await api.post('/planes-suscripcion-motrix', payload)

    $q.notify({
      type: 'positive',
      position: 'top',
      icon: 'check_circle',
      message: respuesta.data?.mensaje || 'Plan guardado correctamente.'
    })

    dialogPlan.value = false
    await Promise.all([cargarPlanes(), cargarPanel()])
  } catch (error) {
    notificarError(error)
  } finally {
    guardandoPlan.value = false
  }
}

async function abrirAsignacion () {
  formAsignacion.value = asignacionVacia()
  opcionesMototaxistas.value = []
  mototaxistasCache.value = []

  if (!planes.value.length) await cargarPlanes()
  if (planesActivos.value.length === 1) {
    formAsignacion.value.plan_id = Number(planesActivos.value[0].id)
  }

  dialogAsignacion.value = true
}

function filtrarMototaxistas (val, update, abort) {
  const texto = String(val || '').trim()

  if (texto.length < 2) {
    update(() => {
      opcionesMototaxistas.value = []
    })
    return
  }

  buscandoMototaxistas.value = true

  const params = {
    paginated: 1,
    q: texto,
    per_page: 10
  }

  if (esAdminGeneral.value && sindicatoSeleccionado.value) {
    const sindicato = sindicatos.value.find(item => Number(item.id) === Number(sindicatoSeleccionado.value))
    if (sindicato?.nombre) params.sindicato = sindicato.nombre
  }

  api.get('/mototaxistas', { params })
    .then(({ data }) => {
      const items = Array.isArray(data) ? data : data?.data || []
      mototaxistasCache.value = items

      update(() => {
        opcionesMototaxistas.value = items.map(item => ({
          value: Number(item.id),
          label: etiquetaMototaxista(item)
        }))
      })
    })
    .catch(error => {
      abort()
      notificarError(error)
    })
    .finally(() => {
      buscandoMototaxistas.value = false
    })
}

function etiquetaMototaxista (item) {
  const persona = item?.persona || {}
  const nombre = [persona.nombre, persona.apellidos].filter(Boolean).join(' ').trim() || 'Mototaxista'
  const ci = persona.ci ? `CI ${persona.ci}` : 'Sin CI'
  const chaleco = item?.nro_chaleco ? `Chaleco ${item.nro_chaleco}` : 'Sin chaleco'
  const sindicato = item?.sindicato?.nombre || 'Sin sindicato'
  return `${nombre} · ${ci} · ${chaleco} · ${sindicato}`
}

async function guardarAsignacion () {
  if (guardandoAsignacion.value) return

  guardandoAsignacion.value = true

  try {
    const respuesta = await api.post('/suscripciones-motrix/configurar', {
      id_mototaxista: Number(formAsignacion.value.id_mototaxista),
      plan_id: Number(formAsignacion.value.plan_id),
      fecha_inicio: formAsignacion.value.fecha_inicio
    })

    let sincronizada = false
    if (esAdminGeneral.value) {
      try {
        await api.post('/suscripciones-motrix/sincronizar')
        sincronizada = true
      } catch {
        sincronizada = false
      }
    }

    const mensajeBase = respuesta.data?.mensaje || 'Suscripción MOTRIX asignada correctamente.'
    $q.notify({
      type: 'positive',
      position: 'top',
      icon: 'check_circle',
      timeout: 4500,
      message: sincronizada
        ? `${mensajeBase} La cuota inicial también fue sincronizada.`
        : mensajeBase
    })

    dialogAsignacion.value = false
    paginacion.value.page = 1
    await actualizarTodo()
  } catch (error) {
    notificarError(error)
  } finally {
    guardandoAsignacion.value = false
  }
}

async function abrirDetalle (row) {
  dialogDetalle.value = true
  cargandoDetalle.value = true
  detalle.value = row

  try {
    const { data } = await api.get(`/suscripciones-motrix/${row.id}`)
    detalle.value = data?.data || row
  } catch (error) {
    notificarError(error)
  } finally {
    cargandoDetalle.value = false
  }
}

async function prepararRenovacion (row) {
  try {
    const respuesta = await api.post(`/suscripciones-motrix/${row.id}/generar-renovacion`)

    $q.notify({
      type: 'positive',
      position: 'top',
      icon: 'check_circle',
      message: respuesta.data?.mensaje || 'Renovación preparada correctamente.'
    })

    await actualizarTodo()
  } catch (error) {
    notificarError(error)
  }
}

async function actualizarPanel () {
  paginacion.value.page = 1
  await Promise.all([cargarPanel(), cargarSuscripciones()])
}

async function actualizarTodo () {
  await Promise.all([cargarPanel(), cargarSuscripciones(), cargarPlanes()])
}

async function cambioSindicato () {
  paginacion.value.page = 1
  await actualizarTodo()
}

async function reiniciarLista () {
  paginacion.value.page = 1
  await cargarSuscripciones()
}

async function solicitarPagina (props) {
  paginacion.value = {
    ...paginacion.value,
    page: props.pagination.page,
    rowsPerPage: props.pagination.rowsPerPage
  }
  await cargarSuscripciones()
}

function nombreConductor (row) {
  const persona = row?.mototaxista?.persona
  const nombre = [persona?.nombre, persona?.apellidos].filter(Boolean).join(' ').trim()
  return nombre || 'Mototaxista'
}

function numero (valor) {
  const n = Number(valor)
  return Number.isFinite(n) ? n : 0
}

function dinero (valor) {
  return new Intl.NumberFormat('es-BO', {
    style: 'currency',
    currency: 'BOB',
    minimumFractionDigits: 2
  }).format(numero(valor))
}

function fecha (valor) {
  if (!valor) return '—'
  const soloFecha = String(valor).slice(0, 10)
  const [anio, mes, dia] = soloFecha.split('-')
  if (!anio || !mes || !dia) return String(valor)
  return `${dia}/${mes}/${anio}`
}

function textoDias (valor) {
  const dias = Number(valor || 0)
  if (dias < 0) return `${Math.abs(dias)} día(s) vencida`
  if (dias === 0) return 'Vence hoy'
  return `${dias} día(s) restantes`
}

function colorEstado (estado) {
  const valor = String(estado || '').trim().toLowerCase()
  if (valor === 'activa') return 'green-8'
  if (valor === 'por vencer') return 'orange-8'
  if (valor === 'periodo de gracia') return 'amber-9'
  if (valor === 'vencida') return 'negative'
  if (valor === 'suspendida') return 'grey-8'
  return 'blue-grey-7'
}

function colorPago (estado) {
  const valor = String(estado || '').trim().toLowerCase()
  if (valor === 'pagado') return 'green-8'
  if (valor === 'pendiente') return 'orange-8'
  if (valor === 'vencido') return 'negative'
  if (valor === 'exonerado') return 'blue-8'
  return 'grey-7'
}

function mensajeError (error) {
  const errores = error.response?.data?.errors
  const primero = errores ? Object.values(errores).flat().find(Boolean) : null

  return (
    primero ||
    error.response?.data?.message ||
    error.response?.data?.mensaje ||
    'No se pudo completar la operación.'
  )
}

function notificarError (error) {
  $q.notify({
    type: 'negative',
    position: 'top',
    message: mensajeError(error)
  })
}

onMounted(async () => {
  await cargarSindicatos()
  await actualizarTodo()
})
</script>

<style scoped>
.stat-card,
.plan-card {
  height: 100%;
  border-radius: 14px;
}

.section-card {
  border-radius: 14px;
}

.dialog-card {
  width: min(920px, 94vw);
  max-width: 920px;
}

.dialog-form-card {
  width: min(720px, 94vw);
  max-width: 720px;
}

.min-width-zero {
  min-width: 0;
}
</style>
