import axios from 'axios'
import { API_URL } from 'src/config/runtime.js'

const api = axios.create({
  baseURL: API_URL,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json'
  }
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('motrix_token')

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('motrix_token')
      localStorage.removeItem('motrix_user')
      localStorage.removeItem('mototaxista_id')
      localStorage.removeItem('pasajero_id')

      if (typeof window !== 'undefined' && window.location.hash !== '#/login') {
        window.location.hash = '#/login'
      }
    }

    return Promise.reject(error)
  }
)

export default ({ app }) => {
  app.config.globalProperties.$axios = axios
  app.config.globalProperties.$api = api
}

export { axios, api }
