<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/stores/notification'
import api from '@/services/api'
import MainLayout from '@/components/layout/MainLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import { useToastStore } from '@/stores/toast'

const router = useRouter()
const notificationStore = useNotificationStore()
const toast = useToastStore()

const loading = ref(false)
const messages = ref([])
const filter = ref('all') // all, unread, approval

const headerActions = computed(() => [
  {
    label: 'Tandai Semua Dibaca',
    icon: 'ri-check-double-line',
    onClick: markAllRead,
    variant: 'btn-secondary',
    show: notificationStore.unreadCount > 0
  },
  {
    label: 'Refresh',
    icon: 'ri-refresh-line',
    onClick: loadMessages,
    variant: 'btn-ghost'
  }
])

async function loadMessages() {
  loading.value = true
  try {
    const response = await api.get('/notifications/all-messages')
    messages.value = response.data.data || []
    // Also update the store's unread count
    notificationStore.unreadCount = response.data.unread_count || 0
  } catch (error) {
    toast.error('Gagal memuat pesan')
  } finally {
    loading.value = false
  }
}

async function markAllRead() {
  try {
    await notificationStore.markAllAsRead()
    await loadMessages() // Reload to update UI
    toast.success('Semua notifikasi ditandai dibaca')
  } catch (error) {
    toast.error('Gagal menandai notifikasi')
  }
}

async function handleMessageClick(message) {
  // Mark notification as read if it's unread
  if (message.type === 'notification' && !message.is_read) {
    const notifId = message.id.replace('notif_', '')
    try {
      await notificationStore.markAsRead(notifId)
      // Update local state
      message.is_read = true
      await notificationStore.fetchUnreadCount()
    } catch (error) {
      console.error('Failed to mark as read:', error)
    }
  }

  // Navigate to edit pengajuan if available, with highlight info
  if (message.pengajuan_id) {
    router.push({
      path: `/pengajuan/${message.pengajuan_id}/edit`,
      state: {
        highlight: message.type,
        highlightId: message.type === 'document' ? message.id.replace('document_', '') : null
      }
    })
  }
}

function getMessageIcon(message) {
  if (message.type === 'approval') {
    return { icon: 'ri-message-3-line', color: 'text-purple-500', bg: 'bg-purple-100' }
  }
  if (message.type === 'document') {
    return { icon: 'ri-file-text-line', color: 'text-orange-500', bg: 'bg-orange-100' }
  }

  const icons = {
    info: { icon: 'ri-information-line', color: 'text-blue-500', bg: 'bg-blue-100' },
    warning: { icon: 'ri-alert-line', color: 'text-amber-500', bg: 'bg-amber-100' },
    success: { icon: 'ri-checkbox-circle-line', color: 'text-green-500', bg: 'bg-green-100' },
    error: { icon: 'ri-error-warning-line', color: 'text-red-500', bg: 'bg-red-100' }
  }
  return icons[message.notif_type] || { icon: 'ri-notification-line', color: 'text-secondary-500', bg: 'bg-secondary-100' }
}

function getTimeAgo(dateString) {
  const date = new Date(dateString)
  const now = new Date()
  const seconds = Math.floor((now - date) / 1000)

  if (seconds < 60) return 'Baru saja'
  if (seconds < 3600) return `${Math.floor(seconds / 60)} menit lalu`
  if (seconds < 86400) return `${Math.floor(seconds / 3600)} jam lalu`
  if (seconds < 604800) return `${Math.floor(seconds / 86400)} hari lalu`
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
}

function getTypeLabel(message) {
  if (message.type === 'approval') {
    return 'Catatan Admin'
  }
  if (message.type === 'document') {
    return 'Catatan Dokumen'
  }
  const labels = {
    info: 'Info',
    warning: 'Peringatan',
    success: 'Sukses',
    error: 'Penting'
  }
  return labels[message.notif_type] || 'Notifikasi'
}

const filteredMessages = computed(() => {
  let filtered = messages.value

  if (filter.value === 'unread') {
    filtered = filtered.filter(m => !m.is_read)
  } else if (filter.value === 'approval') {
    filtered = filtered.filter(m => m.type === 'approval')
  }

  return filtered
})

