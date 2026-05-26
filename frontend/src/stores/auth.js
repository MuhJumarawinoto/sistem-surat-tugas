import { defineStore } from 'pinia'
import api from '@/services/api'

let tokenCheckInterval = null

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user')) || null,
    token: localStorage.getItem('token') || null,
    tokenExpiryTime: localStorage.getItem('tokenExpiryTime') || null,
    loading: false,
    showSessionWarning: false,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    userRole: (state) => state.user?.role || null,
    isPemohon: (state) => state.user?.role === 'pemohon',
    isAtasan: (state) => state.user?.role === 'atasan',
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
  },

  actions: {
    async login(identity, password) {
      this.loading = true
      try {
        const response = await api.post('/login', { identity, password })
        this.token = response.data.token
        this.user = response.data.user
        localStorage.setItem('token', this.token)
        localStorage.setItem('user', JSON.stringify(this.user))

        // Set token expiry (default 3 hours from now)
        const expiryTime = new Date(Date.now() + 3 * 60 * 60 * 1000) // 3 hours
        this.tokenExpiryTime = expiryTime.toISOString()
        localStorage.setItem('tokenExpiryTime', this.tokenExpiryTime)

        // Start token check interval
        this.startTokenCheck()

        return response.data
      } catch (error) {
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
  },
})
