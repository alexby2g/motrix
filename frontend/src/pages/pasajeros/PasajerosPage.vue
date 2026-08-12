<template>
  <q-page class="q-pa-md bg-grey-1">
    
    <div class="row items-center justify-between q-mb-md bg-primary text-white q-pa-md border-radius-md shadow-2">
      <div>
        <div class="text-h6 text-bold">Gestión de Pasajeros</div>
        <div class="text-caption text-grey-3">Administración de clientes del sistema MOTRIX</div>
      </div>
      <q-btn 
        color="white" 
        text-color="primary" 
        label="NUEVO PASAJERO" 
        icon="add" 
        class="text-bold" 
        @click="abrirModalCrear" 
      />
    </div>

    <q-card class="shadow-2 border-radius-md">
      <q-card-section class="q-pa-none">
        <q-table
          :rows="pasajeros"
          :columns="columnas"
          row-key="id"
          :loading="loading"
          no-data-label="No hay pasajeros registrados"
          rows-per-page-label="Registros por página:"
        >
          <template v-slot:body-cell-cuenta="props">
            <q-td :props="props" class="text-center">
              <q-badge
                :color="
                  props.row.usuario_pasajero
                    ? 'positive'
                    : 'grey-6'
                "
                class="q-pa-xs text-weight-bold"
              >
                {{
                  props.row.usuario_pasajero
                    ? 'Cuenta pasajero'
                    : 'Sin cuenta'
                }}
              </q-badge>
            </q-td>
          </template>

          <template v-slot:body-cell-acciones="props">
            <q-td :props="props" class="q-gutter-xs text-center">
              
              <q-btn 
                flat 
                round 
                color="blue-13" 
                icon="visibility" 
                size="sm" 
                @click="abrirExpediente(props.row)" 
              >
                <q-tooltip>Ver Expediente Completo</q-tooltip>
              </q-btn>

              <q-btn
                v-if="esAdminGeneral"
                flat
                round
                color="purple-7"
                icon="support_agent"
                size="sm"
                @click="abrirSoportePasajero(props.row)"
              >
                <q-tooltip>Modo soporte del pasajero</q-tooltip>
              </q-btn>


              <q-btn
                v-if="
                  esAdminGeneral
                  && !props.row.usuario_pasajero
                "
                flat
                round
                color="deep-purple-7"
                icon="person_add"
                size="sm"
                @click="abrirCuentaPasajero(props.row)"
              >
                <q-tooltip>Crear cuenta de pasajero</q-tooltip>
              </q-btn>

              <q-btn
                v-else-if="
                  esAdminGeneral
                  && props.row.usuario_pasajero
                "
                flat
                round
                color="positive"
                icon="verified_user"
                size="sm"
                disable
              >
                <q-tooltip>
                  Cuenta de pasajero ya creada
                </q-tooltip>
              </q-btn>

              <q-btn 
                flat 
                round 
                color="blue" 
                icon="edit" 
                size="sm" 
                @click="abrirModalEditar(props.row)" 
              >
                <q-tooltip>Editar Pasajero</q-tooltip>
              </q-btn>

              <q-btn 
                flat 
                round 
                color="red" 
                icon="delete" 
                size="sm" 
                @click="confirmarEliminar(props.row.id)" 
              >
                <q-tooltip>Eliminar Pasajero</q-tooltip>
              </q-btn>
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>

    <q-dialog v-model="modalFormulario" persistent>
      <q-card style="width: 500px; max-width: 90vw;" class="border-radius-md">
        <q-card-section class="row items-center bg-primary text-white q-pa-md">
          <q-icon :name="esEdicion ? 'edit' : 'person_add'" size="sm" class="q-mr-sm" />
          <div class="text-h6 text-bold">{{ esEdicion ? 'Editar Pasajero' : 'Registrar Pasajero' }}</div>
          <q-space />
          <q-btn flat round icon="close" v-close-popup />
        </q-card-section>

        <q-form @submit.prevent="guardarPasajero" ref="formRef">
          <q-card-section class="q-pa-md q-gutter-y-sm">
            
            <div v-if="!esEdicion">
              <q-select
                outlined
                v-model="personaSeleccionada"
                :options="personasLista"
                option-label="nombre"
                label="Seleccionar Persona"
                emit-value
                map-options
                @update:model-value="alSeleccionarPersona"
                :rules="[val => !!val || 'Seleccione una persona de la lista']"
              >
                <template v-slot:prepend>
                  <q-icon name="person" />
                </template>
              </q-select>
            </div>

            <div v-else class="q-pa-sm bg-grey-2 border-radius-sm q-mb-md">
              <div class="text-caption text-grey-7">Pasajero:</div>
              <div class="text-subtitle1 text-bold text-primary">{{ formulario.nombre_completo }}</div>
            </div>

            <q-input 
              outlined 
              v-model="formulario.ci" 
              label="Cédula de Identidad (CI)" 
              readonly
              disable
              bg-color="grey-2"
            >
              <template v-slot:prepend>
                <q-icon name="fingerprint" />
              </template>
            </q-input>

            <q-input 
              outlined 
              v-model="formulario.telefono" 
              label="Teléfono" 
              readonly
              disable
              bg-color="grey-2"
            >
              <template v-slot:prepend>
                <q-icon name="phone" />
              </template>
            </q-input>

            <q-input 
              outlined 
              v-model="formulario.email" 
              label="Correo Electrónico (Usuario)" 
              type="email"
              placeholder="ejemplo@correo.com"
              :rules="[
                val => !!val || 'Ingrese el correo electrónico',
                val => /.+@.+\..+/.test(val) || 'Ingrese un correo electrónico válido'
              ]"
            >
              <template v-slot:prepend>
                <q-icon name="mail" />
              </template>
            </q-input>

          </q-card-section>

          <q-card-actions align="right" class="bg-grey-2 q-pa-md">
            <q-btn flat label="CANCELAR" color="grey-7" v-close-popup />
            <q-btn type="submit" label="GUARDAR" color="primary" class="text-bold" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <q-dialog
      v-model="modalCuentaPasajero"
      persistent
    >
      <q-card
        style="width: 520px; max-width: 92vw;"
        class="border-radius-md"
      >
        <q-card-section
          class="row items-center bg-deep-purple-7 text-white q-pa-md"
        >
          <q-icon
            name="person_add"
            size="sm"
            class="q-mr-sm"
          />

          <div>
            <div class="text-h6 text-bold">
              Crear cuenta de pasajero
            </div>

            <div class="text-caption">
              {{ cuentaPasajeroNombre }}
            </div>
          </div>

          <q-space />

          <q-btn
            flat
            round
            icon="close"
            :disable="creandoCuentaPasajero"
            @click="cerrarCuentaPasajero"
          />
        </q-card-section>

        <q-card-section class="q-pa-md q-gutter-y-md">
          <q-banner
            rounded
            class="bg-blue-1 text-blue-10"
          >
            <template #avatar>
              <q-icon
                name="info"
                color="blue-8"
              />
            </template>

            Esta cuenta permitirá al pasajero iniciar sesión en MOTRIX
            desde la aplicación móvil o la plataforma web.
          </q-banner>

          <q-input
            v-model.trim="cuentaPasajero.email"
            outlined
            type="email"
            label="Correo electrónico *"
            :rules="[
              val => !!val || 'Ingrese el correo electrónico',
              val => /.+@.+\..+/.test(val) || 'Ingrese un correo válido'
            ]"
          >
            <template #prepend>
              <q-icon
                name="email"
                color="deep-purple-7"
              />
            </template>
          </q-input>

          <q-input
            v-model.trim="cuentaPasajero.nickname"
            outlined
            label="Nickname (opcional)"
            hint="También podrá iniciar sesión con este nickname."
          >
            <template #prepend>
              <q-icon
                name="alternate_email"
                color="deep-purple-7"
              />
            </template>
          </q-input>

          <q-input
            v-model="cuentaPasajero.password"
            outlined
            :type="
              mostrarPasswordCuenta
                ? 'text'
                : 'password'
            "
            label="Contraseña *"
            :rules="[
              val => !!val || 'Ingrese una contraseña',
              val => String(val || '').length >= 6 || 'Mínimo 6 caracteres'
            ]"
          >
            <template #prepend>
              <q-icon
                name="lock"
                color="deep-purple-7"
              />
            </template>

            <template #append>
              <q-btn
                flat
                round
                dense
                :icon="
                  mostrarPasswordCuenta
                    ? 'visibility_off'
                    : 'visibility'
                "
                @click="
                  mostrarPasswordCuenta =
                    !mostrarPasswordCuenta
                "
              />
            </template>
          </q-input>
        </q-card-section>

        <q-card-actions
          align="right"
          class="bg-grey-2 q-pa-md"
        >
          <q-btn
            flat
            label="CANCELAR"
            color="grey-7"
            :disable="creandoCuentaPasajero"
            @click="cerrarCuentaPasajero"
          />

          <q-btn
            color="deep-purple-7"
            icon="person_add"
            label="CREAR CUENTA"
            class="text-bold"
            :loading="creandoCuentaPasajero"
            @click="crearCuentaPasajero"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>


    <q-dialog v-model="modalExpediente">
      <q-card style="width: 850px; max-width: 95vw;" class="border-radius-md">
        
        <q-card-section class="bg-blue text-white row items-center q-pa-md">
          <q-icon name="assignment" size="sm" class="q-mr-sm" />
          <div class="text-h6 text-bold">Expediente del Pasajero</div>
          <q-space />
          <q-btn flat round icon="close" v-close-popup />
        </q-card-section>

        <q-card-section class="q-pa-md q-gutter-y-md">
          
          <div class="text-subtitle2 text-bold text-grey-8 text-uppercase">Datos Personales</div>
          <q-separator class="q-mb-md" />
          
          <div class="row q-col-gutter-md">
            <div class="col-12 col-md-6">
              <q-input outlined v-model="expediente.nombre" label="Nombre Completo" readonly disable dense bg-color="grey-1" />
            </div>
            <div class="col-12 col-md-6">
              <q-input outlined v-model="expediente.email" label="Email / Usuario" readonly disable dense bg-color="grey-1" />
            </div>
            <div class="col-12 col-md-6">
              <q-input outlined v-model="expediente.telefono" label="Teléfono / Celular" readonly disable dense bg-color="grey-1" />
            </div>
            <div class="col-12 col-md-6">
              <q-input outlined v-model="expediente.ci" label="Cédula de Identidad (CI)" readonly disable dense bg-color="grey-1" />
            </div>
          </div>

          <div class="text-subtitle2 text-bold text-grey-8 text-uppercase q-mt-lg">Historial de Viajes</div>
          <q-separator class="q-mb-md" />

          <q-table
            :rows="historialViajes"
            :columns="columnasHistorial"
            row-key="id"
            :loading="loadingHistorial"
            no-data-label="Este pasajero aún no cuenta con solicitudes de viaje registradas"
            rows-per-page-label="Records per page:"
            dense
            flat
            bordered
          >
            <template v-slot:body-cell-precio="props">
              <q-td :props="props" class="text-right text-bold text-green">
                Bs. {{ props.value }}
              </q-td>
            </template>

            <template v-slot:body-cell-estado="props">
              <q-td :props="props" class="text-center">
                <q-badge 
                  :color="obtenerColorEstado(props.value)"
                  class="q-pa-xs text-weight-bold text-uppercase"
                >
                  {{ props.value }}
                </q-badge>
              </q-td>
            </template>
          </q-table>

        </q-card-section>

        <q-card-actions align="right" class="bg-grey-2 q-pa-md">
          <q-btn flat label="CERRAR" color="primary" class="text-bold" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import { api } from 'src/boot/axios.js'

