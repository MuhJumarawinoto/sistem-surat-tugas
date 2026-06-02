<script setup>
import { computed, watch, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

// Track user activity
let activityTimeout = null

function resetActivityTimer() {
  clearTimeout(activityTimeout)
  // Consider inactive after 5 minutes of no activity
  activityTimeout = setTimeout(() => {
    // User inactive, do nothing (token will expire naturally)
  }, 5 * 60 * 1000)
}

function handleUserActivity() {
  resetActivityTimer()
  // Extend token if user is active
  authStore.extendToken()
}

// Setup activity listeners
onMounted(() => {
  const events = ['mousedown', 'keydown', 'scroll', 'touchstart']
  events.forEach(event => {
    document.addEventListener(event, handleUserActivity, { passive: true })
  })
  resetActivityTimer()
})

onUnmounted(() => {
  const events = ['mousedown', 'keydown', 'scroll', 'touchstart']
  events.forEach(event => {
    document.removeEventListener(event, handleUserActivity)
  })
  clearTimeout(activityTimeout)
})

// Computed
const minutesLeft = computed(() => authStore.tokenExpiryMinutes)
const showWarning = computed(() => authStore.showSessionWarning && minutesLeft.value !== null)

const warningMessage = computed(() => {
  if (minutesLeft.value === null) return ''
  if (minutesLeft.value <= 1) {
    return `Sesi Anda akan berakhir dalam kurang dari 1 menit. Silakan simpan pekerjaan Anda.`
  }
  return `Sesi Anda akan berakhir dalam ${minutesLeft.value} menit.`
})

// Format time remaining
const formatTime = (minutes) => {
  if (minutes < 1) return 'Kurang dari 1 menit'
  if (minutes === 1) return '1 menit'
  return `${minutes} menit`
}
</script>

<template>
  <Transition name="slide-up">
    <div
      v-if="showWarning"
      class="fixed bottom-4 right-4 z-50 max-w-sm"
    >
      <div class="card border-l-4 border-amber-500 shadow-lg">
        <div class="card-body py-3 px-4">
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
              <i class="ri-time-line text-amber-600 text-lg"></i>
            </div>
            <div class="flex-1">
              <p class="font-semibold text-amber-800 text-sm">Sesi Segera Berakhir</p>
              <p class="text-amber-700 text-xs mt-1">{{ warningMessage }}</p>
            </div>
            <button
              @click="authStore.showSessionWarning = false"
              class="text-amber-500 hover:text-amber-700"
            >
              <i class="ri-close-line text-lg"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(20px);
}
</style>
