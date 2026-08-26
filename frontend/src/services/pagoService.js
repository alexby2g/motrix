import { api } from 'src/boot/axios.js'

export default {
  getAll() {
    return api.get('/pagos')
  },
  create(data) {
    return api.post('/pagos', data)
  },
  update(id, data) {
    return api.put(`/pagos/${id}`, data)
  },
  delete(id) {
    return api.delete(`/pagos/${id}`)
  }
}
