<template>
  <q-page class="q-pa-md q-pa-lg-md personas-page">
    <div class="row items-center q-col-gutter-md q-mb-md">
      <div class="col">
        <div class="row items-center no-wrap">
          <q-avatar
            color="green-1"
            text-color="green-9"
            icon="badge"
            size="48px"
            class="q-mr-md"
          />

          <div>
            <div class="text-h5 text-weight-bold text-green-9">
              Personas
            </div>
            <div class="text-caption text-grey-7">
              Registro base de pasajeros, mototaxistas y usuarios.
            </div>
          </div>
        </div>
      </div>

      <div class="col-auto">
        <q-btn
          color="green-8"
          icon="person_add"
          label="Nueva persona"
          unelevated
          :disable="loading"
          @click="abrirFormulario()"
        />
      </div>
    </div>

    <div class="row q-col-gutter-md q-mb-md">
      <div class="col-12 col-sm-4">
        <q-card
          flat
          bordered
          class="stat-card"
        >
          <q-card-section class="row items-center no-wrap">
            <q-avatar
              color="green-1"
              text-color="green-9"
              icon="groups"
              size="46px"
              class="q-mr-md"
            />
            <div>
              <div class="text-caption text-grey-7">
                Personas registradas
              </div>
              <div class="text-h5 text-weight-bold text-green-9">
                {{ estadisticas.total }}
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-sm-4">
        <q-card
          flat
          bordered
          class="stat-card"
        >
          <q-card-section class="row items-center no-wrap">
            <q-avatar
              color="blue-1"
              text-color="blue-8"
              icon="photo_camera"
              size="46px"
              class="q-mr-md"
            />
            <div>
              <div class="text-caption text-grey-7">
                Con fotografía
              </div>
              <div class="text-h5 text-weight-bold text-blue-8">
                {{ totalConFoto }}
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-sm-4">
        <q-card
          flat
          bordered
          class="stat-card"
        >
          <q-card-section class="row items-center no-wrap">
            <q-avatar
              color="orange-1"
              text-color="orange-9"
              icon="phone"
              size="46px"
              class="q-mr-md"
            />
            <div>
              <div class="text-caption text-grey-7">
                Con teléfono
              </div>
              <div class="text-h5 text-weight-bold text-orange-9">
                {{ totalConTelefono }}
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <q-card
      flat
      bordered
      class="q-mb-md filtro-card"
    >
      <q-card-section class="row items-center q-col-gutter-md">
        <div class="col-12 col-md">
          <q-input
            v-model="filter"
            outlined
            dense
            debounce="250"
            placeholder="Buscar por nombre, apellido, CI, teléfono o dirección"
          >
            <template #prepend>
              <q-icon
                name="search"
                color="green-8"
              />
            </template>

            <template
              v-if="filter"
              #append
            >
              <q-icon
                name="close"
                class="cursor-pointer"
                @click="filter = ''"
              />
            </template>
          </q-input>
        </div>

        <div class="col-auto">
          <q-chip
            color="green-1"
            text-color="green-9"
            icon="groups"
          >
            {{ personas.length }} en esta página · {{ pagination.rowsNumber }} resultados
          </q-chip>
        </div>
      </q-card-section>
    </q-card>

    <q-card
      flat
      bordered
      class="lista-card"
    >
      <q-linear-progress
        v-if="loading"
        indeterminate
        color="green-8"
      />

      <q-list
        v-if="personasFiltradas.length"
        separator
      >
        <q-item
          v-for="persona in personasFiltradas"
          :key="persona.id"
          clickable
          v-ripple
          class="persona-item"
          @click="abrirDetallePersona(persona)"
        >
          <q-item-section avatar>
            <q-avatar
              size="52px"
              color="green-1"
              text-color="green-9"
            >
              <img
                v-if="fotoPersona(persona)"
                :src="fotoPersona(persona)"
                alt="Foto"
                @error="ocultarImagen($event)"
              >
              <span v-else>
                {{ iniciales(persona) }}
              </span>
            </q-avatar>
          </q-item-section>

          <q-item-section>
            <q-item-label class="text-weight-bold text-grey-9">
              {{ nombreCompleto(persona) }}
            </q-item-label>

            <q-item-label caption>
              <q-icon
                name="fingerprint"
                size="14px"
                class="q-mr-xs"
              />
              CI: {{ persona.ci || 'No registrado' }}
            </q-item-label>

            <q-item-label
              v-if="persona.telefono"
              caption
            >
              <q-icon
                name="phone"
                size="14px"
                class="q-mr-xs"
              />
              {{ persona.telefono }}
            </q-item-label>
          </q-item-section>

          <q-item-section
            side
            class="gt-xs"
          >
            <div class="text-caption text-grey-7 text-right">
              {{ persona.direccion || 'Sin dirección' }}
            </div>
          </q-item-section>

          <q-item-section side>
            <q-btn
              flat
              round
              icon="more_vert"
              color="grey-7"
              @click.stop
            >
              <q-menu>
                <q-list style="min-width: 190px">
                  <q-item
                    clickable
                    v-close-popup
                    @click="abrirFormulario(persona)"
                  >
                    <q-item-section avatar>
                      <q-icon
                        name="edit"
                        color="green-8"
                      />
                    </q-item-section>
                    <q-item-section>
                      Editar
                    </q-item-section>
                  </q-item>

                  <q-item
                    clickable
                    v-close-popup
                    @click="abrirFotografias(persona)"
                  >
                    <q-item-section avatar>
                      <q-icon
                        name="photo_camera"
                        color="blue-8"
                      />
                    </q-item-section>
                    <q-item-section>
                      Fotografías
                    </q-item-section>
                  </q-item>

                  <q-separator />

                  <q-item
                    clickable
                    v-close-popup
                    @click="confirmarEliminar(persona)"
                  >
                    <q-item-section avatar>
                      <q-icon
                        name="delete"
                        color="negative"
                      />
                    </q-item-section>
                    <q-item-section class="text-negative">
                      Eliminar
                    </q-item-section>
                  </q-item>
                </q-list>
              </q-menu>
            </q-btn>
          </q-item-section>
        </q-item>
      </q-list>

      <div
        v-else-if="!loading"
        class="column items-center q-pa-xl text-grey-6"
      >
        <q-icon
          name="person_search"
          size="54px"
          class="q-mb-sm"
        />
        <div class="text-subtitle1 text-weight-medium">
          No se encontraron personas
        </div>
      </div>

      <q-separator
        v-if="pagination.lastPage > 1"
      />

      <div
        v-if="pagination.lastPage > 1"
        class="row justify-center q-pa-md"
      >
        <q-pagination
          v-model="pagination.page"
          :max="pagination.lastPage"
          :max-pages="7"
          boundary-numbers
          direction-links
          color="green-8"
          @update:model-value="cargarPersonas"
        />
      </div>
    </q-card>

    <!-- DETALLE EN UN PASO -->
    <q-dialog v-model="detalleDialogOpen">
      <q-card class="detalle-persona-dialog">
        <q-card-section class="bg-green-8 text-white row items-center">
          <q-avatar size="54px" color="white" text-color="green-9" class="q-mr-md">
            <img
              v-if="fotoPersona(personaDetalle)"
              :src="fotoPersona(personaDetalle)"
              alt="Fotografía de la persona"
              @error="ocultarImagen($event)"
            >
            <span v-else>{{ iniciales(personaDetalle) }}</span>
          </q-avatar>

          <div class="col min-width-zero">
            <div class="text-h6 text-weight-bold ellipsis">
              {{ nombreCompleto(personaDetalle) }}
            </div>
            <div class="text-caption text-green-1">
              Detalle del registro personal
            </div>
          </div>

          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>

        <q-card-section v-if="personaDetalle" class="q-pa-lg">
          <q-list bordered separator class="rounded-borders">
            <q-item>
              <q-item-section avatar><q-icon name="fingerprint" color="green-8" /></q-item-section>
              <q-item-section>
                <q-item-label caption>Cédula de identidad</q-item-label>
                <q-item-label class="text-weight-medium">{{ personaDetalle.ci || 'No registrada' }}</q-item-label>
              </q-item-section>
            </q-item>

            <q-item>
              <q-item-section avatar><q-icon name="phone" color="green-8" /></q-item-section>
              <q-item-section>
                <q-item-label caption>Teléfono / celular</q-item-label>
                <q-item-label class="text-weight-medium">{{ personaDetalle.telefono || 'No registrado' }}</q-item-label>
              </q-item-section>
            </q-item>

            <q-item>
              <q-item-section avatar><q-icon name="home" color="green-8" /></q-item-section>
              <q-item-section>
                <q-item-label caption>Dirección</q-item-label>
                <q-item-label class="text-weight-medium">{{ personaDetalle.direccion || 'No registrada' }}</q-item-label>
              </q-item-section>
            </q-item>

            <q-item>
              <q-item-section avatar><q-icon name="photo_camera" color="green-8" /></q-item-section>
              <q-item-section>
                <q-item-label caption>Fotografías</q-item-label>
                <q-item-label class="text-weight-medium">
                  {{ Array.isArray(personaDetalle.imagenes) ? personaDetalle.imagenes.length : 0 }} registrada(s)
                </q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md bg-grey-1">
          <q-btn flat color="grey-7" label="Cerrar" v-close-popup />
          <q-btn
            outline
            color="blue-8"
            icon="photo_camera"
            label="Fotografías"
            no-caps
            @click="fotografiasDesdeDetalle"
          />
          <q-btn
            color="green-8"
            icon="edit"
            label="Editar"
            unelevated
            no-caps
            @click="editarDesdeDetallePersona"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- FORMULARIO -->
    <q-dialog
      v-model="dialogOpen"
      persistent
    >
      <q-card class="persona-dialog">
        <q-card-section class="bg-green-8 text-white row items-center">
          <q-icon
            :name="isEditing ? 'edit' : 'person_add'"
            size="28px"
            class="q-mr-sm"
          />
          <div>
            <div class="text-h6 text-weight-bold">
              {{ isEditing ? 'Editar persona' : 'Nueva persona' }}
            </div>
            <div class="text-caption text-green-1">
              Información personal registrada en MOTRIX.
            </div>
          </div>

          <q-space />

          <q-btn
            flat
            round
            dense
            icon="close"
            :disable="saving"
            @click="cerrarFormulario"
          />
        </q-card-section>

        <q-form
          ref="formRef"
          @submit.prevent="guardarPersona"
        >
          <q-card-section class="q-pa-md q-pa-lg-md">
            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.trim="form.nombre"
                  outlined
                  label="Nombre *"
                  :rules="[requerido]"
                >
                  <template #prepend>
                    <q-icon name="person" color="green-8" />
                  </template>
                </q-input>
              </div>

              <div class="col-12 col-sm-6">
                <q-input
                  v-model.trim="form.apellidos"
                  outlined
                  label="Apellidos"
                />
              </div>

              <div class="col-12 col-sm-6">
                <q-input
                  v-model.trim="form.ci"
                  outlined
                  label="Cédula de identidad (opcional)"
                >
                  <template #prepend>
                    <q-icon name="fingerprint" color="green-8" />
                  </template>
                </q-input>
              </div>

              <div class="col-12 col-sm-6">
                <q-input
                  v-model.trim="form.telefono"
                  outlined
                  label="Teléfono / celular"
                  maxlength="20"
                >
                  <template #prepend>
                    <q-icon name="phone" color="green-8" />
                  </template>
                </q-input>
              </div>

              <div class="col-12">
                <q-input
                  v-model.trim="form.direccion"
                  outlined
                  label="Dirección"
                  autogrow
                >
                  <template #prepend>
                    <q-icon name="home" color="green-8" />
                  </template>
                </q-input>
              </div>

              <div
                v-if="!isEditing"
                class="col-12"
              >
                <q-file
                  v-model="archivoImagen"
                  outlined
                  clearable
                  accept=".jpg,.jpeg,.png,.gif,.webp,.bmp,image/*"
                  max-file-size="2097152"
                  label="Fotografía (opcional)"
                  @rejected="archivoRechazado"
                >
                  <template #prepend>
                    <q-icon
                      name="photo_camera"
                      color="green-8"
                    />
                  </template>
                </q-file>

                <div class="text-caption text-grey-6 q-mt-xs">
                  JPG, PNG, GIF, WEBP o BMP. Máximo 2 MB.
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
              label="Cancelar"
              :disable="saving"
              @click="cerrarFormulario"
            />

            <q-btn
              type="submit"
              color="green-8"
              icon="save"
              :label="isEditing ? 'Guardar cambios' : 'Registrar'"
              unelevated
              :loading="saving"
            />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- FOTOGRAFÍAS -->
    <q-dialog
      v-model="fotosDialogOpen"
      persistent
    >
      <q-card class="fotos-dialog">
        <q-card-section class="bg-green-8 text-white row items-center">
          <q-icon
            name="photo_camera"
            size="28px"
            class="q-mr-sm"
          />

          <div>
            <div class="text-h6 text-weight-bold">
              Fotografías
            </div>
            <div class="text-caption text-green-1">
              {{ nombreCompleto(personaFotos) }}
            </div>
          </div>

          <q-space />

          <q-btn
            flat
            round
            dense
            icon="close"
            :disable="subiendoFoto"
            @click="cerrarFotografias"
          />
        </q-card-section>

        <q-card-section class="q-pa-md">
          <div
            v-if="personaFotos?.imagenes?.length"
            class="row q-col-gutter-md q-mb-md"
          >
            <div
              v-for="imagen in personaFotos.imagenes"
              :key="imagen.id"
              class="col-6 col-sm-4"
            >
              <q-card
                flat
                bordered
                class="foto-card"
              >
                <q-img
                  :src="urlImagen(imagen.ruta)"
                  ratio="1"
                  fit="cover"
                >
                  <template #error>
                    <div class="absolute-full flex flex-center bg-grey-2 text-grey-6">
                      Archivo no disponible
                    </div>
                  </template>
                </q-img>

                <q-card-actions align="center">
                  <q-btn
                    flat
                    dense
                    color="negative"
                    icon="delete"
                    label="Eliminar"
                    :loading="eliminandoFotoId === imagen.id"
                    @click="eliminarFoto(imagen)"
                  />
                </q-card-actions>
              </q-card>
            </div>
          </div>

          <q-banner
            v-else
            rounded
            class="bg-grey-2 text-grey-7 q-mb-md"
          >
            Esta persona todavía no tiene fotografías disponibles.
          </q-banner>

          <q-separator class="q-my-md" />

          <q-file
            v-model="archivoFotoAdicional"
            outlined
            clearable
            accept=".jpg,.jpeg,.png,.gif,.webp,.bmp,image/*"
            max-file-size="2097152"
            label="Agregar fotografía"
            @rejected="archivoRechazado"
          >
            <template #prepend>
              <q-icon
                name="add_a_photo"
                color="green-8"
              />
            </template>
          </q-file>

          <q-btn
            color="green-8"
            icon="cloud_upload"
            label="Subir fotografía"
            class="q-mt-md"
            unelevated
            :disable="!archivoFotoAdicional"
            :loading="subiendoFoto"
            @click="subirFotoAdicional"
          />
        </q-card-section>

        <q-card-actions
          align="right"
          class="q-pa-md bg-grey-1"
        >
          <q-btn
            flat
            color="grey-7"
            label="Cerrar"
            :disable="subiendoFoto"
            @click="cerrarFotografias"
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
import { api } from 'src/boot/axios.js'
import { API_ORIGIN } from 'src/config/runtime.js'

