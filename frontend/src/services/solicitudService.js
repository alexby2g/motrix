import { api } from '../boot/axios.js'

const solicitudService = {
  getAll() {
    return api.get('/solicitudes')
  },

  getById(id) {
    return api.get(`/solicitudes/${id}`)
  },

  create(datos) {
    return api.post('/solicitudes', datos)
  },

  update(id, datos) {
    return api.put(`/solicitudes/${id}`, datos)
  },

  delete(id) {
    return api.delete(`/solicitudes/${id}`)
  },

  aceptar(id, mototaxistaId) {
    return api.post(`/solicitudes/${id}/aceptar`, {
      mototaxista_id: mototaxistaId
    })
  },

  rechazar(id, mototaxistaId) {
    return api.post(`/solicitudes/${id}/rechazar`, {
      mototaxista_id: mototaxistaId
    })
  },

  actualizarEstado(id, datos) {
    return api.put(
      `/solicitudes/${id}/estado`,
      datos
    )
  }
}

export default solicitudService