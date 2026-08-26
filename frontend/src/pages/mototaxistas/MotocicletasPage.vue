<template>
  <q-page class="q-pa-md q-pa-lg-md motocicletas-page">
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

          <div class="min-width-zero">
            <div class="text-h5 text-weight-bold text-green-9">
              Motocicletas
            </div>
            <div class="text-caption text-grey-7">
              Registro, fotografía, placa, SOAT y asignación al mototaxista.
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-auto">
        <q-btn
          color="green-8"
          icon="add"
          label="Nueva motocicleta"
          unelevated
          class="full-width"
          @click="abrirFormulario()"
        />
      </div>
    </div>

    <!-- RESUMEN -->
    <div class="row q-col-gutter-md q-mb-md">
      <div class="col-6 col-md-3">
        <q-card flat bordered class="stat-card">
          <q-card-section>
            <div class="text-caption text-grey-7">Registradas</div>
            <div class="text-h5 text-weight-bold text-green-9">
              {{ estadisticas.total }}
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-6 col-md-3">
        <q-card flat bordered class="stat-card">
          <q-card-section>
            <div class="text-caption text-grey-7">Con placa</div>
            <div class="text-h5 text-weight-bold text-blue-8">
              {{ totalConPlaca }}
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-6 col-md-3">
        <q-card flat bordered class="stat-card">
          <q-card-section>
            <div class="text-caption text-grey-7">Con SOAT</div>
            <div class="text-h5 text-weight-bold text-positive">
              {{ totalConSoat }}
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-6 col-md-3">
        <q-card flat bordered class="stat-card">
          <q-card-section>
            <div class="text-caption text-grey-7">Sin SOAT</div>
            <div class="text-h5 text-weight-bold text-negative">
              {{ totalSinSoat }}
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- FILTROS -->
    <q-card flat bordered class="filtro-card q-mb-md">
      <q-card-section class="row q-col-gutter-md items-center">
        <div class="col-12 col-md">
          <q-input
            v-model="filtro"
            outlined
            dense
            debounce="250"
            placeholder="Buscar por placa, modelo, color, conductor o sindicato"
            clearable
          >
            <template #prepend>
              <q-icon name="search" color="green-8" />
            </template>
          </q-input>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <q-select
            v-model="filtroSoat"
            :options="['Todos', 'Con SOAT', 'Sin SOAT']"
            outlined
            dense
            label="SOAT"
          />
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <q-select
            v-model="filtroPlaca"
            :options="['Todos', 'Con placa', 'Sin placa']"
            outlined
            dense
            label="Placa"
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

    <!-- TARJETAS -->
    <div
      v-if="!loading && motocicletasFiltradas.length"
      class="row q-col-gutter-md"
    >
      <div
        v-for="m in motocicletasFiltradas"
        :key="m.id"
        class="col-12 col-md-6 col-xl-4"
      >
        <q-card flat bordered class="moto-card full-height">
          <div class="moto-card-strip bg-green-8" />

          <q-card-section class="row no-wrap items-start">
            <q-avatar
              rounded
              size="82px"
              color="green-1"
              text-color="green-9"
              class="q-mr-md moto-avatar"
            >
              <img
                v-if="fotoMotoUrl(m)"
                :src="fotoMotoUrl(m)"
                alt="Fotografía de la motocicleta"
                @error="marcarImagenFallida(m.id)"
              >
              <q-icon
                v-else
                name="two_wheeler"
                size="42px"
              />
            </q-avatar>

            <div class="col min-width-zero">
              <div class="text-subtitle1 text-weight-bold text-grey-9 ellipsis">
                {{ m.modelo || 'Motocicleta' }}
              </div>

              <div class="row q-gutter-xs q-mt-xs">
                <q-badge
                  :color="tienePlaca(m) ? 'blue-8' : 'grey-7'"
                >
                  {{ tienePlaca(m) ? (m.placa || 'Placa registrada') : 'Sin placa' }}
                </q-badge>

                <q-badge
                  :color="m.tiene_soat ? 'positive' : 'negative'"
                >
                  {{ m.tiene_soat ? 'SOAT: Sí' : 'SOAT: No' }}
                </q-badge>
              </div>

              <div class="text-caption text-grey-7 q-mt-sm">
                {{ m.color || 'Color no registrado' }}
              </div>
            </div>

            <q-btn flat round dense icon="more_vert" color="grey-7">
              <q-menu>
                <q-list style="min-width: 190px">
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

          <q-card-section class="q-gutter-y-sm">
            <div class="detail-row">
              <q-icon name="person" color="green-8" size="20px" />
              <div class="min-width-zero">
                <div class="detail-label">Mototaxista responsable</div>
                <div class="detail-value ellipsis">
                  {{ nombreConductor(m) }}
                </div>
                <div class="text-caption text-grey-6">
                  {{ m.mototaxista?.sindicato?.nombre || 'Sin sindicato' }}
                  <span v-if="m.mototaxista?.nro_chaleco">
                    · Chaleco {{ m.mototaxista.nro_chaleco }}
                  </span>
                </div>
              </div>
            </div>
          </q-card-section>

          <q-card-actions align="right" class="q-px-md q-pb-md">
            <q-btn
              flat
              color="green-8"
              icon="visibility"
              label="Ver detalles"
              no-caps
              @click="abrirDetalle(m)"
            />
          </q-card-actions>
        </q-card>
      </div>
    </div>

    <q-card
      v-if="!loading && !motocicletasFiltradas.length"
      flat
      bordered
      class="empty-card"
    >
      <q-card-section class="column items-center q-pa-xl text-grey-6">
        <q-icon name="two_wheeler" size="58px" />
        <div class="text-subtitle1 text-weight-medium q-mt-sm">
          No se encontraron motocicletas
        </div>
        <div class="text-caption text-center">
          Registra una motocicleta o cambia los filtros.
        </div>
      </q-card-section>
    </q-card>

    <div
      v-if="!loading && paginacion.lastPage > 1"
      class="row justify-center q-mt-lg"
    >
      <q-pagination
        v-model="paginacion.page"
        :max="paginacion.lastPage"
        :max-pages="7"
        direction-links
        boundary-links
        color="green-8"
        @update:model-value="cargarDatos"
      />
    </div>

    <!-- FORMULARIO -->
    <q-dialog v-model="dialogFormulario" persistent>
      <q-card class="dialog-card column no-wrap">
        <q-card-section class="bg-green-8 text-white row items-center">
          <q-icon name="two_wheeler" size="28px" class="q-mr-sm" />
          <div>
            <div class="text-h6 text-weight-bold">
              {{ editando ? 'Editar motocicleta' : 'Nueva motocicleta' }}
            </div>
            <div class="text-caption text-green-1">
              Datos del vehículo y su documentación básica.
            </div>
          </div>
          <q-space />
          <q-btn
            icon="close"
            flat
            round
            dense
            :disable="guardando"
            @click="dialogFormulario = false"
          />
        </q-card-section>

        <q-form class="dialog-form column no-wrap col" @submit.prevent="guardarMotocicleta">
          <q-card-section class="q-pa-lg dialog-body col scroll">
            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-6">
                <q-toggle
                  v-model="form.tiene_placa"
                  color="green-8"
                  label="¿Tiene placa?"
                  left-label
                />
              </div>

              <div class="col-12 col-sm-6">
                <q-toggle
                  v-model="form.tiene_soat"
                  color="green-8"
                  label="¿Tiene SOAT?"
                  left-label
                />
              </div>

              <div
                v-if="form.tiene_placa"
                class="col-12 col-sm-6"
              >
                <q-input
                  v-model.trim="form.placa"
                  outlined
                  label="Placa *"
                  placeholder="Ej: 4523-XYZ"
                  :rules="[requerido]"
                >
                  <template #prepend>
                    <q-icon name="confirmation_number" color="green-8" />
                  </template>
                </q-input>
              </div>

              <div :class="form.tiene_placa ? 'col-12 col-sm-6' : 'col-12'">
                <q-input
                  v-model.trim="form.color"
                  outlined
                  label="Color *"
                  placeholder="Ej: Rojo"
                  :rules="[requerido]"
                >
                  <template #prepend>
                    <q-icon name="palette" color="green-8" />
                  </template>
                </q-input>
              </div>

              <div class="col-12">
                <q-input
                  v-model.trim="form.modelo"
                  outlined
                  label="Modelo *"
                  placeholder="Ej: Kingo 2024"
                  :rules="[requerido]"
                >
                  <template #prepend>
                    <q-icon name="two_wheeler" color="green-8" />
                  </template>
                </q-input>
              </div>

              <div class="col-12">
                <q-input
                  v-model.trim="form.chasis"
                  outlined
                  label="N.º de chasis (opcional)"
                  placeholder="Número de chasis"
                >
                  <template #prepend>
                    <q-icon name="pin" color="green-8" />
                  </template>
                </q-input>
              </div>

              <div class="col-12">
                <q-select
                  v-model="form.id_mototaxista"
                  :options="mototaxistasFiltrables"
                  outlined
                  label="Mototaxista responsable *"
                  emit-value
                  map-options
                  option-value="id"
                  :option-label="labelMototaxista"
                  use-input
                  fill-input
                  hide-selected
                  input-debounce="300"
                  clearable
                  :loading="buscandoMototaxistas"
                  :hint="
                    form.id_mototaxista
                      ? undefined
                      : 'Escribe al menos 2 caracteres del nombre, CI o chaleco.'
                  "
                  hide-bottom-space
                  :rules="[requerido]"
                  @filter="filtrarMototaxistas"
                >
                  <template #prepend>
                    <q-icon name="person_search" color="green-8" />
                  </template>

                  <template #selected-item="scope">
                    <div class="persona-seleccionada q-py-xs">
                      <div class="persona-seleccionada__nombre">
                        {{ nombreMototaxistaOpcion(scope.opt) }}
                      </div>
                      <div class="persona-seleccionada__detalle">
                        CI {{ scope.opt?.persona?.ci || '—' }}
                        <span v-if="scope.opt?.nro_chaleco">
                          · Chaleco {{ scope.opt.nro_chaleco }}
                        </span>
                      </div>
                    </div>
                  </template>

                  <template #option="scope">
                    <q-item v-bind="scope.itemProps">
                      <q-item-section avatar>
                        <q-avatar
                          color="green-1"
                          text-color="green-9"
                          icon="two_wheeler"
                        />
                      </q-item-section>

                      <q-item-section>
                        <q-item-label class="text-weight-medium">
                          {{ nombreMototaxistaOpcion(scope.opt) }}
                        </q-item-label>
                        <q-item-label caption>
                          CI {{ scope.opt?.persona?.ci || '—' }}
                          · Chaleco {{ scope.opt?.nro_chaleco || '—' }}
                          <span v-if="scope.opt?.sindicato?.nombre">
                            · {{ scope.opt.sindicato.nombre }}
                          </span>
                        </q-item-label>
                      </q-item-section>
                    </q-item>
                  </template>

                  <template #no-option>
                    <q-item>
                      <q-item-section class="text-grey-7">
                        {{ mensajeBusquedaMototaxista }}
                      </q-item-section>
                    </q-item>
                  </template>
                </q-select>
              </div>

              <div class="col-12">
                <q-file
                  v-model="archivoImagenFormulario"
                  outlined
                  clearable
                  accept=".jpg,.jpeg,.png,.webp"
                  max-file-size="4194304"
                  :label="
                    editando
                      ? 'Reemplazar fotografía (opcional)'
                      : 'Fotografía de la motocicleta (opcional)'
                  "
                  hint="JPG, PNG o WEBP. Máximo 4 MB."
                  @rejected="archivoRechazado"
                >
                  <template #prepend>
                    <q-icon name="photo_camera" color="green-8" />
                  </template>
                </q-file>
              </div>
            </div>

            <q-banner
              rounded
              class="bg-blue-1 text-blue-10 q-mt-md"
            >
              <template #avatar>
                <q-icon name="info" color="blue-8" />
              </template>
              Si la motocicleta todavía no tiene placa, desactiva “¿Tiene placa?”.
              La placa puede agregarse después editando el registro.
            </q-banner>
          </q-card-section>

          <q-card-actions align="right" class="q-pa-md bg-grey-1 dialog-actions">
            <q-btn
              flat
              label="Cancelar"
              color="grey-7"
              :disable="guardando"
              @click="dialogFormulario = false"
            />
            <q-btn
              type="submit"
              :label="editando ? 'Guardar cambios' : 'Registrar'"
              color="green-8"
              icon="save"
              unelevated
              :loading="guardando"
            />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- DETALLE Y FOTOGRAFÍA -->
    <q-dialog v-model="dialogDetalle">
      <q-card class="detalle-card">
        <q-card-section class="bg-green-8 text-white row items-center">
          <q-icon name="two_wheeler" size="30px" class="q-mr-sm" />
          <div>
            <div class="text-h6 text-weight-bold">Detalle de la motocicleta</div>
            <div class="text-caption text-green-1">
              Información, documentación y fotografía.
            </div>
          </div>
          <q-space />
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>

        <q-card-section v-if="seleccionada" class="q-pa-lg">
          <div class="row q-col-gutter-lg">
            <div class="col-12 col-md-5">
              <div class="foto-principal-wrapper">
                <q-img
                  v-if="fotoMotoUrl(seleccionada)"
                  :src="fotoMotoUrl(seleccionada)"
                  ratio="4/3"
                  fit="cover"
                  class="foto-principal"
                  @error="marcarImagenFallida(seleccionada.id)"
                />
                <div
                  v-else
                  class="foto-placeholder column items-center justify-center"
                >
                  <q-icon name="two_wheeler" size="72px" color="green-7" />
                  <div class="text-caption text-grey-7 q-mt-sm">
                    Sin fotografía
                  </div>
                </div>
              </div>

              <q-file
                v-model="archivoImagen"
                outlined
                dense
                accept=".jpg,.jpeg,.png,.webp"
                max-file-size="4194304"
                label="Seleccionar nueva fotografía"
                class="q-mt-md"
                @rejected="archivoRechazado"
              >
                <template #prepend>
                  <q-icon name="photo_camera" color="green-8" />
                </template>
              </q-file>

              <div class="row q-gutter-sm q-mt-sm">
                <q-btn
                  color="green-8"
                  icon="cloud_upload"
                  :label="fotoMotoUrl(seleccionada) ? 'Cambiar foto' : 'Subir foto'"
                  unelevated
                  no-caps
                  :disable="!archivoImagen"
                  :loading="subiendoImagen"
                  @click="subirImagen"
                />

                <q-btn
                  v-if="imagenPrincipal(seleccionada)"
                  flat
                  color="negative"
                  icon="delete"
                  label="Quitar foto"
                  no-caps
                  :disable="subiendoImagen"
                  @click="eliminarImagenActual"
                />
              </div>
            </div>

            <div class="col-12 col-md-7">
              <div class="text-h5 text-weight-bold text-green-9">
                {{ seleccionada.modelo || 'Motocicleta' }}
              </div>

              <div class="row q-gutter-xs q-mt-sm q-mb-lg">
                <q-chip
                  dense
                  :color="tienePlaca(seleccionada) ? 'blue-1' : 'grey-3'"
                  :text-color="tienePlaca(seleccionada) ? 'blue-9' : 'grey-8'"
                  icon="confirmation_number"
                >
                  {{ tienePlaca(seleccionada) ? (seleccionada.placa || 'Placa registrada') : 'Sin placa' }}
                </q-chip>

                <q-chip
                  dense
                  :color="seleccionada.tiene_soat ? 'green-1' : 'red-1'"
                  :text-color="seleccionada.tiene_soat ? 'green-9' : 'red-9'"
                  icon="verified_user"
                >
                  {{ seleccionada.tiene_soat ? 'SOAT: Sí' : 'SOAT: No' }}
                </q-chip>
              </div>

              <q-list bordered separator class="detalle-lista">
                <q-item>
                  <q-item-section avatar>
                    <q-icon name="palette" color="green-8" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label caption>Color</q-item-label>
                    <q-item-label>{{ seleccionada.color || 'No registrado' }}</q-item-label>
                  </q-item-section>
                </q-item>

                <q-item>
                  <q-item-section avatar>
                    <q-icon name="confirmation_number" color="green-8" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label caption>Placa</q-item-label>
                    <q-item-label>
                      {{ tienePlaca(seleccionada) ? (seleccionada.placa || 'Pendiente') : 'No tiene placa' }}
                    </q-item-label>
                  </q-item-section>
                </q-item>

                <q-item>
                  <q-item-section avatar>
                    <q-icon name="verified_user" color="green-8" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label caption>SOAT</q-item-label>
                    <q-item-label>
                      {{ seleccionada.tiene_soat ? 'Sí tiene SOAT' : 'No tiene SOAT' }}
                    </q-item-label>
                  </q-item-section>
                </q-item>

                <q-item>
                  <q-item-section avatar>
                    <q-icon name="pin" color="green-8" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label caption>N.º de chasis</q-item-label>
                    <q-item-label>{{ seleccionada.chasis || 'No registrado' }}</q-item-label>
                  </q-item-section>
                </q-item>

                <q-item>
                  <q-item-section avatar>
                    <q-avatar size="38px" color="green-1" text-color="green-9">
                      <img
                        v-if="fotoConductorUrl(seleccionada)"
                        :src="fotoConductorUrl(seleccionada)"
                      >
                      <q-icon v-else name="person" />
                    </q-avatar>
                  </q-item-section>
                  <q-item-section>
                    <q-item-label caption>Mototaxista responsable</q-item-label>
                    <q-item-label class="text-weight-medium">
                      {{ nombreConductor(seleccionada) }}
                    </q-item-label>
                    <q-item-label caption>
                      {{ seleccionada.mototaxista?.sindicato?.nombre || 'Sin sindicato' }}
                      <span v-if="seleccionada.mototaxista?.nro_chaleco">
                        · Chaleco {{ seleccionada.mototaxista.nro_chaleco }}
                      </span>
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md bg-grey-1">
          <q-btn
            flat
            color="grey-7"
            label="Cerrar"
            v-close-popup
          />
          <q-btn
            color="green-8"
            icon="edit"
            label="Editar datos"
            unelevated
            @click="editarDesdeDetalle"
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
import motocicletaService from 'src/services/motocicletaService'
import mototaxistaService from 'src/services/mototaxistaService'
import { API_ORIGIN } from 'src/config/runtime.js'