const $q = useQuasar()

const personas = ref([])
const filter = ref('')
const loading = ref(false)
const saving = ref(false)

const pagination = ref({
  page: 1,
  rowsPerPage: 20,
  rowsNumber: 0,
  lastPage: 1
})

const estadisticas = ref({
  total: 0,
  con_foto: 0,
  con_telefono: 0
})

let temporizadorBusqueda = null

const dialogOpen = ref(false)
const detalleDialogOpen = ref(false)
const personaDetalle = ref(null)
const isEditing = ref(false)
const formRef = ref(null)
const archivoImagen = ref(null)

const fotosDialogOpen = ref(false)
const personaFotos = ref(null)
const archivoFotoAdicional = ref(null)
const subiendoFoto = ref(false)
const eliminandoFotoId = ref(null)

const formDefault = {
  id: null,
  nombre: '',
  apellidos: '',
  ci: '',
  telefono: '',
  direccion: ''
}

const form = ref({
  ...formDefault
})

const requerido = valor =>
  Boolean(String(valor || '').trim())
  || 'Campo obligatorio'

const personasFiltradas = computed(
  () => personas.value
)

const totalConFoto = computed(
  () => estadisticas.value.con_foto
)

const totalConTelefono = computed(
  () => estadisticas.value.con_telefono
)

