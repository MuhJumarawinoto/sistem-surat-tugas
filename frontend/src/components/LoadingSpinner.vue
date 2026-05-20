<script setup>
import { computed } from 'vue'

const props = defineProps({
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg', 'xl'].includes(value)
  },
  color: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'white', 'success', 'danger', 'secondary'].includes(value)
  },
  text: {
    type: String,
    default: ''
  },
  type: {
    type: String,
    default: 'spin',
    validator: (value) => ['spin', 'progress', 'dots', 'pulse'].includes(value)
  },
  progress: {
    type: Number,
    default: 0,
    validator: (value) => value >= 0 && value <= 100
  },
  showPercent: {
    type: Boolean,
    default: false
  },
  indeterminate: {
    type: Boolean,
    default: false
  }
})

const sizeClasses = {
  sm: { spin: 'w-4 h-4', progress: 'w-8 h-8', dots: 'scale-75', pulse: 'w-8 h-8' },
  md: { spin: 'w-8 h-8', progress: 'w-16 h-16', dots: 'scale-100', pulse: 'w-12 h-12' },
  lg: { spin: 'w-12 h-12', progress: 'w-24 h-24', dots: 'scale-125', pulse: 'w-16 h-16' },
  xl: { spin: 'w-16 h-16', progress: 'w-32 h-32', dots: 'scale-150', pulse: 'w-20 h-20' }
}

const colorClasses = {
  primary: { stroke: '#A39700', text: 'text-primary-600', bg: 'bg-primary-600' },
  white: { stroke: '#ffffff', text: 'text-white', bg: 'bg-white' },
  success: { stroke: '#22c55e', text: 'text-success', bg: 'bg-success' },
  danger: { stroke: '#ef4444', text: 'text-danger', bg: 'bg-danger' },
  secondary: { stroke: '#57534e', text: 'text-secondary-600', bg: 'bg-secondary-600' }
}

const strokeWidth = {
  sm: 3,
  md: 4,
  lg: 5,
  xl: 6
}

const radius = computed(() => {
  const r = { sm: 14, md: 28, lg: 42, xl: 56 }
  return r[props.size] || r.md
})

const circumference = computed(() => 2 * Math.PI * radius.value)

const offset = computed(() => {
  if (props.indeterminate) return circumference.value * 0.75
  return circumference.value - (props.progress / 100) * circumference.value
})

const displaySize = computed(() => sizeClasses[props.size][props.type] || sizeClasses[props.size].spin)
const displayColor = computed(() => colorClasses[props.color] || colorClasses.primary)
</script>

<template>
  <div class="inline-flex flex-col items-center justify-center">
    <!-- Spin Type (Default) -->
    <div
      v-if="type === 'spin'"
      :class="[displaySize]"
      class="loader-spinner"
    ></div>

    <!-- Progress Ring Type -->
    <div v-else-if="type === 'progress'" class="relative">
      <svg
        :class="[displaySize]"
        class="transform -rotate-90"
        xmlns="http://www.w3.org/2000/svg"
      >
        <!-- Background Circle -->
        <circle
          :cx="radius"
          :cy="radius"
          :r="radius"
          fill="none"
          :stroke="displayColor.stroke"
          stroke-opacity="0.2"
          :stroke-width="strokeWidth[size]"
        />
        <!-- Progress Circle -->
        <circle
          :cx="radius"
          :cy="radius"
          :r="radius"
          fill="none"
          :stroke="displayColor.stroke"
          :stroke-width="strokeWidth[size]"
          stroke-linecap="round"
          class="transition-all duration-350 ease-out"
          :class="{ 'animate-progress-dash': indeterminate }"
          :stroke-dasharray="circumference"
          :stroke-dashoffset="offset"
        />
      </svg>
      <!-- Percentage Text in Center -->
      <div
        v-if="showPercent || indeterminate"
        class="absolute inset-0 flex items-center justify-center"
      >
        <span :class="['text-sm font-semibold', displayColor.text]">
          {{ indeterminate ? '...' : `${progress}%` }}
        </span>
      </div>
    </div>

    <!-- Dots Type -->
    <div v-else-if="type === 'dots'" class="flex items-center justify-center space-x-1">
      <div
        v-for="i in 3"
        :key="i"
        :class="[sizeClasses[size].dots, displayColor.bg]"
        class="rounded-full animate-bounce"
        :style="{ animationDelay: `${i * 150}ms` }"
      />
    </div>

    <!-- Pulse Type -->
    <div v-else-if="type === 'pulse'" class="relative">
      <div
        v-for="i in 2"
        :key="i"
        :class="[displaySize, displayColor.bg]"
        class="absolute rounded-full animate-ping opacity-20"
        :style="{ animationDelay: `${i * 200}ms` }"
      />
      <div
        :class="[displaySize, displayColor.bg]"
        class="relative rounded-full"
      />
    </div>

    <!-- Text Below -->
    <p v-if="text && type !== 'progress'" class="mt-2 text-sm text-secondary-600">{{ text }}</p>
    <p v-if="text && type === 'progress'" class="mt-3 text-sm text-secondary-600">{{ text }}</p>

    <!-- Additional Progress Text (for progress type) -->
    <p v-if="type === 'progress' && !indeterminate && showPercent" class="text-xs font-medium text-secondary-500">
      {{ progress }}% Selesai
    </p>
  </div>
</template>

<style scoped>
@keyframes progress-dash {
  0% {
    stroke-dashoffset: var(--circumference);
  }
  50% {
    stroke-dashoffset: calc(var(--circumference) * 0.25);
  }
  100% {
    stroke-dashoffset: var(--circumference);
  }
}

.animate-progress-dash {
  animation: progress-dash 2s ease-in-out infinite;
}
</style>