const $q = useQuasar()

const motocicletas = ref([])
const mototaxistasFiltrables = ref([])
const buscandoMototaxistas = ref(false)
const terminoMototaxista = ref('')
let secuenciaBusquedaMototaxista = 0

const filtro = ref('')
const filtroSoat = ref('Todos')
const filtroPlaca = ref('Todos')

const paginacion = ref({
  page: 1,
  lastPage: 1,
  perPage: 12,
  total: 0
})

const estadisticas = ref({
  total: 0,
  con_placa: 0,
  con_soat: 0,
  sin_soat: 0
})

const loading = ref(false)
const guardando = ref(false)
const dialogFormulario = ref(false)
const editando = ref(false)

const dialogDetalle = ref(false)
const seleccionada = ref(null)
const archivoImagen = ref(null)
const archivoImagenFormulario = ref(null)
const subiendoImagen = ref(false)
const imagenesFallidas = ref(new Set())

const formDefault = {
  id: null,
  placa: '',
  chasis: '',
  modelo: '',
  color: '',
  tiene_placa: true,
  tiene_soat: false,
  id_mototaxista: null
}

const form = ref({ ...formDefault })

const totalConPlaca = computed(() =>
  Number(estadisticas.value.con_placa) || 0
)

const totalConSoat = computed(() =>
  Number(estadisticas.value.con_soat) || 0
)

