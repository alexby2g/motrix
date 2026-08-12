<template>
  <q-page class="q-pa-lg bg-grey-2">
    <div class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h4 text-weight-bold text-grey-9">Gestión de Motocicletas</div>
        <div class="text-subtitle2 text-grey-6">Administración del parque automotor de la aplicación</div>
      </div>
      <q-btn 
        color="positive" 
        icon="add" 
        label="Nueva Motocicleta" 
        class="q-px-md text-bold"
        @click="openDialogForm()"
      />
    </div>

    <q-card class="shadow-2 border-radius-md">
      <q-card-section class="q-pa-none">
        <q-table
          :rows="motocicletas"
          :columns="columns"
          row-key="id"
          :filter="filter"
          :loading="loading"
          flat
          binary-state-sort
          no-data-label="No se encontraron registros"
        >
          <template v-slot:top-right>
            <q-input outlined dense debounce="300" v-model="filter" placeholder="Buscar..." class="bg-white" style="width: 250px">
              <template v-slot:append><q-icon name="search" /></template>
            </q-input>
          </template>

          <template v-slot:body-cell-actions="props">
            <q-td :props="props" class="q-gutter-xs text-center">
              <q-btn flat round color="blue" icon="edit" size="sm" @click="openDialogForm(props.row)" />
              <q-btn flat round color="red" icon="delete" size="sm" @click="confirmDelete(props.row)" />
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>

    <q-dialog v-model="dialogOpen" persistent>
      <q-card style="width: 400px; max-width: 80vw;" class="border-radius-md">
        <q-card-section class="bg-positive text-white row items-center">
          <div class="text-h6 text-bold">{{ isEditing ? 'Editar Motocicleta' : 'Registrar Motocicleta' }}</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-form @submit.prevent>
          <q-card-section class="q-gutter-md q-pt-lg">
            
            <q-input outlined v-model="form.placa" label="Placa *" placeholder="Ej: 4523-XYZ" />
            <q-input outlined v-model="form.modelo" label="Modelo *" placeholder="Ej: Kingo 2024" />
            <q-input outlined v-model="form.color" label="Color *" placeholder="Ej: Rojo" />

            <q-select
              outlined
              v-model="form.id_mototaxista"
              :options="mototaxistasOptions"
              label="Asignar Mototaxista *"
              emit-value
              map-options
              option-value="id"
              option-label="nombre_completo"
            />

          </q-card-section>

          <q-card-actions align="right" class="q-pa-md bg-grey-1">
            <q-btn flat label="Cancelar" color="grey-7" v-close-popup />
            <q-btn @click="saveMotocicleta" :label="isEditing ? 'Guardar' : 'Registrar'" color="positive" class="text-bold" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import motocicletaService from 'src/services/motocicletaService'
import mototaxistaService from 'src/services/mototaxistaService'

const $q = useQuasar()

const motocicletas = ref([])
const mototaxistasOptions = ref([])

const filter = ref('')
const loading = ref(false)
const dialogOpen = ref(false)
const isEditing = ref(false)

const formDefault = { id: null, placa: '', modelo: '', color: '', id_mototaxista: null }
const form = ref({ ...formDefault })

const columns = [
  { name: 'id', label: 'ID', align: 'left', field: row => row.id, sortable: true },
  { name: 'placa', label: 'Placa', align: 'left', field: 'placa', sortable: true },
  { name: 'modelo', label: 'Modelo', align: 'left', field: 'modelo', sortable: true },
  { name: 'color', label: 'Color', align: 'left', field: 'color', sortable: true },
  { 
    name: 'mototaxista', 
    label: 'Conductor Asignado', 
    align: 'left', 
    field: row => row.mototaxista && row.mototaxista.persona ? row.mototaxista.persona.nombre : 'Sin Asignar', 
    sortable: true 
  },
  { name: 'actions', label: 'Acciones', align: 'center', field: 'actions' }
]

const cargarDatos = async () => {
  loading.value = true
  try {
    const resMotos = await motocicletaService.getAll()
    motocicletas.value = resMotos.data

    // Cargamos los mototaxistas para el selector
    const resTaxistas = await mototaxistaService.getAll()
    // Mapeamos para mostrar el nombre de la persona en el select label
    mototaxistasOptions.value = resTaxistas.data.map(m => ({
      id: m.id,
      nombre_completo: m.persona ? m.persona.nombre : `Mototaxista ID: ${m.id}`
    }))
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}

const openDialogForm = (row = null) => {
  if (row) {
    isEditing.value = true
    form.value = { ...row }
  } else {
    isEditing.value = false
    form.value = { ...formDefault }
  }
  dialogOpen.value = true
}

const saveMotocicleta = async () => {
  if (!form.value.placa || !form.value.modelo || !form.value.color || !form.value.id_mototaxista) {
    $q.notify({ type: 'negative', message: 'Por favor, llena todos los campos obligatorios (*)' })
    return
  }

  try {
    if (isEditing.value) {
      await motocicletaService.update(form.value.id, form.value)
    } else {
      await motocicletaService.create(form.value)
    }
    dialogOpen.value = false
    cargarDatos()
  } catch (error) {
    console.error(error)
    if (error.response && error.response.status === 422) {
      alert('Error de validación de Laravel:\n' + JSON.stringify(error.response.data.errors || error.response.data))
    }
  }
}

const confirmDelete = (row) => {
  $q.dialog({
    title: 'Eliminar Motocicleta',
    message: `¿Estás seguro de eliminar la motocicleta con placa ${row.placa}?`,
    cancel: true
  }).onOk(async () => {
    try {
      await motocicletaService.delete(row.id)
      cargarDatos()
    } catch (error) {
      console.error(error)
    }
  })
}

onMounted(() => {
  cargarDatos()
})
</script>

<style scoped>
.border-radius-md { border-radius: 12px; }
</style>