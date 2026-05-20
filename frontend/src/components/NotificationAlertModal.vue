<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/stores/notification'

const router = useRouter()
const notificationStore = useNotificationStore()

const currentIndex = ref(0)
const showAlert = ref(false)
const unreadNotifications = ref([])

const currentNotification = computed(() => unreadNotifications.value[currentIndex.value])
const hasMore = computed(() => currentIndex.value < unreadNotifications.value.length - 1)

function getAlertIcon(type) {
  const icons = {
    info: {
      icon: 'ri-information-line',
      color: 'text-info',
      bg: 'bg-blue-100'
    },
    warning: {
      icon: 'ri-alert-line',
      color: 'text-warning',
      bg: 'bg-amber-100'
    },
    success: {
      icon: 'ri-checkbox-circle-line',
      color: 'text-success',
      bg: 'bg-green-100'
    },
    error: {
      icon: 'ri-error-warning-line',
      color: 'text-danger',
      bg: 'bg-red-100'
    }
  }
  return icons[type] || icons.info
}

function getAlertTitle(type) {
  const titles = {
    info: 'Informasi',
    warning: 'Peringatan',
    success: 'Informasi',
    error: 'Penting'
  }
  return titles[type] || 'Notifikasi'
}

async function loadUnreadNotifications() {
  try {
    const notifications = await notificationStore.fetchUnreadNotifications()
    unreadNotifications.value = notifications.filter(n =>
      n.type === 'warning' || n.type === 'error' || n.type === 'info'
    )

    if (unreadNotifications.value.length > 0) {
      showAlert.value = true
    }
  } catch (error) {
    console.error('Failed to load unread notifications:', error)
  }
}

async function handleMarkAsRead() {
  if (currentNotification.value) {
    await notificationStore.markAsRead(currentNotification.value.id)
  }
}

async function handleNext() {
  await handleMarkAsRead()

  if (hasMore.value) {
    currentIndex.value++
  } else {
    closeAlert()
  }
}

async function handleClose() {
  await handleMarkAsRead()
  closeAlert()
}

function closeAlert() {
  showAlert.value = false
}

function goToPengajuan() {
  if (currentNotification.value?.pengajuan_id) {
    handleClose()
    router.push(`/pengajuan/${currentNotification.value.pengajuan_id}`)
  }
}

onMounted(() => {
  loadUnreadNotifications()
})

defineExpose({
  loadUnreadNotifications
})
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="showAlert && currentNotification"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
      >
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="handleClose"></div>
        <div class="relative bg-white rounded-2xl shadow-soft max-w-md w-full overflow-hidden animate-slide-up">
          <!-- Header -->
          <div class="p-4 pb-2">
            <div class="flex items-start gap-3">
              <div :class="['w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0', getAlertIcon(currentNotification.type).bg]">
                <i :class="[getAlertIcon(currentNotification.type).icon, 'text-xl', getAlertIcon(currentNotification.type).color]"></i>
              </div>
              <div class="flex-1">
                <h3 class="text-sm font-bold text-secondary-800">{{ getAlertTitle(currentNotification.type) }}</h3>
                <p class="text-xs text-secondary-500 mt-0.5">{{ currentNotification.title }}</p>
              </div>
              <button
                @click="handleClose"
                class="btn btn-ghost btn-icon text-secondary-400 hover:text-secondary-600"
              >
                <i class="ri-close-line text-xl"></i>
              </button>
            </div>
          </div>

          <!-- Body -->
          <div class="px-4 py-3">
            <p class="text-sm text-secondary-700">{{ currentNotification.message }}</p>
            <p class="text-xs text-secondary-400 mt-2">
              {{ new Date(currentNotification.created_at).toLocaleDateString('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
              }) }}
            </p>
          </div>

          <!-- Footer -->
          <div class="px-4 py-3 bg-secondary-50 flex justify-between items-center">
            <span v-if="unreadNotifications.length > 1" class="text-xs text-secondary-500">
              {{ currentIndex + 1 }} dari {{ unreadNotifications.length }}
            </span>
            <span v-else></span>
            <div class="flex gap-2">
              <button
                v-if="currentNotification.pengajuan_id"
                @click="goToPengajuan"
                class="btn btn-secondary btn-sm"
              >
                <i class="ri-eye-line"></i>
                Lihat Pengajuan
              </button>
              <button
                @click="handleNext"
                class="btn btn-primary btn-sm"
              >
                {{ hasMore ? 'Selanjutnya' : 'Tutup' }}
                <i :class="hasMore ? 'ri-arrow-right-line' : 'ri-close-line'"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .relative,
.modal-leave-active .relative {
  transition: transform 0.3s ease;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
  transform: scale(0.9);
}
</style>
