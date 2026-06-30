import { ref, computed } from 'vue'
import { defineStore } from 'pinia'

export const useToastStore = defineStore('toast', () => {
  const toasts = ref([])
  let toastIdCounter = 0

  function show(message, type = 'info', duration = 3000, action = null) {
    const id = ++toastIdCounter
    const toast = {
      id,
      message,
      type,
      duration,
      action // action object: { label: string, onClick: function }
    }
    toasts.value.push(toast)

    if (duration > 0) {
      setTimeout(() => {
        remove(id)
      }, duration)
    }

    return id
  }

  function remove(id) {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index !== -1) {
      toasts.value.splice(index, 1)
    }
  }

  function success(message, duration, action) {
    return show(message, 'success', duration, action)
  }

  function error(message, duration, action) {
    return show(message, 'error', duration, action)
  }

  function warning(message, duration, action) {
    return show(message, 'warning', duration, action)
  }

  function info(message, duration, action) {
    return show(message, 'info', duration, action)
  }

  function clear() {
    toasts.value = []
  }

  return {
    toasts: computed(() => toasts.value),
    show,
    remove,
    success,
    error,
    warning,
    info,
    clear
  }
})