const $q = useQuasar()
const router = useRouter()

const esAdminGeneral = computed(() => {
  try {
    const usuario = JSON.parse(
      localStorage.getItem('motrix_user') || 'null'
    )

    return String(
      usuario?.role || ''
    )
      .trim()
      .toLowerCase() === 'admin_general'
  } catch {
    return false
  }
})

const pasajeros = ref([])
const personasLista = ref([]) 
const loading = ref(false)
const modalFormulario = ref(false)
const esEdicion = ref(false)
const formRef = ref(null) 
const personaSeleccionada = ref(null)


const modalCuentaPasajero = ref(false)
const creandoCuentaPasajero = ref(false)
const pasajeroCuentaSeleccionado = ref(null)
const mostrarPasswordCuenta = ref(false)

const cuentaPasajero = ref({
  email: '',
  nickname: '',
  password: ''
})

const cuentaPasajeroNombre = computed(() => {
  return (
    pasajeroCuentaSeleccionado.value?.persona?.nombre
    || 'Pasajero MOTRIX'
  )
})

// Variables reactivas para el Expediente Único (Datos + Historial)
const modalExpediente = ref(false)
const loadingHistorial = ref(false)
const historialViajes = ref([])

const expediente = ref({
  nombre: '',
  email: '',
  telefono: '',
  ci: ''
})


