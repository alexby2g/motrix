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

export default api
