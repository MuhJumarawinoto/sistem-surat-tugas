<script setup>
import { ref } from 'vue'

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  requirements: {
    type: Array,
    default: () => []
  },
  notes: {
    type: String,
    default: ''
  }
})

const showTooltip = ref(false)
</script>

<template>
  <div class="relative inline-block ml-2">
    <button
      type="button"
      @click="showTooltip = !showTooltip"
      class="inline-flex items-center justify-center w-5 h-5 text-blue-600 bg-blue-100 rounded-full hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-blue-500"
      tabindex="0"
    >
      <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
      </svg>
    </button>

    <!-- Tooltip Content -->
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-1"
    >
      <div
        v-if="showTooltip"
        class="absolute z-50 w-80 p-4 mt-2 bg-white rounded-lg shadow-lg border border-gray-200 left-0 sm:left-auto sm:right-0"
      >
        <div class="flex justify-between items-start mb-3">
          <h4 class="text-sm font-semibold text-gray-900">{{ title }}</h4>
          <button
            @click="showTooltip = false"
            class="text-gray-400 hover:text-gray-600"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div v-if="requirements.length > 0" class="mb-3">
          <p class="text-xs font-medium text-gray-700 mb-2">Ketentuan:</p>
          <ul class="text-xs text-gray-600 space-y-1">
            <li v-for="(req, index) in requirements" :key="index" class="flex items-start">
              <svg class="w-3.5 h-3.5 text-blue-500 mr-1.5 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
              </svg>
              <span>{{ req }}</span>
            </li>
          </ul>
        </div>

        <div v-if="notes" class="p-2 bg-yellow-50 rounded border border-yellow-200">
          <p class="text-xs text-yellow-800">
            <span class="font-medium">Catatan:</span> {{ notes }}
          </p>
        </div>

        <div class="mt-3 pt-3 border-t border-gray-100">
          <p class="text-xs text-gray-500">
            <span class="font-medium">Format:</span> PDF, JPG, JPEG, PNG
          </p>
          <p class="text-xs text-gray-500">
            <span class="font-medium">Ukuran Maks:</span> 5MB per file
          </p>
          <p class="text-xs text-orange-600 font-medium">
            Maksimal 1 file untuk setiap jenis dokumen
          </p>
        </div>
      </div>
    </Transition>

    <!-- Backdrop -->
    <div
      v-if="showTooltip"
      @click="showTooltip = false"
      class="fixed inset-0 z-40"
    />
  </div>
</template>
