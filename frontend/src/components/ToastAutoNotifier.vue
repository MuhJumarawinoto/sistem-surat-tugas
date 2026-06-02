<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useNotificationStore } from '@/stores/notification'
import { useToastStore } from '@/stores/toast'

const notificationStore = useNotificationStore()
const toast = useToastStore()

const processedNotifications = ref(new Set())
let pollingInterval = null
let lastUnreadCount = 0
const isPolling = ref(false)

async function showPendingNotifications() {
  // Prevent concurrent polling
  if (isPolling.value) return

  isPolling.value = true
  try {
    // Use fetchUnreadCount instead - much lighter query
    const currentCount = await notificationStore.fetchUnreadCount()

    // Only fetch full notifications if count increased
    if (currentCount > lastUnreadCount) {
      const notifications = await notificationStore.fetchUnreadNotifications()

      // Filter important new notifications (warning, error, info)
      const importantNotifications = notifications.filter(n =>
        !processedNotifications.value.has(n.id) &&
        (n.type === 'warning' || n.type === 'error' || n.type === 'info')
      )

      if (importantNotifications.length > 0) {
        // Show toast for each important notification (max 3)
        const toShow = importantNotifications.slice(0, 3)
        toShow.forEach((notif, index) => {
          setTimeout(() => {
            const duration = notif.type === 'error' ? 8000 : 6000
            const toastFn = toast[notif.type] || toast.info
            toastFn(`${notif.title}: ${notif.message}`, duration)
            processedNotifications.value.add(notif.id)
          }, index * 1500)
        })
      }
    }

    lastUnreadCount = currentCount
  } catch (error) {
    // Silently fail - don't spam console
  } finally {
    isPolling.value = false
  }
}

function startPolling() {
  // Poll every 2 minutes (reduced from 90s to reduce server load & errors)
  pollingInterval = setInterval(() => {
    showPendingNotifications()
  }, 120000)
}

function stopPolling() {
  if (pollingInterval) {
    clearInterval(pollingInterval)
    pollingInterval = null
  }
}

onMounted(async () => {
  // Initial check after 5 seconds (let page fully load first)
  setTimeout(() => {
    showPendingNotifications()
  }, 5000)

  startPolling()
})

onUnmounted(() => {
  stopPolling()
})
</script>

<template>
  <!-- This component doesn't render anything visible -->
  <!-- It just shows toast notifications automatically -->
</template>
