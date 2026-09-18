<template>
  <q-page class="q-pa-md bg-grey-1">
    <div class="row items-center q-col-gutter-md q-mb-lg">
      <div class="col-12 col-md">
        <div class="text-h5 text-weight-bold text-green-9">
          Liquidaciones MOTRIX
        </div>
        <div class="text-body2 text-grey-7">
          Conciliación de suscripciones cobradas por el sindicato y transferidas a MOTRIX.
        </div>
      </div>

      <div class="col-12 col-md-auto">
        <q-btn
          color="green-8"
          icon="sync"
          label="Preparar / actualizar"
          no-caps
          unelevated
          :loading="preparando"
          :disable="!idSindicatoObjetivo"
          @click="prepararLiquidacion"
        />
      </div>
    </div>

    <q-banner
      rounded
      class="bg-blue-1 text-blue-10 q-mb-lg"
    >
      <template #avatar>
        <q-icon name="info" color="blue-8" />
      </template>
      Solo forman parte de la liquidación los pagos de suscripción MOTRIX
      efectivamente cobrados por el sindicato. Los pagos directos a MOTRIX
      se muestran como referencia, pero no generan saldo sindical.
    </q-banner>

    <q-card flat bordered class="q-mb-lg">
      <q-card-section>
        <div class="row q-col-gutter-md items-end">
          <div class="col-12 col-sm-6 col-md-3">
            <q-input
              v-model="periodo"
              type="month"
              outlined
              dense
              label="Periodo"
              @update:model-value="actualizarTodo"
            >
              <template #prepend>
                <q-icon name="calendar_month" color="green-8" />
              </template>
            </q-input>
          </div>

          <div
            v-if="!esSecretario"
            class="col-12 col-sm-6 col-md-5"
          >
            <q-select
              v-model="sindicatoSeleccionado"
              :options="opcionesSindicato"
              emit-value
              map-options
              outlined
              dense
              clearable
              label="Sindicato"
              :loading="cargandoSindicatos"
              @update:model-value="actualizarTodo"
            >
              <template #prepend>
                <q-icon name="business" color="green-8" />
              </template>
            </q-select>
          </div>

          <div
            v-else
            class="col-12 col-sm-6 col-md-5"
          >
            <q-field
              outlined
              dense
              label="Sindicato"
              stack-label
            >
              <template #control>
                <div class="self-center full-width no-outline">
                  {{ nombreSindicatoActual }}
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
              :loading="loading"
              :disable="!idSindicatoObjetivo"
              @click="actualizarTodo"
            />
          </div>
        </div>
      </q-card-section>
    </q-card>

    <div class="row q-col-gutter-md q-mb-lg">
      <div class="col-12 col-sm-6 col-lg">
        <q-card flat bordered class="stat-card">
          <q-card-section>
            <div class="row items-center no-wrap">
              <q-avatar color="green-1" text-color="green-9" icon="payments" />
              <div class="q-ml-md">
                <div class="text-caption text-grey-7">
                  Recaudado por sindicato
                </div>
                <div class="text-h6 text-weight-bold">
                  {{ dinero(resumen.recaudado_sindicato) }}
                </div>
                <div class="text-caption text-grey-6">
                  {{ resumen.pagos_sindicato || 0 }} pagos
                </div>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-lg">
        <q-card flat bordered class="stat-card">
          <q-card-section>
            <div class="row items-center no-wrap">
              <q-avatar color="blue-1" text-color="blue-9" icon="account_balance" />
              <div class="q-ml-md">
                <div class="text-caption text-grey-7">
                  Pago directo a MOTRIX
                </div>
                <div class="text-h6 text-weight-bold">
                  {{ dinero(resumen.motrix_directo) }}
                </div>
                <div class="text-caption text-grey-6">
                  {{ resumen.pagos_directos || 0 }} pagos
                </div>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-lg">
        <q-card flat bordered class="stat-card">
          <q-card-section>
            <div class="row items-center no-wrap">
              <q-avatar color="teal-1" text-color="teal-9" icon="verified" />
              <div class="q-ml-md">
                <div class="text-caption text-grey-7">
                  Validado por MOTRIX
                </div>
                <div class="text-h6 text-weight-bold">
                  {{ dinero(resumen.liquidado_validado) }}
                </div>
                <div class="text-caption text-grey-6">
                  Transferencias aceptadas
                </div>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-lg">
        <q-card flat bordered class="stat-card">
          <q-card-section>
            <div class="row items-center no-wrap">
              <q-avatar color="orange-1" text-color="orange-9" icon="hourglass_top" />
              <div class="q-ml-md">
                <div class="text-caption text-grey-7">
                  Pendiente de validación
                </div>
                <div class="text-h6 text-weight-bold">
                  {{ dinero(resumen.pendiente_validacion) }}
                </div>
                <div class="text-caption text-grey-6">
                  En revisión
                </div>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-lg">
        <q-card flat bordered class="stat-card">
          <q-card-section>
            <div class="row items-center no-wrap">
              <q-avatar color="red-1" text-color="red-9" icon="pending_actions" />
              <div class="q-ml-md">
                <div class="text-caption text-grey-7">
                  Pendiente de liquidar
                </div>
                <div class="text-h6 text-weight-bold">
                  {{ dinero(resumen.pendiente_liquidar) }}
                </div>
                <div class="text-caption text-grey-6">
                  Disponible: {{ dinero(resumen.disponible_transferir) }}
                </div>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <q-card flat bordered>
      <q-card-section class="row items-center">
        <div>
          <div class="text-subtitle1 text-weight-bold">
            Historial de liquidaciones
          </div>
          <div class="text-caption text-grey-7">
            Una liquidación consolidada por sindicato y periodo.
          </div>
        </div>
        <q-space />
        <q-chip
          v-if="resumen.estado"
          :color="colorEstadoLiquidacion(resumen.estado)"
          text-color="white"
          :label="resumen.estado"
        />
      </q-card-section>

      <q-separator />

      <q-table
        flat
        :rows="liquidaciones"
        :columns="columnas"
        row-key="id"
        :loading="loading"
        :pagination="{ rowsPerPage: 10 }"
        no-data-label="No hay liquidaciones preparadas para este filtro."
      >
        <template #body-cell-sindicato="props">
          <q-td :props="props">
            {{ props.row.sindicato?.nombre || '—' }}
          </q-td>
        </template>

        <template #body-cell-declarado="props">
          <q-td :props="props">
            {{ dinero(props.row.monto_declarado) }}
          </q-td>
        </template>

        <template #body-cell-transferido="props">
          <q-td :props="props">
            {{ dinero(props.row.monto_transferido) }}
          </q-td>
        </template>

        <template #body-cell-pendiente="props">
          <q-td :props="props">
            {{ dinero(props.row.saldo_pendiente) }}
            <q-badge
              v-if="numero(props.row.monto_pendiente_validacion) > 0"
              color="orange-8"
              class="q-ml-xs"
            >
              {{ dinero(props.row.monto_pendiente_validacion) }} en revisión
            </q-badge>
          </q-td>
        </template>

        <template #body-cell-estado="props">
          <q-td :props="props">
            <q-chip
              dense
              :color="colorEstadoLiquidacion(props.row.estado)"
              text-color="white"
              :label="props.row.estado"
            />
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
              <q-tooltip>Ver detalle</q-tooltip>
            </q-btn>

            <q-btn
              flat
              round
              dense
              color="green-8"
              icon="add_card"
              :disable="numero(props.row.disponible_transferir) <= 0"
              @click="abrirTransferencia(props.row)"
            >
              <q-tooltip>Registrar transferencia</q-tooltip>
            </q-btn>
          </q-td>
        </template>
      </q-table>
    </q-card>

    <q-dialog
      v-model="dialogTransferencia"
      persistent
    >
      <q-card class="dialog-card">
        <q-form @submit.prevent="guardarTransferencia">
          <q-card-section class="bg-green-8 text-white row items-center">
            <div>
              <div class="text-h6 text-weight-bold">
                Registrar transferencia
              </div>
              <div class="text-caption text-green-1">
                Sindicato → MOTRIX
              </div>
            </div>
            <q-space />
            <q-btn
              flat
              round
              dense
              icon="close"
              :disable="guardando"
              v-close-popup
            />
          </q-card-section>

          <q-card-section>
            <q-banner
              rounded
              class="bg-green-1 text-green-10 q-mb-md"
            >
              Saldo disponible para transferir:
              <strong>{{ dinero(liquidacionTransferencia?.disponible_transferir) }}</strong>
            </q-banner>

            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="formTransferencia.monto"
                  type="number"
                  step="0.01"
                  min="0.01"
                  outlined
                  label="Monto transferido *"
                  prefix="Bs."
                  :rules="[reglaMonto]"
                />
              </div>

              <div class="col-12 col-sm-6">
                <q-select
                  v-model="formTransferencia.forma_pago"
                  :options="formasPago"
                  outlined
                  label="Forma de pago *"
                  :rules="[requerido]"
                />
              </div>

              <div class="col-12 col-sm-6">
                <q-input
                  v-model="formTransferencia.fecha_transferencia"
                  type="date"
                  outlined
                  label="Fecha de transferencia *"
                  :rules="[requerido]"
                />
              </div>

              <div class="col-12 col-sm-6">
                <q-input
                  v-model.trim="formTransferencia.referencia"
                  outlined
                  maxlength="150"
                  label="Referencia / N.º operación"
                />
              </div>

              <div class="col-12">
                <q-file
                  v-model="formTransferencia.comprobante"
                  outlined
                  accept=".jpg,.jpeg,.png,.webp,.pdf"
                  max-file-size="5242880"
                  label="Comprobante (JPG, PNG, WEBP o PDF)"
                  clearable
                  @rejected="archivoRechazado"
                >
                  <template #prepend>
                    <q-icon name="attach_file" color="green-8" />
                  </template>
                </q-file>
              </div>

              <div class="col-12">
                <q-input
                  v-model.trim="formTransferencia.observacion"
                  type="textarea"
                  autogrow
                  outlined
                  maxlength="500"
                  counter
                  label="Observación"
                />
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
              label="Cancelar"
              :disable="guardando"
              v-close-popup
            />
            <q-btn
              type="submit"
              color="green-8"
              icon="send"
              label="Registrar"
              no-caps
              unelevated
              :loading="guardando"
            />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <q-dialog
      v-model="dialogDetalle"
      maximized
      transition-show="slide-up"
      transition-hide="slide-down"
    >
      <q-card>
        <q-bar class="bg-green-8 text-white">
          <q-icon name="account_balance" />
          <div class="text-weight-bold">
            Detalle de liquidación MOTRIX
          </div>
          <q-space />
          <q-btn flat dense icon="close" v-close-popup />
        </q-bar>

        <q-card-section
          v-if="detalle"
          class="q-pa-md q-pa-lg-md"
        >
          <div class="row q-col-gutter-md q-mb-lg">
            <div class="col-12 col-md-4">
              <q-card flat bordered>
                <q-card-section>
                  <div class="text-caption text-grey-7">Sindicato</div>
                  <div class="text-h6 text-weight-bold">
                    {{ detalle.sindicato?.nombre || '—' }}
                  </div>
                  <div class="text-body2 q-mt-xs">
                    Periodo: {{ periodoMMYYYY(detalle.periodo) }}
                  </div>
                </q-card-section>
              </q-card>
            </div>

            <div class="col-12 col-md-8">
              <div class="row q-col-gutter-sm">
                <div class="col-6 col-md-3">
                  <q-card flat bordered>
                    <q-card-section>
                      <div class="text-caption text-grey-7">Recaudado</div>
                      <div class="text-subtitle1 text-weight-bold">
                        {{ dinero(detalle.monto_declarado) }}
                      </div>
                    </q-card-section>
                  </q-card>
                </div>
                <div class="col-6 col-md-3">
                  <q-card flat bordered>
                    <q-card-section>
                      <div class="text-caption text-grey-7">Validado</div>
                      <div class="text-subtitle1 text-weight-bold">
                        {{ dinero(detalle.monto_transferido) }}
                      </div>
                    </q-card-section>
                  </q-card>
                </div>
                <div class="col-6 col-md-3">
                  <q-card flat bordered>
                    <q-card-section>
                      <div class="text-caption text-grey-7">En revisión</div>
                      <div class="text-subtitle1 text-weight-bold">
                        {{ dinero(detalle.monto_pendiente_validacion) }}
                      </div>
                    </q-card-section>
                  </q-card>
                </div>
                <div class="col-6 col-md-3">
                  <q-card flat bordered>
                    <q-card-section>
                      <div class="text-caption text-grey-7">Saldo</div>
                      <div class="text-subtitle1 text-weight-bold">
                        {{ dinero(detalle.saldo_pendiente) }}
                      </div>
                    </q-card-section>
                  </q-card>
                </div>
              </div>
            </div>
          </div>

          <div class="row items-center q-mb-sm">
            <div class="text-h6 text-weight-bold">
              Transferencias
            </div>
            <q-space />
            <q-btn
              color="green-8"
              icon="add_card"
              label="Nueva transferencia"
              no-caps
              unelevated
              :disable="numero(detalle.disponible_transferir) <= 0"
              @click="abrirTransferencia(detalle)"
            />
          </div>

          <q-table
            flat
            bordered
            :rows="detalle.transferencias || []"
            :columns="columnasTransferencias"
            row-key="id"
            :pagination="{ rowsPerPage: 10 }"
            no-data-label="Aún no se registraron transferencias."
            class="q-mb-xl"
          >
            <template #body-cell-monto="props">
              <q-td :props="props">
                {{ dinero(props.row.monto) }}
              </q-td>
            </template>

            <template #body-cell-fecha="props">
              <q-td :props="props">
                {{ fechaDDMMYYYY(props.row.fecha_transferencia) }}
              </q-td>
            </template>

            <template #body-cell-comprobante="props">
              <q-td :props="props">
                <q-btn
                  v-if="props.row.comprobante_url"
                  flat
                  dense
                  color="blue-8"
                  icon="open_in_new"
                  label="Ver"
                  no-caps
                  :href="props.row.comprobante_url"
                  target="_blank"
                />
                <span v-else class="text-grey-6">Sin archivo</span>
              </q-td>
            </template>

            <template #body-cell-estado="props">
              <q-td :props="props">
                <q-chip
                  dense
                  :color="colorEstadoTransferencia(props.row.estado)"
                  text-color="white"
                  :label="props.row.estado"
                />
              </q-td>
            </template>

            <template #body-cell-acciones="props">
              <q-td :props="props">
                <template
                  v-if="
                    esAdminGeneral
                    && props.row.estado === 'Pendiente'
                  "
                >
                  <q-btn
                    flat
                    round
                    dense
                    color="positive"
                    icon="check_circle"
                    @click="validarTransferencia(props.row)"
                  >
                    <q-tooltip>Validar transferencia</q-tooltip>
                  </q-btn>

                  <q-btn
                    flat
                    round
                    dense
                    color="negative"
                    icon="report_problem"
                    @click="observarTransferencia(props.row)"
                  >
                    <q-tooltip>Observar transferencia</q-tooltip>
                  </q-btn>
                </template>
              </q-td>
            </template>
          </q-table>

          <div class="text-h6 text-weight-bold q-mb-sm">
            Pagos incluidos
          </div>

          <q-table
            flat
            bordered
            :rows="detalle.detalles || []"
            :columns="columnasPagos"
            row-key="id"
            :pagination="{ rowsPerPage: 10 }"
            no-data-label="No hay pagos asociados."
          >
            <template #body-cell-conductor="props">
              <q-td :props="props">
                {{ nombreConductor(props.row.pago) }}
              </q-td>
            </template>

            <template #body-cell-monto="props">
              <q-td :props="props">
                {{ dinero(props.row.monto_incluido) }}
              </q-td>
            </template>

            <template #body-cell-fecha="props">
              <q-td :props="props">
                {{ fechaHora(props.row.pago?.fecha_pago) }}
              </q-td>
            </template>
          </q-table>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { fechaDDMMYYYY, fechaHoraDDMMYYYY, fechaISOHoyBolivia, motrixDateV57, periodoActualBolivia, periodoMMYYYY } from 'src/utils/motrixDate.js'

