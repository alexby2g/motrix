<template>
  <q-dialog
    v-model="dialogo"
    persistent
    maximized
    transition-show="fade"
    transition-hide="fade"
  >
    <q-card class="legal-shell">
      <div class="legal-panel">
        <div class="legal-header">
          <q-avatar
            size="54px"
            color="green-1"
            text-color="green-9"
            icon="verified_user"
          />

          <div class="col">
            <div class="text-h5 text-weight-bold text-grey-9">
              Antes de continuar
            </div>
            <div class="text-body2 text-grey-6">
              Revisa y acepta las condiciones vigentes de MOTRIX.
            </div>
          </div>
        </div>

        <q-banner
          rounded
          class="bg-green-1 text-green-10 q-mt-lg"
        >
          <template #avatar>
            <q-icon
              name="privacy_tip"
              size="28px"
              color="green-8"
            />
          </template>

          Esta aceptación se solicita una sola vez por cada versión
          vigente y queda registrada con fecha y hora.
        </q-banner>

        <div
          v-if="cargando"
          class="q-py-xl text-center"
        >
          <q-spinner
            color="green-8"
            size="42px"
          />
          <div class="text-grey-6 q-mt-md">
            Verificando documentos...
          </div>
        </div>

        <template v-else>
          <div class="legal-document-list q-mt-lg">
            <button
              type="button"
              class="legal-document"
              @click="abrirDocumento('terms')"
            >
              <q-icon
                name="description"
                size="28px"
                color="green-8"
              />
              <span class="col text-left">
                <strong>Términos y Condiciones</strong>
                <small>
                  Versión {{ documentos?.terms?.version || '—' }}
                  · vigente desde
                  {{ documentos?.terms?.effective_date || '—' }}
                </small>
              </span>
              <q-icon name="chevron_right" />
            </button>

            <button
              type="button"
              class="legal-document"
              @click="abrirDocumento('privacy')"
            >
              <q-icon
                name="shield"
                size="28px"
                color="green-8"
              />
              <span class="col text-left">
                <strong>Política de Privacidad</strong>
                <small>
                  Versión {{ documentos?.privacy?.version || '—' }}
                  · vigente desde
                  {{ documentos?.privacy?.effective_date || '—' }}
                </small>
              </span>
              <q-icon name="chevron_right" />
            </button>
          </div>

          <q-checkbox
            v-model="acepto"
            class="legal-checkbox q-mt-lg"
            color="green-8"
          >
            <span class="text-body2 text-grey-9">
              He leído y acepto los
              <button
                type="button"
                class="legal-inline-link"
                @click.stop="abrirDocumento('terms')"
              >
                Términos y Condiciones
              </button>
              y la
              <button
                type="button"
                class="legal-inline-link"
                @click.stop="abrirDocumento('privacy')"
              >
                Política de Privacidad
              </button>
              de MOTRIX.
            </span>
          </q-checkbox>

          <div class="row q-col-gutter-sm q-mt-lg">
            <div class="col-12 col-sm-5">
              <q-btn
                outline
                color="grey-8"
                icon="logout"
                label="Cerrar sesión"
                class="full-width legal-action"
                no-caps
                :disable="guardando"
                @click="cerrarSesion"
              />
            </div>

            <div class="col-12 col-sm-7">
              <q-btn
                color="green-8"
                icon="check_circle"
                label="Aceptar y continuar"
                class="full-width legal-action"
                unelevated
                no-caps
                :loading="guardando"
                :disable="!acepto"
                @click="aceptar"
              />
            </div>
          </div>

          <div class="text-caption text-grey-6 text-center q-mt-md">
            Si no deseas aceptar, puedes cerrar sesión.
          </div>
        </template>
      </div>
    </q-card>
  </q-dialog>

  <q-dialog v-model="dialogoDocumento">
    <q-card class="document-card">
      <q-card-section class="row items-center no-wrap">
        <q-avatar
          color="green-1"
          text-color="green-9"
          :icon="documentoActivo?.key === 'privacy' ? 'shield' : 'description'"
        />
        <div class="col q-ml-md">
          <div class="text-h6 text-weight-bold">
            {{ documentoActivo?.title }}
          </div>
          <div class="text-caption text-grey-6">
            Versión {{ documentoActivo?.version }}
            · {{ documentoActivo?.effective_date }}
          </div>
        </div>
        <q-btn
          flat
          round
          dense
          icon="close"
          v-close-popup
        />
      </q-card-section>

      <q-separator />

      <q-card-section class="document-content">
        <p class="text-grey-7">
          {{ documentoActivo?.summary }}
        </p>

        <section
          v-for="section in documentoActivo?.sections || []"
          :key="section.title"
          class="q-mb-lg"
        >
          <div class="text-subtitle1 text-weight-bold text-green-10 q-mb-sm">
            {{ section.title }}
          </div>
          <p
            v-for="paragraph in section.paragraphs || []"
            :key="paragraph"
            class="text-body2 text-grey-9"
          >
            {{ paragraph }}
          </p>
        </section>
      </q-card-section>

      <q-separator />

      <q-card-actions align="right" class="q-pa-md">
        <q-btn
          color="green-8"
          label="Entendido"
          no-caps
          unelevated
          v-close-popup
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup>
import {
  computed,
  onMounted,
  ref
} from 'vue'

import {
  useQuasar
} from 'quasar'

import {
  useRouter
} from 'vue-router'

import {
  api
} from 'src/boot/axios.js'

const $q = useQuasar()
const router = useRouter()

const dialogo = ref(false)
const dialogoDocumento = ref(false)
const cargando = ref(false)
const guardando = ref(false)
const acepto = ref(false)
const documentos = ref(null)
const documentoKey = ref('terms')

