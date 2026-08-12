import { api } from 'src/boot/axios.js'

export default {
  getAll() {
    return api.get('/sindicatos')
  },
  create(data) {
    return api.post('/sindicatos', data)
  },
  update(id, data) {
    return api.put(`/sindicatos/${id}`, data)
  },
  delete(id) {
    return api.delete(`/sindicatos/${id}`)
  }
}
