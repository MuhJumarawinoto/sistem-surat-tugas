<script setup>
import { ref, computed } from 'vue'
import LoadingSpinner from './LoadingSpinner.vue'

const props = defineProps({
  modelValue: {
    type: File,
    default: null
  },
  label: {
    type: String,
    default: 'Pilih File'
  },
  accept: {
    type: String,
    default: '.pdf,.jpg,.jpeg,.png'
  },
  maxSize: {
    type: Number,
    default: 2 * 1024 * 1024 // 2MB (sesuai server config)
  },
  preview: {
    type: Boolean,
    default: true
  },
  existingFile: {
    type: Object,
    default: null
  },
  existingFileUrl: {
    type: String,
    default: ''
  },
  uploading: {
    type: Boolean,
    default: false
  },
  uploadProgress: {
    type: Number,
    default: 0
  }
})

const emit = defineEmits(['update:modelValue', 'fileSelected'])

const isDragging = ref(false)
const fileInput = ref(null)
const error = ref('')

const hasFile = computed(() => props.modelValue || props.existingFile)
const displayFileName = computed(() => {
  if (props.modelValue) return props.modelValue.name
  if (props.existingFile) return props.existingFile.file_name
  return ''
})

const isImage = computed(() => {
  if (props.modelValue) {
    return props.modelValue.type?.startsWith('image/')
  }
  if (props.existingFile) {
    if (props.existingFile.file_type?.startsWith('image/')) return true
    const ext = props.existingFile.file_name?.toLowerCase() || ''
    return ['.jpg', '.jpeg', '.png', '.gif', '.webp'].some(e => ext.endsWith(e))
  }
  return false
})

const previewUrl = computed(() => {
  if (props.modelValue && props.modelValue.type?.startsWith('image/')) {
    try {
      return URL?.createObjectURL?.(props.modelValue) || ''
    } catch {
      return ''
    }
  }
  if (props.existingFile && props.existingFileUrl) {
    return props.existingFileUrl
  }
  return null
})

function onDragOver(e) {
  e.preventDefault()
  isDragging.value = true
}

function onDragLeave(e) {
  e.preventDefault()
  isDragging.value = false
}

function onDrop(e) {
  e.preventDefault()
  isDragging.value = false
  error.value = ''

  const files = e.dataTransfer.files
  if (files.length > 0) {
    handleFile(files[0])
  }
}

function onFileSelect(e) {
  error.value = ''
  const file = e.target.files?.[0]
  if (file) {
    handleFile(file)
  }
}

function handleFile(file) {
  // Check file type
  const acceptedTypes = props.accept.split(',').map(t => t.trim())
  const fileExt = '.' + file.name.split('.').pop().toLowerCase()
  const isValidType = acceptedTypes.some(t => {
    if (t.startsWith('.')) return fileExt === t.toLowerCase()
    if (t.includes('/')) return file.type === t
    return false
  })

  if (!isValidType) {
    error.value = `Tipe file tidak valid. Yang diterima: ${props.accept}`
    return
  }

  // Check file size
  if (file.size > props.maxSize) {
    const maxSizeMB = (props.maxSize / (1024 * 1024)).toFixed(0)
    error.value = `Ukuran file terlalu besar. Maksimum ${maxSizeMB}MB`
    return
  }

  emit('update:modelValue', file)
  emit('fileSelected', file)
}

function triggerFileInput() {
  fileInput.value?.click()
}

function removeFile() {
  emit('update:modelValue', null)
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}
</script>

<template>
  <div class="w-full">
    <!-- Drop Zone -->
    <div
      :class="[
        'border-2 border-dashed rounded p-2 text-center transition-colors cursor-pointer',
        isDragging ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-gray-400'
      ]"
      @dragover="onDragOver"
      @dragleave="onDragLeave"
      @drop="onDrop"
      @click="triggerFileInput"
    >
      <input
        ref="fileInput"
        type="file"
        :accept="accept"
        @change="onFileSelect"
        class="hidden"
      />

      <!-- No File State -->
      <div v-if="!hasFile" class="py-2">
        <svg class="mx-auto h-8 w-8 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
          <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        <p class="mt-1 text-xs text-gray-600">
          <span class="font-medium text-blue-600 hover:text-blue-700">Klik untuk upload</span>
          atau drag & drop
        </p>
        <p class="mt-0.5 text-xs text-gray-500">
          PDF, JPG, PNG (Max {{ (maxSize / (1024 * 1024)).toFixed(0) }}MB)
        </p>
      </div>

      <!-- Has File State -->
      <div v-else class="py-1">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-2 flex-1 min-w-0">
            <!-- File Icon / Upload Progress -->
            <div class="flex-shrink-0 relative">
              <svg v-if="!isImage && !uploading" class="h-8 w-8 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
              </svg>
              <img v-else-if="!uploading" :src="previewUrl" class="h-8 w-8 object-cover rounded" />
              <LoadingSpinner v-else type="progress" :progress="uploadProgress" show-percent size="sm" color="blue" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-medium text-gray-900 truncate">
                {{ displayFileName }}
              </p>
              <p v-if="uploading" class="text-xs text-blue-600">
                Mengupload... {{ uploadProgress }}%
              </p>
              <p v-else-if="modelValue" class="text-xs text-gray-500">
                {{ (modelValue.size / 1024 / 1024).toFixed(2) }} MB
              </p>
              <p v-else-if="existingFile" class="text-xs text-green-600">
                File yang ada
              </p>
            </div>
          </div>
          <button
            v-if="!uploading"
            type="button"
            @click.stop="removeFile"
            class="ml-1 flex-shrink-0 text-gray-400 hover:text-red-500 p-1"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Upload Progress Bar -->
        <div v-if="uploading" class="mt-1">
          <div class="w-full bg-gray-200 rounded-full h-1.5">
            <div
              class="bg-blue-600 h-1.5 rounded-full transition-all duration-300 ease-out"
              :style="{ width: `${uploadProgress}%` }"
            />
          </div>
        </div>
      </div>

      <!-- Error Message -->
      <p v-if="error" class="mt-1 text-xs text-red-600">
        {{ error }}
      </p>
    </div>

    <!-- Preview Image (Optional) -->
    <div v-if="preview && isImage && hasFile" class="mt-2">
      <img
        :src="previewUrl"
        :alt="displayFileName"
        class="max-w-xs max-h-32 rounded border object-cover cursor-pointer hover:opacity-80"
        @click="$emit('preview', previewUrl)"
      />
    </div>
  </div>
</template>