// Estructura de datos del formulario para creación/edición
const formulario = ref({
  id: null,
  id_persona: null,
  nombre_completo: '',
  ci: '',
  telefono: '',
  email: ''
})

// Columnas de la tabla principal
const columnas = [
  { name: 'id', align: 'left', label: 'ID', field: 'id' },
  { name: 'nombre', align: 'left', label: 'Nombre Completo', field: row => row.persona?.nombre || 'Sin nombre' },
  { name: 'ci', align: 'left', label: 'Cédula de Identidad', field: row => row.persona?.ci || 'Sin CI' },
  { name: 'telefono', align: 'left', label: 'Teléfono', field: row => row.persona?.telefono || 'Sin teléfono' },
  { name: 'email', align: 'left', label: 'Correo Electrónico', field: 'email' },
  { name: 'cuenta', align: 'center', label: 'Cuenta', field: row => row.usuario_pasajero },
  { name: 'acciones', align: 'center', label: 'Acciones' }
]

// Columnas para la tabla interna del Historial de Viajes dentro del Expediente
const columnasHistorial = [
  { name: 'id', align: 'left', label: 'ID Viaje', field: 'id' },
  { name: 'origen', align: 'left', label: 'Origen', field: 'origen' },
  { name: 'destino', align: 'left', label: 'Destino', field: 'destino' },
  { name: 'fecha', align: 'center', label: 'Fecha', field: 'fecha' },
  { name: 'precio', align: 'right', label: 'Tarifa', field: 'precio' },
  { name: 'estado', align: 'center', label: 'Estado', field: 'estado' }
]