import {
  computed,
  onMounted,
  ref
} from 'vue'
import {
  useQuasar
} from 'quasar'

import { api } from 'src/boot/axios.js'
const $q = useQuasar()

const loading = ref(false)
const preparando = ref(false)
const guardando = ref(false)
const cargandoSindicatos = ref(false)

const sindicatos = ref([])
const sindicatoSeleccionado = ref(null)
const periodo = ref(periodoActual())
const liquidaciones = ref([])
const resumen = ref(resumenVacio())

const dialogTransferencia = ref(false)
const dialogDetalle = ref(false)
const liquidacionTransferencia = ref(null)
const detalle = ref(null)

const formasPago = [
  'Transferencia',
  'QR',
  'Deposito',
  'Efectivo',
  'Otro'
]

const formTransferencia = ref(formTransferenciaVacio())

function leerUsuario() {
  try {
    return JSON.parse(
      localStorage.getItem('motrix_user')
      || 'null'
    )
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

const esSecretario = computed(() =>
  rol.value === 'secretario'
)

const esAdminGeneral = computed(() =>
  rol.value === 'admin_general'
)

const idSindicatoObjetivo = computed(() => {
  if (esSecretario.value) {
    return Number(
      usuario.value?.sindicato_id || 0
    ) || null
  }

  return Number(
    sindicatoSeleccionado.value || 0
  ) || null
})

const nombreSindicatoActual = computed(() =>
  usuario.value?.sindicato_nombre
  || sindicatos.value.find(
    item =>
      Number(item.id)
      === Number(idSindicatoObjetivo.value)
  )?.nombre
  || 'Mi sindicato'
)

const opcionesSindicato = computed(() =>
  sindicatos.value.map(
    item => ({
      label: item.nombre,
      value: Number(item.id)
    })
  )
)

const columnas = [
  {
    name: 'periodo',
    label: 'Periodo',
    field: 'periodo',
    align: 'left',
    sortable: true
  },
  {
    name: 'sindicato',
    label: 'Sindicato',
    field: 'sindicato',
    align: 'left'
  },
  {
    name: 'declarado',
    label: 'Recaudado',
    field: 'monto_declarado',
    align: 'right'
  },
  {
    name: 'transferido',
    label: 'Validado',
    field: 'monto_transferido',
    align: 'right'
  },
  {
    name: 'pendiente',
    label: 'Pendiente',
    field: 'saldo_pendiente',
    align: 'right'
  },
  {
    name: 'estado',
    label: 'Estado',
    field: 'estado',
    align: 'center'
  },
  {
    name: 'acciones',
    label: '',
    field: 'acciones',
    align: 'center'
  }
]

const columnasTransferencias = [
  {
    name: 'fecha',
    label: 'Fecha',
    field: 'fecha_transferencia',
    format: value => motrixDateV57(value),
    align: 'left'
  },
  {
    name: 'monto',
    label: 'Monto',
    field: 'monto',
    align: 'right'
  },
  {
    name: 'forma',
    label: 'Forma',
    field: 'forma_pago',
    align: 'left'
  },
  {
    name: 'referencia',
    label: 'Referencia',
    field: row => row.referencia || '—',
    align: 'left'
  },
  {
    name: 'comprobante',
    label: 'Comprobante',
    field: 'comprobante_url',
    align: 'center'
  },
  {
    name: 'estado',
    label: 'Estado',
    field: 'estado',
    align: 'center'
  },
  {
    name: 'acciones',
    label: '',
    field: 'acciones',
    align: 'center'
  }
]

const columnasPagos = [
  {
    name: 'conductor',
    label: 'Mototaxista',
    field: 'pago',
    align: 'left'
  },
  {
    name: 'monto',
    label: 'Monto',
    field: 'monto_incluido',
    align: 'right'
  },
  {
    name: 'forma',
    label: 'Forma',
    field: row =>
      row.pago?.forma_pago || '—',
    align: 'left'
  },
  {
    name: 'fecha',
    label: 'Fecha de pago',
    field: row =>
      row.pago?.fecha_pago,
    align: 'left'
  }
]

const requerido = valor =>
  Boolean(
    String(valor ?? '').trim()
  )
  || 'Campo obligatorio'

const reglaMonto = valor => {
  const monto = numero(valor)
  const disponible = numero(
    liquidacionTransferencia.value
      ?.disponible_transferir
  )

  if (monto <= 0) {
    return 'Ingresa un monto mayor a cero'
  }

  if (monto > disponible + 0.009) {
    return `Máximo disponible: ${dinero(disponible)}`
  }

  return true
}

function resumenVacio() {
  return {
    recaudado_sindicato: '0.00',
    pagos_sindicato: 0,
    motrix_directo: '0.00',
    pagos_directos: 0,
    liquidado_validado: '0.00',
    pendiente_validacion: '0.00',
    pendiente_liquidar: '0.00',
    disponible_transferir: '0.00',
    pagos_incluidos: 0,
    estado: 'Pendiente'
  }
}

function formTransferenciaVacio() {
  return {
    monto: null,
    forma_pago: 'Transferencia',
    referencia: '',
    fecha_transferencia: fechaHoy(),
    comprobante: null,
    observacion: ''
  }
}

function numero(valor) {
  const n = Number.parseFloat(valor)
  return Number.isFinite(n) ? n : 0
}

function dinero(valor) {
  return `Bs. ${numero(valor).toFixed(2)}`
}

function fechaHoy() {
  return fechaISOHoyBolivia()
}

function periodoActual() {
  return periodoActualBolivia()
}

function fechaHora(valor) {
  return fechaHoraDDMMYYYY(valor)
}

function nombreConductor(pago) {
  const persona =
    pago?.mototaxista?.persona

  const nombre = [
    persona?.nombre,
    persona?.apellidos
  ]
    .filter(Boolean)
    .join(' ')
    .trim()

  if (nombre) {
    return nombre
  }

  if (pago?.mototaxista?.nro_chaleco) {
    return `Chaleco ${pago.mototaxista.nro_chaleco}`
  }

  return 'Mototaxista'
}

function colorEstadoLiquidacion(estado) {
  switch (estado) {
    case 'Liquidada':
      return 'positive'
    case 'Parcial':
      return 'orange-8'
    case 'Pendiente':
      return 'grey-7'
    default:
      return 'grey-7'
  }
}

function colorEstadoTransferencia(estado) {
  switch (estado) {
    case 'Validada':
      return 'positive'
    case 'Observada':
      return 'negative'
    case 'Pendiente':
      return 'orange-8'
    default:
      return 'grey-7'
  }
}

function mensajeError(error) {
  const errores =
    error.response?.data?.errors

  const primero =
    errores
      ? Object.values(errores)
        .flat()
        .find(Boolean)
      : null

  return (
    primero
    || error.response?.data?.message
    || error.response?.data?.mensaje
    || 'Ocurrió un error al procesar la solicitud.'
  )
}

async function cargarSindicatos() {
  cargandoSindicatos.value = true

  try {
    const { data } =
      await api.get('/sindicatos')

    sindicatos.value =
      Array.isArray(data)
        ? data
        : data?.data || []

    if (
      !esSecretario.value
      && !sindicatoSeleccionado.value
      && sindicatos.value.length
    ) {
      sindicatoSeleccionado.value =
        Number(sindicatos.value[0].id)
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      message: mensajeError(error)
    })
  } finally {
    cargandoSindicatos.value = false
  }
}

function parametrosBase() {
  const params = {
    periodo: periodo.value
  }

  if (
    !esSecretario.value
    && idSindicatoObjetivo.value
  ) {
    params.id_sindicato =
      idSindicatoObjetivo.value
  }

  return params
}

async function actualizarTodo() {
  if (!idSindicatoObjetivo.value) {
    liquidaciones.value = []
    resumen.value = resumenVacio()
    return
  }

  loading.value = true

  try {
    const params =
      parametrosBase()

    const [
      respuestaResumen,
      respuestaLista
    ] = await Promise.all([
      api.get(
        '/liquidaciones-motrix/resumen',
        { params }
      ),
      api.get(
        '/liquidaciones-motrix',
        {
          params: {
            ...params,
            per_page: 50
          }
        }
      )
    ])

    resumen.value =
      respuestaResumen.data?.data
      || resumenVacio()

    liquidaciones.value =
      respuestaLista.data?.data
      || []
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      message: mensajeError(error)
    })
  } finally {
    loading.value = false
  }
}

