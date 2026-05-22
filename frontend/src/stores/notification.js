import { defineStore } from 'pinia'
import api, { apiQuick } from '@/services/api'

// Cache configuration
const UNREAD_COUNT_CACHE_TTL = 30000 // 30 seconds
const UNREAD_LIST_CACHE_TTL = 60000 // 1 minute

export const useNotificationStore = defineStore('notification', {
  state: () => ({
    notifications: [],
    unreadCount: 0,
    loading: false,
    _unreadCountCache: null,
    _unreadCountCacheTime: 0,
    _unreadListCache: null,
    _unreadListCacheTime: 0
  }),

  actions: {
    async fetchNotifications() {
      this.loading = true
      try {
        const response = await api.get('/notifications')
        this.notifications = response.data.data || []
        this.unreadCount = response.data.unread_count || 0
      } catch (error) {
        console.error('Failed to fetch notifications:', error)
      } finally {
        this.loading = false
      }
    },

    async fetchUnreadNotifications() {
      // Check cache first
      const now = Date.now()
      if (this._unreadListCache &&
          (now - this._unreadListCacheTime) < UNREAD_LIST_CACHE_TTL) {
        return this._unreadListCache
      }

      try {
        const response = await apiQuick.get('/notifications/unread')
        const data = response.data || []
        this._unreadListCache = data
        this._unreadListCacheTime = now
        return data
      } catch (error) {
        // Return cached data if available, even if expired
        if (this._unreadListCache) {
          return this._unreadListCache
        }
        console.error('Failed to fetch unread notifications:', error)
        return []
      }
    },

    async fetchUnreadCount() {
      // Check cache first
      const now = Date.now()
      if (this._unreadCountCache !== null &&
          (now - this._unreadCountCacheTime) < UNREAD_COUNT_CACHE_TTL) {
        return this._unreadCountCache
      }

      try {
        const response = await apiQuick.get('/notifications/unread-count')
        this.unreadCount = response.data.count || 0
        this._unreadCountCache = this.unreadCount
        this._unreadCountCacheTime = now
        return this.unreadCount
      } catch (error) {
        // Return cached count if available, even if expired
        if (this._unreadCountCache !== null) {
          return this._unreadCountCache
        }
        console.error('Failed to fetch unread count:', error)
        return 0
      }
    },

    // Invalidate cache (call after marking as read, etc.)
    invalidateCache() {
      this._unreadCountCache = null
      this._unreadListCache = null
    },

    async markAsRead(id) {
      try {
        await api.patch(`/notifications/${id}/read`)
        const index = this.notifications.findIndex(n => n.id === id)
        if (index !== -1) {
          this.notifications[index].is_read = true
          this.notifications[index].read_at = new Date().toISOString()
        }
        this.unreadCount = Math.max(0, this.unreadCount - 1)
        this.invalidateCache()
      } catch (error) {
        console.error('Failed to mark notification as read:', error)
      }
    },

    async markAllAsRead() {
      try {
        await api.post('/notifications/mark-all-read')
        this.notifications.forEach(n => {
          n.is_read = true
          n.read_at = new Date().toISOString()
        })
        this.unreadCount = 0
        this.invalidateCache()
      } catch (error) {
        console.error('Failed to mark all notifications as read:', error)
      }
    },

    async deleteNotification(id) {
      try {
        await api.delete(`/notifications/${id}`)
        const index = this.notifications.findIndex(n => n.id === id)
        if (index !== -1) {
          const wasUnread = !this.notifications[index].is_read
          this.notifications.splice(index, 1)
          if (wasUnread) {
            this.unreadCount = Math.max(0, this.unreadCount - 1)
          }
        }
        this.invalidateCache()
      } catch (error) {
        console.error('Failed to delete notification:', error)
      }
    }
  }
})
