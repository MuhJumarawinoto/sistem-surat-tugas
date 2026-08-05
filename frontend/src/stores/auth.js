import { defineStore } from 'pinia'
import api from '@/services/api'

let tokenCheckInterval = null

// Helper function to safely get from localStorage
function getFromLocalStorage(key, parse = false) {
  try {
    const value = localStorage.getItem(key)

    // Handle null, undefined, or "undefined" string
    if (value === null || value === 'undefined') return null

    return parse ? JSON.parse(value) : value
  } catch (error) {
    console.error(`[AUTH] Error reading ${key} from localStorage:`, error)

    // Clear corrupted data
    localStorage.removeItem(key)
    return null
  }
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: getFromLocalStorage('user', true),
    token: getFromLocalStorage('token'),
    tokenExpiryTime: getFromLocalStorage('tokenExpiryTime'),
    selectedService: getFromLocalStorage('selectedService'), // 'tugas-belajar' or 'pga'
    loading: false,
    showSessionWarning: false,
  }),

  getters: {
    isAuthenticated: (state) => {
      const authenticated = !!state.token && !!state.user
      console.log('[AUTH] isAuthenticated check:', {
        hasToken: !!state.token,
        hasUser: !!state.user,
        result: authenticated
      })
      return authenticated
    },
    userRole: (state) => state.user?.role || null,
    isPemohon: (state) => state.user?.role === 'pemohon',
    isAtasan: (state) => state.user?.role === 'atasan',
    isBidang: (state) => state.user?.role === 'bidang',
    isAdmin: (state) => state.user?.role === 'admin_bkpsdm',
    isKepala: (state) => state.user?.role === 'kepala_bkpsdm',
    isKepalaUnit: (state) => state.user?.is_kepala_unit === true,

    // Token expiry in minutes
    tokenExpiryMinutes: (state) => {
      if (!state.tokenExpiryTime) return null
      const now = new Date().getTime()
      const expiry = new Date(state.tokenExpiryTime).getTime()
      const diff = expiry - now
      return Math.max(0, Math.floor(diff / 60000))
    },
    // Service selection getters
    isTugasBelajarService: (state) => state.selectedService === 'tugas-belajar' || !state.selectedService,
    isPgaService: (state) => state.selectedService === 'pga',
  },

  actions: {
    // Initialize store from localStorage (call this on app mount if needed)
    initializeFromStorage() {
      this.user = getFromLocalStorage('user', true)
      this.token = getFromLocalStorage('token')
      this.tokenExpiryTime = getFromLocalStorage('tokenExpiryTime')
      this.selectedService = getFromLocalStorage('selectedService')

      console.log('[AUTH] Initialized from storage:', {
        hasToken: !!this.token,
        hasUser: !!this.user,
        userRole: this.user?.role,
        rawToken: this.token?.substring(0, 30) + '...'
      })

      // Clean up corrupted data
      this.cleanupCorruptedData()

      // Start token check if token exists
      if (this.token && this.tokenExpiryTime) {
        this.startTokenCheck()
      }
    },

    // Clean up corrupted localStorage data
    cleanupCorruptedData() {
      const token = localStorage.getItem('token')
      const user = localStorage.getItem('user')

      // Remove "undefined" string values
      if (token === 'undefined' || token === 'null') {
        console.warn('[AUTH] Removing corrupted token from localStorage')
        localStorage.removeItem('token')
      }

      if (user === 'undefined' || user === 'null') {
        console.warn('[AUTH] Removing corrupted user from localStorage')
        localStorage.removeItem('user')
      }

      // Also clear token expiry
      const expiry = localStorage.getItem('tokenExpiryTime')
      if (expiry === 'undefined' || expiry === 'null') {
        localStorage.removeItem('tokenExpiryTime')
      }
    },
    async login(identity, password) {
      this.loading = true
      try {
        const response = await api.post('/login', { identity, password })

        // Update store state
        this.token = response.data.token
        this.user = response.data.user

        // Save to localStorage
        localStorage.setItem('token', this.token)
        localStorage.setItem('user', JSON.stringify(this.user))

        // Set token expiry (default 3 hours from now)
        const expiryTime = new Date(Date.now() + 3 * 60 * 60 * 1000) // 3 hours
        this.tokenExpiryTime = expiryTime.toISOString()
        localStorage.setItem('tokenExpiryTime', this.tokenExpiryTime)

        console.log('[AUTH] Login successful:', {
          token: this.token ? 'exists' : 'missing',
          user: this.user,
          role: this.user?.role,
          isAuthenticated: !!this.token
        })

        // Verify storage
        console.log('[AUTH] Verification:', {
          localStorageToken: localStorage.getItem('token')?.substring(0, 20) + '...',
          storeToken: this.token?.substring(0, 20) + '...'
        })

        // Start token check interval
        this.startTokenCheck()

        return response.data
      } catch (error) {
        console.error('[AUTH] Login failed:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async logout() {
      try {
        await api.post('/logout')
      } catch (error) {
        console.error('Logout error:', error)
      } finally {
        this.stopTokenCheck()
        this.token = null
        this.user = null
        this.tokenExpiryTime = null
        this.showSessionWarning = false
        this.clearService()
        localStorage.removeItem('token')
        localStorage.removeItem('user')
        localStorage.removeItem('tokenExpiryTime')
      }
    },

    async fetchUser() {
      try {
        const response = await api.get('/me')
        this.user = response.data
        localStorage.setItem('user', JSON.stringify(this.user))
      } catch (error) {
        this.logout()
      }
    },

    // Start checking token validity
    startTokenCheck() {
      this.stopTokenCheck() // Clear existing interval first

      tokenCheckInterval = setInterval(() => {
        const minutesLeft = this.tokenExpiryMinutes
        if (minutesLeft === null) return

        // Show warning when 5 minutes remaining
        if (minutesLeft <= 5 && minutesLeft > 0 && !this.showSessionWarning) {
          this.showSessionWarning = true
        }

        // Auto-logout when token expired
        if (minutesLeft <= 0) {
          this.stopTokenCheck()
          this.logout()
          // Redirect will be handled by API interceptor
        }
      }, 30000) // Check every 30 seconds
    },

    // Stop token check interval
    stopTokenCheck() {
      if (tokenCheckInterval) {
        clearInterval(tokenCheckInterval)
        tokenCheckInterval = null
      }
    },

    // Extend token expiry (call this when user is active)
    extendToken() {
      if (!this.tokenExpiryTime) return

      const now = new Date()
      const expiry = new Date(this.tokenExpiryTime)

      // Extend if less than 1 hour remaining
      if (expiry - now < 60 * 60 * 1000) {
        const newExpiry = new Date(Date.now() + 3 * 60 * 60 * 1000)
        this.tokenExpiryTime = newExpiry.toISOString()
        localStorage.setItem('tokenExpiryTime', this.tokenExpiryTime)
        this.showSessionWarning = false
      }
    },

    // Set selected service
    setService(service) {
      this.selectedService = service
      localStorage.setItem('selectedService', service)
    },

    // Clear selected service (on logout)
    clearService() {
      this.selectedService = null
      localStorage.removeItem('selectedService')
    },
  },
})
