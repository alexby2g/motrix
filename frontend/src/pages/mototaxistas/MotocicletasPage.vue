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
              Registro y asignación de motocicletas a mototaxistas.
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
          @click="openDialogForm()"
        />
      </div>
    </div>

    <div class="row q-col-gutter-md q-mb-md">
      <div class="col-6 col-md-4">
        <q-card flat bordered class="stat-card">
          <q-card-section>
            <div class="text-caption text-grey-7">Registradas</div>
            <div class="text-h5 text-weight-bold text-green-9">
              {{ motocicletas.length }}
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-6 col-md-4">
        <q-card flat bordered class="stat-card">
          <q-card-section>
            <div class="text-caption text-grey-7">Con conductor</div>
            <div class="text-h5 text-weight-bold text-positive">
              {{ totalAsignadas }}
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-4">
        <q-card flat bordered class="stat-card">
          <q-card-section>
            <div class="text-caption text-grey-7">Sin asignación</div>
            <div class="text-h5 text-weight-bold text-orange-8">
              {{ totalSinAsignar }}
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <q-card flat bordered class="filtro-card q-mb-md">
      <q-card-section>
        <q-input
          v-model="filter"
          outlined
          dense
          debounce="250"
          placeholder="Buscar por placa, modelo, color o conductor"
          clearable
        >
          <template #prepend>
            <q-icon name="search" color="green-8" />
          </template>
        </q-input>
      </q-card-section>
    </q-card>

    <q-table
      :rows="motocicletas"
      :columns="columns"
      row-key="id"
      :filter="filter"
      :loading="loading"
      :grid="$q.screen.lt.md"
      flat
      bordered
      binary-state-sort
      no-data-label="No se encontraron motocicletas"
      class="motocicletas-table"
    >
      <template #loading>
        <q-inner-loading showing color="green-8" />
      </template>

      <template #body-cell-placa="props">
        <q-td :props="props">
          <q-chip
            color="green-1"
            text-color="green-9"
            icon="confirmation_number"
            class="text-weight-bold"
          >
            {{ props.row.placa }}
          </q-chip>
        </q-td>
      </template>

      <template #body-cell-mototaxista="props">
        <q-td :props="props">
          <div class="row items-center no-wrap">
            <q-avatar
              size="32px"
              color="green-1"
              text-color="green-9"
              icon="person"
              class="q-mr-sm"
            />
            <div>
              <div class="text-weight-medium text-grey-9">
                {{ nombreConductor(props.row) }}
              </div>
              <div
                v-if="props.row.mototaxista?.nro_chaleco"
                class="text-caption text-grey-6"
              >
                Chaleco {{ props.row.mototaxista.nro_chaleco }}
              </div>
            </div>
          </div>
        </q-td>
      </template>

      <template #body-cell-actions="props">
        <q-td :props="props" class="text-center">
          <q-btn
            flat
            round
            color="green-8"
            icon="edit"
            size="sm"
            @click="openDialogForm(props.row)"
          >
            <q-tooltip>Editar motocicleta</q-tooltip>
          </q-btn>
          <q-btn
            flat
            round
            color="negative"
            icon="delete"
            size="sm"
            @click="confirmDelete(props.row)"
          >
            <q-tooltip>Eliminar motocicleta</q-tooltip>
          </q-btn>
        </q-td>
      </template>

      <template #item="props">
        <div class="q-pa-xs col-12 col-sm-6">
          <q-card flat bordered class="moto-card full-height">
            <div class="moto-card-strip bg-green-8" />

            <q-card-section class="row items-start no-wrap">
              <q-avatar
                color="green-1"
                text-color="green-9"
                icon="two_wheeler"
                size="52px"
                class="q-mr-md"
              />

              <div class="col min-width-zero">
                <div class="text-subtitle1 text-weight-bold text-grey-9">
                  {{ props.row.modelo || 'Motocicleta' }}
                </div>
                <q-chip
                  dense
                  color="green-1"
                  text-color="green-9"
                  icon="confirmation_number"
                  class="q-mt-xs text-weight-bold"
                >
                  {{ props.row.placa || 'Sin placa' }}
                </q-chip>
              </div>

              <q-btn flat round dense icon="more_vert" color="grey-7">
                <q-menu>
                  <q-list style="min-width: 170px">
                    <q-item
                      clickable
                      v-close-popup
                      @click="openDialogForm(props.row)"
                    >
                      <q-item-section avatar>
                        <q-icon name="edit" color="green-8" />
                      </q-item-section>
                      <q-item-section>Editar</q-item-section>
                    </q-item>
                    <q-item
                      clickable
                      v-close-popup
                      @click="confirmDelete(props.row)"
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
                <q-icon name="palette" color="green-8" size="20px" />
                <div>
                  <div class="detail-label">Color</div>
                  <div class="detail-value">
                    {{ props.row.color || 'No registrado' }}
                  </div>
                </div>
              </div>

              <div class="detail-row">
                <q-icon name="person" color="green-8" size="20px" />
                <div class="min-width-zero">
                  <div class="detail-label">Mototaxista asignado</div>
                  <div class="detail-value ellipsis">
                    {{ nombreConductor(props.row) }}
                  </div>
                  <div
                    v-if="props.row.mototaxista?.nro_chaleco"
                    class="text-caption text-grey-6"
                  >
                    Chaleco {{ props.row.mototaxista.nro_chaleco }}
                  </div>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>
      </template>

      <template #no-data>
        <div class="full-width column items-center q-pa-xl text-grey-6">
          <q-icon name="two_wheeler" size="58px" />
          <div class="text-subtitle1 text-weight-medium q-mt-sm">
            No se encontraron motocicletas
          </div>
          <div class="text-caption text-center">
            Registra una motocicleta para comenzar.
          </div>
        </div>
      </template>
    </q-table>

    <q-dialog v-model="dialogOpen" persistent>
      <q-card class="dialog-card">
        <q-card-section class="bg-green-8 text-white row items-center">
          <q-icon name="two_wheeler" size="28px" class="q-mr-sm" />
          <div>
            <div class="text-h6 text-weight-bold">
              {{ isEditing ? 'Editar motocicleta' : 'Nueva motocicleta' }}
            </div>
            <div class="text-caption text-green-1">
              Datos del vehículo y mototaxista responsable.
            </div>
          </div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-form @submit.prevent="saveMotocicleta">
          <q-card-section class="q-pa-lg">
            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-6">
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

              <div class="col-12 col-sm-6">
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
                <q-select
                  v-model="form.id_mototaxista"
                  :options="mototaxistasOptions"
                  outlined
                  label="Mototaxista asignado *"
                  emit-value
                  map-options
                  option-value="id"
                  option-label="nombre_completo"
                  use-input
                  input-debounce="0"
                  clearable
                  :rules="[requerido]"
                >
                  <template #prepend>
                    <q-icon name="person" color="green-8" />
                  </template>
                </q-select>
              </div>
            </div>
          </q-card-section>

          <q-card-actions align="right" class="q-pa-md bg-grey-1">
            <q-btn
              flat
              label="Cancelar"
              color="grey-7"
              :disable="saving"
              v-close-popup
            />
            <q-btn
              type="submit"
              :label="isEditing ? 'Guardar cambios' : 'Registrar'"
              color="green-8"
              icon="save"
              unelevated
              :loading="saving"
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
import motocicletaService from 'src/services/motocicletaService'
import mototaxistaService from 'src/services/mototaxistaService'

