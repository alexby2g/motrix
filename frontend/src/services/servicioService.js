import { api } from 'src/boot/axios.js'

export default {
  getAll() {
    return api.get('/servicios')
  },
  create(data) {
    return api.post('/servicios', data)
  },
  update(id, data) {
    return api.put(`/servicios/${id}`, data)
  },
  delete(id) {
    return api.delete(`/servicios/${id}`)
  }
}
