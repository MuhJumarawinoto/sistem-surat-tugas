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

// Get all messages for panel display (unread first, then read)
const unreadMessages = computed(() => {
  if (!notificationStore.allMessages.length) return []

  // Prioritize unread messages, but show all up to 10
  const unread = notificationStore.allMessages.filter(m => !m.is_read)
  const read = notificationStore.allMessages.filter(m => m.is_read)

  // Show all unread first, then fill with read messages if less than 10
  return [...unread, ...read.slice(0, Math.max(0, 10 - unread.length))].slice(0, 10)
})

async function togglePanel() {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    await notificationStore.fetchAllMessages()
  }
}

async function handleNotificationClick(message) {
  // Only mark as read for actual notifications (not document/approval notes)
  if (!message.is_read && message.type === 'notification') {
    await notificationStore.markAsRead(message.id)
  }

  isOpen.value = false

  if (message.pengajuan_id) {
    // Determine highlight information based on message type
    let highlightType = null
    let highlightId = null

    if (message.type === 'document') {
      // Document verification note - highlight the document
      highlightType = 'document'
      // Extract document ID from message.data (which contains the DokumenPengajuan object)
      highlightId = message.data?.id || message.id?.replace('document_', '')
    } else if (message.type === 'approval') {
      // Approval history - could highlight the approval section
      highlightType = 'approval'
    }

    // Navigate with state for highlighting
    router.push({
      path: `/pengajuan/${message.pengajuan_id}`,
      query: highlightType ? { highlight: highlightType, highlightId: String(highlightId) } : {}
    }, {
      state: { highlight: highlightType, highlightId: highlightId ? String(highlightId) : null }
    })
  }
}

async function markAllRead() {
  await notificationStore.markAllAsRead()
}

function getNotificationIcon(type, notifType) {
  // Use notif_type if available, otherwise fall back to type
  const typeToUse = notifType || type

  const icons = {
    info: { icon: 'ri-information-line', color: 'text-info', bg: 'bg-blue-100' },
    warning: { icon: 'ri-alert-line', color: 'text-warning', bg: 'bg-amber-100' },
    success: { icon: 'ri-checkbox-circle-line', color: 'text-success', bg: 'bg-green-100' },
    error: { icon: 'ri-error-warning-line', color: 'text-danger', bg: 'bg-red-100' }
  }
  return icons[typeToUse] || { icon: 'ri-notification-line', color: 'text-secondary-500', bg: 'bg-secondary-100' }
}

function getMessageIcon(message) {
  // Custom icons based on message type
  if (message.type === 'document') {
    return { icon: 'ri-file-text-line', color: 'text-primary-600', bg: 'bg-primary-100' }
  } else if (message.type === 'approval') {
    return { icon: 'ri-user-follow-line', color: 'text-purple-600', bg: 'bg-purple-100' }
  }
  return getNotificationIcon('info', message.notif_type)
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
      class="relative btn btn-ghost btn-icon border border-secondary-300 hover:border-primary-400"
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

          <div v-else-if="unreadMessages.length === 0" class="p-8 text-center text-secondary-500">
            <div class="w-12 h-12 rounded-full bg-secondary-100 flex items-center justify-center mx-auto mb-3">
              <i class="ri-notification-off-line text-2xl text-secondary-400"></i>
            </div>
            <p class="text-sm">Tidak ada notifikasi terbaru</p>
          </div>

          <div v-else class="divide-y divide-secondary-100">
            <div
              v-for="message in unreadMessages"
              :key="message.id"
              @click="handleNotificationClick(message)"
              class="p-3 hover:bg-secondary-50 cursor-pointer transition-colors"
              :class="{ 'bg-primary-50/50': !message.is_read }"
            >
              <div class="flex items-start gap-3">
                <div :class="['w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0', getMessageIcon(message).bg]">
                  <i :class="[getMessageIcon(message).icon, 'text-sm', getMessageIcon(message).color]"></i>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-xs font-medium text-secondary-800 truncate">{{ message.title }}</p>
                  <p class="text-xs text-secondary-500 mt-0.5 truncate-2">{{ message.message }}</p>
                  <p class="text-xs text-secondary-400 mt-1 flex items-center gap-1">
                    <i class="ri-time-line"></i>
                    {{ getTimeAgo(message.created_at) }}
                  </p>
                </div>
                <div v-if="!message.is_read" class="flex-shrink-0">
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
