<template>
  <q-page padding>
    <div class="text-h5 text-weight-bold q-mb-sm">Mis documentos y reportes</div>
    <p class="text-grey-7">Descarga los registros disponibles para tu cuenta y organización.</p>
    <q-banner v-if="error" class="bg-red-1 text-negative q-mb-md">{{ error }}</q-banner>
    <div class="row q-col-gutter-md q-mb-lg">
      <q-input class="col-12 col-sm-6" outlined type="date" v-model="desde" label="Desde" />
      <q-input class="col-12 col-sm-6" outlined type="date" v-model="hasta" label="Hasta" />
    </div>
    <q-banner class="bg-blue-1 q-mb-md">Las fechas se aplican a viajes y actividad. Word descarga un archivo .doc compatible; los reportes son informativos.</q-banner>
    <q-inner-loading :showing="cargando" />
    <q-card v-for="opcion in opciones" :key="opcion.tipo" flat bordered class="q-mb-md">
      <q-card-section class="text-subtitle1 text-weight-bold">{{ opcion.titulo }}</q-card-section>
      <q-card-actions class="q-pa-md">
        <q-btn v-for="formato in formatos" :key="formato.tipo" :label="formato.nombre" :icon="formato.icono"
          outline color="primary" :disable="descargando" @click="descargar(opcion.tipo, formato.tipo)" />
      </q-card-actions>
    </q-card>
  </q-page>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { api } from 'boot/axios'
const opciones = ref([])
const error = ref('')
const cargando = ref(true)
const descargando = ref(false)
const fechaLocal = (fecha) => `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, '0')}-${String(fecha.getDate()).padStart(2, '0')}`
const hoy = new Date()
const desde = ref(fechaLocal(new Date(hoy.getFullYear(), hoy.getMonth(), 1)))
const hasta = ref(fechaLocal(hoy))
const formatos = [{ tipo: 'pdf', nombre: 'PDF', icono: 'picture_as_pdf' }, { tipo: 'excel', nombre: 'Excel', icono: 'table_chart' }, { tipo: 'word', nombre: 'Word', icono: 'description' }]
onMounted(async () => {
  try { opciones.value = (await api.get('/documentos')).data } catch { error.value = 'No se pudieron cargar los documentos.' }
  finally { cargando.value = false }
})
async function descargar(tipo, formato) {
  if (!desde.value || !hasta.value || hasta.value < desde.value) { error.value = 'Revisa las fechas del período.'; return }
  descargando.value = true
  error.value = ''
  try {
    const respuesta = await api.get(`/documentos/${tipo}/${formato}`, { params: { desde: desde.value, hasta: hasta.value }, responseType: 'blob' })
    const url = URL.createObjectURL(respuesta.data)
    const enlace = document.createElement('a')
    enlace.href = url
    enlace.download = `MOTRIX_${tipo}.${formato === 'excel' ? 'xlsx' : formato === 'word' ? 'doc' : 'pdf'}`
    document.body.appendChild(enlace); enlace.click(); enlace.remove()
    window.setTimeout(() => URL.revokeObjectURL(url), 10000)
  } catch (e) {
    try { error.value = JSON.parse(await e.response.data.text()).message || 'No se pudo generar el reporte.' }
    catch { error.value = 'No se pudo generar el reporte. Revisa tu conexión e inténtalo nuevamente.' }
  } finally { descargando.value = false }
}
</script>