const obtenerPasajeros = async () => {
  loading.value = true
  try {
    const res = await api.get('/pasajeros')
    pasajeros.value = res.data
  } catch (error) {
    console.error(error)
    $q.notify({ type: 'negative', message: 'Error al cargar los pasajeros' })
  } finally {
    loading.value = false
  }
}

const obtenerPersonasExistentes = async () => {
  try {
    const res = await api.get('/personas')
    personasLista.value = res.data
  } catch (error) {
    console.error(error)
  }
}

const abrirModalCrear = () => {
  esEdicion.value = false
  personaSeleccionada.value = null
  formulario.value = { id: null, id_persona: null, nombre_completo: '', ci: '', telefono: '', email: '' }
  obtenerPersonasExistentes()
  modalFormulario.value = true
}

const alSeleccionarPersona = (persona) => {
  if (persona) {
    formulario.value.id_persona = persona.id
    formulario.value.nombre_completo = persona.nombre
    formulario.value.ci = persona.ci
    formulario.value.telefono = persona.telefono
  } else {
    formulario.value.id_persona = null
    formulario.value.nombre_completo = ''
    formulario.value.ci = ''
    formulario.value.telefono = ''
  }
}

const abrirModalEditar = (pasajero) => {
  esEdicion.value = true
  formulario.value = {
    id: pasajero.id,
    id_persona: pasajero.id_persona,
    nombre_completo: pasajero.persona?.nombre || '',
    ci: pasajero.persona?.ci || '',
    telefono: pasajero.persona?.telefono || '',
    email: pasajero.email
  }
  modalFormulario.value = true
}

const guardarPasajero = async () => {
  try {
    if (esEdicion.value) {
      await api.put(`/pasajeros/${formulario.value.id}`, {
        email: formulario.value.email
      })
      $q.notify({ type: 'positive', message: 'Pasajero actualizado correctamente' })
    } else {
      await api.post('/pasajeros', {
        id_persona: formulario.value.id_persona,
        email: formulario.value.email
      })
      $q.notify({ type: 'positive', message: 'Pasajero registrado exitosamente' })
    }
    modalFormulario.value = false
    obtenerPasajeros()
  } catch (error) {
    console.error(error)
    const errorMsg = error.response?.data?.message || 'Error al procesar la solicitud'
    $q.notify({ type: 'negative', message: errorMsg })
  }
}

const confirmarEliminar = (id) => {
  $q.dialog({
    title: 'Confirmar Eliminación',
    message: '¿Está seguro de que desea eliminar a este pasajero?',
    cancel: true,
    persistent: true
  }).onOk(async () => {
    try {
      await api.delete(`/pasajeros/${id}`)
      $q.notify({ type: 'positive', message: 'Pasajero eliminado' })
      obtenerPasajeros()
    } catch (error) {
      console.error(error)
      $q.notify({ type: 'negative', message: 'No se pudo eliminar al pasajero' })
    }
  })
}