const $q = useQuasar()

const motocicletas = ref([])
const mototaxistasOptions = ref([])
const filter = ref('')
const loading = ref(false)
const saving = ref(false)
const dialogOpen = ref(false)
const isEditing = ref(false)

const formDefault = {
  id: null,
  placa: '',
  modelo: '',
  color: '',
  id_mototaxista: null
}

const form = ref({ ...formDefault })

const columns = [
  {
    name: 'placa',
    label: 'Placa',
    align: 'left',
    field: 'placa',
    sortable: true
  },
  {
    name: 'modelo',
    label: 'Modelo',
    align: 'left',
    field: 'modelo',
    sortable: true
  },
  {
    name: 'color',
    label: 'Color',
    align: 'left',
    field: 'color',
    sortable: true
  },
  {
    name: 'mototaxista',
    label: 'Mototaxista asignado',
    align: 'left',
    field: row => nombreConductor(row),
    sortable: true
  },
  {
    name: 'actions',
    label: 'Acciones',
    align: 'center',
    field: 'actions'
  }
]

const totalAsignadas = computed(() => (
  motocicletas.value.filter(m => Boolean(m.id_mototaxista)).length
))

const totalSinAsignar = computed(() => (
  motocicletas.value.length - totalAsignadas.value
))

function requerido(valor) {
  return Boolean(valor) || 'Este campo es obligatorio.'
}

function nombreConductor(row) {
  const persona = row?.mototaxista?.persona
  const nombre = [persona?.nombre, persona?.apellidos]
    .filter(Boolean)
    .join(' ')
    .trim()

  return nombre || 'Sin asignar'
}

