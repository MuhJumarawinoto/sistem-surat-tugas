import axios from 'axios'

// Flag untuk mencegah multiple redirect
let isRedirecting = false

// Simpan original URL untuk redirect setelah login
function saveRedirectUrl() {
  const currentPath = window.location.pathname + window.location.search
  if (currentPath !== '/login' && currentPath !== '/register') {
    sessionStorage.setItem('redirectAfterLogin', currentPath)
  }
}

// Main API instance - for normal requests (30s timeout)
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Quick API instance - for non-critical, background requests (5s timeout)
// Use for: notifications, unread count, etc.
const apiQuick = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  timeout: 5000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Long-running API instance - for file uploads, large data (60s timeout)
const apiLong = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  timeout: 60000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Setup interceptors for all instances
function setupInterceptors(instance) {
  instance.interceptors.request.use(
    (config) => {
      const token = localStorage.getItem('token')
      if (token) {
        config.headers.Authorization = `Bearer ${token}`
      }
      return config
    },
    (error) => {
      return Promise.reject(error)
    }
  )

  instance.interceptors.response.use(
    (response) => response,
    (error) => {
      // Handle 401 Unauthorized - Session expired
      if (error.response?.status === 401) {
        // Cegah multiple redirect
        if (isRedirecting) return Promise.reject(error)

        isRedirecting = true

        // Simpan URL untuk redirect setelah login
        saveRedirectUrl()

        // Clear auth data
        localStorage.removeItem('token')
        localStorage.removeItem('user')

        // Tampilkan pesan session expired
        const showMessage = !sessionStorage.getItem('sessionExpiredShown')
        if (showMessage) {
          sessionStorage.setItem('sessionExpiredShown', 'true')
          // Simpan pesan untuk ditampilkan di login page
          sessionStorage.setItem('sessionMessage', 'Sesi Anda telah berakhir. Silakan login kembali.')
        }

        // Redirect ke login
        setTimeout(() => {
          isRedirecting = false
          window.location.href = '/login'
        }, 100)

        return Promise.reject(error)
      }

      // Handle 403 Forbidden - Unauthorized access
      if (error.response?.status === 403) {
        console.error('Access forbidden:', error.response.data?.message || 'Anda tidak memiliki akses')
        return Promise.reject(error)
      }

      // Handle 419 CSRF token mismatch
      if (error.response?.status === 419) {
        console.error('CSRF token mismatch')
        localStorage.removeItem('token')
        localStorage.removeItem('user')
        window.location.href = '/login'
        return Promise.reject(error)
      }

      // Handle 500 Server Error
      if (error.response?.status === 500) {
        console.error('Server error:', error.response.data?.message || 'Terjadi kesalahan pada server')
        return Promise.reject(error)
      }

      // Handle network error (no response)
      if (!error.response) {
        console.error('Network error: Tidak dapat terhubung ke server')
        return Promise.reject(error)
      }

      return Promise.reject(error)
    }
  )
}

setupInterceptors(api)
setupInterceptors(apiQuick)
setupInterceptors(apiLong)

export default api
export { apiQuick, apiLong }