function nombreCompleto(persona) {
  if (!persona) return 'Persona'

  return [
    persona.nombre,
    persona.apellidos
  ]
    .filter(Boolean)
    .join(' ')
    .trim()
    || `Persona #${persona.id || '—'}`
}

function iniciales(persona) {
  const partes = nombreCompleto(persona)
    .split(/\s+/)
    .filter(Boolean)

  return (
    (
      partes[0]?.charAt(0)
      || ''
    )
    + (
      partes.length > 1
        ? partes[partes.length - 1].charAt(0)
        : ''
    )
  )
    .toUpperCase()
    .slice(0, 2)
    || 'P'
}

function apiOrigen() {
  try {
    return new URL(api.defaults.baseURL).origin
  } catch {
    return API_ORIGIN
  }
}

function urlImagen(ruta) {
  if (!ruta) return ''

  if (/^https?:\/\//i.test(ruta)) {
    return ruta
  }

  return `${apiOrigen()}/storage/${String(ruta).replace(/^\/+/, '')}`
}

function fotoPersona(persona) {
  const imagenes =
    Array.isArray(persona?.imagenes)
      ? persona.imagenes
      : []

  if (!imagenes.length) {
    return ''
  }

  return urlImagen(
    imagenes[imagenes.length - 1]?.ruta
  )
}

