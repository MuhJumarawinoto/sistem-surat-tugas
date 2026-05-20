import { defineStore } from 'pinia'
import api from '@/services/api'

export const useNotificationStore = defineStore('notification', {
  state: () => ({
    notifications: [],
    unreadCount: 0,
    loading: false
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
      try {
        const response = await api.get('/notifications/unread')
        return response.data || []
      } catch (error) {
        console.error('Failed to fetch unread notifications:', error)
        return []
      }
    },

    async fetchUnreadCount() {
      try {
        const response = await api.get('/notifications/unread-count')
        this.unreadCount = response.data.count || 0
        return this.unreadCount
      } catch (error) {
        console.error('Failed to fetch unread count:', error)
        return 0
      }
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
      } catch (error) {
        console.error('Failed to delete notification:', error)
      }
    }
  }
})