const unreadCount = computed(() => {
  return messages.value.filter(m => !m.is_read).length
})

onMounted(() => {
  loadMessages()
})
</script>

<template>
  <MainLayout>
    <PageHeader
      title="Pesan & Notifikasi"
      subtitle="Semua pesan dan notifikasi Anda"
      :actions="headerActions"
    />

    <div class="card">
      <div class="card-header">
        <div class="flex items-center justify-between flex-wrap gap-3">
          <div class="flex items-center gap-2">
            <button
              @click="filter = 'all'"
              :class="['px-3 py-1.5 rounded-lg text-sm font-medium transition-colors', filter === 'all' ? 'bg-primary-100 text-primary-700' : 'text-secondary-600 hover:bg-secondary-100']"
            >
              Semua
            </button>
            <button
              @click="filter = 'unread'"
              :class="['px-3 py-1.5 rounded-lg text-sm font-medium transition-colors', filter === 'unread' ? 'bg-primary-100 text-primary-700' : 'text-secondary-600 hover:bg-secondary-100']"
            >
              Belum Dibaca
              <span v-if="unreadCount > 0" class="ml-1 px-1.5 py-0.5 bg-primary-500 text-white text-xs rounded-full">
                {{ unreadCount }}
              </span>
            </button>
            <button
              @click="filter = 'approval'"
              :class="['px-3 py-1.5 rounded-lg text-sm font-medium transition-colors', filter === 'approval' ? 'bg-primary-100 text-primary-700' : 'text-secondary-600 hover:bg-secondary-100']"
            >
              Catatan Admin
            </button>
          </div>
          <span class="text-sm text-secondary-500">
            {{ filteredMessages.length }} pesan
          </span>
        </div>
      </div>

      <div class="card-body">
        <LoadingSpinner v-if="loading && messages.length === 0" />

        <div v-else-if="filteredMessages.length === 0" class="text-center py-12">
          <div class="w-16 h-16 rounded-full bg-secondary-100 flex items-center justify-center mx-auto mb-4">
            <i class="ri-notification-off-line text-3xl text-secondary-400"></i>
          </div>
          <p class="text-secondary-500">Tidak ada pesan</p>
        </div>

        <div v-else class="divide-y divide-secondary-100">
          <div
            v-for="message in filteredMessages"
            :key="message.id"
            @click="handleMessageClick(message)"
            class="p-4 hover:bg-secondary-50 cursor-pointer transition-colors"
            :class="{ 'bg-primary-50/30': !message.is_read }"
          >
            <div class="flex items-start gap-4">
              <div :class="['w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0', getMessageIcon(message).bg]">
                <i :class="[getMessageIcon(message).icon, 'text-lg', getMessageIcon(message).color]"></i>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2">
                  <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                      <span class="text-xs font-medium px-2 py-0.5 rounded-full" :class="[
                        message.type === 'approval' ? 'bg-purple-100 text-purple-700' :
                        message.type === 'document' ? 'bg-orange-100 text-orange-700' :
                        'bg-blue-100 text-blue-700'
                      ]">
                        {{ getTypeLabel(message) }}
                      </span>
                      <span v-if="message.pengajuan" class="text-xs text-secondary-400">
                        #{{ message.pengajuan.nomor_pengajuan }}
                      </span>
                    </div>
                    <p class="text-sm font-medium text-secondary-800">{{ message.title }}</p>
                    <p class="text-sm text-secondary-600 mt-0.5">{{ message.message }}</p>
                    <p class="text-xs text-secondary-400 mt-1 flex items-center gap-1">
                      <i class="ri-time-line"></i>
                      {{ getTimeAgo(message.created_at) }}
                      <span v-if="message.approver" class="ml-2 text-secondary-500">
                        • Oleh {{ message.approver?.name || 'Admin' }}
                      </span>
                    </p>
                  </div>
                  <div v-if="!message.is_read" class="flex-shrink-0">
                    <span class="w-2.5 h-2.5 bg-primary-500 rounded-full"></span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>
