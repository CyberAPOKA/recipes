import axios from 'axios'

// Ensure baseURL always ends with /api (no trailing slash)
let baseURL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
// Remove any trailing slashes
baseURL = baseURL.replace(/\/+$/, '')
// Ensure it ends with /api
if (!baseURL.endsWith('/api')) {
  baseURL = baseURL + '/api'
}

const api = axios.create({
  baseURL: baseURL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Debug: log the baseURL to ensure it's correct
console.log('Axios baseURL configured:', baseURL)

// Request interceptor to add auth token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }

    // Debug: log the full URL being requested
    // Axios combines baseURL + url, so if url starts with /, it replaces the baseURL path
    const fullURL = config.url?.startsWith('http')
      ? config.url
      : `${config.baseURL}${config.url}`
    console.log('Axios making request to:', fullURL, '(baseURL:', config.baseURL, ', url:', config.url, ')')

    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor to handle errors
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default api

