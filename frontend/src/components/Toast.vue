<script setup>
import { computed } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  message: {
    type: String,
    required: true
  },
  type: {
    type: String,
    default: 'info',
    validator: (value) => ['success', 'error', 'warning', 'info'].includes(value)
  },
  duration: {
    type: Number,
    default: 3000
  }
})

const emit = defineEmits(['close'])

const toastStyles = computed(() => {
  const styles = {
    success: {
      container: 'bg-white border-l-4 border-green-500 shadow-lg shadow-green-500/10',
      icon: 'ri-checkbox-circle-fill text-green-500',
      iconBg: 'bg-green-100'
    },
    error: {
      container: 'bg-white border-l-4 border-red-500 shadow-lg shadow-red-500/10',
      icon: 'ri-close-circle-fill text-red-500',
      iconBg: 'bg-red-100'
    },
    warning: {
      container: 'bg-white border-l-4 border-amber-500 shadow-lg shadow-amber-500/10',
      icon: 'ri-error-warning-fill text-amber-500',
      iconBg: 'bg-amber-100'
    },
    info: {
      container: 'bg-white border-l-4 border-blue-500 shadow-lg shadow-blue-500/10',
      icon: 'ri-information-fill text-blue-500',
      iconBg: 'bg-blue-100'
    }
  }
  return styles[props.type] || styles.info
})

const progressWidth = computed(() => {
  return '100%'
})
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="transform translate-x-full opacity-0"
      enter-to-class="transform translate-x-0 opacity-100"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="transform translate-x-0 opacity-100"
      leave-to-class="transform translate-x-full opacity-0"
    >
      <div
        v-if="show"
        class="fixed top-4 right-4 sm:top-20 sm:right-4 z-[9999] min-w-[320px] max-w-md w-full rounded-xl overflow-hidden"
        :class="toastStyles.container"
      >
        <div class="flex items-start gap-3 p-4">
          <!-- Icon -->
          <div :class="['w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0', toastStyles.iconBg]">
            <i :class="[toastStyles.icon, 'text-xl']"></i>
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-secondary-800">{{ message }}</p>
          </div>

          <!-- Close Button -->
          <button
            @click="emit('close')"
            class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center text-secondary-400 hover:bg-secondary-100 hover:text-secondary-600 transition-all"
          >
            <i class="ri-close-line text-lg"></i>
          </button>
        </div>

        <!-- Progress Bar (auto-dismiss indicator) -->
        <div class="h-1 bg-secondary-100">
          <Transition
            enter-active-class="transition ease-out duration-[3000ms]"
            enter-from-class="w-0"
            enter-to-class="w-full"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
          >
            <div
              v-if="show"
              class="h-full"
              :class="{
                'bg-green-500': type === 'success',
                'bg-red-500': type === 'error',
                'bg-amber-500': type === 'warning',
                'bg-blue-500': type === 'info'
              }"
            ></div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
