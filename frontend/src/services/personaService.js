import { api } from 'src/boot/axios.js'

export default {
  getAll() {
    return api.get('/personas')
  },
  create(data) {
    return api.post('/personas', data)
  },
  update(id, data) {
    return api.put(`/personas/${id}`, data)
  },
  delete(id) {
    return api.delete(`/personas/${id}`)
  }
}