async function prepararLiquidacion() {
  if (!idSindicatoObjetivo.value) {
    $q.notify({
      type: 'warning',
      position: 'top',
      message: 'Selecciona un sindicato.'
    })
    return
  }

  preparando.value = true

  try {
    const payload = {
      periodo: periodo.value
    }

    if (!esSecretario.value) {
      payload.id_sindicato =
        idSindicatoObjetivo.value
    }

    const { data } =
      await api.post(
        '/liquidaciones-motrix/preparar',
        payload
      )

    $q.notify({
      type: 'positive',
      position: 'top',
      icon: 'check_circle',
      message:
        data?.mensaje
        || 'Liquidación actualizada.'
    })

    await actualizarTodo()

    if (data?.data?.id) {
      await abrirDetalle(
        data.data
      )
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      message: mensajeError(error)
    })
  } finally {
    preparando.value = false
  }
}

async function abrirDetalle(row) {
  const id = Number(row?.id)

  if (!id) return

  loading.value = true

  try {
    const { data } =
      await api.get(
        `/liquidaciones-motrix/${id}`
      )

    detalle.value =
      data?.data || null

    dialogDetalle.value = true
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      message: mensajeError(error)
    })
  } finally {
    loading.value = false
  }
}

function abrirTransferencia(row) {
  const disponible =
    numero(row?.disponible_transferir)

  if (disponible <= 0) {
    $q.notify({
      type: 'warning',
      position: 'top',
      message:
        'No existe saldo disponible para registrar otra transferencia.'
    })
    return
  }

  liquidacionTransferencia.value =
    row

  formTransferencia.value =
    formTransferenciaVacio()

  formTransferencia.value.monto =
    disponible

  dialogTransferencia.value = true
}

