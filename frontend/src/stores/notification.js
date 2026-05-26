import { defineStore } from 'pinia'
import api, { apiQuick } from '@/services/api'

// Cache configuration
const UNREAD_COUNT_CACHE_TTL = 30000 // 30 seconds
const UNREAD_LIST_CACHE_TTL = 60000 // 1 minute
const ALL_MESSAGES_CACHE_TTL = 60000 // 1 minute

export const useNotificationStore = defineStore('notification', {
  state: () => ({
    notifications: [],
    allMessages: [],
    unreadCount: 0,
    loading: false,
    _unreadCountCache: null,
    _unreadCountCacheTime: 0,
    _unreadListCache: null,
    _unreadListCacheTime: 0,
    _allMessagesCache: null,
    _allMessagesCacheTime: 0
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

    async fetchAllMessages() {
      // Check cache first
      const now = Date.now()
      if (this._allMessagesCache &&
          (now - this._allMessagesCacheTime) < ALL_MESSAGES_CACHE_TTL) {
        this.allMessages = this._allMessagesCache
        return this._allMessagesCache
      }

      this.loading = true
      try {
        const response = await api.get('/notifications/all-messages')
        this.allMessages = response.data.data || []
        this._allMessagesCache = this.allMessages
        this._allMessagesCacheTime = now
        return this.allMessages
      } catch (error) {
        console.error('Failed to fetch all messages:', error)
        // Return cached data if available
        if (this._allMessagesCache) {
          this.allMessages = this._allMessagesCache
          return this._allMessagesCache
        }
        this.allMessages = []
        return []
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
        const response = await apiQuick.get('/notifications/unread-count', {
          timeout: 3000 // Shorter timeout, fail fast
        })
        this.unreadCount = response.data.count || 0
        this._unreadCountCache = this.unreadCount
        this._unreadCountCacheTime = now
        return this.unreadCount
      } catch (error) {
        // Return cached count if available, even if expired
        if (this._unreadCountCache !== null) {
          return this._unreadCountCache
        }
        // Silent fail - don't spam console
        return 0
      }
    },

    // Invalidate cache (call after marking as read, etc.)
    invalidateCache() {
      this._unreadCountCache = null
      this._unreadListCache = null
      this._allMessagesCache = null
    },

    async markAsRead(id) {
      try {
        // Handle different ID formats (notif_x, approval_x, document_x)
        const originalId = id
        if (id.startsWith('notif_')) {
          id = id.replace('notif_', '')
          await api.patch(`/notifications/${id}/read`)
        }

        // Update local state
        const notifIndex = this.notifications.findIndex(n => n.id === originalId)
        if (notifIndex !== -1) {
          this.notifications[notifIndex].is_read = true
          this.notifications[notifIndex].read_at = new Date().toISOString()
        }

        const msgIndex = this.allMessages.findIndex(m => m.id === originalId)
        if (msgIndex !== -1) {
          this.allMessages[msgIndex].is_read = true
          this.allMessages[msgIndex].read_at = new Date().toISOString()
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
        this.allMessages.forEach(m => {
          if (m.type === 'notification') {
            m.is_read = true
            m.read_at = new Date().toISOString()
          }
        })
        this.unreadCount = 0
        this.invalidateCache()
      } catch (error) {
        console.error('Failed to mark all notifications as read:', error)
      }
    },

    async deleteNotification(id) {
      try {
        // Handle different ID formats
        const originalId = id
        if (id.startsWith('notif_')) {
          id = id.replace('notif_', '')
          await api.delete(`/notifications/${id}`)
        }

        const index = this.notifications.findIndex(n => n.id === originalId)
        if (index !== -1) {
          const wasUnread = !this.notifications[index].is_read
          this.notifications.splice(index, 1)
          if (wasUnread) {
            this.unreadCount = Math.max(0, this.unreadCount - 1)
          }
        }

        const msgIndex = this.allMessages.findIndex(m => m.id === originalId)
        if (msgIndex !== -1) {
          const wasUnread = !this.allMessages[msgIndex].is_read
          this.allMessages.splice(msgIndex, 1)
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