const totalSinSoat = computed(() =>
  Number(estadisticas.value.sin_soat) || 0
)

const motocicletasFiltradas = computed(() =>
  motocicletas.value
)

const mensajeBusquedaMototaxista = computed(() => {
  const texto = String(
    terminoMototaxista.value || ''
  ).trim()

  if (buscandoMototaxistas.value) {
    return 'Buscando mototaxistas...'
  }

  if (texto.length < 2) {
    return 'Escribe al menos 2 caracteres para buscar.'
  }

  return 'No se encontraron mototaxistas con ese criterio.'
})

function requerido(valor) {
  return Boolean(String(valor ?? '').trim())
    || 'Este campo es obligatorio.'
}

function tienePlaca(m) {
  if (typeof m?.tiene_placa === 'boolean') {
    return m.tiene_placa
  }

  return Boolean(String(m?.placa || '').trim())
}

function nombreConductor(row) {
  const persona = row?.mototaxista?.persona
  const nombre = [persona?.nombre, persona?.apellidos]
    .filter(Boolean)
    .join(' ')
    .trim()

  return nombre || 'Sin asignar'
}

function apiOrigen() {
  return API_ORIGIN
}

function imagenPrincipal(m) {
  const imagenes = Array.isArray(m?.imagenes)
    ? m.imagenes
    : []

  return imagenes[imagenes.length - 1] || null
}

