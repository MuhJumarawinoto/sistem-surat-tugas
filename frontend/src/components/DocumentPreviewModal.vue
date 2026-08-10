<script setup>
import { ref, watch, onMounted, onUnmounted, computed } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  // For backward compatibility - accept document object
  document: {
    type: Object,
    default: null
  },
  src: {
    type: String,
    default: ''
  },
  alt: {
    type: String,
    default: 'Dokumen'
  },
  fileType: {
    type: String,
    default: '' // 'image', 'pdf', or empty for auto-detect
  }
})

const emit = defineEmits(['close'])

// Extract values from document object if provided, otherwise use direct props
const computedSrc = computed(() => {
  if (props.document?.url) return props.document.url
  return props.src
})

const computedAlt = computed(() => {
  if (props.document?.name) return props.document.name
  return props.alt
})

const computedFileType = computed(() => {
  if (props.document?.type) {
    const type = props.document.type
    if (type.includes('pdf')) return 'pdf'
    if (type.includes('image')) return 'image'
  }
  return props.fileType
})

const scale = ref(1)
const position = ref({ x: 0, y: 0 })
const isDragging = ref(false)
const dragStart = ref({ x: 0, y: 0 })
const imageRef = ref(null)
const pdfLoadError = ref(false)
const pdfObjectRef = ref(null)

const detectedFileType = computed(() => {
  if (computedFileType.value) return computedFileType.value
  if (!computedSrc.value) return 'unknown'

  const extension = computedSrc.value.split('.').pop().toLowerCase()
  if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'].includes(extension)) {
    return 'image'
  } else if (extension === 'pdf') {
    return 'pdf'
  }
  return 'unknown'
})

const isImage = computed(() => detectedFileType.value === 'image')
const isPdf = computed(() => detectedFileType.value === 'pdf')

const pdfUrl = computed(() => {
  if (!isPdf.value || !computedSrc.value) return ''
  // Add parameters to force inline viewing instead of download
  const url = new URL(computedSrc.value, window.location.origin)
  url.searchParams.set('inline', '1')
  return url.toString()
})

// Reset state when document changes
watch(() => computedSrc.value, () => {
  resetView()
  pdfLoadError.value = false
})

