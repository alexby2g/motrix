import fs from 'node:fs'
import path from 'node:path'

const expectedPackage = 'bo.edu.josecastillo.motrix'
const file = path.resolve('android/app/google-services.json')

function fail(message) {
  console.error(`ERROR Firebase Android: ${message}`)
  process.exit(1)
}

if (!fs.existsSync(file)) {
  fail('falta android/app/google-services.json. Sin este archivo FCM puede cerrar la APK al registrar notificaciones.')
}

let data
try {
  data = JSON.parse(fs.readFileSync(file, 'utf8'))
} catch (error) {
  fail(`google-services.json no es JSON válido: ${error.message}`)
}

const project = data?.project_info || {}
const clients = Array.isArray(data?.client) ? data.client : []
const client = clients.find((item) =>
  item?.client_info?.android_client_info?.package_name === expectedPackage
)

if (!client) {
  fail(`no existe un cliente Android para ${expectedPackage}`)
}

const appId = String(client?.client_info?.mobilesdk_app_id || '').trim()
const projectId = String(project?.project_id || '').trim()
const projectNumber = String(project?.project_number || '').trim()
const apiKey = String(client?.api_key?.[0]?.current_key || '').trim()

const missing = []
if (!appId) missing.push('mobilesdk_app_id')
if (!projectId) missing.push('project_id')
if (!projectNumber) missing.push('project_number')
if (!apiKey) missing.push('api_key.current_key')

if (missing.length) {
  fail(`faltan campos obligatorios: ${missing.join(', ')}`)
}

console.log(`Firebase Android OK para ${expectedPackage} (${projectId}).`)
