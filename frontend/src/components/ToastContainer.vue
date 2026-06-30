<script setup>
import { useToastStore } from '@/stores/toast'
import Toast from './Toast.vue'

const toastStore = useToastStore()
</script>

<template>
  <div class="toast-container">
    <Toast
      v-for="toast in toastStore.toasts"
      :key="toast.id"
      :show="true"
      :message="toast.message"
      :type="toast.type"
      :action="toast.action"
      @close="toastStore.remove(toast.id)"
    />
  </div>
</template>

<style scoped>
.toast-container {
  position: fixed;
  top: 80px;
  right: 16px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 8px;
  pointer-events: none;
}

.toast-container > * {
  pointer-events: auto;
}
</style>