function archivoRechazado() {
  $q.notify({
    type: 'negative',
    position: 'top',
    message:
      'El comprobante debe ser JPG, JPEG, PNG, WEBP o PDF y no superar 5 MB.'
  })
}

async function subirComprobante() {
  const archivo =
    formTransferencia.value
      .comprobante

  if (!archivo) {
    return null
  }

  const formData =
    new FormData()

  formData.append(
    'comprobante',
    archivo
  )

  const { data } =
    await api.post(
      '/liquidaciones-motrix/comprobante',
      formData,
      {
        headers: {
          'Content-Type':
            'multipart/form-data'
        }
      }
    )

  return data?.url || null
}

async function guardarTransferencia() {
  const liquidacionId =
    Number(
      liquidacionTransferencia.value?.id
    )

  if (!liquidacionId) return

  guardando.value = true

  try {
    const comprobanteUrl =
      await subirComprobante()

    const payload = {
      monto:
        numero(
          formTransferencia.value.monto
        ),
      forma_pago:
        formTransferencia.value
          .forma_pago,
      referencia:
        formTransferencia.value
          .referencia || null,
      comprobante_url:
        comprobanteUrl,
      fecha_transferencia:
        formTransferencia.value
          .fecha_transferencia,
      observacion:
        formTransferencia.value
          .observacion || null
    }

    const { data } =
      await api.post(
        `/liquidaciones-motrix/${liquidacionId}/transferencias`,
        payload
      )

    dialogTransferencia.value = false

    $q.notify({
      type: 'positive',
      position: 'top',
      icon: 'check_circle',
      message:
        data?.mensaje
        || 'Transferencia registrada.'
    })

    await actualizarTodo()

    if (
      detalle.value?.id
      === liquidacionId
    ) {
      await abrirDetalle({
        id: liquidacionId
      })
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      message: mensajeError(error)
    })
  } finally {
    guardando.value = false
  }
}