function construirArchivoUrl(ruta) {
  if (!ruta) return ''

  if (/^https?:\/\//i.test(ruta)) {
    return ruta
  }

  return `${apiOrigen()}/storage/${String(ruta).replace(/^\/+/, '')}`
}

function fotoMotoUrl(m) {
  if (!m?.id || imagenesFallidas.value.has(m.id)) {
    return ''
  }

  return construirArchivoUrl(
    imagenPrincipal(m)?.ruta
  )
}

function fotoConductorUrl(m) {
  const imagenes = Array.isArray(m?.mototaxista?.persona?.imagenes)
    ? m.mototaxista.persona.imagenes
    : []

  const imagen = imagenes[imagenes.length - 1]
  return construirArchivoUrl(imagen?.ruta)
}

function marcarImagenFallida(id) {
  const nuevo = new Set(imagenesFallidas.value)
  nuevo.add(id)
  imagenesFallidas.value = nuevo
}

function nombreMototaxistaOpcion(m) {
  if (!m) return ''

  return [
    m.persona?.nombre,
    m.persona?.apellidos
  ]
    .filter(Boolean)
    .join(' ')
    .trim()
    || `Mototaxista #${m.id}`
}

function labelMototaxista(m) {
  if (!m) return ''

  return (
    nombreMototaxistaOpcion(m)
    + (
      m.persona?.ci
        ? ` · CI ${m.persona.ci}`
        : ''
    )
    + (
      m.nro_chaleco
        ? ` · Chaleco ${m.nro_chaleco}`
        : ''
    )
  )
}

