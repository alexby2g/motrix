import { api } from 'src/boot/axios.js'

export default {
  getAll() {
    return api.get('/mototaxistas')
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
