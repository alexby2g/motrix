import { api } from 'src/boot/axios.js'

export default {
  getAll() {
    return api.get('/motocicletas')
  },
  create(data) {
    return api.post('/motocicletas', data)
  },
  update(id, data) {
    return api.put(`/motocicletas/${id}`, data)
  },
  delete(id) {
    return api.delete(`/motocicletas/${id}`)
  }
}