function mensajeError(error, porDefecto = 'No se pudo completar la operación.') {
  const errores = error?.response?.data?.errors
  const primerError = errores
    ? Object.values(errores).flat().find(Boolean)
    : null

  return primerError
    || error?.response?.data?.mensaje
    || error?.response?.data?.message
    || porDefecto
}

async function cargarDatos() {
  if (loading.value) return

  loading.value = true

  try {
    const respuesta = await motocicletaService.getAll({
      paginated: 1,
      page: paginacion.value.page,
      per_page: paginacion.value.perPage,
      q: String(filtro.value || '').trim() || undefined,
      soat:
        filtroSoat.value === 'Con SOAT'
          ? 'con'
          : filtroSoat.value === 'Sin SOAT'
            ? 'sin'
            : undefined,
      placa:
        filtroPlaca.value === 'Con placa'
          ? 'con'
          : filtroPlaca.value === 'Sin placa'
            ? 'sin'
            : undefined
    })

    motocicletas.value = Array.isArray(respuesta.data?.data)
      ? respuesta.data.data
      : []

    const meta = respuesta.data?.meta || {}

    paginacion.value = {
      ...paginacion.value,
      page: Number(meta.current_page) || 1,
      lastPage: Number(meta.last_page) || 1,
      perPage: Number(meta.per_page) || paginacion.value.perPage,
      total: Number(meta.total) || 0
    }

    estadisticas.value = {
      total: Number(meta.stats?.total) || 0,
      con_placa: Number(meta.stats?.con_placa) || 0,
      con_soat: Number(meta.stats?.con_soat) || 0,
      sin_soat: Number(meta.stats?.sin_soat) || 0
    }

    imagenesFallidas.value = new Set()
  } catch (error) {
    console.error('Error cargando motocicletas:', error)
    $q.notify({
      type: 'negative',
      position: 'top',
      message: mensajeError(
        error,
        'No fue posible cargar las motocicletas.'
      )
    })
  } finally {
    loading.value = false
  }
}