function ocultarImagen(evento) {
  const imagen = evento?.target

  if (imagen) {
    imagen.style.display = 'none'
  }
}

function abrirDetallePersona(persona) {
  personaDetalle.value = persona
  detalleDialogOpen.value = true
}

function editarDesdeDetallePersona() {
  if (!personaDetalle.value) return
  const persona = personaDetalle.value
  detalleDialogOpen.value = false
  abrirFormulario(persona)
}

function fotografiasDesdeDetalle() {
  if (!personaDetalle.value) return
  const persona = personaDetalle.value
  detalleDialogOpen.value = false
  abrirFotografias(persona)
}

function extraerMensaje(error) {
  const data = error?.response?.data

  if (data?.errors) {
    const mensaje = Object.values(
      data.errors
    )
      .flat()
      .find(Boolean)

    if (mensaje) return mensaje
  }

  if (data?.errores) {
    const mensaje = Object.values(
      data.errores
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

async function cargarPersonas(
  pagina = pagination.value.page
) {
  loading.value = true

  try {
    const respuesta = await api.get(
      '/personas',
      {
        params: {
          paginated: 1,
          page: pagina,
          per_page:
            pagination.value.rowsPerPage,
          q: String(
            filter.value || ''
          ).trim() || undefined
        }
      }
    )

    personas.value = Array.isArray(
      respuesta.data?.data
    )
      ? respuesta.data.data
      : []

    const meta =
      respuesta.data?.meta || {}

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
      con_foto:
        Number(meta.stats?.con_foto || 0),
      con_telefono:
        Number(
          meta.stats?.con_telefono || 0
        )
    }
  } catch (error) {
    console.error(
      'Error cargando personas:',
      error
    )

    $q.notify({
      type: 'negative',
      position: 'top',
      message: extraerMensaje(error)
    })
  } finally {
    loading.value = false
  }
}

function abrirFormulario(persona = null) {
  archivoImagen.value = null

  if (persona) {
    isEditing.value = true

    form.value = {
      id: persona.id,
      nombre: persona.nombre || '',
      apellidos: persona.apellidos || '',
      ci: persona.ci || '',
      telefono: persona.telefono || '',
      direccion: persona.direccion || ''
    }
  } else {
    isEditing.value = false
    form.value = {
      ...formDefault
    }
  }

  dialogOpen.value = true
}

function cerrarFormulario(forzar = false) {
  if (saving.value && !forzar) return

  dialogOpen.value = false
  archivoImagen.value = null
  form.value = {
    ...formDefault
  }
}

function payloadPersona() {
  return {
    nombre: form.value.nombre,
    apellidos: form.value.apellidos || null,
    ci: form.value.ci,
    telefono: form.value.telefono || null,
    direccion: form.value.direccion || null
  }
}

async function guardarPersona() {
  const valido =
    await formRef.value?.validate()

  if (valido === false) return

  saving.value = true

  try {
    if (isEditing.value) {
      await api.put(
        `/personas/${form.value.id}`,
        payloadPersona()
      )
    } else if (archivoImagen.value) {
      const datos = new FormData()

      Object.entries(
        payloadPersona()
      ).forEach(([clave, valor]) => {
        if (
          valor !== null
          && valor !== undefined
        ) {
          datos.append(clave, valor)
        }
      })

      datos.append(
        'imagen',
        archivoImagen.value
      )

      await api.post(
        '/imagen/persona/registrar',
        datos,
        {
          headers: {
            'Content-Type':
              'multipart/form-data'
          }
        }
      )
    } else {
      await api.post(
        '/personas',
        payloadPersona()
      )
    }

    $q.notify({
      type: 'positive',
      position: 'top',
      icon: 'check_circle',
      message: isEditing.value
        ? 'Persona actualizada correctamente.'
        : 'Persona registrada correctamente.'
    })

    cerrarFormulario(true)
    await cargarPersonas()
  } catch (error) {
    console.error(
      'Error guardando persona:',
      error
    )

    $q.notify({
      type: 'negative',
      position: 'top',
      multiLine: true,
      message: extraerMensaje(error)
    })
  } finally {
    saving.value = false
  }
}

function confirmarEliminar(persona) {
  $q.dialog({
    title: 'Eliminar persona',
    message:
      `¿Eliminar a ${nombreCompleto(persona)}?`,
    cancel: {
      label: 'Cancelar',
      flat: true
    },
    ok: {
      label: 'Eliminar',
      color: 'negative',
      unelevated: true
    },
    persistent: true
  }).onOk(async () => {
    try {
      await api.delete(
        `/personas/${persona.id}`
      )

      $q.notify({
        type: 'positive',
        position: 'top',
        message:
          'Persona eliminada correctamente.'
      })

      await cargarPersonas()
    } catch (error) {
      $q.notify({
        type: 'negative',
        position: 'top',
        multiLine: true,
        message: extraerMensaje(error)
      })
    }
  })
}

async function abrirFotografias(persona) {
  archivoFotoAdicional.value = null

  try {
    const respuesta = await api.get(
      `/personas/${persona.id}`
    )

    personaFotos.value =
      respuesta.data

    fotosDialogOpen.value = true
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      message: extraerMensaje(error)
    })
  }
}

