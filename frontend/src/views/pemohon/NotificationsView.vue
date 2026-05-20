<script setup>
import { ref, onMounted } from 'vue'
import { useNotificationStore } from '@/stores/notification'
import { useRouter } from 'vue-router'
import MainLayout from '@/components/layout/MainLayout.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import NotificationBell from '@/components/NotificationBell.vue'

const router = useRouter()
const notificationStore = useNotificationStore()

const loading = ref(false)

async function loadNotifications() {
  loading.value = true
  await notificationStore.fetchNotifications()
  loading.value = false
}

async function handleNotificationClick(notification) {
  if (!notification.is_read) {
    await notificationStore.markAsRead(notification.id)
  }
  if (notification.pengajuan_id) {
    router.push(`/pengajuan/${notification.pengajuan_id}`)
  }
}

async function markAllRead() {
  await notificationStore.markAllAsRead()
}

async function deleteNotification(id, event) {
  event.stopPropagation()
  if (confirm('Hapus notifikasi ini?')) {
    await notificationStore.deleteNotification(id)
  }
}

function getNotificationIcon(type) {
  const icons = {
    info: '🔵',
    warning: '⚠️',
    success: '✅',
    error: '❌'
  }
  return icons[type] || '🔔'
}

function getNotificationColor(type) {
  const colors = {
    info: 'border-blue-300 bg-blue-50',
    warning: 'border-yellow-300 bg-yellow-50',
    success: 'border-green-300 bg-green-50',
    error: 'border-red-300 bg-red-50'
  }
  return colors[type] || 'border-gray-300 bg-gray-50'
}

function getTimeAgo(dateString) {
  const date = new Date(dateString)
  const now = new Date()
  const seconds = Math.floor((now - date) / 1000)

  if (seconds < 60) return 'Baru saja'
  if (seconds < 3600) return `${Math.floor(seconds / 60)} menit lalu`
  if (seconds < 86400) return `${Math.floor(seconds / 3600)} jam lalu`
  if (seconds < 604800) return `${Math.floor(seconds / 86400)} hari lalu`
  return date.toLocaleDateString('id-ID')
}

onMounted(() => {
  loadNotifications()
})
</script>

<template>
  <MainLayout>
    <Breadcrumb :current-page="'Notifikasi'" />
    <div class="mb-3 flex justify-between items-center">
      <h2 class="text-lg font-bold text-gray-900">Notifikasi</h2>
      <button
        v-if="notificationStore.unreadCount > 0"
        @click="markAllRead"
        class="text-xs text-blue-600 hover:text-blue-800"
      >
        Tandai semua dibaca ({{ notificationStore.unreadCount }})
          </button>
        </div>

        <div class="card p-3">
          <div v-if="loading" class="flex items-center justify-center py-8">
            <LoadingSpinner size="md" text="Memuat..." />
          </div>

          <div v-else-if="notificationStore.notifications.length === 0" class="text-center py-8 text-gray-500">
            <p class="text-sm">Tidak ada notifikasi</p>
          </div>

          <div v-else class="space-y-2">
            <div
              v-for="notification in notificationStore.notifications"
              :key="notification.id"
              @click="handleNotificationClick(notification)"
              class="p-3 border rounded cursor-pointer transition-colors"
              :class="[
                getNotificationColor(notification.type),
                notification.is_read ? 'opacity-70' : 'border-l-4'
              ]"
            >
              <div class="flex items-start justify-between">
                <div class="flex items-start space-x-2 flex-1">
                  <span class="text-sm flex-shrink-0">{{ getNotificationIcon(notification.type) }}</span>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900">{{ notification.title }}</p>
                    <p class="text-sm text-gray-700 mt-1">{{ notification.message }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ getTimeAgo(notification.created_at) }}</p>
                  </div>
                </div>
                <button
                  @click="deleteNotification(notification.id, $event)"
                  class="ml-2 flex-shrink-0 text-gray-400 hover:text-red-500 p-1"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            </div>
        </div>
      </div>
  </MainLayout>
</template>
