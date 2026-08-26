<template>
  <q-page class="q-pa-lg bg-grey-2">
    <!-- Encabezado del Módulo -->
    <div class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h4 text-weight-bold text-grey-9">Gestión de Sindicatos</div>
        <div class="text-subtitle2 text-grey-6">Administración de asociaciones de transporte en MOTRIX</div>
      </div>
      <q-btn 
        color="primary" 
        icon="add" 
        label="Nuevo Sindicato" 
        class="q-px-md text-bold"
        @click="openDialogForm()"
      />
    </div>

    <!-- Tabla de Datos -->
    <q-card class="shadow-2 border-radius-md">
      <q-card-section class="q-pa-none">
        <q-table
          :rows="sindicatos"
          :columns="columns"
          row-key="id"
          :filter="filter"
          :loading="loading"
          flat
          binary-state-sort
          no-data-label="No se encontraron registros"
        >
          <!-- Buscador Superior -->
          <template v-slot:top-right>
            <q-input 
              outlined 
              dense 
              debounce="300" 
              v-model="filter" 
              placeholder="Buscar sindicato..."
              class="bg-white"
              style="width: 250px"
            >
              <template v-slot:append>
                <q-icon name="search" />
              </template>
            </q-input>
          </template>

          <!-- Acciones (Editar / Eliminar) -->
          <template v-slot:body-cell-actions="props">
            <q-td :props="props" class="q-gutter-xs text-center">
              <q-btn flat round color="blue" icon="edit" size="sm" @click="openDialogForm(props.row)">
                <q-tooltip>Editar Registro</q-tooltip>
              </q-btn>
              <q-btn flat round color="red" icon="delete" size="sm" @click="confirmDelete(props.row)">
                <q-tooltip>Eliminar Registro</q-tooltip>
              </q-btn>
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>

    <!-- Modal Formulario -->
    <q-dialog v-model="dialogOpen" persistent>
      <q-card style="width: 450px; max-width: 80vw;" class="border-radius-md">
        <q-card-section class="bg-primary text-white row items-center">
          <div class="text-h6 text-bold">{{ isEditing ? 'Editar Sindicato' : 'Registrar Sindicato' }}</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-form @submit.prevent>
          <q-card-section class="q-gutter-md q-pt-lg">
            <q-input outlined v-model="form.nombre" label="Nombre del Sindicato *" />
            <q-input outlined v-model="form.direccion" label="Dirección" />
            
            <!-- Entrada de Fecha de Creación (Requerida por Laravel) -->
            <q-input 
              outlined 
              v-model="form.fecha_creacion" 
              label="Fecha de Creación *" 
              mask="date" 
              hint="Formato: AAAA/MM/DD"
            >
              <template v-slot:append>
                <q-icon name="event" class="cursor-pointer">
                  <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                    <q-date v-model="form.fecha_creacion">
                      <div class="row items-center justify-end">
                        <q-btn v-close-popup label="Cerrar" color="primary" flat />
                      </div>
                    </q-date>
                  </q-popup-proxy>
                </q-icon>
              </template>
            </q-input>

          </q-card-section>

          <q-card-actions align="right" class="q-pa-md bg-grey-1">
            <q-btn flat label="Cancelar" color="grey-7" v-close-popup />
            <q-btn 
              @click="saveSindicato" 
              :label="isEditing ? 'Guardar Cambios' : 'Registrar'" 
              color="primary" 
              class="text-bold" 
            />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import sindicatoService from 'src/services/sindicatoService'

const $q = useQuasar()

const sindicatos = ref([])
const filter = ref('')
const loading = ref(false)
const dialogOpen = ref(false)
const isEditing = ref(false)

// Agregamos fecha_creacion al estado inicial
const formDefault = { id: null, nombre: '', direccion: '', fecha_creacion: '' }
const form = ref({ ...formDefault })

// Columnas ajustadas
const columns = [
  { name: 'id', label: 'ID', align: 'left', field: row => row.id, sortable: true },
  { name: 'nombre', label: 'Sindicato', align: 'left', field: row => row.nombre, sortable: true },
  { name: 'direccion', label: 'Dirección', align: 'left', field: row => row.direccion },
  { name: 'fecha_creacion', label: 'Fecha Creación', align: 'left', field: row => row.fecha_creacion, sortable: true },
  { name: 'actions', label: 'Acciones', align: 'center', field: 'actions' }
 ]

const fetchSindicatos = async () => {
  loading.value = true
  try {
    const response = await sindicatoService.getAll()
    sindicatos.value = response.data
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

const saveSindicato = async () => {
  if (!form.value.nombre || !form.value.fecha_creacion) {
    alert('El nombre y la fecha de creación son obligatorios (*)')
    return
  }

  try {
    if (isEditing.value) {
      await sindicatoService.update(form.value.id, form.value)
    } else {
      await sindicatoService.create(form.value)
    }
    dialogOpen.value = false
    fetchSindicatos()
  } catch (error) {
    console.error(error)
    if (error.response && error.response.status === 422) {
      alert('Laravel rechazó los datos (Error 422):\n' + JSON.stringify(error.response.data.errors || error.response.data))
    } else {
      alert('Error en el servidor al intentar guardar.')
    }
  }
}

const confirmDelete = (row) => {
  $q.dialog({
    title: 'Confirmar Eliminación',
    message: `¿Estás seguro de que deseas eliminar el sindicato ${row.nombre}?`,
    cancel: true,
    persistent: true
  }).onOk(async () => {
    try {
      await sindicatoService.delete(row.id)
      fetchSindicatos()
    } catch (error) {
      console.error(error)
    }
  })
}

onMounted(() => {
  fetchSindicatos()
})
</script>

<style scoped>
.border-radius-md {
  border-radius: 12px;
}
</style>