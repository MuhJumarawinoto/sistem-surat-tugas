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

const typeClasses = computed(() => {
  const classes = {
    success: 'bg-white border-l-4 border-green-500 text-green-800',
    error: 'bg-white border-l-4 border-red-500 text-red-800',
    warning: 'bg-white border-l-4 border-amber-500 text-amber-800',
    info: 'bg-white border-l-4 border-blue-500 text-blue-800'
  }
  return classes[props.type]
})

const iconClasses = computed(() => {
  const classes = {
    success: 'ri-checkbox-circle-fill text-green-500',
    error: 'ri-close-circle-fill text-red-500',
    warning: 'ri-error-warning-fill text-amber-500',
    info: 'ri-information-fill text-blue-500'
  }
  return classes[props.type]
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
        class="fixed top-20 right-4 z-50 max-w-sm w-full shadow-lg rounded-lg p-4 flex items-start gap-3"
        :class="typeClasses"
      >
        <i :class="[iconClasses, 'text-xl flex-shrink-0 mt-0.5']"></i>
        <div class="flex-1">
          <p class="text-sm font-medium">{{ message }}</p>
        </div>
        <button
          @click="emit('close')"
          class="flex-shrink-0 text-secondary-400 hover:text-secondary-600 transition-colors"
        >
          <i class="ri-close-line text-lg"></i>
        </button>
      </div>
    </Transition>
  </Teleport>
</template>
