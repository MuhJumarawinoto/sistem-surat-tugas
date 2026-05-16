<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  src: {
    type: String,
    default: ''
  },
  alt: {
    type: String,
    default: 'Image'
  }
})

const emit = defineEmits(['close'])

const scale = ref(1)
const position = ref({ x: 0, y: 0 })
const isDragging = ref(false)
const dragStart = ref({ x: 0, y: 0 })
const imageRef = ref(null)

// Reset state when image changes
watch(() => props.src, () => {
  resetView()
})

// Reset state when modal opens
watch(() => props.show, (newValue) => {
  if (newValue) {
    resetView()
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})

function resetView() {
  scale.value = 1
  position.value = { x: 0, y: 0 }
}

function handleWheel(e) {
  e.preventDefault()
  const delta = e.deltaY > 0 ? -0.1 : 0.1
  const newScale = Math.min(Math.max(0.1, scale.value + delta), 5)
  scale.value = newScale

  // Reset position when zooming out to minimum
  if (newScale <= 1) {
    position.value = { x: 0, y: 0 }
  }
}

function handleMouseDown(e) {
  if (scale.value > 1) {
    isDragging.value = true
    dragStart.value = {
      x: e.clientX - position.value.x,
      y: e.clientY - position.value.y
    }
  }
}

function handleMouseMove(e) {
  if (isDragging.value) {
    position.value = {
      x: e.clientX - dragStart.value.x,
      y: e.clientY - dragStart.value.y
    }
  }
}

function handleMouseUp() {
  isDragging.value = false
}

function handleTouchStart(e) {
  if (e.touches.length === 2 && scale.value <= 1) {
    // Double touch - zoom in
    scale.value = 2
  } else if (e.touches.length === 1 && scale.value > 1) {
    isDragging.value = true
    dragStart.value = {
      x: e.touches[0].clientX - position.value.x,
      y: e.touches[0].clientY - position.value.y
    }
  }
}

function handleTouchMove(e) {
  if (isDragging.value && e.touches.length === 1) {
    e.preventDefault()
    position.value = {
      x: e.touches[0].clientX - dragStart.value.x,
      y: e.touches[0].clientY - dragStart.value.y
    }
  }
}

function handleTouchEnd() {
  isDragging.value = false
}

function zoomIn() {
  scale.value = Math.min(scale.value + 0.25, 5)
}

function zoomOut() {
  const newScale = Math.max(scale.value - 0.25, 1)
  scale.value = newScale
  if (newScale <= 1) {
    position.value = { x: 0, y: 0 }
  }
}

function resetZoom() {
  resetView()
}

function handleClose() {
  emit('close')
}

function handleBackdropClick(e) {
  if (e.target === e.currentTarget) {
    handleClose()
  }
}

// Keyboard shortcuts
function handleKeydown(e) {
  if (!props.show) return
  if (e.key === 'Escape') handleClose()
  if (e.key === '+' || e.key === '=') zoomIn()
  if (e.key === '-' || e.key === '_') zoomOut()
  if (e.key === '0') resetZoom()
}

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
  document.body.style.overflow = ''
})
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-90"
        @click="handleBackdropClick"
        @wheel="handleWheel"
        @mousemove="handleMouseMove"
        @mouseup="handleMouseUp"
        @mouseleave="handleMouseUp"
        @touchstart="handleTouchStart"
        @touchmove="handleTouchMove"
        @touchend="handleTouchEnd"
      >
        <!-- Close Button -->
        <button
          @click="handleClose"
          class="absolute top-4 right-4 text-white hover:text-gray-300 z-10 p-2"
        >
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>

        <!-- Image Container -->
        <div
          class="relative max-w-full max-h-full overflow-hidden"
          :style="{ cursor: scale > 1 ? (isDragging ? 'grabbing' : 'grab') : 'default' }"
          @mousedown="handleMouseDown"
        >
          <img
            ref="imageRef"
            :src="src"
            :alt="alt"
            class="max-w-full max-h-[85vh] object-contain transition-transform duration-100"
            :style="{
              transform: `scale(${scale}) translate(${position.x / scale}px, ${position.y / scale}px)`,
              cursor: scale > 1 ? 'grab' : 'default'
           }"
            draggable="false"
          />
        </div>

        <!-- Controls -->
        <div class="absolute bottom-6 left-1/2 transform -translate-x-1/2 flex items-center gap-3 bg-black bg-opacity-70 rounded-full px-6 py-3">
          <!-- Zoom Out -->
          <button
            @click="zoomOut"
            :disabled="scale <= 1"
            class="text-white hover:text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed p-1"
            title="Perkecil (-)"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
            </svg>
          </button>

          <!-- Scale Indicator -->
          <span class="text-white text-sm min-w-[60px] text-center">
            {{ Math.round(scale * 100) }}%
          </span>

          <!-- Zoom In -->
          <button
            @click="zoomIn"
            :disabled="scale >= 5"
            class="text-white hover:text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed p-1"
            title="Perbesar (+)"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </button>

          <!-- Divider -->
          <div class="w-px h-6 bg-gray-500 mx-2"></div>

          <!-- Reset -->
          <button
            @click="resetZoom"
            class="text-white hover:text-gray-300 p-1"
            title="Reset (0)"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </button>
        </div>

        <!-- Help Text -->
        <div class="absolute bottom-6 left-6 text-white text-xs bg-black bg-opacity-50 rounded px-3 py-2">
          <p>Scroll: Zoom • Drag: Geser • ESC: Tutup</p>
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

/* Prevent text selection during drag */
* {
  user-select: none;
  -webkit-user-select: none;
}
</style>