const mensajeError = (error) => {
  const data = error?.response?.data

  if (data?.errors) {
    const primerMensaje = Object.values(
      data.errors
    )
      .flat()
      .find(Boolean)

    if (primerMensaje) {
      return primerMensaje
    }
  }

  return (
    data?.mensaje
    || data?.message
    || 'No se pudo completar la operación.'
  )
}

const abrirCuentaPasajero = (pasajero) => {
  if (
    !esAdminGeneral.value
    || !pasajero?.id
    || pasajero?.usuario_pasajero
  ) {
    return
  }

  pasajeroCuentaSeleccionado.value =
    pasajero

  cuentaPasajero.value = {
    email:
      pasajero.email || '',
    nickname: '',
    password: ''
  }

  mostrarPasswordCuenta.value = false
  modalCuentaPasajero.value = true
}

const cerrarCuentaPasajero = () => {
  if (creandoCuentaPasajero.value) {
    return
  }

  modalCuentaPasajero.value = false
  pasajeroCuentaSeleccionado.value = null

  cuentaPasajero.value = {
    email: '',
    nickname: '',
    password: ''
  }

  mostrarPasswordCuenta.value = false
}

const crearCuentaPasajero = async () => {
  const pasajeroId =
    pasajeroCuentaSeleccionado.value?.id

  if (!pasajeroId) {
    return
  }

  if (!cuentaPasajero.value.email) {
    $q.notify({
      type: 'negative',
      message: 'Ingrese el correo electrónico.'
    })

    return
  }

  if (
    String(
      cuentaPasajero.value.password || ''
    ).length < 6
  ) {
    $q.notify({
      type: 'negative',
      message: 'La contraseña debe tener al menos 6 caracteres.'
    })

    return
  }

  creandoCuentaPasajero.value = true

  try {
    await api.post(
      `/pasajeros/${pasajeroId}/cuenta-pasajero`,
      {
        email:
          cuentaPasajero.value.email,
        nickname:
          cuentaPasajero.value.nickname
          || null,
        password:
          cuentaPasajero.value.password
      }
    )

    $q.notify({
      type: 'positive',
      position: 'top',
      icon: 'verified_user',
      message:
        'Cuenta de pasajero creada correctamente.'
    })

    creandoCuentaPasajero.value = false
    cerrarCuentaPasajero()
    await obtenerPasajeros()
  } catch (error) {
    console.error(
      'Error creando cuenta de pasajero:',
      error
    )

    $q.notify({
      type: 'negative',
      position: 'top',
      multiLine: true,
      message: mensajeError(error)
    })
  } finally {
    creandoCuentaPasajero.value = false
  }
}

const abrirSoportePasajero = (pasajero) => {
  if (!esAdminGeneral.value || !pasajero?.id) {
    return
  }

  router.push(
    `/soporte/pasajero/${pasajero.id}`
  )
}

// 📂 ABRE EL EXPEDIENTE ÚNICO (DATOS PERSONALES + CONSULTA DEL HISTORIAL EN LA BASE DE DATOS)
const abrirExpediente = async (pasajero) => {
  // 1. Cargamos de inmediato los datos personales en los inputs superiores
  expediente.value = {
    nombre: pasajero.persona?.nombre || 'Sin nombre',
    email: pasajero.email || '',
    telefono: pasajero.persona?.telefono || 'Sin teléfono',
    ci: pasajero.persona?.ci || 'Sin CI'
  }

  // Abrimos el modal
  modalExpediente.value = true
  loadingHistorial.value = true
  historialViajes.value = []

  try {
    // 2. Traemos su historial de viajes real desde Laravel
    const res = await api.get('/solicitudes', { params: { id_pasajero: pasajero.id } })
    historialViajes.value = res.data
  } catch (error) {
    console.error("Error al obtener historial de solicitudes:", error)
    $q.notify({
      type: 'warning',
      message: 'No se pudo cargar el historial de viajes'
    })
  } finally {
    loadingHistorial.value = false
  }
}

// Devuelve el color apropiado para cada estado del viaje
const obtenerColorEstado = (estado) => {
  switch (estado?.toLowerCase()) {
    case 'finalizado':
    case 'completado':
      return 'green'
    case 'aceptado':
    case 'en curso':
      return 'blue'
    case 'pendiente':
      return 'amber-9'
    case 'cancelado':
      return 'red'
    default:
      return 'grey-7'
  }
}

onMounted(() => {
  obtenerPasajeros()
})
</script>

<style scoped>
.border-radius-md { border-radius: 12px; }
.border-radius-sm { border-radius: 6px; }
</style>