function cerrarFotografias() {
  if (subiendoFoto.value) return

  fotosDialogOpen.value = false
  personaFotos.value = null
  archivoFotoAdicional.value = null
}

function archivoRechazado() {
  $q.notify({
    type: 'negative',
    position: 'top',
    message:
      'La imagen debe pesar máximo 2 MB y ser JPG, PNG, GIF, WEBP o BMP.'
  })
}

async function subirFotoAdicional() {
  if (
    !personaFotos.value?.id
    || !archivoFotoAdicional.value
  ) {
    return
  }

  subiendoFoto.value = true

  try {
    const datos = new FormData()

    datos.append(
      'imagen',
      archivoFotoAdicional.value
    )

    await api.post(
      `/personas/${personaFotos.value.id}/imagen`,
      datos,
      {
        headers: {
          'Content-Type':
            'multipart/form-data'
        }
      }
    )

    const respuesta = await api.get(
      `/personas/${personaFotos.value.id}`
    )

    personaFotos.value =
      respuesta.data

    archivoFotoAdicional.value = null

    await cargarPersonas()

    $q.notify({
      type: 'positive',
      position: 'top',
      message:
        'Fotografía agregada correctamente.'
    })
  } catch (error) {
    $q.notify({
      type: 'negative',
      position: 'top',
      message: extraerMensaje(error)
    })
  } finally {
    subiendoFoto.value = false
  }
}

