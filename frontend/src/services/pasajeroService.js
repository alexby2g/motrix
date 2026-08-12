import { api } from '../boot/axios.js'

const pasajeroService = {
  getAll() {
    return api.get('/pasajeros')
  },

  getById(id) {
    return api.get(`/pasajeros/${id}`)
  },

  create(datos) {
    return api.post('/pasajeros', datos)
  },

  update(id, datos) {
    return api.put(`/pasajeros/${id}`, datos)
  },

  delete(id) {
    return api.delete(`/pasajeros/${id}`)
  }
}

export default pasajeroService