async function filtrarMototaxistas(valor, update) {
  const texto = String(valor || '').trim()
  terminoMototaxista.value = texto

  if (texto.length < 2) {
    secuenciaBusquedaMototaxista += 1
    buscandoMototaxistas.value = false

    update(() => {
      mototaxistasFiltrables.value = []
    })

    return
  }

  const secuencia = ++secuenciaBusquedaMototaxista
  buscandoMototaxistas.value = true

  try {
    const respuesta = await mototaxistaService.opcionesMotocicleta({
      q: texto,
      include_id: form.value.id_mototaxista || undefined
    })

    if (secuencia !== secuenciaBusquedaMototaxista) return

    update(() => {
      mototaxistasFiltrables.value = Array.isArray(respuesta.data)
        ? respuesta.data
        : []
    })
  } catch (error) {
    if (secuencia !== secuenciaBusquedaMototaxista) return

    console.error('Error buscando mototaxistas:', error)

    update(() => {
      mototaxistasFiltrables.value = []
    })
  } finally {
    if (secuencia === secuenciaBusquedaMototaxista) {
      buscandoMototaxistas.value = false
    }
  }
}

function abrirFormulario(row = null) {
  secuenciaBusquedaMototaxista += 1
  terminoMototaxista.value = ''
  buscandoMototaxistas.value = false
  archivoImagenFormulario.value = null

  if (row) {
    editando.value = true
    form.value = {
      id: row.id,
      placa: row.placa || '',
      chasis: row.chasis || '',
      modelo: row.modelo || '',
      color: row.color || '',
      tiene_placa: tienePlaca(row),
      tiene_soat: Boolean(row.tiene_soat),
      id_mototaxista:
        row.id_mototaxista
        || row.mototaxista?.id
        || null
    }

    mototaxistasFiltrables.value = row.mototaxista
      ? [row.mototaxista]
      : []
  } else {
    editando.value = false
    form.value = { ...formDefault }
    mototaxistasFiltrables.value = []
  }

  dialogFormulario.value = true
}