function eliminarFoto(imagen) {
  $q.dialog({
    title: 'Eliminar fotografía',
    message:
      '¿Deseas eliminar esta fotografía?',
    cancel: true,
    persistent: true
  }).onOk(async () => {
    eliminandoFotoId.value =
      imagen.id

    try {
      await api.delete(
        `/imagenes-personas/${imagen.id}`
      )

      personaFotos.value.imagenes =
        personaFotos.value.imagenes
          .filter(
            item => item.id !== imagen.id
          )

      await cargarPersonas()

      $q.notify({
        type: 'positive',
        position: 'top',
        message: 'Fotografía eliminada.'
      })
    } catch (error) {
      $q.notify({
        type: 'negative',
        position: 'top',
        message: extraerMensaje(error)
      })
    } finally {
      eliminandoFotoId.value = null
    }
  })
}

watch(
  filter,
  () => {
    if (temporizadorBusqueda) {
      window.clearTimeout(
        temporizadorBusqueda
      )
    }

    temporizadorBusqueda =
      window.setTimeout(
        () => {
          pagination.value.page = 1
          cargarPersonas(1)
        },
        350
      )
  }
)

onMounted(
  cargarPersonas
)
</script>

<style scoped>
.personas-page {
  min-height: 100%;
  background: transparent;
}

.stat-card,
.filtro-card,
.lista-card {
  border-color: #d8e7d5;
  border-radius: 14px;
}

.stat-card {
  border-left: 4px solid #2e7d32;
}

.lista-card {
  overflow: hidden;
  border-top: 3px solid #2e7d32;
}

.persona-item {
  min-height: 82px;
  transition:
    background-color 0.15s ease;
}

.persona-item:hover {
  background: #f7fbf5;
}

.detalle-persona-dialog {
  width: 680px;
  max-width: 94vw;
  border-radius: 16px;
  overflow: hidden;
}

.persona-dialog {
  width: 700px;
  max-width: 94vw;
  border-radius: 16px;
}

.fotos-dialog {
  width: 760px;
  max-width: 94vw;
  border-radius: 16px;
}

.foto-card {
  overflow: hidden;
  border-radius: 12px;
}

@media (max-width: 599px) {
  .detalle-persona-dialog,
  .persona-dialog,
  .fotos-dialog {
    width: 100vw;
    max-width: 100vw;
    border-radius: 0;
  }
}
</style>