// Reset state when modal opens
watch(() => props.show, (newValue) => {
  if (newValue) {
    resetView()
    pdfLoadError.value = false
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
  if (!isImage.value) return
  e.preventDefault()
  const delta = e.deltaY > 0 ? -0.1 : 0.1
  const newScale = Math.min(Math.max(0.1, scale.value + delta), 5)
  scale.value = newScale

  if (newScale <= 1) {
    position.value = { x: 0, y: 0 }
  }
}

function handleMouseDown(e) {
  if (!isImage.value || scale.value <= 1) return
  isDragging.value = true
  dragStart.value = {
    x: e.clientX - position.value.x,
    y: e.clientY - position.value.y
  }
}

function handleMouseMove(e) {
  if (!isDragging.value) return
  position.value = {
    x: e.clientX - dragStart.value.x,
    y: e.clientY - dragStart.value.y
  }
}

function handleMouseUp() {
  isDragging.value = false
}

function handleTouchStart(e) {
  if (!isImage.value) return
  if (e.touches.length === 2 && scale.value <= 1) {
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
  if (!isDragging.value || e.touches.length !== 1) return
  e.preventDefault()
  position.value = {
    x: e.touches[0].clientX - dragStart.value.x,
    y: e.touches[0].clientY - dragStart.value.y
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

function downloadDocument() {
  if (!computedSrc.value) return
  const link = document.createElement('a')
  link.href = computedSrc.value
  link.download = computedAlt.value || 'dokumen'
  link.target = '_blank'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

function openPdfInNewTab() {
  if (!computedSrc.value) return
  console.log('Opening PDF in new tab:', computedSrc.value)
  window.open(computedSrc.value, '_blank')
}

function handlePdfError() {
  console.warn('PDF failed to load in object tag')
  pdfLoadError.value = true
}

// Keyboard shortcuts
function handleKeydown(e) {
  if (!props.show) return
  if (e.key === 'Escape') handleClose()
  if (isImage.value) {
    if (e.key === '+' || e.key === '=') zoomIn()
    if (e.key === '-' || e.key === '_') zoomOut()
    if (e.key === '0') resetZoom()
  }
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
        <!-- Header -->
        <div class="absolute top-0 left-0 right-0 z-10 flex items-center justify-between px-4 py-3 bg-gradient-to-b from-black/70 to-transparent">
          <div class="flex items-center gap-2">
            <span class="text-white text-sm font-medium truncate max-w-md">
              {{ computedAlt }}
            </span>
            <span v-if="isPdf" class="px-2 py-0.5 bg-red-500 text-white text-xs rounded-full">PDF</span>
            <span v-else-if="isImage" class="px-2 py-0.5 bg-blue-500 text-white text-xs rounded-full">Gambar</span>
          </div>
          <div class="flex items-center gap-2">
            <!-- Download Button -->
            <button
              @click="downloadDocument"
              class="text-white hover:text-gray-300 p-2 rounded-lg hover:bg-white/10 transition-colors"
              title="Download"
            >
              <i class="ri-download-line text-xl"></i>
            </button>
            <!-- Close Button -->
            <button
              @click="handleClose"
              class="text-white hover:text-gray-300 p-2 rounded-lg hover:bg-white/10 transition-colors"
              title="Tutup (ESC)"
            >
              <i class="ri-close-line text-xl"></i>
            </button>
          </div>
        </div>

        <!-- Content Container -->
        <div class="w-full h-full flex items-center justify-center p-4 pt-16">
          <!-- PDF Viewer -->
          <div v-if="isPdf" class="w-full h-full max-w-5xl max-h-[85vh] bg-white rounded-lg shadow-2xl overflow-hidden">
            <iframe
              :src="pdfUrl"
              class="w-full h-full border-0"
              type="application/pdf"
              credentials="include"
              @error="handlePdfError"
            ></iframe>
          </div>

          <!-- Image Viewer -->
          <div
            v-else-if="isImage"
            class="relative max-w-full max-h-full overflow-hidden"
            :style="{ cursor: scale > 1 ? (isDragging ? 'grabbing' : 'grab') : 'default' }"
            @mousedown="handleMouseDown"
          >
            <img
              ref="imageRef"
              :src="computedSrc"
              :alt="computedAlt"
              class="max-w-full max-h-[85vh] object-contain transition-transform duration-100"
              :style="{
                transform: `scale(${scale}) translate(${position.x / scale}px, ${position.y / scale}px)`,
                cursor: scale > 1 ? 'grab' : 'default'
              }"
              draggable="false"
            />
          </div>

          <!-- Unknown File Type -->
          <div v-else class="text-center text-white">
            <i class="ri-file-unknow-line text-6xl mb-4 block"></i>
            <p class="text-lg mb-4">Tipe file tidak didukung untuk preview</p>
            <button
              @click="downloadDocument"
              class="btn btn-primary gap-2"
            >
              <i class="ri-download-line"></i>
              Download File
            </button>
          </div>
        </div>

        <!-- Image Controls -->
        <div v-if="isImage" class="absolute bottom-6 left-1/2 transform -translate-x-1/2 flex items-center gap-3 bg-black/70 backdrop-blur-sm rounded-full px-6 py-3">
          <button
            @click="zoomOut"
            :disabled="scale <= 1"
            class="text-white hover:text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed p-1"
            title="Perkecil (-)"
          >
            <i class="ri-subtract-line text-xl"></i>
          </button>

          <span class="text-white text-sm min-w-[60px] text-center font-medium">
            {{ Math.round(scale * 100) }}%
          </span>

          <button
            @click="zoomIn"
            :disabled="scale >= 5"
            class="text-white hover:text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed p-1"
            title="Perbesar (+)"
          >
            <i class="ri-add-line text-xl"></i>
          </button>

          <div class="w-px h-6 bg-gray-500 mx-2"></div>

          <button
            @click="resetZoom"
            class="text-white hover:text-gray-300 p-1"
            title="Reset (0)"
          >
            <i class="ri-refresh-line text-xl"></i>
          </button>
        </div>

        <!-- Help Text -->
        <div class="absolute bottom-6 left-6 text-white text-xs bg-black/50 backdrop-blur-sm rounded px-3 py-2">
          <p v-if="isImage">Scroll: Zoom • Drag: Geser • ESC: Tutup</p>
          <p v-else>ESC: Tutup</p>
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

iframe {
  border: none;
}
</style>
