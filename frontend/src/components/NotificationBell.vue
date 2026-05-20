<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/stores/notification'

const router = useRouter()
const notificationStore = useNotificationStore()

const isOpen = ref(false)
const pollingInterval = ref(null)

const unreadCount = computed(() => notificationStore.unreadCount)
const hasUnread = computed(() => unreadCount.value > 0)

async function togglePanel() {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    await notificationStore.fetchNotifications()
  }
}

async function handleNotificationClick(notification) {
  if (!notification.is_read) {
    await notificationStore.markAsRead(notification.id)
  }
  if (notification.pengajuan_id) {
    router.push(`/pengajuan/${notification.pengajuan_id}`)
  }
  isOpen.value = false
}

async function markAllRead() {
  await notificationStore.markAllAsRead()
}

function getNotificationIcon(type) {
  const icons = {
    info: { icon: 'ri-information-line', color: 'text-info', bg: 'bg-blue-100' },
    warning: { icon: 'ri-alert-line', color: 'text-warning', bg: 'bg-amber-100' },
    success: { icon: 'ri-checkbox-circle-line', color: 'text-success', bg: 'bg-green-100' },
    error: { icon: 'ri-error-warning-line', color: 'text-danger', bg: 'bg-red-100' }
  }
  return icons[type] || { icon: 'ri-notification-line', color: 'text-secondary-500', bg: 'bg-secondary-100' }
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

// Poll for new notifications every 30 seconds
function startPolling() {
  pollingInterval.value = setInterval(() => {
    notificationStore.fetchUnreadCount()
  }, 30000)
}

function stopPolling() {
  if (pollingInterval.value) {
    clearInterval(pollingInterval.value)
    pollingInterval.value = null
  }
}

onMounted(() => {
  notificationStore.fetchUnreadCount()
  startPolling()
})

onUnmounted(() => {
  stopPolling()
})
</script>

<template>
  <div class="relative">
    <!-- Notification Bell -->
    <button
      @click="togglePanel"
      class="relative btn btn-ghost btn-icon"
      :class="{ 'text-primary-600': hasUnread, 'text-secondary-500': !hasUnread }"
    >
      <i class="ri-notification-3-line text-xl"></i>
      <span
        v-if="hasUnread"
        class="absolute -top-1 -right-1 w-5 h-5 bg-danger text-white text-xs rounded-full flex items-center justify-center font-medium"
      >
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>

    <!-- Notification Panel -->
    <Transition name="dropdown">
      <div
        v-if="isOpen"
        class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-xl shadow-soft border border-secondary-200 z-50 animate-slide-up"
        @click.outside="isOpen = false"
      >
        <!-- Header -->
        <div class="p-4 border-b border-secondary-100 flex items-center justify-between">
          <h3 class="text-sm font-semibold text-secondary-800 flex items-center gap-2">
            <i class="ri-notification-3-line text-primary-600"></i>
            Notifikasi
          </h3>
          <button
            v-if="hasUnread"
            @click.stop="markAllRead"
            class="text-xs text-primary-600 hover:text-primary-700 font-medium"
          >
            Tandai semua dibaca
          </button>
        </div>

        <!-- Content -->
        <div class="max-h-80 overflow-y-auto scrollbar-thin">
          <div v-if="notificationStore.loading" class="p-8 text-center text-secondary-500">
            <div class="loader-spinner mx-auto mb-2"></div>
            <p class="text-xs">Memuat notifikasi...</p>
          </div>

          <div v-else-if="notificationStore.notifications.length === 0" class="p-8 text-center text-secondary-500">
            <div class="w-12 h-12 rounded-full bg-secondary-100 flex items-center justify-center mx-auto mb-3">
              <i class="ri-notification-off-line text-2xl text-secondary-400"></i>
            </div>
            <p class="text-sm">Tidak ada notifikasi</p>
          </div>

          <div v-else class="divide-y divide-secondary-100">
            <div
              v-for="notification in notificationStore.notifications"
              :key="notification.id"
              @click="handleNotificationClick(notification)"
              class="p-3 hover:bg-secondary-50 cursor-pointer transition-colors"
              :class="{ 'bg-primary-50/50': !notification.is_read }"
            >
              <div class="flex items-start gap-3">
                <div :class="['w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0', getNotificationIcon(notification.type).bg]">
                  <i :class="[getNotificationIcon(notification.type).icon, 'text-sm', getNotificationIcon(notification.type).color]"></i>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-xs font-medium text-secondary-800 truncate">{{ notification.title }}</p>
                  <p class="text-xs text-secondary-500 mt-0.5 truncate-2">{{ notification.message }}</p>
                  <p class="text-xs text-secondary-400 mt-1 flex items-center gap-1">
                    <i class="ri-time-line"></i>
                    {{ getTimeAgo(notification.created_at) }}
                  </p>
                </div>
                <div v-if="!notification.is_read" class="flex-shrink-0">
                  <span class="w-2 h-2 bg-primary-500 rounded-full"></span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="p-3 border-t border-secondary-100 bg-secondary-50">
          <router-link
            to="/notifications"
            @click="isOpen = false"
            class="block text-center text-xs text-primary-600 hover:text-primary-700 font-medium py-1"
          >
            Lihat Semua Notifikasi
            <i class="ri-arrow-right-line ml-1"></i>
          </router-link>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.truncate-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