async function guardarMotocicleta() {
  if (
    (form.value.tiene_placa && !String(form.value.placa || '').trim())
    || !String(form.value.modelo || '').trim()
    || !String(form.value.color || '').trim()
    || !form.value.id_mototaxista
  ) {
    $q.notify({
      type: 'warning',
      position: 'top',
      message: 'Completa todos los campos obligatorios.'
    })
    return
  }

  guardando.value = true

  const payload = {
    placa: form.value.tiene_placa
      ? String(form.value.placa || '').trim().toUpperCase()
      : null,
    chasis: String(form.value.chasis || '').trim() || null,
    modelo: String(form.value.modelo || '').trim(),
    color: String(form.value.color || '').trim(),
    tiene_placa: Boolean(form.value.tiene_placa),
    tiene_soat: Boolean(form.value.tiene_soat),
    id_mototaxista: form.value.id_mototaxista
  }

  try {
    const eraEdicion = editando.value

    const respuesta = eraEdicion
      ? await motocicletaService.update(form.value.id, payload)
      : await motocicletaService.create(payload)

    const motocicletaId = Number(
      respuesta?.data?.id
      || respuesta?.data?.data?.id
      || form.value.id
    )

    let fotoGuardada = false
    let errorFoto = null

    if (archivoImagenFormulario.value && motocicletaId) {
      try {
        await motocicletaService.uploadImage(
          motocicletaId,
          archivoImagenFormulario.value
        )
        fotoGuardada = true
      } catch (error) {
        errorFoto = error
        console.error('La motocicleta se guardó, pero la foto falló:', error)
      }
    }

    dialogFormulario.value = false
    archivoImagenFormulario.value = null
    await cargarDatos()

    if (errorFoto) {
      $q.notify({
        type: 'warning',
        position: 'top',
        multiLine: true,
        message:
          'La motocicleta se guardó correctamente, pero no se pudo subir la fotografía. ' +
          mensajeError(errorFoto, '')
      })
    } else {
      $q.notify({
        type: 'positive',
        position: 'top',
        message: eraEdicion
          ? (fotoGuardada
              ? 'Motocicleta y fotografía actualizadas correctamente.'
              : 'Motocicleta actualizada correctamente.')
          : (fotoGuardada
              ? 'Motocicleta y fotografía registradas correctamente.'
              : 'Motocicleta registrada correctamente.')
      })
    }

    if (!eraEdicion && motocicletaId) {
      await abrirDetalle({ id: motocicletaId })
    }
  } catch (error) {
    console.error('Error guardando motocicleta:', error)
    $q.notify({
      type: 'negative',
      position: 'top',
      message: mensajeError(
        error,
        'No fue posible guardar la motocicleta.'
      )
    })
  } finally {
    guardando.value = false
  }
}

async function abrirDetalle(row) {
  try {
    const respuesta = await motocicletaService.getById(row.id)
    seleccionada.value = respuesta.data
    archivoImagen.value = null
    imagenesFallidas.value.delete(row.id)
    dialogDetalle.value = true
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      message: mensajeError(error)
    })
  }
}

function editarDesdeDetalle() {
  if (!seleccionada.value) return

  const item = { ...seleccionada.value }
  dialogDetalle.value = false
  abrirFormulario(item)
}

function archivoRechazado() {
  $q.notify({
    type: 'warning',
    position: 'top',
    message: 'Usa una imagen JPG, PNG o WEBP de máximo 4 MB.'
  })
}

async function subirImagen() {
  if (!seleccionada.value?.id || !archivoImagen.value) {
    return
  }

  subiendoImagen.value = true

  try {
    const respuesta = await motocicletaService.uploadImage(
      seleccionada.value.id,
      archivoImagen.value
    )

    seleccionada.value = respuesta.data?.data || seleccionada.value
    archivoImagen.value = null
    imagenesFallidas.value.delete(seleccionada.value.id)

    $q.notify({
      type: 'positive',
      position: 'top',
      message: respuesta.data?.mensaje
        || 'Fotografía actualizada correctamente.'
    })

    await cargarDatos()

    const refrescada = motocicletas.value.find(
      m => m.id === seleccionada.value?.id
    )

    if (refrescada) {
      const detalle = await motocicletaService.getById(refrescada.id)
      seleccionada.value = detalle.data
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      message: mensajeError(error, 'No se pudo subir la fotografía.')
    })
  } finally {
    subiendoImagen.value = false
  }
}

