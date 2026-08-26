import { api } from 'src/boot/axios.js'

export default {
  getAll(params = {}) {
    return api.get('/motocicletas', { params })
  },

  getById(id) {
    return api.get(`/motocicletas/${id}`)
  },

  create(data) {
    return api.post('/motocicletas', data)
  },

  update(id, data) {
    return api.put(`/motocicletas/${id}`, data)
  },

  uploadImage(id, archivo) {
    const formData = new FormData()
    formData.append('imagen', archivo)

    return api.post(
      `/motocicletas/${id}/imagen`,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      }
    )
  },

  deleteImage(id) {
    return api.delete(`/imagenes-motocicletas/${id}`)
  },

  delete(id) {
    return api.delete(`/motocicletas/${id}`)
  }
}