const documentoActivo = computed(() =>
  documentos.value?.[documentoKey.value] || null
)

function usuarioActual() {
  try {
    return JSON.parse(
      localStorage.getItem('motrix_user') || 'null'
    )
  } catch {
    return null
  }
}

function rolRequiereAceptacion() {
  const role = String(
    usuarioActual()?.role || ''
  )
    .trim()
    .toLowerCase()

  return [
    'pasajero',
    'conductor'
  ].includes(role)
}

async function cargarDocumentos() {
  if (documentos.value) return

  const respuesta = await api.get(
    '/legal/documents'
  )

  documentos.value =
    respuesta?.data?.data || null
}

async function verificar() {
  if (!rolRequiereAceptacion()) {
    dialogo.value = false
    return
  }

  cargando.value = true

  try {
    const [
      respuestaEstado
    ] = await Promise.all([
      api.get('/legal/status'),
      cargarDocumentos()
    ])

    const estado =
      respuestaEstado?.data?.data

    dialogo.value =
      Boolean(
        estado?.required
        && !estado?.accepted
      )

    if (!dialogo.value) {
      acepto.value = false
    }
  } catch (error) {
    /*
     * No bloqueamos administradores ni usuarios sin sesión.
     * Para pasajero/conductor, si el backend indica que falta la
     * migración, se muestra el problema de forma explícita.
     */
    if (
      error?.response?.data?.code
      === 'LEGAL_MIGRATION_REQUIRED'
    ) {
      dialogo.value = true

      $q.notify({
        type: 'negative',
        position: 'top',
        timeout: 7000,
        message:
          'Falta instalar la migración de Términos y Privacidad.'
      })
    }
  } finally {
    cargando.value = false
  }
}

async function abrirDocumento(key) {
  documentoKey.value = key
  await cargarDocumentos()
  dialogoDocumento.value = true
}

async function aceptar() {
  if (!acepto.value) return

  guardando.value = true

  try {
    await cargarDocumentos()

    await api.post(
      '/legal/accept',
      {
        accepted_terms: true,
        accepted_privacy: true,
        terms_version:
          documentos.value?.terms?.version,
        privacy_version:
          documentos.value?.privacy?.version,
        channel: 'web'
      }
    )

    dialogo.value = false
    acepto.value = false

    $q.notify({
      type: 'positive',
      position: 'top',
      icon: 'verified_user',
      message:
        'Términos y Política de Privacidad aceptados.'
    })
  } catch (error) {
    const primerError =
      error?.response?.data?.errors
        ? Object.values(
            error.response.data.errors
          )
          .flat()
          .find(Boolean)
        : null

    $q.notify({
      type: 'negative',
      position: 'top',
      message:
        primerError
        || error?.response?.data?.message
        || 'No se pudo registrar la aceptación.'
    })
  } finally {
    guardando.value = false
  }
}

function limpiarSesionLocal() {
  const keys = [
    'motrix_token',
    'motrix_user',
    'mototaxista_id',
    'pasajero_id'
  ]

  keys.forEach(key => {
    localStorage.removeItem(key)
    sessionStorage.removeItem(key)
  })
}

async function cerrarSesion() {
  guardando.value = true

  try {
    await api.post('/auth/logout')
  } catch {
    // La sesión local se limpia incluso si el token ya expiró.
  } finally {
    limpiarSesionLocal()
    dialogo.value = false
    guardando.value = false
    await router.replace('/login')
  }
}

onMounted(verificar)
</script>

<style scoped>
.legal-shell {
  display: grid;
  min-height: 100vh;
  place-items: center;
  padding: 24px;
  background:
    radial-gradient(
      circle at 15% 10%,
      rgba(102, 187, 106, 0.18),
      transparent 30%
    ),
    #f4f8f2;
}

.legal-panel {
  width: min(660px, 100%);
  padding: 30px;
  border: 1px solid #d9e7d4;
  border-radius: 24px;
  background: #fff;
  box-shadow: 0 18px 55px rgba(27, 94, 32, 0.12);
}

.legal-header {
  display: flex;
  align-items: center;
  gap: 16px;
}

.legal-document-list {
  display: grid;
  gap: 10px;
}

.legal-document {
  display: flex;
  width: 100%;
  align-items: center;
  gap: 14px;
  padding: 16px;
  border: 1px solid #dbe7d8;
  border-radius: 16px;
  color: #263238;
  background: #fbfdf9;
  cursor: pointer;
}

.legal-document:hover {
  border-color: #66bb6a;
  background: #f4fbf1;
}

.legal-document strong,
.legal-document small {
  display: block;
}

.legal-document small {
  margin-top: 3px;
  color: #78909c;
}

.legal-checkbox {
  align-items: flex-start;
  padding: 14px 8px;
  border-radius: 14px;
  background: #fafcf9;
}

.legal-inline-link {
  padding: 0;
  border: 0;
  color: #2e7d32;
  font: inherit;
  font-weight: 700;
  text-decoration: underline;
  background: transparent;
  cursor: pointer;
}

.legal-action {
  min-height: 48px;
  border-radius: 12px;
  font-weight: 700;
}

.document-card {
  width: min(760px, calc(100vw - 24px));
  max-width: 760px;
  border-radius: 20px;
}

.document-content {
  max-height: min(68vh, 720px);
  overflow-y: auto;
  line-height: 1.6;
}

@media (max-width: 599px) {
  .legal-shell {
    padding: 12px;
  }

  .legal-panel {
    padding: 22px 18px;
    border-radius: 20px;
  }

  .legal-header {
    align-items: flex-start;
  }

  .document-card {
    width: calc(100vw - 12px);
    border-radius: 16px;
  }
}
</style>