function eliminarImagenActual() {
  const imagen = imagenPrincipal(seleccionada.value)
  if (!imagen?.id) return

  $q.dialog({
    title: 'Quitar fotografía',
    message: '¿Deseas eliminar la fotografía actual de esta motocicleta?',
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Eliminar', color: 'negative' },
    persistent: true
  }).onOk(async () => {
    try {
      await motocicletaService.deleteImage(imagen.id)
      const detalle = await motocicletaService.getById(seleccionada.value.id)
      seleccionada.value = detalle.data
      imagenesFallidas.value.delete(seleccionada.value.id)
      await cargarDatos()

      $q.notify({
        type: 'positive',
        position: 'top',
        message: 'Fotografía eliminada correctamente.'
      })
    } catch (error) {
      $q.notify({
        type: 'negative',
        position: 'top',
        message: mensajeError(error)
      })
    }
  })
}

function confirmarEliminar(row) {
  const identificador = tienePlaca(row)
    ? `con placa ${row.placa}`
    : `${row.modelo || 'sin placa'}`

  $q.dialog({
    title: 'Eliminar motocicleta',
    message: `¿Eliminar la motocicleta ${identificador}?`,
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Eliminar', color: 'negative' },
    persistent: true
  }).onOk(async () => {
    try {
      await motocicletaService.delete(row.id)
      $q.notify({
        type: 'positive',
        position: 'top',
        message: 'Motocicleta eliminada correctamente.'
      })
      await cargarDatos()
    } catch (error) {
      $q.notify({
        type: 'negative',
        position: 'top',
        message: mensajeError(
          error,
          'No fue posible eliminar la motocicleta.'
        )
      })
    }
  })
}

let temporizadorFiltros = null

watch(
  [filtro, filtroSoat, filtroPlaca],
  () => {
    if (temporizadorFiltros) {
      clearTimeout(temporizadorFiltros)
    }

    temporizadorFiltros = setTimeout(() => {
      paginacion.value.page = 1
      cargarDatos()
    }, 280)
  }
)

onMounted(cargarDatos)
</script>

<style scoped>
.motocicletas-page {
  min-height: 100%;
  background: #f6faef;
}

.stat-card,
.filtro-card,
.moto-card,
.empty-card {
  border-color: #dce8da;
  border-radius: 16px;
  background: #fff;
}

.stat-card {
  height: 100%;
}

.moto-card {
  position: relative;
  overflow: hidden;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.moto-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 28px rgba(27, 94, 32, 0.11);
}

.moto-card-strip {
  height: 5px;
}

.moto-avatar {
  overflow: hidden;
}

.moto-avatar :deep(img) {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.detail-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.detail-label {
  color: #78909c;
  font-size: 0.75rem;
}

.detail-value {
  color: #263238;
  font-weight: 600;
}

.dialog-card {
  width: 600px;
  max-width: calc(100vw - 24px);
  max-height: calc(100vh - 32px);
  border-radius: 18px;
  overflow: hidden;
}

.dialog-form {
  min-height: 0;
  overflow: hidden;
}

.dialog-body {
  min-height: 0;
  overflow-y: auto;
}

.dialog-actions {
  flex: 0 0 auto;
  border-top: 1px solid #e0e0e0;
}

.detalle-card {
  width: 860px;
  max-width: calc(100vw - 24px);
  border-radius: 18px;
  overflow: hidden;
}

.foto-principal-wrapper {
  overflow: hidden;
  min-height: 230px;
  border: 1px solid #dce8da;
  border-radius: 16px;
  background: #f1f8e9;
}

.foto-principal {
  min-height: 230px;
}

.foto-placeholder {
  min-height: 230px;
}

.detalle-lista {
  overflow: hidden;
  border-radius: 14px;
  border-color: #dce8da;
}

.min-width-zero {
  min-width: 0;
}

@media (max-width: 599px) {
  .motocicletas-page {
    padding-left: 10px;
    padding-right: 10px;
  }

  .detalle-card,
  .dialog-card {
    width: calc(100vw - 16px);
  }

  .dialog-card {
    max-height: calc(100vh - 16px);
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

.persona-seleccionada__detalle {
  margin-top: 4px;
  color: #6b7280;
  font-size: 12px;
}

</style>