async function validarTransferencia(
  transferencia
) {
  const aceptar =
    await new Promise(resolve => {
      $q.dialog({
        title: 'Validar transferencia',
        message:
          `¿Confirmas que MOTRIX recibió ${dinero(transferencia.monto)}?`,
        cancel: true,
        persistent: true,
        ok: {
          label: 'Validar',
          color: 'positive',
          unelevated: true
        }
      })
        .onOk(() => resolve(true))
        .onCancel(() => resolve(false))
    })

  if (!aceptar) return

  try {
    const { data } =
      await api.post(
        `/transferencias-liquidacion-motrix/${transferencia.id}/validar`
      )

    $q.notify({
      type: 'positive',
      position: 'top',
      message:
        data?.mensaje
        || 'Transferencia validada.'
    })

    await actualizarTodo()

    if (detalle.value?.id) {
      await abrirDetalle({
        id: detalle.value.id
      })
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      message: mensajeError(error)
    })
  }
}

function observarTransferencia(
  transferencia
) {
  $q.dialog({
    title: 'Observar transferencia',
    message:
      'Indica el motivo de la observación.',
    prompt: {
      model: '',
      type: 'textarea',
      isValid: valor =>
        String(valor || '')
          .trim()
          .length >= 3
    },
    cancel: true,
    persistent: true,
    ok: {
      label: 'Observar',
      color: 'negative',
      unelevated: true
    }
  }).onOk(
    async observacion => {
      try {
        const { data } =
          await api.post(
            `/transferencias-liquidacion-motrix/${transferencia.id}/observar`,
            {
              observacion:
                String(observacion)
                  .trim()
            }
          )

        $q.notify({
          type: 'positive',
          position: 'top',
          message:
            data?.mensaje
            || 'Transferencia observada.'
        })

        await actualizarTodo()

        if (detalle.value?.id) {
          await abrirDetalle({
            id: detalle.value.id
          })
        }
      } catch (error) {
        $q.notify({
          type: 'negative',
          position: 'top',
          message: mensajeError(error)
        })
      }
    }
  )
}

onMounted(
  async () => {
    await cargarSindicatos()
    await actualizarTodo()
  }
)
</script>

<style scoped>
.stat-card {
  height: 100%;
  border-radius: 14px;
}

.dialog-card {
  width: 720px;
  max-width: 95vw;
}

:deep(.q-table__container) {
  border-radius: 12px;
}

@media (max-width: 599px) {
  .dialog-card {
    width: 100%;
  }
}
</style>
