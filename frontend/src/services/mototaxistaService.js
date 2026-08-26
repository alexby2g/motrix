import { api } from 'src/boot/axios.js'

export default {
  getAll(params = {}) {
    return api.get('/mototaxistas', { params })
  },
  opcionesMotocicleta(params = {}) {
    return api.get('/mototaxistas/opciones-motocicleta', { params })
  },
  opcionesPagoSindical(params = {}) {
    return api.get('/mototaxistas/opciones-pago-sindical', { params })
  },
  create(data) {
    return api.post('/mototaxistas', data)
  },
  update(id, data) {
    return api.put(`/mototaxistas/${id}`, data)
  },
  delete(id) {
    return api.delete(`/mototaxistas/${id}`)
  }
}