function mensajeError(error, porDefecto) {
  return error?.response?.data?.mensaje
    || error?.response?.data?.message
    || porDefecto
}

async function cargarDatos() {
  loading.value = true

  try {
    const [resMotos, resTaxistas] = await Promise.all([
      motocicletaService.getAll(),
      mototaxistaService.getAll()
    ])

    motocicletas.value = Array.isArray(resMotos.data)
      ? resMotos.data
      : (resMotos.data?.data || [])

    const mototaxistas = Array.isArray(resTaxistas.data)
      ? resTaxistas.data
      : (resTaxistas.data?.data || [])

    mototaxistasOptions.value = mototaxistas.map(m => {
      const persona = m.persona || {}
      const nombre = [persona.nombre, persona.apellidos]
        .filter(Boolean)
        .join(' ')
        .trim()

      const chaleco = m.nro_chaleco
        ? ` · Chaleco ${m.nro_chaleco}`
        : ''

      return {
        id: m.id,
        nombre_completo: `${nombre || 'Mototaxista'}${chaleco}`
      }
    })
  } catch (error) {
    console.error('Error cargando motocicletas:', error)
    $q.notify({
      type: 'negative',
      position: 'top',
      message: mensajeError(error, 'No fue posible cargar las motocicletas.')
    })
  } finally {
    loading.value = false
  }
}

function openDialogForm(row = null) {
  if (row) {
    isEditing.value = true
    form.value = {
      id: row.id,
      placa: row.placa || '',
      modelo: row.modelo || '',
      color: row.color || '',
      id_mototaxista: row.id_mototaxista || row.mototaxista?.id || null
    }
  } else {
    isEditing.value = false
    form.value = { ...formDefault }
  }

  dialogOpen.value = true
}

async function saveMotocicleta() {
  if (
    !form.value.placa
    || !form.value.modelo
    || !form.value.color
    || !form.value.id_mototaxista
  ) {
    $q.notify({
      type: 'warning',
      position: 'top',
      message: 'Completa todos los campos obligatorios.'
    })
    return
  }

  saving.value = true

  const payload = {
    placa: form.value.placa,
    modelo: form.value.modelo,
    color: form.value.color,
    id_mototaxista: form.value.id_mototaxista
  }

  try {
    if (isEditing.value) {
      await motocicletaService.update(form.value.id, payload)
    } else {
      await motocicletaService.create(payload)
    }

    $q.notify({
      type: 'positive',
      position: 'top',
      message: isEditing.value
        ? 'Motocicleta actualizada correctamente.'
        : 'Motocicleta registrada correctamente.'
    })

    dialogOpen.value = false
    await cargarDatos()
  } catch (error) {
    console.error('Error guardando motocicleta:', error)

    const validacion = error?.response?.data?.errors
    const primerError = validacion
      ? Object.values(validacion)?.flat()?.[0]
      : null

    $q.notify({
      type: 'negative',
      position: 'top',
      message: primerError || mensajeError(
        error,
        'No fue posible guardar la motocicleta.'
      )
    })
  } finally {
    saving.value = false
  }
}

function confirmDelete(row) {
  $q.dialog({
    title: 'Eliminar motocicleta',
    message: `¿Eliminar la motocicleta con placa ${row.placa}?`,
    cancel: {
      label: 'Cancelar',
      flat: true
    },
    ok: {
      label: 'Eliminar',
      color: 'negative'
    },
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
      console.error('Error eliminando motocicleta:', error)
      $q.notify({
        type: 'negative',
        position: 'top',
        message: mensajeError(error, 'No fue posible eliminar la motocicleta.')
      })
    }
  })
}

onMounted(cargarDatos)
</script>

<style scoped>
.motocicletas-page {
  min-height: 100%;
  background: #f6faef;
}

.stat-card,
.filtro-card,
.motocicletas-table,
.moto-card {
  border-color: #dce8da;
  border-radius: 16px;
  background: #fff;
}

.stat-card {
  height: 100%;
}

.motocicletas-table {
  overflow: hidden;
}

.moto-card {
  position: relative;
  overflow: hidden;
}

.moto-card-strip {
  height: 5px;
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
  width: 520px;
  max-width: calc(100vw - 24px);
  border-radius: 18px;
  overflow: hidden;
}

.min-width-zero {
  min-width: 0;
}

@media (max-width: 599px) {
  .motocicletas-page {
    padding-left: 10px;
    padding-right: 10px;
  }
}
</style>
